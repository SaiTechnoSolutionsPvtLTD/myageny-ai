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

        // Also merge any users who have leads allocated in customer_support_executive_id or customer_support_tl_id
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
     * Display the CST Department Daily Closing list and KPIs.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $today = now()->toDateString();

        $fromDate = $request->input('from_date') ?: $request->input('date_from');
        $toDate = $request->input('to_date') ?: $request->input('date_to');
        $selectedDate = $toDate ?: ($fromDate ?: ($request->input('date') ?: $today));

        $canViewAll = $this->canViewAllCst($user);
        $isAdminLike = $canViewAll;
        $isTlLike = false;

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

        // Base query for CstDailyClosing submissions
        $query = CstDailyClosing::with([
            'user:id,name,email,branch_id,designation',
            'branch:id,name',
            'reviewer:id,name',
        ])->latest('closing_date')->latest('id');

        // Apply visibility
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

        $closings = $query->paginate(15)->withQueryString();

        $targetUserId = $request->filled('user_id') && (int) $request->input('user_id') > 0
            ? (int) $request->input('user_id')
            : (!$canViewAll && $visibleUserIds !== null && count($visibleUserIds) === 1 ? $user->id : null);

        $branchIdFilter = $request->filled('branch_id') ? (int) $request->input('branch_id') : null;
        $dateRangeFilter = ($fromDate && $toDate) ? [$fromDate, $toDate] : ($fromDate ?: ($toDate ?: $selectedDate));

        $targetUser = $targetUserId ? User::find($targetUserId) : null;
        $kpiStats = $this->calculateCstStats($targetUserId, $dateRangeFilter, $visibleUserIds, $branchIdFilter);

        // Check if the current user has submitted today's closing
        $myTodayClosing = CstDailyClosing::where('user_id', $user->id)
            ->whereDate('closing_date', $today)
            ->first();

        return view('pages.cst.day_closing.index', compact(
            'closings',
            'kpiStats',
            'selectedDate',
            'fromDate',
            'toDate',
            'today',
            'assignableUsers',
            'branches',
            'isAdminLike',
            'isTlLike',
            'canViewAll',
            'targetUserId',
            'targetUser',
            'myTodayClosing'
        ));
    }

    /**
     * AJAX endpoint: calculate CST stats dynamically for selected user, branch & date range.
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
            'date'     => is_array($dateRangeFilter) ? implode(' to ', $dateRangeFilter) : $dateRangeFilter,
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

        $canViewAll = $this->canViewAllCst($currentUser);
        $targetUserId = !empty($validated['user_id']) && $canViewAll
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

        $canViewAll = $this->canViewAllCst($currentUser);
        // Only owner or Company Admin / CBO can update
        if ($closing->user_id !== $currentUser->id && !$canViewAll) {
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
     * Review / Approve closing by Company Admin or CBO.
     */
    public function review(Request $request, CstDailyClosing $closing): JsonResponse|RedirectResponse
    {
        $user = auth()->user();
        if (!$this->canViewAllCst($user)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action. Only Company Admin and CBO can review day closings.'], 403);
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

        if ($closing->user_id !== $currentUser->id && !$this->canViewAllCst($currentUser)) {
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
        if ($closing->user_id !== $currentUser->id && !$this->canViewAllCst($currentUser)) {
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

        // ---------------------------------------------------------------------------------
        // 1. Monthly Target :
        // a) CST Allocated Leads Current Month Prospect Value (hot status)
        // b) Renewal Product Yes & Current Month Renewal Value
        // c) Development Received Payment >= 60% of Total Payment -> Remaining Value/Target
        // ---------------------------------------------------------------------------------
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

        // Renewal Product Yes & Current month renewal
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

        // Direct renewal lead products (not through production_initiations)
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

        // Development received payment >= 60% of total payment -> map that value
        $devQuery = ProductionInitiation::query()
            ->join('leads', 'leads.id', '=', 'production_initiations.lead_id')
            ->join('lead_products', 'lead_products.id', '=', 'production_initiations.lead_product_id')
            ->where('production_initiations.department_id', 1); // 1 = Development

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

        // 2. Today's Revenue : Today Received Value
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

        // 3. Till Now Achieved : Month Starting la irundhu current date varaikum evlo achive panirukanga
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

        // 4. Completed Percentage : Till date varaikum achieve paniruka amount percentage
        $completedPercentage = $monthlyTarget > 0 ? round(($tillNowAchieved / $monthlyTarget) * 100, 1) : 0.0;

        // ---------------------------------------------------------------------------------
        // 5. Current Week Meetings, Weekly Updates, Reviews & Escalations
        // ---------------------------------------------------------------------------------
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

        // CST Executive added Weekly Updates, Reviews, Escalations
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

        // ---------------------------------------------------------------------------------
        // 6. Total Allocated Account : Matches CST Allocation (leads with converted products allocated to CST)
        // ---------------------------------------------------------------------------------
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

        // ---------------------------------------------------------------------------------
        // 7. Today's Added Account : Matches CST Allocation newly allocated today
        // ---------------------------------------------------------------------------------
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

        // 8. Welcome Call Pending Account Count : OVP Modules la Pending, New, Overdue status la iruka count matum
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

        // Exact match with OvpModuleController::resolveBucket()
        // 1. Pending: status = 'pending'
        $ovpPendingCount = (clone $ovpWelcomeQuery)
            ->where('status', 'pending')
            ->distinct()->count('production_initiations.id');

        // 2. New: status in ['ovp_pending', 'initiated'] and created within 3 days
        $ovpNewCount = (clone $ovpWelcomeQuery)
            ->whereIn('status', ['ovp_pending', 'initiated'])
            ->where('created_at', '>=', $threeDaysAgo)
            ->distinct()->count('production_initiations.id');

        // 3. Overdue: status in ['ovp_pending', 'initiated'] and created older than 3 days
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
            'prospect_val'               => $prospectVal,
            'renewal_val'                => $renewalVal,
            'dev_val'                    => $devVal,
            'weekly_updates_count'       => $weeklyUpdatesCount,
            'reviews_count'              => $reviewsCount,
            'escalations_count'          => $escalationsCount,
            'meeting_reminders_count'    => $remindersMeetingCount,
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

    /**
     * AJAX Endpoint: Fetch detailed records list for KPI card clicked on CST Day Closing page.
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
        $monthEnd   = (clone $parsedDate)->endOfMonth();
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
                // Hot prospect lead products
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
}

