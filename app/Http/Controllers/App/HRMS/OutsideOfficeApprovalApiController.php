<?php

namespace App\Http\Controllers\App\HRMS;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\DailyAttendance;
use App\Models\OutsideOfficeAttendanceRequest;
use App\Models\User;
use App\Services\DataVisibilityService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * HR/Admin side of the Outside Office Attendance approval workflow. The
 * self-service submission (employee side) lives in
 * App\Http\Controllers\App\DailyAttendanceController — this controller only
 * ever reads/approves/rejects OutsideOfficeAttendanceRequest rows it did not
 * create. Approving is the ONLY place a pending request's attempted time
 * gets written into daily_attendances — see approve() below.
 */
class OutsideOfficeApprovalApiController extends Controller
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    /**
     * Resolves role hierarchy visibility flags, descendant user IDs, and assigned branches.
     * Mirrors LeaveRequestApiController::resolveVisibility() and DataVisibilityService conventions.
     */
    private function resolveVisibility(User $user): array
    {
        $isSuperAdmin    = $user->isSuperAdmin() || $user->isSystemAdmin();
        $isCompanyAdmin  = $user->isCompanyAdmin();
        $isHr            = $user->isHrOrAdmin() || $user->belongsToHrDepartment() || $user->hasHrLikeRole();
        $isBranchManager = $user->isBranchManager() || app(DataVisibilityService::class)->hasBranchManagerRole($user);
        $isBranchAdmin   = ($user->isBranchAdmin() || app(DataVisibilityService::class)->hasBranchAdminRole($user)) && ! $isBranchManager;

        /** @var DataVisibilityService $visibility */
        $visibility     = app(DataVisibilityService::class);
        $descendantIds  = $visibility->descendantUserIds($user);
        $visibleUserIds = $visibility->visibleUserIds($user) ?? [];
        $teamUserIds    = array_diff($visibleUserIds, [$user->id]);
        $hasTeamMembers = $descendantIds->isNotEmpty() || $user->managedUsers()->exists() || ! empty($teamUserIds);
        $isTlOrManager  = $hasTeamMembers || $user->hasTlLikeRole() || $isBranchManager;
        $isAdminOrHr    = $isSuperAdmin || $isCompanyAdmin || $isHr || $isBranchAdmin;

        // Assigned branches for branch manager — excludes the default main branch
        // unless explicitly assigned via branch_user or branches.manager_id
        $assignedBranchIds = [];
        if ($isBranchManager) {
            $defaultBranchIds = Branch::where('is_default', true)->pluck('id')->map(fn ($id) => (int) $id)->toArray();
            if (empty($defaultBranchIds)) {
                $defaultBranchIds = [1];
            }

            $buBranchIds = DB::table('branch_user')
                ->where('user_id', $user->id)
                ->pluck('branch_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $mgrBranchIds = DB::table('branches')
                ->where('manager_id', $user->id)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $explicitBranchIds = array_values(array_unique(array_merge($buBranchIds, $mgrBranchIds)));

            $uBranchId = $user->branch_id ? (int) $user->branch_id : null;
            $nonDefaultBranch = ($uBranchId && ! in_array($uBranchId, $defaultBranchIds, true)) ? [$uBranchId] : [];

            $assignedBranchIds = array_values(array_unique(array_merge($explicitBranchIds, $nonDefaultBranch)));
        }

        return [
            'is_super_admin'      => $isSuperAdmin,
            'is_company_admin'    => $isCompanyAdmin,
            'is_hr'               => $isHr,
            'is_branch_admin'     => $isBranchAdmin,
            'is_branch_manager'   => $isBranchManager,
            'is_tl_or_manager'    => $isTlOrManager,
            'has_team_members'    => $hasTeamMembers,
            'is_admin_or_hr'      => $isAdminOrHr,
            'descendant_ids'      => $descendantIds->all(),
            'visible_user_ids'    => $visibleUserIds,
            'assigned_branch_ids' => $assignedBranchIds,
        ];
    }

    private function canManage(?User $user, ?array $vis = null): bool
    {
        if (! $user) {
            return false;
        }

        $vis ??= $this->resolveVisibility($user);

        return $vis['is_admin_or_hr']
            || $vis['is_branch_manager']
            || $vis['is_tl_or_manager'];
    }

    private function isCompanyAdmin(?User $user, ?array $vis = null): bool
    {
        if (! $user) {
            return false;
        }

        $vis ??= $this->resolveVisibility($user);

        return $vis['is_super_admin'] || $vis['is_company_admin'] || $vis['is_hr'];
    }

    /**
     * Applies role-aware visibility and branch isolation to the OutsideOfficeAttendanceRequest query.
     */
    private function applyVisibilityScope(Builder $query, User $user, array $vis, ?string $requestedBranchId = null): void
    {
        if ($vis['is_super_admin']) {
            if ($requestedBranchId && $requestedBranchId !== 'all') {
                $this->filterByBranchIds($query, [(int) $requestedBranchId]);
            }
            return;
        }

        if ($vis['is_company_admin'] || $vis['is_hr']) {
            if ($user->company_id) {
                $query->where(function ($q) use ($user) {
                    $q->where('company_id', $user->company_id)
                      ->orWhereNull('company_id');
                });
            }

            if ($requestedBranchId && $requestedBranchId !== 'all') {
                $this->filterByBranchIds($query, [(int) $requestedBranchId]);
            }
            return;
        }

        if ($vis['is_branch_manager']) {
            $assignedBranchIds = $vis['assigned_branch_ids'];
            $visibleUserIds    = $vis['visible_user_ids'];

            // Security: branch manager cannot access an unauthorized branch via parameter
            if ($requestedBranchId && $requestedBranchId !== 'all') {
                $reqId = (int) $requestedBranchId;
                if (in_array($reqId, $assignedBranchIds, true)) {
                    $assignedBranchIds = [$reqId];
                } else {
                    $query->whereRaw('1 = 0');
                    return;
                }
            }

            if (empty($assignedBranchIds) && empty($visibleUserIds)) {
                $query->whereRaw('1 = 0');
                return;
            }

            $query->where(function (Builder $q) use ($assignedBranchIds, $visibleUserIds) {
                $q->whereHas('employee', function (Builder $eq) use ($assignedBranchIds, $visibleUserIds) {
                    $eq->where(function (Builder $eqSub) use ($assignedBranchIds, $visibleUserIds) {
                        $eqSub->whereHas('portalUser', function (Builder $pu) use ($assignedBranchIds, $visibleUserIds) {
                            $pu->where(function (Builder $puSub) use ($assignedBranchIds, $visibleUserIds) {
                                if (! empty($visibleUserIds)) {
                                    $puSub->whereIn('id', $visibleUserIds);
                                }
                                if (! empty($assignedBranchIds)) {
                                    $puSub->orWhereIn('branch_id', $assignedBranchIds)
                                          ->orWhereExists(function ($sub) use ($assignedBranchIds) {
                                              $sub->select(DB::raw(1))
                                                  ->from('branch_user')
                                                  ->whereColumn('branch_user.user_id', 'users.id')
                                                  ->whereIn('branch_user.branch_id', $assignedBranchIds);
                                          });
                                }
                            });
                        });

                        if (! empty($assignedBranchIds)) {
                            $branchCodes = Branch::whereIn('id', $assignedBranchIds)->whereNotNull('code')->pluck('code')->all();
                            foreach ($branchCodes as $code) {
                                if ($code && $code !== 'STS') {
                                    $eqSub->orWhere(function ($q2) use ($code) {
                                        $q2->whereNull('portal_user_id')->where('employee_id', 'like', $code . '%');
                                    });
                                }
                            }
                        }
                    });
                })->orWhereHas('intern', function (Builder $iq) use ($assignedBranchIds, $visibleUserIds) {
                    $iq->where(function (Builder $iqSub) use ($assignedBranchIds, $visibleUserIds) {
                        $iqSub->whereHas('portalUser', function (Builder $pu) use ($assignedBranchIds, $visibleUserIds) {
                            $pu->where(function (Builder $puSub) use ($assignedBranchIds, $visibleUserIds) {
                                if (! empty($visibleUserIds)) {
                                    $puSub->whereIn('id', $visibleUserIds);
                                }
                                if (! empty($assignedBranchIds)) {
                                    $puSub->orWhereIn('branch_id', $assignedBranchIds)
                                          ->orWhereExists(function ($sub) use ($assignedBranchIds) {
                                              $sub->select(DB::raw(1))
                                                  ->from('branch_user')
                                                  ->whereColumn('branch_user.user_id', 'users.id')
                                                  ->whereIn('branch_user.branch_id', $assignedBranchIds);
                                          });
                                }
                            });
                        });

                        if (! empty($assignedBranchIds)) {
                            $branchCodes = Branch::whereIn('id', $assignedBranchIds)->whereNotNull('code')->pluck('code')->all();
                            foreach ($branchCodes as $code) {
                                if ($code && $code !== 'STS') {
                                    $iqSub->orWhere(function ($q2) use ($code) {
                                        $q2->whereNull('portal_user_id')->where('intern_id', 'like', $code . '%');
                                    });
                                }
                            }
                        }
                    });
                });
            });

            return;
        }

        if ($vis['is_branch_admin']) {
            $branchIds = $user->getMyBranchIds();
            if ($requestedBranchId && $requestedBranchId !== 'all') {
                $reqId = (int) $requestedBranchId;
                if (in_array($reqId, $branchIds, true)) {
                    $branchIds = [$reqId];
                } else {
                    $query->whereRaw('1 = 0');
                    return;
                }
            }

            if (empty($branchIds)) {
                $query->whereRaw('1 = 0');
                return;
            }

            $this->filterByBranchIds($query, $branchIds);
            return;
        }

        if ($vis['has_team_members']) {
            $allowedUserIds = array_merge([$user->id], $vis['descendant_ids']);
            $query->where(function (Builder $q) use ($allowedUserIds) {
                $q->whereHas('employee.portalUser', fn ($pu) => $pu->whereIn('id', $allowedUserIds))
                  ->orWhereHas('intern.portalUser', fn ($pu) => $pu->whereIn('id', $allowedUserIds));
            });
            return;
        }

        // Default: strictly own requests
        $query->where(function (Builder $q) use ($user) {
            $q->whereHas('employee.portalUser', fn ($pu) => $pu->where('id', $user->id))
              ->orWhereHas('intern.portalUser', fn ($pu) => $pu->where('id', $user->id));
        });
    }

    private function filterByBranchIds(Builder $query, array $branchIds): void
    {
        $branchCodes = Branch::whereIn('id', $branchIds)->whereNotNull('code')->pluck('code')->all();

        $query->where(function (Builder $sub) use ($branchIds, $branchCodes) {
            $sub->whereHas('employee', function ($eq) use ($branchIds, $branchCodes) {
                $eq->where(function ($eqSub) use ($branchIds, $branchCodes) {
                    $eqSub->whereHas('portalUser', function ($pu) use ($branchIds) {
                        $pu->whereIn('branch_id', $branchIds)
                           ->orWhereExists(function ($bSub) use ($branchIds) {
                               $bSub->select(DB::raw(1))
                                    ->from('branch_user')
                                    ->whereColumn('branch_user.user_id', 'users.id')
                                    ->whereIn('branch_user.branch_id', $branchIds);
                           });
                    });
                    foreach ($branchCodes as $branchCode) {
                        if ($branchCode && $branchCode !== 'STS') {
                            $eqSub->orWhere(function ($q2) use ($branchCode) {
                                $q2->whereNull('portal_user_id')->where('employee_id', 'like', $branchCode . '%');
                            });
                        }
                    }
                });
            })->orWhereHas('intern', function ($iq) use ($branchIds, $branchCodes) {
                $iq->where(function ($iqSub) use ($branchIds, $branchCodes) {
                    $iqSub->whereHas('portalUser', function ($pu) use ($branchIds) {
                        $pu->whereIn('branch_id', $branchIds)
                           ->orWhereExists(function ($bSub) use ($branchIds) {
                               $bSub->select(DB::raw(1))
                                    ->from('branch_user')
                                    ->whereColumn('branch_user.user_id', 'users.id')
                                    ->whereIn('branch_user.branch_id', $branchIds);
                           });
                    });
                    foreach ($branchCodes as $branchCode) {
                        if ($branchCode && $branchCode !== 'STS') {
                            $iqSub->orWhere(function ($q2) use ($branchCode) {
                                $q2->whereNull('portal_user_id')->where('intern_id', 'like', $branchCode . '%');
                            });
                        }
                    }
                });
            });
        });
    }

    private function requestBelongsToBranches(OutsideOfficeAttendanceRequest $request, array $branchIds): bool
    {
        if (empty($branchIds)) {
            return false;
        }

        $request->loadMissing(['employee.portalUser', 'intern.portalUser']);
        $portalUser = $request->attendee_type === 'intern'
            ? $request->intern?->portalUser
            : $request->employee?->portalUser;

        if ($portalUser) {
            if (in_array((int) $portalUser->branch_id, $branchIds, true)) {
                return true;
            }

            $hasPivot = DB::table('branch_user')
                ->where('user_id', $portalUser->id)
                ->whereIn('branch_id', $branchIds)
                ->exists();
            if ($hasPivot) {
                return true;
            }
        }

        $branches = Branch::whereIn('id', $branchIds)->get();
        foreach ($branches as $branch) {
            $branchCode = $branch->code;
            if ($branchCode && $branchCode !== 'STS' && ! $portalUser) {
                $identifier = $request->attendee_type === 'intern'
                    ? $request->intern?->intern_id
                    : $request->employee?->employee_id;
                if ($identifier && str_starts_with((string) $identifier, $branchCode)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function canAccessRequest(OutsideOfficeAttendanceRequest $request, User $user, array $vis): bool
    {
        if ($user->isSystemAdmin() || $vis['is_super_admin']) {
            return true;
        }

        if ($vis['is_company_admin'] || $vis['is_hr']) {
            return ! $user->company_id || ! $request->company_id || (int) $request->company_id === (int) $user->company_id;
        }

        $request->loadMissing(['employee.portalUser', 'intern.portalUser']);
        $portalUser = $request->attendee_type === 'intern'
            ? $request->intern?->portalUser
            : $request->employee?->portalUser;

        if ($vis['is_branch_manager']) {
            $assignedBranchIds = $vis['assigned_branch_ids'];
            $visibleUserIds    = $vis['visible_user_ids'];

            if ($portalUser && in_array((int) $portalUser->id, $visibleUserIds, true)) {
                return true;
            }

            if ($this->requestBelongsToBranches($request, $assignedBranchIds)) {
                return true;
            }

            return false;
        }

        if ($vis['is_branch_admin']) {
            $branchIds = $user->getMyBranchIds();
            return $this->requestBelongsToBranches($request, $branchIds);
        }

        if ($vis['has_team_members']) {
            return $portalUser && in_array((int) $portalUser->id, $vis['descendant_ids'], true);
        }

        return $portalUser && (int) $portalUser->id === (int) $user->id;
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user() ?: auth()->user();
        if (! $user) {
            return response()->json(['status' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $vis = $this->resolveVisibility($user);
        if (! $this->canManage($user, $vis)) {
            return response()->json(['status' => false, 'message' => 'You are not authorized to view outside-office requests.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'status'        => ['nullable', 'in:pending,approved,rejected,all'],
            'request_type'  => ['nullable', 'in:checkin,checkout,any'],
            'employee_name' => ['nullable', 'string', 'max:255'],
            'from_date'     => ['nullable', 'date'],
            'to_date'       => ['nullable', 'date', 'after_or_equal:from_date'],
            'per_page'      => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $status            = $request->input('status', 'pending');
        $requestedBranchId = $request->input('branch_id');

        $query = OutsideOfficeAttendanceRequest::withoutGlobalScope('branch')->latest('created_at');
        $this->applyVisibilityScope($query, $user, $vis, $requestedBranchId);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $type = $request->input('request_type', 'any');
        if ($type !== 'any') {
            $query->where('request_type', $type);
        }

        if ($request->filled('employee_name')) {
            $query->where('employee_name', 'like', '%' . $request->input('employee_name') . '%');
        }

        if ($request->filled('from_date')) {
            $query->whereDate('attendance_date', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('attendance_date', '<=', $request->input('to_date'));
        }

        $paginated = $query->paginate($request->integer('per_page', 20));
        $paginated->getCollection()->transform(fn(OutsideOfficeAttendanceRequest $r) => $this->formatRequest($r));

        $pendingBase = OutsideOfficeAttendanceRequest::withoutGlobalScope('branch')->where('status', OutsideOfficeAttendanceRequest::STATUS_PENDING);
        $this->applyVisibilityScope($pendingBase, $user, $vis, $requestedBranchId);

        $stats = [
            'pending_checkins'  => (clone $pendingBase)->where('request_type', OutsideOfficeAttendanceRequest::TYPE_CHECKIN)->count(),
            'pending_checkouts' => (clone $pendingBase)->where('request_type', OutsideOfficeAttendanceRequest::TYPE_CHECKOUT)->count(),
        ];
        $stats['pending_total'] = $stats['pending_checkins'] + $stats['pending_checkouts'];

        return response()->json([
            'status'  => true,
            'message' => 'Outside office requests fetched successfully.',
            'data'    => $paginated,
            'stats'   => $stats,
        ]);
    }

    public function approve(Request $request, OutsideOfficeAttendanceRequest $outsideOfficeRequest): JsonResponse
    {
        $user = $request->user() ?: auth()->user();
        if (! $user) {
            return response()->json(['status' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $vis = $this->resolveVisibility($user);
        if (! $this->canManage($user, $vis)) {
            return response()->json(['status' => false, 'message' => 'You are not authorized to approve outside-office requests.'], 403);
        }

        if (! $this->canAccessRequest($outsideOfficeRequest, $user, $vis)) {
            return response()->json(['status' => false, 'message' => 'You do not have permission to approve outside-office requests for another branch.'], 403);
        }

        if (! $outsideOfficeRequest->isPending()) {
            return response()->json(['status' => false, 'message' => "This request has already been {$outsideOfficeRequest->status}."], 422);
        }

        $validator = Validator::make($request->all(), [
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        DB::transaction(function () use ($request, $outsideOfficeRequest) {
            // The employee's ORIGINAL attempted time (requested_at), never
            // the approval time — this is the whole point of staging the
            // request instead of writing straight to daily_attendances.
            $requestedAt = Carbon::parse($outsideOfficeRequest->requested_at);

            if ($outsideOfficeRequest->request_type === OutsideOfficeAttendanceRequest::TYPE_CHECKIN) {
                // Explicit, rather than relying on BelongsToCompany's
                // creating() auto-fill — that fills from the CURRENTLY
                // AUTHENTICATED user (the approving HR/Admin), which is
                // wrong here and was silently leaving company_id NULL
                // whenever a super-admin (company_id-less) approved the
                // request. Prefer the request row's own company_id (set at
                // submission time from the employee's session), but fall
                // back to the employee/intern record's own company_id
                // column — the authoritative source, independent of any
                // user session — in case the request row itself was ever
                // created without one.
                $companyId = $outsideOfficeRequest->company_id
                    ?? $outsideOfficeRequest->employee?->company_id
                    ?? $outsideOfficeRequest->intern?->company_id;

                $attendance = DailyAttendance::create([
                    'company_id'             => $companyId,
                    'employee_id'            => $outsideOfficeRequest->employee_id,
                    'intern_joining_form_id' => $outsideOfficeRequest->intern_joining_form_id,
                    'attendee_type'          => $outsideOfficeRequest->attendee_type,
                    'employee_name'          => $outsideOfficeRequest->employee_name,
                    'attendance_photo'       => $outsideOfficeRequest->photo ?: '',
                    'login_location'         => $outsideOfficeRequest->location,
                    'login_latitude'         => $outsideOfficeRequest->latitude,
                    'login_longitude'        => $outsideOfficeRequest->longitude,
                    'login_time'             => $requestedAt->format('H:i:s'),
                    'attendance_date'        => optional($outsideOfficeRequest->attendance_date)->format('Y-m-d'),
                    'attendance_status'      => 'present',
                    'is_outside_office_checkin'     => true,
                    'outside_office_checkin_reason' => $outsideOfficeRequest->reason,
                ]);

                $outsideOfficeRequest->daily_attendance_id = $attendance->id;
            } else {
                $attendance = $outsideOfficeRequest->dailyAttendance;

                if ($attendance) {
                    $loginAt = Carbon::parse(
                        $attendance->attendance_date->format('Y-m-d') . ' ' . $attendance->login_time
                    );
                    $workingSeconds = max($loginAt->diffInSeconds($requestedAt, false), 0);
                    $hours    = floor($workingSeconds / 3600);
                    $minutes  = floor(($workingSeconds % 3600) / 60);
                    $seconds  = $workingSeconds % 60;

                    $attendance->update([
                        'logout_photo'           => $outsideOfficeRequest->photo,
                        'logout_location'        => $outsideOfficeRequest->location,
                        'logout_latitude'        => $outsideOfficeRequest->latitude,
                        'logout_longitude'       => $outsideOfficeRequest->longitude,
                        'logout_time'            => $requestedAt->format('H:i:s'),
                        'overall_working_hours'  => sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds),
                        'is_outside_office_checkout'     => true,
                        'outside_office_checkout_reason' => $outsideOfficeRequest->reason,
                    ]);
                }
            }

            $outsideOfficeRequest->update([
                'status'        => OutsideOfficeAttendanceRequest::STATUS_APPROVED,
                'reviewed_by'   => auth()->id(),
                'reviewed_at'   => now(),
                'admin_remarks' => $request->input('remarks'),
                'daily_attendance_id' => $outsideOfficeRequest->daily_attendance_id,
            ]);
        });

        $outsideOfficeRequest->refresh()->load('reviewer');
        $this->notifyEmployeeOfDecision($outsideOfficeRequest, 'approved');

        return response()->json([
            'status'  => true,
            'message' => 'Outside office request approved and attendance recorded.',
            'data'    => $this->formatRequest($outsideOfficeRequest),
        ]);
    }

    public function reject(Request $request, OutsideOfficeAttendanceRequest $outsideOfficeRequest): JsonResponse
    {
        $user = $request->user() ?: auth()->user();
        if (! $user) {
            return response()->json(['status' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $vis = $this->resolveVisibility($user);
        if (! $this->canManage($user, $vis)) {
            return response()->json(['status' => false, 'message' => 'You are not authorized to reject outside-office requests.'], 403);
        }

        if (! $this->canAccessRequest($outsideOfficeRequest, $user, $vis)) {
            return response()->json(['status' => false, 'message' => 'You do not have permission to reject outside-office requests for another branch.'], 403);
        }

        if (! $outsideOfficeRequest->isPending()) {
            return response()->json(['status' => false, 'message' => "This request has already been {$outsideOfficeRequest->status}."], 422);
        }

        $validator = Validator::make($request->all(), [
            'remarks' => ['required', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        // No DailyAttendance is touched — the whole point of rejecting is
        // that this attempted check-in/out never becomes real attendance.
        $outsideOfficeRequest->update([
            'status'        => OutsideOfficeAttendanceRequest::STATUS_REJECTED,
            'reviewed_by'   => auth()->id(),
            'reviewed_at'   => now(),
            'admin_remarks' => $request->input('remarks'),
        ]);

        $outsideOfficeRequest->refresh()->load('reviewer');
        $this->notifyEmployeeOfDecision($outsideOfficeRequest, 'rejected');

        return response()->json([
            'status'  => true,
            'message' => 'Outside office request rejected.',
            'data'    => $this->formatRequest($outsideOfficeRequest),
        ]);
    }

    private function notifyEmployeeOfDecision(OutsideOfficeAttendanceRequest $r, string $status): void
    {
        $applicantUser = $r->attendee_type === 'intern'
            ? $r->intern?->portalUser
            : $r->employee?->portalUser;

        if (! $applicantUser) {
            return;
        }

        $typeLabel = $r->request_type === OutsideOfficeAttendanceRequest::TYPE_CHECKIN ? 'check-in' : 'check-out';
        $reviewerName = $r->reviewer?->name ?? 'HR/Admin';

        $message = $status === 'approved'
            ? "Your outside-office {$typeLabel} request has been approved by {$reviewerName}. Attendance has been recorded."
            : "Your outside-office {$typeLabel} request has been rejected by {$reviewerName}.";

        $this->notifications->notify($applicantUser, 'hrms', "outside_office_{$status}", [
            'title'          => 'Outside Office Request ' . ucfirst($status),
            'message'        => $message,
            'detail'         => $r->admin_remarks,
            'priority'       => 'medium',
            'request_type'   => 'outside_office',
            'request_id'     => $r->id,
            'actor_name'     => $reviewerName,
            'requester_name' => $r->employee_name,
            'status'         => $status,
        ]);
    }

    private function formatRequest(OutsideOfficeAttendanceRequest $r): array
    {
        $r->loadMissing(['employee.department', 'employee.portalUser.branch', 'intern.portalUser.branch', 'reviewer']);

        $isIntern = $r->attendee_type === 'intern';
        $portalUser = $isIntern ? $r->intern?->portalUser : $r->employee?->portalUser;

        return [
            'id'                 => $r->id,
            'attendee_type'      => $r->attendee_type,
            'employee_id'        => $r->employee_id,
            'intern_id'          => $r->intern_joining_form_id,
            'employee_name'      => $r->employee_name,
            'branch_name'        => $portalUser?->branch?->name,
            'department_name'    => $isIntern ? null : $r->employee?->department?->name,
            'request_type'       => $r->request_type,
            'daily_attendance_id' => $r->daily_attendance_id,
            'requested_at'       => optional($r->requested_at)->toIso8601String(),
            'requested_time_label' => optional($r->requested_at)->format('h:i A'),
            'attendance_date'    => optional($r->attendance_date)->format('Y-m-d'),
            'photo_url'          => $r->photo ? asset($r->photo) : null,
            'latitude'           => $r->latitude,
            'longitude'          => $r->longitude,
            'location'           => $r->location,
            'reason'             => $r->reason,
            'status'             => $r->status,
            'reviewer_name'      => $r->reviewer?->name,
            'reviewed_at'        => optional($r->reviewed_at)->toIso8601String(),
            'admin_remarks'      => $r->admin_remarks,
            'submitted_at'       => optional($r->created_at)->toIso8601String(),
            'created_at'         => optional($r->created_at)->toIso8601String(),
        ];
    }
}
