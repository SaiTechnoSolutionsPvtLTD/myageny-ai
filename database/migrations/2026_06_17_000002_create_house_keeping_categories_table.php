<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('house_keeping_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        $now = Carbon::now();

        DB::table('house_keeping_categories')->insert([
            ['name' => 'Office', 'description' => 'Office area cleaning and upkeep.', 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ladies Toilet', 'description' => 'Ladies toilet hygiene and cleaning tasks.', 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'House', 'description' => 'Household support and utility cleaning works.', 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('house_keeping_categories');
    }
};
