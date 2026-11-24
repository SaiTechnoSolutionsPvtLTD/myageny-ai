<?php

namespace App\Services;

use App\Mail\EarlyCheckoutNotificationMail;
use App\Models\DailyAttendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AttendanceNotificationService
{
    /**
     * Check if a saved attendance record is an early checkout and notify HR.
     */
    public function handleCheckoutSaved(DailyAttendance $attendance): void
    {
        // 1. Must have a valid logout_time
        if (empty($attendance->logout_time) || $attendance->logout_time === '00:00:00') {
            return;
        }

        // 2. Only trigger for today's attendance checkout
        if (! $this->isAttendanceForToday($attendance)) {
            return;
        }

        // 3. Format and compare logout_time with the standard threshold (18:30:00)
        $standardTimeStr = config('hrms.standard_checkout_time', '18:30:00');
        $logoutTimeStr = $this->normalizeTime($attendance->logout_time);

        if ($logoutTimeStr >= $standardTimeStr) {
            return; // Normal or late checkout — no early checkout alert needed
        }

        // 4. Prevent duplicate notification for the same attendance record today
        $dateStr = Carbon::parse($attendance->attendance_date)->toDateString();
        $cacheKey = "early_checkout_mail_sent_{$attendance->id}_{$dateStr}";

        if (Cache::has($cacheKey)) {
            return;
        }

        // Mark as notified for the rest of today
        Cache::put($cacheKey, true, now()->endOfDay());

        // 5. Send early checkout alert to HR
        $hrEmail = config('hrms.hr_notification_email', 'hr@saitechnosolutions.net');
        if (empty($hrEmail)) {
            return;
        }

        try {
            Mail::to($hrEmail)->send(new EarlyCheckoutNotificationMail($attendance));
        } catch (\Throwable $e) {
            Log::error("Failed to send early checkout alert to HR ({$hrEmail}): " . $e->getMessage(), [
                'attendance_id' => $attendance->id,
                'employee_name' => $attendance->employee_name,
                'logout_time'   => $attendance->logout_time,
                'error'         => $e->getMessage(),
            ]);
        }
    }

    private function isAttendanceForToday(DailyAttendance $attendance): bool
    {
        if (empty($attendance->attendance_date)) {
            return false;
        }

        try {
            return Carbon::parse($attendance->attendance_date)->isToday();
        } catch (\Throwable) {
            return false;
        }
    }

    private function normalizeTime(mixed $time): string
    {
        if ($time instanceof Carbon) {
            return $time->format('H:i:s');
        }

        $str = trim((string) $time);
        if (strlen($str) === 5) { // '18:30' -> '18:30:00'
            return $str . ':00';
        }

        return $str;
    }
}
