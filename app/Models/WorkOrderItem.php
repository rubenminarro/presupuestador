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
}