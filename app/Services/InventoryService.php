<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Enums\PartStatus;
use App\Exceptions\InventoryException;
use App\Exceptions\SupplierException;
use App\Models\InventoryMovement;
use App\Models\Part;
use App\Models\PartStock;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\Decimal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    private const QUANTITY_SCALE = 3;
    private const COST_SCALE = 4;
    private const WORK_SCALE = 8;

    public function registerEntry(array $data, User $user): InventoryMovement
    {
        return DB::transaction(function () use ($data, $user) {

            $type = InventoryMovementType::from($data['type']);

            if (!$type->isManualEntry()) {
                throw $this->invalidType($type);
            }

            $part = Part::findOrFail($data['part_id']);

            if ($part->status === PartStatus::DISCONTINUED) {
                throw new InventoryException(
                    'El repuesto está descontinuado y no admite entradas de stock.',
                    422,
                    'PART_DISCONTINUED_NO_ENTRY'
                );
            }

            $quantity = $this->normalizeQuantity($part, $data['quantity']);
            $unitCost = Decimal::of($data['unit_cost'], self::COST_SCALE);
            $supplier = $this->activeSupplier($data['supplier_id'] ?? null);

            $stock = $this->lockStock($part);

            if ($type === InventoryMovementType::INITIAL && $part->movements()->exists()) {
                throw new InventoryException(
                    'El repuesto ya tiene movimientos; el stock inicial solo puede cargarse una vez.',
                    409,
                    'INVENTORY_INITIAL_ALREADY_REGISTERED'
                );
            }

            $movement = $this->applyMovement($stock, $type, $quantity, $unitCost, $user, [
                'supplier_id' => $supplier?->id,
                'document_number' => $data['document_number'] ?? null,
                'reason' => $data['reason'] ?? null,
            ]);

            if (
                $type === InventoryMovementType::PURCHASE &&
                $supplier &&
                $part->suppliers()->whereKey($supplier->id)->exists()
            ) {
                $part->suppliers()->updateExistingPivot($supplier->id, [
                    'last_cost' => Decimal::round($unitCost, 2),
                ]);
            }

            return $this->loadMovement($movement);
        });
    }

    public function registerExit(array $data, User $user): InventoryMovement
    {
        return DB::transaction(function () use ($data, $user) {

            $type = InventoryMovementType::from($data['type']);

            if (!$type->isManualExit()) {
                throw $this->invalidType($type);
            }

            $part = Part::findOrFail($data['part_id']);

            $quantity = $this->normalizeQuantity($part, $data['quantity']);
            $supplier = $this->activeSupplier($data['supplier_id'] ?? null);

            $stock = $this->lockStock($part);

            $this->ensureAvailable($stock, $quantity);

            $movement = $this->applyMovement($stock, $type, $quantity, null, $user, [
                'supplier_id' => $supplier?->id,
                'document_number' => $data['document_number'] ?? null,
                'reason' => $data['reason'],
            ]);

            return $this->loadMovement($movement);
        });
    }

    /**
     * Ajuste por conteo físico: se informa la cantidad contada y
     * el sistema registra la diferencia como ajuste positivo o negativo.
     */
    public function adjust(array $data, User $user): InventoryMovement
    {
        return DB::transaction(function () use ($data, $user) {

            $part = Part::findOrFail($data['part_id']);

            $counted = $this->normalizeQuantity($part, $data['counted_quantity'], allowZero: true);

            $stock = $this->lockStock($part);

            $difference = bcsub($counted, $stock->quantity_on_hand, self::QUANTITY_SCALE);

            if (bccomp($difference, '0', self::QUANTITY_SCALE) === 0) {
                throw new InventoryException(
                    'La cantidad contada coincide con el stock actual; no hay diferencia para ajustar.',
                    422,
                    'INVENTORY_NO_DIFFERENCE'
                );
            }

            if (bccomp($counted, $stock->quantity_reserved, self::QUANTITY_SCALE) < 0) {
                throw new InventoryException(
                    "La cantidad contada no puede ser menor a la reservada ({$stock->quantity_reserved}).",
                    422,
                    'STOCK_BELOW_RESERVED',
                    ['counted_quantity' => ["Stock reservado: {$stock->quantity_reserved}."]]
                );
            }

            $isPositive = bccomp($difference, '0', self::QUANTITY_SCALE) > 0;

            $type = $isPositive
                ? InventoryMovementType::ADJUSTMENT_IN
                : InventoryMovementType::ADJUSTMENT_OUT;

            $unitCost = $isPositive && isset($data['unit_cost'])
                ? Decimal::of($data['unit_cost'], self::COST_SCALE)
                : null;

            $movement = $this->applyMovement(
                $stock,
                $type,
                ltrim($difference, '-'),
                $unitCost,
                $user,
                ['reason' => $data['reason']]
            );

            return $this->loadMovement($movement);
        });
    }

    /**
     * Reserva stock disponible (uso interno, FASE 12 — WorkOrderPart).
     */
    public function reserve(Part $part, int|float|string $quantity): PartStock
    {
        return DB::transaction(function () use ($part, $quantity) {

            $quantity = $this->normalizeQuantity($part, $quantity);

            $stock = $this->lockStock($part);

            $this->ensureAvailable($stock, $quantity);

            $stock->update([
                'quantity_reserved' => bcadd($stock->quantity_reserved, $quantity, self::QUANTITY_SCALE),
            ]);

            return $stock;
        });
    }

    /**
     * Libera stock reservado (uso interno, FASE 12 — WorkOrderPart).
     */
    public function release(Part $part, int|float|string $quantity): PartStock
    {
        return DB::transaction(function () use ($part, $quantity) {

            $quantity = $this->normalizeQuantity($part, $quantity);

            $stock = $this->lockStock($part);

            if (bccomp($quantity, $stock->quantity_reserved, self::QUANTITY_SCALE) > 0) {
                throw new InventoryException(
                    "No se puede liberar {$quantity}; el stock reservado es {$stock->quantity_reserved}.",
                    422,
                    'INVENTORY_RELEASE_EXCEEDS_RESERVED'
                );
            }

            $stock->update([
                'quantity_reserved' => bcsub($stock->quantity_reserved, $quantity, self::QUANTITY_SCALE),
            ]);

            return $stock;
        });
    }

    /**
     * Consumo desde una OT: libera lo reservado y registra la salida de lo
     * realmente usado bajo el mismo bloqueo. Lo usado puede diferir de lo
     * reservado; el excedente se toma del disponible.
     */
    public function consumeReservation(
        Part $part,
        int|float|string $reserved,
        int|float|string $used,
        User $user,
        Model $reference,
        ?string $reason = null
    ): InventoryMovement {
        return DB::transaction(function () use ($part, $reserved, $used, $user, $reference, $reason) {

            $reserved = Decimal::of($reserved, self::QUANTITY_SCALE);
            $used = $this->normalizeQuantity($part, $used);

            $stock = $this->lockStock($part);

            if (bccomp($reserved, $stock->quantity_reserved, self::QUANTITY_SCALE) > 0) {
                throw new InventoryException(
                    "No se puede liberar {$reserved}; el stock reservado es {$stock->quantity_reserved}.",
                    422,
                    'INVENTORY_RELEASE_EXCEEDS_RESERVED'
                );
            }

            $stock->quantity_reserved = bcsub($stock->quantity_reserved, $reserved, self::QUANTITY_SCALE);

            $this->ensureAvailable($stock, $used);

            return $this->applyMovement($stock, InventoryMovementType::WORK_ORDER_OUT, $used, null, $user, [
                'reference_type' => $reference->getMorphClass(),
                'reference_id' => $reference->getKey(),
                'reason' => $reason,
            ]);
        });
    }

    /**
     * Devolución desde una OT, valuada al costo con que se consumió.
     * Se acepta aunque el repuesto esté descontinuado: es stock propio que vuelve.
     */
    public function returnToStock(
        Part $part,
        int|float|string $quantity,
        string $unitCost,
        User $user,
        Model $reference,
        string $reason
    ): InventoryMovement {
        return DB::transaction(function () use ($part, $quantity, $unitCost, $user, $reference, $reason) {

            $quantity = $this->normalizeQuantity($part, $quantity);

            $stock = $this->lockStock($part);

            return $this->applyMovement(
                $stock,
                InventoryMovementType::WORK_ORDER_RETURN,
                $quantity,
                Decimal::of($unitCost, self::COST_SCALE),
                $user,
                [
                    'reference_type' => $reference->getMorphClass(),
                    'reference_id' => $reference->getKey(),
                    'reason' => $reason,
                ]
            );
        });
    }

    /**
     * Único punto que modifica part_stocks: registra el movimiento y
     * actualiza el saldo y el costo promedio en la misma transacción.
     * El stock debe llegar bloqueado (lockStock).
     */
    private function applyMovement(
        PartStock $stock,
        InventoryMovementType $type,
        string $quantity,
        ?string $unitCost,
        User $user,
        array $attributes = []
    ): InventoryMovement {
        $onHand = $stock->quantity_on_hand;
        $averageCost = $stock->average_cost;

        if ($type->isEntry()) {
            $unitCost ??= $averageCost;
            $newOnHand = bcadd($onHand, $quantity, self::QUANTITY_SCALE);
            $newAverageCost = $this->weightedAverage($onHand, $averageCost, $quantity, $unitCost);
            $signedQuantity = $quantity;
        } else {
            $unitCost = $averageCost;
            $newOnHand = bcsub($onHand, $quantity, self::QUANTITY_SCALE);
            $newAverageCost = $averageCost;
            $signedQuantity = bcmul($quantity, '-1', self::QUANTITY_SCALE);
        }

        $movement = InventoryMovement::create([
            ...$attributes,
            'part_id' => $stock->part_id,
            'warehouse_id' => $stock->warehouse_id,
            'type' => $type,
            'quantity' => $signedQuantity,
            'unit_cost' => $unitCost,
            'total_cost' => Decimal::round(bcmul($signedQuantity, $unitCost, self::WORK_SCALE), 2),
            'balance_after' => $newOnHand,
            'average_cost_after' => $newAverageCost,
            'created_by' => $user->id,
        ]);

        $stock->update([
            'quantity_on_hand' => $newOnHand,
            'average_cost' => $newAverageCost,
            'last_movement_at' => $movement->created_at,
        ]);

        return $movement;
    }

    private function weightedAverage(
        string $onHand,
        string $averageCost,
        string $quantity,
        string $unitCost
    ): string {
        if (bccomp($onHand, '0', self::QUANTITY_SCALE) <= 0) {
            return $unitCost;
        }

        $currentValue = bcmul($onHand, $averageCost, self::WORK_SCALE);
        $entryValue = bcmul($quantity, $unitCost, self::WORK_SCALE);
        $totalQuantity = bcadd($onHand, $quantity, self::QUANTITY_SCALE);

        return Decimal::round(
            bcdiv(bcadd($currentValue, $entryValue, self::WORK_SCALE), $totalQuantity, self::WORK_SCALE),
            self::COST_SCALE
        );
    }

    private function lockStock(Part $part): PartStock
    {
        $warehouseId = Warehouse::getDefault()->id;

        // Respaldo: los repuestos nuevos ya nacen con su fila de stock (PartService::create).
        PartStock::firstOrCreate([
            'part_id' => $part->id,
            'warehouse_id' => $warehouseId,
        ]);

        return PartStock::where('part_id', $part->id)
            ->where('warehouse_id', $warehouseId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function normalizeQuantity(Part $part, int|float|string $value, bool $allowZero = false): string
    {
        $quantity = Decimal::of($value, self::QUANTITY_SCALE);

        $comparison = bccomp($quantity, '0', self::QUANTITY_SCALE);

        if ($comparison < 0 || (!$allowZero && $comparison === 0)) {
            throw new InventoryException(
                'La cantidad debe ser mayor a cero.',
                422,
                'INVENTORY_INVALID_QUANTITY'
            );
        }

        if (!$part->unit->allowsFractions() && Decimal::hasFraction($quantity)) {
            throw new InventoryException(
                "El repuesto se mide en {$part->unit->label()} y no admite cantidades fraccionadas.",
                422,
                'INVENTORY_FRACTIONAL_QUANTITY_NOT_ALLOWED'
            );
        }

        return $quantity;
    }

    private function ensureAvailable(PartStock $stock, string $quantity): void
    {
        $available = $stock->availableQuantity();

        if (bccomp($quantity, $available, self::QUANTITY_SCALE) > 0) {
            throw new InventoryException(
                "Stock insuficiente. Disponible: {$available}, solicitado: {$quantity}.",
                422,
                'INSUFFICIENT_STOCK',
                ['quantity' => ["Stock disponible: {$available}."]]
            );
        }
    }

    private function activeSupplier(?int $supplierId): ?Supplier
    {
        if ($supplierId === null) {
            return null;
        }

        $supplier = Supplier::findOrFail($supplierId);

        if (!$supplier->is_active) {
            throw new SupplierException(
                'El proveedor seleccionado está inactivo.',
                422,
                'SUPPLIER_INACTIVE'
            );
        }

        return $supplier;
    }

    private function invalidType(InventoryMovementType $type): InventoryException
    {
        return new InventoryException(
            "El tipo de movimiento {$type->label()} no puede registrarse en esta operación.",
            422,
            'INVENTORY_INVALID_MOVEMENT_TYPE'
        );
    }

    private function loadMovement(InventoryMovement $movement): InventoryMovement
    {
        return $movement->load(['part.category', 'warehouse', 'supplier', 'creator']);
    }
}
