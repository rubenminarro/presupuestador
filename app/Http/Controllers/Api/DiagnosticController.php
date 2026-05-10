<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\DiagnosticResource;
use App\Http\Requests\StoreDiagnosticRequest;
use App\Http\Requests\UpdateDiagnosticRequest;
use App\Models\Diagnostic;
use App\Traits\ApiResponse;

class DiagnosticController extends Controller
{
    
    use ApiResponse;

    public function index(Request $request)
    {
        $search = $request->query('search');

        $diagnostics = Diagnostic::query()
            ->with([
                'reception',
                'mechanic'
            ])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('customer_complaint', 'like', "%{$search}%")
                    ->orWhere('diagnosis', 'like', "%{$search}%")
                    ->orWhere('recommendation', 'like', "%{$search}%")
                    ->orWhereHas('reception', function ($q2) use ($search) {
                        $q2->where('reception_id', 'like', "%{$search}%");
                    })
                    ->orWhereHas('mechanic', function ($q3) use ($search) {
                        $q3->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                    });
                });
            })
            ->latest()
            ->paginate(10);

        return $this->successResponse(
            'Lista de diagnósticos obtenida correctamente.',
            DiagnosticResource::collection($diagnostics->items()),
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

    public function store(StoreDiagnosticRequest $request)
    {
        
        $data = $request->validated();
    
        $diagnostic = Diagnostic::create($data);

        $diagnostic->load([
            'reception',
            'mechanic'
        ]);

        return $this->successResponse(
            'Diagnóstico creado correctamente.',
            new DiagnosticResource($diagnostic),
            201
        );
    }

    public function show(Diagnostic $diagnostic)
    {
        $diagnostic->load([
            'reception',
            'mechanic'
        ]);

        return $this->successResponse(
            new DiagnosticResource($diagnostic),
            'Diagnóstico obtenido correctamente'
        );
    }

    public function update(UpdateDiagnosticRequest $request, Diagnostic $diagnostic)
    {
        $diagnostic->update(
            $request->validated()
        );

        $diagnostic->load([
            'reception',
            'mechanic'
        ]);

        return $this->successResponse(
            new DiagnosticResource($diagnostic),
            'Diagnóstico actualizado correctamente'
        );
    }

     public function destroy(Diagnostic $diagnostic)
    {
        $diagnostic->delete();

        return $this->successResponse(
            null,
            'Diagnóstico eliminado correctamente'
        );
    }

}
