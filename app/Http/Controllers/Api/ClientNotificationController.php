<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexClientNotificationRequest;
use App\Http\Resources\ClientNotificationResource;
use App\Http\Resources\ShowClientNotificationResource;
use App\Models\Client;
use App\Models\ClientNotification;
use App\Services\ClientNotificationService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ClientNotificationController extends Controller implements HasMiddleware
{
    use ApiResponse;

    public function __construct(
        protected ClientNotificationService $clientNotificationService
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:client-notifications.index', only: ['index', 'clientNotifications']),
            new Middleware('permission:client-notifications.show', only: ['show']),
            new Middleware('permission:client-notifications.resend', only: ['resend']),
        ];
    }

    public function index(IndexClientNotificationRequest $request)
    {
        $notifications = ClientNotification::query()
            ->with('client')
            ->when($request->filled('client_id'), fn ($q) => $q->where('client_id', $request->client_id))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->orderByDesc('id')
            ->paginate($request->per_page ?? 10);

        return $this->successResponse(
            'Avisos a clientes obtenidos correctamente.',
            ClientNotificationResource::collection($notifications->items()),
            200,
            [
                'pagination' => [
                    'total'       => $notifications->total(),
                    'perPage'     => $notifications->perPage(),
                    'currentPage' => $notifications->currentPage(),
                    'lastPage'    => $notifications->lastPage(),
                ]
            ]
        );
    }

    public function clientNotifications(Request $request, Client $client)
    {
        $notifications = $client->clientNotifications()
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 10));

        return $this->successResponse(
            'Avisos del cliente obtenidos correctamente.',
            ClientNotificationResource::collection($notifications->items()),
            200,
            [
                'pagination' => [
                    'total'       => $notifications->total(),
                    'perPage'     => $notifications->perPage(),
                    'currentPage' => $notifications->currentPage(),
                    'lastPage'    => $notifications->lastPage(),
                ]
            ]
        );
    }

    public function show(ClientNotification $clientNotification)
    {
        return $this->successResponse(
            'Aviso al cliente obtenido correctamente.',
            new ShowClientNotificationResource($clientNotification->load(['client', 'subject', 'creator'])),
            200
        );
    }

    public function resend(Request $request, ClientNotification $clientNotification)
    {
        $notification = $this->clientNotificationService->resend($clientNotification, $request->user());

        return $this->successResponse(
            'Aviso reenviado correctamente.',
            new ShowClientNotificationResource($notification->refresh()->load(['client', 'subject', 'creator'])),
            201
        );
    }
}
