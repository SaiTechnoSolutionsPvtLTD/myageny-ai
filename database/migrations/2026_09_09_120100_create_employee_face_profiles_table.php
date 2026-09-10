<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per employee who has completed Face Registration (HRMS ›
     * Face Registration screen, HR/Admin only). The actual biometric
     * embedding is NOT stored here — it lives in the separate
     * face-recognition Python service (face_recognition_myagenci_python),
     * keyed by this same employee_id. This table only tracks REGISTRATION
     * STATUS so:
     *   - the Face Registration screen can show who has/hasn't registered
     *     without round-tripping to the Python service for every employee,
     *   - the Face Attendance check-in/out flow (FaceAttendanceApiController)
     *     knows up front whether to even attempt a face-match — an employee
     *     with no row here is hard-blocked with a "not registered" message
     *     instead of getting an ambiguous verification failure.
     */
    public function up(): void
    {
        Schema::create('employee_face_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id')->unique();
            $table->unsignedBigInteger('registered_by')->nullable();
            $table->timestamp('registered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_face_profiles');
    }
};
