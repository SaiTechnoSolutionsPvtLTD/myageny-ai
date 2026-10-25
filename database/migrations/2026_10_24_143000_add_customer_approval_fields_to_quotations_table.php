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
        Schema::table('quotations', function (Blueprint $table) {
            $table->string('customer_signature')->nullable()->after('customer_responded_at');
            $table->string('customer_location')->nullable()->after('customer_signature');
            $table->string('customer_latitude', 30)->nullable()->after('customer_location');
            $table->string('customer_longitude', 30)->nullable()->after('customer_latitude');
            $table->string('customer_ip', 45)->nullable()->after('customer_longitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn([
                'customer_signature',
                'customer_location',
                'customer_latitude',
                'customer_longitude',
                'customer_ip',
            ]);
        });
    }
};
