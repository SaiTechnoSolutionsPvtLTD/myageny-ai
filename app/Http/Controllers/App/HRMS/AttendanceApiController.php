<?php

namespace App\Http\Controllers\App\HRMS;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\DailyAttendance;
use App\Models\EmployeeOnboarding;
use App\Models\InternJoiningForm;
use App\Models\PayrollSetting;
use App\Models\User;
use App\Services\DataVisibilityService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceApiController extends Controller
{
    private const EARLY_LOGIN_BEFORE = '09:00:00';

    // ── Permission helpers (mirrors AttendanceController) ────────────────────

    private function canViewAllAttendance(): bool
    {
        $user = auth()->user();
        return (bool) ($user && (
            $user->isSystemAdmin()
            || $user->belongsToHrDepartment()
            || $user->hasHrLikeRole()
            || $user->isCompanyAdmin()
            || $user->isCbo()
            || app(\App\Services\DataVisibilityService::class)->isCompanyWideUser($user)
            || ($user->isBranchAdmin() && ! $user->isBranchManager())
            || app(\App\Services\DataVisibilityService::class)->hasBranchAdminRole($user)
        ));
    }

    private function hasMappedTeamMembers(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->managedUsers()->exists()
            || app(DataVisibilityService::class)->descendantUserIds($user)->isNotEmpty();
    }

    private function canManageOrViewTeam(): bool
    {
        return $this->canViewAllAttendance() || $this->hasMappedTeamMembers();
    }

    private function canManageAttendance(): bool
    {
        return $this->canViewAllAttendance() || (bool) auth()->user()?->isBranchManager();
    }

    private function shouldFilterByBranch(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if ($user->isBranchManager() || $user->isBranchAdmin()) {
            return true;
        }

        if (! $this->canViewAllAttendance()) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->isSystemAdmin()) {
            return false;
        }

        if ($user->isCompanyAdmin()) {
            return false;
        }

        $keys = collect($user->roleKeys()->all());
        $exemptRoles = [
            'company_admin',
            'coo',
            'cbo',
            'cheif_operating_officer',
            'chief_operating_officer',
            'chief_business_officer',
        ];

        if ($keys->intersect($exemptRoles)->isNotEmpty()) {
            return false;
        }

        return true;
    }

    private function isCompanyAdminUser(?\App\Models\User $user): bool
    {
        if (! $user) {
            return false;
        }

        return (bool) ($user->isSuperAdmin()
            || $user->isSystemAdmin()
            || $user->isCompanyAdmin()
            || $user->hasRole('company_admin'));
    }

    private function attendanceBranches(): \Illuminate\Support\Collection
    {
        $user = auth()->user();
        if (! $user) {
            return collect();
        }

        $query = Branch::query()
            ->where('is_active', true)
            ->orderBy('name');

        if ($user->company_id) {
            $query->where('company_id', $user->company_id);
        }

        if ($this->shouldFilterByBranch()) {
            $branchIds = $user->getMyBranchIds() ?? [];
            if (!empty($branchIds)) {
                $query->whereIn('id', $branchIds);
            } else {
                return collect();
            }
        }

        return $query->get(['id', 'name']);
    }

    private function attendanceDepartments(): \Illuminate\Support\Collection
    {
        $user = auth()->user();
        $query = \App\Models\Department::query()
            ->withoutGlobalScope('company')
            ->orderBy('name');

        if ($user?->company_id) {
            $query->where(function ($q) use ($user) {
                $q->where('company_id', $user->company_id)
                    ->orWhereNull('company_id');
            });
        }

        return $query->get(['id', 'name']);
    }

    private function resolveActingBranchIds(?Request $request = null): ?array
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        $request = $request ?? request();
        $requestedBranchId = $request->input('branch_id');

        // If user is branch-scoped (e.g. Branch Manager)
        if ($this->shouldFilterByBranch()) {
            $myBranchIds = $user->getMyBranchIds() ?? [];
            if (empty($myBranchIds) && $user->branch_id) {
                $myBranchIds = [(int) $user->branch_id];
            }

            if (filled($requestedBranchId) && $requestedBranchId !== 'all') {
                $reqId = (int) $requestedBranchId;
                if (in_array($reqId, $myBranchIds)) {
                    return [$reqId];
                }
            }
            return !empty($myBranchIds) ? $myBranchIds : null;
        }

        // For exempt roles (Company Admin, CBO, COO, Super Admin, System Admin)
        if ($this->canViewAllAttendance()) {
            if (filled($requestedBranchId) && $requestedBranchId !== 'all') {
                return [(int) $requestedBranchId];
            }
            if ($requestedBranchId === 'all') {
                return null;
            }
            // Initially scope to the logged-in user's branch
            return $user->branch_id ? [(int) $user->branch_id] : null;
        }

        return $user->branch_id ? [(int) $user->branch_id] : null;
    }

    private function resolveActingBranchId(?Request $request = null): ?int
    {
        $ids = $this->resolveActingBranchIds($request);
        return (!empty($ids) && count($ids) === 1) ? $ids[0] : null;
    }

    private function currentEmployee(): ?EmployeeOnboarding
    {
        $user = auth()->user();

        return $this->withActivePortalAccount(
            EmployeeOnboarding::query()
                ->where(function ($query) use ($user) {
                    $query->where('portal_user_id', $user?->id)
                        ->orWhere('email', $user?->email);
                })
                ->active()
        )
            ->latest('id')
            ->first();
    }

    /**
     * Excludes an employee/intern whose linked portal login account has been
     * deactivated (User.is_active = false) — mirrors
     * AttendanceController::activeEmployeesQuery()/activeInternsQuery()
     * exactly (web). This is a *separate* flag from EmployeeOnboarding's own
     * `status` column (active/resigned, already covered by the `active()`
     * scope): an employee can stay status=active in their onboarding record
     * while HR deactivates just their portal account, and that deactivation
     * is what the app's Employee module surfaces as "Inactive". Records with
     * no linked portal account at all (portal_user_id null) are left alone,
     * same as web. Applied everywhere an employee/intern is listed, searched,
     * or selected in the Attendance module so a deactivated user's row can
     * never appear (as "Absent" or any other status).
     */
    private function withActivePortalAccount(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('portal_user_id')
                ->orWhereHas('portalUser', fn ($uq) => $uq->where('is_active', true));
        });
    }

    /**
     * Returns a collection of accessible attendees:
     * - HR / Admin / Super Admin: all employees + interns (branch-filtered if branch admin/manager)
     * - Team Lead / Manager: their assigned subordinates (direct + indirect) + themselves
     * - Regular Employee / Intern: only their own record
     * Each item: ['id', 'attendee_type', 'display_id', 'name', 'photo_url', 'select_key', 'branch_name', 'department_name']
     */
    private function accessibleAttendees(array|int|null $branchIds = null, ?int $departmentId = null): \Illuminate\Support\Collection
    {
        if (is_int($branchIds)) {
            $branchIds = [$branchIds];
        }

        $employeeQuery = $this->withActivePortalAccount(
            EmployeeOnboarding::query()->with(['department', 'portalUser.branch'])->active()
        )->whereNotNull('name');

        $internQuery = $this->withActivePortalAccount(
            InternJoiningForm::query()->with(['department', 'portalUser.branch'])->active()
        )->whereNotNull('name');

        if (! $this->canViewAllAttendance()) {
            $user = auth()->user();

            if (! $user) {
                return collect();
            }

            /** @var \App\Services\DataVisibilityService $visibility */
            $visibility = app(\App\Services\DataVisibilityService::class);
            $mappedUserIds = $visibility->descendantUserIds($user)->push($user->id)->unique()->values();

            $mappedUsers = \App\Models\User::whereIn('id', $mappedUserIds)->get(['id', 'email']);
            $mappedPortalUserIds = $mappedUsers->pluck('id')->filter()->values()->all();
            $mappedEmails = $mappedUsers->pluck('email')->filter()->values()->all();

            $employeeQuery->where(function (Builder $query) use ($mappedPortalUserIds, $mappedEmails) {
                $query->whereIn('portal_user_id', $mappedPortalUserIds)
                    ->orWhereIn('email', $mappedEmails);
            });

            $internQuery->where(function (Builder $query) use ($mappedPortalUserIds, $mappedEmails) {
                $query->whereIn('portal_user_id', $mappedPortalUserIds)
                    ->orWhereIn('email', $mappedEmails);
            });
        }

        if ($this->canViewAllAttendance()) {
            if ($this->shouldFilterByBranch()) {
                $myBranchIds = auth()->user()?->getMyBranchIds() ?? [];
                if (empty($myBranchIds) && auth()->user()?->branch_id) {
                    $myBranchIds = [(int) auth()->user()->branch_id];
                }

                if (!empty($branchIds)) {
                    $targetBranchIds = array_values(array_intersect($branchIds, $myBranchIds));
                    if (empty($targetBranchIds)) {
                        $targetBranchIds = $myBranchIds;
                    }
                } else {
                    $targetBranchIds = $myBranchIds;
                }

                if (!empty($targetBranchIds)) {
                    $branchCodes = Branch::withoutGlobalScopes()->whereIn('id', $targetBranchIds)->pluck('code')->filter()->all();
                    $employeeQuery->where(function (Builder $q) use ($targetBranchIds, $branchCodes) {
                        $q->whereHas('portalUser', function ($puQ) use ($targetBranchIds) {
                            $puQ->inBranches($targetBranchIds);
                        });
                        foreach ($branchCodes as $code) {
                            $q->orWhere('employee_id', 'like', $code . '%');
                        }
                    });
                    $internQuery->where(function (Builder $q) use ($targetBranchIds, $branchCodes) {
                        $q->whereHas('portalUser', function ($puQ) use ($targetBranchIds) {
                            $puQ->inBranches($targetBranchIds);
                        });
                        foreach ($branchCodes as $code) {
                            $q->orWhere('intern_id', 'like', $code . '%');
                        }
                    });
                }
            } else {
                // Roles exempt from branch restriction (Company Admin, CBO, COO, Super Admin, System Admin)
                if (!empty($branchIds)) {
                    $branchCodes = Branch::withoutGlobalScopes()->whereIn('id', $branchIds)->pluck('code')->filter()->all();
                    $employeeQuery->where(function (Builder $sub) use ($branchIds, $branchCodes) {
                        $sub->whereHas('portalUser', fn (Builder $pu) => $pu->inBranches($branchIds));
                        foreach ($branchCodes as $code) {
                            $sub->orWhere(function (Builder $q2) use ($code) {
                                $q2->whereNull('portal_user_id')->where('employee_id', 'like', $code . '%');
                            });
                        }
                    });
                    $internQuery->where(function (Builder $sub) use ($branchIds, $branchCodes) {
                        $sub->whereHas('portalUser', fn (Builder $pu) => $pu->inBranches($branchIds));
                        foreach ($branchCodes as $code) {
                            $sub->orWhere(function (Builder $q2) use ($code) {
                                $q2->whereNull('portal_user_id')->where('intern_id', 'like', $code . '%');
                            });
                        }
                    });
                }
            }
        }

        if ($departmentId && $departmentId > 0) {
            $employeeQuery->where('department_id', $departmentId);
            $internQuery->where('department_id', $departmentId);
        }

        $employees = $employeeQuery
            ->orderBy('name')
            ->get(['id', 'employee_id', 'name', 'status', 'photograph', 'department_id', 'portal_user_id'])
            ->map(fn(EmployeeOnboarding $e) => [
                'id'              => $e->id,
                'attendee_type'   => 'employee',
                'display_id'      => (string) $e->employee_id,
                'name'            => $e->name,
                'photo_url'       => $e->photograph ? asset('storage/' . $e->photograph) : null,
                'select_key'      => 'employee:' . $e->id,
                'branch_id'       => $e->branch?->id ?? $e->portalUser?->branch_id,
                'branch_name'     => $e->branch_name !== '—' ? $e->branch_name : ($e->portalUser?->branch?->name ?? ''),
                'department_id'   => $e->department_id,
                'department_name' => $e->department?->name ?? '',
            ]);

        $interns = $internQuery
            ->orderBy('name')
            ->get(['id', 'intern_id', 'name', 'photograph', 'department_id', 'portal_user_id'])
            ->map(fn(InternJoiningForm $i) => [
                'id'              => $i->id,
                'attendee_type'   => 'intern',
                'display_id'      => (string) ($i->intern_id ?: 'INT-' . $i->id),
                'name'            => $i->name,
                'photo_url'       => $i->photograph ? asset('storage/' . $i->photograph) : null,
                'select_key'      => 'intern:' . $i->id,
                'branch_id'       => $i->branch?->id ?? $i->portalUser?->branch_id,
                'branch_name'     => $i->branch_name !== '—' ? $i->branch_name : ($i->portalUser?->branch?->name ?? ''),
                'department_id'   => $i->department_id,
                'department_name' => $i->department?->name ?? '',
            ]);

        return $employees->concat($interns)
            ->sortBy(fn(array $a) => strtolower(trim($a['name'])))
            ->values();
    }

    private function graceLoginTime(): string
    {
        return (string) (PayrollSetting::forCompany(auth()->user()?->company_id)->grace_login_time ?: '09:30:00');
    }

    private function resolveLoginTiming(?string $loginTime): ?string
    {
        if (! $loginTime) {
            return null;
        }

        if ($loginTime <= self::EARLY_LOGIN_BEFORE) {
            return 'early';
        }

        if ($loginTime > $this->graceLoginTime()) {
            return 'late';
        }

        return 'on-time';
    }

    private function normalize(?string $value): string
    {
        return strtolower(trim((string) $value));
    }

    /**
     * Mirrors AttendanceController::parseAttendeeKey() — 'employee:12' →
     * ['employee', 12]. Used by lookup/store/storeCheckout below.
     */
    private function parseAttendeeKey(string $attendeeKey): array
    {
        $parts = explode(':', $attendeeKey, 2);

        return [
            $parts[0] ?? '',
            isset($parts[1]) ? (int) $parts[1] : 0,
        ];
    }

    /**
     * Shared branch-scoping check for a specific attendance record — reused by
     * show(), updateCheckIn(), and updateCheckOut() rather than re-inlining
     * the same contains() lookup in each.
     */
    private function isAccessibleRecord(DailyAttendance $attendance): bool
    {
        return $this->accessibleAttendees()->contains(function (array $attendee) use ($attendance) {
            if ($attendance->attendee_type === 'employee') {
                return $attendee['attendee_type'] === 'employee' && $attendee['id'] === $attendance->employee_id;
            }

            return $attendee['attendee_type'] === 'intern' && $attendee['id'] === $attendance->intern_joining_form_id;
        });
    }

    /** Mirrors AttendanceController::calculateWorkingHours(). */
    private function calculateWorkingHours(string $attendanceDate, string $loginTime, ?string $logoutTime): ?string
    {
        if (! $logoutTime || ! $loginTime) {
            return null;
        }

        $loginAt  = Carbon::parse($attendanceDate . ' ' . $loginTime);
        $logoutAt = Carbon::parse($attendanceDate . ' ' . $logoutTime);
        $seconds  = (int) max($loginAt->diffInSeconds($logoutAt, false), 0);

        return sprintf(
            '%02d:%02d:%02d',
            floor($seconds / 3600),
            floor(($seconds % 3600) / 60),
            $seconds % 60
        );
    }

    /** Mirrors AttendanceController::leaveCategoryLabel(). */
    private function leaveCategoryLabel(?string $leaveCategory): ?string
    {
        return match ($leaveCategory) {
            'paid' => 'Paid Leave',
            'lop' => 'Loss of Pay',
            'half_day' => 'Half Day Leave',
            default => null,
        };
    }

    /** Mirrors AttendanceController::leaveSessionLabel(). */
    private function leaveSessionLabel(?string $leaveSession): ?string
    {
        return match ($leaveSession) {
            'first_half' => 'First Half',
            'second_half' => 'Second Half',
            default => null,
        };
    }

    /** Mirrors AttendanceController::buildLeaveLabel(). */
    private function buildLeaveLabel(?string $leaveCategory, ?string $leaveSession): ?string
    {
        $categoryLabel = $this->leaveCategoryLabel($leaveCategory);

        if (! $categoryLabel) {
            return null;
        }

        if ($leaveCategory !== 'half_day') {
            return $categoryLabel;
        }

        $sessionLabel = $this->leaveSessionLabel($leaveSession);

        return $sessionLabel ? $categoryLabel . ' - ' . $sessionLabel : $categoryLabel;
    }

    // ── API endpoints ────────────────────────────────────────────────────────

    /**
     * GET /mobile/hrms/attendance
     *
     * Query params:
     *   date_from        (Y-m-d) + date_to (Y-m-d) — preferred, supports any range
     *   attendance_date  (Y-m-d) — back-compat single-day shortcut, still
     *                     supported since nothing else needs to change to
     *                     keep working; treated as date_from == date_to.
     *                     Ignored if date_from/date_to are present.
     *   (no date param at all) — defaults to the current month to date,
     *                     matching this app's other quick-filter screens
     *                     and the web Attendance page's own default.
     *   status           (present|absent|leave)
     *   login_timing     (early|late|on-time)
     *   employee_name    (string search — HR only)
     *   employee_id      (string search — HR only)
     *   attendee_type    (employee|intern — HR only)
     *   per_page         (int, default 15)
     *   page             (int, default 1)
     */
    public function index(Request $request): JsonResponse
    {
        $rules = [
            'attendance_date' => ['nullable', 'date'],
            'date_from'       => ['nullable', 'date'],
            'date_to'         => ['nullable', 'date', 'after_or_equal:date_from'],
            'status'          => ['nullable', 'in:present,absent,leave,od'],
            'login_timing'    => ['nullable', 'in:early,late,on-time'],
            'outside_office'  => ['nullable', 'in:checkin,checkout,any'],
            'per_page'        => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'            => ['nullable', 'integer', 'min:1'],
            'branch_id'       => ['nullable', 'string'],
            'department_id'   => ['nullable', 'string'],
        ];

        // HR/Admin/TL/Manager can filter by name, employee_id, attendee_type
        if ($this->canManageOrViewTeam()) {
            $rules['employee_name'] = ['nullable', 'string', 'max:255'];
            $rules['employee_id']   = ['nullable', 'string', 'max:255'];
            $rules['attendee_type'] = ['nullable', 'in:employee,intern'];
        }

        $validated = $request->validate($rules);

        // ── Resolve the date range ────────────────────────────────────────────
        // Priority: explicit date_from/date_to > single-day attendance_date
        // (back-compat) > default to "this month to date".
        if (!empty($validated['date_from']) || !empty($validated['date_to'])) {
            $selectedFromDate = $validated['date_from'] ?? $validated['date_to'];
            $selectedToDate   = $validated['date_to']   ?? $selectedFromDate;
        } elseif (!empty($validated['attendance_date'])) {
            $selectedFromDate = $validated['attendance_date'];
            $selectedToDate   = $validated['attendance_date'];
        } else {
            $selectedFromDate = now()->startOfMonth()->toDateString();
            $selectedToDate   = now()->toDateString();
        }

        $statusFilter       = $validated['status']        ?? null;
        $loginTimingFilter  = $validated['login_timing']  ?? null;
        $outsideOfficeFilter = $validated['outside_office'] ?? null;
        $employeeNameFilter = $this->canManageOrViewTeam() ? trim((string) ($validated['employee_name'] ?? '')) : '';
        $employeeIdFilter   = $this->canManageOrViewTeam() ? trim((string) ($validated['employee_id']   ?? '')) : '';
        $attendeeTypeFilter = $this->canManageOrViewTeam() ? ($validated['attendee_type'] ?? '') : '';
        $perPage            = (int) ($validated['per_page'] ?? 15);
        $page               = (int) ($validated['page']     ?? 1);

        $actingBranchIds     = $this->resolveActingBranchIds($request);
        $departmentIdFilter  = null;
        if ($request->filled('department_id') && $request->input('department_id') !== 'all') {
            $departmentIdFilter = (int) $request->input('department_id');
        }

        $accessibleAttendees = $this->accessibleAttendees($actingBranchIds, $departmentIdFilter);

        $requestedBranchId = $request->input('branch_id');
        if (filled($requestedBranchId) && $requestedBranchId !== 'all') {
            $targetBranchId = (int) $requestedBranchId;
            $accessibleAttendees = $accessibleAttendees
                ->filter(fn(array $att) => (int) ($att['branch_id'] ?? 0) === $targetBranchId)
                ->values();
        }

        if ($departmentIdFilter && $departmentIdFilter > 0) {
            $accessibleAttendees = $accessibleAttendees
                ->filter(fn(array $att) => (int) ($att['department_id'] ?? 0) === $departmentIdFilter)
                ->values();
        }

        if ($accessibleAttendees->isEmpty()) {
            $user = auth()->user();
            return response()->json([
                'status'             => true,
                'message'            => 'No accessible records.',
                'can_view_all'       => $this->canViewAllAttendance(),
                'can_manage'         => $this->canManageAttendance(),
                'has_team_members'   => $this->hasMappedTeamMembers(),
                'can_view_team'      => $this->canManageOrViewTeam(),
                'is_company_admin'   => $this->isCompanyAdminUser($user),
                'user_branch_id'     => $user?->branch_id ? (int) $user->branch_id : null,
                'branches'           => $this->attendanceBranches(),
                'departments'        => $this->attendanceDepartments(),
                'stats'              => $this->emptyStats(),
                'data'               => $this->emptyPagination($page, $perPage),
                'selected_from_date' => $selectedFromDate,
                'selected_to_date'   => $selectedToDate,
            ]);
        }

        $accessibleEmployeeIds = $accessibleAttendees->where('attendee_type', 'employee')->pluck('id')->values();
        $accessibleInternIds   = $accessibleAttendees->where('attendee_type', 'intern')->pluck('id')->values();

        // ── Fetch present / leave records across the range (scoped to accessible) ──
        $attendanceCollection = DailyAttendance::query()
            ->with(['employee.department', 'employee.portalUser.branch', 'intern.department', 'intern.portalUser.branch'])
            ->whereDate('attendance_date', '>=', $selectedFromDate)
            ->whereDate('attendance_date', '<=', $selectedToDate)
            ->where(function ($query) use ($accessibleEmployeeIds, $accessibleInternIds) {
                if ($accessibleEmployeeIds->isNotEmpty()) {
                    $query->orWhere(function ($q) use ($accessibleEmployeeIds) {
                        $q->where('attendee_type', 'employee')
                            ->whereIn('employee_id', $accessibleEmployeeIds);
                    });
                }
                if ($accessibleInternIds->isNotEmpty()) {
                    $query->orWhere(function ($q) use ($accessibleInternIds) {
                        $q->where('attendee_type', 'intern')
                            ->whereIn('intern_joining_form_id', $accessibleInternIds);
                    });
                }
            })
            ->orderBy('attendance_date')
            ->orderBy('login_time')
            ->get();

        $attendanceRecords = $attendanceCollection->map(fn(DailyAttendance $a) => $this->formatRecord($a));

        // ── Build absent records — one per missing day per accessible attendee ──
        // Mirrors AttendanceController::buildAttendanceData() on the web side:
        // for every calendar day in the selected range, an accessible attendee
        // with no present/leave row on that specific day counts as absent for
        // that day. A single-day request (the old default) is just a
        // one-day CarbonPeriod, so this is a strict superset of the previous
        // behavior — existing single-day callers see identical results.
        $presentKeys = $attendanceRecords
            ->map(fn(array $r) => implode(':', [
                $r['attendee_type'],
                $this->normalize($r['employee_id']),
                $r['attendance_date'],
            ]))
            ->filter()->unique()->values();

        $selectedDates = collect(CarbonPeriod::create($selectedFromDate, $selectedToDate))
            ->map(fn(Carbon $date) => $date->format('Y-m-d'))
            ->values();

        $absentRecords = $selectedDates
            ->flatMap(function (string $date) use ($accessibleAttendees, $presentKeys) {
                return $accessibleAttendees
                    ->reject(function (array $attendee) use ($presentKeys, $date) {
                        return $presentKeys->contains(implode(':', [
                            $attendee['attendee_type'],
                            $this->normalize($attendee['display_id']),
                            $date,
                        ]));
                    })
                    ->map(fn(array $attendee) => $this->absentRecord($attendee, $date));
            })
            ->values();

        // ── Stats (aggregated across the whole range) ─────────────────────────
        $stats = [
            'total_employees'  => $accessibleAttendees->count(),
            'present_count'    => $attendanceRecords->where('attendance_status', 'present')->count(),
            'od_count'         => $attendanceRecords->where('attendance_status', 'od')->count(),
            'absent_count'     => $absentRecords->count(),
            'leave_count'      => $attendanceRecords->where('attendance_status', 'leave')->count(),
            'late_count'       => $attendanceRecords->where('login_timing', 'late')->count(),
            'early_count'      => $attendanceRecords->where('login_timing', 'early')->count(),
            'employee_count'   => $accessibleAttendees->where('attendee_type', 'employee')->count(),
            'intern_count'     => $accessibleAttendees->where('attendee_type', 'intern')->count(),
            ...$this->outsideOfficeTodayCounts($accessibleEmployeeIds, $accessibleInternIds),
        ];

        // ── Merge & status filter ────────────────────────────────────────────
        $records = match ($statusFilter) {
            'present' => $attendanceRecords->where('attendance_status', 'present')->values(),
            'od'      => $attendanceRecords->where('attendance_status', 'od')->values(),
            'absent'  => $absentRecords->values(),
            'leave'   => $attendanceRecords->where('attendance_status', 'leave')->values(),
            default   => $attendanceRecords->concat($absentRecords),
        };

        $requestedBranchId = $request->input('branch_id');

        // ── Apply remaining filters ──────────────────────────────────────────
        $records = $records->filter(function (array $rec) use (
            $employeeNameFilter,
            $employeeIdFilter,
            $departmentIdFilter,
            $actingBranchIds,
            $requestedBranchId,
            $loginTimingFilter,
            $attendeeTypeFilter,
            $outsideOfficeFilter,
        ) {
            // Unified search: search by employee name OR employee ID (case-insensitive)
            if ($employeeNameFilter !== '') {
                $term = $this->normalize($employeeNameFilter);
                $nameMatches = str_contains($this->normalize($rec['employee_name']), $term);
                $idMatches   = str_contains($this->normalize((string) ($rec['employee_id'] ?? '')), $term);
                if (! $nameMatches && ! $idMatches) {
                    return false;
                }
            }

            if (
                $employeeIdFilter !== '' &&
                ! str_contains($this->normalize((string) ($rec['employee_id'] ?? '')), $this->normalize($employeeIdFilter))
            ) {
                return false;
            }

            if ($departmentIdFilter && (int) ($rec['department_id'] ?? 0) !== $departmentIdFilter) {
                return false;
            }

            if (filled($requestedBranchId) && $requestedBranchId !== 'all') {
                if ((int) ($rec['branch_id'] ?? 0) !== (int) $requestedBranchId) {
                    return false;
                }
            } elseif ($actingBranchIds !== null) {
                if (! empty($rec['branch_id']) && ! in_array((int) $rec['branch_id'], $actingBranchIds, true)) {
                    return false;
                }
            }

            if ($loginTimingFilter !== null && ($rec['login_timing'] ?? null) !== $loginTimingFilter) {
                return false;
            }

            if ($attendeeTypeFilter !== '' && $rec['attendee_type'] !== $attendeeTypeFilter) {
                return false;
            }

            if ($outsideOfficeFilter === 'checkin' && empty($rec['is_outside_office_checkin'])) {
                return false;
            }

            if ($outsideOfficeFilter === 'checkout' && empty($rec['is_outside_office_checkout'])) {
                return false;
            }

            if ($outsideOfficeFilter === 'any' && empty($rec['is_outside_office_checkin']) && empty($rec['is_outside_office_checkout'])) {
                return false;
            }

            return true;
        })
            // Date first (oldest → newest — a strict generalization of the
            // old single-day sort, which was effectively "by employee only"
            // since every record shared the same date), then employee within
            // a day, same as before.
            ->sortBy(fn(array $rec) => $rec['attendance_date'] . '|' . $this->normalize($rec['employee_id']) . '|' . $this->normalize($rec['employee_name']), options: SORT_NATURAL)
            ->values();

        // ── Paginate ─────────────────────────────────────────────────────────
        $total = $records->count();
        $paged = $records->forPage($page, $perPage)->values();

        $user = auth()->user();

        return response()->json([
            'status'             => true,
            'message'            => 'Attendance records fetched successfully.',
            'can_view_all'       => $this->canViewAllAttendance(),
            'can_manage'         => $this->canManageAttendance(),
            'has_team_members'   => $this->hasMappedTeamMembers(),
            'can_view_team'      => $this->canManageOrViewTeam(),
            'is_company_admin'   => $this->isCompanyAdminUser($user),
            'user_branch_id'     => $user?->branch_id ? (int) $user->branch_id : null,
            'branches'           => $this->attendanceBranches(),
            'departments'        => $this->attendanceDepartments(),
            'stats'              => $stats,
            'data'               => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'total'        => $total,
                'last_page'    => (int) ceil($total / max($perPage, 1)),
                'data'         => $paged,
            ],
            'selected_from_date' => $selectedFromDate,
            'selected_to_date'   => $selectedToDate,
        ]);
    }

    /**
     * GET /mobile/hrms/attendance/{id}
     */
    public function show(int $id): JsonResponse
    {
        $attendance = DailyAttendance::with(['employee', 'intern'])->find($id);

        if (! $attendance) {
            return response()->json(['status' => false, 'message' => 'Attendance record not found.'], 404);
        }

        // Authorization check: record must belong to an accessible attendee
        // (HR/Admin can view all, Branch Admin/Manager within branch, TL/Manager their team, Employee only self)
        if ((! $this->canViewAllAttendance() || $this->shouldFilterByBranch()) && ! $this->isAccessibleRecord($attendance)) {
            return response()->json(['status' => false, 'message' => 'Unauthorized.'], 403);
        }

        return response()->json([
            'status'           => true,
            'message'          => 'Attendance record fetched successfully.',
            'can_view_all'     => $this->canViewAllAttendance(),
            'has_team_members' => $this->hasMappedTeamMembers(),
            'can_view_team'    => $this->canManageOrViewTeam(),
            'data'             => $this->formatRecord($attendance),
        ]);
    }

    /**
     * GET /mobile/hrms/attendance/attendees
     *
     * List of employees + interns the caller may manage attendance for
     * (branch-scoped, same set as the web "Employee / Intern" picker on the
     * Manual Check-In / Checkout / Mark Leave forms). HR/Admin only — mirrors
     * AttendanceController::create()'s abort_unless() guard.
     */
    public function attendees(Request $request): JsonResponse
    {
        abort_unless($this->canManageAttendance(), 403);

        $actingBranchIds    = $this->resolveActingBranchIds($request);
        $departmentIdFilter = null;
        if ($request->filled('department_id') && $request->input('department_id') !== 'all') {
            $departmentIdFilter = (int) $request->input('department_id');
        }

        $accessibleAttendees = $this->accessibleAttendees($actingBranchIds, $departmentIdFilter);

        $requestedBranchId = $request->input('branch_id');
        if (filled($requestedBranchId) && $requestedBranchId !== 'all') {
            $targetBranchId = (int) $requestedBranchId;
            $accessibleAttendees = $accessibleAttendees
                ->filter(fn(array $att) => (int) ($att['branch_id'] ?? 0) === $targetBranchId)
                ->values();
        }

        return response()->json([
            'status'  => true,
            'message' => 'Attendees fetched successfully.',
            'data'    => $accessibleAttendees->values(),
        ]);
    }

    /**
     * GET /mobile/hrms/attendance/lookup?attendee_key=employee:12&attendance_date=2026-07-22
     *
     * Auto-fills the existing check-in/out time for an attendee+date pair —
     * mirrors AttendanceController::lookupAttendance(), used by both the
     * Manual Check-In/Leave and Manual Checkout forms so HR never has to
     * guess an existing login time. HR/Admin/Manager.
     */
    public function lookup(Request $request): JsonResponse
    {
        abort_unless($this->canManageAttendance(), 403);

        $validated = $request->validate([
            'attendee_key'    => ['required', 'string'],
            'attendance_date' => ['required', 'date'],
        ]);

        [$attendeeType, $attendeeId] = $this->parseAttendeeKey($validated['attendee_key']);

        if (! in_array($attendeeType, ['employee', 'intern'], true) || ! $attendeeId) {
            return response()->json(['status' => true, 'data' => ['found' => false]]);
        }

        $isAccessible = $this->accessibleAttendees()->contains(function ($attendee) use ($attendeeType, $attendeeId) {
            return $attendee['attendee_type'] === $attendeeType && $attendee['id'] === $attendeeId;
        });

        if (! $isAccessible) {
            return response()->json(['status' => true, 'data' => ['found' => false]]);
        }

        $attendance = DailyAttendance::query()
            ->where('attendee_type', $attendeeType)
            ->when(
                $attendeeType === 'employee',
                fn($query) => $query->where('employee_id', $attendeeId),
                fn($query) => $query->where('intern_joining_form_id', $attendeeId)
            )
            ->whereDate('attendance_date', $validated['attendance_date'])
            ->first();

        return response()->json([
            'status' => true,
            'data'   => [
                'found'          => (bool) $attendance,
                'login_time'     => $attendance?->login_time ? Carbon::createFromFormat('H:i:s', $attendance->login_time)->format('H:i') : '',
                'logout_time'    => $attendance?->logout_time ? Carbon::createFromFormat('H:i:s', $attendance->logout_time)->format('H:i') : '',
                'status'         => $attendance?->attendance_status,
                'leave_category' => $attendance?->leave_category,
                'leave_session'  => $attendance?->leave_session,
            ],
        ]);
    }

    /**
     * POST /mobile/hrms/attendance
     *
     * Manual Check-In (attendance_status=present) or Mark Leave
     * (attendance_status=leave) — mirrors AttendanceController::store()
     * exactly (validation, per-date uniqueness, leave category/session
     * rules, working-hours calc). HR/Admin only.
     */
    public function store(Request $request): JsonResponse
    {
        abort_unless($this->canManageAttendance(), 403);

        $validated = $request->validate([
            'attendee_key'      => ['required', 'string'],
            'attendance_date'   => ['required', 'date'],
            'login_time'        => ['nullable', 'date_format:H:i'],
            'logout_time'       => ['nullable', 'date_format:H:i', 'after:login_time'],
            'attendance_status' => ['required', 'in:present,leave'],
            'leave_category'    => ['nullable', 'in:paid,lop,half_day'],
            'leave_session'     => ['nullable', 'in:first_half,second_half'],
            'remarks'           => ['nullable', 'string', 'max:1000'],
        ], [
            'logout_time.after' => 'Out time must be after in time.',
        ]);

        if ($validated['attendance_status'] === 'present') {
            $request->validate([
                'login_time' => ['required', 'date_format:H:i'],
            ]);
        }

        if ($validated['attendance_status'] === 'leave') {
            $request->validate([
                'leave_category' => ['required', 'in:paid,lop,half_day'],
            ]);

            if (($validated['leave_category'] ?? null) === 'half_day') {
                $request->validate([
                    'leave_session' => ['required', 'in:first_half,second_half'],
                ]);
            }
        }

        [$attendeeType, $attendeeId] = $this->parseAttendeeKey($validated['attendee_key']);

        if (! in_array($attendeeType, ['employee', 'intern'], true) || ! $attendeeId) {
            return response()->json(['status' => false, 'message' => 'Please select a valid employee or intern.'], 422);
        }

        $isAccessible = $this->accessibleAttendees()->contains(function ($attendee) use ($attendeeType, $attendeeId) {
            return $attendee['attendee_type'] === $attendeeType && $attendee['id'] === $attendeeId;
        });

        if (! $isAccessible) {
            return response()->json(['status' => false, 'message' => 'Please select a valid employee or intern.'], 422);
        }

        if ($attendeeType === 'employee') {
            $duplicate = DailyAttendance::query()
                ->where('attendee_type', 'employee')
                ->where('employee_id', $attendeeId)
                ->whereDate('attendance_date', $validated['attendance_date'])
                ->exists();

            if ($duplicate) {
                return response()->json(['status' => false, 'message' => 'Attendance is already entered for this employee on the selected date.'], 422);
            }

            $employee = EmployeeOnboarding::query()->active()->find($attendeeId);

            if (! $employee) {
                return response()->json(['status' => false, 'message' => 'Employee not found.'], 404);
            }

            $attendanceAttributes = [
                'company_id'             => $employee->company_id,
                'branch_id'              => $employee->portalUser?->branch_id ?? $employee->branch?->id,
                'employee_id'            => $employee->id,
                'attendee_type'          => 'employee',
                'intern_joining_form_id' => null,
                'employee_name'          => $employee->name,
                'attendance_photo'       => $employee->photograph ? 'storage/' . $employee->photograph : '',
            ];
        } else {
            $duplicate = DailyAttendance::query()
                ->where('attendee_type', 'intern')
                ->where('intern_joining_form_id', $attendeeId)
                ->whereDate('attendance_date', $validated['attendance_date'])
                ->exists();

            if ($duplicate) {
                return response()->json(['status' => false, 'message' => 'Attendance is already entered for this intern on the selected date.'], 422);
            }

            $intern = InternJoiningForm::query()->active()->find($attendeeId);

            if (! $intern) {
                return response()->json(['status' => false, 'message' => 'Intern not found.'], 404);
            }

            $attendanceAttributes = [
                'company_id'             => $intern->company_id,
                'branch_id'              => $intern->portalUser?->branch_id ?? $intern->branch?->id,
                'employee_id'            => null,
                'attendee_type'          => 'intern',
                'intern_joining_form_id' => $intern->id,
                'employee_name'          => $intern->name,
                'attendance_photo'       => $intern->photograph ? 'storage/' . $intern->photograph : '',
            ];
        }

        $workingHours = $this->calculateWorkingHours(
            $validated['attendance_date'],
            $validated['login_time'] ?? '',
            $validated['logout_time'] ?? null
        );

        $attendance = DailyAttendance::create(array_merge($attendanceAttributes, [
            'login_location'   => $validated['attendance_status'] === 'leave' ? 'Manual HR Leave Entry' : 'Manual HR Entry',
            'login_latitude'   => 0,
            'login_longitude'  => 0,
            'login_time'       => filled($validated['login_time'] ?? null)
                ? Carbon::createFromFormat('H:i', $validated['login_time'])->format('H:i:s')
                : '00:00:00',
            'logout_location'  => filled($validated['logout_time'] ?? null) ? 'Manual HR Entry' : null,
            'logout_latitude'  => filled($validated['logout_time'] ?? null) ? 0 : null,
            'logout_longitude' => filled($validated['logout_time'] ?? null) ? 0 : null,
            'logout_time'      => filled($validated['logout_time'] ?? null)
                ? Carbon::createFromFormat('H:i', $validated['logout_time'])->format('H:i:s')
                : null,
            'overall_working_hours' => $validated['attendance_status'] === 'leave' ? null : $workingHours,
            'attendance_date'   => $validated['attendance_date'],
            'attendance_status' => $validated['attendance_status'],
            'leave_category'    => $validated['attendance_status'] === 'leave' ? ($validated['leave_category'] ?? null) : null,
            'leave_session'     => $validated['attendance_status'] === 'leave' ? ($validated['leave_session'] ?? null) : null,
            'remarks'           => $validated['remarks'] ?? null,
        ]));

        return response()->json([
            'status'  => true,
            'message' => $validated['attendance_status'] === 'leave'
                ? 'Leave entry created successfully.'
                : 'Attendance entry created successfully.',
            'data'    => $this->formatRecord($attendance),
        ], 201);
    }

    /**
     * POST /mobile/hrms/attendance/checkout
     *
     * Manual Checkout — backfills a logout time for an existing check-in
     * that never got one. Mirrors AttendanceController::storeCheckout().
     * HR/Admin only.
     */
    public function storeCheckout(Request $request): JsonResponse
    {
        abort_unless($this->canManageAttendance(), 403);

        $validated = $request->validate([
            'attendee_key'    => ['required', 'string'],
            'attendance_date' => ['required', 'date'],
            'logout_time'     => ['required', 'date_format:H:i'],
            'remarks'         => ['nullable', 'string', 'max:1000'],
        ]);

        [$attendeeType, $attendeeId] = $this->parseAttendeeKey($validated['attendee_key']);

        if (! in_array($attendeeType, ['employee', 'intern'], true) || ! $attendeeId) {
            return response()->json(['status' => false, 'message' => 'Please select a valid employee or intern.'], 422);
        }

        $isAccessible = $this->accessibleAttendees()->contains(function ($attendee) use ($attendeeType, $attendeeId) {
            return $attendee['attendee_type'] === $attendeeType && $attendee['id'] === $attendeeId;
        });

        if (! $isAccessible) {
            return response()->json(['status' => false, 'message' => 'Please select a valid employee or intern.'], 422);
        }

        $attendance = DailyAttendance::query()
            ->where('attendee_type', $attendeeType)
            ->when(
                $attendeeType === 'employee',
                fn($query) => $query->where('employee_id', $attendeeId),
                fn($query) => $query->where('intern_joining_form_id', $attendeeId)
            )
            ->whereDate('attendance_date', $validated['attendance_date'])
            ->first();

        if (! $attendance) {
            return response()->json(['status' => false, 'message' => 'No check-in record found for the selected attendee and date.'], 422);
        }

        $loginTime = Carbon::createFromFormat('H:i:s', $attendance->login_time)->format('H:i');

        if ($validated['logout_time'] <= $loginTime) {
            return response()->json(['status' => false, 'message' => 'Out time must be after in time.'], 422);
        }

        $workingHours = $this->calculateWorkingHours(
            $validated['attendance_date'],
            $loginTime,
            $validated['logout_time']
        );

        $attendance->update([
            'logout_location'       => 'Manual HR Checkout',
            'logout_latitude'       => 0,
            'logout_longitude'      => 0,
            'logout_time'           => Carbon::createFromFormat('H:i', $validated['logout_time'])->format('H:i:s'),
            'overall_working_hours' => $workingHours,
            // Mirrors AttendanceController::storeCheckout() — a blank remarks
            // field on resubmission keeps the existing remarks rather than
            // wiping them out.
            'remarks'               => filled($validated['remarks'] ?? null) ? $validated['remarks'] : $attendance->remarks,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Checkout time updated successfully.',
            'data'    => $this->formatRecord($attendance->fresh(['employee', 'intern'])),
        ]);
    }

    /**
     * PUT /mobile/hrms/attendance/{id}/checkin
     *
     * Edit an existing manual (or any accessible) check-in record's
     * login_time/remarks. Distinct from store() — never creates a record,
     * so store()'s duplicate-prevention validation is completely unaffected.
     * HR/Admin only.
     */
    public function updateCheckIn(Request $request, int $id): JsonResponse
    {
        abort_unless($this->canViewAllAttendance(), 403);

        $attendance = DailyAttendance::query()->with(['employee', 'intern'])->find($id);

        if (! $attendance) {
            return response()->json(['status' => false, 'message' => 'Attendance record not found.'], 404);
        }

        if (! $this->isAccessibleRecord($attendance)) {
            return response()->json(['status' => false, 'message' => 'You do not have permission to edit this record.'], 403);
        }

        if ($attendance->attendance_status !== 'present') {
            return response()->json(['status' => false, 'message' => 'Only present-day check-in records can be edited.'], 422);
        }

        $validated = $request->validate([
            'login_time' => ['required', 'date_format:H:i'],
            'remarks'    => ['nullable', 'string', 'max:1000'],
        ]);

        $logoutTime = $attendance->logout_time
            ? Carbon::createFromFormat('H:i:s', $attendance->logout_time)->format('H:i')
            : null;

        if ($logoutTime !== null && $validated['login_time'] >= $logoutTime) {
            return response()->json([
                'status'  => false,
                'message' => 'Check-in time must be before the check-out time.',
                'errors'  => ['login_time' => ['Check-in time must be before the check-out time.']],
            ], 422);
        }

        // Reused, not duplicated — same helper store()/storeCheckout() use.
        $workingHours = $this->calculateWorkingHours(
            optional($attendance->attendance_date)->format('Y-m-d'),
            $validated['login_time'],
            $logoutTime
        );

        $attendance->update([
            'login_time'            => Carbon::createFromFormat('H:i', $validated['login_time'])->format('H:i:s'),
            'overall_working_hours' => $workingHours,
            'remarks'               => filled($validated['remarks'] ?? null) ? $validated['remarks'] : $attendance->remarks,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Check-in time updated successfully.',
            'data'    => $this->formatRecord($attendance->fresh(['employee', 'intern'])),
        ]);
    }

    /**
     * PUT /mobile/hrms/attendance/{id}/checkout
     *
     * Edit an existing check-out record's logout_time/remarks directly by
     * record id — a more direct counterpart to storeCheckout() (which looks
     * the record up by attendee_key + date) for the "tap a record in the
     * list → Edit Check-Out" flow. HR/Admin only.
     */
    public function updateCheckOut(Request $request, int $id): JsonResponse
    {
        abort_unless($this->canViewAllAttendance(), 403);

        $attendance = DailyAttendance::query()->with(['employee', 'intern'])->find($id);

        if (! $attendance) {
            return response()->json(['status' => false, 'message' => 'Attendance record not found.'], 404);
        }

        if (! $this->isAccessibleRecord($attendance)) {
            return response()->json(['status' => false, 'message' => 'You do not have permission to edit this record.'], 403);
        }

        if ($attendance->attendance_status !== 'present' || ! $attendance->login_time) {
            return response()->json(['status' => false, 'message' => 'This record has no check-in to check out against.'], 422);
        }

        $validated = $request->validate([
            'logout_time' => ['required', 'date_format:H:i'],
            'remarks'     => ['nullable', 'string', 'max:1000'],
        ]);

        $loginTime = Carbon::createFromFormat('H:i:s', $attendance->login_time)->format('H:i');

        if ($validated['logout_time'] <= $loginTime) {
            return response()->json([
                'status'  => false,
                'message' => 'Check-out time must be after the check-in time.',
                'errors'  => ['logout_time' => ['Check-out time must be after the check-in time.']],
            ], 422);
        }

        // Reused, not duplicated — same helper store()/storeCheckout() use.
        $workingHours = $this->calculateWorkingHours(
            optional($attendance->attendance_date)->format('Y-m-d'),
            $loginTime,
            $validated['logout_time']
        );

        $attendance->update([
            'logout_location'       => $attendance->logout_location ?: 'Manual HR Checkout',
            'logout_latitude'       => $attendance->logout_latitude ?? 0,
            'logout_longitude'      => $attendance->logout_longitude ?? 0,
            'logout_time'           => Carbon::createFromFormat('H:i', $validated['logout_time'])->format('H:i:s'),
            'overall_working_hours' => $workingHours,
            'remarks'               => filled($validated['remarks'] ?? null) ? $validated['remarks'] : $attendance->remarks,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Checkout time updated successfully.',
            'data'    => $this->formatRecord($attendance->fresh(['employee', 'intern'])),
        ]);
    }

    // ── Private formatters ───────────────────────────────────────────────────

    private function formatRecord(DailyAttendance $a): array
    {
        $isIntern    = $a->attendee_type === 'intern';
        $employeeId  = $isIntern
            ? ($a->intern?->intern_id ?: 'INT-' . $a->intern_joining_form_id)
            : ($a->employee?->employee_id ?: $a->employee_id);
        $employeeName = $isIntern
            ? ($a->intern?->name ?: ($a->employee_name ?: 'Unknown Intern'))
            : ($a->employee?->name ?: ($a->employee_name ?: 'Unknown Employee'));
        $branch = $isIntern
            ? ($a->intern?->branch ?? $a->intern?->portalUser?->branch)
            : ($a->employee?->branch ?? $a->employee?->portalUser?->branch);
        $branchId = $branch?->id ?? ($isIntern ? $a->intern?->portalUser?->branch_id : $a->employee?->portalUser?->branch_id);
        $branchName = $branch?->name ?? ($isIntern
            ? ($a->intern?->branch_name !== '—' ? $a->intern?->branch_name : ($a->intern?->portalUser?->branch?->name ?? ''))
            : ($a->employee?->branch_name !== '—' ? $a->employee?->branch_name : ($a->employee?->portalUser?->branch?->name ?? '')));
        $departmentId = $isIntern
            ? $a->intern?->department_id
            : $a->employee?->department_id;
        $departmentName = $isIntern
            ? ($a->intern?->department?->name ?? '')
            : ($a->employee?->department?->name ?? '');

        return [
            'id'                    => $a->id,
            'employee_id'           => (string) $employeeId,
            'employee_name'         => $employeeName,
            'branch_id'             => $branchId ? (int) $branchId : null,
            'branch_name'           => $branchName,
            'department_id'         => $departmentId ? (int) $departmentId : null,
            'department_name'       => $departmentName,
            'attendee_type'         => $isIntern ? 'intern' : 'employee',
            'attendance_date'       => optional($a->attendance_date)->format('Y-m-d'),
            'attendance_status'     => strtolower((string) ($a->attendance_status ?? 'present')),
            'leave_category'        => $a->leave_category,
            'leave_session'         => $a->leave_session,
            'leave_label'           => $this->buildLeaveLabel($a->leave_category, $a->leave_session),
            'login_time'            => $a->login_time,
            'logout_time'           => $a->logout_time,
            'overall_working_hours' => $a->overall_working_hours,
            'login_location'        => $a->login_location,
            'logout_location'       => $a->logout_location,
            'login_latitude'        => $a->login_latitude,
            'login_longitude'       => $a->login_longitude,
            'logout_latitude'       => $a->logout_latitude,
            'logout_longitude'      => $a->logout_longitude,
            'remarks'               => $a->remarks,
            'attendance_photo_url'  => $a->attendance_photo ? asset($a->attendance_photo) : null,
            'logout_photo_url'      => $a->logout_photo ? asset($a->logout_photo) : null,
            'login_timing'          => $this->resolveLoginTiming($a->login_time),
            'is_outside_office_checkin'      => (bool) $a->is_outside_office_checkin,
            'outside_office_checkin_reason'  => $a->outside_office_checkin_reason,
            'is_outside_office_checkout'     => (bool) $a->is_outside_office_checkout,
            'outside_office_checkout_reason' => $a->outside_office_checkout_reason,
            'checkin_location_status'  => $a->login_time
                ? ($a->is_outside_office_checkin ? 'outside_office' : 'inside_office')
                : null,
            'checkout_location_status' => $a->logout_time
                ? ($a->is_outside_office_checkout ? 'outside_office' : 'inside_office')
                : null,
            'is_derived'            => false,
        ];
    }

    private function absentRecord(array $attendee, string $date): array
    {
        return [
            'id'                    => null,
            'employee_id'           => $attendee['display_id'],
            'employee_name'         => $attendee['name'],
            'branch_id'             => isset($attendee['branch_id']) ? (int) $attendee['branch_id'] : null,
            'branch_name'           => $attendee['branch_name'] ?? '',
            'department_id'         => isset($attendee['department_id']) ? (int) $attendee['department_id'] : null,
            'department_name'       => $attendee['department_name'] ?? '',
            'attendee_type'         => $attendee['attendee_type'],
            'attendance_date'       => $date,
            'attendance_status'     => 'absent',
            'leave_category'        => null,
            'leave_session'         => null,
            'leave_label'           => null,
            'login_time'            => null,
            'logout_time'           => null,
            'overall_working_hours' => null,
            'login_location'        => null,
            'logout_location'       => null,
            'login_latitude'        => null,
            'login_longitude'       => null,
            'logout_latitude'       => null,
            'logout_longitude'      => null,
            'remarks'               => 'No check-in record found for the selected date.',
            'attendance_photo_url'  => $attendee['photo_url'] ?? null,
            'login_timing'          => null,
            'is_outside_office_checkin'      => false,
            'outside_office_checkin_reason'  => null,
            'is_outside_office_checkout'     => false,
            'outside_office_checkout_reason' => null,
            'checkin_location_status'  => null,
            'checkout_location_status' => null,
            'is_derived'            => true,
        ];
    }

    private function emptyStats(): array
    {
        return [
            'total_employees' => 0,
            'present_count'   => 0,
            'od_count'        => 0,
            'absent_count'    => 0,
            'leave_count'     => 0,
            'late_count'      => 0,
            'early_count'     => 0,
            'employee_count'  => 0,
            'intern_count'    => 0,
            'outside_office_checkins_today'  => 0,
            'outside_office_checkouts_today' => 0,
        ];
    }

    /**
     * Today's outside-office check-in/check-out counts, scoped to the same
     * accessible/branch-visible attendee set as the rest of this endpoint.
     * Computed independently of the caller's selected date range/filters —
     * per the "Outside Office Check-Ins Today" / "Outside Office Check-Outs
     * Today" requirement, these are always "today," not "today within the
     * currently viewed range."
     */
    private function outsideOfficeTodayCounts(
        \Illuminate\Support\Collection $accessibleEmployeeIds,
        \Illuminate\Support\Collection $accessibleInternIds
    ): array {
        if ($accessibleEmployeeIds->isEmpty() && $accessibleInternIds->isEmpty()) {
            return ['outside_office_checkins_today' => 0, 'outside_office_checkouts_today' => 0];
        }

        $today = Carbon::today()->toDateString();

        $scoped = fn() => DailyAttendance::query()
            ->whereDate('attendance_date', $today)
            ->where(function ($query) use ($accessibleEmployeeIds, $accessibleInternIds) {
                if ($accessibleEmployeeIds->isNotEmpty()) {
                    $query->orWhere(function ($q) use ($accessibleEmployeeIds) {
                        $q->where('attendee_type', 'employee')
                            ->whereIn('employee_id', $accessibleEmployeeIds);
                    });
                }
                if ($accessibleInternIds->isNotEmpty()) {
                    $query->orWhere(function ($q) use ($accessibleInternIds) {
                        $q->where('attendee_type', 'intern')
                            ->whereIn('intern_joining_form_id', $accessibleInternIds);
                    });
                }
            });

        return [
            'outside_office_checkins_today'  => $scoped()->where('is_outside_office_checkin', true)->count(),
            'outside_office_checkouts_today' => $scoped()->where('is_outside_office_checkout', true)->count(),
        ];
    }

    private function emptyPagination(int $page, int $perPage): array
    {
        return [
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => 0,
            'last_page'    => 1,
            'data'         => [],
        ];
    }
}
