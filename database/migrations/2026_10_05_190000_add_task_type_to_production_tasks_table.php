<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('production_tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('production_tasks', 'task_type')) {
                $table->string('task_type', 50)->default('daily')->after('status')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('production_tasks', 'task_type')) {
                $table->dropColumn('task_type');
            }
        });
    }
};
