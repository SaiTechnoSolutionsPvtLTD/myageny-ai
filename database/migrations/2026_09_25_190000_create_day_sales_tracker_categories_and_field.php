<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('day_sales_tracker_categories')) {
            Schema::create('day_sales_tracker_categories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->string('name', 100);
                $table->timestamps();
            });

            $defaults = [
                'Digital Marketing',
                'Web Development',
                'Mobile App',
                'Software Development',
                'Branding & Design',
                'SEO & SMM',
            ];
            foreach ($defaults as $name) {
                DB::table('day_sales_tracker_categories')->insertOrIgnore([
                    'name' => $name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (Schema::hasTable('lead_products') && !Schema::hasColumn('lead_products', 'day_sales_category')) {
            Schema::table('lead_products', function (Blueprint $table) {
                $table->string('day_sales_category', 100)->nullable()->after('product_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('lead_products', 'day_sales_category')) {
            Schema::table('lead_products', function (Blueprint $table) {
                $table->dropColumn('day_sales_category');
            });
        }

        Schema::dropIfExists('day_sales_tracker_categories');
    }
};
