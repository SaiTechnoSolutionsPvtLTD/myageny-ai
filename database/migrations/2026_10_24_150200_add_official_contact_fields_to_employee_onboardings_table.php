<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employee_onboardings')) {
            Schema::table('employee_onboardings', function (Blueprint $table) {
                if (! Schema::hasColumn('employee_onboardings', 'official_mobile')) {
                    $table->string('official_mobile', 20)->nullable()->after('mobile');
                }
                if (! Schema::hasColumn('employee_onboardings', 'official_email')) {
                    $table->string('official_email', 150)->nullable()->after('email');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('employee_onboardings')) {
            Schema::table('employee_onboardings', function (Blueprint $table) {
                if (Schema::hasColumn('employee_onboardings', 'official_mobile')) {
                    $table->dropColumn('official_mobile');
                }
                if (Schema::hasColumn('employee_onboardings', 'official_email')) {
                    $table->dropColumn('official_email');
                }
            });
        }
    }
};
