<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\AdAccountMaster;
use App\Models\AdBudgetRequest;
use App\Models\AdSpend;
use App\Models\Branch;
use App\Models\DomainRecord;
use App\Models\HostingRecord;
use App\Models\LeadProductPayment;
use App\Models\User;
use App\Mail\AdBudgetNotificationMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Mobile API Controller for the Accounts Module.
 *
 * Dedicated strictly to the mobile application, maintaining parity
 * with web business logic without modifying any web files or shared controllers.
 */
class AccountsApiController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    //  1. ACCOUNTS DASHBOARD
    // ─────────────────────────────────────────────────────────────────────────

    public function dashboard(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;

            $activeAdAccountsCount = AdAccountMaster::when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->where('status', 'active')
                ->count();

            $clientRequestsCount = AdBudgetRequest::when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->where('type', 'client')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();

            $partnerRequestsCount = AdBudgetRequest::when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->where('type', 'partner')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();

            $pendingApprovalsCount = AdBudgetRequest::when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->whereIn('status', ['tl_pending', 'accounts_pending'])
                ->count();

            $pendingAccountsCount = AdBudgetRequest::when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->where('status', 'accounts_pending')
                ->count();

            $totalClientBudgetAmount = (float) AdBudgetRequest::when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->where('type', 'client')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount');

            $recentRequests = AdBudgetRequest::with(['adAccount', 'requester', 'tlApprover', 'approver'])
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->latest()
                ->take(10)
                ->get()
                ->map(fn($item) => $this->formatBudgetRequest($item));

            return response()->json([
                'status' => true,
                'data'   => [
                    'active_ad_accounts_count'    => $activeAdAccountsCount,
                    'client_requests_count'       => $clientRequestsCount,
                    'partner_requests_count'      => $partnerRequestsCount,
                    'pending_approvals_count'     => $pendingApprovalsCount,
                    'pending_accounts_count'      => $pendingAccountsCount,
                    'total_client_budget_amount'  => $totalClientBudgetAmount,
                    'recent_requests'             => $recentRequests,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts dashboard error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load Accounts dashboard data: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  2. AD BUDGET — FOR CLIENTS
    // ─────────────────────────────────────────────────────────────────────────

    public function adBudgetClients(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;
            $perPage = (int) $request->query('per_page', 15);

            $baseQuery = AdBudgetRequest::where('type', 'client')
                ->when($companyId, fn($q) => $q->where('company_id', $companyId));

            // Summary stats before filtering
            $totalRequested  = (float) (clone $baseQuery)->sum('amount');
            $totalApproved   = (float) (clone $baseQuery)->where('status', 'approved')->sum('approved_amount');
            $pendingTlCount  = (clone $baseQuery)->where('status', 'tl_pending')->count();
            $pendingAccCount = (clone $baseQuery)->where('status', 'accounts_pending')->count();

            $statusCounts = [
                'all'              => (clone $baseQuery)->count(),
                'tl_pending'       => (clone $baseQuery)->where('status', 'tl_pending')->count(),
                'accounts_pending' => (clone $baseQuery)->where('status', 'accounts_pending')->count(),
                'approved'         => (clone $baseQuery)->where('status', 'approved')->count(),
                'rejected'         => (clone $baseQuery)->where('status', 'rejected')->count(),
            ];

            // Apply search & filters
            $query = clone $baseQuery;
            $query->with(['adAccount', 'requester', 'tlApprover', 'approver']);

            if ($request->filled('search')) {
                $search = trim($request->search);
                $query->where(function ($q2) use ($search) {
                    $q2->where('client_name', 'like', "%{$search}%")
                       ->orWhere('remarks', 'like', "%{$search}%")
                       ->orWhere('accounts_remarks', 'like', "%{$search}%")
                       ->orWhereHas('adAccount', function ($q3) use ($search) {
                           $q3->where('account_name', 'like', "%{$search}%")
                              ->orWhere('account_id', 'like', "%{$search}%");
                       });
                });
            }

            if ($request->filled('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->filled('ad_account_id')) {
                $query->where('ad_account_id', $request->ad_account_id);
            }

            $paginated = $query->latest()->paginate($perPage);

            // Active Ad Accounts list for dropdown picker
            $adAccounts = AdAccountMaster::when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->active()
                ->orderBy('account_name')
                ->get()
                ->map(fn($a) => [
                    'id'           => $a->id,
                    'account_name' => $a->account_name,
                    'account_id'   => $a->account_id,
                    'platform'     => $a->platform,
                ]);

            return response()->json([
                'status'  => true,
                'data'    => [
                    'items'        => collect($paginated->items())->map(fn($item) => $this->formatBudgetRequest($item)),
                    'pagination'   => [
                        'current_page' => $paginated->currentPage(),
                        'last_page'    => $paginated->lastPage(),
                        'per_page'     => $paginated->perPage(),
                        'total'        => $paginated->total(),
                        'has_more'     => $paginated->hasMorePages(),
                    ],
                    'summary'      => [
                        'total_requested'  => $totalRequested,
                        'total_approved'   => $totalApproved,
                        'pending_tl_count' => $pendingTlCount,
                        'pending_acc_count'=> $pendingAccCount,
                        'status_counts'    => $statusCounts,
                    ],
                    'ad_accounts'  => $adAccounts,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile AdBudget clients error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load client budget requests: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function raiseClientBudget(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ad_account_id'  => 'required|exists:ad_account_masters,id',
            'amount'         => 'required|numeric|min:1',
            'selected_dates' => 'required',
            'client_name'    => 'nullable|string|max:255',
            'remarks'        => 'nullable|string',
        ]);

        $dates = [];
        if (is_array($request->selected_dates)) {
            $dates = array_values(array_filter($request->selected_dates));
        } elseif (is_string($request->selected_dates)) {
            $dates = array_values(array_filter(array_map('trim', explode(',', $request->selected_dates))));
        }

        if (empty($dates)) {
            return response()->json([
                'status'  => false,
                'message' => 'Please select at least one valid date.',
            ], 422);
        }

        $user = $request->user();
        $initialStatus = ($user->hasAdminLikeRole() || $user->hasTlLikeRole()) ? 'accounts_pending' : 'tl_pending';

        $validated['selected_dates'] = $dates;
        $validated['type']           = 'client';
        $validated['requested_by']   = $user->id;
        $validated['company_id']     = $user?->company_id;
        $validated['branch_id']      = $user?->branch_id;
        $validated['status']         = $initialStatus;

        if ($initialStatus === 'accounts_pending') {
            $validated['tl_approved_by'] = $user->id;
            $validated['tl_approved_at'] = now();
        }

        $item = AdBudgetRequest::create($validated);
        $this->sendNotificationMail($item, 'request_created');

        return response()->json([
            'status'  => true,
            'message' => 'Ad Budget Request raised successfully!',
            'data'    => $this->formatBudgetRequest($item->load(['adAccount', 'requester'])),
        ]);
    }

    public function tlApproveAdBudget(Request $request, AdBudgetRequest $adBudgetRequest): JsonResponse
    {
        $action = $request->input('action', 'approve');

        $validated = $request->validate([
            'tl_remarks' => 'required|string|max:1000',
        ], [
            'tl_remarks.required' => 'Please enter remarks before submitting your decision.',
        ]);

        $user = $request->user();

        if ($action === 'reject') {
            $adBudgetRequest->update([
                'status'         => 'rejected',
                'tl_approved_by' => $user->id,
                'tl_approved_at' => now(),
                'tl_remarks'     => $validated['tl_remarks'],
            ]);

            $this->sendNotificationMail($adBudgetRequest, 'rejected');

            return response()->json([
                'status'  => true,
                'message' => 'Request rejected by DM TL.',
                'data'    => $this->formatBudgetRequest($adBudgetRequest->load(['adAccount', 'requester', 'tlApprover'])),
            ]);
        }

        $adBudgetRequest->update([
            'status'         => 'accounts_pending',
            'tl_approved_by' => $user->id,
            'tl_approved_at' => now(),
            'tl_remarks'     => $validated['tl_remarks'],
        ]);

        $this->sendNotificationMail($adBudgetRequest, 'tl_approved');

        return response()->json([
            'status'  => true,
            'message' => 'Request approved by DM TL and sent to Accounts Team.',
            'data'    => $this->formatBudgetRequest($adBudgetRequest->load(['adAccount', 'requester', 'tlApprover'])),
        ]);
    }

    public function accountsApproveAdBudget(Request $request, AdBudgetRequest $adBudgetRequest): JsonResponse
    {
        $action = $request->input('action', 'approve');
        $user = $request->user();

        if ($action === 'reject') {
            $adBudgetRequest->update([
                'status'      => 'rejected',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'accounts_remarks' => $request->input('accounts_remarks'),
            ]);

            $this->sendNotificationMail($adBudgetRequest, 'rejected');

            return response()->json([
                'status'  => true,
                'message' => 'Request rejected by Accounts Team.',
                'data'    => $this->formatBudgetRequest($adBudgetRequest->load(['adAccount', 'requester', 'tlApprover', 'approver'])),
            ]);
        }

        $validated = $request->validate([
            'payment_date'     => 'required|date',
            'ad_account_id'    => 'required|exists:ad_account_masters,id',
            'approved_amount'  => 'required|numeric|min:0.01',
            'accounts_remarks' => 'nullable|string',
            'attachments.*'    => 'nullable|file|mimes:jpeg,png,jpg,pdf,doc,docx,xlsx,xls|max:10240',
        ]);

        $uploadedPaths = $adBudgetRequest->attachments ?? [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('ad_budget_attachments', 'public');
                $uploadedPaths[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'url'  => Storage::url($path),
                    'size' => $file->getSize(),
                ];
            }
        }

        $adBudgetRequest->update([
            'status'           => 'approved',
            'approved_by'      => $user->id,
            'approved_at'      => now(),
            'payment_date'     => $validated['payment_date'],
            'ad_account_id'    => $validated['ad_account_id'],
            'approved_amount'  => $validated['approved_amount'],
            'accounts_remarks' => $validated['accounts_remarks'] ?? null,
            'attachments'      => $uploadedPaths,
        ]);

        $this->sendNotificationMail($adBudgetRequest, 'accounts_approved');

        return response()->json([
            'status'  => true,
            'message' => 'Ad Budget Request approved and finalized by Accounts Team!',
            'data'    => $this->formatBudgetRequest($adBudgetRequest->load(['adAccount', 'requester', 'tlApprover', 'approver'])),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  3. AD BUDGET — FOR PARTNERS
    // ─────────────────────────────────────────────────────────────────────────

    public function adBudgetPartners(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;
            $perPage = (int) $request->query('per_page', 15);

            $isCompanyAdminOrCbo = $user && (
                $user->isSuperAdmin()
                || $user->isSystemAdmin()
                || $user->isCompanyAdminRole()
                || $user->isCbo()
            );

            $query = LeadProductPayment::with(['lead', 'product', 'branch', 'recordedBy'])
                ->whereIn('payment_type', ['ad_budget_partner', 'ad_budget_for_partner', 'Ad Budget for partner'])
                ->when($companyId, function ($q) use ($companyId) {
                    $q->where(function ($q2) use ($companyId) {
                        $q2->whereHas('lead', fn($l) => $l->where('company_id', $companyId))
                           ->orWhereHas('branch', fn($b) => $b->where('company_id', $companyId));
                    });
                });

            if (! $isCompanyAdminOrCbo) {
                $myBranchIds = $user ? $user->getMyBranchIds() : [];
                if (! empty($myBranchIds)) {
                    $query->whereIn('branch_id', $myBranchIds);
                } else {
                    $query->whereRaw('1 = 0');
                }
            }

            if ($request->filled('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            }
            if ($request->filled('from_date')) {
                $query->whereDate('payment_date', '>=', $request->from_date);
            }
            if ($request->filled('to_date')) {
                $query->whereDate('payment_date', '<=', $request->to_date);
            }

            if ($request->filled('search')) {
                $search = trim($request->search);
                $query->where(function ($q2) use ($search) {
                    $q2->where('reference_number', 'like', "%{$search}%")
                      ->orWhere('notes', 'like', "%{$search}%")
                      ->orWhere('amount', 'like', "%{$search}%")
                      ->orWhereHas('lead', function ($ql) use ($search) {
                          $ql->where('company_name', 'like', "%{$search}%")
                             ->orWhere('contact_name', 'like', "%{$search}%")
                             ->orWhere('mobile_number', 'like', "%{$search}%")
                             ->orWhere('id', 'like', "%{$search}%");
                      })
                      ->orWhereHas('branch', function ($qb) use ($search) {
                          $qb->where('name', 'like', "%{$search}%");
                      })
                      ->orWhereHas('recordedBy', function ($qu) use ($search) {
                          $qu->where('name', 'like', "%{$search}%");
                      });
                });
            }

            // Stats before pagination
            $statsQuery = clone $query;
            $totalAmount = (float) $statsQuery->sum('amount');
            $totalCount  = $statsQuery->count();

            $paginated = $query->orderBy('payment_date', 'desc')
                ->orderBy('id', 'desc')
                ->paginate($perPage);

            // Branches list for filter dropdown
            $branchesQuery = Branch::withoutGlobalScope('branch')->where('is_active', true);
            if ($companyId) {
                $branchesQuery->where('company_id', $companyId);
            }
            if (! $isCompanyAdminOrCbo && $user) {
                $myBranchIds = $user->getMyBranchIds();
                if (! empty($myBranchIds)) {
                    $branchesQuery->whereIn('id', $myBranchIds);
                }
            }
            $branches = $branchesQuery->orderBy('name')->get()->map(fn($b) => [
                'id'   => $b->id,
                'name' => $b->name,
            ]);

            // Ad accounts list for raising partner request
            $adAccounts = AdAccountMaster::when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->active()
                ->orderBy('account_name')
                ->get()
                ->map(fn($a) => [
                    'id'           => $a->id,
                    'account_name' => $a->account_name,
                    'account_id'   => $a->account_id,
                    'platform'     => $a->platform,
                ]);

            return response()->json([
                'status' => true,
                'data'   => [
                    'items'       => collect($paginated->items())->map(fn($p) => [
                        'id'               => $p->id,
                        'lead_id'          => $p->lead_id,
                        'lead_name'        => $p->lead?->company_name ?: $p->lead?->contact_name,
                        'contact_name'     => $p->lead?->contact_name,
                        'mobile_number'    => $p->lead?->mobile_number,
                        'product_name'     => $p->product?->product_name,
                        'branch_name'      => $p->branch?->name,
                        'amount'           => (float) $p->amount,
                        'payment_date'     => $p->payment_date ? date('Y-m-d', strtotime($p->payment_date)) : null,
                        'payment_type'     => $p->payment_type,
                        'payment_mode'     => $p->payment_mode,
                        'reference_number' => $p->reference_number,
                        'notes'            => $p->notes,
                        'recorded_by'      => $p->recordedBy?->name,
                        'created_at'       => $p->created_at?->format('Y-m-d H:i:s'),
                    ]),
                    'pagination'  => [
                        'current_page' => $paginated->currentPage(),
                        'last_page'    => $paginated->lastPage(),
                        'per_page'     => $paginated->perPage(),
                        'total'        => $paginated->total(),
                        'has_more'     => $paginated->hasMorePages(),
                    ],
                    'summary'     => [
                        'total_amount' => $totalAmount,
                        'total_count'  => $totalCount,
                    ],
                    'branches'    => $branches,
                    'ad_accounts' => $adAccounts,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile AdBudget partners error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load partner ad budget payments: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function raisePartnerBudget(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ad_account_id'  => 'required|exists:ad_account_masters,id',
            'amount'         => 'required|numeric|min:1',
            'selected_dates' => 'required',
            'partner_name'   => 'nullable|string|max:255',
            'remarks'        => 'nullable|string',
        ]);

        $dates = [];
        if (is_array($request->selected_dates)) {
            $dates = array_values(array_filter($request->selected_dates));
        } elseif (is_string($request->selected_dates)) {
            $dates = array_values(array_filter(array_map('trim', explode(',', $request->selected_dates))));
        }

        if (empty($dates)) {
            return response()->json([
                'status'  => false,
                'message' => 'Please select at least one valid date.',
            ], 422);
        }

        $user = $request->user();
        $initialStatus = ($user->hasAdminLikeRole() || $user->hasTlLikeRole()) ? 'accounts_pending' : 'tl_pending';

        $validated['selected_dates'] = $dates;
        $validated['client_name']    = $request->partner_name ?? $request->client_name;
        $validated['type']           = 'partner';
        $validated['requested_by']   = $user->id;
        $validated['company_id']     = $user?->company_id;
        $validated['branch_id']      = $user?->branch_id;
        $validated['status']         = $initialStatus;

        if ($initialStatus === 'accounts_pending') {
            $validated['tl_approved_by'] = $user->id;
            $validated['tl_approved_at'] = now();
        }

        $item = AdBudgetRequest::create($validated);
        $this->sendNotificationMail($item, 'request_created');

        return response()->json([
            'status'  => true,
            'message' => 'Partner Ad Budget Request raised successfully!',
            'data'    => $this->formatBudgetRequest($item->load(['adAccount', 'requester'])),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  4. AD SPENT
    // ─────────────────────────────────────────────────────────────────────────

    public function adSpends(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;
            $perPage = (int) $request->query('per_page', 15);

            $query = AdSpend::with(['adAccount', 'creator'])
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->when($request->filled('search'), function ($q) use ($request) {
                    $search = trim($request->search);
                    $q->where(function ($q2) use ($search) {
                        $q2->where('remarks', 'like', "%{$search}%")
                           ->orWhereHas('adAccount', function ($q3) use ($search) {
                               $q3->where('account_name', 'like', "%{$search}%")
                                  ->orWhere('account_id', 'like', "%{$search}%");
                           })
                           ->orWhereHas('creator', function ($q3) use ($search) {
                               $q3->where('name', 'like', "%{$search}%")
                                  ->orWhere('email', 'like', "%{$search}%");
                           });
                    });
                })
                ->when($request->filled('ad_account_id'), function ($q) use ($request) {
                    $q->where('ad_account_id', $request->ad_account_id);
                })
                ->when($request->filled('date_from'), function ($q) use ($request) {
                    $q->whereDate('spend_date', '>=', $request->date_from);
                })
                ->when($request->filled('date_to'), function ($q) use ($request) {
                    $q->whereDate('spend_date', '<=', $request->date_to);
                });

            $statsQuery = clone $query;
            $totalSpend     = (float) $statsQuery->sum('amount');
            $totalEntries   = $statsQuery->count();
            $thisMonthSpend = (float) AdSpend::when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->whereYear('spend_date', now()->year)
                ->whereMonth('spend_date', now()->month)
                ->sum('amount');

            $paginated = $query->orderByDesc('spend_date')
                ->latest()
                ->paginate($perPage);

            $adAccounts = AdAccountMaster::when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->active()
                ->orderBy('account_name')
                ->get()
                ->map(fn($a) => [
                    'id'           => $a->id,
                    'account_name' => $a->account_name,
                    'account_id'   => $a->account_id,
                    'platform'     => $a->platform,
                ]);

            return response()->json([
                'status' => true,
                'data'   => [
                    'items'       => collect($paginated->items())->map(fn($s) => [
                        'id'             => $s->id,
                        'spend_date'     => $s->spend_date?->format('Y-m-d'),
                        'ad_account_id'  => $s->ad_account_id,
                        'ad_account'     => $s->adAccount ? [
                            'id'           => $s->adAccount->id,
                            'account_name' => $s->adAccount->account_name,
                            'account_id'   => $s->adAccount->account_id,
                            'platform'     => $s->adAccount->platform,
                        ] : null,
                        'amount'         => (float) $s->amount,
                        'remarks'        => $s->remarks,
                        'created_by'     => $s->creator?->name,
                        'created_at'     => $s->created_at?->format('Y-m-d H:i:s'),
                    ]),
                    'pagination'  => [
                        'current_page' => $paginated->currentPage(),
                        'last_page'    => $paginated->lastPage(),
                        'per_page'     => $paginated->perPage(),
                        'total'        => $paginated->total(),
                        'has_more'     => $paginated->hasMorePages(),
                    ],
                    'summary'     => [
                        'total_spend'      => $totalSpend,
                        'total_entries'    => $totalEntries,
                        'this_month_spend' => $thisMonthSpend,
                    ],
                    'ad_accounts' => $adAccounts,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile AdSpends error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load ad spends: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function storeAdSpend(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'spend_date'    => 'required|date',
            'ad_account_id' => 'required|exists:ad_account_masters,id',
            'amount'        => 'required|numeric|min:0.01',
            'remarks'       => 'nullable|string',
        ]);

        $user = $request->user();
        $validated['created_by'] = $user->id;
        $validated['company_id'] = $user?->company_id;
        $validated['branch_id']  = $user?->branch_id;

        $adSpend = AdSpend::create($validated);

        return response()->json([
            'status'  => true,
            'message' => 'Ad Spend entry added successfully!',
            'data'    => $adSpend->load(['adAccount', 'creator']),
        ]);
    }

    public function updateAdSpend(Request $request, AdSpend $adSpend): JsonResponse
    {
        $validated = $request->validate([
            'spend_date'    => 'required|date',
            'ad_account_id' => 'required|exists:ad_account_masters,id',
            'amount'        => 'required|numeric|min:0.01',
            'remarks'       => 'nullable|string',
        ]);

        $adSpend->update($validated);

        return response()->json([
            'status'  => true,
            'message' => 'Ad Spend entry updated successfully!',
            'data'    => $adSpend->load(['adAccount', 'creator']),
        ]);
    }

    public function destroyAdSpend(AdSpend $adSpend): JsonResponse
    {
        $adSpend->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Ad Spend entry deleted successfully.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  5. ALLOCATION VS SPENT LEDGER
    // ─────────────────────────────────────────────────────────────────────────

    public function allocationVsSpent(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;

            $adAccounts = AdAccountMaster::when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->active()
                ->orderBy('account_name')
                ->get()
                ->map(fn($a) => [
                    'id'           => $a->id,
                    'account_name' => $a->account_name,
                    'account_id'   => $a->account_id,
                    'platform'     => $a->platform,
                ]);

            $selectedAccountId = $request->query('ad_account_id');
            $dateFrom          = $request->query('date_from');
            $dateTo            = $request->query('date_to');
            $search            = $request->query('search');

            // 1. Fetch Approved Budget Allocations (Credit)
            $allocations = AdBudgetRequest::with(['adAccount', 'requester'])
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->where('status', 'approved')
                ->when($selectedAccountId, fn($q) => $q->where('ad_account_id', $selectedAccountId))
                ->get();

            // 2. Fetch Ad Spends (Debit)
            $spends = AdSpend::with(['adAccount', 'creator'])
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->when($selectedAccountId, fn($q) => $q->where('ad_account_id', $selectedAccountId))
                ->get();

            // 3. Build unified transaction list
            $transactions = collect();

            foreach ($allocations as $alloc) {
                $date = $alloc->payment_date
                    ? $alloc->payment_date->format('Y-m-d')
                    : $alloc->created_at->format('Y-m-d');
                $amount = (float) ($alloc->approved_amount > 0 ? $alloc->approved_amount : $alloc->amount);

                $transactions->push([
                    'id'               => 'alloc_' . $alloc->id,
                    'ref_id'           => $alloc->id,
                    'type'             => 'allocation',
                    'date'             => $date,
                    'ad_account_id'    => $alloc->ad_account_id,
                    'ad_account'       => $alloc->adAccount ? [
                        'id'           => $alloc->adAccount->id,
                        'account_name' => $alloc->adAccount->account_name,
                        'account_id'   => $alloc->adAccount->account_id,
                        'platform'     => $alloc->adAccount->platform,
                    ] : null,
                    'description'      => 'Approved Budget Allocation (Request #' . $alloc->id . ($alloc->client_name ? ' - ' . $alloc->client_name : '') . ')',
                    'allocated_amount' => $amount,
                    'spent_amount'     => 0.0,
                    'user_name'        => $alloc->requester?->name ?? 'System',
                    'created_at'       => $alloc->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            foreach ($spends as $spend) {
                $date = $spend->spend_date->format('Y-m-d');

                $transactions->push([
                    'id'               => 'spend_' . $spend->id,
                    'ref_id'           => $spend->id,
                    'type'             => 'spend',
                    'date'             => $date,
                    'ad_account_id'    => $spend->ad_account_id,
                    'ad_account'       => $spend->adAccount ? [
                        'id'           => $spend->adAccount->id,
                        'account_name' => $spend->adAccount->account_name,
                        'account_id'   => $spend->adAccount->account_id,
                        'platform'     => $spend->adAccount->platform,
                    ] : null,
                    'description'      => 'Ad Spend Entry' . ($spend->remarks ? ' (' . $spend->remarks . ')' : ''),
                    'allocated_amount' => 0.0,
                    'spent_amount'     => (float) $spend->amount,
                    'user_name'        => $spend->creator?->name ?? 'System',
                    'created_at'       => $spend->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            // 4. Sort chronologically by Date ASC, created_at ASC to calculate Running Balance
            $sortedTransactions = $transactions->sortBy([
                ['date', 'asc'],
                ['created_at', 'asc'],
            ])->values();

            $accountBalances = [];
            $runningTransactions = collect();

            foreach ($sortedTransactions as $txn) {
                $accId = $txn['ad_account_id'];
                if (!isset($accountBalances[$accId])) {
                    $accountBalances[$accId] = 0.0;
                }

                $accountBalances[$accId] += ($txn['allocated_amount'] - $txn['spent_amount']);
                $txn['running_balance'] = $accountBalances[$accId];

                $runningTransactions->push($txn);
            }

            // Overall Totals
            $totalAllocatedOverall = (float) $runningTransactions->sum('allocated_amount');
            $totalSpentOverall     = (float) $runningTransactions->sum('spent_amount');
            $currentBalanceOverall = $totalAllocatedOverall - $totalSpentOverall;

            // Apply Filters for display
            $filteredRows = $runningTransactions;

            if ($dateFrom) {
                $filteredRows = $filteredRows->filter(fn($t) => $t['date'] >= $dateFrom);
            }
            if ($dateTo) {
                $filteredRows = $filteredRows->filter(fn($t) => $t['date'] <= $dateTo);
            }
            if ($search) {
                $searchLower = strtolower(trim($search));
                $filteredRows = $filteredRows->filter(function ($t) use ($searchLower) {
                    $accName  = strtolower($t['ad_account']['account_name'] ?? '');
                    $platform = strtolower($t['ad_account']['platform'] ?? '');
                    $desc     = strtolower($t['description'] ?? '');
                    $user     = strtolower($t['user_name'] ?? '');

                    return str_contains($accName, $searchLower)
                        || str_contains($platform, $searchLower)
                        || str_contains($desc, $searchLower)
                        || str_contains($user, $searchLower)
                        || str_contains($t['date'], $searchLower);
                });
            }

            // Display newest first
            $rows = $filteredRows->sortBy([
                ['date', 'desc'],
                ['created_at', 'desc'],
            ])->values();

            $accountCurrentBalance = $currentBalanceOverall;
            $selectedAccount = null;

            if ($selectedAccountId) {
                $accountCurrentBalance = (float) ($accountBalances[$selectedAccountId] ?? 0.0);
                $accountModel = AdAccountMaster::find($selectedAccountId);
                if ($accountModel) {
                    $selectedAccount = [
                        'id'           => $accountModel->id,
                        'account_name' => $accountModel->account_name,
                        'account_id'   => $accountModel->account_id,
                        'platform'     => $accountModel->platform,
                    ];
                }
            }

            return response()->json([
                'status' => true,
                'data'   => [
                    'transactions'            => $rows,
                    'summary'                 => [
                        'total_allocated_overall' => $totalAllocatedOverall,
                        'total_spent_overall'     => $totalSpentOverall,
                        'current_balance_overall' => $currentBalanceOverall,
                        'account_current_balance' => $accountCurrentBalance,
                    ],
                    'ad_accounts'             => $adAccounts,
                    'selected_account'        => $selectedAccount,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile AllocationVsSpent error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load ledger: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  6. DOMAINS & HOSTING
    // ─────────────────────────────────────────────────────────────────────────

    public function domains(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;
            $perPage = (int) $request->query('per_page', 15);

            $godaddyKey = config('services.godaddy.key', env('GODADDY_API_KEY'));
            $godaddySecret = config('services.godaddy.secret', env('GODADDY_API_SECRET'));
            $isGodaddyConfigured = ! empty($godaddyKey) && ! empty($godaddySecret);

            $query = DomainRecord::when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->when($request->filled('search'), function ($q) use ($request) {
                    $search = trim($request->search);
                    $q->where(function ($q2) use ($search) {
                        $q2->where('domain_name', 'like', "%{$search}%")
                           ->orWhere('registrar', 'like', "%{$search}%")
                           ->orWhere('client_name', 'like', "%{$search}%")
                           ->orWhere('notes', 'like', "%{$search}%");
                    });
                })
                ->when($request->filled('status') && $request->status !== 'all', function ($q) use ($request) {
                    if ($request->status === 'expiring_soon') {
                        $q->whereBetween('expires_at', [now()->startOfDay(), now()->addDays(30)->endOfDay()]);
                    } elseif ($request->status === 'expired') {
                        $q->where('expires_at', '<', now()->startOfDay());
                    } else {
                        $q->where('status', $request->status);
                    }
                });

            $paginated = $query->orderBy('expires_at', 'asc')->paginate($perPage);

            $allDomains = DomainRecord::when($companyId, fn($q) => $q->where('company_id', $companyId))->get();
            $stats = [
                'total'         => $allDomains->count(),
                'active'        => $allDomains->where('status', 'ACTIVE')->count(),
                'expiring_soon' => $allDomains->filter(fn($d) => $d->expires_at && $d->days_until_expiration >= 0 && $d->days_until_expiration <= 30)->count(),
                'expired'       => $allDomains->filter(fn($d) => $d->expires_at && $d->days_until_expiration < 0)->count(),
            ];

            return response()->json([
                'status' => true,
                'data'   => [
                    'items'                 => collect($paginated->items())->map(fn($d) => [
                        'id'                     => $d->id,
                        'domain_name'            => $d->domain_name,
                        'registrar'              => $d->registrar,
                        'status'                 => $d->status,
                        'expires_at'             => $d->expires_at?->format('Y-m-d'),
                        'days_until_expiration'  => $d->days_until_expiration,
                        'auto_renew'             => (bool) $d->auto_renew,
                        'privacy'                => (bool) $d->privacy,
                        'client_name'            => $d->client_name,
                        'notes'                  => $d->notes,
                        'godaddy_domain_id'      => $d->godaddy_domain_id,
                        'created_at'             => $d->created_at?->format('Y-m-d H:i:s'),
                    ]),
                    'pagination'            => [
                        'current_page' => $paginated->currentPage(),
                        'last_page'    => $paginated->lastPage(),
                        'per_page'     => $paginated->perPage(),
                        'total'        => $paginated->total(),
                        'has_more'     => $paginated->hasMorePages(),
                    ],
                    'stats'                 => $stats,
                    'is_godaddy_configured' => $isGodaddyConfigured,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Domains error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load domains: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function storeDomain(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'domain_name' => 'required|string|max:255',
            'registrar'   => 'required|string|max:100',
            'status'      => 'required|string|in:ACTIVE,EXPIRED,CANCELLED,PENDING_RENEWAL',
            'expires_at'  => 'nullable|date',
            'auto_renew'  => 'nullable|boolean',
            'privacy'     => 'nullable|boolean',
            'client_name' => 'nullable|string|max:255',
            'notes'       => 'nullable|string',
        ]);

        $user = $request->user();
        $validated['company_id'] = $user?->company_id;
        $validated['created_by'] = $user?->id;
        $validated['auto_renew'] = filter_var($request->input('auto_renew', false), FILTER_VALIDATE_BOOLEAN);
        $validated['privacy']    = filter_var($request->input('privacy', false), FILTER_VALIDATE_BOOLEAN);

        $domain = DomainRecord::create($validated);

        return response()->json([
            'status'  => true,
            'message' => 'Domain record created successfully!',
            'data'    => $domain,
        ]);
    }

    public function updateDomain(Request $request, DomainRecord $domain): JsonResponse
    {
        $validated = $request->validate([
            'domain_name' => 'required|string|max:255',
            'registrar'   => 'required|string|max:100',
            'status'      => 'required|string|in:ACTIVE,EXPIRED,CANCELLED,PENDING_RENEWAL',
            'expires_at'  => 'nullable|date',
            'auto_renew'  => 'nullable|boolean',
            'privacy'     => 'nullable|boolean',
            'client_name' => 'nullable|string|max:255',
            'notes'       => 'nullable|string',
        ]);

        $validated['auto_renew'] = filter_var($request->input('auto_renew', false), FILTER_VALIDATE_BOOLEAN);
        $validated['privacy']    = filter_var($request->input('privacy', false), FILTER_VALIDATE_BOOLEAN);

        $domain->update($validated);

        return response()->json([
            'status'  => true,
            'message' => 'Domain record updated successfully!',
            'data'    => $domain,
        ]);
    }

    public function destroyDomain(DomainRecord $domain): JsonResponse
    {
        $domain->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Domain record deleted successfully!',
        ]);
    }

    public function syncGodaddy(Request $request): JsonResponse
    {
        $godaddyKey = config('services.godaddy.key', env('GODADDY_API_KEY'));
        $godaddySecret = config('services.godaddy.secret', env('GODADDY_API_SECRET'));
        $godaddyEnv = config('services.godaddy.environment', env('GODADDY_ENVIRONMENT', 'production'));

        if (empty($godaddyKey) || empty($godaddySecret)) {
            return response()->json([
                'status'  => false,
                'message' => 'GoDaddy API Credentials are not configured in environment.',
            ], 400);
        }

        $baseUrl = strtolower($godaddyEnv) === 'ote'
            ? 'https://api.ote-godaddy.com/v1/domains'
            : 'https://api.godaddy.com/v1/domains';

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => "sso-key {$godaddyKey}:{$godaddySecret}",
                    'Accept'        => 'application/json',
                ])
                ->get($baseUrl);

            if ($response->failed()) {
                Log::error('GoDaddy API Sync Error', ['status' => $response->status(), 'body' => $response->body()]);
                return response()->json([
                    'status'  => false,
                    'message' => 'Failed to fetch domains from GoDaddy API: ' . ($response->json('message') ?? 'HTTP ' . $response->status()),
                ], 400);
            }

            $godaddyDomains = $response->json();
            if (! is_array($godaddyDomains)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Invalid response received from GoDaddy API.',
                ], 400);
            }

            $user = $request->user();
            $companyId = $user?->company_id;
            $syncedCount = 0;

            foreach ($godaddyDomains as $item) {
                if (empty($item['domain'])) {
                    continue;
                }

                $expiresAt = null;
                if (! empty($item['expires'])) {
                    $expiresAt = date('Y-m-d', strtotime($item['expires']));
                }

                DomainRecord::updateOrCreate(
                    [
                        'company_id'  => $companyId,
                        'domain_name' => $item['domain'],
                    ],
                    [
                        'registrar'         => 'GoDaddy',
                        'status'            => strtoupper($item['status'] ?? 'ACTIVE'),
                        'expires_at'        => $expiresAt,
                        'auto_renew'        => ! empty($item['autoRenew']),
                        'privacy'           => ! empty($item['privacy']),
                        'godaddy_domain_id' => (string) ($item['domainId'] ?? ''),
                        'created_by'        => $user?->id,
                    ]
                );
                $syncedCount++;
            }

            return response()->json([
                'status'  => true,
                'message' => "Successfully synced {$syncedCount} domain(s) from GoDaddy API!",
                'count'   => $syncedCount,
            ]);
        } catch (Throwable $e) {
            Log::error('GoDaddy Sync Exception: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'GoDaddy API connection failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function hostings(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;
            $perPage = (int) $request->query('per_page', 15);

            $query = HostingRecord::when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->when($request->filled('search'), function ($q) use ($request) {
                    $search = trim($request->search);
                    $q->where(function ($q2) use ($search) {
                        $q2->where('hosting_name', 'like', "%{$search}%")
                           ->orWhere('provider', 'like', "%{$search}%")
                           ->orWhere('ip_address', 'like', "%{$search}%")
                           ->orWhere('client_name', 'like', "%{$search}%");
                    });
                })
                ->when($request->filled('status') && $request->status !== 'all', function ($q) use ($request) {
                    if ($request->status === 'expiring_soon') {
                        $q->whereBetween('renewal_date', [now()->startOfDay(), now()->addDays(30)->endOfDay()]);
                    } elseif ($request->status === 'expired') {
                        $q->where('renewal_date', '<', now()->startOfDay());
                    } else {
                        $q->where('status', $request->status);
                    }
                });

            $paginated = $query->orderBy('renewal_date', 'asc')->paginate($perPage);

            $allHostings = HostingRecord::when($companyId, fn($q) => $q->where('company_id', $companyId))->get();
            $stats = [
                'total'         => $allHostings->count(),
                'active'        => $allHostings->where('status', 'ACTIVE')->count(),
                'renewal_due'   => $allHostings->filter(fn($h) => $h->renewal_date && $h->days_until_renewal >= 0 && $h->days_until_renewal <= 30)->count(),
                'total_amount'  => (float) $allHostings->sum('renewal_amount'),
            ];

            return response()->json([
                'status' => true,
                'data'   => [
                    'items'      => collect($paginated->items())->map(fn($h) => [
                        'id'                 => $h->id,
                        'hosting_name'       => $h->hosting_name,
                        'provider'           => $h->provider,
                        'ip_address'         => $h->ip_address,
                        'plan_type'          => $h->plan_type,
                        'status'             => $h->status,
                        'renewal_date'       => $h->renewal_date?->format('Y-m-d'),
                        'days_until_renewal' => $h->days_until_renewal,
                        'renewal_amount'     => (float) $h->renewal_amount,
                        'client_name'        => $h->client_name,
                        'notes'              => $h->notes,
                        'created_at'         => $h->created_at?->format('Y-m-d H:i:s'),
                    ]),
                    'pagination' => [
                        'current_page' => $paginated->currentPage(),
                        'last_page'    => $paginated->lastPage(),
                        'per_page'     => $paginated->perPage(),
                        'total'        => $paginated->total(),
                        'has_more'     => $paginated->hasMorePages(),
                    ],
                    'stats'      => $stats,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Hostings error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load hostings: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function storeHosting(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'hosting_name'   => 'required|string|max:255',
            'provider'       => 'required|string|max:100',
            'ip_address'     => 'nullable|string|max:100',
            'plan_type'      => 'required|string|max:100',
            'status'         => 'required|string|in:ACTIVE,EXPIRED,PENDING_RENEWAL',
            'renewal_date'   => 'nullable|date',
            'renewal_amount' => 'nullable|numeric|min:0',
            'client_name'    => 'nullable|string|max:255',
            'notes'          => 'nullable|string',
        ]);

        $user = $request->user();
        $validated['company_id'] = $user?->company_id;
        $validated['created_by'] = $user?->id;

        $hosting = HostingRecord::create($validated);

        return response()->json([
            'status'  => true,
            'message' => 'Hosting record created successfully!',
            'data'    => $hosting,
        ]);
    }

    public function updateHosting(Request $request, HostingRecord $hosting): JsonResponse
    {
        $validated = $request->validate([
            'hosting_name'   => 'required|string|max:255',
            'provider'       => 'required|string|max:100',
            'ip_address'     => 'nullable|string|max:100',
            'plan_type'      => 'required|string|max:100',
            'status'         => 'required|string|in:ACTIVE,EXPIRED,PENDING_RENEWAL',
            'renewal_date'   => 'nullable|date',
            'renewal_amount' => 'nullable|numeric|min:0',
            'client_name'    => 'nullable|string|max:255',
            'notes'          => 'nullable|string',
        ]);

        $hosting->update($validated);

        return response()->json([
            'status'  => true,
            'message' => 'Hosting record updated successfully!',
            'data'    => $hosting,
        ]);
    }

    public function destroyHosting(HostingRecord $hosting): JsonResponse
    {
        $hosting->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Hosting record deleted successfully!',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  7. AD ACCOUNTS MASTER
    // ─────────────────────────────────────────────────────────────────────────

    public function adAccounts(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;
            $perPage = (int) $request->query('per_page', 15);

            $query = AdAccountMaster::with(['branch', 'creator'])
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->when($request->filled('search'), function ($q) use ($request) {
                    $search = trim($request->search);
                    $q->where(function ($q2) use ($search) {
                        $q2->where('account_name', 'like', "%{$search}%")
                           ->orWhere('account_id', 'like', "%{$search}%")
                           ->orWhere('platform', 'like', "%{$search}%");
                    });
                })
                ->when($request->filled('status') && $request->status !== 'all', function ($q) use ($request) {
                    $q->where('status', $request->status);
                })
                ->when($request->filled('platform') && $request->platform !== 'all', function ($q) use ($request) {
                    $q->where('platform', $request->platform);
                });

            $paginated = $query->latest()->paginate($perPage);

            $branches = Branch::active()
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->get()
                ->map(fn($b) => [
                    'id'   => $b->id,
                    'name' => $b->name,
                ]);

            return response()->json([
                'status' => true,
                'data'   => [
                    'items'      => collect($paginated->items())->map(fn($a) => [
                        'id'           => $a->id,
                        'account_name' => $a->account_name,
                        'account_id'   => $a->account_id,
                        'platform'     => $a->platform,
                        'status'       => $a->status,
                        'branch_id'    => $a->branch_id,
                        'branch_name'  => $a->branch?->name,
                        'notes'        => $a->notes,
                        'created_by'   => $a->creator?->name,
                        'created_at'   => $a->created_at?->format('Y-m-d H:i:s'),
                    ]),
                    'pagination' => [
                        'current_page' => $paginated->currentPage(),
                        'last_page'    => $paginated->lastPage(),
                        'per_page'     => $paginated->perPage(),
                        'total'        => $paginated->total(),
                        'has_more'     => $paginated->hasMorePages(),
                    ],
                    'branches'   => $branches,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile AdAccountsMaster error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load ad accounts: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function storeAdAccount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_name' => 'required|string|max:255',
            'account_id'   => 'nullable|string|max:255',
            'platform'     => 'required|string|max:100',
            'branch_id'    => 'nullable|exists:branches,id',
            'notes'        => 'nullable|string',
        ]);

        $user = $request->user();
        $validated['company_id'] = $user?->company_id;
        $validated['status']     = 'active';
        $validated['created_by'] = $user?->id;

        $account = AdAccountMaster::create($validated);

        return response()->json([
            'status'  => true,
            'message' => 'Ad Account created successfully!',
            'data'    => $account->load(['branch', 'creator']),
        ]);
    }

    public function updateAdAccount(Request $request, AdAccountMaster $adAccount): JsonResponse
    {
        $validated = $request->validate([
            'account_name' => 'required|string|max:255',
            'account_id'   => 'nullable|string|max:255',
            'platform'     => 'required|string|max:100',
            'branch_id'    => 'nullable|exists:branches,id',
            'notes'        => 'nullable|string',
            'status'       => 'required|in:active,inactive',
        ]);

        $adAccount->update($validated);

        return response()->json([
            'status'  => true,
            'message' => 'Ad Account updated successfully!',
            'data'    => $adAccount->load(['branch', 'creator']),
        ]);
    }

    public function toggleAdAccountStatus(AdAccountMaster $adAccount): JsonResponse
    {
        $newStatus = $adAccount->status === 'active' ? 'inactive' : 'active';
        $adAccount->update(['status' => $newStatus]);

        return response()->json([
            'status'  => true,
            'message' => "Ad Account status set to {$newStatus}.",
            'data'    => [
                'id'     => $adAccount->id,
                'status' => $newStatus,
            ],
        ]);
    }

    public function destroyAdAccount(AdAccountMaster $adAccount): JsonResponse
    {
        $adAccount->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Ad Account deleted successfully!',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    private function formatBudgetRequest(AdBudgetRequest $item): array
    {
        return [
            'id'               => $item->id,
            'type'             => $item->type,
            'ad_account_id'    => $item->ad_account_id,
            'ad_account'       => $item->adAccount ? [
                'id'           => $item->adAccount->id,
                'account_name' => $item->adAccount->account_name,
                'account_id'   => $item->adAccount->account_id,
                'platform'     => $item->adAccount->platform,
            ] : null,
            'amount'           => (float) $item->amount,
            'approved_amount'  => (float) $item->approved_amount,
            'selected_dates'   => $item->selected_dates ?? [],
            'client_name'      => $item->client_name,
            'remarks'          => $item->remarks,
            'status'           => $item->status,
            'requested_by'     => $item->requester?->name,
            'tl_approved_by'   => $item->tlApprover?->name,
            'tl_approved_at'   => $item->tl_approved_at?->format('Y-m-d H:i:s'),
            'tl_remarks'       => $item->tl_remarks,
            'approved_by'      => $item->approver?->name,
            'approved_at'      => $item->approved_at?->format('Y-m-d H:i:s'),
            'payment_date'     => $item->payment_date?->format('Y-m-d'),
            'accounts_remarks' => $item->accounts_remarks,
            'attachments'      => $item->attachments ?? [],
            'created_at'       => $item->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    private function sendNotificationMail(AdBudgetRequest $requestItem, string $event): void
    {
        try {
            $recipients = [];

            if ($event === 'request_created') {
                if ($requestItem->status === 'tl_pending') {
                    $tls = User::where(function ($q) {
                        $q->whereHas('roles', function ($r) {
                            $r->where('name', 'like', '%TL%')
                              ->orWhere('name', 'like', '%Team Lead%')
                              ->orWhere('name', 'like', '%Manager%');
                        });
                    })->where('is_active', true)->pluck('email')->toArray();

                    $recipients = !empty($tls) ? $tls : ['hr@saitechnosolutions.net'];
                } else {
                    $recipients = ['hr@saitechnosolutions.net'];
                }
            } elseif ($event === 'tl_approved') {
                $recipients[] = 'hr@saitechnosolutions.net';
                if ($requestItem->requester?->email) {
                    $recipients[] = $requestItem->requester->email;
                }
            } elseif ($event === 'accounts_approved' || $event === 'rejected') {
                if ($requestItem->requester?->email) {
                    $recipients[] = $requestItem->requester->email;
                }
                if ($requestItem->tlApprover?->email) {
                    $recipients[] = $requestItem->tlApprover->email;
                }
            }

            $recipients = array_unique(array_filter($recipients));

            foreach ($recipients as $email) {
                Mail::to($email)->send(
                    new AdBudgetNotificationMail($requestItem, $event)
                );
            }
        } catch (Throwable $e) {
            Log::error('Ad Budget Mail Dispatch Error: ' . $e->getMessage());
        }
    }
}
