<?php

namespace App\Services;

use App\Models\ReceptionCheckList;
use App\Models\ReceptionCheckListItem;
use App\Models\CheckListItem;

class ReceptionChecklistService
{
    public function generateChecklistItems(ReceptionCheckList $checkList, array $serviceCategoryIds): void
    {
        $items = CheckListItem::whereHas(
            'serviceCategories',
            fn ($query) => $query->whereIn('service_categories.id', $serviceCategoryIds)
        )
        ->distinct()
        ->get();

        foreach ($items as $item) {
            ReceptionCheckListItem::create([
                'reception_check_list_id' => $checkList->id,
                'check_list_item_id'      => $item->id,
                'value'                   => null,
                'observation'             => null,
            ]);
        }
    }
}