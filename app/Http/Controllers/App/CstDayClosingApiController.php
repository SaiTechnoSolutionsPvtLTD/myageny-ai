<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CstDailyClosing;
use App\Models\Lead;
use App\Models\LeadProductPayment;
use App\Models\User;
use App\Services\DataVisibilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CstDayClosingApiController extends Controller
{
    public function __construct(private readonly DataVisibilityService $visibility) {}

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

        $branches = Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $query = CstDailyClosing::with([
            'user:id,name,email,branch_id,designation',
            'branch:id,name',
            'reviewer:id,name',
        ])->latest('closing_date')->latest('id');

        if (!$canViewAll) {
            $query->where('user_id', $user->id);
        } elseif ($request->filled('user_id') && (int) $request->input('user_id') > 0) {
            $query->where('user_id', (int) $request->input('user_id'));
        }

        if ($request->filled('branch_id') && (int) $request->input('branch_id') > 0) {
            $query->where('branch_id', (int) $request->input('branch_id'));
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

        $targetUserId = $request->filled('user_id') ? (int) $request->input('user_id') : ($canViewAll ? null : $user->id);
        $kpiStats = $this->calculateCstStats($targetUserId, $selectedDate);

        return response()->json([
            'success' => true,
            'can_create' => true,
            'can_review' => $canViewAll,
            'selected_date' => $selectedDate,
            'today' => $today,
            'kpi_stats' => $kpiStats,
            'branches' => $branches,
            'closings' => [
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
        $targetUserId = $request->filled('user_id') ? (int) $request->input('user_id') : $user->id;
        $dateStr = $request->input('date', now()->toDateString());

        if (!$this->canViewAllCst($user) && $targetUserId !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        try {
            $date = Carbon::parse($dateStr)->toDateString();
        } catch (\Throwable) {
            $date = now()->toDateString();
        }

        $stats = $this->calculateCstStats($targetUserId, $date);

        $existing = CstDailyClosing::where('user_id', $targetUserId)
            ->whereDate('closing_date', $date)
            ->first();

        return response()->json([
            'success' => true,
            'date' => $date,
            'kpi_stats' => $stats,
            'exists' => (bool) $existing,
            'closing' => $existing ? [
                'id' => $existing->id,
                'remarks' => $existing->remarks,
                'is_on_leave_tomorrow' => (bool) $existing->is_on_leave_tomorrow,
                'plan_for_tomorrow' => $existing->plan_for_tomorrow,
                'status' => $existing->status,
            ] : null,
        ]);
    }

    /**
     * Mobile API: Create CST Day Closing.
     */
    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'closing_date' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string'],
            'is_on_leave_tomorrow' => ['nullable', 'boolean'],
            'tomorrow_plans' => ['nullable', 'array'],
            'plan_for_tomorrow' => ['nullable', 'string'],
        ]);

        $date = isset($validated['closing_date']) ? Carbon::parse($validated['closing_date'])->toDateString() : now()->toDateString();

        $existing = CstDailyClosing::where('user_id', $user->id)
            ->whereDate('closing_date', $date)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'CST Day Closing has already been submitted for this date.',
            ], 422);
        }

        $stats = $this->calculateCstStats($user->id, $date);

        $closing = CstDailyClosing::create([
            'user_id' => $user->id,
            'branch_id' => $user->branch_id,
            'closing_date' => $date,
            'monthly_target' => $stats['monthly_target'],
            'today_revenue' => $stats['today_revenue'],
            'till_now_achieved' => $stats['till_now_achieved'],
            'completed_percentage' => $stats['completed_percentage'],
            'current_week_meetings' => $stats['current_week_meetings'],
            'total_allocated_accounts' => $stats['total_allocated_accounts'],
            'today_added_accounts' => $stats['today_added_accounts'],
            'welcome_call_pending_count' => $stats['welcome_call_pending_count'],
            'remarks' => $validated['remarks'] ?? null,
            'is_on_leave_tomorrow' => (bool) ($validated['is_on_leave_tomorrow'] ?? false),
            'tomorrow_plans' => $validated['tomorrow_plans'] ?? [],
            'plan_for_tomorrow' => $validated['plan_for_tomorrow'] ?? null,
            'status' => 'submitted',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'CST Day closing submitted successfully.',
            'data' => [
                'id' => $closing->id,
                'status' => $closing->status,
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
            'remarks' => ['nullable', 'string'],
            'is_on_leave_tomorrow' => ['nullable', 'boolean'],
            'tomorrow_plans' => ['nullable', 'array'],
            'plan_for_tomorrow' => ['nullable', 'string'],
        ]);

        $closing->update([
            'remarks' => $validated['remarks'] ?? $closing->remarks,
            'is_on_leave_tomorrow' => isset($validated['is_on_leave_tomorrow']) ? (bool) $validated['is_on_leave_tomorrow'] : $closing->is_on_leave_tomorrow,
            'tomorrow_plans' => $validated['tomorrow_plans'] ?? $closing->tomorrow_plans,
            'plan_for_tomorrow' => $validated['plan_for_tomorrow'] ?? $closing->plan_for_tomorrow,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'CST Day closing updated successfully.',
            'data' => [
                'id' => $closing->id,
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
            'status' => ['required', 'string', 'in:submitted,reviewed,approved'],
        ]);

        $closing->update([
            'status' => $validated['status'],
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.',
            'status' => $closing->status,
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

    private function calculateCstStats(?int $userId, string $date): array
    {
        $parsedDate = Carbon::parse($date);

        $allocatedLeadsQuery = Lead::query();
        if ($userId) {
            $allocatedLeadsQuery->where(function ($q) use ($userId) {
                $q->where('customer_support_executive_id', $userId)
                  ->orWhere('customer_support_tl_id', $userId);
            });
        }
        $totalAllocatedAccounts = (int) $allocatedLeadsQuery->count();

        $todayAddedAccountsQuery = clone $allocatedLeadsQuery;
        $todayAddedAccounts = (int) $todayAddedAccountsQuery->whereDate('created_at', $date)->count();

        $todayRevenueQuery = LeadProductPayment::whereDate('payment_date', $date);
        if ($userId) {
            $todayRevenueQuery->whereHas('lead', function ($lq) use ($userId) {
                $lq->where('customer_support_executive_id', $userId)
                  ->orWhere('customer_support_tl_id', $userId);
            });
        }
        $todayRevenue = (float) $todayRevenueQuery->sum('amount');

        $tillNowAchievedQuery = LeadProductPayment::whereYear('payment_date', $parsedDate->year)
            ->whereMonth('payment_date', $parsedDate->month)
            ->whereDate('payment_date', '<=', $date);
        if ($userId) {
            $tillNowAchievedQuery->whereHas('lead', function ($lq) use ($userId) {
                $lq->where('customer_support_executive_id', $userId)
                  ->orWhere('customer_support_tl_id', $userId);
            });
        }
        $tillNowAchieved = (float) $tillNowAchievedQuery->sum('amount');

        $monthlyTarget = max(100000.0, $tillNowAchieved * 1.25);
        $completedPercentage = $monthlyTarget > 0 ? round(($tillNowAchieved / $monthlyTarget) * 100, 1) : 0.0;

        return [
            'monthly_target' => $monthlyTarget,
            'today_revenue' => $todayRevenue,
            'till_now_achieved' => $tillNowAchieved,
            'completed_percentage' => $completedPercentage,
            'current_week_meetings' => 0,
            'total_allocated_accounts' => $totalAllocatedAccounts,
            'today_added_accounts' => $todayAddedAccounts,
            'welcome_call_pending_count' => 0,
        ];
    }
}
