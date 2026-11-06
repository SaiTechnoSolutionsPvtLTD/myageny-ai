<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $perm = DB::table('permissions')->where('name', 'quotations.approve')->first();
        if (! $perm) {
            return;
        }

        // Branch admin and branch manager roles must not have quotation approval
        $branchRoles = DB::table('roles')->where(function ($q) {
            $q->where('name', 'like', '%branch_admin%')
              ->orWhere('name', 'like', '%branch_manager%')
              ->orWhere('name', 'like', '%_bm%');
        })->pluck('id');

        if ($branchRoles->isNotEmpty()) {
            DB::table('role_has_permissions')
                ->where('permission_id', $perm->id)
                ->whereIn('role_id', $branchRoles)
                ->delete();
        }

        // Remove quotations.approve from HR roles in role_has_permissions so approval is governed by canApproveQuotation()
        $hrRoles = DB::table('roles')->where('name', 'like', '%human_resource%')->orWhere('name', 'like', '%hr%')->pluck('id');
        if ($hrRoles->isNotEmpty()) {
            DB::table('role_has_permissions')
                ->where('permission_id', $perm->id)
                ->whereIn('role_id', $hrRoles)
                ->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse needed as branch roles should never have quotation approval
    }
};
