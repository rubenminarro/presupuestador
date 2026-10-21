<?php

namespace App\Services;

use App\Exceptions\VehicleCatalogException;
use App\Models\Brand;
use App\Models\VehicleModel;
use Illuminate\Support\Facades\DB;

/**
 * Bajas del catálogo de marcas y modelos: no se eliminan si hay vehículos vigentes que los usan, ni una marca con
 * modelos vigentes.
 */
class VehicleCatalogService
{
    public function deleteBrand(Brand $brand): void
    {
        DB::transaction(function () use ($brand) {

            if ($brand->vehicles()->exists()) {
                throw new VehicleCatalogException(
                    'No se puede eliminar la marca porque hay vehículos registrados con ella.',
                    409,
                    'BRAND_HAS_VEHICLES'
                );
            }

            // Sin esto sus modelos quedarían sin marca vigente.
            if ($brand->vehicleModels()->exists()) {
                throw new VehicleCatalogException(
                    'No se puede eliminar la marca porque tiene modelos registrados. Elimine primero sus modelos.',
                    409,
                    'BRAND_HAS_MODELS'
                );
            }

            // El nombre queda libre para otra marca.
            $brand->name = $brand->name . '//deleted_' . now()->timestamp;
            $brand->save();

            $brand->delete();
        });
    }

    public function deleteVehicleModel(VehicleModel $vehicleModel): void
    {
        DB::transaction(function () use ($vehicleModel) {

            if ($vehicleModel->vehicles()->exists()) {
                throw new VehicleCatalogException(
                    'No se puede eliminar el modelo porque hay vehículos registrados con él.',
                    409,
                    'VEHICLE_MODEL_HAS_VEHICLES'
                );
            }

            // El nombre queda libre para otro modelo.
            $vehicleModel->name = $vehicleModel->name . '//deleted_' . now()->timestamp;
            $vehicleModel->save();

            $vehicleModel->delete();
        });
    }
}
