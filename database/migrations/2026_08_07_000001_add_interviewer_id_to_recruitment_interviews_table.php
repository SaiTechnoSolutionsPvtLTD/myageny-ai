<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruitment_interviews', function (Blueprint $table) {
            if (! Schema::hasColumn('recruitment_interviews', 'interviewer_id')) {
                $table->foreignId('interviewer_id')->nullable()->after('scheduled_by')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('recruitment_interviews', function (Blueprint $table) {
            if (Schema::hasColumn('recruitment_interviews', 'interviewer_id')) {
                $table->dropForeign(['interviewer_id']);
                $table->dropColumn('interviewer_id');
            }
        });
    }
};
