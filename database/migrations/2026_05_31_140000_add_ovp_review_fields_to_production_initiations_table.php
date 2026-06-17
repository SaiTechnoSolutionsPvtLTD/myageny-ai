<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_initiations', function (Blueprint $table) {
            $table->date('welcome_call_date')->nullable()->after('status');
            $table->time('welcome_call_time')->nullable()->after('welcome_call_date');
            $table->string('client_name')->nullable()->after('welcome_call_time');
            $table->string('company_name')->nullable()->after('client_name');
            $table->timestamp('reviewed_at')->nullable()->after('company_name');
            $table->foreignId('reviewed_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('production_initiations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn([
                'welcome_call_date',
                'welcome_call_time',
                'client_name',
                'company_name',
                'reviewed_at',
            ]);
        });
    }
};
