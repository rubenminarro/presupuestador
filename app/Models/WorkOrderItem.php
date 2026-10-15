<?php

namespace App\Models;

use App\Enums\WorkOrderItemStatus;
use App\Enums\WorkOrderItemType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkOrderItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'work_order_id',
        'budget_item_id',
        'type',
        'description',
        'quantity',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => WorkOrderItemType::class,
            'status' => WorkOrderItemStatus::class,
            'quantity' => 'decimal:2',
        ];
    }

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function budgetItem()
    {
        return $this->belongsTo(BudgetItem::class);
    }

    public function parts()
    {
        return $this->hasMany(WorkOrderPart::class);
    }

    public function mechanicAssignments()
    {
        // chaperone: cada asignación conoce su ítem (y su budgetItem) sin consultas extra.
        return $this->hasMany(WorkOrderItemMechanic::class)->chaperone('workOrderItem');
    }

    /**
     * Mecánicos asignados (vía work_order_item_mechanics). Permite el scoped
     * binding de /items/{item}/mechanics/{mechanic}: 404 si no está asignado.
     */
    public function mechanics()
    {
        return $this->belongsToMany(Mechanic::class, 'work_order_item_mechanics')
            ->withPivot(['id', 'share_percentage', 'commission_percentage', 'hours', 'hour_cost'])
            ->withTimestamps();
    }

    /**
     * Solo la mano de obra y los servicios generan comisión.
     */
    public function isCommissionable(): bool
    {
        return in_array($this->type, [WorkOrderItemType::LABOR, WorkOrderItemType::SERVICE], true);
    }
}