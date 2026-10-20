<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Models\PurchaseItem;
use App\Models\WorkOrderItem;
use App\Models\WorkOrderPart;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Alias para referencias de inventario y orígenes de líneas de factura.
        // Sin enforce: otras relaciones polimórficas (Spatie) siguen guardando
        // el nombre de clase.
        Relation::morphMap([
            'work_order_part' => WorkOrderPart::class,
            'work_order_item' => WorkOrderItem::class,
            'purchase_item' => PurchaseItem::class,
        ]);

        // Bypass implícito para el rol 'administrador'
        Gate::before(function ($user, $ability) {
            return $user->hasRole('administrador') ? true : null;
        });
    }
}
