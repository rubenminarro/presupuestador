<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\DiagnosticResource;
use App\Http\Requests\StoreDiagnosticRequest;
use App\Http\Requests\UpdateDiagnosticRequest;
use App\Http\Resources\ShowDiagnosticResource;
use App\Models\Diagnostic;
use App\Traits\ApiResponse;
use App\Enums\DiagnosticStatus;
use Illuminate\Support\Facades\DB;
use Exception;

class DiagnosticController extends Controller
{
    
    use ApiResponse;

    public function index(Request $request)
    {
        $search = $request->input('search');

        $query = Diagnostic::query()
        ->with(['reception', 'mechanic']);

        $query->when($request->filled('search'), function ($q) use ($search) {
            $q->where(function ($subQuery) use ($search) {
                $subQuery->where('id', 'like', "%{$search}%")
                ->orWhere('customer_complaint', 'like', "%{$search}%")
                ->orWhere('diagnosis', 'like', "%{$search}%")
                ->orWhere('recommendation', 'like', "%{$search}%")
                ->orWhere('priority', 'like', "%{$search}%")
                ->orWhere('status', 'like', "%{$search}%")
                ->orWhereHas('reception', function ($r) use ($search) {
                    $r->where('id', 'like', "%{$search}%")
                    ->orWhere('problem_description', 'like', "%{$search}%")
                    ->orWhere('observations', 'like', "%{$search}%")
                    ->orWhere('mileage', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhere('fuel_level', 'like', "%{$search}%");
                })
                ->orWhereHas('reception.client', function ($rc) use ($search) {
                    $rc->where('document_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhereHas('mechanic', function ($v) use ($search) {
                    $v->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
                });
            });
        });

        $diagnostics = $query->latest()->paginate($request->per_page ?? 10);

        return $this->successResponse(
            'Diagnósticos obtenidos correctamente.',
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

        $data['status'] = DiagnosticStatus::PENDING;
        $data['diagnosed_at'] = now();

        DB::beginTransaction();

        try {
            $diagnostic = Diagnostic::create($data);

            DB::commit();

            $diagnostic->load([
                'reception',
                'mechanic'
            ]);

            return $this->successResponse(
                'Diagnóstico creado correctamente.',
                new ShowDiagnosticResource($diagnostic),
                201
            );

        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Error al crear el diagnóstico: ' . $e->getMessage(), 500);
        }
    }

    public function show(Diagnostic $diagnostic)
    {
        $diagnostic->load([
            'reception.client',
            'reception.vehicle',
            'reception.photos',
            'mechanic',
            'items.photos',
        ]);

        return $this->successResponse(
            new ShowDiagnosticResource($diagnostic),
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
            new ShowDiagnosticResource($diagnostic),
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
