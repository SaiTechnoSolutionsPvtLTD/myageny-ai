<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function markAsRead(Request $request, string $notificationId): RedirectResponse
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $notificationId)
            ->firstOrFail();

        if (! $notification->read_at) {
            $notification->markAsRead();
        }

        return redirect()->to($notification->data['action_url'] ?? url()->previous());
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $user = $request->user();
        $branchData = \App\Services\NotificationService::getBranchFilteredNotifications($user, 100);
        $notifications = $branchData['filtered_notifications'] ?? collect();

        foreach ($notifications as $notification) {
            if (! $notification->read_at) {
                $notification->markAsRead();
            }
        }

        return back()->with('success', 'Notifications marked as read.');
    }
}
