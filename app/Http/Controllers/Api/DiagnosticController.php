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

        $query->when($request->filled('search'), function ($query) use ($search) {

            $query->where(function ($q) use ($search) {

                if (is_numeric($search)) {
                    $q->orWhere('id', (int) $search)
                    ->orWhere('reception_id', (int) $search);
                }

                $q->orWhere('customer_complaint', 'like', "%{$search}%")
                ->orWhere('diagnosis', 'like', "%{$search}%")
                ->orWhere('recommendation', 'like', "%{$search}%");

                $q->orWhereHas('reception.client', function ($client) use ($search) {

                    $client->where('document_number', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereRaw(
                            "CONCAT(first_name,' ',last_name) LIKE ?",
                            ["%{$search}%"]
                        );

                });

                $q->orWhereHas('reception.vehicle', function ($vehicle) use ($search) {

                    $vehicle->where('plate', 'like', "%{$search}%")
                        ->orWhere('chassis', 'like', "%{$search}%")
                        ->orWhere('engine_number', 'like', "%{$search}%")
                        ->orWhere('year', 'like', "%{$search}%");

                });

                $q->orWhereHas('reception.vehicle.brand', function ($brand) use ($search) {

                    $brand->where('name', 'like', "%{$search}%");

                });

                $q->orWhereHas('reception.vehicle.vehicleModel', function ($model) use ($search) {

                    $model->where('name', 'like', "%{$search}%");

                });

                $q->orWhereHas('mechanic.user', function ($user) use ($search) {

                    $user->where('name', 'like', "%{$search}%")
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

        $query->when($request->filled('status'), function ($q) use ($request) {
            $q->where('status', $request->status);
        });

        $query->when($request->filled('priority'), function ($q) use ($request) {
            $q->where('priority', $request->priority);
        });

        $query->when($request->filled('mechanic_id'), function ($q) use ($request) {
            $q->where('mechanic_id', $request->mechanic_id);
        });

        $query->when($request->filled('reception_id'), function ($q) use ($request) {
            $q->where('reception_id', $request->reception_id);
        });

        $query->when($request->filled('client_id'), function ($q) use ($request) {
            $q->whereHas('reception', function ($reception) use ($request) {
                $reception->where('client_id', $request->client_id);
            });
        });

        $query->when($request->filled('vehicle_id'), function ($q) use ($request) {
            $q->whereHas('reception', function ($reception) use ($request) {
                $reception->where('vehicle_id', $request->vehicle_id);
            });
        });

        $query->when($request->filled('plate'), function ($q) use ($request) {
            $q->whereHas('reception.vehicle', function ($vehicle) use ($request) {
                $vehicle->where('plate', 'like', "%{$request->plate}%");
            });
        });

        $query->when($request->filled('requires_parts'), function ($q) use ($request) {
            $q->where('requires_parts', $request->boolean('requires_parts'));
        });

        $query->when($request->filled('requires_repair'), function ($q) use ($request) {
            $q->where('requires_repair', $request->boolean('requires_repair'));
        });

        $query->when($request->filled('diagnosed_from'), function ($q) use ($request) {
            $q->whereDate('diagnosed_at', '>=', $request->diagnosed_from);

        });

        $query->when($request->filled('diagnosed_to'), function ($q) use ($request) {
            $q->whereDate('diagnosed_at', '<=', $request->diagnosed_to);
        });

        $query->when($request->filled('reception_start_date'), function ($q) use ($request) {
            $q->whereHas('reception', function ($reception) use ($request) {
                $reception->whereDate(
                    'reception_date',
                    '>=',
                    $request->reception_start_date
                );
            });
        });

        $query->when($request->filled('reception_end_date'), function ($q) use ($request) {
            $q->whereHas('reception', function ($reception) use ($request) {
                $reception->whereDate(
                    'reception_date',
                    '<=',
                    $request->reception_end_date
                );
            });
        });

        $query->when($request->filled('estimated_delivery_start_date'), function ($q) use ($request) {
            $q->whereHas('reception', function ($reception) use ($request) {
                $reception->whereDate(
                    'estimated_delivery_date',
                    '>=',
                    $request->estimated_delivery_start_date
                );
            });
        });

        $query->when($request->filled('estimated_delivery_end_date'), function ($q) use ($request) {
            $q->whereHas('reception', function ($reception) use ($request) {
                $reception->whereDate(
                    'estimated_delivery_date',
                    '<=',
                    $request->estimated_delivery_end_date
                );
            });
        });

        $diagnostics = $query
            ->latest()
            ->paginate($request->input('per_page', 10));

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
