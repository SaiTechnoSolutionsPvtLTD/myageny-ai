<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// OvpModuleApiController::index() now paginates at the DB level, one bucket
// at a time (see that controller's docblock). Every bucket query filters on
// `status` and — for the 'new'/'overdue' split — a `created_at` range, then
// sorts by `created_at`. The existing pi_status_approval_status_created_idx
// index (added for Production Approval) leads with `status` but has
// `production_approval_status` sandwiched before `created_at`, so it can't
// serve an efficient status + created_at range scan on its own. This
// dedicated composite index covers exactly the OVP bucket query shape.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_initiations', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'pi_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('production_initiations', function (Blueprint $table) {
            $table->dropIndex('pi_status_created_idx');
        });
    }
};
