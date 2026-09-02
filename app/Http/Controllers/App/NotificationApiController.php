<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = min(50, max(1, (int) $request->query('per_page', 20)));

        $targetBranchId = $this->resolveTargetBranchId($request, $user);

        $query = $user->notifications();
        $this->applyBranchFilter($query, $user, $targetBranchId);

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

        // Branch-filtered unread count
        $unreadQuery = $user->unreadNotifications();
        $this->applyBranchFilter($unreadQuery, $user, $targetBranchId);

        return response()->json([
            'success' => true,
            'data' => [
                'notifications' => collect($notifications->items())->map(fn ($n) => $this->format($n))->values(),
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'total' => $notifications->total(),
                ],
                'unread_count' => $unreadQuery->count(),
                'branch_id' => $targetBranchId,
            ],
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();
        $targetBranchId = $this->resolveTargetBranchId($request, $user);

        $unreadQuery = $user->unreadNotifications();
        $this->applyBranchFilter($unreadQuery, $user, $targetBranchId);

        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $unreadQuery->count(),
                'branch_id' => $targetBranchId,
            ],
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
        $user = $request->user();
        $targetBranchId = $this->resolveTargetBranchId($request, $user);

        $unreadQuery = $user->unreadNotifications();
        $this->applyBranchFilter($unreadQuery, $user, $targetBranchId);

        $unreadQuery->update(['read_at' => now()]);

        return response()->json(['success' => true, 'message' => 'All notifications marked as read.']);
    }

    private function resolveTargetBranchId(Request $request, $user): ?int
    {
        if ($request->filled('branch_id')) {
            return (int) $request->query('branch_id');
        }

        if ($request->header('X-Branch-Id')) {
            return (int) $request->header('X-Branch-Id');
        }

        if ($user->branch_id) {
            return (int) $user->branch_id;
        }

        return null;
    }

    private function applyBranchFilter($query, $user, ?int $targetBranchId): void
    {
        if ($targetBranchId !== null) {
            $query->where(function ($q) use ($targetBranchId) {
                $q->where('data->branch_id', $targetBranchId)
                  ->orWhere('data->branch_id', (string) $targetBranchId);
            });
        } elseif (! $user->isSystemAdmin()) {
            $allowedBranchIds = array_filter($user->getMyBranchIds());
            if (!empty($allowedBranchIds)) {
                $query->where(function ($q) use ($allowedBranchIds) {
                    foreach ($allowedBranchIds as $bId) {
                        $q->orWhere('data->branch_id', $bId)
                          ->orWhere('data->branch_id', (string) $bId);
                    }
                });
            }
        }
    }

    private function format($notification): array
    {
        $data = is_array($notification->data) ? $notification->data : (json_decode($notification->data, true) ?: []);

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
            'branch_id' => isset($data['branch_id']) ? (int) $data['branch_id'] : NotificationService::resolveNotificationBranchId($notification),
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
            'leave', 'permission', 'od', 'outside_office' => 'hrms',
            default => null,
        };
    }
}
