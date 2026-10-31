<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('payroll_settings')) {
            Schema::table('payroll_settings', function (Blueprint $table) {
                if (! Schema::hasColumn('payroll_settings', 'pf_gross_threshold')) {
                    $table->decimal('pf_gross_threshold', 10, 2)->default(21000.00)->after('pf_employer_percentage');
                }
                if (! Schema::hasColumn('payroll_settings', 'pf_basic_cap')) {
                    $table->decimal('pf_basic_cap', 10, 2)->default(15000.00)->after('pf_gross_threshold');
                }
            });

            // Update existing settings to ensure default dynamic formula values
            DB::table('payroll_settings')->update([
                'pf_gross_threshold'      => 21000.00,
                'pf_basic_cap'             => 15000.00,
                'esi_employee_percentage' => 4.00,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('payroll_settings')) {
            Schema::table('payroll_settings', function (Blueprint $table) {
                if (Schema::hasColumn('payroll_settings', 'pf_basic_cap')) {
                    $table->dropColumn('pf_basic_cap');
                }
                if (Schema::hasColumn('payroll_settings', 'pf_gross_threshold')) {
                    $table->dropColumn('pf_gross_threshold');
                }
            });
        }
    }
};
