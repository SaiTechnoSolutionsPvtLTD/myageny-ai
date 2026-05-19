<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('facility_titles')) {
            Schema::create('facility_titles', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150);
                $table->text('description')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('name');
            });
        }

        if (! Schema::hasTable('facility_managements')) {
            Schema::create('facility_managements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('facility_title_id')->nullable()->constrained('facility_titles')->nullOnDelete();
                $table->string('title')->nullable();
                $table->date('entry_date')->nullable()->index();
                $table->time('entry_time')->nullable();
                $table->date('office_mopping_date')->nullable();
                $table->date('office_cleaning_date')->nullable();
                $table->date('toilet_cleaning_date')->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });

            return;
        }

        Schema::table('facility_managements', function (Blueprint $table) {
            if (! Schema::hasColumn('facility_managements', 'facility_title_id')) {
                $table->foreignId('facility_title_id')->nullable()->constrained('facility_titles')->nullOnDelete();
            }

            if (! Schema::hasColumn('facility_managements', 'entry_date')) {
                $table->date('entry_date')->nullable()->index();
            }

            if (! Schema::hasColumn('facility_managements', 'entry_time')) {
                $table->time('entry_time')->nullable();
            }
        });

        $this->backfillTitles();
        $this->backfillEntryDateTime();
    }

    public function down(): void
    {
        if (Schema::hasTable('facility_managements')) {
            Schema::table('facility_managements', function (Blueprint $table) {
                if (Schema::hasColumn('facility_managements', 'facility_title_id')) {
                    $table->dropConstrainedForeignId('facility_title_id');
                }

                if (Schema::hasColumn('facility_managements', 'entry_date')) {
                    $table->dropColumn('entry_date');
                }

                if (Schema::hasColumn('facility_managements', 'entry_time')) {
                    $table->dropColumn('entry_time');
                }
            });
        }

        Schema::dropIfExists('facility_titles');
    }

    private function backfillTitles(): void
    {
        if (! Schema::hasColumn('facility_managements', 'title')) {
            return;
        }

        $now = now();

        DB::table('facility_managements')
            ->whereNotNull('title')
            ->where('title', '<>', '')
            ->select('title')
            ->distinct()
            ->orderBy('title')
            ->pluck('title')
            ->each(function (string $title) use ($now) {
                DB::table('facility_titles')->updateOrInsert(
                    ['name' => $title],
                    [
                        'description' => null,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            });

        DB::table('facility_managements')
            ->whereNull('facility_title_id')
            ->whereNotNull('title')
            ->where('title', '<>', '')
            ->orderBy('id')
            ->chunkById(100, function ($entries) {
                foreach ($entries as $entry) {
                    $titleId = DB::table('facility_titles')
                        ->where('name', $entry->title)
                        ->value('id');

                    if ($titleId) {
                        DB::table('facility_managements')
                            ->where('id', $entry->id)
                            ->update(['facility_title_id' => $titleId]);
                    }
                }
            });
    }

    private function backfillEntryDateTime(): void
    {
        $dateParts = [];

        foreach (['entry_date', 'office_mopping_date', 'office_cleaning_date', 'toilet_cleaning_date'] as $column) {
            if (Schema::hasColumn('facility_managements', $column)) {
                $dateParts[] = "`{$column}`";
            }
        }

        if (Schema::hasColumn('facility_managements', 'created_at')) {
            $dateParts[] = 'DATE(`created_at`)';
        }

        $dateExpression = $dateParts === [] ? 'CURRENT_DATE' : 'COALESCE(' . implode(', ', $dateParts) . ')';
        $timeExpression = Schema::hasColumn('facility_managements', 'created_at')
            ? 'COALESCE(`entry_time`, TIME(`created_at`), "00:00:00")'
            : 'COALESCE(`entry_time`, "00:00:00")';

        DB::statement("
            UPDATE `facility_managements`
            SET `entry_date` = {$dateExpression},
                `entry_time` = {$timeExpression}
            WHERE `entry_date` IS NULL OR `entry_time` IS NULL
        ");
    }
};
