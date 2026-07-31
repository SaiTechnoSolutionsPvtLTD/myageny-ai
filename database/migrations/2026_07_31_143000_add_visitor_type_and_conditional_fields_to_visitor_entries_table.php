<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visitor_entries', function (Blueprint $table) {
            $table->string('visitor_type', 50)->default('others')->after('visitor_name')->index();
            $table->string('email', 150)->nullable()->after('mobile_number');
            $table->string('applied_position', 150)->nullable()->after('email');
            $table->string('company_name', 150)->nullable()->after('applied_position');
        });
    }

    public function down(): void
    {
        Schema::table('visitor_entries', function (Blueprint $table) {
            $table->dropColumn(['visitor_type', 'email', 'applied_position', 'company_name']);
        });
    }
};
