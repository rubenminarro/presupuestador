<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ReceptionCheckList;
use App\Models\ReceptionCheckListItem;
use App\Http\Requests\StoreReceptionCheckListRequest;
use App\Http\Requests\UpdateReceptionChecklistRequest;
use App\Http\Resources\ReceptionCheckListResource;

class ReceptionCheckListController extends Controller
{
    public function store(StoreReceptionCheckListRequest $request)
    {
        $data = $request->validated();

        $check_list = ReceptionCheckList::create([
            'reception_id' => $data['reception_id'],
        ]);

        foreach ($data['items'] as $item) {
            ReceptionCheckListItem::create([
                'reception_check_list_id' => $check_list->id,
                'check_list_item_id' => $item['check_list_item_id'],
                'value' => $item['value'] ?? null,
                'observation' => $item['observation'] ?? null,
            ]);
        }

        $check_list->load('items.checkListItem');

        return response()->json([
            'success' => true,
            'status' => 201,
            'message' => 'Checklist creado correctamente.',
            'data' => new ReceptionCheckListResource($check_list),
        ], 201);
    }

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

    public function update(UpdateReceptionChecklistRequest $request, ReceptionCheckList $receptionCheckList)
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

        return response()->json([
            'success' => true,
            'status' => 200,
            'message' => 'Checklist actualizado correctamente.',
            'data' => new ReceptionCheckListResource($receptionCheckList),
        ]);
    }
}
