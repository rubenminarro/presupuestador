<?php

namespace App\Services;

use App\Enums\ServiceStatus;
use App\Exceptions\ServiceException;
use App\Models\Service;
use Illuminate\Support\Facades\DB;

/**
 * Catálogo de servicios del taller (lista de precios).
 */
class ServiceCatalogService
{
    public function create(array $data): Service
    {
        return DB::transaction(function () use ($data) {

            $service = Service::create([
                ...$data,
                'code' => $this->generateCode(),
                'status' => ServiceStatus::ACTIVE,
            ]);

            return $service->load('category');
        });
    }

    public function update(Service $service, array $data): Service
    {
        return DB::transaction(function () use ($service, $data) {

            $service->update($data);

            return $service->load('category');
        });
    }

    public function activate(Service $service): Service
    {
        return $this->transitionStatus($service, ServiceStatus::ACTIVE);
    }

    public function deactivate(Service $service): Service
    {
        return $this->transitionStatus($service, ServiceStatus::INACTIVE);
    }

    public function delete(Service $service): void
    {
        DB::transaction(function () use ($service) {

            if ($service->budgetItems()->exists()) {
                throw new ServiceException(
                    'No se puede eliminar el servicio porque está en uso en presupuestos. Desactívelo en su lugar.',
                    409,
                    'SERVICE_IN_USE'
                );
            }

            $service->delete();
        });
    }

    private function transitionStatus(Service $service, ServiceStatus $status): Service
    {
        return DB::transaction(function () use ($service, $status) {

            $service->refresh();

            if (!$service->status->canTransitionTo($status)) {
                throw new ServiceException(
                    "El servicio ya está {$status->label()}.",
                    422,
                    'SERVICE_INVALID_STATUS_TRANSITION'
                );
            }

            $service->update(['status' => $status]);

            return $service->load('category');
        });
    }

    private function generateCode(): string
    {
        $nextId = (Service::withTrashed()->max('id') ?? 0) + 1;

        return 'SRV-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
    }
}
