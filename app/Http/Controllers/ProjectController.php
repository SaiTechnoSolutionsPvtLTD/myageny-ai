<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\DesignSettingTarget;
use App\Models\Lead;
use App\Models\LeadProduct;
use App\Models\ProductionCountReport;
use App\Models\ProductionInitiation;
use App\Models\ProjectTimesheet;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    private const TL_ROLE_KEYS = [
        'tl',
        'team_lead',
        'team_leader',
        'teamlead',
        'manager',
        'project_manager',
        'web_team_leader',
        'mobile_app_team_leader',
        'design_team_lead',
        'team_lead_digital_marketing',
        'software_team_leader',
    ];

    public function dashboard(Request $request): View
    {
        $user = auth()->user();
        $isAdminLike = $user->hasAdminLikeRole();
        $canViewSwitcher = $user->canViewProjectsDashboardSwitcher();
        $selectedDashboard = null;

        if ($canViewSwitcher) {
            $selectedDashboard = $request->query('dashboard_type');
            if ($selectedDashboard) {
                session(['selected_dashboard_type' => $selectedDashboard]);
            } else {
                $selectedDashboard = session('selected_dashboard_type');
            }
        }

        if (! $selectedDashboard) {
            if ($user->belongsToTestingDepartment() || $user->hasTestingLikeRole()) {
                $selectedDashboard = 'testing';
            } elseif ($user->belongsToDesigningDepartment()) {
                $selectedDashboard = 'design';
            } elseif ($user->belongsToDigitalMarketingDepartment()) {
                $selectedDashboard = 'dm';
            } else {
                $selectedDashboard = 'development';
            }
        }

        if (in_array($selectedDashboard, ['testing', 'qa'], true)) {
            $testingHandovers = \App\Models\ProjectTestingDetail::with([
                'productionInitiation.leadProduct',
                'productionInitiation.product',
                'productionInitiation.lead',
                'productionInitiation.bugs',
                'movedBy',
                'testingTl'
            ])->latest()->get();

            $activeStatus = (string) $request->query('status', 'open');

            $openCount = $testingHandovers->whereIn('status', ['moved_to_testing', 'open'])->count();
            $ongoingCount = $testingHandovers->where('status', 'ongoing')->count();
            $retestingCount = $testingHandovers->where('status', 'retesting')->count();
            $completedCount = $testingHandovers->where('status', 'completed')->count();

            $filteredHandovers = match ($activeStatus) {
                'ongoing' => $testingHandovers->where('status', 'ongoing'),
                'retesting' => $testingHandovers->where('status', 'retesting'),
                'completed' => $testingHandovers->where('status', 'completed'),
                default => $testingHandovers->filter(fn($h) => in_array($h->status, ['moved_to_testing', 'open'], true)),
            };

            return view('pages.projects.testing-dashboard', [
                'selectedDashboard' => 'testing',
                'canViewSwitcher' => $canViewSwitcher,
                'activeStatus' => $activeStatus,
                'openCount' => $openCount,
                'ongoingCount' => $ongoingCount,
                'retestingCount' => $retestingCount,
                'completedCount' => $completedCount,
                'handovers' => $filteredHandovers->values(),
            ]);
        }

        if (in_array($selectedDashboard, ['design', 'designing'], true)) {
            $designDeptId = Department::whereRaw('LOWER(name) LIKE ?', ['%design%'])->value('id');

            // Get visible Designing projects
            $designProjects = $this->visibleProjectsQuery($user)
                ->where('department_id', $designDeptId)
                ->get();

            // Load timesheets to avoid N+1 query
            $designProjects->load('timesheets');

            $totalPosters = 0;
            $totalVideos = 0;
            $completedPosters = 0;
            $completedVideos = 0;
            $pendingPosters = 0;
            $pendingVideos = 0;
            $overduePosters = 0;
            $overdueVideos = 0;

            foreach ($designProjects as $project) {
                // Parse custom form data for count targets
                $posterCountCustom = 0;
                $videoCountCustom = 0;
                $endDateCustom = null;

                if (is_array($project->custom_form_data)) {
                    foreach ($project->custom_form_data as $field) {
                        $label = strtolower(trim((string) ($field['label'] ?? ($field['key'] ?? ''))));
                        $value = trim((string) ($field['value'] ?? ''));

                        if ($label === 'number of posters' || $label === 'number of poster') {
                            $posterCountCustom = (int) $value;
                        } elseif ($label === 'number of videos' || $label === 'number of video') {
                            $videoCountCustom = (int) $value;
                        } elseif ($label === 'end date') {
                            $endDateCustom = $value;
                        }
                    }
                }

                $projectPostersDelivered = (int) $project->timesheets->sum('poster_count');
                $projectVideosDelivered = (int) $project->timesheets->sum('video_count');

                $projectPostersPending = max(0, $posterCountCustom - $projectPostersDelivered);
                $projectVideosPending = max(0, $videoCountCustom - $projectVideosDelivered);

                $totalPosters += $posterCountCustom;
                $totalVideos += $videoCountCustom;
                $completedPosters += $projectPostersDelivered;
                $completedVideos += $projectVideosDelivered;
                $pendingPosters += $projectPostersPending;
                $pendingVideos += $projectVideosPending;

                // Check if project is overdue
                $end = $endDateCustom ? Carbon::parse($endDateCustom)->startOfDay() : $this->projectDeliveryDate($project);
                $isOverdue = $end && $end->isPast() && ($projectPostersPending > 0 || $projectVideosPending > 0);

                if ($isOverdue) {
                    $overduePosters += $projectPostersPending;
                    $overdueVideos += $projectVideosPending;
                }
            }

            // Daily task Goal count for User (and Team if TL)
            $userIds = [$user->id];
            if ($this->shouldLimitToAssignedProjects($user)) {
                $teamMemberIds = $designProjects
                    ->flatMap(fn ($project) => Arr::wrap($project->project_allocated_employee_user_ids))
                    ->map(fn ($id) => (int) $id)
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();
                $userIds = array_merge($userIds, $teamMemberIds);
            } elseif ($isAdminLike) {
                $teamMemberIds = User::whereHas('roles.department', function ($q) use ($designDeptId) {
                    $q->where('id', $designDeptId);
                })->pluck('id')->toArray();
                $userIds = array_merge($userIds, $teamMemberIds);
            }

            $userPosterTarget = (int) DesignSettingTarget::whereIn('user_id', $userIds)
                ->whereRaw('LOWER(product_type) = ?', ['poster'])
                ->where('is_active', true)
                ->sum('daily_target');
            $userVideoTarget = (int) DesignSettingTarget::whereIn('user_id', $userIds)
                ->whereRaw('LOWER(product_type) = ?', ['video'])
                ->where('is_active', true)
                ->sum('daily_target');

            // Stats array for card rendering
            $stats = [
                'daily_target_posters' => $userPosterTarget,
                'daily_target_videos' => $userVideoTarget,
                'overdue_posters' => $overduePosters,
                'overdue_videos' => $overdueVideos,
                'total_accounts' => $designProjects->count(),
                'total_posters' => $totalPosters,
                'total_videos' => $totalVideos,
                'completed_posters' => $completedPosters,
                'completed_videos' => $completedVideos,
                'pending_posters' => max(0, $pendingPosters - $overduePosters),
                'pending_videos' => max(0, $pendingVideos - $overdueVideos),
            ];

            // Filters
            $filterAccountId = $request->query('project_id', '');
            $filterDate = $request->query('date', Carbon::today()->toDateString());
            $filterStatus = $request->query('status', '');

            // Filter the projects for the Today Planned Tasks table
            $filteredProjects = $designProjects;

            if ($filterAccountId !== '') {
                $filteredProjects = $filteredProjects->where('id', (int) $filterAccountId);
            }

            if ($filterStatus !== '') {
                $filteredProjects = $filteredProjects->filter(function ($project) use ($filterStatus) {
                    $deliveryDate = $this->projectDeliveryDate($project);
                    $isOverdue = $deliveryDate && $deliveryDate->isPast() && $project->project_execution_status !== 'delivered';

                    return match ($filterStatus) {
                        'waiting_approval' => !$project->content_calendar_approved,
                        'inprogress' => in_array($project->project_execution_status, ['ontrack', 'hold'], true),
                        'waiting_review' => strtolower(trim((string) $project->production_approval_status)) === 'approval',
                        'completed' => $project->project_execution_status === 'delivered',
                        'overdue' => $isOverdue,
                        default => true,
                    };
                });
            }


            // TL flag and team members for Add Task feature
            $isTl = $this->shouldLimitToAssignedProjects($user);
            $teamMembers = $isTl ? $this->availableTeamMembers($user) : collect();

            $today = Carbon::today()->startOfDay();

            $todayPlannedTasks = $filteredProjects->map(function ($project) use ($filterDate, $today, $user) {
                // Scope timesheet query by user_id if non-TL employee
                $timesheetQuery = ProjectTimesheet::where('production_initiation_id', $project->id)
                    ->whereDate('timesheet_date', $filterDate);

                if ($this->shouldLimitToEmployeeProjects($user)) {
                    $timesheetQuery->where('user_id', $user->id);
                }

                $timesheet = $timesheetQuery->first();

                // Parse custom form data for date bounds and counts
                $startDateCustom = null;
                $endDateCustom = null;
                $tenureCustom = null;
                $posterCountCustom = 0;
                $videoCountCustom = 0;

                if (is_array($project->custom_form_data)) {
                    foreach ($project->custom_form_data as $field) {
                        $label = strtolower(trim((string) ($field['label'] ?? ($field['key'] ?? ''))));
                        $value = trim((string) ($field['value'] ?? ''));

                        if ($label === 'start date') {
                            $startDateCustom = $value;
                        } elseif ($label === 'end date') {
                            $endDateCustom = $value;
                        } elseif ($label === 'tenure') {
                            $tenureCustom = strtolower($value);
                        } elseif ($label === 'number of posters' || $label === 'number of poster') {
                            $posterCountCustom = (int) $value;
                        } elseif ($label === 'number of videos' || $label === 'number of video') {
                            $videoCountCustom = (int) $value;
                        }
                    }
                }

                $start = $startDateCustom ? Carbon::parse($startDateCustom)->startOfDay() : ($project->production_approval_reviewed_at ?: ($project->project_allocated_at ?: $project->created_at));
                $start = $start ? Carbon::parse($start)->startOfDay() : null;

                $end = $endDateCustom ? Carbon::parse($endDateCustom)->startOfDay() : $this->projectDeliveryDate($project);
                $end = $end ? Carbon::parse($end)->startOfDay() : null;

                $targetDate = Carbon::parse($filterDate)->startOfDay();

                // Compute pending counts from delivered timesheets
                $deliveredPosters = (int) $project->timesheets->sum('poster_count');
                $deliveredVideos  = (int) $project->timesheets->sum('video_count');
                $remainingPosters = max(0, $posterCountCustom - $deliveredPosters);
                $remainingVideos  = max(0, $videoCountCustom - $deliveredVideos);

                // Remaining days from today (or targetDate if in future) to end
                $referenceDate = $end && $end->gte($today) ? $today : $targetDate;
                $remainingDays = $end ? max(1, (int) $referenceDate->diffInDays($end) + 1) : 1;

                // Per-day rate: how many needed per day to finish on time
                $perDayPosters = (int) ceil($remainingPosters / $remainingDays);
                $perDayVideos  = (int) ceil($remainingVideos  / $remainingDays);

                // Default committed counts to 0 (default blank) unless a timesheet already exists
                $committedPosters = $timesheet ? (int) $timesheet->committed_posters : 0;
                $committedVideos  = $timesheet ? (int) $timesheet->committed_videos  : 0;

                $waitingPosters  = $timesheet ? (int) $timesheet->waiting_posters : 0;
                $waitingVideos   = $timesheet ? (int) $timesheet->waiting_videos  : 0;
                $completedPosters = $timesheet ? (int) $timesheet->poster_count   : 0;
                $completedVideos  = $timesheet ? (int) $timesheet->video_count    : 0;

                $startDate = $start ? $start->format('d M Y') : '—';
                $endDate   = $end   ? $end->format('d M Y')   : '—';
                $tenure    = ucfirst($tenureCustom ?: 'daily');

                $isOverdue = $end && $end->lt($today) && ($remainingPosters > 0 || $remainingVideos > 0);

                return [
                    'project'           => $project,
                    'account_name'      => $project->product_name . ' (' . ($project->company_name ?: ($project->lead?->company_name ?: 'No Company')) . ')',
                    'committed_posters'  => $committedPosters,
                    'committed_videos'   => $committedVideos,
                    'waiting_posters'   => $waitingPosters,
                    'waiting_videos'    => $waitingVideos,
                    'completed_posters'  => $completedPosters,
                    'completed_videos'   => $completedVideos,
                    'start_date'        => $startDate,
                    'end_date'          => $endDate,
                    'tenure'            => $tenure,
                    'day_closing_update' => $timesheet ? $timesheet->day_closing_update : '',
                    'pending_posters'   => $remainingPosters,
                    'pending_videos'    => $remainingVideos,
                    'remaining_days'    => $remainingDays,
                    'per_day_posters'   => $perDayPosters,
                    'per_day_videos'    => $perDayVideos,
                    'is_overdue'        => $isOverdue,
                ];
            })
            // Only show accounts that have pending work (committed >= 1)
            ->filter(fn ($task) => ((int) $task['committed_posters'] > 0 || (int) $task['committed_videos'] > 0))
            ->values();

            // Split into regular (on-time) and overdue lists
            $overdueTasksList  = $todayPlannedTasks->filter(fn ($t) => $t['is_overdue'])->values();
            $todayPlannedTasks = $todayPlannedTasks->filter(fn ($t) => ! $t['is_overdue'])->values();

            // Fetch target mapping of all active design department users
            $allDesigningUsers = User::query()
                ->where('is_active', true)
                ->whereHas('roles.department', function ($q) {
                    $q->whereRaw('LOWER(name) LIKE ?', ['%design%']);
                })
                ->get();

            $userTargetsMap = [];
            foreach ($allDesigningUsers as $u) {
                $pTarget = DesignSettingTarget::where('user_id', $u->id)
                    ->whereRaw('LOWER(product_type) = ?', ['poster'])
                    ->where('is_active', true)
                    ->value('daily_target') ?? 0;
                $vTarget = DesignSettingTarget::where('user_id', $u->id)
                    ->whereRaw('LOWER(product_type) = ?', ['video'])
                    ->where('is_active', true)
                    ->value('daily_target') ?? 0;
                $userTargetsMap[$u->id] = [
                    'poster' => $pTarget,
                    'video'  => $vTarget
                ];
            }

            return view('pages.projects.dashboard', [
                'isDesigningDashboard' => true,
                'isAdminLike'         => $isAdminLike,
                'selectedDashboard'   => $selectedDashboard,
                'stats'               => $stats,
                'designProjects'      => $designProjects,
                'todayPlannedTasks'   => $todayPlannedTasks,
                'overdueTasksList'    => $overdueTasksList,
                'isTl'                => $isTl,
                'teamMembers'         => $teamMembers,
                'userTargetsMap'      => $userTargetsMap,
                'filters'             => [
                    'project_id' => $filterAccountId,
                    'date'       => $filterDate,
                    'status'     => $filterStatus,
                ],
            ]);
        }

        $canQuickAddProductionUpdate = $this->canQuickAddProductionUpdate($user);
        $projects = $this->visibleProjectsQuery($user)
            ->get()
            ->map(function (ProductionInitiation $project) use ($user) {
                $project = $this->decorateProjectForUser($project, $user);
                $receivedAmount = (float) ($project->leadProduct?->payments?->sum('amount') ?? $project->leadProduct?->amount_paid ?? 0);

                $project->dashboard_date = $this->dashboardProjectDate($project, $user);
                $project->project_delivery_date = $this->projectDeliveryDate($project);
                $project->project_value = (float) ($project->leadProduct?->total_price ?? 0);
                $project->received_amount = $receivedAmount;
                $project->balance_amount = max(0, $project->project_value - $project->received_amount);
                $project->allocated_employee_count = collect(Arr::wrap($project->project_allocated_employee_user_ids))
                    ->filter()
                    ->count();

                return $project;
            })
            ->values();

        if (in_array($selectedDashboard, ['dm', 'digital_marketing'], true)) {
            $dmDeptIds = Department::where(function ($q) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%digital%'])
                  ->orWhereRaw('LOWER(name) LIKE ?', ['%marketing%'])
                  ->orWhereRaw('LOWER(name) LIKE ?', ['%dm%']);
            })->pluck('id')->toArray();

            $projects = $projects->filter(function ($project) use ($dmDeptIds) {
                if ($project->department_id && in_array((int) $project->department_id, $dmDeptIds, true)) {
                    return true;
                }
                $deptName = strtolower((string) ($project->department?->name ?? ''));
                return str_contains($deptName, 'digital') || str_contains($deptName, 'marketing') || str_contains($deptName, 'dm');
            })->values();
        } else {
            // Default or 'development' / 'production'
            $devDeptIds = Department::whereRaw('LOWER(name) LIKE ?', ['%develop%'])->pluck('id')->toArray();

            $projects = $projects->filter(function ($project) use ($devDeptIds) {
                if ($project->department_id && in_array((int) $project->department_id, $devDeptIds, true)) {
                    return true;
                }
                return $this->isDevelopmentProject($project);
            })->values();
        }

        $quickUpdateProjects = $projects
            ->sortBy('product_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $defaultDateFrom = Carbon::now()->startOfMonth()->toDateString();
        $defaultDateTo = Carbon::now()->endOfMonth()->toDateString();

        $dashboardFilters = [
            'date_from' => $request->has('date_from') ? trim((string) $request->query('date_from', '')) : $defaultDateFrom,
            'date_to' => $request->has('date_to') ? trim((string) $request->query('date_to', '')) : $defaultDateTo,
            'project_id' => trim((string) $request->query('project_id', '')),
            'team_member_id' => $this->shouldAllowDashboardUserFilter($user)
                ? trim((string) $request->query('team_member_id', ''))
                : '',
            'allocation_status' => trim((string) $request->query('allocation_status', '')),
        ];

        $filteredProjects = $this->filterDashboardProjects($projects, $dashboardFilters, $user);
        $stats = [
            'allocated_projects' => $filteredProjects->count(),
            'project_value' => round($filteredProjects->sum('project_value'), 2),
            'received_amount' => round($filteredProjects->sum('received_amount'), 2),
            'balance_amount' => round($filteredProjects->sum('balance_amount'), 2),
        ];

        // Count unallocated projects for project_coordinator and tl users
        $allocationPendingProjects = ProductionInitiation::query()
            ->whereNull('project_allocated_at')
            ->count();

        $deliverySectionTitle = 'Delivery Planned Projects';
        $deliverySectionBadge = 'Planned';

        $df = $this->parseFilterDate($dashboardFilters['date_from']);
        $dt = $this->parseFilterDate($dashboardFilters['date_to']);

        if ($df && $dt) {
            if ($df->format('Y-m') === $dt->format('Y-m')) {
                $monthName = $df->format('F Y');
                $deliverySectionTitle = "{$monthName} Delivery Planned Projects";
                $deliverySectionBadge = "Planned in {$df->format('M Y')}";
            } else {
                $deliverySectionTitle = "Delivery Planned Projects ({$df->format('d M Y')} - {$dt->format('d M Y')})";
                $deliverySectionBadge = "Planned in Range";
            }
        }

        $developmentProductWiseStats = $this->currentMonthDeliveryProjects($filteredProjects, $dashboardFilters)
            ->groupBy('product_name')
            ->map(function ($group) {
                return [
                    'delivered' => $group->where('project_execution_status', 'delivered')->count(),
                    'ongoing' => $group->whereIn('project_execution_status', ['ontrack', 'hold'])->count(),
                ];
            });

        $paymentStats = [
            'received' => round((float) $filteredProjects->sum('received_amount'), 2),
            'pending' => round((float) $filteredProjects->sum('balance_amount'), 2),
        ];

        $visibleLeadProductIds = $projects->pluck('lead_product_id')->filter()->unique()->all();
        $lastSixMonths = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthKey = now()->subMonths($i)->format('Y-m');
            $monthName = now()->subMonths($i)->format('F Y');
            $lastSixMonths[$monthKey] = [
                'month_name' => $monthName,
                'revenue' => 0.0,
            ];
        }

        if (!empty($visibleLeadProductIds)) {
            $sixMonthsAgo = now()->startOfMonth()->subMonths(5);
            $sixMonthsPayments = \DB::table('lead_product_payments')
                ->whereIn('lead_product_id', $visibleLeadProductIds)
                ->where('payment_date', '>=', $sixMonthsAgo->toDateString())
                ->selectRaw('DATE_FORMAT(payment_date, "%Y-%m") as month, SUM(amount) as total_amount')
                ->groupBy('month')
                ->get();

            foreach ($sixMonthsPayments as $payment) {
                if (isset($lastSixMonths[$payment->month])) {
                    $lastSixMonths[$payment->month]['revenue'] = round((float) $payment->total_amount, 2);
                }
            }
        }
        $sixMonthsRevenue = array_values($lastSixMonths);

        return view('pages.projects.dashboard', [
            'isAdminLike' => $isAdminLike,
            'selectedDashboard' => $selectedDashboard,
            'stats' => $stats,
            'allocationPendingCount' => $allocationPendingProjects,
            'dashboardFilters' => $dashboardFilters,
            'projectOptions' => $projects->sortBy('product_name', SORT_NATURAL | SORT_FLAG_CASE)->values(),
            'teamMemberOptions' => $this->dashboardTeamMembers($user, $projects),
            'currentMonthDeliveryProjects' => $this->currentMonthDeliveryProjects($filteredProjects, $dashboardFilters),
            'deliverySectionTitle' => $deliverySectionTitle,
            'deliverySectionBadge' => $deliverySectionBadge,
            'recentProjects' => $filteredProjects->take(10),
            'timesheetSummary' => $this->dashboardTimesheetSummary($user, $filteredProjects, $dashboardFilters),
            'isTlScopedView' => $this->shouldLimitToAssignedProjects($user),
            'isContributorScopedView' => $this->shouldLimitToEmployeeProjects($user),
            'canQuickAddProductionUpdate' => $canQuickAddProductionUpdate,
            'quickUpdateProjects' => $quickUpdateProjects,
            'developmentProductWiseStats' => $developmentProductWiseStats,
            'highlightedProductionInitiations' => [],
            'paymentStats' => $paymentStats,
            'sixMonthsRevenue' => $sixMonthsRevenue,
        ]);
    }

    public function timesheets(Request $request): View
    {
        $user = auth()->user();
        $isAdminLike = $user->hasAdminLikeRole();

        $allUsers = $isAdminLike ? \App\Models\User::where('user_status', 'active')->orderBy('name')->get(['id', 'name']) : collect();
        $departments = $isAdminLike ? \App\Models\Department::orderBy('name')->get(['id', 'name']) : collect();

        if ($isAdminLike) {
            $assignedProjects = ProductionInitiation::query()
                ->with($this->projectRelations())
                ->whereIn('production_approval_status', ['approval', 'approved'])
                ->latest('production_approval_reviewed_at')
                ->get()
                ->map(function (ProductionInitiation $project) {
                    $project->timesheet_delivery_date = $this->projectDeliveryDate($project)?->toDateString();

                    return $project;
                });
        } else {
            $assignedProjects = $this->timesheetProjectsQuery($user)
                ->get()
                ->map(function (ProductionInitiation $project) {
                    $project->timesheet_delivery_date = $this->projectDeliveryDate($project)?->toDateString();

                    return $project;
                });
        }

        $timesheetFilters = [
            'filter_date' => trim((string) $request->query('filter_date', '')),
            'filter_lead_id' => trim((string) $request->query('filter_lead_id', '')),
            'filter_project_id' => trim((string) $request->query('filter_project_id', '')),
            'filter_status' => trim((string) $request->query('filter_status', '')),
            'filter_user_id' => trim((string) $request->query('filter_user_id', '')),
            'filter_department_id' => trim((string) $request->query('filter_department_id', '')),
        ];

        $timesheets = ProjectTimesheet::query()
            ->with(['project' => fn ($query) => $query->with($this->projectRelations()), 'user'])
            ->when(!$isAdminLike, function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->when($timesheetFilters['filter_date'] !== '', function ($query) use ($timesheetFilters) {
                try {
                    $query->whereDate('timesheet_date', Carbon::parse($timesheetFilters['filter_date'])->toDateString());
                } catch (\Throwable) {
                    // Ignore invalid filter dates from query string.
                }
            })
            ->when($timesheetFilters['filter_lead_id'] !== '', function ($query) use ($timesheetFilters) {
                $query->whereHas('project', function ($q) use ($timesheetFilters) {
                    $q->where('lead_id', (int) $timesheetFilters['filter_lead_id']);
                });
            })
            ->when($timesheetFilters['filter_project_id'] !== '', function ($query) use ($timesheetFilters) {
                $query->where('production_initiation_id', (int) $timesheetFilters['filter_project_id']);
            })
            ->when($timesheetFilters['filter_status'] !== '', function ($query) use ($timesheetFilters) {
                $query->where('status', $timesheetFilters['filter_status']);
            })
            ->when($isAdminLike && $timesheetFilters['filter_user_id'] !== '', function ($query) use ($timesheetFilters) {
                $query->where('user_id', (int) $timesheetFilters['filter_user_id']);
            })
            ->when($isAdminLike && $timesheetFilters['filter_department_id'] !== '', function ($query) use ($timesheetFilters) {
                $query->whereHas('project', function ($q) use ($timesheetFilters) {
                    $q->where('department_id', (int) $timesheetFilters['filter_department_id']);
                });
            })
            ->latest('created_at')
            ->latest('timesheet_date')
            ->get();

        return view('pages.projects.timesheets', [
            'assignedProjects' => $assignedProjects,
            'timesheets' => $timesheets,
            'timesheetFilters' => $timesheetFilters,
            'today' => Carbon::today()->toDateString(),
            'isAdminLike' => $isAdminLike,
            'allUsers' => $allUsers,
            'departments' => $departments,
        ]);
    }

    public function storeTimesheet(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'production_initiation_id' => ['required', 'integer'],
            'timesheet_date' => ['required', 'date'],
            'status' => ['required', 'string', 'in:pending,completed'],
            'project_type' => ['nullable', 'string', 'in:recurring,onetime'],
            'poster_count' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'video_count' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'committed_posters' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'committed_videos' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'waiting_posters' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'waiting_videos' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'day_closing_update' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                    $projectType = $request->input('project_type');
                    $isRecurring = $projectType === 'recurring';

                    $projectId = $request->input('production_initiation_id');
                    $project = \App\Models\ProductionInitiation::with('department')->find($projectId);
                    $isDesignOrDm = false;
                    if ($project) {
                        $deptName = $project->department ? strtolower($project->department->name) : '';
                        $isDesignOrDm = str_contains($deptName, 'design') || str_contains($deptName, 'dm') || str_contains($deptName, 'digital marketing');
                    }

                    $isRequired = !$isDesignOrDm || !$isRecurring;

                    if ($isRequired && empty(trim((string) $value))) {
                        $fail('The day closing update is required.');
                        return;
                    }

                    if (filled($value)) {
                        $lines = collect(preg_split('/\R/', (string) $value))
                            ->map(fn (string $line) => trim($line))
                            ->filter();

                        if ($lines->count() < 5) {
                            $fail('Please add at least 5 task lines in the day closing update.');
                        }
                    }
                },
            ],
        ]);

        $projectQuery = $user->hasAdminLikeRole()
            ? ProductionInitiation::query()->whereIn('production_approval_status', ['approval', 'approved'])
            : $this->timesheetProjectsQuery($user);

        $project = $projectQuery
            ->whereKey($validated['production_initiation_id'])
            ->firstOrFail();
        $timesheetDate = Carbon::parse($validated['timesheet_date'])->toDateString();

        $isOnetime = ($validated['project_type'] ?? '') === 'onetime';

        $timesheet = ProjectTimesheet::where('production_initiation_id', $project->id)
            ->where('user_id', $user->id)
            ->whereDate('timesheet_date', $timesheetDate)
            ->first();

        if ($timesheet) {
            $timesheet->update([
                'status' => $validated['status'],
                'project_type' => $validated['project_type'] ?? null,
                'poster_count' => $isOnetime ? 0 : (int) ($validated['poster_count'] ?? 0),
                'video_count' => $isOnetime ? 0 : (int) ($validated['video_count'] ?? 0),
                'committed_posters' => $isOnetime ? 0 : (int) ($validated['committed_posters'] ?? 0),
                'committed_videos' => $isOnetime ? 0 : (int) ($validated['committed_videos'] ?? 0),
                'waiting_posters' => $isOnetime ? 0 : (int) ($validated['waiting_posters'] ?? 0),
                'waiting_videos' => $isOnetime ? 0 : (int) ($validated['waiting_videos'] ?? 0),
                'day_closing_update' => $validated['day_closing_update'] ?? '',
            ]);
        } else {
            ProjectTimesheet::create([
                'company_id' => $project->company_id,
                'production_initiation_id' => $project->id,
                'user_id' => $user->id,
                'timesheet_date' => $timesheetDate,
                'project_delivery_date' => $this->projectDeliveryDate($project)?->toDateString(),
                'status' => $validated['status'],
                'project_type' => $validated['project_type'] ?? null,
                'poster_count' => $isOnetime ? 0 : (int) ($validated['poster_count'] ?? 0),
                'video_count' => $isOnetime ? 0 : (int) ($validated['video_count'] ?? 0),
                'committed_posters' => $isOnetime ? 0 : (int) ($validated['committed_posters'] ?? 0),
                'committed_videos' => $isOnetime ? 0 : (int) ($validated['committed_videos'] ?? 0),
                'waiting_posters' => $isOnetime ? 0 : (int) ($validated['waiting_posters'] ?? 0),
                'waiting_videos' => $isOnetime ? 0 : (int) ($validated['waiting_videos'] ?? 0),
                'day_closing_update' => $validated['day_closing_update'] ?? '',
            ]);
        }

        \App\Models\ProjectUpdate::create([
            'production_initiation_id' => $project->id,
            'type' => 'timesheet',
            'content' => "Timesheet Date: " . Carbon::parse($timesheetDate)->format('d M Y') . "\nUpdate:\n" . $validated['day_closing_update'],
            'created_by' => $user->id,
        ]);

        return redirect()
            ->route('projects.timesheets')
            ->with('success', 'Timesheet saved successfully.');
    }

    public function getTimesheetData(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();
        $projectId = (int) $request->query('production_initiation_id');
        $date = trim((string) $request->query('timesheet_date'));

        if (!$projectId || !$date) {
            return response()->json(['success' => false, 'message' => 'Invalid parameters.']);
        }

        try {
            $timesheetDate = \Carbon\Carbon::parse($date)->toDateString();
        } catch (\Throwable) {
            return response()->json(['success' => false, 'message' => 'Invalid date.']);
        }

        $timesheet = ProjectTimesheet::where('production_initiation_id', $projectId)
            ->where('user_id', $user->id)
            ->whereDate('timesheet_date', $timesheetDate)
            ->first();

        if ($timesheet) {
            return response()->json([
                'success' => true,
                'exists' => true,
                'data' => [
                    'status' => $timesheet->status,
                    'project_type' => $timesheet->project_type ?: 'recurring',
                    'committed_posters' => (int) $timesheet->committed_posters,
                    'committed_videos' => (int) $timesheet->committed_videos,
                    'waiting_posters' => (int) $timesheet->waiting_posters,
                    'waiting_videos' => (int) $timesheet->waiting_videos,
                    'poster_count' => (int) $timesheet->poster_count,
                    'video_count' => (int) $timesheet->video_count,
                    'day_closing_update' => $timesheet->day_closing_update ?: '',
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'exists' => false,
            'data' => null
        ]);
    }

    public function updateTimesheetStatus(Request $request, ProjectTimesheet $timesheet): RedirectResponse
    {
        $user = auth()->user();
        if ($timesheet->user_id !== $user->id && !$user->hasAdminLikeRole()) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,completed'],
        ]);

        $timesheet->update([
            'status' => $validated['status'],
        ]);

        return back()->with('success', 'Timesheet status updated successfully.');
    }

    public function updatePlannedTask(Request $request): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user->belongsToDesigningDepartment(), 403);

        $validated = $request->validate([
            'production_initiation_id' => ['required', 'integer'],
            'timesheet_date' => ['required', 'date'],
            'committed_posters' => ['required', 'integer', 'min:0', 'max:100000'],
            'committed_videos' => ['required', 'integer', 'min:0', 'max:100000'],
            'waiting_posters' => ['required', 'integer', 'min:0', 'max:100000'],
            'waiting_videos' => ['required', 'integer', 'min:0', 'max:100000'],
            'poster_count' => ['required', 'integer', 'min:0', 'max:100000'], // Completed Posters
            'video_count' => ['required', 'integer', 'min:0', 'max:100000'],  // Completed Videos
            'day_closing_update' => ['nullable', 'string'],
        ]);

        if (($validated['poster_count'] + $validated['waiting_posters']) > $validated['committed_posters']) {
            return back()->withErrors([
                'poster_count' => 'The sum of Completed Posters (' . $validated['poster_count'] . ') and Waiting Posters (' . $validated['waiting_posters'] . ') cannot exceed Committed Posters (' . $validated['committed_posters'] . ').'
            ])->withInput();
        }

        if (($validated['video_count'] + $validated['waiting_videos']) > $validated['committed_videos']) {
            return back()->withErrors([
                'video_count' => 'The sum of Completed Videos (' . $validated['video_count'] . ') and Waiting Videos (' . $validated['waiting_videos'] . ') cannot exceed Committed Videos (' . $validated['committed_videos'] . ').'
            ])->withInput();
        }

        $project = $this->visibleProjectsQuery($user)
            ->whereKey($validated['production_initiation_id'])
            ->firstOrFail();

        $timesheetDate = Carbon::parse($validated['timesheet_date'])->toDateString();

        $timesheet = ProjectTimesheet::where('production_initiation_id', $project->id)
            ->where('user_id', $user->id)
            ->whereDate('timesheet_date', $timesheetDate)
            ->first();

        $dayClosingUpdate = $validated['day_closing_update'];
        if ($timesheet) {
            $dayClosingUpdate = $dayClosingUpdate ?: $timesheet->day_closing_update;
        }
        if (empty($dayClosingUpdate)) {
            $dayClosingUpdate = "";
        }

        // Enforce 5 lines rule for day closing update if not already meeting it (or pad it)
        $lines = collect(preg_split('/\R/', (string) $dayClosingUpdate))
            ->map(fn (string $line) => trim($line))
            ->filter();

        if ($lines->count() < 5) {
            $paddedLines = $lines->toArray();
            $filler = [

            ];
            while (count($paddedLines) < 5) {
                $paddedLines[] = array_shift($filler) ?: '';
            }
            $dayClosingUpdate = implode("\n", $paddedLines);
        }

        if ($timesheet) {
            $timesheet->update([
                'committed_posters' => $validated['committed_posters'],
                'committed_videos' => $validated['committed_videos'],
                'waiting_posters' => $validated['waiting_posters'],
                'waiting_videos' => $validated['waiting_videos'],
                'poster_count' => $validated['poster_count'],
                'video_count' => $validated['video_count'],
                'day_closing_update' => $dayClosingUpdate,
            ]);
        } else {
            ProjectTimesheet::create([
                'company_id' => $project->company_id,
                'production_initiation_id' => $project->id,
                'user_id' => $user->id,
                'timesheet_date' => $timesheetDate,
                'project_delivery_date' => $this->projectDeliveryDate($project)?->toDateString(),
                'committed_posters' => $validated['committed_posters'],
                'committed_videos' => $validated['committed_videos'],
                'waiting_posters' => $validated['waiting_posters'],
                'waiting_videos' => $validated['waiting_videos'],
                'poster_count' => $validated['poster_count'],
                'video_count' => $validated['video_count'],
                'day_closing_update' => $dayClosingUpdate,
            ]);
        }

        return back()->with('success', 'Planned task details updated successfully.');
    }

    public function allocateDailyTask(Request $request): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user->belongsToDesigningDepartment(), 403);

        $validated = $request->validate([
            'assigned_user_id' => ['required', 'integer', 'exists:users,id'],
            'timesheet_date'   => ['required', 'date'],
            'allocations'      => ['required', 'array'],
            'allocations.*.production_initiation_id' => ['required', 'integer', 'exists:production_initiations,id'],
            'allocations.*.committed_posters'        => ['required', 'integer', 'min:0', 'max:100000'],
            'allocations.*.committed_videos'         => ['required', 'integer', 'min:0', 'max:100000'],
            'allocations.*.selected'                 => ['nullable', 'string', 'in:1'],
        ]);

        $isTl = $this->shouldLimitToAssignedProjects($user);
        $assignedUserId = (int) $validated['assigned_user_id'];

        if (!$isTl) {
            // Normal employee can only allocate tasks to themselves
            if ($assignedUserId !== $user->id) {
                return back()->with('error', 'You can only allocate tasks to yourself.')->withInput();
            }
        }

        // Filter only selected allocations
        $allocations = collect($validated['allocations'])->filter(fn($a) => isset($a['selected']) && $a['selected'] === '1');

        if ($allocations->isEmpty()) {
            return back()->with('error', 'Please select at least one account/project to allocate tasks.')->withInput();
        }

        // Daily target commitment validation: committed counts must not be less than daily targets if > 0
        $posterTarget = (int) DesignSettingTarget::where('user_id', $assignedUserId)
            ->whereRaw('LOWER(product_type) = ?', ['poster'])
            ->where('is_active', true)
            ->value('daily_target');

        $videoTarget = (int) DesignSettingTarget::where('user_id', $assignedUserId)
            ->whereRaw('LOWER(product_type) = ?', ['video'])
            ->where('is_active', true)
            ->value('daily_target');

        foreach ($allocations as $alloc) {
            $committedPosters = (int) $alloc['committed_posters'];
            $committedVideos  = (int) $alloc['committed_videos'];

            if ($committedPosters > 0 && $committedPosters < $posterTarget) {
                return back()->withErrors([
                    'allocations' => "Committed posters cannot be less than your daily target of {$posterTarget}."
                ])->withInput();
            }

            if ($committedVideos > 0 && $committedVideos < $videoTarget) {
                return back()->withErrors([
                    'allocations' => "Committed videos cannot be less than your daily target of {$videoTarget}."
                ])->withInput();
            }
        }

        $timesheetDate = Carbon::parse($validated['timesheet_date'])->toDateString();

        foreach ($allocations as $alloc) {
            $projId = (int) $alloc['production_initiation_id'];
            $committedPosters = (int) $alloc['committed_posters'];
            $committedVideos  = (int) $alloc['committed_videos'];

            $project = $this->visibleProjectsQuery($user)
                ->whereKey($projId)
                ->firstOrFail();

            $timesheet = ProjectTimesheet::where('production_initiation_id', $project->id)
                ->where('user_id', $assignedUserId)
                ->whereDate('timesheet_date', $timesheetDate)
                ->first();

            if ($timesheet) {
                $timesheet->update([
                    'committed_posters' => $committedPosters,
                    'committed_videos'  => $committedVideos,
                ]);
            } else {
                ProjectTimesheet::create([
                    'company_id'               => $project->company_id,
                    'production_initiation_id' => $project->id,
                    'user_id'                  => $assignedUserId,
                    'timesheet_date'           => $timesheetDate,
                    'project_delivery_date'    => $this->projectDeliveryDate($project)?->toDateString(),
                    'committed_posters'        => $committedPosters,
                    'committed_videos'         => $committedVideos,
                    'waiting_posters'          => 0,
                    'waiting_videos'           => 0,
                    'poster_count'             => 0,
                    'video_count'              => 0,
                    'day_closing_update'       => '',
                ]);
            }
        }

        return back()->with('success', 'Daily task allocated successfully.');
    }

    public function index(Request $request): View
    {
        $user = auth()->user();
        $isTlScopedView = $this->shouldLimitToAssignedProjects($user);
        $isContributorScopedView = $this->shouldLimitToEmployeeProjects($user);
        if ($isContributorScopedView) {
            $projects = $this->visibleProjectsQuery($user)
                ->get()
                ->map(function (ProductionInitiation $project) {
                    $project->project_delivery_date = $this->projectDeliveryDate($project);

                    return $project;
                });

            $filters = [
                'search' => trim((string) $request->query('search', '')),
                'project_category' => trim((string) $request->query('project_category', '')),
                'delivery_from' => trim((string) $request->query('delivery_from', '')),
                'delivery_to' => trim((string) $request->query('delivery_to', '')),
            ];

            $filteredProjects = $this->filterEmployeeProjects($projects, $filters);

            return view('pages.projects.index', [
                'cards' => [],
                'selectedBucket' => 'allocated',
                'selectedCard' => [
                    'title' => 'Allocated Projects',
                    'status_label' => 'Allocated',
                    'count' => $filteredProjects->count(),
                    'items' => $filteredProjects,
                ],
                'isTlScopedView' => false,
                'isContributorScopedView' => true,
                'employeeProjects' => $filteredProjects,
                'projectFilters' => $filters,
                'projectCategories' => $projects
                    ->map(fn (ProductionInitiation $project) => $project->product?->category?->name)
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values(),
            ]);
        }

        $initiations = $this->visibleProjectsQuery($user)
            ->get()
            ->map(fn (ProductionInitiation $initiation) => $this->decorateProjectForUser($initiation, $user));

        $buckets = [
            'allocation_pending' => [
                'title' => 'Allocation Pending',
                'status_label' => 'Allocation Pending',
                'count' => 0,
                'items' => collect(),
            ],
            'allocated' => [
                'title' => 'Allocated',
                'status_label' => 'Allocated',
                'count' => 0,
                'items' => collect(),
            ],
        ];

        foreach ($initiations as $initiation) {
            $bucket = $this->resolveBucketForUser($initiation, $user);

            if (! $bucket) {
                continue;
            }

            /** @var Collection $items */
            $items = $buckets[$bucket]['items'];
            $items->push($initiation);
            $buckets[$bucket]['items'] = $items;
            $buckets[$bucket]['count']++;
        }

        $selectedBucket = (string) $request->query('bucket', 'allocation_pending');

        if (! array_key_exists($selectedBucket, $buckets)) {
            $selectedBucket = 'allocation_pending';
        }

        return view('pages.projects.index', [
            'cards' => $buckets,
            'selectedBucket' => $selectedBucket,
            'selectedCard' => $buckets[$selectedBucket],
            'isTlScopedView' => $isTlScopedView,
            'isContributorScopedView' => false,
        ]);
    }

    public function show(Request $request, ProductionInitiation $productionInitiation): View
    {
        $user = auth()->user();

        $this->ensureProjectIsVisibleToUser($productionInitiation, $user);
        $productionInitiation = $this->decorateProjectForUser($productionInitiation, $user);
        $productionInitiation->load(['projectUpdates.createdBy:id,name']);

        $projectUpdatesQuery = $productionInitiation->projectUpdates()
            ->with('createdBy:id,name');
        $projectUpdateCounts = $productionInitiation->projectUpdates()
            ->selectRaw('type, COUNT(*) as aggregate')
            ->groupBy('type')
            ->pluck('aggregate', 'type');

        $selectedUpdateType = (string) $request->query('update_type', '');
        $selectedUpdateDate = (string) $request->query('update_date', '');

        if (in_array($selectedUpdateType, ['production_update', 'meeting_update', 'weekly_update', 'timesheet'], true)) {
            $projectUpdatesQuery->where('type', $selectedUpdateType);
        } else {
            $selectedUpdateType = '';
        }

        if ($selectedUpdateDate !== '') {
            try {
                $projectUpdatesQuery->whereDate('created_at', Carbon::parse($selectedUpdateDate)->toDateString());
            } catch (\Throwable) {
                $selectedUpdateDate = '';
            }
        }

        $allocatedTlUsers = $this->allocatedTlUsers($productionInitiation);
        $teamMembers = $this->availableTeamMembers($user);
        $allocatedEmployees = $this->allocatedEmployees(
            $productionInitiation,
            $this->shouldLimitToAssignedProjects($user) ? $user : null
        );
        $tlAllocationSummaries = $this->tlAllocationSummaries($productionInitiation);
        $projectDeliveryDate = $this->projectDeliveryDate($productionInitiation);

        $testingTlUsers = User::with(['roles.department', 'branch'])
            ->where('is_active', true)
            ->get()
            ->filter(function ($u) {
                $deptNames = strtolower($u->roles->map(fn($r) => $r->department?->name)->filter()->implode(' '));
                $roleNames = strtolower($u->roles->implode('display_name', ' ') . ' ' . $u->roles->implode('name', ' '));

                return str_contains($deptNames, 'testing') || str_contains($roleNames, 'testing');
            })
            ->values();

        if ($testingTlUsers->isEmpty()) {
            $testingTlUsers = User::with(['roles.department', 'branch'])
                ->where('is_active', true)
                ->get()
                ->filter(fn($u) => $u->hasTlLikeRole() || $u->isSuperAdmin() || $u->isCompanyAdmin())
                ->values();
        }

        $testingDetails = $productionInitiation->testingDetails()
            ->with(['movedBy', 'testingTl'])
            ->latest()
            ->get();

        $bugs = $productionInitiation->bugs()
            ->with('createdBy')
            ->latest()
            ->get();

        return view('pages.projects.show', [
            'projectItem' => $productionInitiation,
            'projectDeliveryDate' => $projectDeliveryDate,
            'canAllocate' => $this->canAllocateTl($productionInitiation, $user),
            'tlUsers' => $this->availableTlUsers(),
            'allocatedTlUsers' => $allocatedTlUsers,
            'tlAllocationSummaries' => $tlAllocationSummaries,
            'isTlScopedView' => $this->shouldLimitToAssignedProjects($user),
            'isAssignedTl' => $this->isAssignedTlForProject($productionInitiation, $user),
            'canManageProjectSchedule' => $this->canManageProjectSchedule($productionInitiation, $user),
            'teamMembers' => $teamMembers,
            'canAllocateEmployees' => $this->canAllocateEmployees($productionInitiation, $user),
            'allocatedEmployees' => $allocatedEmployees,
            'projectUpdates' => $projectUpdatesQuery->latest('created_at')->get(),
            'projectUpdateCounts' => $projectUpdateCounts,
            'selectedUpdateType' => $selectedUpdateType,
            'selectedUpdateDate' => $selectedUpdateDate,
            'testingTlUsers' => $testingTlUsers,
            'testingDetails' => $testingDetails,
            'bugs' => $bugs,
        ]);
    }

    public function storeBug(Request $request, ProductionInitiation $productionInitiation): RedirectResponse
    {
        $validated = $request->validate([
            'description' => ['required', 'string', 'max:5000'],
            'priority'    => ['required', \Illuminate\Validation\Rule::in(['High', 'Medium', 'Low'])],
            'attachment'  => ['nullable', 'file', 'max:10240'],
        ]);

        // Prevent duplicate bug submissions within 15 seconds
        $existingBug = \App\Models\ProjectBug::where('production_initiation_id', $productionInitiation->id)
            ->where('created_by_user_id', auth()->id())
            ->where('description', $validated['description'])
            ->where('created_at', '>=', now()->subSeconds(15))
            ->first();

        if ($existingBug) {
            return redirect()
                ->back()
                ->with('info', 'Bug report already submitted.');
        }

        $attachmentPath = null;
        $attachmentName = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentName = $file->getClientOriginalName();

            $folder = public_path('uploads/project-bugs');
            if (! file_exists($folder)) {
                mkdir($folder, 0777, true);
            }

            $fileName = time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $file->move($folder, $fileName);
            $attachmentPath = 'uploads/project-bugs/' . $fileName;
        }

        $productionInitiation->bugs()->create([
            'company_id' => auth()->user()?->company_id,
            'lead_id' => $productionInitiation->lead_id,
            'lead_product_id' => $productionInitiation->lead_product_id,
            'description' => $validated['description'],
            'priority' => $validated['priority'],
            'attachment_path' => $attachmentPath,
            'attachment_original_name' => $attachmentName,
            'status' => 'open',
            'created_by_user_id' => auth()->id(),
        ]);

        return redirect()
            ->back()
            ->with('success', 'Bug reported successfully to project testing.');
    }

    public function testingDetails(Request $request, ProductionInitiation $productionInitiation): View
    {
        $productionInitiation->load([
            'leadProduct',
            'product',
            'lead',
            'testingDetails.movedBy',
            'testingDetails.testingTl',
            'bugs.createdBy',
        ]);

        $latestHandover = $productionInitiation->testingDetails()->latest()->first();
        $bugs = $productionInitiation->bugs()->with('createdBy')->latest()->get();

        return view('pages.projects.testing-details', [
            'projectItem' => $productionInitiation,
            'handover' => $latestHandover,
            'bugs' => $bugs,
        ]);
    }

    public function updateTestingStatus(Request $request, ProductionInitiation $productionInitiation): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', \Illuminate\Validation\Rule::in(['open', 'moved_to_testing', 'ongoing', 'retesting', 'completed'])],
        ]);

        $latestHandover = $productionInitiation->testingDetails()->latest()->first();
        if ($latestHandover) {
            $latestHandover->update([
                'status' => $validated['status'],
            ]);
        }

        return redirect()
            ->back()
            ->with('success', 'Testing status updated successfully.');
    }

    public function updateBugStatus(Request $request, \App\Models\ProjectBug $bug): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', \Illuminate\Validation\Rule::in(['open', 'fixed', 'closed'])],
        ]);

        $bug->update([
            'status' => $validated['status'],
        ]);

        return redirect()
            ->back()
            ->with('success', 'Bug status updated successfully.');
    }

    public function moveToTesting(Request $request, ProductionInitiation $productionInitiation): RedirectResponse
    {
        $validated = $request->validate([
            'credentials'  => ['nullable', 'string', 'max:5000'],
            'notes'        => ['nullable', 'string', 'max:5000'],
            'testing_tl_id' => ['nullable', 'exists:users,id'],
        ]);

        $testingDetail = $productionInitiation->testingDetails()->create([
            'company_id' => auth()->user()?->company_id,
            'lead_id' => $productionInitiation->lead_id,
            'lead_product_id' => $productionInitiation->lead_product_id,
            'credentials' => $validated['credentials'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'moved_to_testing',
            'moved_by_user_id' => auth()->id(),
            'testing_tl_id' => $validated['testing_tl_id'] ?? null,
        ]);

        // 1. TO Email: Selected Testing TL Mail
        $testingTl = !empty($validated['testing_tl_id']) ? User::find($validated['testing_tl_id']) : null;
        $toEmail = $testingTl?->email ?: 'projects@saitechnosolutions.net';

        // 2. CC Emails:
        // - Default: projects@saitechnosolutions.net
        // - Development Department TL Mails (from mappedManagers & allocatedTlUsers)
        $filterDevUsers = function ($collection) {
            return $collection->filter(function ($u) {
                if (!is_object($u)) return false;
                $roles = method_exists($u, 'resolvedRoles') ? $u->resolvedRoles(true) : ($u->roles ?? collect());
                $deptNames = strtolower($roles->map(fn($r) => $r->department?->name)->filter()->implode(' '));
                $roleNames = strtolower($roles->implode('display_name', ' ') . ' ' . $roles->implode('name', ' '));
                return str_contains($deptNames, 'development') 
                    || str_contains($roleNames, 'development') 
                    || str_contains($roleNames, 'software') 
                    || str_contains($roleNames, 'web') 
                    || str_contains($roleNames, 'app');
            })->pluck('email')->filter()->all();
        };

        $userDevTlUsers = auth()->user()?->mappedManagers()->get() ?? collect();
        $allocatedDevTlUsers = $this->allocatedTlUsers($productionInitiation);

        $userDevTlEmails = $filterDevUsers($userDevTlUsers);
        $allocatedDevTlEmails = $filterDevUsers($allocatedDevTlUsers);

        $ccEmails = array_values(array_unique(array_filter(array_merge(
            ['projects@saitechnosolutions.net'],
            $userDevTlEmails,
            $allocatedDevTlEmails
        ))));

        // Exclude TO email from CC list if present
        $ccEmails = array_values(array_diff($ccEmails, [$toEmail]));

        try {
            Mail::to($toEmail)
                ->cc($ccEmails)
                ->send(new \App\Mail\ProjectTestingNotificationMail($productionInitiation, $testingDetail));
        } catch (\Throwable $e) {
            Log::error('Failed sending Project Testing notification mail: ' . $e->getMessage());
        }

        return redirect()
            ->route('projects.show', ['productionInitiation' => $productionInitiation, 'tab' => 'testing'])
            ->with('success', 'Project details updated and moved to Testing. Notification email sent to Testing TL.');
    }

    public function allocate(Request $request, ProductionInitiation $productionInitiation): RedirectResponse
    {
        $user = auth()->user();

        abort_unless($this->canAllocateTl($productionInitiation, $user), 403);

        $validated = $request->validate([
            'tl_user_ids' => ['required', 'array', 'min:1'],
            'tl_user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $selectedTlIds = $this->availableTlUsers()
            ->pluck('id')
            ->intersect(collect($validated['tl_user_ids'])->map(fn ($id) => (int) $id))
            ->values()
            ->all();

        abort_if($selectedTlIds === [], 422, 'Please select at least one TL user.');

        $existingTlAllocations = $this->tlEmployeeAllocations($productionInitiation);
        $tlAllocations = collect($selectedTlIds)
            ->mapWithKeys(function (int $tlUserId) use ($existingTlAllocations) {
                $existingAllocation = $existingTlAllocations->get((string) $tlUserId, []);

                return [
                    (string) $tlUserId => $this->normalizeTlEmployeeAllocation([
                        'tl_user_id' => $tlUserId,
                        'status' => $existingAllocation['status'] ?? 'allocation_pending',
                        'allocated_at' => $existingAllocation['allocated_at'] ?? null,
                        'allocated_by' => $existingAllocation['allocated_by'] ?? null,
                        'employee_user_ids' => $existingAllocation['employee_user_ids'] ?? [],
                    ]),
                ];
            })
            ->all();

        $productionInitiation->update([
            'project_allocation_status' => 'allocated',
            'project_allocated_at' => Carbon::now(),
            'project_allocated_by' => $user->id,
            'project_allocated_tl_user_ids' => $selectedTlIds,
            'tl_employee_allocations' => $tlAllocations,
            ...$this->summarizeTlEmployeeAllocations($tlAllocations),
        ]);
        $this->syncProductionCountReportAllocation($productionInitiation->fresh(), $user->id);

        try {
            $productionInitiation->loadMissing(['lead.branch', 'leadProduct', 'department']);
            $allocatedTls = User::whereIn('id', $selectedTlIds)
                ->where('is_active', true)
                ->whereNotNull('email')
                ->get();
            $tlEmails = $allocatedTls->pluck('email')->filter()->unique()->values()->all();

            if (!empty($tlEmails)) {
                $allocatedBy = auth()->user();
                Mail::send('emails.tl_allocation', [
                    'initiation' => $productionInitiation,
                    'lead' => $productionInitiation->lead,
                    'leadProduct' => $productionInitiation->leadProduct,
                    'departmentName' => $productionInitiation->department?->name ?? 'Production',
                    'allocatedBy' => $allocatedBy,
                    'allocatedTls' => $allocatedTls,
                ], function ($message) use ($tlEmails, $productionInitiation) {
                    $message->to($tlEmails)
                        ->subject('New Project TL Allocation - Lead #' . $productionInitiation->lead_id . ' (' . ($productionInitiation->product_name ?: 'Product') . ')');
                });
            }
        } catch (\Throwable $e) {
            Log::error('Failed sending TL Allocation email: ' . $e->getMessage());
        }

        return redirect()
            ->route('projects.show', $productionInitiation)
            ->with('success', 'Project moved to TL allocation successfully. Notification email sent to allocated Team Lead(s).');
    }

    public function allocateEmployees(Request $request, ProductionInitiation $productionInitiation): RedirectResponse
    {
        $user = auth()->user();

        abort_unless($this->canAllocateEmployees($productionInitiation, $user), 403);

        $validated = $request->validate([
            'employee_user_ids' => ['required', 'array', 'min:1'],
            'employee_user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $selectedEmployeeIds = $this->availableTeamMembers($user)
            ->pluck('id')
            ->intersect(collect($validated['employee_user_ids'])->map(fn ($id) => (int) $id))
            ->values()
            ->all();

        abort_if($selectedEmployeeIds === [], 422, 'Please select at least one employee.');

        $tlAllocations = $this->tlEmployeeAllocations($productionInitiation);
        $tlAllocations->put((string) $user->id, $this->normalizeTlEmployeeAllocation([
            'tl_user_id' => $user->id,
            'status' => 'allocated',
            'allocated_at' => Carbon::now()->toDateTimeString(),
            'allocated_by' => $user->id,
            'employee_user_ids' => $selectedEmployeeIds,
        ]));
        $allocationSummary = $this->summarizeTlEmployeeAllocations($tlAllocations->all());

        $productionInitiation->update([
            'tl_employee_allocations' => $tlAllocations->all(),
            ...$allocationSummary,
        ]);
        $this->syncProductionCountReportAllocation($productionInitiation->fresh(), $user->id);

        try {
            $productionInitiation->loadMissing(['lead.branch', 'leadProduct', 'department']);
            $allocatedEmployees = User::whereIn('id', $selectedEmployeeIds)
                ->where('is_active', true)
                ->whereNotNull('email')
                ->get();
            $empEmails = $allocatedEmployees->pluck('email')->filter()->unique()->values()->all();

            if (!empty($empEmails)) {
                $allocatedBy = auth()->user();
                Mail::send('emails.team_allocation', [
                    'initiation' => $productionInitiation,
                    'lead' => $productionInitiation->lead,
                    'leadProduct' => $productionInitiation->leadProduct,
                    'departmentName' => $productionInitiation->department?->name ?? 'Production',
                    'allocatedBy' => $allocatedBy,
                    'allocatedEmployees' => $allocatedEmployees,
                ], function ($message) use ($empEmails, $productionInitiation) {
                    $message->to($empEmails)
                        ->subject('New Team Project Assignment - Lead #' . $productionInitiation->lead_id . ' (' . ($productionInitiation->product_name ?: 'Product') . ')');
                });
            }
        } catch (\Throwable $e) {
            Log::error('Failed sending Team Allocation email: ' . $e->getMessage());
        }

        return redirect()
            ->route('projects.show', $productionInitiation)
            ->with('success', 'Employees allocated to this project successfully. Notification email sent to assigned team members.');
    }

    public function updateSchedule(Request $request, ProductionInitiation $productionInitiation): RedirectResponse
    {
        $user = auth()->user();

        $this->ensureProjectIsVisibleToUser($productionInitiation, $user);
        abort_unless($this->canManageProjectSchedule($productionInitiation, $user), 403);

        $validated = $request->validate([
            'project_delivery_date' => ['nullable', 'date'],
            'project_execution_status' => ['required', 'in:ontrack,hold,delivered'],
        ]);

        $oldDeliveryDate = $productionInitiation->project_delivery_date;
        $oldStatus = $productionInitiation->project_execution_status;

        $newDeliveryDate = $validated['project_delivery_date'] ?: null;
        $newStatus = $validated['project_execution_status'];

        $changes = [];

        $oldDateStr = $oldDeliveryDate ? \Illuminate\Support\Carbon::parse($oldDeliveryDate)->toDateString() : 'None';
        $newDateStr = $newDeliveryDate ? \Illuminate\Support\Carbon::parse($newDeliveryDate)->toDateString() : 'None';

        if ($oldDateStr !== $newDateStr) {
            $changes[] = "Delivery Date updated from <strong>{$oldDateStr}</strong> to <strong>{$newDateStr}</strong>";
        }

        if ($oldStatus !== $newStatus) {
            $oldStatusLabel = ucfirst($oldStatus ?: 'None');
            $newStatusLabel = ucfirst($newStatus);
            $changes[] = "Project Status updated from <strong>{$oldStatusLabel}</strong> to <strong>{$newStatusLabel}</strong>";
        }

        $productionInitiation->update([
            'project_delivery_date' => $newDeliveryDate,
            'project_execution_status' => $newStatus,
        ]);

        if (count($changes) > 0) {
            $content = implode('<br>', $changes);
            
            $productionInitiation->projectUpdates()->create([
                'type' => 'schedule_history',
                'content' => $content,
                'created_by' => $user->id,
            ]);
        }

        return redirect()
            ->route('projects.show', ['productionInitiation' => $productionInitiation, 'tab' => 'timeline'])
            ->with('success', 'Project delivery date and status updated successfully.');
    }

    public function updateContentCalendarSheet(Request $request, ProductionInitiation $productionInitiation): RedirectResponse
    {
        $user = auth()->user();

        $this->ensureProjectIsVisibleToUser($productionInitiation, $user);

        if ($productionInitiation->content_calendar_approved) {
            return redirect()
                ->route('projects.show', ['productionInitiation' => $productionInitiation, 'tab' => 'content_calendar'])
                ->with('error', 'Content Calendar is already approved. You cannot change the Google Sheet URL.');
        }

        $validated = $request->validate([
            'content_calendar_sheet_url' => ['nullable', 'url', 'max:2000'],
        ]);

        $productionInitiation->update([
            'content_calendar_sheet_url' => $validated['content_calendar_sheet_url'] ?: null,
        ]);

        return redirect()
            ->route('projects.show', ['productionInitiation' => $productionInitiation, 'tab' => 'content_calendar'])
            ->with('success', 'Content Calendar Google Sheet URL updated successfully.');
    }

    public function approveContentCalendar(Request $request, ProductionInitiation $productionInitiation): RedirectResponse
    {
        $user = auth()->user();

        $this->ensureProjectIsVisibleToUser($productionInitiation, $user);

        if (!$user->belongsToDesigningDepartment()) {
            return redirect()
                ->route('projects.show', ['productionInitiation' => $productionInitiation, 'tab' => 'content_calendar'])
                ->with('error', 'Only members of the Designing department can approve the Content Calendar.');
        }

        if ($productionInitiation->content_calendar_approved) {
            return redirect()
                ->route('projects.show', ['productionInitiation' => $productionInitiation, 'tab' => 'content_calendar'])
                ->with('error', 'Content Calendar is already approved.');
        }

        $validated = $request->validate([
            'remarks' => ['required', 'string', 'min:3', 'max:5000'],
        ]);

        $productionInitiation->update([
            'content_calendar_approved' => true,
            'content_calendar_remarks' => $validated['remarks'],
        ]);

        return redirect()
            ->route('projects.show', ['productionInitiation' => $productionInitiation, 'tab' => 'content_calendar'])
            ->with('success', 'Content Calendar approved successfully with remarks.');
    }

    /**
     * Server-side proxy: fetch the Google Sheet CSV for the Content Calendar.
     * Bypasses CORS entirely — browser calls our own Laravel endpoint.
     */
    public function fetchContentCalendarData(Request $request, ProductionInitiation $productionInitiation): JsonResponse
    {
        $user = auth()->user();
        $this->ensureProjectIsVisibleToUser($productionInitiation, $user);

        $rawUrl = (string) ($productionInitiation->content_calendar_sheet_url ?? '');

        if (empty($rawUrl)) {
            return response()->json(['error' => 'No sheet URL configured for this project.'], 422);
        }

        $csvUrl = $this->buildGoogleSheetCsvUrl($rawUrl);

        if (! $csvUrl) {
            return response()->json(['error' => 'Invalid Google Sheets URL. Please reconfigure.'], 422);
        }

        try {
            $response = Http::timeout(20)
                ->withHeaders(['Accept' => 'text/csv,text/plain,*/*'])
                ->get($csvUrl);

            if (! $response->successful()) {
                return response()->json([
                    'error' => 'Google Sheet fetch failed (HTTP ' . $response->status() . '). Make sure the sheet is publicly accessible.',
                ], 502);
            }

            return response()->json(['csv' => $response->body()]);

        } catch (\Throwable $e) {
            return response()->json(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Convert any Google Sheets URL to its CSV export equivalent.
     *
     * Handles:
     *   1. Already pub?output=csv or export?format=csv → use as-is
     *   2. /d/e/PUB_ID/pubhtml (Published to web)     → /d/e/PUB_ID/pub?output=csv
     *   3. /d/SHEET_ID/edit (Regular share link)      → /d/SHEET_ID/export?format=csv
     */
    private function buildGoogleSheetCsvUrl(string $raw): ?string
    {
        $raw = trim((string) preg_replace('/#.*$/', '', $raw));

        if (str_contains($raw, 'output=csv') || str_contains($raw, 'format=csv')) {
            return $raw;
        }

        if (preg_match('#^(https://docs\.google\.com/spreadsheets/d/e/[A-Za-z0-9_-]+)/pub(html)?(\?.*)?$#', $raw, $m)) {
            return $m[1] . '/pub?output=csv';
        }

        if (preg_match('#/spreadsheets/d/([A-Za-z0-9_-]+)#', $raw, $m)) {
            $sheetId = $m[1];
            $gid     = '0';
            if (preg_match('/[?&]gid=([0-9]+)/', $raw, $gm)) {
                $gid = $gm[1];
            }
            return 'https://docs.google.com/spreadsheets/d/' . $sheetId . '/export?format=csv&gid=' . $gid;
        }

        return null;
    }

    public function storeUpdate(Request $request, ProductionInitiation $productionInitiation): RedirectResponse
    {
        $user = auth()->user();

        $this->ensureProjectIsVisibleToUser($productionInitiation, $user);


        $validated = $request->validate([
            'type' => ['required', 'in:production_update,meeting_update,weekly_update'],
            'content' => ['required', 'string'],
        ]);

        $productionInitiation->projectUpdates()->create([
            'type' => $validated['type'],
            'content' => $validated['content'],
            'created_by' => $user->id,
        ]);

        return redirect()
            ->route('projects.show', ['productionInitiation' => $productionInitiation, 'tab' => 'updates'])
            ->with('success', 'Project update added successfully.');
    }

    public function storeQuickUpdate(Request $request): RedirectResponse
    {
        $user = auth()->user();

        abort_unless($this->canQuickAddProductionUpdate($user), 403);

        $validated = $request->validate([
            'production_initiation_id' => ['required', 'integer'],
            'type' => ['required', 'in:production_update,meeting_update,weekly_update'],
            'content' => ['required', 'string'],
        ]);

        $productionInitiation = $this->visibleProjectsQuery($user)
            ->whereKey($validated['production_initiation_id'])
            ->get()
            ->first(function (ProductionInitiation $project) {
                return $this->isDevelopmentProject($project);
            });

        abort_unless($productionInitiation, 404);

        $productionInitiation->projectUpdates()->create([
            'type' => $validated['type'],
            'content' => $validated['content'],
            'created_by' => $user->id,
        ]);

        return redirect()
            ->route('projects.index')
            ->with('success', 'Project update added successfully.');
    }

    private function visibleProjectsQuery(User $user): Builder
    {
        $query = ProductionInitiation::query()
            ->with($this->projectRelations())
            ->whereIn('production_approval_status', ['approval', 'approved']);

        if ($this->shouldLimitToAssignedProjects($user)) {
            $query
                ->where('project_allocation_status', 'allocated')
                ->whereJsonContains('project_allocated_tl_user_ids', $user->id);
        } elseif ($this->shouldLimitToEmployeeProjects($user)) {
            $query
                ->where('project_allocation_status', 'allocated')
                ->whereJsonContains('project_allocated_employee_user_ids', $user->id);
        } else {
            $query->whereIn('project_allocation_status', ['allocation_pending', 'allocated']);
        }

        return $query->latest('production_approval_reviewed_at');
    }

    private function timesheetProjectsQuery(User $user): Builder
    {
        return ProductionInitiation::query()
            ->with($this->projectRelations())
            ->whereIn('production_approval_status', ['approval', 'approved'])
            ->where('project_allocation_status', 'allocated')
            ->whereJsonContains('project_allocated_employee_user_ids', $user->id)
            ->latest('production_approval_reviewed_at');
    }

    private function projectDeliveryDate(ProductionInitiation $project): ?Carbon
    {
        if ($project->project_delivery_date) {
            return $project->project_delivery_date instanceof Carbon
                ? $project->project_delivery_date
                : Carbon::parse($project->project_delivery_date);
        }

        $baseDate = $project->production_approval_reviewed_at
            ?: $project->project_allocated_at
            ?: $project->created_at;

        if (! $baseDate) {
            return null;
        }

        return Carbon::parse($baseDate)->addDays((int) $project->total_working_days);
    }

    private function dashboardProjectDate(ProductionInitiation $project, User $user): ?Carbon
    {
        if ($this->shouldLimitToEmployeeProjects($user)) {
            return $project->employee_allocated_at
                ? Carbon::parse($project->employee_allocated_at)
                : null;
        }

        if ($this->shouldLimitToAssignedProjects($user)) {
            return $project->current_team_allocated_at
                ? Carbon::parse($project->current_team_allocated_at)
                : null;
        }

        return $project->project_allocated_at
            ? Carbon::parse($project->project_allocated_at)
            : ($project->production_approval_reviewed_at
                ? Carbon::parse($project->production_approval_reviewed_at)
                : null);
    }

    private function filterDashboardProjects(Collection $projects, array $filters, User $user): Collection
    {
        $dateFrom = $this->parseFilterDate($filters['date_from'] ?? '');
        $dateTo = $this->parseFilterDate($filters['date_to'] ?? '')?->endOfDay();
        $selectedProjectId = (int) ($filters['project_id'] ?? 0);
        $selectedTeamMemberId = (int) ($filters['team_member_id'] ?? 0);
        $selectedAllocationStatus = trim((string) ($filters['allocation_status'] ?? ''));

        return $projects
            ->filter(function (ProductionInitiation $project) use ($dateFrom, $dateTo, $selectedProjectId, $selectedTeamMemberId, $selectedAllocationStatus, $user) {
                if ($selectedProjectId > 0 && (int) $project->id !== $selectedProjectId) {
                    return false;
                }

                if ($dateFrom || $dateTo) {
                    $deliveryDate = $project->project_delivery_date;

                    if (! $deliveryDate) {
                        return false;
                    }

                    if ($dateFrom && $deliveryDate->lt($dateFrom)) {
                        return false;
                    }

                    if ($dateTo && $deliveryDate->gt($dateTo)) {
                        return false;
                    }
                }

                if ($selectedTeamMemberId > 0 && $this->shouldAllowDashboardUserFilter($user)) {
                    $employeeIds = collect(Arr::wrap($project->project_allocated_employee_user_ids))
                        ->map(fn ($id) => (int) $id);

                    if (! $employeeIds->contains($selectedTeamMemberId)) {
                        return false;
                    }
                }

                if ($selectedAllocationStatus !== '') {
                    if ($project->project_allocation_status !== $selectedAllocationStatus) {
                        return false;
                    }
                }

                return true;
            })
            ->values();
    }

    private function filterEmployeeProjects(Collection $projects, array $filters): Collection
    {
        $deliveryFrom = $this->parseFilterDate($filters['delivery_from'] ?? '');
        $deliveryTo = $this->parseFilterDate($filters['delivery_to'] ?? '');

        return $projects
            ->filter(function (ProductionInitiation $project) use ($filters, $deliveryFrom, $deliveryTo) {
                $search = Str::lower($filters['search'] ?? '');

                if ($search !== '') {
                    $haystack = Str::lower(implode(' ', [
                        $project->product_name,
                        $project->client_name,
                        $project->company_name,
                        $project->lead?->contact_name,
                        $project->lead?->company_name,
                        $project->lead?->mobile_number,
                    ]));

                    if (! Str::contains($haystack, $search)) {
                        return false;
                    }
                }

                if (($filters['project_category'] ?? '') !== '' && $project->product?->category?->name !== $filters['project_category']) {
                    return false;
                }

                $deliveryDate = $project->project_delivery_date;

                if ($deliveryFrom) {
                    if (! $deliveryDate || $deliveryDate->lt($deliveryFrom)) {
                        return false;
                    }
                }

                if ($deliveryTo) {
                    if (! $deliveryDate || $deliveryDate->gt($deliveryTo)) {
                        return false;
                    }
                }

                return true;
            })
            ->values();
    }

    private function parseFilterDate(string $value): ?Carbon
    {
        if (trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function dashboardTeamMembers(User $user, Collection $projects): Collection
    {
        if (! $this->shouldAllowDashboardUserFilter($user)) {
            return collect();
        }

        $employeeIds = $projects
            ->flatMap(fn (ProductionInitiation $project) => Arr::wrap($project->project_allocated_employee_user_ids))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $this->usersFromIds($employeeIds);
    }

    private function shouldAllowDashboardUserFilter(User $user): bool
    {
        return $this->shouldLimitToAssignedProjects($user);
    }

    private function dashboardTimesheetSummary(User $user, Collection $projects, array $filters): array
    {
        $projectIds = $projects->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        if ($projectIds->isEmpty()) {
            return [];
        }

        $query = ProjectTimesheet::query()
            ->with([
                'user:id,name',
                'project' => fn ($builder) => $builder->with($this->projectRelations()),
            ])
            ->whereIn('production_initiation_id', $projectIds->all());

        if ($this->shouldLimitToEmployeeProjects($user)) {
            $query->where('user_id', $user->id);
        }

        $dateFrom = $this->parseFilterDate($filters['date_from'] ?? '');
        $dateTo = $this->parseFilterDate($filters['date_to'] ?? '')?->endOfDay();

        if ($dateFrom) {
            $query->whereDate('timesheet_date', '>=', $dateFrom->toDateString());
        }

        if ($dateTo) {
            $query->whereDate('timesheet_date', '<=', $dateTo->toDateString());
        }

        $selectedTeamMemberId = (int) ($filters['team_member_id'] ?? 0);

        if ($selectedTeamMemberId > 0 && $this->shouldAllowDashboardUserFilter($user)) {
            $query->where('user_id', $selectedTeamMemberId);
        }

        $entries = $query
            ->latest('timesheet_date')
            ->latest('created_at')
            ->get();

        return $entries->groupBy('production_initiation_id')->map(function ($projectEntries) {
            $first = $projectEntries->first();
            $project = $first->project;
            return [
                'project_name' => $project?->product_name ?: 'Unknown Project',
                'company_name' => $project?->company_name ?: ($project?->lead?->company_name ?: 'No Company'),
                'entries_count' => $projectEntries->count(),
                'total_posters' => $projectEntries->sum('poster_count'),
                'total_videos' => $projectEntries->sum('video_count'),
            ];
        })->values()->all();
    }


    private function currentMonthDeliveryProjects(Collection $projects, array $filters = []): Collection
    {
        $dateFrom = $this->parseFilterDate($filters['date_from'] ?? '');
        $dateTo = $this->parseFilterDate($filters['date_to'] ?? '');

        $monthStart = $dateFrom ?: Carbon::today()->startOfMonth();
        $monthEnd = $dateTo ?: Carbon::today()->endOfMonth();

        return $projects
            ->filter(function (ProductionInitiation $project) use ($monthStart, $monthEnd) {
                return $project->project_delivery_date
                    && $project->project_delivery_date->between($monthStart, $monthEnd);
            })
            ->map(function (ProductionInitiation $project) {
                $employeeNames = $this->usersFromIds(Arr::wrap($project->project_allocated_employee_user_ids))
                    ->pluck('name')
                    ->filter()
                    ->values();
                $tlNames = $this->usersFromIds(Arr::wrap($project->project_allocated_tl_user_ids))
                    ->pluck('name')
                    ->filter()
                    ->values();

                $project->allocated_person_label = $employeeNames->isNotEmpty()
                    ? $employeeNames->implode(', ')
                    : ($tlNames->isNotEmpty() ? $tlNames->implode(', ') : 'Not allocated');

                return $project;
            })
            ->sortBy('project_delivery_date')
            ->values();
    }

    private function projectRelations(): array
    {
        return [
            'lead:id,company_name,contact_name,email,mobile_number,assigned_to,created_by',
            'lead.assignedTo:id,name,email,designation',
            'lead.createdBy:id,name,email,designation',
            'leadProduct:id,lead_id,product_id,total_price,amount_paid',
            'leadProduct.payments:id,lead_product_id,amount',
            'department:id,name',
            'product:id,product_category_id',
            'product.category:id,name',
            'initiatedBy:id,name',
            'reviewedBy:id,name',
            'productionApprovalReviewedBy:id,name',
            'projectAllocatedBy:id,name',
            'employeeAllocatedBy:id,name',
        ];
    }

    private function decorateProjectForUser(ProductionInitiation $productionInitiation, User $user): ProductionInitiation
    {
        if (! $this->shouldLimitToAssignedProjects($user)) {
            return $productionInitiation;
        }

        $currentAllocation = $this->tlEmployeeAllocationForUser($productionInitiation, $user);

        $productionInitiation->current_team_status = $currentAllocation['status'];
        $productionInitiation->current_team_allocated_at = $currentAllocation['allocated_at']
            ? Carbon::parse($currentAllocation['allocated_at'])
            : null;
        $productionInitiation->current_team_allocated_by = $currentAllocation['allocated_by'];
        $productionInitiation->current_team_allocated_by_name = $this->userNameFromId($currentAllocation['allocated_by']);
        $productionInitiation->current_team_employee_user_ids = $currentAllocation['employee_user_ids'];

        return $productionInitiation;
    }

    private function ensureProjectIsVisibleToUser(ProductionInitiation $productionInitiation, User $user): void
    {
        $productionInitiation->loadMissing($this->projectRelations());

        abort_unless($this->resolveBucketForUser($productionInitiation, $user) !== null, 404);

        if ($this->shouldLimitToAssignedProjects($user)) {
            abort_unless($this->isAssignedTlForProject($productionInitiation, $user), 403);
        }

        if ($this->shouldLimitToEmployeeProjects($user)) {
            abort_unless($this->isAssignedEmployeeForProject($productionInitiation, $user), 403);
        }
    }

    private function tlEmployeeAllocations(ProductionInitiation $productionInitiation): Collection
    {
        return collect(Arr::wrap($productionInitiation->tl_employee_allocations))
            ->mapWithKeys(function ($allocation, $tlUserId) {
                if (is_array($allocation) && array_key_exists('tl_user_id', $allocation)) {
                    $tlUserId = (int) $allocation['tl_user_id'];
                } else {
                    $tlUserId = (int) $tlUserId;
                    $allocation = is_array($allocation) ? $allocation : [];
                    $allocation['tl_user_id'] = $tlUserId;
                }

                if ($tlUserId <= 0) {
                    return [];
                }

                return [
                    (string) $tlUserId => $this->normalizeTlEmployeeAllocation($allocation),
                ];
            });
    }

    private function tlEmployeeAllocationForUser(ProductionInitiation $productionInitiation, User $user): array
    {
        $allocation = $this->tlEmployeeAllocations($productionInitiation)->get((string) $user->id, [
            'tl_user_id' => $user->id,
            'status' => 'allocation_pending',
            'allocated_at' => null,
            'allocated_by' => null,
            'employee_user_ids' => [],
        ]);

        return $this->normalizeTlEmployeeAllocation($allocation);
    }

    private function tlEmployeeAllocationStatusForUser(ProductionInitiation $productionInitiation, User $user): string
    {
        return (string) ($this->tlEmployeeAllocationForUser($productionInitiation, $user)['status'] ?? 'allocation_pending');
    }

    private function normalizeTlEmployeeAllocation(array $allocation): array
    {
        $employeeUserIds = collect(Arr::wrap($allocation['employee_user_ids'] ?? []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        return [
            'tl_user_id' => (int) ($allocation['tl_user_id'] ?? 0),
            'status' => in_array($allocation['status'] ?? null, ['allocated', 'allocation_pending'], true)
                ? $allocation['status']
                : ($employeeUserIds !== [] ? 'allocated' : 'allocation_pending'),
            'allocated_at' => ! empty($allocation['allocated_at'])
                ? Carbon::parse($allocation['allocated_at'])->toDateTimeString()
                : null,
            'allocated_by' => ! empty($allocation['allocated_by']) ? (int) $allocation['allocated_by'] : null,
            'employee_user_ids' => $employeeUserIds,
        ];
    }

    private function summarizeTlEmployeeAllocations(array $tlAllocations): array
    {
        $normalizedAllocations = collect($tlAllocations)
            ->map(fn ($allocation) => $this->normalizeTlEmployeeAllocation((array) $allocation))
            ->filter(fn (array $allocation) => $allocation['tl_user_id'] > 0)
            ->values();

        $allEmployeeIds = $normalizedAllocations
            ->flatMap(fn (array $allocation) => $allocation['employee_user_ids'])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $latestAllocation = $normalizedAllocations
            ->filter(fn (array $allocation) => ! empty($allocation['allocated_at']))
            ->sortByDesc('allocated_at')
            ->first();

        $allTlAllocated = $normalizedAllocations->isNotEmpty()
            && $normalizedAllocations->every(fn (array $allocation) => $allocation['status'] === 'allocated');

        return [
            'employee_allocation_status' => $allTlAllocated ? 'allocated' : 'allocation_pending',
            'employee_allocated_at' => $latestAllocation['allocated_at'] ?? null,
            'employee_allocated_by' => $latestAllocation['allocated_by'] ?? null,
            'project_allocated_employee_user_ids' => $allEmployeeIds === [] ? null : $allEmployeeIds,
        ];
    }

    private function syncProductionCountReportAllocation(?ProductionInitiation $project, ?int $allocatedBy): void
    {
        if (! $project) {
            return;
        }

        $countReport = ProductionCountReport::query()
            ->where('production_initiation_id', $project->id)
            ->first();

        if (! $countReport) {
            return;
        }

        $countReport->update([
            'allocated_team_user_ids' => Arr::wrap($project->project_allocated_tl_user_ids) ?: null,
            'allocated_user_ids' => Arr::wrap($project->project_allocated_employee_user_ids) ?: null,
            'allocated_by' => $allocatedBy,
            'allocated_at' => Carbon::now(),
            'status' => $project->employee_allocation_status === 'allocated' ? 'employee_allocated' : 'team_allocated',
        ]);
    }

    private function resolveBucketForUser(ProductionInitiation $productionInitiation, User $user): ?string
    {
        if ($this->shouldLimitToAssignedProjects($user)) {
            return $this->resolveBucket((string) ($productionInitiation->current_team_status ?? $this->tlEmployeeAllocationStatusForUser($productionInitiation, $user)));
        }

        if ($this->shouldLimitToEmployeeProjects($user)) {
            return $this->isAssignedEmployeeForProject($productionInitiation, $user)
                ? 'allocated'
                : null;
        }

        return $this->resolveBucket((string) $productionInitiation->project_allocation_status);
    }

    private function resolveBucket(string $status): ?string
    {
        return match (strtolower(trim($status))) {
            'allocation_pending', 'pending' => 'allocation_pending',
            'allocated' => 'allocated',
            default => null,
        };
    }

    private function canAllocateTl(ProductionInitiation $productionInitiation, User $user): bool
    {
        if ($this->shouldLimitToAssignedProjects($user)) {
            return false;
        }

        $isApproved = in_array(strtolower(trim((string) $productionInitiation->production_approval_status)), ['approval', 'approved'], true);
        if (! $isApproved) {
            return false;
        }

        $isPending = strtolower(trim((string) $productionInitiation->project_allocation_status)) === 'allocation_pending';
        if ($isPending) {
            return true;
        }

        return $user->hasAdminLikeRole() || $this->hasProjectCoordinatorRole($user);
    }

    private function canAllocateEmployees(ProductionInitiation $productionInitiation, User $user): bool
    {
        return $this->isAssignedTlForProject($productionInitiation, $user)
            && strtolower(trim((string) $productionInitiation->project_allocation_status)) === 'allocated'
            && in_array(strtolower(trim((string) $this->tlEmployeeAllocationStatusForUser($productionInitiation, $user))), ['allocation_pending', 'allocated'], true);
    }

    private function canManageProjectSchedule(ProductionInitiation $productionInitiation, User $user): bool
    {
        return $user->hasAdminLikeRole() || $this->hasProjectCoordinatorRole($user);
    }

    private function shouldLimitToAssignedProjects(User $user): bool
    {
        return ! $user->hasAdminLikeRole() && $this->isUserTl($user);
    }

    private function shouldLimitToEmployeeProjects(User $user): bool
    {
        return ! $user->hasAdminLikeRole() && ! $this->isUserTl($user);
    }

    private function canQuickAddProductionUpdate(User $user): bool
    {
        return $this->shouldLimitToEmployeeProjects($user)
            && $user->resolvedRoles(withDepartment: true)->contains(function ($role) {
                return $this->roleKey((string) ($role->department?->name ?? '')) === 'development';
            });
    }

    private function isDevelopmentProject(ProductionInitiation $productionInitiation): bool
    {
        return $this->roleKey((string) ($productionInitiation->department?->name ?? '')) === 'development';
    }

    private function hasProjectCoordinatorRole(User $user): bool
    {
        return $user->resolvedRoles(withDepartment: true)->contains(function ($role) {
            $keys = [
                $this->roleKey((string) $role->name),
                $this->roleKey((string) ($role->display_name ?? '')),
            ];

            return collect($keys)->intersect([
                'project_coordinator',
                'project_coordination',
                'pc',
                'development_project_coordinator',
            ])->isNotEmpty();
        });
    }

    private function isUserTl(User $user): bool
    {
        return $user->resolvedRoles(withDepartment: true)->contains(function ($role) {
            return $this->isTlRole((string) $role->name)
                || $this->isTlRole((string) ($role->display_name ?? ''));
        });
    }

    private function isAssignedTlForProject(ProductionInitiation $productionInitiation, User $user): bool
    {
        return collect(Arr::wrap($productionInitiation->project_allocated_tl_user_ids))
            ->map(fn ($id) => (int) $id)
            ->contains((int) $user->id);
    }

    private function isAssignedEmployeeForProject(ProductionInitiation $productionInitiation, User $user): bool
    {
        return collect(Arr::wrap($productionInitiation->project_allocated_employee_user_ids))
            ->map(fn ($id) => (int) $id)
            ->contains((int) $user->id);
    }

    private function availableTlUsers(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->with(['roles.department'])
            ->get()
            ->filter(function (User $user) {
                return $user->resolvedRoles(withDepartment: true)->contains(function ($role) {
                    return $this->isTlRole((string) $role->name)
                        || $this->isTlRole((string) ($role->display_name ?? ''));
                });
            })
            ->map(fn (User $user) => $this->mapUserSummary($user))
            ->sortBy('name')
            ->values();
    }

    private function availableTeamMembers(User $user): Collection
    {
        $managedMembers = $user->managedUsers()
            ->where('users.is_active', true)
            ->with(['roles.department'])
            ->get()
            ->map(fn (User $teamMember) => $this->mapUserSummary($teamMember));

        return $managedMembers
            ->push($this->mapUserSummary($user))
            ->unique('id')
            ->sortBy('name')
            ->values();
    }

    private function allocatedTlUsers(ProductionInitiation $productionInitiation): Collection
    {
        return $this->usersFromIds(
            Arr::wrap($productionInitiation->project_allocated_tl_user_ids)
        );
    }

    private function tlAllocationSummaries(ProductionInitiation $productionInitiation): Collection
    {
        $allocatedTlUsers = $this->allocatedTlUsers($productionInitiation)->keyBy('id');
        $tlAllocations = $this->tlEmployeeAllocations($productionInitiation);

        return collect(Arr::wrap($productionInitiation->project_allocated_tl_user_ids))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->map(function (int $tlUserId) use ($allocatedTlUsers, $tlAllocations) {
                $tlUser = $allocatedTlUsers->get($tlUserId);
                $allocation = $this->normalizeTlEmployeeAllocation(
                    $tlAllocations->get((string) $tlUserId, ['tl_user_id' => $tlUserId])
                );
                $employees = $this->usersFromIds($allocation['employee_user_ids']);

                return (object) [
                    'tl_user_id' => $tlUserId,
                    'tl_user' => $tlUser,
                    'status' => (string) ($allocation['status'] ?? 'allocation_pending'),
                    'allocated_at' => ! empty($allocation['allocated_at'])
                        ? Carbon::parse($allocation['allocated_at'])
                        : null,
                    'allocated_by_name' => $this->userNameFromId($allocation['allocated_by'] ?? null) ?: 'Pending',
                    'employees' => $employees,
                ];
            })
            ->values();
    }

    private function allocatedEmployees(ProductionInitiation $productionInitiation, ?User $tlUser = null): Collection
    {
        $employeeUserIds = $tlUser
            ? ($this->tlEmployeeAllocations($productionInitiation)->get((string) $tlUser->id)['employee_user_ids'] ?? [])
            : Arr::wrap($productionInitiation->project_allocated_employee_user_ids);

        return $this->usersFromIds(
            Arr::wrap($employeeUserIds)
        );
    }

    private function usersFromIds(array $ids): Collection
    {
        $selectedIds = collect($ids)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        if ($selectedIds->isEmpty()) {
            return collect();
        }

        return User::query()
            ->whereIn('id', $selectedIds->all())
            ->where('is_active', true)
            ->with(['roles.department'])
            ->get()
            ->map(fn (User $user) => $this->mapUserSummary($user))
            ->sortBy('name')
            ->values();
    }

    private function userNameFromId(?int $userId): ?string
    {
        static $userNameCache = [];

        $userId = $userId ? (int) $userId : 0;

        if ($userId <= 0) {
            return null;
        }

        if (! array_key_exists($userId, $userNameCache)) {
            $userNameCache[$userId] = User::query()
                ->whereKey($userId)
                ->value('name');
        }

        return $userNameCache[$userId];
    }

    private function mapUserSummary(User $user): object
    {
        $roles = $user->resolvedRoles(withDepartment: true);
        $departments = $roles
            ->map(fn ($role) => $role->department?->name)
            ->filter()
            ->unique()
            ->values();

        return (object) [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role_names' => $roles
                ->map(fn ($role) => $role->display_name ?: Str::of((string) $role->name)->afterLast('__')->replace('_', ' ')->title()->value())
                ->unique()
                ->values()
                ->all(),
            'departments' => $departments->all(),
            'department_label' => $departments->isNotEmpty() ? $departments->implode(', ') : 'All Departments',
        ];
    }

    private function roleKey(string $value): string
    {
        $value = Str::contains($value, '__') ? Str::afterLast($value, '__') : $value;

        return Str::of($value)
            ->lower()
            ->replace('&', 'and')
            ->replace(['-', ' '], '_')
            ->replaceMatches('/[^a-z0-9_]+/', '')
            ->replaceMatches('/_+/', '_')
            ->trim('_')
            ->value();
    }

    private function isTlRole(string $value): bool
    {
        $key = $this->roleKey($value);

        if (in_array($key, self::TL_ROLE_KEYS, true)) {
            return true;
        }

        return Str::contains($key, [
            'tl',
            'team_lead',
            'teamleader',
            'team_leader',
            'manager',
            'lead',
        ]);
    }

    public function myAccounts(Request $request): View
    {
        $user = auth()->user();
        abort_unless($user->belongsToDesigningDepartment() || $user->belongsToDigitalMarketingDepartment(), 403);

        $projectQuery = ProductionInitiation::query()
            ->whereIn('production_approval_status', ['approval', 'approved'])
            ->where('project_allocation_status', 'allocated');

        if ($this->shouldLimitToAssignedProjects($user)) {
            $projectQuery->whereJsonContains('project_allocated_tl_user_ids', $user->id);
        } elseif ($this->shouldLimitToEmployeeProjects($user)) {
            $projectQuery->whereJsonContains('project_allocated_employee_user_ids', $user->id);
        } else {
            $deptIds = Department::where(function($q) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%design%'])
                  ->orWhereRaw('LOWER(name) LIKE ?', ['%marketing%'])
                  ->orWhereRaw('LOWER(name) LIKE ?', ['%digital%']);
            })->pluck('id')->toArray();
            $projectQuery->whereIn('department_id', $deptIds);
        }

        $allocatedProjects = $projectQuery->get();
        $leadIds = $allocatedProjects->pluck('lead_id')->filter()->unique();

        $leads = Lead::whereIn('id', $leadIds)
            ->orderBy('company_name')
            ->get()
            ->map(function ($lead) {
                $projects = ProductionInitiation::where('lead_id', $lead->id)->get();

                $totalPosters = 0;
                $totalVideos = 0;

                foreach ($projects as $project) {
                    if (is_array($project->custom_form_data)) {
                        foreach ($project->custom_form_data as $field) {
                            $label = strtolower(trim((string) ($field['label'] ?? ($field['key'] ?? ''))));
                            if ($label === 'number of posters' || $label === 'number of poster') {
                                $totalPosters += (int) ($field['value'] ?? 0);
                            } elseif ($label === 'number of videos' || $label === 'number of video') {
                                $totalVideos += (int) ($field['value'] ?? 0);
                            }
                        }
                    }
                }

                $completedPosters = (int) ProjectTimesheet::whereIn('production_initiation_id', $projects->pluck('id'))->sum('poster_count');
                $pendingPosters = max(0, $totalPosters - $completedPosters);

                $completedVideos = (int) ProjectTimesheet::whereIn('production_initiation_id', $projects->pluck('id'))->sum('video_count');
                $pendingVideos = max(0, $totalVideos - $completedVideos);

                $renewalsCount = LeadProduct::where('lead_id', $lead->id)
                    ->whereHas('product', function ($query) {
                        $query->where('count_wise_report', true);
                    })
                    ->count();

                $lead->total_posters = $totalPosters;
                $lead->completed_posters = $completedPosters;
                $lead->pending_posters = $pendingPosters;

                $lead->total_videos = $totalVideos;
                $lead->completed_videos = $completedVideos;
                $lead->pending_videos = $pendingVideos;

                $lead->renewals_count = $renewalsCount;

                return $lead;
            });

        return view('pages.projects.my-accounts', [
            'leads' => $leads,
            'isDesigningDashboard' => $user->belongsToDesigningDepartment(),
        ]);
    }

    public function showMyAccount(Lead $lead): View
    {
        $user = auth()->user();
        abort_unless($user->belongsToDesigningDepartment() || $user->belongsToDigitalMarketingDepartment(), 403);

        $renewals = LeadProduct::where('lead_id', $lead->id)->with(['product'])->get();

        $projects = ProductionInitiation::where('lead_id', $lead->id)
            ->with(['department', 'timesheets', 'leadProduct.product'])
            ->get()
            ->map(function ($project) {
                // Parse custom form data for custom date bounds
                $startDateCustom = null;
                $endDateCustom = null;

                if (is_array($project->custom_form_data)) {
                    foreach ($project->custom_form_data as $field) {
                        $label = strtolower(trim((string) ($field['label'] ?? ($field['key'] ?? ''))));
                        $value = trim((string) ($field['value'] ?? ''));

                        if ($label === 'start date') {
                            $startDateCustom = $value;
                        } elseif ($label === 'end date') {
                            $endDateCustom = $value;
                        }
                    }
                }

                $start = $startDateCustom ? Carbon::parse($startDateCustom)->startOfDay() : ($project->production_approval_reviewed_at ?: ($project->project_allocated_at ?: $project->created_at));
                $project->start_date = $start ? Carbon::parse($start)->startOfDay() : null;

                $end = $endDateCustom ? Carbon::parse($endDateCustom)->startOfDay() : $this->projectDeliveryDate($project);
                $project->project_delivery_date = $end ? Carbon::parse($end)->startOfDay() : null;

                $deliveryDate = $project->project_delivery_date;
                $project->is_overdue = $deliveryDate && $deliveryDate->isPast() && $project->project_execution_status !== 'delivered';

                $project->onboarded_posters = 0;
                $project->onboarded_videos = 0;
                if (is_array($project->custom_form_data)) {
                    foreach ($project->custom_form_data as $field) {
                        $label = strtolower(trim((string) ($field['label'] ?? ($field['key'] ?? ''))));
                        if ($label === 'number of posters' || $label === 'number of poster') {
                            $project->onboarded_posters = (int) ($field['value'] ?? 0);
                        } elseif ($label === 'number of videos' || $label === 'number of video') {
                            $project->onboarded_videos = (int) ($field['value'] ?? 0);
                        }
                    }
                }

                $project->delivered_posters = (int) $project->timesheets->sum('poster_count');
                $project->delivered_videos = (int) $project->timesheets->sum('video_count');

                $project->allocated_names = $this->allocatedEmployees($project)->pluck('name')->implode(', ') ?: 'Pending';

                return $project;
            });

        // ── Section 1 Projects: count_wise_report = true AND is_this_renewal_product = true ──
        $countWiseProjects = $projects->filter(function ($project) {
            $product = $project->leadProduct?->product;
            $deptName = strtolower(trim((string) ($project->department?->name ?? '')));
            $isDesignOrDm = str_contains($deptName, 'design') || str_contains($deptName, 'digital') || str_contains($deptName, 'marketing');

            if ($product) {
                return (bool) $product->count_wise_report && (bool) $product->is_this_renewal_product;
            }

            // Fallback if product model link is missing: posters/videos count > 0 AND design/DM dept
            return $isDesignOrDm && ($project->onboarded_posters > 0 || $project->onboarded_videos > 0);
        });

        // ── Section 2 Projects: count_wise_report = false AND is_this_renewal_product = true ──
        $nonCountWiseProjects = $projects->filter(function ($project) {
            $product = $project->leadProduct?->product;
            $deptName = strtolower(trim((string) ($project->department?->name ?? '')));
            $isDesignOrDm = str_contains($deptName, 'design') || str_contains($deptName, 'digital') || str_contains($deptName, 'marketing');

            if ($product) {
                return (! (bool) $product->count_wise_report) && (bool) $product->is_this_renewal_product;
            }

            // Fallback if product model link is missing: no poster/video count AND design/DM dept
            return $isDesignOrDm && ($project->onboarded_posters == 0 && $project->onboarded_videos == 0);
        });

        // Construct flat rows mapping renewals and Section 1 count-wise projects
        $tableRows = collect();
        foreach ($renewals as $renewal) {
            $product = $renewal->product;
            $isCountWiseRenewal = $product
                ? ((bool) $product->count_wise_report && (bool) $product->is_this_renewal_product)
                : true;

            if (! $isCountWiseRenewal) {
                continue;
            }

            $renewalProjects = $countWiseProjects->where('lead_product_id', $renewal->id);
            if ($renewalProjects->isNotEmpty()) {
                foreach ($renewalProjects as $project) {
                    $tableRows->push([
                        'renewal_id' => $renewal->id,
                        'renewal_name' => $renewal->product?->product_name ?: ($renewal->product_name ?: '—'),
                        'has_project' => true,
                        'project' => $project,
                    ]);
                }
            }
        }

        // Push any matching count-wise projects not linked directly to a renewal row
        foreach ($countWiseProjects as $project) {
            if (! $tableRows->pluck('project.id')->contains($project->id)) {
                $tableRows->push([
                    'renewal_id' => $project->lead_product_id,
                    'renewal_name' => $project->product_name ?: ($project->leadProduct?->product_name ?? 'Count-Wise Renewal Project'),
                    'has_project' => true,
                    'project' => $project,
                ]);
            }
        }

        // Compute overall aggregates for count-wise projects
        $totalPosters = $countWiseProjects->sum('onboarded_posters');
        $completedPosters = $countWiseProjects->sum('delivered_posters');
        $pendingPosters = max(0, $totalPosters - $completedPosters);
        $overduePosters = $countWiseProjects->filter(fn ($p) => $p->is_overdue)->sum(fn ($p) => max(0, $p->onboarded_posters - $p->delivered_posters));

        $totalVideos = $countWiseProjects->sum('onboarded_videos');
        $completedVideos = $countWiseProjects->sum('delivered_videos');
        $pendingVideos = max(0, $totalVideos - $completedVideos);
        $overdueVideos = $countWiseProjects->filter(fn ($p) => $p->is_overdue)->sum(fn ($p) => max(0, $p->onboarded_videos - $p->delivered_videos));

        $totalRenewals = $tableRows->count();

        $stats = [
            'total_posters' => $totalPosters,
            'completed_posters' => $completedPosters,
            'pending_posters' => $pendingPosters,
            'overdue_posters' => $overduePosters,
            'total_videos' => $totalVideos,
            'completed_videos' => $completedVideos,
            'pending_videos' => $pendingVideos,
            'overdue_videos' => $overdueVideos,
            'total_renewals' => $totalRenewals,
        ];

        return view('pages.projects.show-my-account', [
            'lead' => $lead,
            'tableRows' => $tableRows,
            'nonCountWiseProjects' => $nonCountWiseProjects,
            'stats' => $stats,
            'isDesigningDashboard' => $user->belongsToDesigningDepartment(),
        ]);
    }
}
