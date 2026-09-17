<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReceptionCheckList;
use App\Http\Requests\UpdateReceptionCheckListRequest;
use App\Http\Resources\ReceptionCheckListResource;
use App\Services\ReceptionCheckListItemService;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ReceptionCheckListController extends Controller implements HasMiddleware
{
   
    use ApiResponse;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:reception-check-lists.show', only: ['show']),
            new Middleware('permission:reception-check-lists.update', only: ['update']),
        ];
    }

    public function show(ReceptionCheckList $receptionCheckList)
    {
        return $this->successResponse(
            'Checklist obtenido correctamente.',
            new ReceptionCheckListResource(
                $receptionCheckList->load([
                    'items.checkListItem',
                ])
            )
        );
    }

    public function update(
        UpdateReceptionCheckListRequest $request,
        ReceptionCheckList $receptionCheckList,
        ReceptionCheckListItemService $service
    ) {
        if (
            in_array(
                $receptionCheckList->reception->status,
                [
                    'in_progress',
                    'completed',
                    'delivered',
                ]
            )
        ) {
            return $this->errorResponse(
                'No se puede modificar el checklist de una recepción procesada.',
                422
            );
        }

        DB::transaction(function () use (
            $request,
            $receptionCheckList,
            $service
        ) {

            foreach ($request->items as $item) {

                $receptionCheckList
                    ->items()
                    ->where('id', $item['id'])
                    ->update([
                        'value'       => $item['value'] ?? null,
                        'observation' => $item['observation'] ?? null,
                    ]);
            }

            $status = $service->calculateCheckListStatus(
                $receptionCheckList->fresh()->load('items')
            );

            $receptionCheckList->update([
                'status' => $status,
            ]);
        });

        return $this->successResponse(
            'Checklist actualizado correctamente.',
            new ReceptionCheckListResource(
                $receptionCheckList->fresh()->load([
                    'items.checkListItem',
                ])
            )
        );
    }
}
