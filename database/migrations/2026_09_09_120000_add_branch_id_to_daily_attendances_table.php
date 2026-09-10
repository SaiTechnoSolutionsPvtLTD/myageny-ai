<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive, mobile-only: the new Face Attendance module ("Lead Priority"
     * sibling ticket — Face Recognition Attendance) needs to record which
     * branch each attendance record was marked from. Existing branch
     * filtering on this table (see DailyAttendance::booted()'s global scope)
     * derives branch indirectly via employee->portalUser->branch_id, which
     * only reflects the employee's CURRENT branch — an employee who later
     * transfers branches would have their historical attendance silently
     * reattributed. Storing branch_id directly on the row at the moment of
     * check-in/out fixes that and is what the ticket asks for explicitly.
     *
     * Nullable, no FK constraint (matches this table's existing style e.g.
     * intern_joining_form_id), so every existing row and the untouched
     * DailyAttendanceController::attendanceCheckIn()/attendanceCheckOut()
     * flow are completely unaffected — they simply never populate it.
     */
    public function up(): void
    {
        Schema::table('daily_attendances', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->after('company_id');
            $table->index('branch_id');
        });
    }

    public function down(): void
    {
        Schema::table('daily_attendances', function (Blueprint $table) {
            $table->dropIndex(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};
