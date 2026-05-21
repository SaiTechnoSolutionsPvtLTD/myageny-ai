<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addCompanyColumn('employee_onboardings');
        $this->addCompanyColumn('intern_joining_forms');
        $this->addCompanyColumn('daily_attendances');
        $this->addCompanyColumn('leave_requests');
        $this->addCompanyColumn('permission_requests');
        $this->addCompanyColumn('leave_types');
        $this->addCompanyColumn('holiday_calendars');

        $this->backfillEmployeeOnboardings();
        $this->backfillInternJoiningForms();
        $this->backfillDailyAttendances();
        $this->backfillLeaveRequests();
        $this->backfillPermissionRequests();
    }

    public function down(): void
    {
        $this->dropCompanyColumn('holiday_calendars');
        $this->dropCompanyColumn('leave_types');
        $this->dropCompanyColumn('permission_requests');
        $this->dropCompanyColumn('leave_requests');
        $this->dropCompanyColumn('daily_attendances');
        $this->dropCompanyColumn('intern_joining_forms');
        $this->dropCompanyColumn('employee_onboardings');
    }

    private function addCompanyColumn(string $table): void
    {
        if (Schema::hasColumn($table, 'company_id')) {
            return;
        }

        Schema::table($table, function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->index();
        });
    }

    private function dropCompanyColumn(string $table): void
    {
        if (! Schema::hasColumn($table, 'company_id')) {
            return;
        }

        Schema::table($table, function (Blueprint $table) {
            $table->dropIndex($table->getTable() . '_company_id_index');
        });

        Schema::table($table, function (Blueprint $table) {
            $table->dropColumn('company_id');
        });
    }

    private function backfillEmployeeOnboardings(): void
    {
        DB::table('employee_onboardings as eo')
            ->leftJoin('users as u', 'u.id', '=', 'eo.portal_user_id')
            ->select('eo.id', 'u.company_id')
            ->orderBy('eo.id')
            ->chunk(200, function ($rows) {
                foreach ($rows as $row) {
                    if ($row->company_id === null) {
                        continue;
                    }

                    DB::table('employee_onboardings')
                        ->where('id', $row->id)
                        ->whereNull('company_id')
                        ->update(['company_id' => $row->company_id]);
                }
            });
    }

    private function backfillInternJoiningForms(): void
    {
        DB::table('intern_joining_forms as ijf')
            ->leftJoin('users as u', 'u.id', '=', 'ijf.portal_user_id')
            ->select('ijf.id', 'u.company_id')
            ->orderBy('ijf.id')
            ->chunk(200, function ($rows) {
                foreach ($rows as $row) {
                    if ($row->company_id === null) {
                        continue;
                    }

                    DB::table('intern_joining_forms')
                        ->where('id', $row->id)
                        ->whereNull('company_id')
                        ->update(['company_id' => $row->company_id]);
                }
            });
    }

    private function backfillDailyAttendances(): void
    {
        $query = DB::table('daily_attendances as da')
            ->leftJoin('employee_onboardings as eo', 'eo.id', '=', 'da.employee_id')
            ->select('da.id', 'eo.company_id as employee_company_id')
            ->orderBy('da.id');

        if (Schema::hasColumn('daily_attendances', 'intern_joining_form_id')) {
            $query->leftJoin('intern_joining_forms as ijf', 'ijf.id', '=', 'da.intern_joining_form_id')
                ->addSelect('ijf.company_id as intern_company_id');
        }

        $query->chunk(200, function ($rows) {
            foreach ($rows as $row) {
                $companyId = $row->employee_company_id ?? ($row->intern_company_id ?? null);

                if ($companyId === null) {
                    continue;
                }

                DB::table('daily_attendances')
                    ->where('id', $row->id)
                    ->whereNull('company_id')
                    ->update(['company_id' => $companyId]);
            }
        });
    }

    private function backfillLeaveRequests(): void
    {
        DB::table('leave_requests as lr')
            ->leftJoin('users as u', 'u.id', '=', 'lr.user_id')
            ->select('lr.id', 'u.company_id')
            ->orderBy('lr.id')
            ->chunk(200, function ($rows) {
                foreach ($rows as $row) {
                    if ($row->company_id === null) {
                        continue;
                    }

                    DB::table('leave_requests')
                        ->where('id', $row->id)
                        ->whereNull('company_id')
                        ->update(['company_id' => $row->company_id]);
                }
            });
    }

    private function backfillPermissionRequests(): void
    {
        DB::table('permission_requests as pr')
            ->leftJoin('users as u', 'u.id', '=', 'pr.user_id')
            ->select('pr.id', 'u.company_id')
            ->orderBy('pr.id')
            ->chunk(200, function ($rows) {
                foreach ($rows as $row) {
                    if ($row->company_id === null) {
                        continue;
                    }

                    DB::table('permission_requests')
                        ->where('id', $row->id)
                        ->whereNull('company_id')
                        ->update(['company_id' => $row->company_id]);
                }
            });
    }
};
