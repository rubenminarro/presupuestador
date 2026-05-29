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

class CheckListController extends Controller
{
    
    use ApiResponse;

    public function index(Request $request)
    {
        
        $checklists = CheckListItem::when($request->filled('search'), function ($query) use ($request) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('type', 'like', "%{$search}%")
                ->orWhere('required', 'like', "%{$search}%");
        })
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

    public function show(CheckListItem $checkListItem)
    {
        return $this->successResponse(
            'Checklist encontrado.',
            new ShowCheckListResource($checkListItem),
            200
        );
    }

    public function update(UpdateChecklistRequest $request, CheckListItem $checkListItem) {
        
        $data = $request->validated();

        $checkListItem->update($data);

        return $this->successResponse(
            'Checklist actualizado correctamente.',
            new ShowCheckListResource($checkListItem),
            200
        );   
    }
    
    public function destroy(CheckListItem $checkListItem)
    {
        
        $suffix = '//deleted_' . now()->timestamp;

        if ($checkListItem->name) {
            $checkListItem->name = $checkListItem->name . $suffix;
        }

        $checkListItem->save();

        $checkListItem->delete();

        return $this->successResponse(
            'Checklist eliminado correctamente.',
            null,
            200
        );
    }
}
