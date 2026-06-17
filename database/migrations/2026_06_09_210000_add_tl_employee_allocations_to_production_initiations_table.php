<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_initiations', function (Blueprint $table) {
            $table->json('tl_employee_allocations')->nullable()->after('project_allocated_tl_user_ids');
        });

        DB::table('production_initiations')
            ->select([
                'id',
                'project_allocated_tl_user_ids',
                'employee_allocation_status',
                'employee_allocated_at',
                'employee_allocated_by',
                'project_allocated_employee_user_ids',
            ])
            ->orderBy('id')
            ->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    $tlUserIds = json_decode((string) ($row->project_allocated_tl_user_ids ?? '[]'), true);

                    if (! is_array($tlUserIds) || $tlUserIds === []) {
                        continue;
                    }

                    $employeeUserIds = json_decode((string) ($row->project_allocated_employee_user_ids ?? '[]'), true);
                    $status = in_array($row->employee_allocation_status, ['allocated', 'allocation_pending'], true)
                        ? $row->employee_allocation_status
                        : 'allocation_pending';

                    $allocations = [];

                    foreach ($tlUserIds as $tlUserId) {
                        $tlUserId = (int) $tlUserId;

                        if ($tlUserId <= 0) {
                            continue;
                        }

                        $allocations[(string) $tlUserId] = [
                            'tl_user_id' => $tlUserId,
                            'status' => $status,
                            'allocated_at' => $row->employee_allocated_at,
                            'allocated_by' => $row->employee_allocated_by ? (int) $row->employee_allocated_by : null,
                            'employee_user_ids' => array_values(array_map('intval', is_array($employeeUserIds) ? $employeeUserIds : [])),
                        ];
                    }

                    if ($allocations === []) {
                        continue;
                    }

                    DB::table('production_initiations')
                        ->where('id', $row->id)
                        ->update(['tl_employee_allocations' => json_encode($allocations)]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('production_initiations', function (Blueprint $table) {
            $table->dropColumn('tl_employee_allocations');
        });
    }
};
