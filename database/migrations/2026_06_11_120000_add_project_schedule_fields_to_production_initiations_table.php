<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_initiations', function (Blueprint $table) {
            $table->date('project_delivery_date')->nullable()->after('employee_allocated_by');
            $table->string('project_execution_status')->default('ontrack')->after('project_delivery_date');
        });
    }

    public function down(): void
    {
        Schema::table('production_initiations', function (Blueprint $table) {
            $table->dropColumn(['project_delivery_date', 'project_execution_status']);
        });
    }
};
