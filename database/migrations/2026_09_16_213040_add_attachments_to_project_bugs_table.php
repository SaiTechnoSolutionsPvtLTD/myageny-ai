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
            if (! Schema::hasColumn('project_bugs', 'attachments')) {
                $table->json('attachments')->nullable()->after('attachment_original_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_bugs', function (Blueprint $table) {
            if (Schema::hasColumn('project_bugs', 'attachments')) {
                $table->dropColumn('attachments');
            }
        });
    }
};
