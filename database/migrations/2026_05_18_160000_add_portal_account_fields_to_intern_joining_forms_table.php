<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intern_joining_forms', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('emergency_contact_no');
            $table->foreignId('department_id')->nullable()->after('role_id');
            $table->foreignId('portal_user_id')->nullable()->after('department_id');

            $table->foreign('role_id', 'ijf_role_fk')->references('id')->on('roles')->nullOnDelete();
            $table->foreign('department_id', 'ijf_department_fk')->references('id')->on('departments')->nullOnDelete();
            $table->foreign('portal_user_id', 'ijf_portal_user_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('intern_joining_forms', function (Blueprint $table) {
            $table->dropForeign('ijf_role_fk');
            $table->dropForeign('ijf_department_fk');
            $table->dropForeign('ijf_portal_user_fk');
            $table->dropColumn(['role_id', 'department_id', 'portal_user_id']);
        });
    }
};
