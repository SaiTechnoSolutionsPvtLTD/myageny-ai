<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\LeadReminder;

class AppApiController extends Controller
{
    public function reminderList(Request $request)
    {
        $userId = $request->user()->id;

        $reminders = LeadReminder::where('user_id', $userId)
            ->orderBy('remind_at', 'asc')
            ->get()
            ->map(function ($reminder) {
                return [
                    'id'             => $reminder->id,
                    'lead_id'        => $reminder->lead_id,
                    'title'          => $reminder->title,
                    'description'    => $reminder->description,
                    'remind_at'      => $reminder->remind_at?->format('Y-m-d H:i:s'),
                    // Explicit ->format() is required here — Carbon's default
                    // JSON serialization (what happens if you assign the raw
                    // Carbon instance directly) always converts to UTC first,
                    // which silently shifted every displayed time back by the
                    // app's UTC+5:30 offset on mobile.
                    'remainder_time' => optional($reminder->remainder_time)->format('H:i:s'),
                    'type'           => $reminder->type,
                    'type_label'     => $reminder->type_label,
                    'type_icon'      => $reminder->type_icon,
                    'priority'       => $reminder->priority,
                    'is_completed'   => $reminder->is_completed,
                    'completed_at'   => $reminder->completed_at?->format('Y-m-d H:i:s'),
                    'is_overdue'     => $reminder->is_overdue,
                ];
            });

        return response()->json([
            'status'  => true,
            'message' => 'Reminder list fetched successfully',
            'data'    => $reminders,
        ]);
    }
}
