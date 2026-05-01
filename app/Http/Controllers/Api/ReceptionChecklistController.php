<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ReceptionChecklist;
use App\Http\Requests\StoreReceptionChecklistRequest;
use App\Http\Requests\UpdateReceptionChecklistRequest;
use App\Http\Resources\ShowReceptionChecklistResource;
use App\Traits\ApiResponse;

class ReceptionChecklistController extends Controller
{
    
    use ApiResponse;
    
    public function index(Request $request)
    {
        $checklists = ReceptionChecklist::with([
                'reception',
            ])
            ->latest()
            ->paginate(20);

        return $this->successResponse(
            'Checklist obtenidos correctamente.',
            [
                'items' => ShowReceptionChecklistResource::collection($checklists->items()),
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

    public function store(StoreReceptionChecklistRequest $request)
    {
        $data = $request->validated();

        $checklist = ReceptionChecklist::create($data);

        return $this->successResponse(
            'Checklist creado correctamente.',
            new ShowReceptionChecklistResource(
                $checklist->load([
                    'reception',
                ])
            ),
            201
        );
    }

    public function show(ReceptionChecklist $receptionChecklist)
    {
        return $this->successResponse(
            'Checklist encontrado.',
            new ShowReceptionChecklistResource(
                $receptionChecklist->load([
                    'reception',
                ])
            )
        );
    }

    public function update(UpdateReceptionChecklistRequest $request, ReceptionChecklist $receptionChecklist) {
        
        $data = $request->validated();

        $receptionChecklist->update($data);

        return $this->successResponse(
            'Checklist actualizado correctamente.',
            new ShowReceptionChecklistResource(
                $receptionChecklist->load([
                    'reception',
                ])
            )
        );
    }

    public function destroy(ReceptionChecklist $receptionChecklist)
    {
        $receptionChecklist->delete();

        return $this->successResponse(
            'Checklist eliminado correctamente.'
        );
    }
}
