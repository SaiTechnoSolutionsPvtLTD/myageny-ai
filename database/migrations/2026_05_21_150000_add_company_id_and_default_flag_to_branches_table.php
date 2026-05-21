<?php

use App\Models\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (! Schema::hasColumn('branches', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id')->index();
            }

            if (! Schema::hasColumn('branches', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('manager_id');
            }
        });

        DB::table('branches as b')
            ->leftJoin('users as u', 'u.branch_id', '=', 'b.id')
            ->whereNull('b.company_id')
            ->whereNotNull('u.company_id')
            ->select('b.id', DB::raw('MIN(u.company_id) as company_id'))
            ->groupBy('b.id')
            ->orderBy('b.id')
            ->chunk(100, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('branches')
                        ->where('id', $row->id)
                        ->whereNull('company_id')
                        ->update(['company_id' => $row->company_id]);
                }
            });

        Company::query()->orderBy('id')->each(function (Company $company) {
            $branch = DB::table('branches')
                ->where('company_id', $company->id)
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->first();

            if ($branch) {
                DB::table('branches')
                    ->where('id', $branch->id)
                    ->update(['is_default' => true]);

                return;
            }

            DB::table('branches')->insert([
                'company_id' => $company->id,
                'name' => 'Default Branch',
                'code' => 'CMP' . $company->id . '-MAIN',
                'address' => $company->address,
                'email' => $company->email,
                'phone' => $company->mobile_number,
                'is_active' => true,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'is_default')) {
                $table->dropColumn('is_default');
            }

            if (Schema::hasColumn('branches', 'company_id')) {
                $table->dropIndex('branches_company_id_index');
                $table->dropColumn('company_id');
            }
        });
    }
};
