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
        $search = trim($request->input('search', ''));

        $query = Diagnostic::query()
        ->with([
            'reception.client',
            'reception.vehicle.brand',
            'reception.vehicle.vehicleModel',
            'mechanic.user',
        ]);

        $query->when($request->filled('search'), function ($q) use ($search) {
           
            $q->where(function ($d) use ($search) {

                if (is_numeric($search)) {
                    $d->orWhere('id', (int) $search);
                }

                $d->orWhere('customer_complaint', 'like', "%{$search}%")
                ->orWhere('diagnosis', 'like', "%{$search}%")
                ->orWhere('recommendation', 'like', "%{$search}%")
                ->orWhereHas('reception', function ($r) use ($search) {

                    if (is_numeric($search)) {
                        $r->orWhere('id', (int) $search);
                    }

                    $r->orWhere('problem_description', 'like', "%{$search}%")
                    ->orWhere('observations', 'like', "%{$search}%");
                })
                ->orWhereHas('reception.client', function ($rc) use ($search) {
                    $rc->where('document_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereRaw(
                        "CONCAT(first_name,' ',last_name) LIKE ?",
                        ["%{$search}%"]
                    );
                })
                ->orWhereHas('reception.vehicle', function ($rv) use ($search) {
                    $rv->where('chassis', 'like', "%{$search}%")
                    ->orWhere('plate', 'like', "%{$search}%")
                    ->orWhere('engine_number', 'like', "%{$search}%")
                    ->orWhere('year', 'like', "%{$search}%");
                })
                ->orWhereHas('reception.vehicle.brand', function ($rvb) use ($search) {
                    $rvb->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('reception.vehicle.vehicleModel', function ($rvm) use ($search) {
                    $rvm->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('mechanic.user', function ($mu) use ($search) {
                    $mu->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhereRaw(
                        "CONCAT(first_name,' ',last_name) LIKE ?",
                        ["%{$search}%"]
                    );
                });
            });
        });

        /*$query->when($request->filled('priority'), function ($q) use ($request) {
            $q->where('priority', $request->priority);
        });*/

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
            'mechanic.user',
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
