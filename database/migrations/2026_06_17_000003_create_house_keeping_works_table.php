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
        Schema::create('house_keeping_works', function (Blueprint $table) {
            $table->id();
            $table->foreignId('house_keeping_category_id')->constrained('house_keeping_categories');
            $table->string('work_name', 255);
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        $now = Carbon::now();
        $categories = DB::table('house_keeping_categories')->pluck('id', 'name');

        DB::table('house_keeping_works')->insert([
            ['house_keeping_category_id' => $categories['Office'], 'work_name' => 'Filling of water cans', 'notes' => null, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['house_keeping_category_id' => $categories['Office'], 'work_name' => 'Tea/Coffee preparation', 'notes' => null, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['house_keeping_category_id' => $categories['Office'], 'work_name' => 'Sweep and mop office floors', 'notes' => null, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['house_keeping_category_id' => $categories['Office'], 'work_name' => 'Dust and wipe desks, chairs, and tables', 'notes' => null, 'sort_order' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['house_keeping_category_id' => $categories['Ladies Toilet'], 'work_name' => 'Sweep and mop toilet floors', 'notes' => null, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['house_keeping_category_id' => $categories['Ladies Toilet'], 'work_name' => 'Clean and disinfect toilet seats', 'notes' => null, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['house_keeping_category_id' => $categories['Ladies Toilet'], 'work_name' => 'Scrub and sanitize sinks and taps', 'notes' => null, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['house_keeping_category_id' => $categories['House'], 'work_name' => 'Vessels washing', 'notes' => null, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['house_keeping_category_id' => $categories['House'], 'work_name' => 'Drying clothes', 'notes' => null, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['house_keeping_category_id' => $categories['House'], 'work_name' => 'Sweep and mop all rooms', 'notes' => null, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('house_keeping_works');
    }
};
