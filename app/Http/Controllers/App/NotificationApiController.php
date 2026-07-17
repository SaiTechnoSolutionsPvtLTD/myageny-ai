<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = min(50, max(1, (int) $request->query('per_page', 20)));

        $query = $user->notifications();

        $module = trim((string) $request->query('module', ''));
        if ($module !== '') {
            $query->where('data->module', $module);
        }

        $readFilter = trim((string) $request->query('status', '')); // unread|read
        if ($readFilter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($readFilter === 'read') {
            $query->whereNotNull('read_at');
        }

        $notifications = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'notifications' => collect($notifications->items())->map(fn ($n) => $this->format($n))->values(),
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'total' => $notifications->total(),
                ],
                'unread_count' => $user->unreadNotifications()->count(),
            ],
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => ['unread_count' => $request->user()->unreadNotifications()->count()],
        ]);
    }

    public function markAsRead(Request $request, string $notificationId): JsonResponse
    {
        $notification = $request->user()->notifications()->where('id', $notificationId)->first();

        if (! $notification) {
            return response()->json(['success' => false, 'message' => 'Notification not found.'], 404);
        }

        if (! $notification->read_at) {
            $notification->markAsRead();
        }

        return response()->json(['success' => true, 'message' => 'Notification marked as read.']);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['success' => true, 'message' => 'All notifications marked as read.']);
    }

    private function format($notification): array
    {
        $data = $notification->data ?? [];

        return [
            'id' => $notification->id,
            'module' => $data['module'] ?? $this->legacyModuleFallback($data),
            'notification_type' => $data['notification_type'] ?? ($data['event_type'] ?? null),
            'priority' => $data['priority'] ?? 'medium',
            'title' => $data['title'] ?? 'Notification',
            'message' => $data['message'] ?? '',
            'detail' => $data['detail'] ?? null,
            'action_url' => $data['action_url'] ?? null,
            'action_label' => $data['action_label'] ?? 'View Details',
            'request_type' => $data['request_type'] ?? null,
            'request_id' => $data['request_id'] ?? null,
            'actor_name' => $data['actor_name'] ?? null,
            'requester_name' => $data['requester_name'] ?? null,
            'status' => $data['status'] ?? null,
            'is_read' => (bool) $notification->read_at,
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }

    /**
     * Rows created by the pre-existing HrmsApprovalFlowNotification never set
     * 'module' (it predates this feature). Infer it from request_type so old
     * and new rows render consistently in the unified Flutter feed.
     */
    private function legacyModuleFallback(array $data): ?string
    {
        return match ($data['request_type'] ?? null) {
            'leave', 'permission' => 'hrms',
            default => null,
        };
    }
}