<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('house_keeping_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('name', 150);
            $table->string('mobile_number', 50)->nullable();
            $table->text('address')->nullable();
            $table->decimal('salary', 12, 2)->default(0);
            $table->string('status', 20)->default('Active'); // Active / Inactive
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('house_keeping_employees');
    }
};
