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
     * Mobile API: List Sales Day Closings with filters and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $today = now()->toDateString();

        $fromDate = $request->input('from_date') ?: $request->input('date_from');
        $toDate = $request->input('to_date') ?: $request->input('date_to');
        $selectedDate = $toDate ?: ($fromDate ?: ($request->input('date') ?: $today));

        $isAdminLike = $user->hasAdminLikeRole() || $user->isSuperAdmin() || $user->isCompanyAdmin() || $user->isBranchAdmin();
        $isTlLike = $user->hasTlLikeRole();

        // Query visible users for filter dropdown
        $assignableUsers = $this->visibility->visibleAssignableUsers();
        $assignableUserIds = $assignableUsers->pluck('id')->all();

        $branches = Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        // Base query for SalesDailyClosing submissions
        $query = SalesDailyClosing::with([
            'user:id,name,email,branch_id,designation',
            'reviewer:id,name',
        ])->latest('closing_date')->latest('id');

        // Apply visibility permissions
        if (!$isAdminLike) {
            if ($isTlLike) {
                $subordinateIds = $user->managedUsers()->pluck('users.id')->push($user->id)->all();
                $query->whereIn('user_id', $subordinateIds);
            } else {
                $query->where('user_id', $user->id);
            }
        } elseif ($request->filled('user_id') && (int) $request->input('user_id') > 0) {
            $query->where('user_id', (int) $request->input('user_id'));
        }

        if ($request->filled('branch_id') && (int) $request->input('branch_id') > 0) {
            $branchId = (int) $request->input('branch_id');
            $query->whereHas('user', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
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
                $q->where('closing_notes', 'like', "%{$search}%")
                  ->orWhere('plan_for_tomorrow', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = min((int) ($request->input('per_page', 20)), 100);
        $paginator = $query->paginate($perPage);

        // Calculate summary call stats for selected user & date
        $targetUserId = $request->filled('user_id') ? (int) $request->input('user_id') : ($isAdminLike ? null : $user->id);
        $stats = $this->calculateCallStats($targetUserId, $selectedDate);

        return response()->json([
            'success' => true,
            'can_create' => true,
            'can_review' => $isAdminLike || $isTlLike,
            'selected_date' => $selectedDate,
            'stats' => $stats,
            'kpi_stats' => $stats,
            'branches' => $branches,
            'users' => $assignableUsers->map(fn($u) => ['id' => $u->id, 'name' => $u->name]),
            'data' => collect($paginator->items())->map(function ($item) {
                return [
                    'id' => $item->id,
                    'user_id' => $item->user_id,
                    'user_name' => $item->user?->name ?? 'Unknown',
                    'user_email' => $item->user?->email,
                    'user_designation' => $item->user?->designation,
                    'closing_date' => $item->closing_date ? Carbon::parse($item->closing_date)->toDateString() : '',
                    'total_calls' => (int) $item->total_calls_made,
                    'total_calls_made' => (int) $item->total_calls_made,
                    'unique_calls' => (int) $item->unique_calls,
                    'new_calls' => (int) $item->new_calls,
                    'followup_calls' => (int) $item->followup_calls,
                    'onetime_calls' => (int) $item->all_one_time_calls,
                    'all_one_time_calls' => (int) $item->all_one_time_calls,
                    'converted_count' => (int) $item->converted_leads_count,
                    'converted_leads_count' => (int) $item->converted_leads_count,
                    'quotations_count' => (int) $item->quotations_created_count,
                    'quotations_created_count' => (int) $item->quotations_created_count,
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
            }),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Mobile API: Get Call Stats for user and date.
     */
    public function getStats(Request $request): JsonResponse
    {
        $user = auth()->user();
        $targetUserId = $request->filled('user_id') ? (int) $request->input('user_id') : $user->id;
        $dateStr = $request->input('date', now()->toDateString());

        $isAdminLike = $user->hasAdminLikeRole() || $user->isSuperAdmin() || $user->isCompanyAdmin() || $user->isBranchAdmin();
        $isTlLike = $user->hasTlLikeRole();

        if (!$isAdminLike && !$isTlLike && $targetUserId !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        try {
            $date = Carbon::parse($dateStr)->toDateString();
        } catch (\Throwable) {
            $date = now()->toDateString();
        }

        $stats = $this->calculateCallStats($targetUserId, $date);

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
            'exists' => (bool) $existing,
            'closing' => $existingData,
        ]);
    }

    /**
     * Mobile API: Fetch call log details for metric card.
     */
    public function getCallDetails(Request $request): JsonResponse
    {
        $user = auth()->user();
        $metric = trim((string) $request->input('metric', 'unique_calls'));
        $date = trim((string) $request->input('date', now()->toDateString()));
        $targetUserId = $request->filled('user_id') ? (int) $request->input('user_id') : null;

        $isAdminLike = $user->hasAdminLikeRole() || $user->isSuperAdmin() || $user->isCompanyAdmin() || $user->isBranchAdmin();
        $isTlLike = $user->hasTlLikeRole();

        if (!$isAdminLike && !$isTlLike) {
            $targetUserId = $user->id;
        }

        try {
            $date = Carbon::parse($date)->toDateString();
        } catch (\Throwable) {
            $date = now()->toDateString();
        }

        $query = LeadCallUpdate::with([
            'lead:id,company_name,contact_name,mobile_number,email',
            'user:id,name',
            'outCome:id,name',
            'outComeSubCategory:id,name',
        ])->whereDate('called_at', $date)->latest('called_at');

        if ($targetUserId) {
            $query->where('user_id', $targetUserId);
        } elseif ($isTlLike && !$isAdminLike) {
            $subordinateIds = $user->managedUsers()->pluck('users.id')->push($user->id)->all();
            $query->whereIn('user_id', $subordinateIds);
        }

        if ($request->filled('branch_id')) {
            $branchId = (int) $request->input('branch_id');
            $query->whereHas('user', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        $nextFollowupDateCol = Schema::hasColumn('lead_call_updates', 'next_follow_up')
            ? 'next_follow_up'
            : (Schema::hasColumn('lead_call_updates', 'next_followup_date') ? 'next_followup_date' : null);
        $nextFollowupTimeCol = Schema::hasColumn('lead_call_updates', 'followup_time')
            ? 'followup_time'
            : (Schema::hasColumn('lead_call_updates', 'next_followup_time') ? 'next_followup_time' : null);

        // Filter according to selected metric card
        switch ($metric) {
            case 'unique_calls':
                $query->whereIn('id', function ($sub) use ($date, $targetUserId) {
                    $sub->select(DB::raw('MAX(id)'))
                        ->from('lead_call_updates')
                        ->whereDate('called_at', $date);
                    if ($targetUserId) {
                        $sub->where('user_id', $targetUserId);
                    }
                    $sub->groupBy('lead_id');
                });
                break;

            case 'new_calls':
                $query->whereNotExists(function ($q) use ($date) {
                    $q->select(DB::raw(1))
                        ->from('lead_call_updates as prev')
                        ->whereColumn('prev.lead_id', 'lead_call_updates.lead_id')
                        ->whereDate('prev.called_at', '<', $date);
                });
                break;

            case 'followup_calls':
                $query->whereExists(function ($q) use ($date) {
                    $q->select(DB::raw(1))
                        ->from('lead_call_updates as prev')
                        ->whereColumn('prev.lead_id', 'lead_call_updates.lead_id')
                        ->whereDate('prev.called_at', '<', $date);
                });
                break;

            case 'one_time_calls':
                if ($nextFollowupDateCol) {
                    $query->whereNull($nextFollowupDateCol);
                }
                if ($nextFollowupTimeCol) {
                    $query->whereNull($nextFollowupTimeCol);
                }
                break;

            case 'total_calls':
            default:
                break;
        }

        $perPage = min((int) ($request->input('per_page', 30)), 100);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'metric' => $metric,
            'date' => $date,
            'total' => $paginator->total(),
            'data' => collect($paginator->items())->map(function ($c) use ($nextFollowupDateCol, $nextFollowupTimeCol) {
                $dateVal = $nextFollowupDateCol ? $c->{$nextFollowupDateCol} : null;
                $timeVal = $nextFollowupTimeCol ? $c->{$nextFollowupTimeCol} : null;
                $nextFollowup = $dateVal ? (Carbon::parse($dateVal)->format('Y-m-d') . ($timeVal ? ' ' . $timeVal : '')) : null;

                return [
                    'id' => $c->id,
                    'lead_id' => $c->lead_id,
                    'company_name' => $c->lead?->company_name ?? 'N/A',
                    'contact_name' => $c->lead?->contact_name ?? '',
                    'user_name' => $c->user?->name ?? 'Unknown',
                    'outcome' => $c->outCome?->name ?? 'N/A',
                    'sub_outcome' => $c->outComeSubCategory?->name,
                    'called_at' => $c->called_at ? Carbon::parse($c->called_at)->format('h:i A') : '',
                    'remarks' => $c->remarks ?? '',
                    'next_followup' => $nextFollowup,
                ];
            }),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Mobile API: Create Sales Day Closing.
     */
    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();
        $isAdminLike = $user->hasAdminLikeRole() || $user->isSuperAdmin() || $user->isCompanyAdmin() || $user->isBranchAdmin();
        $isTlLike = $user->hasTlLikeRole();

        $targetUserId = $user->id;
        if ($request->filled('user_id') && ($isAdminLike || $isTlLike)) {
            $targetUserId = (int) $request->input('user_id');
        }

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

        $stats = $this->calculateCallStats($targetUserId, $date);

        $cleanedPlans = $this->cleanTomorrowPlans($validated['tomorrow_plans'] ?? []);
        $totalExpected = array_sum(array_column($cleanedPlans, 'expected_value'));

        $closing = SalesDailyClosing::create([
            'user_id' => $targetUserId,
            'closing_date' => $date,
            'total_calls_made' => $stats['total_calls_made'],
            'unique_calls' => $stats['unique_calls'],
            'new_calls' => $stats['new_calls'],
            'followup_calls' => $stats['followup_calls'],
            'all_one_time_calls' => $stats['all_one_time_calls'],
            'converted_leads_count' => $stats['converted_leads_count'],
            'quotations_created_count' => $stats['quotations_created_count'],
            'closing_notes' => $validated['closing_notes'] ?? null,
            'is_on_leave_tomorrow' => (bool) ($validated['is_on_leave_tomorrow'] ?? false),
            'tomorrow_plans' => $cleanedPlans,
            'total_expected_value' => $totalExpected,
            'plan_for_tomorrow' => $validated['plan_for_tomorrow'] ?? null,
            'status' => 'submitted',
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
        if ($closing->user_id !== $user->id && !$user->hasAdminLikeRole()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'closing_notes' => ['nullable', 'string'],
            'is_on_leave_tomorrow' => ['nullable', 'boolean'],
            'tomorrow_plans' => ['nullable', 'array'],
            'plan_for_tomorrow' => ['nullable', 'string'],
        ]);

        $cleanedPlans = isset($validated['tomorrow_plans']) ? $this->cleanTomorrowPlans($validated['tomorrow_plans']) : $closing->tomorrow_plans;
        $totalExpected = array_sum(array_column($cleanedPlans ?? [], 'expected_value'));

        $closing->update([
            'closing_notes' => $validated['closing_notes'] ?? $closing->closing_notes,
            'is_on_leave_tomorrow' => isset($validated['is_on_leave_tomorrow']) ? (bool) $validated['is_on_leave_tomorrow'] : $closing->is_on_leave_tomorrow,
            'tomorrow_plans' => $cleanedPlans,
            'total_expected_value' => $totalExpected,
            'plan_for_tomorrow' => $validated['plan_for_tomorrow'] ?? $closing->plan_for_tomorrow,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Day closing updated successfully.',
            'data' => [
                'id' => $closing->id,
                'status' => $closing->status,
            ],
        ]);
    }

    /**
     * Mobile API: Review/Approve Sales Day Closing.
     */
    public function updateStatus(Request $request, SalesDailyClosing $closing): JsonResponse
    {
        $user = auth()->user();
        if (!$user->hasAdminLikeRole() && !$user->hasTlLikeRole()) {
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
        if ($closing->user_id !== $user->id && !$user->hasAdminLikeRole()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $closing->delete();

        return response()->json(['success' => true, 'message' => 'Record deleted successfully.']);
    }

    private function calculateCallStats(?int $userId, string $date): array
    {
        $totalCallsQuery = LeadCallUpdate::whereDate('called_at', $date);
        if ($userId) {
            $totalCallsQuery->where('user_id', $userId);
        }
        $totalCalls = (int) $totalCallsQuery->count();

        $uniqueCallsQuery = LeadCallUpdate::whereDate('called_at', $date);
        if ($userId) {
            $uniqueCallsQuery->where('user_id', $userId);
        }
        $uniqueCalls = (int) $uniqueCallsQuery->distinct('lead_id')->count('lead_id');

        $newCallsQuery = LeadCallUpdate::whereDate('called_at', $date)
            ->whereNotExists(function ($q) use ($date) {
                $q->select(DB::raw(1))
                    ->from('lead_call_updates as prev')
                    ->whereColumn('prev.lead_id', 'lead_call_updates.lead_id')
                    ->whereDate('prev.called_at', '<', $date);
            });
        if ($userId) {
            $newCallsQuery->where('user_id', $userId);
        }
        $newCalls = (int) $newCallsQuery->distinct('lead_id')->count('lead_id');

        $followupCalls = max(0, $uniqueCalls - $newCalls);

        $nextFollowupDateCol = Schema::hasColumn('lead_call_updates', 'next_follow_up')
            ? 'next_follow_up'
            : (Schema::hasColumn('lead_call_updates', 'next_followup_date') ? 'next_followup_date' : null);
        $nextFollowupTimeCol = Schema::hasColumn('lead_call_updates', 'followup_time')
            ? 'followup_time'
            : (Schema::hasColumn('lead_call_updates', 'next_followup_time') ? 'next_followup_time' : null);

        $oneTimeQuery = LeadCallUpdate::whereDate('called_at', $date);
        if ($nextFollowupDateCol) {
            $oneTimeQuery->whereNull($nextFollowupDateCol);
        }
        if ($nextFollowupTimeCol) {
            $oneTimeQuery->whereNull($nextFollowupTimeCol);
        }
        if ($userId) {
            $oneTimeQuery->where('user_id', $userId);
        }
        $oneTimeCalls = (int) $oneTimeQuery->count();

        $convertedStatusIds = LeadStatus::whereRaw('LOWER(name) in (?, ?)', ['converted', 'won'])->pluck('id')->toArray();
        if (empty($convertedStatusIds)) {
            $convertedStatusIds = [5, 15];
        }

        $wonQuery = Lead::where(function ($q) use ($convertedStatusIds) {
            $q->whereIn('lead_status', ['won', 'converted'])
              ->orWhereIn('lead_status_id', $convertedStatusIds);
        })->whereDate('updated_at', $date);

        if ($userId) {
            $wonQuery->where('assigned_to', $userId);
        }
        $convertedLeads = (int) $wonQuery->count();

        $quotationsQuery = Quotation::whereDate('created_at', $date);
        if ($userId) {
            $quotationsQuery->where('created_by', $userId);
        }
        $quotationsCreated = (int) $quotationsQuery->count();

        return [
            'total_calls' => $totalCalls,
            'total_calls_made' => $totalCalls,
            'unique_calls' => $uniqueCalls,
            'new_calls' => $newCalls,
            'followup_calls' => $followupCalls,
            'onetime_calls' => $oneTimeCalls,
            'all_one_time_calls' => $oneTimeCalls,
            'converted_count' => $convertedLeads,
            'converted_leads_count' => $convertedLeads,
            'quotations_count' => $quotationsCreated,
            'quotations_created_count' => $quotationsCreated,
        ];
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
