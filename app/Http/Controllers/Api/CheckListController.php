<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\CheckListResource;
use App\Http\Requests\StoreCheckListRequest;
use App\Http\Requests\UpdateChecklistRequest;
use App\Http\Resources\ShowCheckListResource;
use App\Models\CheckListItem;
use App\Traits\ApiResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CheckListController extends Controller implements HasMiddleware
{
    
    use ApiResponse;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:checklists.index', only: ['index']),
            new Middleware('permission:checklists.store', only: ['store']),
            new Middleware('permission:checklists.show', only: ['show']),
            new Middleware('permission:checklists.update', only: ['update']),
            new Middleware('permission:checklists.destroy', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        
        $checklists = CheckListItem::when($request->filled('search'), function ($query) use ($request) {
            $search = $request->search;
            // Agrupado: el OR no debe saltarse el filtro por estado.
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('type', 'like', "%{$search}%")
                ->orWhere('required', 'like', "%{$search}%"));
        })
        ->when($request->has('is_active'), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
        ->latest()
        ->paginate(
            $request->per_page ?? 10
        );

        return $this->successResponse(
            'Checklist obtenidos correctamente.',
            CheckListResource::collection($checklists->items()),
            200,
            [
                'pagination' => [
                    'total'       => $checklists->total(),
                    'perPage'     => $checklists->perPage(),
                    'currentPage' => $checklists->currentPage(),
                    'lastPage'    => $checklists->lastPage(),
                ]
            ]
        );
    }

    public function store(StoreCheckListRequest $request)
    {
        $data = $request->validated();

        $checklist = CheckListItem::create($data);

        return $this->successResponse(
            'Checklist creado correctamente.',
            new ShowCheckListResource($checklist),
            201
        );
    }

    public function show(CheckListItem $checklist)
    {
        return $this->successResponse(
            'Checklist encontrado.',
            new ShowCheckListResource($checklist),
            200
        );
    }

    public function update(UpdateChecklistRequest $request, CheckListItem $checklist) {
        
        $data = $request->validated();

        $checklist->update($data);

        return $this->successResponse(
            'Checklist actualizado correctamente.',
            new ShowCheckListResource($checklist),
            200
        );   
    }
    
    public function destroy(CheckListItem $checklist)
    {
        // Usado en recepciones: el checklist viejo debe seguir mostrando su nombre (sin el sufijo de baja).
        if ($checklist->receptionItems()->exists()) {
            return $this->errorResponse(
                'El ítem ya se usó en checklists de recepciones: desactívelo en lugar de eliminarlo.',
                409,
                'CHECKLIST_ITEM_IN_USE'
            );
        }

        $suffix = '//deleted_' . now()->timestamp;

        if ($checklist->name) {
            $checklist->name = $checklist->name . $suffix;
        }

        $checklist->save();

        $checklist->delete();

        return $this->successResponse(
            'Checklist eliminado correctamente.',
            null,
            200
        );
    }
}
