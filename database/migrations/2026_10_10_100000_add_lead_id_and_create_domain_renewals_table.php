<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add lead_id to domain_records
        if (Schema::hasTable('domain_records')) {
            Schema::table('domain_records', function (Blueprint $table) {
                if (!Schema::hasColumn('domain_records', 'lead_id')) {
                    $table->unsignedBigInteger('lead_id')->nullable()->after('client_name')->index();
                }
            });
        }

        // 2. Create domain_renewals table
        if (!Schema::hasTable('domain_renewals')) {
            Schema::create('domain_renewals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('domain_record_id')->index();
                $table->date('renewal_date');
                $table->date('expires_at')->nullable();
                $table->decimal('amount', 12, 2)->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_renewals');

        if (Schema::hasTable('domain_records') && Schema::hasColumn('domain_records', 'lead_id')) {
            Schema::table('domain_records', function (Blueprint $table) {
                $table->dropColumn('lead_id');
            });
        }
    }
};
