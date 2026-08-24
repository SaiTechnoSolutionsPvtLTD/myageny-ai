<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Production Approval screen (mobile + web) filters on `status` (must be
// approval/approved) AND `production_approval_status` (pending/approval/
// approved/rejected/reject) simultaneously, then orders by `created_at`.
// The existing pi_production_approval_status_department_idx index leads
// with production_approval_status, which doesn't help the `status` half of
// that pair. This composite index lets the planner satisfy both whereIn()
// clauses from the index and avoid a full table scan as the table grows —
// see ProductionApprovalApiController::index().
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_initiations', function (Blueprint $table) {
            $table->index(['status', 'production_approval_status', 'created_at'], 'pi_status_approval_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('production_initiations', function (Blueprint $table) {
            $table->dropIndex('pi_status_approval_status_created_idx');
        });
    }
};
