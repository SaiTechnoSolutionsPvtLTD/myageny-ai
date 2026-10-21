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
            $table->unsignedBigInteger('account_support_category_id')->nullable()->after('company_id')->index();
            $table->unsignedBigInteger('account_support_subcategory_id')->nullable()->after('account_support_category_id')->index();
            $table->json('attachments')->nullable()->after('attachment_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropColumn([
                'account_support_category_id',
                'account_support_subcategory_id',
                'attachments',
            ]);
        });
    }
};
