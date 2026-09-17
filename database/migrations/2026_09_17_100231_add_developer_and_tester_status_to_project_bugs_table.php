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
        Schema::table('project_bugs', function (Blueprint $table) {
            if (! Schema::hasColumn('project_bugs', 'developer_status')) {
                $table->string('developer_status', 30)->default('pending')->after('status');
            }
            if (! Schema::hasColumn('project_bugs', 'tester_status')) {
                $table->string('tester_status', 30)->default('pending')->after('developer_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_bugs', function (Blueprint $table) {
            if (Schema::hasColumn('project_bugs', 'tester_status')) {
                $table->dropColumn('tester_status');
            }
            if (Schema::hasColumn('project_bugs', 'developer_status')) {
                $table->dropColumn('developer_status');
            }
        });
    }
};
