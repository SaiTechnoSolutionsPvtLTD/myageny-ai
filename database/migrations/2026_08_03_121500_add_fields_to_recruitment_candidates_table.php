<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruitment_candidates', function (Blueprint $table) {
            $table->string('candidate_type', 30)->default('fresher')->after('job_title');
            $table->string('source_details', 255)->nullable()->after('source');
            $table->string('previous_company', 150)->nullable()->after('experience_years');
            $table->string('previous_hr_name', 150)->nullable()->after('previous_company');
            $table->string('previous_hr_contact', 50)->nullable()->after('previous_hr_name');
            $table->json('education_details')->nullable()->after('previous_hr_contact');
        });
    }

    public function down(): void
    {
        Schema::table('recruitment_candidates', function (Blueprint $table) {
            $table->dropColumn([
                'candidate_type',
                'source_details',
                'previous_company',
                'previous_hr_name',
                'previous_hr_contact',
                'education_details',
            ]);
        });
    }
};
