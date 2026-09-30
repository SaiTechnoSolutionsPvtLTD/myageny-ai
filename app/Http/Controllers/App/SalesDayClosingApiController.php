<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Lead;
use App\Models\LeadCallUpdate;
use App\Models\LeadStatus;
use App\Models\Quotation;
use App\Models\SalesDailyClosing;
use App\Models\User;
use App\Services\DataVisibilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SalesDayClosingApiController extends Controller
{
    public function __construct(private readonly DataVisibilityService $visibility) {}

    /**
     * Mobile API: List Sales Day Closings with branch-wise filters and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $today = now()->toDateString();

        $fromDate = $request->input('from_date') ?: $request->input('date_from');
        $toDate = $request->input('to_date') ?: $request->input('date_to');
        $selectedDate = $request->filled('date') ? $request->input('date') : ($toDate ?: ($fromDate ?: $today));

        $isCompanyAdmin  = $user->isSuperAdmin() || $user->isCompanyAdminRole() || $user->isCbo();
        $isBranchManager = !$isCompanyAdmin && $user->isBranchManager();
        $isBranchAdmin   = !$isCompanyAdmin && !$isBranchManager && $user->isBranchAdmin();
        $isTl            = !$isCompanyAdmin && !$isBranchManager && !$isBranchAdmin && $user->hasTlLikeRole();
        $isExecutive     = !$isCompanyAdmin && !$isBranchManager && !$isBranchAdmin && !$isTl;

        $isAdminLike = $isCompanyAdmin || $isBranchManager || $isBranchAdmin;
        $isTlLike    = $isTl;

        $visibleUserIds = $this->resolveVisibleUserIds($user);
        $allowedBranchIds = $this->resolveAllowedBranchIds($user);
        $effectiveBranchId = $this->resolveEffectiveBranchId($user, $request->input('branch_id'), $allowedBranchIds);

        // Query visible users for filter dropdown
        if ($visibleUserIds !== null) {
            $assignableUsers = User::withoutGlobalScope('branch')
                ->whereIn('id', $visibleUserIds)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'branch_id']);
        } else {
            $assignableUsers = $this->visibility->visibleAssignableUsers($user);
        }
        if ($assignableUsers->isEmpty()) {
            $assignableUsers = collect([$user]);
        }

        // Branch filter dropdown - exactly mirrors web SalesDailyClosingController lines 63-73
        if ($isBranchAdmin || $isBranchManager) {
            $userBranchIds = $user->getMyBranchIds();
            $branches = Branch::whereIn('id', $userBranchIds)->where('is_active', true)->orderBy('name')->get(['id', 'name']);
            if ($branches->isEmpty() && $user->branch) {
                $branches = collect([$user->branch]);
            }
        } elseif ($isCompanyAdmin) {
            $branches = Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        } else {
            $branches = $user->branch ? collect([$user->branch]) : collect();
        }

        // Base query for SalesDailyClosing submissions
        $query = SalesDailyClosing::with([
            'user:id,name,email,branch_id,designation',
            'user.branch:id,name',
            'branch:id,name',
            'reviewer:id,name',
        ])->latest('closing_date')->latest('id');

        // Apply visibility
        if ($visibleUserIds !== null) {
            $query->whereIn('user_id', $visibleUserIds);
        }

        // Apply user filter
        if ($request->filled('user_id')) {
            $filterUserId = (int) $request->input('user_id');
            if ($visibleUserIds === null || in_array($filterUserId, $visibleUserIds, true)) {
                $query->where('user_id', $filterUserId);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // Apply branch filter (branch-wise data restriction)
        if ($effectiveBranchId) {
            $query->where('branch_id', $effectiveBranchId);
        } elseif (!empty($allowedBranchIds)) {
            $query->whereIn('branch_id', $allowedBranchIds);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($fromDate && $toDate) {
            $query->whereBetween('closing_date', [$fromDate, $toDate]);
        } elseif ($fromDate) {
            $query->whereDate('closing_date', '>=', $fromDate);
        } elseif ($toDate) {
            $query->whereDate('closing_date', '<=', $toDate);
        } elseif ($request->filled('date')) {
            $query->whereDate('closing_date', $selectedDate);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('closing_notes', 'like', "%{$search}%")
                  ->orWhere('plan_for_tomorrow', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = min((int) ($request->input('per_page', 20)), 100);
        $paginator = $query->paginate($perPage);

        // Calculate summary KPI stats for selected date & user & branch
        $targetUserId = $request->filled('user_id') ? (int) $request->input('user_id') : null;
        if ($targetUserId && $visibleUserIds !== null && !in_array($targetUserId, $visibleUserIds, true)) {
            $targetUserId = $user->id;
        }
        if (!$isCompanyAdmin && !$targetUserId && $visibleUserIds !== null && count($visibleUserIds) === 1) {
            $targetUserId = $user->id;
        }

        $dateRangeFilter = ($fromDate && $toDate) ? [$fromDate, $toDate] : ($fromDate ?: ($toDate ?: $selectedDate));
        $stats = $this->calculateCallStats($targetUserId, $dateRangeFilter, $visibleUserIds, $effectiveBranchId, $allowedBranchIds);

        // Check if the current user has submitted today's closing
        $myTodayClosing = SalesDailyClosing::where('user_id', $user->id)
            ->whereDate('closing_date', $today)
            ->first();

        $closingRows = collect($paginator->items())->map(function ($item) {
            return [
                'id' => $item->id,
                'user_id' => $item->user_id,
                'branch_id' => $item->branch_id ?? $item->user?->branch_id,
                'branch_name' => $item->branch?->name ?? ($item->user?->branch?->name ?? '—'),
                'user_name' => $item->user?->name ?? 'Unknown',
                'user_email' => $item->user?->email,
                'user_designation' => $item->user?->designation,
                'closing_date' => $item->closing_date ? Carbon::parse($item->closing_date)->toDateString() : '',
                'total_calls' => (int) ($item->total_calls ?? $item->total_calls_made),
                'total_calls_made' => (int) ($item->total_calls ?? $item->total_calls_made),
                'unique_calls' => (int) $item->unique_calls,
                'new_calls' => (int) $item->new_calls,
                'followup_calls' => (int) $item->followup_calls,
                'onetime_calls' => (int) ($item->onetime_calls ?? $item->all_one_time_calls),
                'all_one_time_calls' => (int) ($item->onetime_calls ?? $item->all_one_time_calls),
                'converted_count' => (int) ($item->converted_count ?? $item->converted_leads_count),
                'converted_leads_count' => (int) ($item->converted_count ?? $item->converted_leads_count),
                'quotations_count' => (int) ($item->quotations_count ?? $item->quotations_created_count),
                'quotations_created_count' => (int) ($item->quotations_count ?? $item->quotations_created_count),
                'closing_notes' => $item->closing_notes ?? '',
                'is_on_leave_tomorrow' => (bool) $item->is_on_leave_tomorrow,
                'tomorrow_plans' => is_array($item->tomorrow_plans) ? $item->tomorrow_plans : [],
                'total_expected_value' => (float) $item->total_expected_value,
                'plan_for_tomorrow' => $item->plan_for_tomorrow ?? '',
                'status' => $item->status ?? 'submitted',
                'reviewed_by_name' => $item->reviewer?->name,
                'reviewed_at' => $item->reviewed_at ? Carbon::parse($item->reviewed_at)->toDateTimeString() : null,
                'created_at' => $item->created_at ? $item->created_at->toDateTimeString() : null,
            ];
        });

        return response()->json([
            'success' => true,
            'can_create' => true,
            'can_review' => $isAdminLike || $isTlLike,
            'is_admin_like' => $isAdminLike,
            'is_tl_like' => $isTlLike,
            'selected_date' => $selectedDate,
            'date_from' => $fromDate,
            'date_to' => $toDate,
            'today' => $today,
            'selected_branch_id' => $effectiveBranchId,
            'stats' => $stats,
            'kpi_stats' => $stats,
            'branches' => $branches->map(fn($b) => ['id' => $b->id, 'name' => $b->name]),
            'assignable_users' => $assignableUsers->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'branch_id' => $u->branch_id]),
            'users' => $assignableUsers->map(fn($u) => ['id' => $u->id, 'name' => $u->name]),
            'my_today_closing' => $myTodayClosing ? [
                'id' => $myTodayClosing->id,
                'user_id' => $myTodayClosing->user_id,
                'closing_date' => $myTodayClosing->closing_date ? Carbon::parse($myTodayClosing->closing_date)->toDateString() : '',
                'total_calls' => (int) ($myTodayClosing->total_calls ?? $myTodayClosing->total_calls_made),
                'unique_calls' => (int) $myTodayClosing->unique_calls,
                'new_calls' => (int) $myTodayClosing->new_calls,
                'followup_calls' => (int) $myTodayClosing->followup_calls,
                'onetime_calls' => (int) ($myTodayClosing->onetime_calls ?? $myTodayClosing->all_one_time_calls),
                'converted_count' => (int) ($myTodayClosing->converted_count ?? $myTodayClosing->converted_leads_count),
                'quotations_count' => (int) ($myTodayClosing->quotations_count ?? $myTodayClosing->quotations_created_count),
                'closing_notes' => $myTodayClosing->closing_notes ?? '',
                'is_on_leave_tomorrow' => (bool) $myTodayClosing->is_on_leave_tomorrow,
                'tomorrow_plans' => is_array($myTodayClosing->tomorrow_plans) ? $myTodayClosing->tomorrow_plans : [],
                'total_expected_value' => (float) $myTodayClosing->total_expected_value,
                'plan_for_tomorrow' => $myTodayClosing->plan_for_tomorrow ?? '',
                'status' => $myTodayClosing->status ?? 'submitted',
            ] : null,
            'closings' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'data' => $closingRows,
            ],
            'data' => $closingRows,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Mobile API: Get Call Stats for user and date with branch scoping.
     */
    public function getStats(Request $request): JsonResponse
    {
        $user = auth()->user();
        $targetUserId = $request->filled('user_id') ? (int) $request->input('user_id') : $user->id;
        $dateStr = $request->input('date') ?: ($request->input('closing_date') ?: now()->toDateString());

        $visibleUserIds = $this->resolveVisibleUserIds($user);

        if ($visibleUserIds !== null && !in_array($targetUserId, $visibleUserIds, true)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        $allowedBranchIds = $this->resolveAllowedBranchIds($user);
        $effectiveBranchId = $this->resolveEffectiveBranchId($user, $request->input('branch_id'), $allowedBranchIds);

        try {
            $date = Carbon::parse($dateStr)->toDateString();
        } catch (\Throwable) {
            $date = now()->toDateString();
        }

        $stats = $this->calculateCallStats($targetUserId, $date, $visibleUserIds, $effectiveBranchId, $allowedBranchIds);

        $existing = SalesDailyClosing::where('user_id', $targetUserId)
            ->whereDate('closing_date', $date)
            ->first();

        $existingData = null;
        if ($existing) {
            $existingData = [
                'id' => $existing->id,
                'closing_notes' => $existing->closing_notes,
                'is_on_leave_tomorrow' => (bool) $existing->is_on_leave_tomorrow,
                'tomorrow_plans' => is_array($existing->tomorrow_plans) ? $existing->tomorrow_plans : [],
                'total_expected_value' => (float) $existing->total_expected_value,
                'plan_for_tomorrow' => $existing->plan_for_tomorrow,
                'status' => $existing->status,
            ];
        }

        return response()->json([
            'success' => true,
            'date' => $date,
            'stats' => $stats,
            'kpi_stats' => $stats,
            'exists' => (bool) $existing,
            'closing' => $existingData,
        ]);
    }

    /**
     * Mobile API: Fetch call log details for metric card strictly scoped to branch and user visibility.
     */
    public function getCallDetails(Request $request): JsonResponse
    {
        $user = auth()->user();
        $metric     = trim((string) $request->input('metric', 'unique_calls'));
        $dateFrom   = trim((string) $request->input('date_from', ''));
        $dateTo     = trim((string) $request->input('date_to', ''));
        $singleDate = trim((string) ($request->input('date') ?: now()->toDateString()));
        $targetUserId = $request->filled('user_id') ? (int) $request->input('user_id') : null;

        $isCompanyAdmin = $user->isSuperAdmin() || $user->isCompanyAdminRole() || $user->isCbo();
        $visibleUserIds = $this->resolveVisibleUserIds($user);

        if ($targetUserId && $visibleUserIds !== null && !in_array($targetUserId, $visibleUserIds, true)) {
            $targetUserId = $user->id;
        }

        if (!$isCompanyAdmin && !$targetUserId && $visibleUserIds !== null && count($visibleUserIds) === 1) {
            $targetUserId = $user->id;
        }

        $allowedBranchIds = $this->resolveAllowedBranchIds($user);
        $effectiveBranchId = $this->resolveEffectiveBranchId($user, $request->input('branch_id'), $allowedBranchIds);

        if ($dateFrom && $dateTo) {
            try {
                $startDate = Carbon::parse($dateFrom)->startOfDay();
                $endDate   = Carbon::parse($dateTo)->endOfDay();
                $dateLabel = Carbon::parse($dateFrom)->format('d M Y') . ' – ' . Carbon::parse($dateTo)->format('d M Y');
            } catch (\Throwable) {
                $startDate = now()->startOfDay();
                $endDate   = now()->endOfDay();
                $dateLabel = now()->format('d M Y');
            }
        } elseif ($dateFrom || $singleDate) {
            $ref = $dateFrom ?: $singleDate;
            try {
                $startDate = Carbon::parse($ref)->startOfDay();
                $endDate   = Carbon::parse($ref)->endOfDay();
                $dateLabel = Carbon::parse($ref)->format('d M Y');
            } catch (\Throwable) {
                $startDate = now()->startOfDay();
                $endDate   = now()->endOfDay();
                $dateLabel = now()->format('d M Y');
            }
        } else {
            $startDate = now()->startOfDay();
            $endDate   = now()->endOfDay();
            $dateLabel = now()->format('d M Y');
        }

        $query = LeadCallUpdate::with([
            'lead' => function ($lq) {
                $lq->withoutGlobalScopes()->withTrashed()->select('id', 'company_name', 'contact_name', 'mobile_number', 'email', 'assigned_to', 'branch_id')
                   ->with(['assignedTo' => function ($aq) {
                       $aq->withoutGlobalScopes()->withTrashed()->select('id', 'name');
                   }]);
            },
            'user' => function ($uq) {
                $uq->withoutGlobalScopes()->withTrashed()->select('id', 'name', 'branch_id');
            },
            'outCome:id,name',
            'outComeSubCategory:id,name',
        ])->whereBetween('called_at', [$startDate, $endDate])->latest('called_at');

        if ($targetUserId) {
            $query->where('user_id', $targetUserId);
        } elseif ($visibleUserIds !== null) {
            $query->whereIn('user_id', $visibleUserIds);
        }

        // Branch filter on Lead: Exactly mirrors web SalesDailyClosingController lines 298-303
        if ($effectiveBranchId) {
            $query->whereHas('lead', function ($lq) use ($effectiveBranchId) {
                $lq->where('branch_id', $effectiveBranchId);
            });
        } elseif (!empty($allowedBranchIds)) {
            $query->whereHas('lead', function ($lq) use ($allowedBranchIds) {
                $lq->whereIn('branch_id', $allowedBranchIds);
            });
        }

        $nextFollowupDateCol = Schema::hasColumn('lead_call_updates', 'next_follow_up')
            ? 'next_follow_up'
            : (Schema::hasColumn('lead_call_updates', 'next_followup_date') ? 'next_followup_date' : null);
        $nextFollowupTimeCol = Schema::hasColumn('lead_call_updates', 'followup_time')
            ? 'followup_time'
            : (Schema::hasColumn('lead_call_updates', 'next_followup_time') ? 'next_followup_time' : null);

        $startDateStr = $startDate->toDateString();
        $metricTitle = 'Call Details';

        switch ($metric) {
            case 'new_calls':
            case 'total_new_calls':
                $metricTitle = 'Total New Calls (Fresh Outreach)';
                $query->whereNotExists(function ($q) use ($startDateStr) {
                    $q->select(DB::raw(1))
                        ->from('lead_call_updates as prev')
                        ->whereColumn('prev.lead_id', 'lead_call_updates.lead_id')
                        ->whereDate('prev.called_at', '<', $startDateStr);
                })->whereIn('lead_call_updates.id', function ($sub) use ($startDate, $endDate, $targetUserId, $visibleUserIds, $effectiveBranchId, $allowedBranchIds) {
                    $sub->select(DB::raw('MAX(id)'))
                        ->from('lead_call_updates')
                        ->whereBetween('called_at', [$startDate, $endDate]);
                    if ($targetUserId) {
                        $sub->where('user_id', $targetUserId);
                    } elseif ($visibleUserIds !== null) {
                        $sub->whereIn('user_id', $visibleUserIds);
                    }
                    if ($effectiveBranchId) {
                        $sub->whereExists(function ($lq) use ($effectiveBranchId) {
                            $lq->select(DB::raw(1))
                               ->from('leads')
                               ->whereColumn('leads.id', 'lead_call_updates.lead_id')
                               ->where('leads.branch_id', $effectiveBranchId);
                        });
                    } elseif (!empty($allowedBranchIds)) {
                        $sub->whereExists(function ($lq) use ($allowedBranchIds) {
                            $lq->select(DB::raw(1))
                               ->from('leads')
                               ->whereColumn('leads.id', 'lead_call_updates.lead_id')
                               ->whereIn('leads.branch_id', $allowedBranchIds);
                        });
                    }
                    $sub->groupBy('lead_id');
                });
                break;

            case 'followup_calls':
                $metricTitle = 'Followup Calls (Pipeline Leads)';
                $query->whereExists(function ($q) use ($startDateStr) {
                    $q->select(DB::raw(1))
                        ->from('lead_call_updates as prev')
                        ->whereColumn('prev.lead_id', 'lead_call_updates.lead_id')
                        ->whereDate('prev.called_at', '<', $startDateStr);
                })->whereIn('lead_call_updates.id', function ($sub) use ($startDate, $endDate, $targetUserId, $visibleUserIds, $effectiveBranchId, $allowedBranchIds) {
                    $sub->select(DB::raw('MAX(id)'))
                        ->from('lead_call_updates')
                        ->whereBetween('called_at', [$startDate, $endDate]);
                    if ($targetUserId) {
                        $sub->where('user_id', $targetUserId);
                    } elseif ($visibleUserIds !== null) {
                        $sub->whereIn('user_id', $visibleUserIds);
                    }
                    if ($effectiveBranchId) {
                        $sub->whereExists(function ($lq) use ($effectiveBranchId) {
                            $lq->select(DB::raw(1))
                               ->from('leads')
                               ->whereColumn('leads.id', 'lead_call_updates.lead_id')
                               ->where('leads.branch_id', $effectiveBranchId);
                        });
                    } elseif (!empty($allowedBranchIds)) {
                        $sub->whereExists(function ($lq) use ($allowedBranchIds) {
                            $lq->select(DB::raw(1))
                               ->from('leads')
                               ->whereColumn('leads.id', 'lead_call_updates.lead_id')
                               ->whereIn('leads.branch_id', $allowedBranchIds);
                        });
                    }
                    $sub->groupBy('lead_id');
                });
                break;

            case 'one_time_calls':
            case 'onetime_calls':
            case 'all_one_time_calls':
            case 'all_onetime_calls':
                $metricTitle = 'All One Time Calls (No Future Follow-up)';
                $query->where(function ($q) use ($nextFollowupDateCol) {
                    if ($nextFollowupDateCol) {
                        $q->whereNull($nextFollowupDateCol);
                    } else {
                        $q->whereNull('next_follow_up');
                    }
                    $q->orWhere('outcome', '9')
                      ->orWhere('outcome', '6')
                      ->orWhere('outcome', 'not_interested')
                      ->orWhere('outcome', 'closed');
                })->whereIn('lead_call_updates.id', function ($sub) use ($startDate, $endDate, $targetUserId, $visibleUserIds, $effectiveBranchId, $allowedBranchIds) {
                    $sub->select(DB::raw('MAX(id)'))
                        ->from('lead_call_updates')
                        ->whereBetween('called_at', [$startDate, $endDate]);
                    if ($targetUserId) {
                        $sub->where('user_id', $targetUserId);
                    } elseif ($visibleUserIds !== null) {
                        $sub->whereIn('user_id', $visibleUserIds);
                    }
                    if ($effectiveBranchId) {
                        $sub->whereExists(function ($lq) use ($effectiveBranchId) {
                            $lq->select(DB::raw(1))
                               ->from('leads')
                               ->whereColumn('leads.id', 'lead_call_updates.lead_id')
                               ->where('leads.branch_id', $effectiveBranchId);
                        });
                    } elseif (!empty($allowedBranchIds)) {
                        $sub->whereExists(function ($lq) use ($allowedBranchIds) {
                            $lq->select(DB::raw(1))
                               ->from('leads')
                               ->whereColumn('leads.id', 'lead_call_updates.lead_id')
                               ->whereIn('leads.branch_id', $allowedBranchIds);
                        });
                    }
                    $sub->groupBy('lead_id');
                });
                break;

            case 'unique_calls':
                $metricTitle = 'Unique Calls (Distinct Leads Contacted)';
                $query->whereIn('lead_call_updates.id', function ($sub) use ($startDate, $endDate, $targetUserId, $visibleUserIds, $effectiveBranchId, $allowedBranchIds) {
                    $sub->select(DB::raw('MAX(id)'))
                        ->from('lead_call_updates')
                        ->whereBetween('called_at', [$startDate, $endDate]);
                    if ($targetUserId) {
                        $sub->where('user_id', $targetUserId);
                    } elseif ($visibleUserIds !== null) {
                        $sub->whereIn('user_id', $visibleUserIds);
                    }
                    if ($effectiveBranchId) {
                        $sub->whereExists(function ($lq) use ($effectiveBranchId) {
                            $lq->select(DB::raw(1))
                               ->from('leads')
                               ->whereColumn('leads.id', 'lead_call_updates.lead_id')
                               ->where('leads.branch_id', $effectiveBranchId);
                        });
                    } elseif (!empty($allowedBranchIds)) {
                        $sub->whereExists(function ($lq) use ($allowedBranchIds) {
                            $lq->select(DB::raw(1))
                               ->from('leads')
                               ->whereColumn('leads.id', 'lead_call_updates.lead_id')
                               ->whereIn('leads.branch_id', $allowedBranchIds);
                        });
                    }
                    $sub->groupBy('lead_id');
                });
                break;

            case 'total_calls':
            case 'total_dials':
            default:
                $metricTitle = 'Total Dials (All Call Attempts)';
                break;
        }

        $perPage = min((int) ($request->input('per_page', 300)), 500);
        $paginator = $query->paginate($perPage);

        $mappedRows = collect($paginator->items())->map(function ($c) use ($nextFollowupDateCol, $nextFollowupTimeCol) {
            $lead = $c->lead;
            $leadId = $lead ? 'LD-' . str_pad($lead->id, 4, '0', STR_PAD_LEFT) : 'N/A';
            $companyName = $lead?->company_name ?: ($lead?->contact_name ?: 'Unknown Lead');
            $contactName = $lead?->contact_name ?: '—';
            $mobileNumber = $lead?->mobile_number ?: '—';
            $leadUrl = $lead ? url('/leads/' . $lead->id) : '';

            $callerName = $c->user?->name
                ?: ($c->user_id ? \App\Models\User::withoutGlobalScopes()->withTrashed()->where('id', $c->user_id)->value('name') : null)
                ?: ($lead?->assignedTo?->name ?: '—');
            if (empty($callerName) || trim($callerName) === '') {
                $callerName = '—';
            }

            $calledTime = $c->called_at ? Carbon::parse($c->called_at)->format('h:i A') : '—';
            $callType = $c->call_type_label ?? ucfirst((string) ($c->call_type ?: 'outgoing'));
            $outcome = $c->outcome_label ?? ($c->outCome?->name ?? (is_string($c->outcome) ? ucfirst($c->outcome) : 'N/A'));
            $outcomeSubcategory = $c->outcome_subcategory_label ?? ($c->outComeSubCategory?->name ?? '—');
            $outcomeColor = is_array($c->outcome_color) ? ($c->outcome_color['text'] ?? null) : ($c->outcome_color ?: null);

            $dateVal = $nextFollowupDateCol ? $c->{$nextFollowupDateCol} : ($c->next_follow_up ?? null);
            $timeVal = $nextFollowupTimeCol ? $c->{$nextFollowupTimeCol} : ($c->followup_time ?? null);
            $nextFollowup = $dateVal ? (Carbon::parse($dateVal)->format('d M Y') . ($timeVal ? ' (' . $timeVal . ')' : '')) : '—';

            $notes = $c->notes ?: ($c->remarks ?: '—');

            return [
                'id' => $c->id,
                'lead_id' => $leadId,
                'lead_url' => $leadUrl,
                'company_name' => $companyName,
                'contact_name' => $contactName,
                'mobile_number' => $mobileNumber,
                'caller_name' => $callerName,
                'called_time' => $calledTime,
                'call_type' => $callType,
                'outcome' => $outcome,
                'outcome_subcategory' => $outcomeSubcategory,
                'outcome_color' => $outcomeColor,
                'next_follow_up' => $nextFollowup,
                'notes' => $notes,
            ];
        });

        return response()->json([
            'success' => true,
            'metric' => $metric,
            'title' => $metricTitle,
            'date' => $dateLabel,
            'total' => $paginator->total(),
            'rows' => $mappedRows,
            'data' => $mappedRows,
        ]);
    }

    /**
     * Mobile API: Store Sales Day Closing.
     */
    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();
        $isCompanyAdmin = $user->isSuperAdmin() || $user->isCompanyAdminRole() || $user->isCbo();
        $visibleUserIds = $this->resolveVisibleUserIds($user);

        $targetUserId = $user->id;
        if ($request->filled('user_id')) {
            $reqUserId = (int) $request->input('user_id');
            if ($visibleUserIds === null || in_array($reqUserId, $visibleUserIds, true)) {
                $targetUserId = $reqUserId;
            }
        }
        $targetUser = User::find($targetUserId) ?? $user;

        $validated = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'closing_date' => ['nullable', 'date'],
            'closing_notes' => ['nullable', 'string'],
            'is_on_leave_tomorrow' => ['nullable', 'boolean'],
            'tomorrow_plans' => ['nullable', 'array'],
            'tomorrow_plans.*.company_name' => ['nullable', 'string'],
            'tomorrow_plans.*.product_name' => ['nullable', 'string'],
            'tomorrow_plans.*.expected_value' => ['nullable', 'numeric'],
            'plan_for_tomorrow' => ['nullable', 'string'],
        ]);

        $date = isset($validated['closing_date']) ? Carbon::parse($validated['closing_date'])->toDateString() : now()->toDateString();

        $existing = SalesDailyClosing::where('user_id', $targetUserId)
            ->whereDate('closing_date', $date)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Day Closing has already been submitted for this user and date.',
            ], 422);
        }

        $allowedBranchIds = $this->resolveAllowedBranchIds($targetUser);
        $effectiveBranchId = $this->resolveEffectiveBranchId($targetUser, $targetUser->branch_id, $allowedBranchIds);

        $stats = $this->calculateCallStats($targetUserId, $date, $visibleUserIds, $effectiveBranchId, $allowedBranchIds);

        $cleanedPlans = $this->cleanTomorrowPlans($validated['tomorrow_plans'] ?? []);
        $totalExpected = array_sum(array_column($cleanedPlans, 'expected_value'));

        $closing = SalesDailyClosing::create([
            'company_id'           => $targetUser->company_id,
            'branch_id'            => $targetUser->branch_id,
            'user_id'              => $targetUserId,
            'closing_date'         => $date,
            'total_calls'          => $stats['total_calls'],
            'unique_calls'         => $stats['unique_calls'],
            'new_calls'            => $stats['new_calls'],
            'followup_calls'       => $stats['followup_calls'],
            'onetime_calls'        => $stats['onetime_calls'],
            'converted_count'      => $stats['converted_count'],
            'quotations_count'     => $stats['quotations_count'],
            'closing_notes'        => $validated['closing_notes'] ?? null,
            'is_on_leave_tomorrow' => (bool) ($validated['is_on_leave_tomorrow'] ?? false),
            'tomorrow_plans'       => $cleanedPlans,
            'plan_for_tomorrow'    => $validated['plan_for_tomorrow'] ?? null,
            'status'               => 'submitted',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Day closing submitted successfully.',
            'data' => [
                'id' => $closing->id,
                'status' => $closing->status,
                'closing_date' => $date,
            ],
        ], 201);
    }

    /**
     * Mobile API: Update Sales Day Closing.
     */
    public function update(Request $request, SalesDailyClosing $closing): JsonResponse
    {
        $user = auth()->user();
        $visibleUserIds = $this->resolveVisibleUserIds($user);

        if ($closing->user_id !== $user->id && $visibleUserIds !== null && !in_array($closing->user_id, $visibleUserIds, true)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'closing_notes' => ['nullable', 'string'],
            'is_on_leave_tomorrow' => ['nullable', 'boolean'],
            'tomorrow_plans' => ['nullable', 'array'],
            'plan_for_tomorrow' => ['nullable', 'string'],
        ]);

        $cleanedPlans = isset($validated['tomorrow_plans']) ? $this->cleanTomorrowPlans($validated['tomorrow_plans']) : $closing->tomorrow_plans;

        $closing->update([
            'closing_notes' => $validated['closing_notes'] ?? $closing->closing_notes,
            'is_on_leave_tomorrow' => isset($validated['is_on_leave_tomorrow']) ? (bool) $validated['is_on_leave_tomorrow'] : $closing->is_on_leave_tomorrow,
            'tomorrow_plans' => $cleanedPlans,
            'plan_for_tomorrow' => $validated['plan_for_tomorrow'] ?? $closing->plan_for_tomorrow,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Day closing updated successfully.',
            'data' => [
                'id' => $closing->id,
                'status' => $closing->status,
                'closing_date' => $closing->closing_date ? Carbon::parse($closing->closing_date)->toDateString() : '',
            ],
        ]);
    }

    /**
     * Mobile API: Review/Approve Sales Day Closing status.
     */
    public function updateStatus(Request $request, SalesDailyClosing $closing): JsonResponse
    {
        $user = auth()->user();
        $isCompanyAdmin = $user->isSuperAdmin() || $user->isCompanyAdminRole() || $user->isCbo();
        $isBranchManager = !$isCompanyAdmin && $user->isBranchManager();
        $isBranchAdmin   = !$isCompanyAdmin && !$isBranchManager && $user->isBranchAdmin();
        $isTl            = !$isCompanyAdmin && !$isBranchManager && !$isBranchAdmin && $user->hasTlLikeRole();

        if (!$isCompanyAdmin && !$isBranchManager && !$isBranchAdmin && !$isTl) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $visibleUserIds = $this->resolveVisibleUserIds($user);
        if ($closing->user_id !== $user->id && $visibleUserIds !== null && !in_array($closing->user_id, $visibleUserIds, true)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:submitted,reviewed,approved'],
        ]);

        $closing->update([
            'status' => $validated['status'],
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Closing status updated successfully.',
            'status' => $closing->status,
        ]);
    }

    /**
     * Mobile API: Delete Sales Day Closing.
     */
    public function destroy(SalesDailyClosing $closing): JsonResponse
    {
        $user = auth()->user();
        $visibleUserIds = $this->resolveVisibleUserIds($user);

        if ($closing->user_id !== $user->id && $visibleUserIds !== null && !in_array($closing->user_id, $visibleUserIds, true)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $closing->delete();

        return response()->json(['success' => true, 'message' => 'Record deleted successfully.']);
    }

    /**
     * Core calculation logic for Call Metrics and Sales KPIs.
     * Mirrors web SalesDailyClosingController::calculateCallStats.
     */
    private function calculateCallStats(
        ?int $userId,
        string|array $dateRange,
        ?array $allowedUserIds = null,
        ?int $branchId = null,
        ?array $allowedBranchIds = null
    ): array {
        $applyDate = function($q, $col = 'called_at') use ($dateRange) {
            if (is_array($dateRange) && count($dateRange) === 2 && !empty($dateRange[0]) && !empty($dateRange[1])) {
                $q->whereBetween($col, [
                    Carbon::parse($dateRange[0])->startOfDay(),
                    Carbon::parse($dateRange[1])->endOfDay(),
                ]);
            } elseif (is_string($dateRange) && !empty($dateRange)) {
                $q->whereDate($col, Carbon::parse($dateRange)->toDateString());
            }
        };

        $applyUser = function($q, $col = 'user_id') use ($userId, $allowedUserIds) {
            if ($userId) {
                $q->where($col, $userId);
            } elseif ($allowedUserIds !== null) {
                $q->whereIn($col, $allowedUserIds);
            }
        };

        $applyBranch = function($q, $relation = 'lead') use ($branchId, $allowedBranchIds) {
            if ($branchId) {
                if ($relation) {
                    $q->whereHas($relation, fn($lq) => $lq->where('branch_id', $branchId));
                } else {
                    $q->where('branch_id', $branchId);
                }
            } elseif (!empty($allowedBranchIds)) {
                if ($relation) {
                    $q->whereHas($relation, fn($lq) => $lq->whereIn('branch_id', $allowedBranchIds));
                } else {
                    $q->whereIn('branch_id', $allowedBranchIds);
                }
            }
        };

        // 1. Total Calls Made
        $totalCallsQuery = LeadCallUpdate::query();
        $applyDate($totalCallsQuery, 'called_at');
        $applyUser($totalCallsQuery, 'user_id');
        $applyBranch($totalCallsQuery, 'lead');
        $totalCalls = (int) $totalCallsQuery->count();

        // 2. Unique Calls
        $uniqueCallsQuery = LeadCallUpdate::query();
        $applyDate($uniqueCallsQuery, 'called_at');
        $applyUser($uniqueCallsQuery, 'user_id');
        $applyBranch($uniqueCallsQuery, 'lead');
        $uniqueCalls = (int) $uniqueCallsQuery->distinct('lead_id')->count('lead_id');

        // 3. Total New Calls
        $refDate = is_array($dateRange) ? $dateRange[0] : $dateRange;
        $refDateStr = Carbon::parse($refDate)->toDateString();
        $newCallsQuery = LeadCallUpdate::query();
        $applyDate($newCallsQuery, 'called_at');
        $applyUser($newCallsQuery, 'user_id');
        $applyBranch($newCallsQuery, 'lead');
        $newCallsQuery->whereNotExists(function ($q) use ($refDateStr) {
            $q->select(DB::raw(1))
                ->from('lead_call_updates as prev')
                ->whereColumn('prev.lead_id', 'lead_call_updates.lead_id')
                ->whereDate('prev.called_at', '<', $refDateStr);
        });
        $newCalls = (int) $newCallsQuery->distinct('lead_id')->count('lead_id');

        // 4. Followup Calls
        $followupCallsQuery = LeadCallUpdate::query();
        $applyDate($followupCallsQuery, 'called_at');
        $applyUser($followupCallsQuery, 'user_id');
        $applyBranch($followupCallsQuery, 'lead');
        $followupCallsQuery->whereExists(function ($q) use ($refDateStr) {
            $q->select(DB::raw(1))
                ->from('lead_call_updates as prev')
                ->whereColumn('prev.lead_id', 'lead_call_updates.lead_id')
                ->whereDate('prev.called_at', '<', $refDateStr);
        });
        $followupCalls = (int) $followupCallsQuery->distinct('lead_id')->count('lead_id');

        // 5. One-time Calls
        $nextFollowupDateCol = Schema::hasColumn('lead_call_updates', 'next_follow_up')
            ? 'next_follow_up'
            : (Schema::hasColumn('lead_call_updates', 'next_followup_date') ? 'next_followup_date' : null);

        $onetimeCallsQuery = LeadCallUpdate::query();
        $applyDate($onetimeCallsQuery, 'called_at');
        $applyUser($onetimeCallsQuery, 'user_id');
        $applyBranch($onetimeCallsQuery, 'lead');
        $onetimeCallsQuery->where(function ($q) use ($nextFollowupDateCol) {
            if ($nextFollowupDateCol) {
                $q->whereNull($nextFollowupDateCol);
            } else {
                $q->whereNull('next_follow_up');
            }
            $q->orWhere('outcome', '9')
              ->orWhere('outcome', '6')
              ->orWhere('outcome', 'not_interested')
              ->orWhere('outcome', 'closed');
        });
        $onetimeCalls = (int) $onetimeCallsQuery->distinct('lead_id')->count('lead_id');

        // 6. Converted Leads
        $convertedStatusIds = LeadStatus::whereRaw('LOWER(name) in (?, ?)', ['converted', 'won'])->pluck('id')->toArray();
        if (empty($convertedStatusIds)) {
            $convertedStatusIds = [5, 15];
        }

        $convertedQuery = Lead::query();
        $applyDate($convertedQuery, 'updated_at');
        $applyUser($convertedQuery, 'assigned_to');
        $applyBranch($convertedQuery, null);
        $convertedQuery->where(function ($q) use ($convertedStatusIds) {
            $q->whereIn('lead_status', ['won', 'converted'])
              ->orWhereIn('lead_status_id', $convertedStatusIds);
        });
        $convertedCount = (int) $convertedQuery->count();

        // 7. Quotations created
        $quotationsQuery = Quotation::query();
        $applyDate($quotationsQuery, 'created_at');
        $applyUser($quotationsQuery, 'created_by');
        $applyBranch($quotationsQuery, 'lead');
        $quotationsCount = (int) $quotationsQuery->count();

        return [
            'total_calls'              => $totalCalls,
            'total_calls_made'         => $totalCalls,
            'unique_calls'             => $uniqueCalls,
            'new_calls'                => $newCalls,
            'followup_calls'           => $followupCalls,
            'onetime_calls'            => $onetimeCalls,
            'all_one_time_calls'       => $onetimeCalls,
            'converted_count'          => $convertedCount,
            'converted_leads_count'    => $convertedCount,
            'quotations_count'         => $quotationsCount,
            'quotations_created_count' => $quotationsCount,
        ];
    }

    /**
     * Resolve visible user IDs based on exact role rules:
     * - Company Admin / Super Admin / CBO: null (all company data)
     * - Branch Manager: all users mapped under them (descendants) + users in additional branches + self
     * - Branch Admin: all users in their branches
     * - Sales TL: all users mapped under them (descendants) + self
     * - Sales Executive / Other: only own data [self]
     */
    private function resolveVisibleUserIds(?User $user = null): ?array
    {
        $user ??= auth()->user();
        if (!$user) {
            return [];
        }

        // 1. Company-wide users (Super Admin, Company Admin, CBO)
        if ($user->isSuperAdmin() || $user->isCompanyAdminRole() || $user->isCbo()) {
            return null;
        }

        // 2. Branch Manager: Users in additional branches + users mapped under them in HO + self
        if ($user->isBranchManager()) {
            $defaultBranchIds = Branch::where('is_default', true)->pluck('id')->toArray();
            if (empty($defaultBranchIds)) {
                $defaultBranchIds = [1];
            }

            $allBranchIds = $user->getMyBranchIds();
            $additionalBranchIds = array_values(array_filter($allBranchIds, fn($id) => !in_array((int)$id, $defaultBranchIds)));

            $descendants = $this->visibility->descendantUserIds($user);
            $hoDescendantIds = [];
            if ($descendants->isNotEmpty()) {
                $hoDescendantIds = User::withoutGlobalScope('branch')
                    ->whereIn('id', $descendants)
                    ->whereIn('branch_id', $defaultBranchIds)
                    ->pluck('id')
                    ->all();
            }

            return User::withoutGlobalScope('branch')
                ->where('is_active', true)
                ->where(function ($query) use ($additionalBranchIds, $hoDescendantIds, $user) {
                    $query->where('id', $user->id);

                    if (!empty($additionalBranchIds)) {
                        $query->orWhereIn('branch_id', $additionalBranchIds)
                              ->orWhereExists(function ($sub) use ($additionalBranchIds) {
                                  $sub->select(DB::raw(1))
                                      ->from('branch_user')
                                      ->whereColumn('branch_user.user_id', 'users.id')
                                      ->whereIn('branch_user.branch_id', $additionalBranchIds);
                              });
                    }

                    if (!empty($hoDescendantIds)) {
                        $query->orWhereIn('id', $hoDescendantIds);
                    }
                })
                ->pluck('id')
                ->push($user->id)
                ->unique()
                ->values()
                ->all();
        }

        // 3. Branch Admin: Users in their branches
        if ($user->isBranchAdmin()) {
            $branchIds = $user->getMyBranchIds();
            try {
                return User::withoutGlobalScope('branch')
                    ->where('is_active', true)
                    ->when(!empty($branchIds), fn ($query) => $query->inBranches($branchIds))
                    ->when(empty($branchIds) && $user->branch_id, fn ($query) => $query->inBranches([(int) $user->branch_id]))
                    ->pluck('id')
                    ->push($user->id)
                    ->unique()
                    ->values()
                    ->all();
            } catch (\Throwable $e) {
                return [$user->id];
            }
        }

        // 4. Sales TL: Users mapped under them (descendants) + self
        if ($user->hasTlLikeRole()) {
            $descendants = $this->visibility->descendantUserIds($user);
            return $descendants->push($user->id)->unique()->values()->all();
        }

        // 5. Sales Executive / Other: Only own data
        return [$user->id];
    }

    /**
     * Resolve allowed branch IDs for user:
     * - Company Admin / Super Admin / CBO: null (all branches allowed)
     * - Branch Admin / Branch Manager: their assigned branches (getMyBranchIds)
     * - Sales TL / Executive / Other: user's primary branch [branch_id] or getMyBranchIds
     */
    private function resolveAllowedBranchIds(?User $user = null): ?array
    {
        $user ??= auth()->user();
        if (!$user) {
            return [];
        }

        if ($user->isSuperAdmin() || $user->isCompanyAdminRole() || $user->isCbo()) {
            return null; // Company-wide: all branches allowed
        }

        if ($user->isBranchAdmin() || $user->isBranchManager()) {
            $branchIds = $user->getMyBranchIds();
            if (empty($branchIds) && $user->branch_id) {
                $branchIds = [(int) $user->branch_id];
            }
            return !empty($branchIds) ? array_values(array_map('intval', $branchIds)) : [];
        }

        $singleBranchId = $user->branch_id ? [(int) $user->branch_id] : array_values(array_map('intval', $user->getMyBranchIds()));
        return !empty($singleBranchId) ? $singleBranchId : [];
    }

    /**
     * Resolve effective branch ID to filter by.
     */
    private function resolveEffectiveBranchId(?User $user, mixed $requestedBranchId, ?array $allowedBranchIds): ?int
    {
        $user ??= auth()->user();
        $isCompanyAdmin = $user && ($user->isSuperAdmin() || $user->isCompanyAdminRole() || $user->isCbo());

        if (!empty($requestedBranchId) && (int) $requestedBranchId > 0) {
            $branchId = (int) $requestedBranchId;
            if ($isCompanyAdmin) {
                return $branchId;
            }
            if ($allowedBranchIds !== null && in_array($branchId, $allowedBranchIds, true)) {
                return $branchId;
            }
            return !empty($allowedBranchIds) ? $allowedBranchIds[0] : null;
        }

        if ($isCompanyAdmin) {
            return null;
        }

        if (!empty($allowedBranchIds) && count($allowedBranchIds) === 1) {
            return $allowedBranchIds[0];
        }

        return null;
    }

    private function cleanTomorrowPlans(array $rawPlans): array
    {
        $cleanedPlans = [];
        foreach ($rawPlans as $p) {
            if (!is_array($p)) continue;
            $company = trim((string)($p['company_name'] ?? ''));
            $product = trim((string)($p['product_name'] ?? ''));
            $val = floatval($p['expected_value'] ?? 0);
            if ($company !== '' || $product !== '' || $val > 0) {
                $cleanedPlans[] = [
                    'company_name' => $company,
                    'product_name' => $product,
                    'expected_value' => $val,
                ];
            }
        }
        return $cleanedPlans;
    }
}
