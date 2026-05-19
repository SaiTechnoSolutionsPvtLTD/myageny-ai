<?php

use App\Models\InternJoiningForm;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intern_joining_forms', function (Blueprint $table) {
            $table->string('intern_id', 20)->nullable()->after('id')->unique();
        });

        DB::transaction(function () {
            $forms = InternJoiningForm::query()
                ->whereNull('intern_id')
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id']);

            $nextNumber = 1;

            foreach ($forms as $form) {
                $form->forceFill([
                    'intern_id' => InternJoiningForm::INTERN_ID_PREFIX . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT),
                ])->save();

                $nextNumber++;
            }
        });
    }

    public function down(): void
    {
        Schema::table('intern_joining_forms', function (Blueprint $table) {
            $table->dropUnique(['intern_id']);
            $table->dropColumn('intern_id');
        });
    }
};
