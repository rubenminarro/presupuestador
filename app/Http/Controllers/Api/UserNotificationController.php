<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexUserNotificationRequest;
use App\Http\Resources\UserNotificationResource;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

/**
 * Bandeja interna del usuario autenticado. Sin permisos: cada usuario solo
 * ve y marca sus propios avisos (los ajenos responden 404).
 */
class UserNotificationController extends Controller
{
    use ApiResponse;

    public function index(IndexUserNotificationRequest $request)
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->when($request->boolean('unread'), fn ($q) => $q->whereNull('read_at'))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->paginate($request->per_page ?? 10);

        return $this->successResponse(
            'Notificaciones obtenidas correctamente.',
            UserNotificationResource::collection($notifications->items()),
            200,
            [
                'pagination' => [
                    'total'       => $notifications->total(),
                    'perPage'     => $notifications->perPage(),
                    'currentPage' => $notifications->currentPage(),
                    'lastPage'    => $notifications->lastPage(),
                ],
                'unread_count' => $user->unreadNotifications()->count(),
            ]
        );
    }

    public function markAsRead(Request $request, string $notification)
    {
        $notification = $request->user()->notifications()->findOrFail($notification);

        $notification->markAsRead();

        return $this->successResponse(
            'Notificación marcada como leída.',
            new UserNotificationResource($notification),
            200
        );
    }

    public function markAllAsRead(Request $request)
    {
        $count = $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return $this->successResponse(
            'Notificaciones marcadas como leídas.',
            ['marked_count' => $count],
            200
        );
    }
}
