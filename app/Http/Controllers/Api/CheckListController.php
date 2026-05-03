<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\CheckListResource;
use App\Http\Requests\StoreCheckListRequest;
use App\Http\Requests\UpdateChecklistRequest;
use App\Models\CheckListItem;
use App\Traits\ApiResponse;

class CheckListController extends Controller
{
    
    use ApiResponse;

    public function index(Request $request)
    {
        $checklists = CheckListItem::latest()
            ->paginate(20);

        return $this->successResponse(
            'Checklist obtenidos correctamente.',
            [
                'items' => CheckListResource::collection($checklists->items()),
                'pagination' => [
                    'current_page' => $checklists->currentPage(),
                    'last_page' => $checklists->lastPage(),
                    'per_page' => $checklists->perPage(),
                    'total' => $checklists->total(),
                ]
            ],
            201
        );
    }

    public function store(StoreCheckListRequest $request)
    {
        $data = $request->validated();

        $checklist = CheckListItem::create($data);

        return $this->successResponse(
            'Checklist creado correctamente.',
            new CheckListResource($checklist),
            201
        );
    }

    public function show(CheckListItem $checkListItem)
    {
        return $this->successResponse(
            'Checklist encontrado.',
            new CheckListResource($checkListItem)
            
        );
    }

    public function update(UpdateChecklistRequest $request, CheckListItem $checkListItem) {
        
        $data = $request->validated();

        $checkListItem->update($data);

        return $this->successResponse(
            'Checklist actualizado correctamente.',
            new CheckListResource($checkListItem)
        );   
    }
    
    public function activate(CheckListItem $checkListItem)
    {
        $checkListItem->update([
            'active' => !$checkListItem->active
        ]);

        return $this->successResponse(
            $checkListItem->active
                ? 'Checklist activado correctamente.'
                : 'Checklist desactivado correctamente.',
            [
                'id' => $checkListItem->id,
                'active' => $checkListItem->active
            ]
        );
    }
}
