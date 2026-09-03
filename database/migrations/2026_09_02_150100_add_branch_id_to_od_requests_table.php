<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mobile-only addition: the web OD Request module (OdRequestController,
 * views under resources/views/pages/hrms/od_requests) never scopes by
 * branch — it relies purely on company_id + the approval-hierarchy chain.
 * The mobile app introduces a strict "only see your own branch's OD
 * requests" rule that doesn't exist on web, so this column is nullable and
 * additive: web's existing `OdRequest::create([...])` call in
 * OdRequestController::store() never sets it and keeps working unchanged
 * (those rows simply get branch_id = null). Only the new mobile API
 * (OdRequestApiController) populates and filters on this column.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('od_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->index()->after('employee_id');
        });
    }

    public function down(): void
    {
        Schema::table('od_requests', function (Blueprint $table) {
            $table->dropColumn('branch_id');
        });
    }
};
