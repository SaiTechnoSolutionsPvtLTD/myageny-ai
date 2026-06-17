<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_initiations', function (Blueprint $table) {
            $table->string('production_approval_status')->nullable()->after('reviewed_by');
            $table->timestamp('production_approval_reviewed_at')->nullable()->after('production_approval_status');
            $table->foreignId('production_approval_reviewed_by')->nullable()->after('production_approval_reviewed_at')->constrained('users')->nullOnDelete();

            $table->index(['production_approval_status', 'department_id'], 'pi_production_approval_status_department_idx');
        });
    }

    public function down(): void
    {
        Schema::table('production_initiations', function (Blueprint $table) {
            $table->dropIndex('pi_production_approval_status_department_idx');
            $table->dropConstrainedForeignId('production_approval_reviewed_by');
            $table->dropColumn([
                'production_approval_status',
                'production_approval_reviewed_at',
            ]);
        });
    }
};
