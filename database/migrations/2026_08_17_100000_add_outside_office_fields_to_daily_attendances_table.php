<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive, mobile-only feature: tracks whether a self-service Check-In
     * and/or Check-Out happened outside the employee's configured office
     * geofence, plus the mandatory reason/remark the employee must supply in
     * that case. Check-in and check-out are tracked independently since an
     * employee could, e.g., check in inside the office but check out from
     * outside it (or vice versa).
     */
    public function up(): void
    {
        Schema::table('daily_attendances', function (Blueprint $table) {
            $table->boolean('is_outside_office_checkin')->default(false)->after('remarks');
            $table->text('outside_office_checkin_reason')->nullable()->after('is_outside_office_checkin');
            $table->boolean('is_outside_office_checkout')->default(false)->after('outside_office_checkin_reason');
            $table->text('outside_office_checkout_reason')->nullable()->after('is_outside_office_checkout');
        });
    }

    public function down(): void
    {
        Schema::table('daily_attendances', function (Blueprint $table) {
            $table->dropColumn([
                'is_outside_office_checkin',
                'outside_office_checkin_reason',
                'is_outside_office_checkout',
                'outside_office_checkout_reason',
            ]);
        });
    }
};
