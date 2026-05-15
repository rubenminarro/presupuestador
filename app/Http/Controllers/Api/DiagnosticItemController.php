<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDiagnosticItemRequest;
use App\Http\Requests\UpdateDiagnosticItemRequest;
use App\Http\Resources\DiagnosticItemCollection;
use App\Http\Resources\DiagnosticItemResource;
use App\Models\DiagnosticItem;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class DiagnosticItemController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $search = $request->query('search');

        $diagnostics = DiagnosticItem::query()
            ->with([
                'diagnostic'
            ])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('severity', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('diagnostic', function ($q2) use ($search) {
                        $q2->where('diagnostic_id', 'like', "%{$search}%");
                    });
                });
            })
            ->latest()
            ->paginate(10);

        return $this->successResponse(
            'Lista de diagnósticos obtenida correctamente.',
            DiagnosticItemResource::collection($diagnostics->items()),
            200,
            [
                'pagination' => [
                    'total'       => $diagnostics->total(),
                    'perPage'     => $diagnostics->perPage(),
                    'currentPage' => $diagnostics->currentPage(),
                    'lastPage'    => $diagnostics->lastPage(),
                ]
            ]
        );
    }

    public function store(StoreDiagnosticItemRequest $request)
    {
        
        $data = $request->validated();
    
        $diagnosticItem = DiagnosticItem::create($data);

        $diagnosticItem->load([
            'diagnostic',
        ]);

        return $this->successResponse(
            new DiagnosticItemResource($diagnosticItem),
            'Item de diagnóstico creado correctamente',
            201
        );
    }

    public function show(DiagnosticItem $diagnosticItem)
    {
        $diagnosticItem->load([
            'diagnostic',
        ]);

        return $this->successResponse(
            new DiagnosticItemResource($diagnosticItem),
            'Item de diagnóstico obtenido correctamente'
        );
    }

    public function update(UpdateDiagnosticItemRequest $request, DiagnosticItem $diagnosticItem) 
    {
        $diagnosticItem->update(
            $request->validated()
        );

        $diagnosticItem->load([
            'diagnostic',
        ]);

        return $this->successResponse(
            new DiagnosticItemResource($diagnosticItem),
            'Item de diagnóstico actualizado correctamente'
        );
    }

    public function destroy(DiagnosticItem $diagnosticItem)
    {
        $diagnosticItem->delete();

        return $this->successResponse(
            null,
            'Item de diagnóstico eliminado correctamente'
        );
    }
}
