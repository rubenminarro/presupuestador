<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reception;
use App\Models\CheckListItem;
use App\Models\ReceptionCheckList;
use App\Models\ReceptionCheckListItem;
use App\Http\Resources\ReceptionResource;
use App\Http\Requests\StoreReceptionRequest;
use App\Http\Requests\UpdateReceptionRequest;
use App\Http\Resources\ShowReceptionResource;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReceptionController extends Controller
{
    
    use ApiResponse;

    public function index(Request $request)
    {
        
        $search = $request->input('search');

        $query = Reception::query()
        ->with(['client', 'vehicle', 'createdBy', 'approvedBy']);

        $query->when($request->filled('plate'), function ($q) use ($request) {
            $q->whereHas('vehicle', function ($v) use ($request) {
                $v->where('plate', 'like', "%{$request->plate}%");
            });
        });

        $query->when($request->filled('status'), function ($q) use ($request) {
            $q->where('status', $request->status);
        });

        $query->when($request->filled('reception_start_date'), function ($q) use ($request) {
            $q->whereDate('reception_date', '>=', $request->reception_start_date);
        });

        $query->when($request->filled('reception_end_date'), function ($q) use ($request) {
            $q->whereDate('reception_date', '<=', $request->reception_end_date);
        });

        $query->when($request->filled('estimated_delivery_start_date'), function ($q) use ($request) {
            $q->whereDate('estimated_delivery_date', '>=', $request->estimated_delivery_start_date);
        });

        $query->when($request->filled('estimated_delivery_end_date'), function ($q) use ($request) {
            $q->whereDate('estimated_delivery_date', '<=', $request->estimated_delivery_end_date);
        });

        $query->when($request->filled('search'), function ($q) use ($search) {
            $q->where(function ($subQuery) use ($search) {
                $subQuery->where('id', 'like', "%{$search}%")
                ->orWhere('problem_description', 'like', "%{$search}%")
                ->orWhereHas('client', function ($c) use ($search) {
                    $c->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%");
                })
                ->orWhereHas('vehicle', function ($v) use ($search) {
                    $v->where('chassis', 'like', "%{$search}%")
                    ->orWhere('plate', 'like', "%{$search}%")
                    ->orWhere('year', 'like', "%{$search}%")
                    ->orWhere('color', 'like', "%{$search}%")
                    ->orWhere('engine_number', 'like', "%{$search}%")
                    ->orWhere('fuel_type', 'like', "%{$search}%");
                })
                ->orWhereHas('createdBy', function ($cb) use ($search) {
                    $cb->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('approvedBy', function ($ab) use ($search) {
                    $ab->where('name', 'like', "%{$search}%");
                });
            });
        });
        
        $receptions = $query->latest()->paginate($request->per_page ?? 10);

        return $this->successResponse(
            'Recepciones obtenidas correctamente.',
            ReceptionResource::collection($receptions->items()),
            200,
            [
                'pagination' => [
                    'total'       => $receptions->total(),
                    'perPage'     => $receptions->perPage(),
                    'currentPage' => $receptions->currentPage(),
                    'lastPage'    => $receptions->lastPage(),
                ]
            ]
        );
    }

    public function store(StoreReceptionRequest $request)
    {
        $data = $request->validated();

        $data['created_by'] = Auth::id();

        $reception = DB::transaction(function () use ($request, $data) {
            
            $reception = Reception::create($data);

            $reception->serviceCategories()->sync(
                $request->service_category_ids
            );

            $checkList = ReceptionCheckList::create([
                'reception_id' => $reception->id,
            ]);

            $this->generateChecklistItems($checkList, $request->service_category_ids);

            return $reception;
            
        });

        return $this->successResponse(
            'Recepción creada correctamente.',
            new ShowReceptionResource(
                $reception->fresh()->load([
                    'client',
                    'vehicle',
                    'createdBy',
                    'approvedBy',
                    'serviceCategories',
                ])
            ),
            201
        );
    }

    public function show(Reception $reception)
    {
        return $this->successResponse(
            'Recepción encontrada.',
            new ShowReceptionResource(
                $reception->load([
                    'client',
                    'vehicle',
                    'createdBy',
                    'approvedBy',
                    'serviceCategories',
                ])
            ),
            200
        );
    }

    public function update(UpdateReceptionRequest $request, Reception $reception)
    {
        $data = $request->validated();

        if (
            $request->has('service_category_ids')
            && $reception->status !== 'pending'
        ) {
            return $this->errorResponse(
                'No se pueden modificar las categorías de servicio una vez procesada la recepción.',
                422
            );
        }

        DB::transaction(function () use (
            $request,
            $reception,
            $data
        ) {

            $reception->update($data);

            if ($request->has('service_category_ids')) {

                $reception->serviceCategories()->sync(
                    $request->service_category_ids
                );

                $checkList = $reception->checkList;

                if ($checkList) {

                    ReceptionCheckListItem::where(
                        'reception_check_list_id',
                        $checkList->id
                    )->delete();

                    $this->generateChecklistItems(
                        $checkList,
                        $request->service_category_ids
                    );
                }
            }
        });

        return $this->successResponse(
            'Recepción actualizada correctamente.',
            new ShowReceptionResource(
                $reception->fresh()->load([
                    'client',
                    'vehicle',
                    'createdBy',
                    'approvedBy',
                    'serviceCategories',
                ])
            )
        );
    }

    public function destroy(Reception $reception)
    {
        if (
            in_array($reception->status, [
                'in_progress',
                'completed',
                'delivered'
            ])
        ) {
            return $this->errorResponse(
                'No se puede eliminar una recepción procesada.',
                422
            );
        }

        $reception->delete();

        return $this->successResponse(
            'Recepción eliminada correctamente.',
            null,
            200
        );
    }

    private function generateChecklistItems( ReceptionCheckList $checkList, array $serviceCategoryIds): void
    {
       $items = CheckListItem::whereHas(
            'serviceCategories',
            fn ($query) =>
                $query->whereIn(
                    'service_categories.id',
                    $serviceCategoryIds
                )
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
