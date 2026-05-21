<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            if (! Schema::hasColumn('departments', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id')->index();
            }

            if (! Schema::hasColumn('departments', 'dashboard_route')) {
                $table->string('dashboard_route', 120)->nullable()->after('description');
            }
        });

        if (Schema::hasColumn('departments', 'company_id')) {
            DB::table('departments as d')
                ->join('roles as r', 'r.department_id', '=', 'd.id')
                ->whereNull('d.company_id')
                ->whereNotNull('r.company_id')
                ->select('d.id', DB::raw('MIN(r.company_id) as company_id'))
                ->groupBy('d.id')
                ->orderBy('d.id')
                ->chunk(100, function ($rows) {
                    foreach ($rows as $row) {
                        DB::table('departments')
                            ->where('id', $row->id)
                            ->whereNull('company_id')
                            ->update(['company_id' => $row->company_id]);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            if (Schema::hasColumn('departments', 'dashboard_route')) {
                $table->dropColumn('dashboard_route');
            }

            if (Schema::hasColumn('departments', 'company_id')) {
                $table->dropIndex('departments_company_id_index');
                $table->dropColumn('company_id');
            }
        });
    }
};
