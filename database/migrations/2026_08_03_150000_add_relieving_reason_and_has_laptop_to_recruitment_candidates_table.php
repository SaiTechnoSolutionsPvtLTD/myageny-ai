<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruitment_candidates', function (Blueprint $table) {
            $table->string('relieving_reason', 255)->nullable()->after('previous_hr_contact');
            $table->string('has_laptop', 10)->nullable()->after('relieving_reason');
        });
    }

    public function down(): void
    {
        Schema::table('recruitment_candidates', function (Blueprint $table) {
            $table->dropColumn([
                'relieving_reason',
                'has_laptop',
            ]);
        });
    }
};
