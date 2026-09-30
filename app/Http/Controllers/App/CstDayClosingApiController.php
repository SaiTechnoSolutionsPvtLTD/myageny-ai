<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CstDailyClosing;
use App\Models\Lead;
use App\Models\LeadProduct;
use App\Models\LeadProductPayment;
use App\Models\LeadReminder;
use App\Models\ProductionInitiation;
use App\Models\SalesTarget;
use App\Models\User;
use App\Services\DataVisibilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CstDayClosingApiController extends Controller
{
    public function __construct(private readonly DataVisibilityService $visibility) {}

    /**
     * Determine if the user has full company-wide visibility for CST (Company Admin, CBO, Super Admin).
     */
    private function canViewAllCst(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->isCompanyAdmin()
            || $user->isCompanyAdminRole()
            || $user->isCbo()
            || collect($user->roleKeys()->all())->intersect([
                'super_admin',
                'company_admin',
                'cbo',
                'chief_business_officer',
                'cheif_business_officer',
            ])->isNotEmpty();
    }

    /**
     * Get Customer Support Team user IDs.
     */
    private function getSupportUserIds(?User $user = null): array
    {
        $user = $user ?? auth()->user();
        $visibleUserIds = $user ? $this->visibility->visibleUserIds() : null;

        $supportUserIds = User::where(function($query) {
            $query->whereHas('roles.department', function($q) {
                $q->where('name', 'like', '%customer support%')
                  ->orWhere('name', 'like', '%customer success%')
                  ->orWhere('id', 5);
            })->orWhereHas('employeeOnboarding', function($q) {
                $q->where('department_id', 5);
            })->orWhere(function($sub) {
                $sub->whereHas('roles', function($rq) {
                    $rq->where('name', 'like', '%cst%')
                      ->orWhere('name', 'like', '%support%')
                      ->orWhere('name', 'like', '%success%');
                });
            });
        })->pluck('id')->toArray();

        $allocatedUserIds = Lead::whereNotNull('customer_support_executive_id')
            ->distinct()
            ->pluck('customer_support_executive_id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->all();

        $allSupportIds = array_values(array_unique(array_merge($supportUserIds, $allocatedUserIds)));

        if ($visibleUserIds !== null) {
            return array_values(array_intersect($visibleUserIds, $allSupportIds));
        }

        return $allSupportIds;
    }

    /**
     * Extract renewal date string from ProductionInitiation custom_form_data or project_delivery_date.
     */
    private function extractRenewalDate($pi): ?string
    {
        $formData = is_array($pi->custom_form_data) ? $pi->custom_form_data : json_decode($pi->custom_form_data ?? '[]', true) ?? [];

        foreach ($formData as $field) {
            if (!is_array($field)) continue;
            $fieldKey = $field['field_name'] ?? '';
            $fieldVal = $field['value'] ?? '';

            $key = strtolower(is_array($fieldKey) ? implode(' ', array_filter(array_map('strval', $fieldKey))) : trim((string) $fieldKey));
            if (is_array($fieldVal)) {
                $value = implode(', ', array_filter(array_map(fn($v) => is_array($v) ? json_encode($v) : (string)$v, $fieldVal)));
            } else {
                $value = trim((string) $fieldVal);
            }
            if (in_array($key, ['end_date', 'enddate', 'end date', 'smm_end_date', 'End Date', 'ovp_end_date']) && !empty($value)) {
                try {
                    return Carbon::parse($value)->toDateString();
                } catch (\Exception $e) {}
            }
        }

        return $pi->project_delivery_date ? Carbon::parse($pi->project_delivery_date)->toDateString() : null;
    }

    /**
     * Calculate CST Department stats for a specific user, branch, and date/date range.
     */
    public function calculateCstStats(?int $userId, string|array $dateRange, ?array $allowedUserIds = null, ?int $branchId = null): array
    {
        $currentUser = auth()->user();
        $supportUserIds = $allowedUserIds !== null ? $allowedUserIds : $this->getSupportUserIds($currentUser);

        $refDateStr = is_array($dateRange) ? ($dateRange[1] ?? $dateRange[0]) : $dateRange;
        $parsedDate = Carbon::parse($refDateStr);
        $monthStart = (clone $parsedDate)->startOfMonth();
        $monthEnd   = (clone $parsedDate)->endOfMonth();
        $weekStart  = (clone $parsedDate)->startOfWeek();
        $weekEnd    = (clone $parsedDate)->endOfWeek();

        // 1. Monthly Target
        $prospectQuery = LeadProduct::query()
            ->whereRaw('LOWER(product_status) = ?', ['hot'])
            ->whereMonth('closure_date', $monthStart->month)
            ->whereYear('closure_date', $monthStart->year);

        if ($userId) {
            $prospectQuery->whereHas('lead', function($lq) use ($userId) {
                $lq->where('customer_support_executive_id', $userId)
                   ->orWhere('customer_support_tl_id', $userId);
            });
        } elseif (!empty($supportUserIds)) {
            $prospectQuery->whereHas('lead', function($lq) use ($supportUserIds) {
                $lq->whereIn('customer_support_executive_id', $supportUserIds)
                   ->orWhereIn('customer_support_tl_id', $supportUserIds);
            });
        }
        $prospectVal = (float) $prospectQuery->sum('total_price');

        $renewalQuery = ProductionInitiation::query()
            ->join('leads', 'leads.id', '=', 'production_initiations.lead_id')
            ->join('lead_products', 'lead_products.id', '=', 'production_initiations.lead_product_id')
            ->join('products', 'products.id', '=', 'production_initiations.product_id')
            ->where(function($q) {
                $q->where('products.is_this_renewal_product', true)
                  ->orWhere('products.count_wise_report', true);
            });

        if ($userId) {
            $renewalQuery->where(function($q) use ($userId) {
                $q->where('leads.customer_support_executive_id', $userId)
                  ->orWhere('leads.customer_support_tl_id', $userId);
            });
        } elseif (!empty($supportUserIds)) {
            $renewalQuery->where(function($q) use ($supportUserIds) {
                $q->whereIn('leads.customer_support_executive_id', $supportUserIds)
                  ->orWhereIn('leads.customer_support_tl_id', $supportUserIds);
            });
        }

        $renewalInitiations = $renewalQuery->select([
            'production_initiations.id',
            'production_initiations.project_delivery_date',
            'production_initiations.custom_form_data',
            'lead_products.total_price',
            'lead_products.closure_date',
        ])->get();

        $renewalVal = 0.0;
        foreach ($renewalInitiations as $ri) {
            $rDateStr = $this->extractRenewalDate($ri);
            if ($rDateStr) {
                $rDate = Carbon::parse($rDateStr);
                if ($rDate->month === $monthStart->month && $rDate->year === $monthStart->year) {
                    $renewalVal += (float) $ri->total_price;
                }
            } elseif ($ri->closure_date) {
                $cDate = Carbon::parse($ri->closure_date);
                if ($cDate->month === $monthStart->month && $cDate->year === $monthStart->year) {
                    $renewalVal += (float) $ri->total_price;
                }
            }
        }

        $directRenewalQuery = LeadProduct::query()
            ->whereMonth('closure_date', $monthStart->month)
            ->whereYear('closure_date', $monthStart->year)
            ->whereHas('product', fn($q) => $q->where('is_this_renewal_product', true))
            ->whereDoesntHave('productionInitiations');

        if ($userId) {
            $directRenewalQuery->whereHas('lead', function($lq) use ($userId) {
                $lq->where('customer_support_executive_id', $userId)
                   ->orWhere('customer_support_tl_id', $userId);
            });
        } elseif (!empty($supportUserIds)) {
            $directRenewalQuery->whereHas('lead', function($lq) use ($supportUserIds) {
                $lq->whereIn('customer_support_executive_id', $supportUserIds)
                   ->orWhereIn('customer_support_tl_id', $supportUserIds);
            });
        }
        $renewalVal += (float) $directRenewalQuery->sum('total_price');

        $devQuery = ProductionInitiation::query()
            ->join('leads', 'leads.id', '=', 'production_initiations.lead_id')
            ->join('lead_products', 'lead_products.id', '=', 'production_initiations.lead_product_id')
            ->where('production_initiations.department_id', 1);

        if ($userId) {
            $devQuery->where(function($q) use ($userId) {
                $q->where('leads.customer_support_executive_id', $userId)
                  ->orWhere('leads.customer_support_tl_id', $userId);
            });
        } elseif (!empty($supportUserIds)) {
            $devQuery->where(function($q) use ($supportUserIds) {
                $q->whereIn('leads.customer_support_executive_id', $supportUserIds)
                  ->orWhereIn('leads.customer_support_tl_id', $supportUserIds);
            });
        }

        $devInitiations = $devQuery->select([
            'lead_products.total_price',
            'lead_products.amount_paid',
        ])->get();

        $devVal = 0.0;
        foreach ($devInitiations as $di) {
            $tPrice = (float) $di->total_price;
            $aPaid = (float) $di->amount_paid;
            if ($tPrice > 0 && ($aPaid / $tPrice) >= 0.60) {
                $pending = max(0, $tPrice - $aPaid);
                $devVal += ($pending > 0 ? $pending : $tPrice);
            }
        }

        $computedMonthlyTarget = $prospectVal + $renewalVal + $devVal;

        if ($computedMonthlyTarget <= 0 && $userId) {
            $targetMonthStr = $monthStart->format('Y-m');
            $explicitTarget = (float) (SalesTarget::where('user_id', $userId)->where('target_month', $targetMonthStr)->value('target_amount')
                ?? SalesTarget::where('user_id', $userId)->value('target_amount')
                ?? 0);
            $monthlyTarget = $explicitTarget;
        } else {
            $monthlyTarget = $computedMonthlyTarget;
        }

        // 2. Today's Revenue
        $todayPaymentQuery = LeadProductPayment::query();
        if (is_array($dateRange) && count($dateRange) === 2 && !empty($dateRange[0]) && !empty($dateRange[1])) {
            $todayPaymentQuery->whereBetween('payment_date', [$dateRange[0], $dateRange[1]]);
        } else {
            $todayPaymentQuery->whereDate('payment_date', $refDateStr);
        }

        if ($branchId) {
            $todayPaymentQuery->whereHas('lead', fn($lq) => $lq->where('branch_id', $branchId));
        }

        if ($userId) {
            $todayPaymentQuery->where(function($q) use ($userId) {
                $q->where('recorded_by', $userId)
                  ->orWhereHas('lead', fn($lq) => $lq->where('customer_support_executive_id', $userId)->orWhere('customer_support_tl_id', $userId));
            });
        } elseif (!empty($supportUserIds)) {
            $todayPaymentQuery->where(function($q) use ($supportUserIds) {
                $q->whereIn('recorded_by', $supportUserIds)
                  ->orWhereHas('lead', fn($lq) => $lq->whereIn('customer_support_executive_id', $supportUserIds)->orWhereIn('customer_support_tl_id', $supportUserIds));
            });
        }
        $todayRevenue = (float) $todayPaymentQuery->sum('amount');

        // 3. Till Now Achieved MTD
        $achievedPaymentQuery = LeadProductPayment::query()
            ->whereDate('payment_date', '>=', $monthStart->toDateString())
            ->whereDate('payment_date', '<=', $refDateStr);

        if ($branchId) {
            $achievedPaymentQuery->whereHas('lead', fn($lq) => $lq->where('branch_id', $branchId));
        }

        if ($userId) {
            $achievedPaymentQuery->where(function($q) use ($userId) {
                $q->where('recorded_by', $userId)
                  ->orWhereHas('lead', fn($lq) => $lq->where('customer_support_executive_id', $userId)->orWhere('customer_support_tl_id', $userId));
            });
        } elseif (!empty($supportUserIds)) {
            $achievedPaymentQuery->where(function($q) use ($supportUserIds) {
                $q->whereIn('recorded_by', $supportUserIds)
                  ->orWhereHas('lead', fn($lq) => $lq->whereIn('customer_support_executive_id', $supportUserIds)->orWhereIn('customer_support_tl_id', $supportUserIds));
            });
        }
        $tillNowAchieved = (float) $achievedPaymentQuery->sum('amount');

        // 4. Completed Percentage
        $completedPercentage = $monthlyTarget > 0 ? round(($tillNowAchieved / $monthlyTarget) * 100, 1) : 0.0;

        // 5. Current Week Meetings
        $meetingsQuery = LeadReminder::query()
            ->where(function($q) {
                $q->where('type', 'meeting')
                  ->orWhere('title', 'like', '%meeting%');
            })
            ->whereBetween('remind_at', [$weekStart->startOfDay(), $weekEnd->endOfDay()]);

        if ($userId) {
            $meetingsQuery->where('user_id', $userId);
        } elseif (!empty($supportUserIds)) {
            $meetingsQuery->whereIn('user_id', $supportUserIds);
        }
        $remindersMeetingCount = (int) $meetingsQuery->count();

        $cstUpdatesQuery = \App\Models\LeadCstUpdate::query()
            ->whereBetween('created_at', [$weekStart->startOfDay(), $weekEnd->endOfDay()]);

        if ($userId) {
            $cstUpdatesQuery->where('user_id', $userId);
        } elseif (!empty($supportUserIds)) {
            $cstUpdatesQuery->whereIn('user_id', $supportUserIds);
        }

        $weeklyUpdatesCount = (clone $cstUpdatesQuery)->where('update_type', 'weekly_update')->count();
        $reviewsCount       = (clone $cstUpdatesQuery)->where('update_type', 'review')->count();
        $escalationsCount   = (clone $cstUpdatesQuery)->where('update_type', 'escalation')->count();

        $currentWeekMeetings = $remindersMeetingCount + $weeklyUpdatesCount + $reviewsCount + $escalationsCount;

        // 6. Total Allocated Accounts
        $allocQuery = Lead::query()
            ->whereNull('deleted_at')
            ->whereHas('products', function($pq) {
                $pq->where('product_status', '=', 'converted');
            });

        if ($userId) {
            $allocQuery->where('customer_support_executive_id', $userId);
        } elseif (!empty($supportUserIds)) {
            $allocQuery->where(function($q) use ($supportUserIds) {
                $q->whereIn('customer_support_executive_id', $supportUserIds)
                  ->orWhereNotNull('customer_support_executive_id');
            });
        } else {
            $allocQuery->whereNotNull('customer_support_executive_id');
        }
        $totalAllocatedAccounts = (int) $allocQuery->distinct()->count('leads.id');

        // 7. Today Added Accounts
        $todayAddedQuery = Lead::query()
            ->whereNull('deleted_at')
            ->whereHas('products', function($pq) {
                $pq->where('product_status', '=', 'converted');
            })
            ->where(function($q) use ($dateRange, $refDateStr) {
                if (is_array($dateRange) && count($dateRange) === 2 && !empty($dateRange[0]) && !empty($dateRange[1])) {
                    $q->whereBetween('customer_support_allocated_at', [$dateRange[0] . ' 00:00:00', $dateRange[1] . ' 23:59:59'])
                      ->orWhere(function($sub) use ($dateRange) {
                          $sub->whereNull('customer_support_allocated_at')
                              ->whereBetween('created_at', [$dateRange[0] . ' 00:00:00', $dateRange[1] . ' 23:59:59']);
                      });
                } else {
                    $q->whereDate('customer_support_allocated_at', $refDateStr)
                      ->orWhere(function($sub) use ($refDateStr) {
                          $sub->whereNull('customer_support_allocated_at')
                              ->whereDate('created_at', $refDateStr);
                      });
                }
            });

        if ($userId) {
            $todayAddedQuery->where('customer_support_executive_id', $userId);
        } elseif (!empty($supportUserIds)) {
            $todayAddedQuery->where(function($q) use ($supportUserIds) {
                $q->whereIn('customer_support_executive_id', $supportUserIds)
                  ->orWhereNotNull('customer_support_executive_id');
            });
        } else {
            $todayAddedQuery->whereNotNull('customer_support_executive_id');
        }
        $todayAddedAccounts = (int) $todayAddedQuery->distinct()->count('leads.id');

        // 8. Welcome Call Pending Account Count
        $threeDaysAgo = Carbon::now()->subDays(3);

        $ovpWelcomeQuery = ProductionInitiation::query()
            ->whereIn('status', ['ovp_pending', 'initiated', 'pending']);

        if ($userId) {
            $ovpWelcomeQuery->where(function($q) use ($userId) {
                $q->where('ovp_allocated_to', $userId)
                  ->orWhere(function($sub) use ($userId) {
                      $sub->whereNull('ovp_allocated_to')
                          ->whereHas('lead', function($lq) use ($userId) {
                              $lq->where('customer_support_executive_id', $userId)
                                 ->orWhere('customer_support_tl_id', $userId);
                          });
                  });
            });
        } elseif (!empty($supportUserIds)) {
            $ovpWelcomeQuery->where(function($q) use ($supportUserIds) {
                $q->whereIn('ovp_allocated_to', $supportUserIds)
                  ->orWhere(function($sub) use ($supportUserIds) {
                      $sub->whereNull('ovp_allocated_to')
                          ->whereHas('lead', function($lq) use ($supportUserIds) {
                              $lq->whereIn('customer_support_executive_id', $supportUserIds)
                                 ->orWhereIn('customer_support_tl_id', $supportUserIds)
                                 ->orWhereNotNull('customer_support_executive_id');
                          });
                  });
            });
        }

        $ovpPendingCount = (clone $ovpWelcomeQuery)
            ->where('status', 'pending')
            ->distinct()->count('production_initiations.id');

        $ovpNewCount = (clone $ovpWelcomeQuery)
            ->whereIn('status', ['ovp_pending', 'initiated'])
            ->where('created_at', '>=', $threeDaysAgo)
            ->distinct()->count('production_initiations.id');

        $ovpOverdueCount = (clone $ovpWelcomeQuery)
            ->whereIn('status', ['ovp_pending', 'initiated'])
            ->where('created_at', '<', $threeDaysAgo)
            ->distinct()->count('production_initiations.id');

        $welcomeCallPendingCount = $ovpPendingCount + $ovpNewCount + $ovpOverdueCount;

        return [
            'monthly_target'                    => $monthlyTarget,
            'today_revenue'                     => $todayRevenue,
            'till_now_achieved'                 => $tillNowAchieved,
            'completed_percentage'              => $completedPercentage,
            'current_week_meetings'             => $currentWeekMeetings,
            'total_allocated_accounts'          => $totalAllocatedAccounts,
            'today_added_accounts'              => $todayAddedAccounts,
            'welcome_call_pending_count'        => $welcomeCallPendingCount,
            'welcome_call_pending_status_count' => $ovpPendingCount,
            'welcome_call_new_count'            => $ovpNewCount,
            'welcome_call_overdue_count'        => $ovpOverdueCount,
            'prospect_val'                      => $prospectVal,
            'renewal_val'                       => $renewalVal,
            'dev_val'                           => $devVal,
            'weekly_updates_count'              => $weeklyUpdatesCount,
            'reviews_count'                     => $reviewsCount,
            'escalations_count'                 => $escalationsCount,
            'meeting_reminders_count'           => $remindersMeetingCount,
        ];
    }

    /**
     * Mobile API: List CST Day Closings with filters and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $today = now()->toDateString();

        $fromDate = $request->input('from_date') ?: $request->input('date_from');
        $toDate = $request->input('to_date') ?: $request->input('date_to');
        $selectedDate = $toDate ?: ($fromDate ?: ($request->input('date') ?: $today));

        $canViewAll = $this->canViewAllCst($user);
        $visibleUserIds = $canViewAll ? null : $this->visibility->visibleUserIds();
        $supportUserIds = $this->getSupportUserIds($user);

        if ($canViewAll) {
            $assignableUsers = User::whereIn('id', $supportUserIds)->orderBy('name')->get(['id', 'name']);
            if ($assignableUsers->isEmpty()) {
                $assignableUsers = collect([$user]);
            }
            $branches = Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        } else {
            $assignableUsers = $this->visibility->visibleAssignableUsers($user);
            if ($assignableUsers->isEmpty()) {
                $assignableUsers = collect([$user]);
            }
            $branches = $this->visibility->visibleBranches($user);
            if ($branches->isEmpty() && $user->branch) {
                $branches = collect([$user->branch]);
            }
        }

        $query = CstDailyClosing::with([
            'user:id,name,email,branch_id,designation',
            'branch:id,name',
            'reviewer:id,name',
        ])->latest('closing_date')->latest('id');

        if (!$canViewAll) {
            if ($visibleUserIds !== null) {
                $query->whereIn('user_id', $visibleUserIds);
            } else {
                $query->where('user_id', $user->id);
            }
        }

        if ($request->filled('user_id') && (int) $request->input('user_id') > 0) {
            $filterUserId = (int) $request->input('user_id');
            if ($canViewAll || ($visibleUserIds !== null && in_array($filterUserId, $visibleUserIds, true))) {
                $query->where('user_id', $filterUserId);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if ($request->filled('branch_id') && (int) $request->input('branch_id') > 0) {
            $query->where('branch_id', (int) $request->input('branch_id'));
        }

        if ($request->filled('status') && !empty($request->input('status'))) {
            $query->where('status', $request->input('status'));
        }

        if ($fromDate && $toDate) {
            $query->whereBetween('closing_date', [$fromDate, $toDate]);
        } elseif ($fromDate) {
            $query->whereDate('closing_date', '>=', $fromDate);
        } elseif ($toDate) {
            $query->whereDate('closing_date', '<=', $toDate);
        } elseif ($request->filled('date')) {
            $query->whereDate('closing_date', $request->input('date'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('remarks', 'like', "%{$search}%")
                  ->orWhere('plan_for_tomorrow', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = min((int) ($request->input('per_page', 20)), 100);
        $paginator = $query->paginate($perPage);

        $targetUserId = $request->filled('user_id') && (int) $request->input('user_id') > 0
            ? (int) $request->input('user_id')
            : (!$canViewAll && $visibleUserIds !== null && count($visibleUserIds) === 1 ? $user->id : null);

        $branchIdFilter = $request->filled('branch_id') ? (int) $request->input('branch_id') : null;
        $dateRangeFilter = ($fromDate && $toDate) ? [$fromDate, $toDate] : ($fromDate ?: ($toDate ?: $selectedDate));

        $kpiStats = $this->calculateCstStats($targetUserId, $dateRangeFilter, $visibleUserIds, $branchIdFilter);

        $myTodayClosing = CstDailyClosing::where('user_id', $user->id)
            ->whereDate('closing_date', $today)
            ->first();

        return response()->json([
            'success'        => true,
            'can_create'     => true,
            'can_review'     => $canViewAll,
            'canViewAll'     => $canViewAll,
            'can_view_all'   => $canViewAll,
            'isAdminLike'    => $canViewAll,
            'selected_date'  => $selectedDate,
            'selectedDate'   => $selectedDate,
            'from_date'      => $fromDate,
            'to_date'        => $toDate,
            'today'          => $today,
            'kpi_stats'      => $kpiStats,
            'kpiStats'       => $kpiStats,
            'assignableUsers' => $assignableUsers->map(fn($u) => ['id' => $u->id, 'name' => $u->name]),
            'branches'       => $branches->map(fn($b) => ['id' => $b->id, 'name' => $b->name]),
            'myTodayClosing' => $myTodayClosing ? [
                'id' => $myTodayClosing->id,
                'closing_date' => $myTodayClosing->closing_date ? Carbon::parse($myTodayClosing->closing_date)->toDateString() : '',
                'remarks' => $myTodayClosing->remarks ?? '',
                'status' => $myTodayClosing->status ?? 'submitted',
            ] : null,
            'closings'       => [
                'data' => collect($paginator->items())->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'user_id' => $item->user_id,
                        'user_name' => $item->user?->name ?? 'Unknown',
                        'user_email' => $item->user?->email ?? '',
                        'designation' => $item->user?->designation ?? '',
                        'branch_name' => $item->branch?->name ?? '',
                        'closing_date' => $item->closing_date ? Carbon::parse($item->closing_date)->toDateString() : '',
                        'monthly_target' => (float) ($item->monthly_target ?? 0.0),
                        'today_revenue' => (float) ($item->today_revenue ?? 0.0),
                        'till_now_achieved' => (float) ($item->till_now_achieved ?? 0.0),
                        'completed_percentage' => (float) ($item->completed_percentage ?? 0.0),
                        'current_week_meetings' => (int) ($item->current_week_meetings ?? 0),
                        'total_allocated_accounts' => (int) ($item->total_allocated_accounts ?? 0),
                        'today_added_accounts' => (int) ($item->today_added_accounts ?? 0),
                        'welcome_call_pending_count' => (int) ($item->welcome_call_pending_count ?? 0),
                        'remarks' => $item->remarks ?? '',
                        'is_on_leave_tomorrow' => (bool) $item->is_on_leave_tomorrow,
                        'tomorrow_plans' => is_array($item->tomorrow_plans) ? $item->tomorrow_plans : [],
                        'plan_for_tomorrow' => $item->plan_for_tomorrow ?? '',
                        'attachments' => is_array($item->attachment_list) ? $item->attachment_list : [],
                        'status' => $item->status ?? 'submitted',
                        'reviewed_by_name' => $item->reviewer?->name,
                        'review_notes' => $item->review_notes ?? null,
                        'reviewed_at' => $item->reviewed_at ? Carbon::parse($item->reviewed_at)->toDateTimeString() : null,
                        'created_at' => $item->created_at ? $item->created_at->toDateTimeString() : null,
                    ];
                }),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Mobile API: Get CST Live Stats.
     */
    public function getStats(Request $request): JsonResponse
    {
        $user = auth()->user();
        $canViewAll = $this->canViewAllCst($user);
        $visibleUserIds = $canViewAll ? null : $this->visibility->visibleUserIds();

        $fromDate = $request->input('from_date') ?: $request->input('date_from');
        $toDate = $request->input('to_date') ?: $request->input('date_to');
        $closingDate = trim((string) $request->input('closing_date', $request->input('date', now()->toDateString())));
        $dateRangeFilter = ($fromDate && $toDate) ? [$fromDate, $toDate] : ($fromDate ?: ($toDate ?: $closingDate));

        $targetUserId = $request->filled('user_id') && (int) $request->input('user_id') > 0
            ? (int) $request->input('user_id')
            : (!$canViewAll && $visibleUserIds !== null && count($visibleUserIds) === 1 ? $user->id : null);

        $branchIdFilter = $request->filled('branch_id') ? (int) $request->input('branch_id') : null;

        $stats = $this->calculateCstStats($targetUserId, $dateRangeFilter, $visibleUserIds, $branchIdFilter);

        $lookupUserId = $targetUserId ?? $user->id;
        $lookupDate = is_array($dateRangeFilter) ? $dateRangeFilter[1] : $dateRangeFilter;
        $existing = CstDailyClosing::where('user_id', $lookupUserId)
            ->whereDate('closing_date', $lookupDate)
            ->first();

        return response()->json([
            'success'   => true,
            'date'      => is_array($dateRangeFilter) ? implode(' to ', $dateRangeFilter) : $dateRangeFilter,
            'kpi_stats' => $stats,
            'kpiStats'  => $stats,
            'exists'    => (bool) $existing,
            'closing'   => $existing ? [
                'id' => $existing->id,
                'monthly_target' => (float) $existing->monthly_target,
                'today_revenue' => (float) $existing->today_revenue,
                'till_now_achieved' => (float) $existing->till_now_achieved,
                'completed_percentage' => (float) $existing->completed_percentage,
                'current_week_meetings' => (int) $existing->current_week_meetings,
                'total_allocated_accounts' => (int) $existing->total_allocated_accounts,
                'today_added_accounts' => (int) $existing->today_added_accounts,
                'welcome_call_pending_count' => (int) $existing->welcome_call_pending_count,
                'remarks' => $existing->remarks,
                'is_on_leave_tomorrow' => (bool) $existing->is_on_leave_tomorrow,
                'tomorrow_plans' => is_array($existing->tomorrow_plans) ? $existing->tomorrow_plans : [],
                'plan_for_tomorrow' => $existing->plan_for_tomorrow,
                'status' => $existing->status,
                'attachments' => $existing->attachment_list,
            ] : null,
        ]);
    }

    /**
     * Mobile API: Fetch detailed records list for KPI card clicked on CST Day Closing page.
     */
    public function getMetricDetails(Request $request): JsonResponse
    {
        $user = auth()->user();
        $canViewAll = $this->canViewAllCst($user);
        $visibleUserIds = $canViewAll ? null : $this->visibility->visibleUserIds();
        $supportUserIds = $this->getSupportUserIds($user);

        $metric = trim((string) $request->input('metric', ''));
        $fromDate = $request->input('from_date') ?: $request->input('date_from');
        $toDate = $request->input('to_date') ?: $request->input('date_to');
        $refDateStr = trim((string) $request->input('closing_date', $request->input('date', now()->toDateString())));
        $dateRangeFilter = ($fromDate && $toDate) ? [$fromDate, $toDate] : ($fromDate ?: ($toDate ?: $refDateStr));

        $targetUserId = $request->filled('user_id') && (int) $request->input('user_id') > 0
            ? (int) $request->input('user_id')
            : (!$canViewAll && $visibleUserIds !== null && count($visibleUserIds) === 1 ? $user->id : null);

        $branchId = $request->filled('branch_id') ? (int) $request->input('branch_id') : null;

        $parsedDate = Carbon::parse(is_array($dateRangeFilter) ? $dateRangeFilter[1] : $dateRangeFilter);
        $monthStart = (clone $parsedDate)->startOfMonth();
        $weekStart  = (clone $parsedDate)->startOfWeek();
        $weekEnd    = (clone $parsedDate)->endOfWeek();

        $records = [];
        $title = 'Metric Details';

        switch ($metric) {
            case 'today_revenue':
                $title = "Today's Revenue Payment Collection";
                $q = LeadProductPayment::with(['lead:id,company_name,name,branch_id', 'lead.branch:id,name', 'leadProduct.product:id,name', 'recorder:id,name']);
                if (is_array($dateRangeFilter)) {
                    $q->whereBetween('payment_date', [$dateRangeFilter[0], $dateRangeFilter[1]]);
                } else {
                    $q->whereDate('payment_date', $refDateStr);
                }
                if ($branchId) {
                    $q->whereHas('lead', fn($lq) => $lq->where('branch_id', $branchId));
                }
                if ($targetUserId) {
                    $q->where(function($sub) use ($targetUserId) {
                        $sub->where('recorded_by', $targetUserId)
                            ->orWhereHas('lead', fn($lq) => $lq->where('customer_support_executive_id', $targetUserId)->orWhere('customer_support_tl_id', $targetUserId));
                    });
                } elseif (!empty($supportUserIds)) {
                    $q->where(function($sub) use ($supportUserIds) {
                        $sub->whereIn('recorded_by', $supportUserIds)
                            ->orWhereHas('lead', fn($lq) => $lq->whereIn('customer_support_executive_id', $supportUserIds)->orWhereIn('customer_support_tl_id', $supportUserIds));
                    });
                }
                $items = $q->latest('payment_date')->latest('id')->get();
                foreach ($items as $p) {
                    $records[] = [
                        'account_name' => $p->lead?->company_name ?: ($p->lead?->name ?: 'Lead #' . $p->lead_id),
                        'branch_name'  => $p->lead?->branch?->name ?: '—',
                        'product_name' => $p->leadProduct?->product?->name ?: '—',
                        'amount'       => (float) $p->amount,
                        'date'         => $p->payment_date ? Carbon::parse($p->payment_date)->format('d M Y') : '—',
                        'mode'         => ucfirst($p->payment_mode ?? 'online'),
                        'recorded_by'  => $p->recorder?->name ?: 'System',
                    ];
                }
                break;

            case 'till_now_achieved':
                $title = "Month-to-Date Achieved Revenue Collection";
                $q = LeadProductPayment::with(['lead:id,company_name,name,branch_id', 'lead.branch:id,name', 'leadProduct.product:id,name', 'recorder:id,name'])
                    ->whereDate('payment_date', '>=', $monthStart->toDateString())
                    ->whereDate('payment_date', '<=', $parsedDate->toDateString());
                if ($branchId) {
                    $q->whereHas('lead', fn($lq) => $lq->where('branch_id', $branchId));
                }
                if ($targetUserId) {
                    $q->where(function($sub) use ($targetUserId) {
                        $sub->where('recorded_by', $targetUserId)
                            ->orWhereHas('lead', fn($lq) => $lq->where('customer_support_executive_id', $targetUserId)->orWhere('customer_support_tl_id', $targetUserId));
                    });
                } elseif (!empty($supportUserIds)) {
                    $q->where(function($sub) use ($supportUserIds) {
                        $sub->whereIn('recorded_by', $supportUserIds)
                            ->orWhereHas('lead', fn($lq) => $lq->whereIn('customer_support_executive_id', $supportUserIds)->orWhereIn('customer_support_tl_id', $supportUserIds));
                    });
                }
                $items = $q->latest('payment_date')->get();
                foreach ($items as $p) {
                    $records[] = [
                        'account_name' => $p->lead?->company_name ?: ($p->lead?->name ?: 'Lead #' . $p->lead_id),
                        'branch_name'  => $p->lead?->branch?->name ?: '—',
                        'product_name' => $p->leadProduct?->product?->name ?: '—',
                        'amount'       => (float) $p->amount,
                        'date'         => $p->payment_date ? Carbon::parse($p->payment_date)->format('d M Y') : '—',
                        'mode'         => ucfirst($p->payment_mode ?? 'online'),
                        'recorded_by'  => $p->recorder?->name ?: 'System',
                    ];
                }
                break;

            case 'monthly_target':
                $title = "Monthly Target Breakdown";
                $hotProds = LeadProduct::with(['lead:id,company_name,name,branch_id', 'lead.branch:id,name', 'product:id,name'])
                    ->whereRaw('LOWER(product_status) = ?', ['hot'])
                    ->whereMonth('closure_date', $monthStart->month)
                    ->whereYear('closure_date', $monthStart->year);
                if ($targetUserId) {
                    $hotProds->whereHas('lead', fn($lq) => $lq->where('customer_support_executive_id', $targetUserId)->orWhere('customer_support_tl_id', $targetUserId));
                } elseif (!empty($supportUserIds)) {
                    $hotProds->whereHas('lead', fn($lq) => $lq->whereIn('customer_support_executive_id', $supportUserIds)->orWhereIn('customer_support_tl_id', $supportUserIds));
                }
                foreach ($hotProds->get() as $hp) {
                    $records[] = [
                        'account_name' => $hp->lead?->company_name ?: ($hp->lead?->name ?: 'Lead #' . $hp->lead_id),
                        'branch_name'  => $hp->lead?->branch?->name ?: '—',
                        'product_name' => $hp->product?->name ?: 'Prospect Deal',
                        'amount'       => (float) $hp->total_price,
                        'date'         => $hp->closure_date ? Carbon::parse($hp->closure_date)->format('d M Y') : '—',
                        'mode'         => 'Prospect (Hot)',
                        'recorded_by'  => 'CST Target',
                    ];
                }
                break;

            case 'completed_percentage':
                $title = "Target vs Achieved Overview";
                $stats = $this->calculateCstStats($targetUserId, $dateRangeFilter, $visibleUserIds, $branchId);
                $records[] = [
                    'account_name' => 'Monthly Target',
                    'branch_name'  => '—',
                    'product_name' => 'Committed Target',
                    'amount'       => (float) $stats['monthly_target'],
                    'date'         => $parsedDate->format('M Y'),
                    'mode'         => 'Target',
                    'recorded_by'  => 'CST Team',
                ];
                $records[] = [
                    'account_name' => 'Till Now Achieved',
                    'branch_name'  => '—',
                    'product_name' => 'Month-to-Date Collections',
                    'amount'       => (float) $stats['till_now_achieved'],
                    'date'         => $parsedDate->format('d M Y'),
                    'mode'         => 'Achieved (' . $stats['completed_percentage'] . '%)',
                    'recorded_by'  => 'CST Team',
                ];
                break;

            case 'current_week_meetings':
                $title = "Current Week Meetings, Reviews & Updates";
                $meetingsQ = LeadReminder::with(['lead:id,company_name,name,branch_id', 'lead.branch:id,name', 'user:id,name'])
                    ->where(fn($q) => $q->where('type', 'meeting')->orWhere('title', 'like', '%meeting%'))
                    ->whereBetween('remind_at', [$weekStart->startOfDay(), $weekEnd->endOfDay()]);
                if ($targetUserId) {
                    $meetingsQ->where('user_id', $targetUserId);
                } elseif (!empty($supportUserIds)) {
                    $meetingsQ->whereIn('user_id', $supportUserIds);
                }
                foreach ($meetingsQ->get() as $m) {
                    $records[] = [
                        'account_name' => $m->lead?->company_name ?: ($m->lead?->name ?: 'Lead #' . $m->lead_id),
                        'branch_name'  => $m->lead?->branch?->name ?: '—',
                        'product_name' => $m->title ?: 'Meeting',
                        'amount'       => 0,
                        'date'         => $m->remind_at ? Carbon::parse($m->remind_at)->format('d M Y h:i A') : '—',
                        'mode'         => 'Scheduled Meeting',
                        'recorded_by'  => $m->user?->name ?: 'CST Exec',
                    ];
                }

                $updatesQ = \App\Models\LeadCstUpdate::with(['lead:id,company_name,name,branch_id', 'lead.branch:id,name', 'user:id,name'])
                    ->whereBetween('created_at', [$weekStart->startOfDay(), $weekEnd->endOfDay()]);
                if ($targetUserId) {
                    $updatesQ->where('user_id', $targetUserId);
                } elseif (!empty($supportUserIds)) {
                    $updatesQ->whereIn('user_id', $supportUserIds);
                }
                foreach ($updatesQ->get() as $u) {
                    $records[] = [
                        'account_name' => $u->lead?->company_name ?: ($u->lead?->name ?: 'Lead #' . $u->lead_id),
                        'branch_name'  => $u->lead?->branch?->name ?: '—',
                        'product_name' => ucfirst(str_replace('_', ' ', $u->update_type)),
                        'amount'       => 0,
                        'date'         => $u->created_at ? $u->created_at->format('d M Y h:i A') : '—',
                        'mode'         => Str::limit($u->notes ?? '', 60),
                        'recorded_by'  => $u->user?->name ?: 'CST Exec',
                    ];
                }
                break;

            case 'total_allocated_accounts':
                $title = "Total Active Allocated Accounts";
                $allocQ = Lead::with(['branch:id,name', 'cstExecutive:id,name', 'products:id,lead_id,product_id,product_status', 'products.product:id,name'])
                    ->whereNull('deleted_at')
                    ->whereHas('products', fn($pq) => $pq->where('product_status', 'converted'));
                if ($targetUserId) {
                    $allocQ->where('customer_support_executive_id', $targetUserId);
                } elseif (!empty($supportUserIds)) {
                    $allocQ->whereIn('customer_support_executive_id', $supportUserIds);
                }
                foreach ($allocQ->latest('id')->limit(100)->get() as $l) {
                    $prodNames = $l->products->filter(fn($p) => $p->product_status === 'converted')->map(fn($p) => $p->product?->name)->filter()->implode(', ');
                    $records[] = [
                        'account_name' => $l->company_name ?: ($l->name ?: 'Lead #' . $l->id),
                        'branch_name'  => $l->branch?->name ?: '—',
                        'product_name' => $prodNames ?: 'Converted Product',
                        'amount'       => 0,
                        'date'         => $l->customer_support_allocated_at ? Carbon::parse($l->customer_support_allocated_at)->format('d M Y') : $l->created_at?->format('d M Y'),
                        'mode'         => 'Active Allocation',
                        'recorded_by'  => $l->cstExecutive?->name ?: 'Unassigned',
                    ];
                }
                break;

            case 'today_added_accounts':
                $title = "Newly Added Allocated Accounts";
                $addedQ = Lead::with(['branch:id,name', 'cstExecutive:id,name', 'products:id,lead_id,product_id,product_status', 'products.product:id,name'])
                    ->whereNull('deleted_at')
                    ->whereHas('products', fn($pq) => $pq->where('product_status', 'converted'))
                    ->where(function($q) use ($dateRangeFilter, $refDateStr) {
                        if (is_array($dateRangeFilter)) {
                            $q->whereBetween('customer_support_allocated_at', [$dateRangeFilter[0] . ' 00:00:00', $dateRangeFilter[1] . ' 23:59:59'])
                              ->orWhere(function($sub) use ($dateRangeFilter) {
                                  $sub->whereNull('customer_support_allocated_at')
                                      ->whereBetween('created_at', [$dateRangeFilter[0] . ' 00:00:00', $dateRangeFilter[1] . ' 23:59:59']);
                              });
                        } else {
                            $q->whereDate('customer_support_allocated_at', $refDateStr)
                              ->orWhere(function($sub) use ($refDateStr) {
                                  $sub->whereNull('customer_support_allocated_at')
                                      ->whereDate('created_at', $refDateStr);
                              });
                        }
                    });
                if ($targetUserId) {
                    $addedQ->where('customer_support_executive_id', $targetUserId);
                } elseif (!empty($supportUserIds)) {
                    $addedQ->whereIn('customer_support_executive_id', $supportUserIds);
                }
                foreach ($addedQ->latest('id')->get() as $l) {
                    $prodNames = $l->products->filter(fn($p) => $p->product_status === 'converted')->map(fn($p) => $p->product?->name)->filter()->implode(', ');
                    $records[] = [
                        'account_name' => $l->company_name ?: ($l->name ?: 'Lead #' . $l->id),
                        'branch_name'  => $l->branch?->name ?: '—',
                        'product_name' => $prodNames ?: 'Converted Product',
                        'amount'       => 0,
                        'date'         => $l->customer_support_allocated_at ? Carbon::parse($l->customer_support_allocated_at)->format('d M Y') : $l->created_at?->format('d M Y'),
                        'mode'         => 'Newly Added Today',
                        'recorded_by'  => $l->cstExecutive?->name ?: 'Unassigned',
                    ];
                }
                break;

            case 'welcome_call_pending_count':
                $title = "Welcome Call Pending Accounts";
                $ovpQ = ProductionInitiation::with(['lead:id,company_name,name,branch_id', 'lead.branch:id,name', 'product:id,name', 'ovpAllocatedUser:id,name'])
                    ->whereIn('status', ['ovp_pending', 'initiated', 'pending']);
                if ($targetUserId) {
                    $ovpQ->where(function($q) use ($targetUserId) {
                        $q->where('ovp_allocated_to', $targetUserId)
                          ->orWhere(fn($sub) => $sub->whereNull('ovp_allocated_to')->whereHas('lead', fn($lq) => $lq->where('customer_support_executive_id', $targetUserId)->orWhere('customer_support_tl_id', $targetUserId)));
                    });
                } elseif (!empty($supportUserIds)) {
                    $ovpQ->where(function($q) use ($supportUserIds) {
                        $q->whereIn('ovp_allocated_to', $supportUserIds)
                          ->orWhere(fn($sub) => $sub->whereNull('ovp_allocated_to')->whereHas('lead', fn($lq) => $lq->whereIn('customer_support_executive_id', $supportUserIds)->orWhereIn('customer_support_tl_id', $supportUserIds)));
                    });
                }
                $threeDaysAgo = Carbon::now()->subDays(3);
                foreach ($ovpQ->latest('id')->get() as $pi) {
                    $st = 'Pending';
                    if (in_array($pi->status, ['ovp_pending', 'initiated'])) {
                        $st = $pi->created_at >= $threeDaysAgo ? 'New Welcome Call' : 'Overdue Call';
                    }
                    $records[] = [
                        'account_name' => $pi->lead?->company_name ?: ($pi->lead?->name ?: 'Lead #' . $pi->lead_id),
                        'branch_name'  => $pi->lead?->branch?->name ?: '—',
                        'product_name' => $pi->product?->name ?: 'OVP Product',
                        'amount'       => 0,
                        'date'         => $pi->created_at ? $pi->created_at->format('d M Y') : '—',
                        'mode'         => $st,
                        'recorded_by'  => $pi->ovpAllocatedUser?->name ?: 'CST Team',
                    ];
                }
                break;
        }

        return response()->json([
            'success' => true,
            'metric'  => $metric,
            'title'   => $title,
            'count'   => count($records),
            'records' => $records,
        ]);
    }

    /**
     * Mobile API: Create CST Day Closing.
     */
    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'closing_date'          => ['nullable', 'date'],
            'user_id'               => ['nullable', 'integer'],
            'remarks'               => ['nullable', 'string'],
            'is_on_leave_tomorrow'  => ['nullable', 'boolean'],
            'tomorrow_plans'        => ['nullable', 'array'],
            'plan_for_tomorrow'     => ['nullable', 'string'],
        ]);

        $date = isset($validated['closing_date']) ? Carbon::parse($validated['closing_date'])->toDateString() : now()->toDateString();
        $targetUserId = !empty($validated['user_id']) && $this->canViewAllCst($user)
            ? (int) $validated['user_id']
            : $user->id;

        $existing = CstDailyClosing::where('user_id', $targetUserId)
            ->whereDate('closing_date', $date)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'CST Day Closing has already been submitted for this date.',
            ], 422);
        }

        $targetUser = User::find($targetUserId) ?: $user;
        $stats = $this->calculateCstStats($targetUserId, $date);

        $closing = CstDailyClosing::create([
            'company_id'                 => $targetUser->company_id,
            'user_id'                    => $targetUserId,
            'branch_id'                  => $targetUser->branch_id,
            'closing_date'               => $date,
            'monthly_target'             => $stats['monthly_target'],
            'today_revenue'              => $stats['today_revenue'],
            'till_now_achieved'          => $stats['till_now_achieved'],
            'completed_percentage'       => $stats['completed_percentage'],
            'current_week_meetings'      => $stats['current_week_meetings'],
            'total_allocated_accounts'   => $stats['total_allocated_accounts'],
            'today_added_accounts'       => $stats['today_added_accounts'],
            'welcome_call_pending_count' => $stats['welcome_call_pending_count'],
            'remarks'                    => $validated['remarks'] ?? null,
            'is_on_leave_tomorrow'       => (bool) ($validated['is_on_leave_tomorrow'] ?? false),
            'tomorrow_plans'             => $validated['tomorrow_plans'] ?? [],
            'plan_for_tomorrow'          => $validated['plan_for_tomorrow'] ?? null,
            'status'                     => 'submitted',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'CST Day closing submitted successfully.',
            'data'    => [
                'id'           => $closing->id,
                'status'       => $closing->status,
                'closing_date' => $date,
            ],
        ], 201);
    }

    /**
     * Mobile API: Update CST Day Closing.
     */
    public function update(Request $request, CstDailyClosing $closing): JsonResponse
    {
        $user = auth()->user();
        if ($closing->user_id !== $user->id && !$this->canViewAllCst($user)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'remarks'              => ['nullable', 'string'],
            'is_on_leave_tomorrow' => ['nullable', 'boolean'],
            'tomorrow_plans'       => ['nullable', 'array'],
            'plan_for_tomorrow'    => ['nullable', 'string'],
        ]);

        $closing->update([
            'remarks'              => $validated['remarks'] ?? $closing->remarks,
            'is_on_leave_tomorrow' => isset($validated['is_on_leave_tomorrow']) ? (bool) $validated['is_on_leave_tomorrow'] : $closing->is_on_leave_tomorrow,
            'tomorrow_plans'       => $validated['tomorrow_plans'] ?? $closing->tomorrow_plans,
            'plan_for_tomorrow'    => $validated['plan_for_tomorrow'] ?? $closing->plan_for_tomorrow,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'CST Day closing updated successfully.',
            'data'    => [
                'id'     => $closing->id,
                'status' => $closing->status,
            ],
        ]);
    }

    /**
     * Mobile API: Review/Approve CST Day Closing.
     */
    public function updateStatus(Request $request, CstDailyClosing $closing): JsonResponse
    {
        $user = auth()->user();
        if (!$this->canViewAllCst($user)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'status'       => ['required', 'string', 'in:submitted,reviewed,approved'],
            'review_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $closing->update([
            'status'       => $validated['status'],
            'reviewed_by'  => $user->id,
            'reviewed_at'  => now(),
            'review_notes' => $validated['review_notes'] ?? $closing->review_notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.',
            'status'  => $closing->status,
        ]);
    }

    /**
     * Mobile API: Delete CST Day Closing.
     */
    public function destroy(CstDailyClosing $closing): JsonResponse
    {
        $user = auth()->user();
        if ($closing->user_id !== $user->id && !$this->canViewAllCst($user)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $closing->delete();

        return response()->json(['success' => true, 'message' => 'Record deleted successfully.']);
    }
}
