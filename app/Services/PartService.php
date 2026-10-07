<?php

namespace App\Services;

use App\Enums\PartStatus;
use App\Exceptions\PartException;
use App\Exceptions\SupplierException;
use App\Models\Part;
use App\Models\PartCategory;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class PartService
{
    public function create(array $data): Part
    {
        return DB::transaction(function () use ($data) {

            $this->ensureCategoryActive((int) $data['part_category_id']);

            $part = Part::create([
                ...$data,
                'code' => $this->generateCode(),
                'status' => PartStatus::ACTIVE,
            ]);

            return $part->load(['category', 'suppliers']);
        });
    }

    public function update(Part $part, array $data): Part
    {
        return DB::transaction(function () use ($part, $data) {

            $part->refresh();

            $this->ensureNotDiscontinued($part);

            if (
                isset($data['part_category_id']) &&
                (int) $data['part_category_id'] !== (int) $part->part_category_id
            ) {
                $this->ensureCategoryActive((int) $data['part_category_id']);
            }

            $part->update($data);

            return $part->load(['category', 'suppliers']);
        });
    }

    public function changeStatus(Part $part, PartStatus $status): Part
    {
        return DB::transaction(function () use ($part, $status) {

            $part->refresh();

            $this->ensureNotDiscontinued($part);

            if (!$part->status->canTransitionTo($status)) {
                throw new PartException(
                    "El repuesto no puede pasar de {$part->status->label()} a {$status->label()}.",
                    422,
                    'PART_INVALID_STATUS_TRANSITION'
                );
            }

            $part->update([
                'status' => $status,
            ]);

            return $part->load(['category', 'suppliers']);
        });
    }

    public function delete(Part $part): void
    {
        DB::transaction(function () use ($part) {
            $part->delete();
        });
    }

    public function attachSupplier(Part $part, Supplier $supplier, array $data): Part
    {
        return DB::transaction(function () use ($part, $supplier, $data) {

            $part->refresh();

            $this->ensureNotDiscontinued($part);

            if (!$supplier->is_active) {
                throw new SupplierException(
                    'El proveedor seleccionado está inactivo.',
                    422,
                    'SUPPLIER_INACTIVE'
                );
            }

            if ($part->suppliers()->whereKey($supplier->id)->exists()) {
                throw new PartException(
                    'El proveedor ya está vinculado a este repuesto.',
                    409,
                    'PART_SUPPLIER_ALREADY_ATTACHED'
                );
            }

            if (!empty($data['is_preferred'])) {
                $this->clearPreferredSupplier($part);
            }

            $part->suppliers()->attach($supplier->id, [
                'supplier_part_code' => $data['supplier_part_code'] ?? null,
                'last_cost' => $data['last_cost'] ?? null,
                'is_preferred' => $data['is_preferred'] ?? false,
            ]);

            return $part->load(['category', 'suppliers']);
        });
    }

    public function updateSupplier(Part $part, Supplier $supplier, array $data): Part
    {
        return DB::transaction(function () use ($part, $supplier, $data) {

            $part->refresh();

            $this->ensureNotDiscontinued($part);

            if (!empty($data['is_preferred'])) {
                $this->clearPreferredSupplier($part);
            }

            $part->suppliers()->updateExistingPivot($supplier->id, $data);

            return $part->load(['category', 'suppliers']);
        });
    }

    public function detachSupplier(Part $part, Supplier $supplier): Part
    {
        return DB::transaction(function () use ($part, $supplier) {

            $part->suppliers()->detach($supplier->id);

            return $part->load(['category', 'suppliers']);
        });
    }

    public function deleteCategory(PartCategory $category): void
    {
        DB::transaction(function () use ($category) {

            if ($category->parts()->withTrashed()->exists()) {
                throw new PartException(
                    'No se puede eliminar la categoría porque tiene repuestos asociados.',
                    409,
                    'PART_CATEGORY_HAS_PARTS'
                );
            }

            $category->delete();
        });
    }

    private function ensureCategoryActive(int $categoryId): void
    {
        $category = PartCategory::findOrFail($categoryId);

        if (!$category->is_active) {
            throw new PartException(
                'La categoría seleccionada está inactiva.',
                422,
                'PART_CATEGORY_INACTIVE'
            );
        }
    }

    private function ensureNotDiscontinued(Part $part): void
    {
        if ($part->status === PartStatus::DISCONTINUED) {
            throw new PartException(
                'El repuesto está descontinuado y no puede modificarse.',
                422,
                'PART_DISCONTINUED'
            );
        }
    }

    private function clearPreferredSupplier(Part $part): void
    {
        DB::table('part_supplier')
            ->where('part_id', $part->id)
            ->update(['is_preferred' => false]);
    }

    private function generateCode(): string
    {
        $nextId = (Part::withTrashed()->max('id') ?? 0) + 1;

        return 'REP-' . str_pad(
            $nextId,
            6,
            '0',
            STR_PAD_LEFT
        );
    }
}
