<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_onboardings', function (Blueprint $table) {
            $table->foreignId('source_intern_joining_form_id')
                ->nullable()
                ->after('employee_id')
                ->constrained('intern_joining_forms')
                ->nullOnDelete()
                ->unique();
        });
    }

    public function down(): void
    {
        Schema::table('employee_onboardings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_intern_joining_form_id');
        });
    }
};
