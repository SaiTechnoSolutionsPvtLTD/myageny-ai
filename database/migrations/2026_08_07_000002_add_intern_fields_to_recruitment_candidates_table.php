<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruitment_candidates', function (Blueprint $table) {
            if (! Schema::hasColumn('recruitment_candidates', 'institute_name')) {
                $table->string('institute_name', 255)->nullable()->after('candidate_type');
            }
            if (! Schema::hasColumn('recruitment_candidates', 'course_name')) {
                $table->string('course_name', 255)->nullable()->after('institute_name');
            }
            if (! Schema::hasColumn('recruitment_candidates', 'internship_months')) {
                $table->string('internship_months', 50)->nullable()->after('course_name');
            }
            if (! Schema::hasColumn('recruitment_candidates', 'has_stipend')) {
                $table->string('has_stipend', 10)->nullable()->after('internship_months');
            }
            if (! Schema::hasColumn('recruitment_candidates', 'stipend_amount')) {
                $table->decimal('stipend_amount', 12, 2)->nullable()->after('has_stipend');
            }
        });
    }

    public function down(): void
    {
        Schema::table('recruitment_candidates', function (Blueprint $table) {
            $table->dropColumn([
                'institute_name',
                'course_name',
                'internship_months',
                'has_stipend',
                'stipend_amount',
            ]);
        });
    }
};
