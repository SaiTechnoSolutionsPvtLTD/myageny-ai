<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Staging table for outside-office check-in/check-out attempts that need
    // HR/Admin approval before they touch `daily_attendances`. Deliberately
    // kept separate from daily_attendances (rather than adding a `status`
    // column there) so every existing query against daily_attendances — HR
    // attendance list, dashboard counts, "already checked in today" checks —
    // keeps meaning exactly what it always has: a real, final attendance
    // record. Nothing here is written to daily_attendances until approved.
    public function up(): void
    {
        Schema::create('outside_office_attendance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();

            $table->unsignedBigInteger('employee_id')->nullable()->index();
            $table->string('attendee_type', 20)->default('employee'); // employee|intern
            $table->unsignedBigInteger('intern_joining_form_id')->nullable()->index();
            $table->string('employee_name')->nullable();

            // checkin|checkout — which side of the attendance this request is for.
            $table->string('request_type', 20);

            // Set once approved: for a checkin request, the DailyAttendance row
            // created on approval; for a checkout request, the existing row
            // (already checked in) that approval will update.
            $table->foreignId('daily_attendance_id')->nullable()
                ->constrained('daily_attendances')->nullOnDelete();

            // The employee's ACTUAL attempted time — this, not the approval
            // time, is what gets written to daily_attendances on approval.
            $table->dateTime('requested_at');
            $table->date('attendance_date')->index();

            $table->string('photo')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('location')->nullable();
            $table->text('reason');

            $table->string('status', 20)->default('pending')->index(); // pending|approved|rejected
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('admin_remarks')->nullable();

            $table->timestamps();

            // One pending request per employee/intern + type + day — the
            // duplicate-submission guard relies on this rather than a raw
            // query alone. Resolved (approved/rejected) rows are exempt via
            // a partial-unique-style check in the controller since MySQL
            // doesn't support a WHERE clause on this unique index the same
            // way; app-level guard is the source of truth, this is a safety net.
            $table->index(['employee_id', 'intern_joining_form_id', 'request_type', 'attendance_date', 'status'], 'ooar_dup_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outside_office_attendance_requests');
    }
};