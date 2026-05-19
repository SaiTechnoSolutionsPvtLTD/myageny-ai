<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_managements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->date('office_mopping_date')->nullable();
            $table->date('office_cleaning_date')->nullable();
            $table->date('toilet_cleaning_date')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_managements');
    }
};
