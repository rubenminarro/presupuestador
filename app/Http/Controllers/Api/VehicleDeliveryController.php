<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelVehicleDeliveryRequest;
use App\Http\Requests\IndexVehicleDeliveryRequest;
use App\Http\Requests\StoreVehicleDeliveryRequest;
use App\Http\Resources\ShowVehicleDeliveryResource;
use App\Http\Resources\VehicleDeliveryResource;
use App\Models\Reception;
use App\Models\VehicleDelivery;
use App\Services\DocumentPdfService;
use App\Services\VehicleDeliveryService;
use App\Traits\ApiResponse;
use App\Traits\FileResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class VehicleDeliveryController extends Controller implements HasMiddleware
{
    use ApiResponse, FileResponse;

    public function __construct(
        protected VehicleDeliveryService $deliveryService
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:vehicle-deliveries.index', only: ['index']),
            new Middleware('permission:vehicle-deliveries.show', only: ['show', 'pdf']),
            new Middleware('permission:vehicle-deliveries.store', only: ['store']),
            new Middleware('permission:vehicle-deliveries.cancel', only: ['cancel']),
        ];
    }

    public function index(IndexVehicleDeliveryRequest $request)
    {
        $search = trim($request->input('search', ''));

        $deliveries = VehicleDelivery::query()
            ->with(['vehicle', 'client'])
            ->when($request->filled('reception_id'), fn ($q) => $q->where('reception_id', $request->reception_id))
            ->when($request->filled('vehicle_id'), fn ($q) => $q->where('vehicle_id', $request->vehicle_id))
            ->when($request->filled('client_id'), fn ($q) => $q->where('client_id', $request->client_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('delivery_number', 'like', "%{$search}%")
                        ->orWhere('received_by_name', 'like', "%{$search}%")
                        ->orWhere('received_by_document', 'like', "%{$search}%")
                        ->orWhereHas('vehicle', fn ($v) => $v->where('plate', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('delivered_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('delivered_at', '<=', $request->date_to))
            ->orderByDesc('id')
            ->paginate($request->per_page ?? 10);

        return $this->successResponse(
            'Entregas obtenidas correctamente.',
            VehicleDeliveryResource::collection($deliveries->items()),
            200,
            [
                'pagination' => [
                    'total'       => $deliveries->total(),
                    'perPage'     => $deliveries->perPage(),
                    'currentPage' => $deliveries->currentPage(),
                    'lastPage'    => $deliveries->lastPage(),
                ]
            ]
        );
    }

    public function show(VehicleDelivery $delivery)
    {
        return $this->successResponse(
            'Entrega obtenida correctamente.',
            new ShowVehicleDeliveryResource($delivery->load(['reception', 'vehicle', 'client'])),
            200
        );
    }

    public function pdf(VehicleDelivery $delivery, DocumentPdfService $documentPdfService)
    {
        return $this->fileResponse($documentPdfService->delivery($delivery), 'application/pdf');
    }

    public function store(StoreVehicleDeliveryRequest $request, Reception $reception)
    {
        $delivery = $this->deliveryService->deliver(
            $reception,
            $request->validated(),
            $request->file('signature'),
            $request->user()
        );

        return $this->successResponse(
            'Entrega del vehículo registrada correctamente.',
            new ShowVehicleDeliveryResource($delivery),
            201
        );
    }

    public function cancel(CancelVehicleDeliveryRequest $request, VehicleDelivery $delivery)
    {
        $delivery = $this->deliveryService->cancel($delivery, $request->validated('reason'), $request->user());

        return $this->successResponse(
            'Entrega anulada correctamente.',
            new ShowVehicleDeliveryResource($delivery),
            200
        );
    }
}
