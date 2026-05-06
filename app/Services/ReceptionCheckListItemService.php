<?php

namespace App\Services;

use App\Models\ReceptionCheckList;

class ReceptionCheckListItemService
{
    public function calculateCheckListStatus(ReceptionCheckList $checkList)
    {
        $items = $checkList->items;
        $total = $items->count();

        if ($total === 0) return 'pending';

        $completed = $items->whereNotNull('value')->count();
        
        $requiredItems = $items->filter(fn($item) => $item->checkListItem->required);
        $requiredTotal = $requiredItems->count();
        $requiredCompleted = $requiredItems->whereNotNull('value')->count();

        $percentage = ($completed / $total) * 100;

        if ($completed === 0) {
            return 'pending';
        }

        if ($requiredCompleted < $requiredTotal) {
            return 'in_progress';
        }

        return $percentage >= 20 ? 'completed' : 'in_progress';
    }
}