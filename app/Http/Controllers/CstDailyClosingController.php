<?php

namespace App\Http\Controllers;

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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CstDailyClosingController extends Controller
{
    public function __construct(private readonly DataVisibilityService $visibility) {}

    /**
     * Get Customer Support Team user IDs.
     */
    private function getSupportUserIds(User $user): array
    {
        $visibleUserIds = $this->visibility->visibleUserIds();

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

        if ($visibleUserIds !== null) {
            return array_values(array_intersect($visibleUserIds, $supportUserIds));
        }

        return $supportUserIds;
    }

    /**
     * Display the CST Department Daily Closing list and KPIs.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $today = now()->toDateString();

        $selectedDate = $request->filled('date') ? $request->input('date') : $today;
        $dateFrom = $request->filled('date_from') ? $request->input('date_from') : null;
        $dateTo = $request->filled('date_to') ? $request->input('date_to') : null;

        $isAdminLike = $user->hasAdminLikeRole() || $user->isSuperAdmin() || $user->isCompanyAdmin() || $user->isBranchAdmin();
        $isTlLike = $user->hasTlLikeRole();

        $supportUserIds = $this->getSupportUserIds($user);
        if (empty($supportUserIds)) {
            $supportUserIds = [$user->id];
        }

        $assignableUsers = User::whereIn('id', $supportUserIds)->orderBy('name')->get(['id', 'name']);
        if ($assignableUsers->isEmpty()) {
            $assignableUsers = collect([$user]);
        }

        $branches = Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        // Base query for CstDailyClosing submissions
        $query = CstDailyClosing::with([
            'user:id,name,email,branch_id,designation',
            'branch:id,name',
            'reviewer:id,name',
        ])->latest('closing_date')->latest('id');

        // Apply visibility
        if (!$isAdminLike) {
            if ($isTlLike) {
                $subordinateIds = $user->managedUsers()->pluck('users.id')->push($user->id)->all();
                $query->whereIn('user_id', $subordinateIds);
            } else {
                $query->where('user_id', $user->id);
            }
        }

        // Apply filters
        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->input('user_id'));
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->input('branch_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($dateFrom && $dateTo) {
            $query->whereBetween('closing_date', [$dateFrom, $dateTo]);
        } elseif ($dateFrom) {
            $query->whereDate('closing_date', '>=', $dateFrom);
        } elseif ($dateTo) {
            $query->whereDate('closing_date', '<=', $dateTo);
        } elseif ($request->filled('date')) {
            $query->whereDate('closing_date', $selectedDate);
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

        $closings = $query->paginate(15)->withQueryString();

        // Calculate KPI summary for the selected date & user filter
        $targetUserId = $request->filled('user_id')
            ? (int) $request->input('user_id')
            : ($isAdminLike || $isTlLike ? null : $user->id);

        $kpiStats = $this->calculateCstStats($targetUserId, $selectedDate);

        // Check if the current user has submitted today's closing
        $myTodayClosing = CstDailyClosing::where('user_id', $user->id)
            ->whereDate('closing_date', $today)
            ->first();

        return view('pages.cst.day_closing.index', compact(
            'closings',
            'kpiStats',
            'selectedDate',
            'dateFrom',
            'dateTo',
            'today',
            'assignableUsers',
            'branches',
            'isAdminLike',
            'isTlLike',
            'myTodayClosing'
        ));
    }

    /**
     * AJAX endpoint: calculate CST stats dynamically for selected user & date.
     */
    public function getStats(Request $request): JsonResponse
    {
        $user = auth()->user();
        $targetUserId = $request->filled('user_id') ? (int) $request->input('user_id') : $user->id;
        $closingDate = trim((string) $request->input('closing_date', now()->toDateString()));

        if ($targetUserId !== $user->id && !$user->hasAdminLikeRole() && !$user->hasTlLikeRole()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        try {
            $date = Carbon::parse($closingDate)->toDateString();
        } catch (\Throwable) {
            $date = now()->toDateString();
        }

        $stats = $this->calculateCstStats($targetUserId, $date);

        // Check if closing already exists for this user and date
        $existing = CstDailyClosing::where('user_id', $targetUserId)
            ->whereDate('closing_date', $date)
            ->first();

        $existingData = null;
        if ($existing) {
            $existingData = [
                'id'                         => $existing->id,
                'monthly_target'             => $existing->monthly_target,
                'today_revenue'              => $existing->today_revenue,
                'till_now_achieved'          => $existing->till_now_achieved,
                'completed_percentage'       => $existing->completed_percentage,
                'current_week_meetings'      => $existing->current_week_meetings,
                'total_allocated_accounts'   => $existing->total_allocated_accounts,
                'today_added_accounts'       => $existing->today_added_accounts,
                'welcome_call_pending_count' => $existing->welcome_call_pending_count,
                'remarks'                    => $existing->remarks,
                'is_on_leave_tomorrow'       => (bool) $existing->is_on_leave_tomorrow,
                'tomorrow_plans'             => is_array($existing->tomorrow_plans) ? $existing->tomorrow_plans : [],
                'total_expected_value'       => $existing->total_expected_value,
                'plan_for_tomorrow'          => $existing->plan_for_tomorrow,
                'status'                     => $existing->status,
                'attachments'                => $existing->attachment_list,
                'update_url'                 => route('cst.day-closing.update', $existing->id),
            ];
        }

        return response()->json([
            'success'  => true,
            'date'     => $date,
            'stats'    => $stats,
            'exists'   => (bool) $existing,
            'closing'  => $existingData,
        ]);
    }

    /**
     * Store a newly created CST daily closing update.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $currentUser = auth()->user();

        $validated = $request->validate([
            'closing_date'                    => ['required', 'date', 'before_or_equal:today'],
            'user_id'                         => ['nullable', 'integer', 'exists:users,id'],
            'remarks'                         => ['required', 'string', 'min:5', 'max:10000'],
            'monthly_target'                  => ['nullable', 'numeric', 'min:0'],
            'today_revenue'                   => ['nullable', 'numeric', 'min:0'],
            'till_now_achieved'               => ['nullable', 'numeric', 'min:0'],
            'completed_percentage'            => ['nullable', 'numeric', 'min:0'],
            'current_week_meetings'           => ['nullable', 'integer', 'min:0'],
            'total_allocated_accounts'        => ['nullable', 'integer', 'min:0'],
            'today_added_accounts'            => ['nullable', 'integer', 'min:0'],
            'welcome_call_pending_count'      => ['nullable', 'integer', 'min:0'],
            'is_on_leave_tomorrow'            => ['nullable'],
            'tomorrow_plans'                  => ['nullable', 'array'],
            'tomorrow_plans.*.company_name'   => ['nullable', 'string', 'max:255'],
            'tomorrow_plans.*.product_name'   => ['nullable', 'string', 'max:255'],
            'tomorrow_plans.*.expected_value' => ['nullable', 'numeric', 'min:0'],
            'attachments'                     => ['nullable', 'array'],
            'attachments.*'                   => ['file', 'max:10240', 'mimes:jpg,jpeg,png,pdf,doc,docx,xlsx,xls,zip'],
        ], [
            'closing_date.before_or_equal' => 'Cannot submit closing for future dates.',
            'remarks.required'             => 'Please enter your CST day closing remarks.',
        ]);

        $targetUserId = !empty($validated['user_id']) && ($currentUser->hasAdminLikeRole() || $currentUser->hasTlLikeRole())
            ? (int) $validated['user_id']
            : $currentUser->id;

        $targetUser = User::find($targetUserId) ?: $currentUser;
        $closingDate = Carbon::parse($validated['closing_date'])->toDateString();

        // Calculate auto-verified system stats
        $stats = $this->calculateCstStats($targetUserId, $closingDate);

        // Process tomorrow plans or planned leave
        $isOnLeave = $request->boolean('is_on_leave_tomorrow');
        $cleanedPlans = [];
        $planSummary = null;

        if ($isOnLeave) {
            $planSummary = '🏖️ Planned Leave Tomorrow';
        } else {
            $cleanedPlans = $this->normalizeTomorrowPlans($request->input('tomorrow_plans', []));
            if (!empty($cleanedPlans)) {
                $planSummary = collect($cleanedPlans)->map(function ($p) {
                    $valStr = $p['expected_value'] > 0 ? ' (₹' . number_format($p['expected_value']) . ')' : '';
                    return trim($p['company_name'] . ($p['product_name'] ? ' - ' . $p['product_name'] : '')) . $valStr;
                })->implode('; ');
            }
        }

        // Handle file uploads
        $existingAttachments = [];
        $closing = CstDailyClosing::where('user_id', $targetUserId)
            ->whereDate('closing_date', $closingDate)
            ->first();

        if ($closing && is_array($closing->attachments)) {
            $existingAttachments = $closing->attachments;
        }

        $newAttachments = $this->handleFileUploads($request);
        $finalAttachments = array_merge($existingAttachments, $newAttachments);

        $monthlyTarget = isset($validated['monthly_target']) ? (float)$validated['monthly_target'] : $stats['monthly_target'];
        $todayRevenue = isset($validated['today_revenue']) ? (float)$validated['today_revenue'] : $stats['today_revenue'];
        $tillNowAchieved = isset($validated['till_now_achieved']) ? (float)$validated['till_now_achieved'] : $stats['till_now_achieved'];
        $completedPct = $monthlyTarget > 0 ? round(($tillNowAchieved / $monthlyTarget) * 100, 1) : 0;

        $dataToSave = [
            'company_id'                 => $targetUser->company_id,
            'branch_id'                  => $targetUser->branch_id,
            'user_id'                    => $targetUserId,
            'closing_date'               => $closingDate,
            'monthly_target'             => $monthlyTarget,
            'today_revenue'              => $todayRevenue,
            'till_now_achieved'          => $tillNowAchieved,
            'completed_percentage'       => $completedPct,
            'current_week_meetings'      => isset($validated['current_week_meetings']) ? (int)$validated['current_week_meetings'] : $stats['current_week_meetings'],
            'total_allocated_accounts'   => isset($validated['total_allocated_accounts']) ? (int)$validated['total_allocated_accounts'] : $stats['total_allocated_accounts'],
            'today_added_accounts'       => isset($validated['today_added_accounts']) ? (int)$validated['today_added_accounts'] : $stats['today_added_accounts'],
            'welcome_call_pending_count' => isset($validated['welcome_call_pending_count']) ? (int)$validated['welcome_call_pending_count'] : $stats['welcome_call_pending_count'],
            'remarks'                    => $validated['remarks'],
            'is_on_leave_tomorrow'       => $isOnLeave,
            'tomorrow_plans'             => $cleanedPlans,
            'plan_for_tomorrow'          => $planSummary,
            'attachments'                => $finalAttachments,
        ];

        if ($closing) {
            $closing->update($dataToSave);
        } else {
            $dataToSave['status'] = 'submitted';
            $closing = CstDailyClosing::create($dataToSave);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'CST Day Closing saved successfully.',
                'closing' => $closing,
            ]);
        }

        return redirect()->route('cst.day-closing.index', ['date' => $closingDate])
            ->with('success', 'CST Day Closing Update submitted successfully.');
    }

    /**
     * Update an existing daily closing.
     */
    public function update(Request $request, CstDailyClosing $closing): RedirectResponse|JsonResponse
    {
        $currentUser = auth()->user();

        // Only owner or Admin/TL can update
        if ($closing->user_id !== $currentUser->id && !$currentUser->hasAdminLikeRole() && !$currentUser->hasTlLikeRole()) {
            abort(403, 'Unauthorized to edit this CST closing update.');
        }

        $validated = $request->validate([
            'remarks'                         => ['required', 'string', 'min:5', 'max:10000'],
            'monthly_target'                  => ['nullable', 'numeric', 'min:0'],
            'today_revenue'                   => ['nullable', 'numeric', 'min:0'],
            'till_now_achieved'               => ['nullable', 'numeric', 'min:0'],
            'current_week_meetings'           => ['nullable', 'integer', 'min:0'],
            'total_allocated_accounts'        => ['nullable', 'integer', 'min:0'],
            'today_added_accounts'            => ['nullable', 'integer', 'min:0'],
            'welcome_call_pending_count'      => ['nullable', 'integer', 'min:0'],
            'is_on_leave_tomorrow'            => ['nullable'],
            'tomorrow_plans'                  => ['nullable', 'array'],
            'tomorrow_plans.*.company_name'   => ['nullable', 'string', 'max:255'],
            'tomorrow_plans.*.product_name'   => ['nullable', 'string', 'max:255'],
            'tomorrow_plans.*.expected_value' => ['nullable', 'numeric', 'min:0'],
            'attachments'                     => ['nullable', 'array'],
            'attachments.*'                   => ['file', 'max:10240', 'mimes:jpg,jpeg,png,pdf,doc,docx,xlsx,xls,zip'],
        ], [
            'remarks.required' => 'Please enter your CST day closing remarks.',
        ]);

        $isOnLeave = $request->boolean('is_on_leave_tomorrow');
        $cleanedPlans = [];
        $planSummary = null;

        if ($isOnLeave) {
            $planSummary = '🏖️ Planned Leave Tomorrow';
        } else {
            $cleanedPlans = $this->normalizeTomorrowPlans($request->input('tomorrow_plans', []));
            if (!empty($cleanedPlans)) {
                $planSummary = collect($cleanedPlans)->map(function ($p) {
                    $valStr = $p['expected_value'] > 0 ? ' (₹' . number_format($p['expected_value']) . ')' : '';
                    return trim($p['company_name'] . ($p['product_name'] ? ' - ' . $p['product_name'] : '')) . $valStr;
                })->implode('; ');
            }
        }

        $existingAttachments = is_array($closing->attachments) ? $closing->attachments : [];
        $newAttachments = $this->handleFileUploads($request);
        $finalAttachments = array_merge($existingAttachments, $newAttachments);

        $monthlyTarget = isset($validated['monthly_target']) ? (float)$validated['monthly_target'] : $closing->monthly_target;
        $tillNowAchieved = isset($validated['till_now_achieved']) ? (float)$validated['till_now_achieved'] : $closing->till_now_achieved;
        $completedPct = $monthlyTarget > 0 ? round(($tillNowAchieved / $monthlyTarget) * 100, 1) : 0;

        $closing->update([
            'monthly_target'             => $monthlyTarget,
            'today_revenue'              => isset($validated['today_revenue']) ? (float)$validated['today_revenue'] : $closing->today_revenue,
            'till_now_achieved'          => $tillNowAchieved,
            'completed_percentage'       => $completedPct,
            'current_week_meetings'      => isset($validated['current_week_meetings']) ? (int)$validated['current_week_meetings'] : $closing->current_week_meetings,
            'total_allocated_accounts'   => isset($validated['total_allocated_accounts']) ? (int)$validated['total_allocated_accounts'] : $closing->total_allocated_accounts,
            'today_added_accounts'       => isset($validated['today_added_accounts']) ? (int)$validated['today_added_accounts'] : $closing->today_added_accounts,
            'welcome_call_pending_count' => isset($validated['welcome_call_pending_count']) ? (int)$validated['welcome_call_pending_count'] : $closing->welcome_call_pending_count,
            'remarks'                    => $validated['remarks'],
            'is_on_leave_tomorrow'       => $isOnLeave,
            'tomorrow_plans'             => $cleanedPlans,
            'plan_for_tomorrow'          => $planSummary,
            'attachments'                => $finalAttachments,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'CST Day Closing updated successfully.',
                'closing' => $closing,
            ]);
        }

        return redirect()->route('cst.day-closing.index', ['date' => $closing->closing_date->toDateString()])
            ->with('success', 'CST Day Closing updated successfully.');
    }

    /**
     * Review / Approve closing by TL or Admin.
     */
    public function review(Request $request, CstDailyClosing $closing): JsonResponse|RedirectResponse
    {
        $user = auth()->user();
        if (!$user->hasAdminLikeRole() && !$user->hasTlLikeRole()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
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

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Status updated to ' . ucfirst($validated['status']),
                'status'  => $validated['status'],
            ]);
        }

        return back()->with('success', 'Status updated successfully.');
    }

    /**
     * Delete a daily closing update.
     */
    public function destroy(CstDailyClosing $closing): RedirectResponse|JsonResponse
    {
        $currentUser = auth()->user();

        if ($closing->user_id !== $currentUser->id && !$currentUser->hasAdminLikeRole()) {
            abort(403, 'Unauthorized.');
        }

        // Clean up files
        if (is_array($closing->attachments)) {
            foreach ($closing->attachments as $item) {
                $path = is_array($item) ? ($item['path'] ?? '') : (string)$item;
                if ($path) {
                    @unlink(public_path(ltrim($path, '/')));
                }
            }
        }

        $closing->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'CST Day closing record deleted.']);
        }

        return back()->with('success', 'CST Day closing record deleted successfully.');
    }

    /**
     * Delete a single attachment from a closing record.
     */
    public function deleteAttachment(Request $request, CstDailyClosing $closing): JsonResponse
    {
        $currentUser = auth()->user();
        if ($closing->user_id !== $currentUser->id && !$currentUser->hasAdminLikeRole() && !$currentUser->hasTlLikeRole()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $pathToDelete = ltrim((string) $request->input('path'), '/');
        $attachments = is_array($closing->attachments) ? $closing->attachments : [];

        $filtered = [];
        $deleted = false;

        foreach ($attachments as $item) {
            $itemPath = is_array($item) ? ltrim((string)($item['path'] ?? ''), '/') : ltrim((string)$item, '/');
            if ($itemPath === $pathToDelete) {
                @unlink(public_path($itemPath));
                $deleted = true;
            } else {
                $filtered[] = $item;
            }
        }

        if ($deleted) {
            $closing->update(['attachments' => array_values($filtered)]);
            return response()->json(['success' => true, 'message' => 'Attachment removed.']);
        }

        return response()->json(['success' => false, 'message' => 'Attachment not found.'], 404);
    }

    /**
     * Calculate CST Department stats for a specific user and date.
     */
    public function calculateCstStats(?int $userId, string $date): array
    {
        $currentUser = auth()->user();
        $supportUserIds = $this->getSupportUserIds($currentUser);

        $parsedDate = Carbon::parse($date);
        $monthStart = (clone $parsedDate)->startOfMonth();
        $weekStart = (clone $parsedDate)->startOfWeek();
        $weekEnd = (clone $parsedDate)->endOfWeek();

        // 1. Monthly Target : Current Month Prospect Value show pananum default
        $prospectQuery = LeadProduct::query()
            ->whereRaw('LOWER(product_status) = ?', ['hot'])
            ->whereMonth('closure_date', $monthStart->month)
            ->whereYear('closure_date', $monthStart->year);

        if ($userId) {
            $prospectQuery->whereHas('lead', function($lq) use ($userId) {
                $lq->where('customer_support_executive_id', $userId)
                   ->orWhere('customer_support_tl_id', $userId)
                   ->orWhere('assigned_to', $userId);
            });
        } elseif (!empty($supportUserIds)) {
            $prospectQuery->whereHas('lead', function($lq) use ($supportUserIds) {
                $lq->whereIn('customer_support_executive_id', $supportUserIds)
                   ->orWhereIn('customer_support_tl_id', $supportUserIds)
                   ->orWhereIn('assigned_to', $supportUserIds);
            });
        }

        $prospectVal = (float) $prospectQuery->sum('total_price');

        if ($prospectVal <= 0 && $userId) {
            $targetMonthStr = $monthStart->format('Y-m');
            $explicitTarget = (float) (SalesTarget::where('user_id', $userId)->where('target_month', $targetMonthStr)->value('target_amount')
                ?? SalesTarget::where('user_id', $userId)->value('target_amount')
                ?? 0);
            $monthlyTarget = $explicitTarget;
        } else {
            $monthlyTarget = $prospectVal;
        }

        // 2. Today's Revenue : Today Received Value
        $todayPaymentQuery = LeadProductPayment::query()
            ->whereDate('payment_date', $date);

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

        // 3. Till Now Achieved : Month Starting la irundhu current date varaikum evlo achive panirukanga
        $achievedPaymentQuery = LeadProductPayment::query()
            ->whereDate('payment_date', '>=', $monthStart->toDateString())
            ->whereDate('payment_date', '<=', $date);

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

        // 4. Completed Percentage : Till date varaikum achieve paniruka amount percentage
        $completedPercentage = $monthlyTarget > 0 ? round(($tillNowAchieved / $monthlyTarget) * 100, 1) : 0.0;

        // 5. Current Week Meetings : Current week attend pana meeting count
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
        $currentWeekMeetings = (int) $meetingsQuery->count();

        // 6. Total Allocated Account : Overall Allocated Accounts (Unique)
        $allocQuery = Lead::query();
        if ($userId) {
            $allocQuery->where(function($q) use ($userId) {
                $q->where('customer_support_executive_id', $userId)
                  ->orWhere('customer_support_tl_id', $userId);
            });
        } else {
            $allocQuery->whereNotNull('customer_support_executive_id');
        }
        $totalAllocatedAccounts = (int) $allocQuery->distinct()->count('id');

        // 7. Today's Added Account : Today Newly Added Count
        $todayAddedQuery = Lead::query();
        if ($userId) {
            $todayAddedQuery->where(function($q) use ($userId) {
                $q->where('customer_support_executive_id', $userId)
                  ->orWhere('customer_support_tl_id', $userId);
            });
        } else {
            $todayAddedQuery->whereNotNull('customer_support_executive_id');
        }
        $todayAddedAccounts = (int) (clone $todayAddedQuery)->where(function($q) use ($date) {
            $q->whereDate('customer_support_allocated_at', $date)
              ->orWhere(function($sub) use ($date) {
                  $sub->whereNull('customer_support_allocated_at')
                      ->whereDate('created_at', $date);
              });
        })->distinct()->count('id');

        // 8. Welcome Call Pending Account Count : Production Initiate pane inum welcome call pending la iruka count
        $welcomePendingQuery = ProductionInitiation::query()
            ->whereNull('welcome_call_date');

        if ($userId) {
            $welcomePendingQuery->whereHas('lead', function($lq) use ($userId) {
                $lq->where('customer_support_executive_id', $userId)
                   ->orWhere('customer_support_tl_id', $userId)
                   ->orWhere('initiated_by', $userId);
            });
        } elseif (!empty($supportUserIds)) {
            $welcomePendingQuery->whereHas('lead', function($lq) use ($supportUserIds) {
                $lq->whereIn('customer_support_executive_id', $supportUserIds)
                   ->orWhereIn('customer_support_tl_id', $supportUserIds)
                   ->orWhereIn('initiated_by', $supportUserIds);
            });
        }
        $welcomeCallPendingCount = (int) $welcomePendingQuery->distinct()->count('lead_id');

        return [
            'monthly_target'             => $monthlyTarget,
            'today_revenue'              => $todayRevenue,
            'till_now_achieved'          => $tillNowAchieved,
            'completed_percentage'       => $completedPercentage,
            'current_week_meetings'      => $currentWeekMeetings,
            'total_allocated_accounts'   => $totalAllocatedAccounts,
            'today_added_accounts'       => $todayAddedAccounts,
            'welcome_call_pending_count' => $welcomeCallPendingCount,
        ];
    }

    /**
     * Normalize tomorrow plans array from request input.
     */
    protected function normalizeTomorrowPlans(mixed $rawPlans): array
    {
        if (!is_array($rawPlans)) {
            return [];
        }

        $cleanedPlans = [];
        foreach ($rawPlans as $p) {
            if (!is_array($p)) continue;
            $company = trim((string)($p['company_name'] ?? ''));
            $product = trim((string)($p['product_name'] ?? ''));
            $val = floatval($p['expected_value'] ?? 0);
            if ($company !== '' || $product !== '' || $val > 0) {
                $cleanedPlans[] = [
                    'company_name'   => $company,
                    'product_name'   => $product,
                    'expected_value' => $val,
                ];
            }
        }

        return $cleanedPlans;
    }

    /**
     * Handle file uploads.
     */
    protected function handleFileUploads(Request $request): array
    {
        $attachments = [];
        if ($request->hasFile('attachments')) {
            $files = $request->file('attachments');
            if (!is_array($files)) {
                $files = [$files];
            }

            $uploadDir = public_path('uploads/cst_daily_closings');
            if (!file_exists($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }

            foreach ($files as $file) {
                if ($file && $file->isValid()) {
                    $originalName = $file->getClientOriginalName();
                    $ext = $file->getClientOriginalExtension();
                    $filename = Str::random(20) . '.' . $ext;
                    $file->move($uploadDir, $filename);

                    $attachments[] = [
                        'path'      => 'uploads/cst_daily_closings/' . $filename,
                        'name'      => $originalName,
                        'size'      => @filesize($uploadDir . '/' . $filename),
                        'mime_type' => $file->getClientMimeType(),
                    ];
                }
            }
        }

        return $attachments;
    }
}
