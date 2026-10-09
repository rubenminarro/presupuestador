<?php

namespace App\Enums;

enum InventoryMovementType: string
{
    case INITIAL = 'initial';
    case PURCHASE = 'purchase';
    case ADJUSTMENT_IN = 'adjustment_in';
    case ADJUSTMENT_OUT = 'adjustment_out';
    case SUPPLIER_RETURN = 'supplier_return';
    case LOSS = 'loss';
    case WORK_ORDER_OUT = 'work_order_out';
    case WORK_ORDER_RETURN = 'work_order_return';

    public function label(): string {
        return match($this) {
            self::INITIAL => 'Stock inicial',
            self::PURCHASE => 'Compra',
            self::ADJUSTMENT_IN => 'Ajuste positivo',
            self::ADJUSTMENT_OUT => 'Ajuste negativo',
            self::SUPPLIER_RETURN => 'Devolución a proveedor',
            self::LOSS => 'Baja / merma',
            self::WORK_ORDER_OUT => 'Consumo en OT',
            self::WORK_ORDER_RETURN => 'Devolución desde OT',
        };
    }

    public function direction(): string
    {
        return $this->isEntry() ? 'in' : 'out';
    }

    public function isEntry(): bool
    {
        return in_array($this, [
            self::INITIAL,
            self::PURCHASE,
            self::ADJUSTMENT_IN,
            self::WORK_ORDER_RETURN,
        ], true);
    }

    public function isManualEntry(): bool
    {
        return in_array($this, self::manualEntries(), true);
    }

    public function isManualExit(): bool
    {
        return in_array($this, self::manualExits(), true);
    }

    /**
     * Entradas que pueden registrarse desde POST /inventory/entries.
     */
    public static function manualEntries(): array
    {
        return [self::INITIAL, self::PURCHASE];
    }

    /**
     * Salidas que pueden registrarse desde POST /inventory/exits.
     */
    public static function manualExits(): array
    {
        return [self::SUPPLIER_RETURN, self::LOSS];
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
