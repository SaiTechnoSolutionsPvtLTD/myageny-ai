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
        Schema::table('support_tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('support_tickets', 'payment_amount')) {
                $table->decimal('payment_amount', 15, 2)->nullable()->after('remark');
            }
            if (!Schema::hasColumn('support_tickets', 'resolution_attachment_path')) {
                $table->string('resolution_attachment_path')->nullable()->after('payment_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('support_tickets', 'resolution_attachment_path')) {
                $columnsToDrop[] = 'resolution_attachment_path';
            }
            if (Schema::hasColumn('support_tickets', 'payment_amount')) {
                $columnsToDrop[] = 'payment_amount';
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
