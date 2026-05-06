<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ReceptionCheckList;
use App\Models\ReceptionCheckListItem;
use App\Http\Requests\UpdateReceptionCheckListRequest;
use App\Http\Resources\ReceptionCheckListResource;
use App\Services\ReceptionCheckListItemService;

class ReceptionCheckListController extends Controller
{
   
    public function show(ReceptionCheckList $receptionCheckList)
    {
        $receptionCheckList->load('items.checkListItem');

        return response()->json([
            'success' => true,
            'status' => 200,
            'message' => 'Checklist obtenido.',
            'data' => new ReceptionCheckListResource($receptionCheckList),
        ]);
    }

    public function update(UpdateReceptionCheckListRequest $request, ReceptionCheckList $receptionCheckList, ReceptionCheckListItemService $service)
    {
        $data = $request->validated();

        foreach ($data['items'] as $item) {
            ReceptionCheckListItem::updateOrCreate(
                [
                    'reception_check_list_id' => $receptionCheckList->id,
                    'check_list_item_id' => $item['check_list_item_id'],
                ],
                [
                    'value' => $item['value'] ?? null,
                    'observation' => $item['observation'] ?? null,
                ]
            );
        }

        $receptionCheckList->load('items.checkListItem');

        $status = $service->calculateCheckListStatus($receptionCheckList);

        $receptionCheckList->update(['status' => $status]);

        $receptionCheckList->load('items.checkListItem');

        return response()->json([
            'success' => true,
            'status' => 200,
            'message' => 'Checklist actualizado correctamente.',
            'data' => new ReceptionCheckListResource($receptionCheckList),
        ]);
    }
}
