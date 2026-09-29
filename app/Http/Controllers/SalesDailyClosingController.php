<?php

namespace App\Http\Controllers;

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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SalesDailyClosingController extends Controller
{
    public function __construct(private readonly DataVisibilityService $visibility) {}

    /**
     * Display the Sales Department Daily Closing list and KPIs.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $today = now()->toDateString();

        $selectedDate = $request->filled('date') ? $request->input('date') : $today;
        $dateFrom = $request->filled('date_from') ? $request->input('date_from') : null;
        $dateTo = $request->filled('date_to') ? $request->input('date_to') : null;

        $isCompanyAdmin  = $user->isSuperAdmin() || $user->isCompanyAdminRole() || $user->isCbo();
        $isBranchManager = !$isCompanyAdmin && $user->isBranchManager();
        $isBranchAdmin   = !$isCompanyAdmin && !$isBranchManager && $user->isBranchAdmin();
        $isTl            = !$isCompanyAdmin && !$isBranchManager && !$isBranchAdmin && $user->hasTlLikeRole();
        $isExecutive     = !$isCompanyAdmin && !$isBranchManager && !$isBranchAdmin && !$isTl;

        $isAdminLike = $isCompanyAdmin || $isBranchManager || $isBranchAdmin;
        $isTlLike    = $isTl;

        $visibleUserIds = $this->resolveVisibleUserIds($user);

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

        // Branch filter dropdown
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
            'branch:id,name',
            'reviewer:id,name',
        ])->latest('closing_date')->latest('id');

        // Apply visibility
        if ($visibleUserIds !== null) {
            $query->whereIn('user_id', $visibleUserIds);
        }

        // Apply filters
        if ($request->filled('user_id')) {
            $filterUserId = (int) $request->input('user_id');
            if ($visibleUserIds === null || in_array($filterUserId, $visibleUserIds, true)) {
                $query->where('user_id', $filterUserId);
            } else {
                $query->whereRaw('1 = 0');
            }
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
                $q->where('closing_notes', 'like', "%{$search}%")
                  ->orWhere('plan_for_tomorrow', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $closings = $query->paginate(15)->withQueryString();

        // Calculate KPI summary for the selected date & user filter
        $targetUserId = $request->filled('user_id') ? (int) $request->input('user_id') : null;

        if ($targetUserId && $visibleUserIds !== null && !in_array($targetUserId, $visibleUserIds, true)) {
            $targetUserId = $user->id;
        }

        if (!$isCompanyAdmin && !$targetUserId && $visibleUserIds !== null && count($visibleUserIds) === 1) {
            $targetUserId = $user->id;
        }

        $branchIdFilter = $request->filled('branch_id') ? (int) $request->input('branch_id') : null;
        $dateRangeFilter = ($dateFrom && $dateTo) ? [$dateFrom, $dateTo] : ($dateFrom ?: ($dateTo ?: $selectedDate));

        $kpiStats = $this->calculateCallStats($targetUserId, $dateRangeFilter, $visibleUserIds, $branchIdFilter);

        // Check if the current user has submitted today's closing
        $myTodayClosing = SalesDailyClosing::where('user_id', $user->id)
            ->whereDate('closing_date', $today)
            ->first();

        if ($request->wantsJson() || $request->is('api/*') || $request->is('mobile/*')) {
            return response()->json([
                'success'          => true,
                'closings'         => $closings,
                'kpi_stats'        => $kpiStats,
                'selected_date'    => $selectedDate,
                'date_from'        => $dateFrom,
                'date_to'          => $dateTo,
                'today'            => $today,
                'assignable_users' => $assignableUsers->map(fn($u) => ['id' => $u->id, 'name' => $u->name]),
                'branches'         => $branches,
                'is_admin_like'    => $isAdminLike,
                'is_tl_like'       => $isTlLike,
                'my_today_closing' => $myTodayClosing,
            ]);
        }

        return view('pages.crm.day_closing.index', compact(
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
     * AJAX endpoint: calculate call stats dynamically for selected user, branch & date range.
     */
    public function getCallStats(Request $request): JsonResponse
    {
        $user = auth()->user();
        $targetUserId = $request->filled('user_id') ? (int) $request->input('user_id') : null;
        $branchIdFilter = $request->filled('branch_id') ? (int) $request->input('branch_id') : null;

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $closingDate = trim((string) $request->input('closing_date', $request->input('date', now()->toDateString())));

        $dateRangeFilter = ($dateFrom && $dateTo) ? [$dateFrom, $dateTo] : ($dateFrom ?: ($dateTo ?: $closingDate));

        $visibleUserIds = $this->resolveVisibleUserIds($user);

        // Check permission if viewing another user's stats
        if ($targetUserId !== null && $targetUserId !== $user->id && $visibleUserIds !== null && !in_array($targetUserId, $visibleUserIds, true)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        $stats = $this->calculateCallStats($targetUserId, $dateRangeFilter, $visibleUserIds, $branchIdFilter);

        // Check if closing already exists for this user and date
        $lookupUserId = $targetUserId ?? $user->id;
        $lookupDate = is_array($dateRangeFilter) ? $dateRangeFilter[1] : $dateRangeFilter;
        $existing = SalesDailyClosing::where('user_id', $lookupUserId)
            ->whereDate('closing_date', $lookupDate)
            ->first();

        $existingData = null;
        if ($existing) {
            $existingData = [
                'id' => $existing->id,
                'closing_notes' => $existing->closing_notes,
                'is_on_leave_tomorrow' => (bool) $existing->is_on_leave_tomorrow,
                'tomorrow_plans' => is_array($existing->tomorrow_plans) ? $existing->tomorrow_plans : [],
                'total_expected_value' => $existing->total_expected_value,
                'plan_for_tomorrow' => $existing->plan_for_tomorrow,
                'status' => $existing->status,
                'attachments' => $existing->attachment_list,
                'update_url' => route('crm.day-closing.update', $existing->id),
            ];
        }

        return response()->json([
            'success' => true,
            'date' => is_array($dateRangeFilter) ? implode(' to ', $dateRangeFilter) : $dateRangeFilter,
            'stats' => $stats,
            'exists' => (bool) $existing,
            'closing' => $existingData,
        ]);
    }

    /**
     * AJAX endpoint: Fetch call log details for a clicked metric card.
     */
    public function getCallDetails(Request $request): JsonResponse
    {
        $user = auth()->user();
        $metric     = trim((string) $request->input('metric', 'unique_calls'));
        $dateFrom   = trim((string) $request->input('date_from', ''));
        $dateTo     = trim((string) $request->input('date_to', ''));
        $singleDate = trim((string) $request->input('date', ''));
        $targetUserId = $request->filled('user_id') ? (int) $request->input('user_id') : null;

        $visibleUserIds = $this->resolveVisibleUserIds($user);

        if ($targetUserId && $visibleUserIds !== null && !in_array($targetUserId, $visibleUserIds, true)) {
            $targetUserId = $user->id;
        }

        if ($visibleUserIds !== null && count($visibleUserIds) === 1 && in_array($user->id, $visibleUserIds, true)) {
            $targetUserId = $user->id;
        }

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
            'lead:id,company_name,contact_name,mobile_number,email',
            'user:id,name',
            'outCome:id,name',
            'outComeSubCategory:id,name',
        ])->whereBetween('called_at', [$startDate, $endDate])->latest('called_at');

        if ($targetUserId) {
            $query->where('user_id', $targetUserId);
        } elseif ($visibleUserIds !== null) {
            $query->whereIn('user_id', $visibleUserIds);
        }

        if ($request->filled('branch_id')) {
            $branchId = (int) $request->input('branch_id');
            $query->whereHas('lead', function ($lq) use ($branchId) {
                $lq->where('branch_id', $branchId);
            });
        }

        $metricTitle = 'Call Details';

        switch ($metric) {
            case 'new_calls':
            case 'total_new_calls':
                $metricTitle = 'Total New Calls (Fresh Outreach)';
                $query->whereNotExists(function ($q) use ($startDate) {
                    $q->select(DB::raw(1))
                        ->from('lead_call_updates as prev')
                        ->whereColumn('prev.lead_id', 'lead_call_updates.lead_id')
                        ->where('prev.called_at', '<', $startDate);
                })->whereIn('lead_call_updates.id', function ($sub) use ($startDate, $endDate, $targetUserId, $visibleUserIds) {
                    $sub->select(DB::raw('MAX(id)'))
                        ->from('lead_call_updates')
                        ->whereBetween('called_at', [$startDate, $endDate]);
                    if ($targetUserId) {
                        $sub->where('user_id', $targetUserId);
                    } elseif ($visibleUserIds !== null) {
                        $sub->whereIn('user_id', $visibleUserIds);
                    }
                    $sub->groupBy('lead_id');
                });
                break;

            case 'followup_calls':
                $metricTitle = 'Followup Calls (Pipeline Leads)';
                $query->whereExists(function ($q) use ($startDate) {
                    $q->select(DB::raw(1))
                        ->from('lead_call_updates as prev')
                        ->whereColumn('prev.lead_id', 'lead_call_updates.lead_id')
                        ->where('prev.called_at', '<', $startDate);
                })->whereIn('lead_call_updates.id', function ($sub) use ($startDate, $endDate, $targetUserId, $visibleUserIds) {
                    $sub->select(DB::raw('MAX(id)'))
                        ->from('lead_call_updates')
                        ->whereBetween('called_at', [$startDate, $endDate]);
                    if ($targetUserId) {
                        $sub->where('user_id', $targetUserId);
                    } elseif ($visibleUserIds !== null) {
                        $sub->whereIn('user_id', $visibleUserIds);
                    }
                    $sub->groupBy('lead_id');
                });
                break;

            case 'onetime_calls':
            case 'all_onetime_calls':
                $metricTitle = 'All One Time Calls (No Future Follow-up)';
                $query->where(function ($q) {
                    $q->whereNull('next_follow_up')
                      ->orWhere('outcome', '9')
                      ->orWhere('outcome', '6')
                      ->orWhere('outcome', 'not_interested')
                      ->orWhere('outcome', 'closed');
                })->whereIn('lead_call_updates.id', function ($sub) use ($startDate, $endDate, $targetUserId, $visibleUserIds) {
                    $sub->select(DB::raw('MAX(id)'))
                        ->from('lead_call_updates')
                        ->whereBetween('called_at', [$startDate, $endDate]);
                    if ($targetUserId) {
                        $sub->where('user_id', $targetUserId);
                    } elseif ($visibleUserIds !== null) {
                        $sub->whereIn('user_id', $visibleUserIds);
                    }
                    $sub->groupBy('lead_id');
                });
                break;

            case 'unique_calls':
                $metricTitle = 'Unique Calls (Distinct Leads Contacted)';
                $query->whereIn('lead_call_updates.id', function ($sub) use ($startDate, $endDate, $targetUserId, $visibleUserIds) {
                    $sub->select(DB::raw('MAX(id)'))
                        ->from('lead_call_updates')
                        ->whereBetween('called_at', [$startDate, $endDate]);
                    if ($targetUserId) {
                        $sub->where('user_id', $targetUserId);
                    } elseif ($visibleUserIds !== null) {
                        $sub->whereIn('user_id', $visibleUserIds);
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

        $calls = $query->limit(300)->get();

        $rows = $calls->map(function ($call) {
            $lead = $call->lead;
            $leadId = $lead ? 'LD-' . str_pad($lead->id, 4, '0', STR_PAD_LEFT) : 'N/A';
            $companyName = $lead?->company_name ?: ($lead?->contact_name ?: 'Unknown Lead');
            $contactName = $lead?->contact_name ?: '—';
            $mobileNumber = $lead?->mobile_number ?: '—';
            $leadUrl = $lead ? url('/leads/' . $lead->id) : '#';

            return [
                'id' => $call->id,
                'lead_id' => $leadId,
                'lead_url' => $leadUrl,
                'company_name' => $companyName,
                'contact_name' => $contactName,
                'mobile_number' => $mobileNumber,
                'caller_name' => $call->user?->name ?? 'Unknown',
                'called_time' => $call->called_at ? $call->called_at->format('h:i A') : '—',
                'call_type' => $call->call_type_label ?? ucfirst((string) $call->call_type),
                'outcome' => $call->outcome_label,
                'outcome_subcategory' => $call->outcome_subcategory_label,
                'outcome_color' => $call->outcome_color,
                'next_follow_up' => $call->next_follow_up ? $call->next_follow_up->format('d M Y') . ($call->followup_time ? ' (' . $call->followup_time . ')' : '') : '—',
                'notes' => $call->notes ?: '—',
            ];
        });

        return response()->json([
            'success' => true,
            'metric' => $metric,
            'title' => $metricTitle,
            'date' => $dateLabel,
            'total' => $rows->count(),
            'rows' => $rows,
        ]);
    }

    /**
     * Store a newly created daily closing update.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $currentUser = auth()->user();

        $validated = $request->validate([
            'closing_date'                    => ['required', 'date', 'before_or_equal:today'],
            'user_id'                         => ['nullable', 'integer', 'exists:users,id'],
            'closing_notes'                   => ['required', 'string', 'min:5', 'max:10000'],
            'is_on_leave_tomorrow'            => ['nullable'],
            'tomorrow_plans'                  => ['nullable', 'array'],
            'tomorrow_plans.*.company_name'   => ['nullable', 'string', 'max:255'],
            'tomorrow_plans.*.product_name'   => ['nullable', 'string', 'max:255'],
            'tomorrow_plans.*.expected_value' => ['nullable', 'numeric', 'min:0'],
        ], [
            'closing_date.before_or_equal' => 'Cannot submit closing for future dates.',
            'closing_notes.required'       => 'Please enter your day closing update notes.',
        ]);

        $visibleUserIds = $this->resolveVisibleUserIds($currentUser);

        $targetUserId = !empty($validated['user_id'])
            ? (int) $validated['user_id']
            : $currentUser->id;

        if ($targetUserId !== $currentUser->id && $visibleUserIds !== null && !in_array($targetUserId, $visibleUserIds, true)) {
            $targetUserId = $currentUser->id;
        }

        $targetUser = User::find($targetUserId) ?: $currentUser;
        $closingDate = Carbon::parse($validated['closing_date'])->toDateString();

        // Calculate verified call stats directly from system logs
        $stats = $this->calculateCallStats($targetUserId, $closingDate, $visibleUserIds);

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

        // Check if an existing record exists for this user and date
        $closing = SalesDailyClosing::where('user_id', $targetUserId)
            ->whereDate('closing_date', $closingDate)
            ->first();

        if ($closing) {
            $closing->update([
                'total_calls'          => $stats['total_calls'],
                'unique_calls'         => $stats['unique_calls'],
                'new_calls'            => $stats['new_calls'],
                'followup_calls'       => $stats['followup_calls'],
                'onetime_calls'        => $stats['onetime_calls'],
                'converted_count'      => $stats['converted_count'],
                'quotations_count'     => $stats['quotations_count'],
                'closing_notes'        => $validated['closing_notes'],
                'is_on_leave_tomorrow' => $isOnLeave,
                'tomorrow_plans'       => $cleanedPlans,
                'plan_for_tomorrow'    => $planSummary,
            ]);
        } else {
            $closing = SalesDailyClosing::create([
                'company_id'           => $targetUser->company_id,
                'branch_id'            => $targetUser->branch_id,
                'user_id'              => $targetUserId,
                'closing_date'         => $closingDate,
                'total_calls'          => $stats['total_calls'],
                'unique_calls'         => $stats['unique_calls'],
                'new_calls'            => $stats['new_calls'],
                'followup_calls'       => $stats['followup_calls'],
                'onetime_calls'        => $stats['onetime_calls'],
                'converted_count'      => $stats['converted_count'],
                'quotations_count'     => $stats['quotations_count'],
                'closing_notes'        => $validated['closing_notes'],
                'is_on_leave_tomorrow' => $isOnLeave,
                'tomorrow_plans'       => $cleanedPlans,
                'plan_for_tomorrow'    => $planSummary,
                'status'               => 'submitted',
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Sales Day Closing saved successfully.',
                'closing' => $closing,
            ]);
        }

        return redirect()->route('crm.day-closing.index', ['date' => $closingDate])
            ->with('success', 'Day Closing Update submitted successfully.');
    }

    /**
     * Update an existing daily closing update.
     */
    public function update(Request $request, SalesDailyClosing $closing): RedirectResponse|JsonResponse
    {
        $currentUser = auth()->user();
        $visibleUserIds = $this->resolveVisibleUserIds($currentUser);

        // Check authorization
        if ($closing->user_id !== $currentUser->id && $visibleUserIds !== null && !in_array($closing->user_id, $visibleUserIds, true)) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        $validated = $request->validate([
            'closing_notes'                   => ['required', 'string', 'min:5', 'max:10000'],
            'is_on_leave_tomorrow'            => ['nullable'],
            'tomorrow_plans'                  => ['nullable', 'array'],
            'tomorrow_plans.*.company_name'   => ['nullable', 'string', 'max:255'],
            'tomorrow_plans.*.product_name'   => ['nullable', 'string', 'max:255'],
            'tomorrow_plans.*.expected_value' => ['nullable', 'numeric', 'min:0'],
        ], [
            'closing_notes.required' => 'Please enter your day closing update notes.',
        ]);

        // Re-check live call stats
        $stats = $this->calculateCallStats($closing->user_id, $closing->closing_date->toDateString(), $visibleUserIds);

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

        $closing->update([
            'total_calls'          => $stats['total_calls'],
            'unique_calls'         => $stats['unique_calls'],
            'new_calls'            => $stats['new_calls'],
            'followup_calls'       => $stats['followup_calls'],
            'onetime_calls'        => $stats['onetime_calls'],
            'converted_count'      => $stats['converted_count'],
            'quotations_count'     => $stats['quotations_count'],
            'closing_notes'        => $validated['closing_notes'],
            'is_on_leave_tomorrow' => $isOnLeave,
            'tomorrow_plans'       => $cleanedPlans,
            'plan_for_tomorrow'    => $planSummary,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Day Closing Update updated successfully.',
                'closing' => $closing,
            ]);
        }

        return back()->with('success', 'Day Closing Update updated successfully.');
    }

    /**
     * Update review status (Approved / Reviewed) by Manager / TL.
     */
    public function updateStatus(Request $request, SalesDailyClosing $closing): JsonResponse|RedirectResponse
    {
        $currentUser = auth()->user();
        $visibleUserIds = $this->resolveVisibleUserIds($currentUser);

        if ($closing->user_id !== $currentUser->id && $visibleUserIds !== null && !in_array($closing->user_id, $visibleUserIds, true)) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:submitted,reviewed,approved'],
        ]);

        $closing->update([
            'status'      => $validated['status'],
            'reviewed_by' => $currentUser->id,
            'reviewed_at' => now(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully.',
                'status' => $closing->status,
            ]);
        }

        return back()->with('success', 'Closing status updated.');
    }

    /**
     * Delete a daily closing entry.
     */
    public function destroy(SalesDailyClosing $closing): RedirectResponse|JsonResponse
    {
        $currentUser = auth()->user();
        $visibleUserIds = $this->resolveVisibleUserIds($currentUser);

        if ($closing->user_id !== $currentUser->id && $visibleUserIds !== null && !in_array($closing->user_id, $visibleUserIds, true)) {
            abort(403);
        }

        $closing->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Record deleted.']);
        }

        return back()->with('success', 'Day Closing record removed.');
    }

    /**
     * Core calculation logic for Call Metrics and Sales KPIs.
     *
     * 1. Total Calls Made
     * 2. Unique Calls (Distinct leads called on that date)
     * 3. Total New Calls (Calls to leads with NO prior calls before that date)
     * 4. Followup Calls (Calls to leads WITH prior calls before that date)
     * 5. All One-Time Calls (Calls where no future follow-up was scheduled or outcome closed)
     * 6. Converted / Won Leads count on that date
     * 7. Quotations Created count on that date
     */
    private function calculateCallStats(?int $userId, string|array $dateRange, ?array $allowedUserIds = null, ?int $branchId = null): array
    {
        $applyDate = function($q, $col = 'called_at') use ($dateRange) {
            if (is_array($dateRange) && count($dateRange) === 2 && !empty($dateRange[0]) && !empty($dateRange[1])) {
                $q->whereBetween($col, [$dateRange[0], $dateRange[1]]);
            } elseif (is_string($dateRange) && !empty($dateRange)) {
                $q->whereDate($col, $dateRange);
            }
        };

        $applyUser = function($q, $col = 'user_id') use ($userId, $allowedUserIds) {
            if ($userId) {
                $q->where($col, $userId);
            } elseif ($allowedUserIds !== null) {
                $q->whereIn($col, $allowedUserIds);
            }
        };

        $applyBranch = function($q, $relation = 'lead') use ($branchId) {
            if ($branchId) {
                if ($relation) {
                    $q->whereHas($relation, fn($lq) => $lq->where('branch_id', $branchId));
                } else {
                    $q->where('branch_id', $branchId);
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
        $newCallsQuery = LeadCallUpdate::query();
        $applyDate($newCallsQuery, 'called_at');
        $applyUser($newCallsQuery, 'user_id');
        $applyBranch($newCallsQuery, 'lead');
        $newCallsQuery->whereNotExists(function ($q) use ($refDate) {
            $q->select(DB::raw(1))
                ->from('lead_call_updates as prev')
                ->whereColumn('prev.lead_id', 'lead_call_updates.lead_id')
                ->whereDate('prev.called_at', '<', $refDate);
        });
        $newCalls = (int) $newCallsQuery->distinct('lead_id')->count('lead_id');

        // 4. Followup Calls
        $followupCallsQuery = LeadCallUpdate::query();
        $applyDate($followupCallsQuery, 'called_at');
        $applyUser($followupCallsQuery, 'user_id');
        $applyBranch($followupCallsQuery, 'lead');
        $followupCallsQuery->whereExists(function ($q) use ($refDate) {
            $q->select(DB::raw(1))
                ->from('lead_call_updates as prev')
                ->whereColumn('prev.lead_id', 'lead_call_updates.lead_id')
                ->whereDate('prev.called_at', '<', $refDate);
        });
        $followupCalls = (int) $followupCallsQuery->distinct('lead_id')->count('lead_id');

        // 5. One-time Calls
        $onetimeCallsQuery = LeadCallUpdate::query();
        $applyDate($onetimeCallsQuery, 'called_at');
        $applyUser($onetimeCallsQuery, 'user_id');
        $applyBranch($onetimeCallsQuery, 'lead');
        $onetimeCallsQuery->where(function ($q) {
            $q->whereNull('next_follow_up')
              ->orWhere('outcome', '9')
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
        if ($branchId) {
            $convertedQuery->where('branch_id', $branchId);
        }
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
            'total_calls'      => $totalCalls,
            'unique_calls'     => $uniqueCalls,
            'new_calls'        => $newCalls,
            'followup_calls'   => $followupCalls,
            'onetime_calls'    => $onetimeCalls,
            'converted_count'  => $convertedCount,
            'quotations_count' => $quotationsCount,
        ];
    }

    /**
     * Resolve visible user IDs based on exact role rules:
     * - Company Admin / Super Admin / CBO: null (all company data)
     * - Branch Admin: all users in their branches
     * - Branch Manager: all users mapped under them (descendants) + self
     * - Sales TL: all users mapped under them (descendants) + self
     * - Sales Executive: only own data [self]
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
     * Normalize tomorrow plans array from request input.
     */
    protected function normalizeTomorrowPlans(mixed $rawPlans): array
    {
        if (!is_array($rawPlans)) {
            return [];
        }

        // If items are fragmented (e.g. 0=>['company_name'=>'x'], 1=>['product_name'=>'y'], 2=>['expected_value'=>100])
        $isFragmented = false;
        if (count($rawPlans) > 1) {
            $onlyOneKeyCount = 0;
            foreach ($rawPlans as $item) {
                if (is_array($item)) {
                    $setCount = count(array_filter($item, fn($v) => $v !== null && $v !== '' && $v !== 0 && $v !== '0'));
                    if ($setCount <= 1) {
                        $onlyOneKeyCount++;
                    }
                }
            }
            if ($onlyOneKeyCount === count($rawPlans)) {
                $isFragmented = true;
            }
        }

        $cleanedPlans = [];

        if ($isFragmented) {
            $current = [];
            foreach ($rawPlans as $item) {
                if (!is_array($item)) continue;
                if (isset($item['company_name'])) {
                    if (!empty($current)) {
                        $cleanedPlans[] = $current;
                        $current = [];
                    }
                    $current['company_name'] = trim((string)$item['company_name']);
                    $current['product_name'] = '';
                    $current['expected_value'] = 0;
                }
                if (isset($item['product_name'])) {
                    $current['product_name'] = trim((string)$item['product_name']);
                    if (!isset($current['company_name'])) $current['company_name'] = '';
                    if (!isset($current['expected_value'])) $current['expected_value'] = 0;
                }
                if (isset($item['expected_value'])) {
                    $current['expected_value'] = floatval($item['expected_value']);
                    if (!isset($current['company_name'])) $current['company_name'] = '';
                    if (!isset($current['product_name'])) $current['product_name'] = '';
                }
            }
            if (!empty($current)) {
                $cleanedPlans[] = $current;
            }
        } else {
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
        }

        return $cleanedPlans;
    }
}

