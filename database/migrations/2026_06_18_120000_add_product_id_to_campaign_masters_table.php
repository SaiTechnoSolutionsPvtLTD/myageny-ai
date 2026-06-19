<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('campaign_masters')) {
            return;
        }

        Schema::table('campaign_masters', function (Blueprint $table) {
            if (!Schema::hasColumn('campaign_masters', 'product_id')) {
                $table->foreignId('product_id')
                    ->nullable()
                    ->constrained('products')
                    ->nullOnDelete()
                    ->after('new_fields');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('campaign_masters') || !Schema::hasColumn('campaign_masters', 'product_id')) {
            return;
        }

        Schema::table('campaign_masters', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_id');
        });
    }
};
