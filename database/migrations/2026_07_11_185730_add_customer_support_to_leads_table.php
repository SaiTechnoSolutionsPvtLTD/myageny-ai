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
        Schema::table('leads', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_support_tl_id')->nullable()->after('assigned_to');
            $table->unsignedBigInteger('customer_support_executive_id')->nullable()->after('customer_support_tl_id');
            $table->timestamp('customer_support_allocated_at')->nullable()->after('customer_support_executive_id');

            $table->foreign('customer_support_tl_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('customer_support_executive_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['customer_support_tl_id']);
            $table->dropForeign(['customer_support_executive_id']);
            $table->dropColumn(['customer_support_tl_id', 'customer_support_executive_id', 'customer_support_allocated_at']);
        });
    }
};
