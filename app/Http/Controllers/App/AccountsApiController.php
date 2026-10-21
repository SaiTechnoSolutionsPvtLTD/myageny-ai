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
use App\Models\AccountExpense;
use App\Models\AccountExpenseCategory;
use App\Models\AccountExpenseSubcategory;
use App\Models\VendorMaster;
use App\Models\CustomerMaster;
use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\Department;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\InvoiceSetting;
use App\Models\ItemMaster;
use App\Http\Controllers\InvoiceController;
use App\Console\Commands\GenerateRecurringAccountExpensesCommand;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        $now = Carbon::now('Asia/Kolkata');
        if ($now->greaterThanOrEqualTo($now->copy()->setTime(11, 30, 0))) {
            return response()->json([
                'status'  => false,
                'message' => 'Daily Ad Budget Request creation is closed for today. Requests must be submitted before 11:30 AM IST.',
            ], 422);
        }

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
        $now = Carbon::now('Asia/Kolkata');
        if ($now->greaterThanOrEqualTo($now->copy()->setTime(12, 30, 0))) {
            return response()->json([
                'status'  => false,
                'message' => 'Ad Budget Approval window is closed for today. Approvals are allowed only until 12:30 PM IST.',
            ], 422);
        }

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
        $now = Carbon::now('Asia/Kolkata');
        if ($now->greaterThanOrEqualTo($now->copy()->setTime(12, 30, 0))) {
            return response()->json([
                'status'  => false,
                'message' => 'Ad Budget Approval window is closed for today. Approvals are allowed only until 12:30 PM IST.',
            ], 422);
        }

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
            $allocations = AdBudgetRequest::with(['adAccount', 'approver', 'tlApprover', 'requester'])
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
                $allocatedByName = $alloc->approver?->name ?: ($alloc->tlApprover?->name ?: ($alloc->requester?->name ?: 'System'));

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
                    'user_name'        => $allocatedByName,
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

    // ─────────────────────────────────────────────────────────────────────────
    //  8. ACCOUNTS EXPENSES
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * List accounts expenses with KPI stats, filters, and future-month forecasting.
     */
    public function expenses(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;

            // Auto-generate due recurring fixed expenses for current month
            try {
                GenerateRecurringAccountExpensesCommand::generateForMonth(now()->startOfMonth());
            } catch (Throwable $e) {
                // Silently continue
            }

            $baseQuery = AccountExpense::query()
                ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')));

            // Next Month Projected Fixed Expenses
            $nextMonthCarbon = now()->addMonth()->startOfMonth();
            $nextMonthStr    = $nextMonthCarbon->format('Y-m');

            $nextMonthRecurringMasters = AccountExpense::activeRecurring($nextMonthCarbon)
                ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->with(['category', 'subcategory', 'vendor'])
                ->get();

            $nextMonthProjectedAmount = (float) $nextMonthRecurringMasters->sum('amount');
            $nextMonthFixedCount      = $nextMonthRecurringMasters->count();

            // Calculate KPI summary stats for actual records
            $allExpenses = (clone $baseQuery)->get(['amount', 'expense_type', 'expense_date']);
            $totalAmount       = (float) $allExpenses->sum('amount');
            $fixedAmount       = (float) $allExpenses->where('expense_type', 'Fixed')->sum('amount');
            $variableAmount    = (float) $allExpenses->where('expense_type', 'Variable')->sum('amount');
            $thisMonthAmount   = (float) $allExpenses->filter(function ($item) {
                return $item->expense_date &&
                       $item->expense_date->format('Y-m') === now()->format('Y-m');
            })->sum('amount');
            $totalCount        = $allExpenses->count();

            // Check if user is filtering for Next Month or a future month (Forecast Mode)
            $isFutureMonthFilter = false;
            $futureMonthLabel = null;
            $projectedExpenses = collect();

            $filteredMonth = $request->input('month');
            if ($filteredMonth && $filteredMonth >= $nextMonthStr) {
                $isFutureMonthFilter = true;
                $targetFutureCarbon = Carbon::createFromFormat('Y-m', $filteredMonth)->startOfMonth();
                $futureMonthLabel = $targetFutureCarbon->format('F Y');

                $futureTemplates = AccountExpense::activeRecurring($targetFutureCarbon)
                    ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                    ->when($request->filled('category_id'), fn ($q) => $q->where('account_expense_category_id', $request->category_id))
                    ->when($request->filled('subcategory_id'), fn ($q) => $q->where('account_expense_subcategory_id', $request->subcategory_id))
                    ->when($request->filled('vendor_id'), fn ($q) => $q->where('vendor_id', $request->vendor_id))
                    ->with(['category', 'subcategory', 'vendor', 'creator'])
                    ->get();

                foreach ($futureTemplates as $tpl) {
                    $day = min($tpl->recurring_day_of_month ?: ($tpl->expense_date ? $tpl->expense_date->day : 1), $targetFutureCarbon->daysInMonth);
                    $scheduledDate = $targetFutureCarbon->copy()->day($day);

                    $existing = AccountExpense::where(function ($q) use ($tpl) {
                            $q->where('id', $tpl->id)->orWhere('parent_expense_id', $tpl->id);
                        })
                        ->whereYear('expense_date', $targetFutureCarbon->year)
                        ->whereMonth('expense_date', $targetFutureCarbon->month)
                        ->first();

                    $projItem = (object)[
                        'id'                             => $tpl->id,
                        'expense_number'                 => $existing ? $existing->expense_number : ($tpl->expense_number . ' (Forecast)'),
                        'expense_date'                   => $scheduledDate,
                        'account_expense_category_id'    => $tpl->account_expense_category_id,
                        'account_expense_subcategory_id' => $tpl->account_expense_subcategory_id,
                        'vendor_id'                      => $tpl->vendor_id,
                        'amount'                         => (float) $tpl->amount,
                        'expense_type'                   => 'Fixed',
                        'is_recurring'                   => true,
                        'recurring_day_of_month'         => $tpl->recurring_day_of_month,
                        'recurring_end_date'             => $tpl->recurring_end_date ? Carbon::parse($tpl->recurring_end_date)->format('Y-m-d') : null,
                        'auto_generated'                 => (bool) $existing,
                        'parent_expense_id'              => $tpl->id,
                        'invoice_no'                     => $existing ? $existing->invoice_no : ($tpl->invoice_no ? $tpl->invoice_no . '-' . $targetFutureCarbon->format('Ym') : 'Scheduled'),
                        'remarks'                        => $tpl->remarks ?: '',
                        'created_by'                     => $tpl->created_by,
                        'category'                       => $tpl->category,
                        'subcategory'                    => $tpl->subcategory,
                        'vendor'                         => $tpl->vendor,
                        'creator'                        => $tpl->creator,
                        'attachment_list'                => $existing ? $existing->attachment_list : [],
                        'is_forecast'                    => true,
                    ];

                    $projectedExpenses->push($projItem);
                }

                // Adjust stats for future forecast month
                $totalAmount       = (float) $projectedExpenses->sum('amount');
                $fixedAmount       = $totalAmount;
                $variableAmount    = 0.0;
                $thisMonthAmount   = $totalAmount;
                $totalCount        = $projectedExpenses->count();

                $perPage = (int) $request->input('per_page', 15);
                $page = (int) $request->input('page', 1);
                $pagedItems = $projectedExpenses->slice(($page - 1) * $perPage, $perPage)->values();

                $formattedItems = $pagedItems->map(fn($item) => $this->formatExpenseItem($item));

                $pagination = [
                    'current_page' => $page,
                    'last_page'    => max(1, (int) ceil($totalCount / $perPage)),
                    'per_page'     => $perPage,
                    'total'        => $totalCount,
                    'has_more'     => ($page * $perPage) < $totalCount,
                ];
            } else {
                // Actual recorded expenses query with filters
                $query = (clone $baseQuery)
                    ->with(['category', 'subcategory', 'vendor', 'creator'])
                    ->when($request->filled('search'), function ($q) use ($request) {
                        $search = trim($request->search);
                        $q->where(function ($sub) use ($search) {
                            $sub->where('invoice_no', 'like', "%{$search}%")
                                ->orWhere('expense_number', 'like', "%{$search}%")
                                ->orWhere('remarks', 'like', "%{$search}%")
                                ->orWhereHas('vendor', function ($vq) use ($search) {
                                    $vq->where('vendor_name', 'like', "%{$search}%")
                                       ->orWhere('company_name', 'like', "%{$search}%");
                                })
                                ->orWhereHas('category', function ($cq) use ($search) {
                                    $cq->where('name', 'like', "%{$search}%");
                                });
                        });
                    })
                    ->when($request->filled('expense_type'), function ($q) use ($request) {
                        $q->where('expense_type', $request->expense_type);
                    })
                    ->when($request->filled('category_id'), function ($q) use ($request) {
                        $q->where('account_expense_category_id', $request->category_id);
                    })
                    ->when($request->filled('subcategory_id'), function ($q) use ($request) {
                        $q->where('account_expense_subcategory_id', $request->subcategory_id);
                    })
                    ->when($request->filled('vendor_id'), function ($q) use ($request) {
                        $q->where('vendor_id', $request->vendor_id);
                    })
                    ->when($request->filled('date_from'), function ($q) use ($request) {
                        $q->whereDate('expense_date', '>=', $request->date_from);
                    })
                    ->when($request->filled('date_to'), function ($q) use ($request) {
                        $q->whereDate('expense_date', '<=', $request->date_to);
                    })
                    ->when($request->filled('month'), function ($q) use ($request) {
                        $parts = explode('-', $request->month);
                        if (count($parts) === 2) {
                            $q->whereYear('expense_date', $parts[0])
                              ->whereMonth('expense_date', $parts[1]);
                        }
                    })
                    ->orderByDesc('expense_date')
                    ->latest('id');

                $perPage = (int) $request->input('per_page', 15);
                $paginated = $query->paginate($perPage);

                $formattedItems = collect($paginated->items())->map(fn($item) => $this->formatExpenseItem($item));

                $pagination = [
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                    'per_page'     => $paginated->perPage(),
                    'total'        => $paginated->total(),
                    'has_more'     => $paginated->hasMorePages(),
                ];
            }

            return response()->json([
                'status' => true,
                'data'   => [
                    'items'        => $formattedItems,
                    'pagination'   => $pagination,
                    'kpi_stats'    => [
                        'total_expenses'             => $totalAmount,
                        'this_month_expenses'        => $thisMonthAmount,
                        'fixed_expenses'             => $fixedAmount,
                        'variable_expenses'          => $variableAmount,
                        'next_month_projected_amount'=> $nextMonthProjectedAmount,
                        'next_month_fixed_count'     => $nextMonthFixedCount,
                        'next_month_label'           => $nextMonthCarbon->format('M Y'),
                        'next_month_str'             => $nextMonthStr,
                        'total_entries'              => $totalCount,
                    ],
                    'is_future_month_filter' => $isFutureMonthFilter,
                    'future_month_label'     => $futureMonthLabel,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts expenses list error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load accounts expenses: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get metadata options for Categories, Subcategories, and Vendors.
     */
    public function expenseMeta(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;

            $categories = AccountExpenseCategory::active()
                ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->orderBy('name')
                ->get(['id', 'name', 'code']);

            $subcategories = AccountExpenseSubcategory::active()
                ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'account_expense_category_id']);

            $vendors = VendorMaster::active()
                ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->orderBy('vendor_name')
                ->get(['id', 'vendor_name', 'company_name', 'mobile_number', 'email', 'gst_number']);

            return response()->json([
                'status' => true,
                'data'   => [
                    'categories'    => $categories,
                    'subcategories' => $subcategories,
                    'vendors'       => $vendors,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts expenseMeta error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load expense form metadata: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get active subcategories for a specific category.
     */
    public function expenseSubcategories(Request $request, $categoryId): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;

            $subcategories = AccountExpenseSubcategory::active()
                ->where('account_expense_category_id', $categoryId)
                ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->orderBy('name')
                ->get(['id', 'name', 'code']);

            return response()->json([
                'status' => true,
                'data'   => $subcategories,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load subcategories: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a new Accounts Expense record with optional file attachments.
     */
    public function storeExpense(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;

            $validated = $request->validate([
                'expense_date'                   => 'required|date',
                'account_expense_category_id'    => 'required|exists:account_expense_categories,id',
                'account_expense_subcategory_id' => 'nullable|exists:account_expense_subcategories,id',
                'vendor_id'                      => 'nullable|exists:vendor_masters,id',
                'amount'                         => 'required|numeric|min:0.01',
                'expense_type'                   => 'required|in:Fixed,Variable',
                'is_recurring'                   => 'nullable|boolean',
                'recurring_day_of_month'         => 'nullable|integer|min:1|max:31',
                'recurring_end_date'             => 'nullable|date',
                'invoice_no'                     => 'nullable|string|max:100',
                'remarks'                        => 'nullable|string|max:5000',
                'attachments'                    => 'nullable|array',
                'attachments.*'                  => 'file|max:10240',
            ]);

            $uploadedAttachments = [];
            if ($request->hasFile('attachments')) {
                $targetDir = public_path('uploads/accounts-expenses');
                if (!file_exists($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }

                foreach ($request->file('attachments') as $file) {
                    if ($file && $file->isValid()) {
                        $originalName = $file->getClientOriginalName();
                        $ext = $file->getClientOriginalExtension();
                        $size = $file->getSize();
                        $mime = $file->getMimeType();
                        $fileName = time() . '_' . Str::random(10) . '.' . $ext;
                        $file->move($targetDir, $fileName);

                        $uploadedAttachments[] = [
                            'path'      => 'uploads/accounts-expenses/' . $fileName,
                            'name'      => $originalName,
                            'size'      => $size,
                            'mime_type' => $mime,
                        ];
                    }
                }
            }

            $isFixed = $validated['expense_type'] === 'Fixed';
            $isRecurring = $isFixed && $request->boolean('is_recurring');
            $dueDay = $isRecurring
                ? ($request->filled('recurring_day_of_month') ? (int) $request->recurring_day_of_month : (int) date('d', strtotime($validated['expense_date'])))
                : null;

            $expense = AccountExpense::create([
                'company_id'                     => $companyId,
                'expense_date'                   => $validated['expense_date'],
                'account_expense_category_id'    => $validated['account_expense_category_id'],
                'account_expense_subcategory_id' => $validated['account_expense_subcategory_id'] ?? null,
                'vendor_id'                      => $validated['vendor_id'] ?? null,
                'amount'                         => $validated['amount'],
                'expense_type'                   => $validated['expense_type'],
                'is_recurring'                   => $isRecurring,
                'recurring_day_of_month'         => $dueDay,
                'recurring_end_date'             => $isRecurring ? ($validated['recurring_end_date'] ?? null) : null,
                'last_generated_month'           => date('Y-m', strtotime($validated['expense_date'])),
                'invoice_no'                     => $validated['invoice_no'] ?? null,
                'remarks'                        => $validated['remarks'] ?? null,
                'attachments'                    => $uploadedAttachments,
                'created_by'                     => $user?->id,
            ]);

            $expense->load(['category', 'subcategory', 'vendor', 'creator']);

            return response()->json([
                'status'  => true,
                'message' => "Expense {$expense->expense_number} recorded successfully!",
                'data'    => $this->formatExpenseItem($expense),
            ], 201);
        } catch (Throwable $e) {
            Log::error('Mobile storeExpense error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to save expense: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display detailed information for an expense.
     */
    public function showExpense(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;

            $expense = AccountExpense::with(['category', 'subcategory', 'vendor', 'creator', 'updater', 'parent', 'generatedChildren'])
                ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->findOrFail($id);

            return response()->json([
                'status' => true,
                'data'   => $this->formatExpenseDetail($expense),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Expense not found: ' . $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Update an existing Accounts Expense.
     */
    public function updateExpense(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;

            $expense = AccountExpense::when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->findOrFail($id);

            $validated = $request->validate([
                'expense_date'                   => 'required|date',
                'account_expense_category_id'    => 'required|exists:account_expense_categories,id',
                'account_expense_subcategory_id' => 'nullable|exists:account_expense_subcategories,id',
                'vendor_id'                      => 'nullable|exists:vendor_masters,id',
                'amount'                         => 'required|numeric|min:0.01',
                'expense_type'                   => 'required|in:Fixed,Variable',
                'is_recurring'                   => 'nullable|boolean',
                'recurring_day_of_month'         => 'nullable|integer|min:1|max:31',
                'recurring_end_date'             => 'nullable|date',
                'invoice_no'                     => 'nullable|string|max:100',
                'remarks'                        => 'nullable|string|max:5000',
                'attachments'                    => 'nullable|array',
                'attachments.*'                  => 'file|max:10240',
                'removed_attachments'            => 'nullable|array',
            ]);

            $existingAttachments = $expense->attachments ?? [];
            $removedIndices = $request->input('removed_attachments', []);

            // Remove deleted attachments
            if (!empty($removedIndices) && is_array($removedIndices)) {
                $filtered = [];
                foreach ($existingAttachments as $idx => $att) {
                    if (in_array((string)$idx, $removedIndices, true) || in_array($idx, $removedIndices, true)) {
                        $path = is_array($att) ? ($att['path'] ?? '') : $att;
                        $fullPath = public_path($path);
                        if (file_exists($fullPath)) {
                            @unlink($fullPath);
                        }
                    } else {
                        $filtered[] = $att;
                    }
                }
                $existingAttachments = $filtered;
            }

            // Upload and append new attachments
            if ($request->hasFile('attachments')) {
                $targetDir = public_path('uploads/accounts-expenses');
                if (!file_exists($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }

                foreach ($request->file('attachments') as $file) {
                    if ($file && $file->isValid()) {
                        $originalName = $file->getClientOriginalName();
                        $ext = $file->getClientOriginalExtension();
                        $size = $file->getSize();
                        $mime = $file->getMimeType();
                        $fileName = time() . '_' . Str::random(10) . '.' . $ext;
                        $file->move($targetDir, $fileName);

                        $existingAttachments[] = [
                            'path'      => 'uploads/accounts-expenses/' . $fileName,
                            'name'      => $originalName,
                            'size'      => $size,
                            'mime_type' => $mime,
                        ];
                    }
                }
            }

            $isFixed = $validated['expense_type'] === 'Fixed';
            $isRecurring = $isFixed && $request->boolean('is_recurring');
            $dueDay = $isRecurring
                ? ($request->filled('recurring_day_of_month') ? (int) $request->recurring_day_of_month : (int) date('d', strtotime($validated['expense_date'])))
                : null;

            $updateData = [
                'expense_date'                   => $validated['expense_date'],
                'account_expense_category_id'    => $validated['account_expense_category_id'],
                'account_expense_subcategory_id' => $validated['account_expense_subcategory_id'] ?? null,
                'vendor_id'                      => $validated['vendor_id'] ?? null,
                'amount'                         => $validated['amount'],
                'expense_type'                   => $validated['expense_type'],
                'is_recurring'                   => $isRecurring,
                'recurring_day_of_month'         => $dueDay,
                'recurring_end_date'             => $isRecurring ? ($validated['recurring_end_date'] ?? null) : null,
                'invoice_no'                     => $validated['invoice_no'] ?? null,
                'remarks'                        => $validated['remarks'] ?? null,
                'attachments'                    => array_values($existingAttachments),
                'updated_by'                     => $user?->id,
            ];

            if ($isRecurring && empty($expense->last_generated_month)) {
                $updateData['last_generated_month'] = date('Y-m', strtotime($validated['expense_date']));
            }

            $expense->update($updateData);
            $expense->load(['category', 'subcategory', 'vendor', 'creator', 'updater']);

            return response()->json([
                'status'  => true,
                'message' => "Expense {$expense->expense_number} updated successfully!",
                'data'    => $this->formatExpenseItem($expense),
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile updateExpense error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to update expense: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete an expense record (Soft delete).
     */
    public function destroyExpense(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;

            $expense = AccountExpense::when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->findOrFail($id);

            $number = $expense->expense_number;
            $expense->delete();

            return response()->json([
                'status'  => true,
                'message' => "Expense '{$number}' deleted successfully!",
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to delete expense: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove a specific attachment by index.
     */
    public function deleteExpenseAttachment(Request $request, $id, $index): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;

            $expense = AccountExpense::when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->findOrFail($id);

            $attachments = $expense->attachments ?? [];
            $index = intval($index);

            if (isset($attachments[$index])) {
                $att = $attachments[$index];
                $path = is_array($att) ? ($att['path'] ?? '') : $att;
                $fullPath = public_path($path);
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                }

                array_splice($attachments, $index, 1);
                $expense->attachments = array_values($attachments);
                $expense->save();

                return response()->json([
                    'status'  => true,
                    'message' => 'Attachment removed successfully!',
                ]);
            }

            return response()->json([
                'status'  => false,
                'message' => 'Attachment not found.',
            ], 404);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to remove attachment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Trigger generation of recurring fixed expenses for a given month.
     */
    public function syncRecurringExpenses(Request $request): JsonResponse
    {
        try {
            $targetMonth = $request->input('month')
                ? Carbon::createFromFormat('Y-m', $request->input('month'))->startOfMonth()
                : now()->startOfMonth();
            $force = $request->boolean('force');

            $count = GenerateRecurringAccountExpensesCommand::generateForMonth($targetMonth, $force);

            return response()->json([
                'status'  => true,
                'count'   => $count,
                'message' => $count > 0
                    ? "{$count} recurring fixed expense(s) generated for {$targetMonth->format('F Y')}!"
                    : "All recurring fixed expenses are already up to date for {$targetMonth->format('F Y')}.",
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to sync recurring expenses: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Expense Pivot Report: Category and Subcategory aggregated totals.
     */
    public function expensePivot(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;

            $dateFrom    = $request->input('date_from');
            $dateTo      = $request->input('date_to');
            $month       = $request->input('month');
            $expenseType = $request->input('expense_type');
            $categoryId  = $request->input('category_id');

            $baseQuery = AccountExpense::query()
                ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->when($month, function ($q) use ($month) {
                    $parts = explode('-', $month);
                    if (count($parts) === 2) {
                        $q->whereYear('expense_date', $parts[0])
                          ->whereMonth('expense_date', $parts[1]);
                    }
                })
                ->when($dateFrom, fn ($q) => $q->whereDate('expense_date', '>=', $dateFrom))
                ->when($dateTo, fn ($q) => $q->whereDate('expense_date', '<=', $dateTo))
                ->when($expenseType, fn ($q) => $q->where('expense_type', $expenseType))
                ->when($categoryId, fn ($q) => $q->where('account_expense_category_id', $categoryId));

            $expenses = (clone $baseQuery)
                ->with(['category', 'subcategory', 'vendor'])
                ->get();

            // If checking future month, include projected recurring fixed expenses
            if ($month && $month >= now()->addMonth()->format('Y-m')) {
                $targetFutureCarbon = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
                $futureTemplates = AccountExpense::activeRecurring($targetFutureCarbon)
                    ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                    ->when($categoryId, fn ($q) => $q->where('account_expense_category_id', $categoryId))
                    ->when($expenseType && $expenseType !== 'Fixed', fn ($q) => $q->whereRaw('1 = 0'))
                    ->with(['category', 'subcategory', 'vendor'])
                    ->get();

                foreach ($futureTemplates as $tpl) {
                    $alreadyExists = $expenses->contains(function ($e) use ($tpl, $targetFutureCarbon) {
                        return ($e->id == $tpl->id || $e->parent_expense_id == $tpl->id) &&
                               $e->expense_date &&
                               $e->expense_date->format('Y-m') === $targetFutureCarbon->format('Y-m');
                    });

                    if (!$alreadyExists) {
                        $day = min($tpl->recurring_day_of_month ?: ($tpl->expense_date ? $tpl->expense_date->day : 1), $targetFutureCarbon->daysInMonth);
                        $virtual = clone $tpl;
                        $virtual->expense_date = $targetFutureCarbon->copy()->day($day);
                        $virtual->auto_generated = true;
                        $expenses->push($virtual);
                    }
                }
            }

            $allCategories = AccountExpenseCategory::active()
                ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->with('activeSubcategories')
                ->orderBy('name')
                ->get();

            $groupedByCategory = $expenses->groupBy('account_expense_category_id');

            $pivotRows = [];
            $grandTotalFixed = 0.0;
            $grandTotalVariable = 0.0;
            $grandTotalAmount = 0.0;
            $grandTotalCount = 0;

            foreach ($allCategories as $cat) {
                $catExpenses = $groupedByCategory->get($cat->id, collect());
                $subcatExpensesGrouped = $catExpenses->groupBy(fn ($item) => $item->account_expense_subcategory_id ?: 0);

                $subcatRows = [];
                $catFixedSum = 0.0;
                $catVarSum = 0.0;
                $catTotalSum = 0.0;
                $catCountSum = $catExpenses->count();

                // 1. Active master subcategories
                foreach ($cat->activeSubcategories as $subcat) {
                    $items = $subcatExpensesGrouped->get($subcat->id, collect());
                    $fixed = (float) $items->where('expense_type', 'Fixed')->sum('amount');
                    $variable = (float) $items->where('expense_type', 'Variable')->sum('amount');
                    $total = $fixed + $variable;
                    $count = $items->count();

                    if ($count > 0 || $request->has('show_all')) {
                        $subcatRows[] = [
                            'id'       => $subcat->id,
                            'name'     => $subcat->name,
                            'code'     => $subcat->code,
                            'count'    => $count,
                            'fixed'    => $fixed,
                            'variable' => $variable,
                            'total'    => $total,
                        ];
                    }
                    $catFixedSum += $fixed;
                    $catVarSum += $variable;
                    $catTotalSum += $total;
                }

                // 2. Direct under category (no subcategory)
                $directItems = $subcatExpensesGrouped->get(0, collect());
                if ($directItems->isNotEmpty()) {
                    $fixed = (float) $directItems->where('expense_type', 'Fixed')->sum('amount');
                    $variable = (float) $directItems->where('expense_type', 'Variable')->sum('amount');
                    $total = $fixed + $variable;
                    $count = $directItems->count();

                    $subcatRows[] = [
                        'id'       => null,
                        'name'     => 'Direct / General (No Subcategory)',
                        'code'     => null,
                        'count'    => $count,
                        'fixed'    => $fixed,
                        'variable' => $variable,
                        'total'    => $total,
                    ];
                    $catFixedSum += $fixed;
                    $catVarSum += $variable;
                    $catTotalSum += $total;
                }

                // 3. Other subcategories not in active list
                $masterSubcatIds = $cat->activeSubcategories->pluck('id')->toArray();
                foreach ($subcatExpensesGrouped as $subId => $items) {
                    if ($subId != 0 && !in_array($subId, $masterSubcatIds)) {
                        $firstItem = $items->first();
                        $subName = $firstItem?->subcategory?->name ?: "Subcategory #{$subId}";
                        $fixed = (float) $items->where('expense_type', 'Fixed')->sum('amount');
                        $variable = (float) $items->where('expense_type', 'Variable')->sum('amount');
                        $total = $fixed + $variable;
                        $count = $items->count();

                        $subcatRows[] = [
                            'id'       => $subId,
                            'name'     => $subName,
                            'code'     => null,
                            'count'    => $count,
                            'fixed'    => $fixed,
                            'variable' => $variable,
                            'total'    => $total,
                        ];
                        $catFixedSum += $fixed;
                        $catVarSum += $variable;
                        $catTotalSum += $total;
                    }
                }

                if ($catCountSum > 0 || $request->has('show_all')) {
                    $pivotRows[] = [
                        'category_id'   => $cat->id,
                        'category_name' => $cat->name,
                        'category_code' => $cat->code,
                        'count'         => $catCountSum,
                        'fixed'         => $catFixedSum,
                        'variable'      => $catVarSum,
                        'total'         => $catTotalSum,
                        'subcategories' => $subcatRows,
                        'expenses'      => $catExpenses->sortByDesc(function ($e) {
                            if (!$e->expense_date) return 0;
                            return $e->expense_date instanceof \Carbon\CarbonInterface ? $e->expense_date->timestamp : (strtotime((string) $e->expense_date) ?: 0);
                        })->values()->map(fn($e) => $this->formatExpenseItem($e)),
                    ];

                    $grandTotalFixed += $catFixedSum;
                    $grandTotalVariable += $catVarSum;
                    $grandTotalAmount += $catTotalSum;
                    $grandTotalCount += $catCountSum;
                }
            }

            // Uncategorized
            $unlinked = $expenses->filter(fn ($e) => empty($e->account_expense_category_id) || !$allCategories->contains('id', $e->account_expense_category_id));
            if ($unlinked->isNotEmpty()) {
                $fixed = (float) $unlinked->where('expense_type', 'Fixed')->sum('amount');
                $variable = (float) $unlinked->where('expense_type', 'Variable')->sum('amount');
                $total = $fixed + $variable;
                $count = $unlinked->count();

                $pivotRows[] = [
                    'category_id'   => null,
                    'category_name' => 'Other / Uncategorized',
                    'category_code' => null,
                    'count'         => $count,
                    'fixed'         => $fixed,
                    'variable'      => $variable,
                    'total'         => $total,
                    'subcategories' => [
                        [
                            'id'       => null,
                            'name'     => 'Unspecified',
                            'code'     => null,
                            'count'    => $count,
                            'fixed'    => $fixed,
                            'variable' => $variable,
                            'total'    => $total,
                        ]
                    ],
                    'expenses'      => $unlinked->map(fn($e) => $this->formatExpenseItem($e)),
                ];

                $grandTotalFixed += $fixed;
                $grandTotalVariable += $variable;
                $grandTotalAmount += $total;
                $grandTotalCount += $count;
            }

            return response()->json([
                'status' => true,
                'data'   => [
                    'grand_total_amount'   => $grandTotalAmount,
                    'grand_total_fixed'    => $grandTotalFixed,
                    'grand_total_variable' => $grandTotalVariable,
                    'grand_total_count'    => $grandTotalCount,
                    'rows'                 => $pivotRows,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts expensePivot error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load expense pivot data: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Formatting Helpers for Accounts Expenses
    // ─────────────────────────────────────────────────────────────────────────

    private function formatExpenseItem($exp): array
    {
        $category = $exp->category ?? null;
        $subcategory = $exp->subcategory ?? null;
        $vendor = $exp->vendor ?? null;
        $creator = $exp->creator ?? null;

        $expDate = $exp->expense_date ?? null;
        $dateFormatted = $expDate ? Carbon::parse($expDate)->format('d M Y') : '—';
        $dateRaw = $expDate ? Carbon::parse($expDate)->format('Y-m-d') : null;

        $attachList = [];
        if (is_array($exp->attachment_list ?? null)) {
            $attachList = $exp->attachment_list;
        } elseif (method_exists($exp, 'getAttachmentListAttribute')) {
            $attachList = $exp->attachment_list;
        }

        return [
            'id'                             => $exp->id,
            'expense_number'                 => $exp->expense_number ?? '—',
            'expense_date'                   => $dateRaw,
            'expense_date_formatted'         => $dateFormatted,
            'account_expense_category_id'    => $exp->account_expense_category_id ?? null,
            'category_name'                  => $category?->name ?: '—',
            'category_code'                  => $category?->code,
            'account_expense_subcategory_id' => $exp->account_expense_subcategory_id ?? null,
            'subcategory_name'               => $subcategory?->name ?: '—',
            'subcategory_code'               => $subcategory?->code,
            'vendor_id'                      => $exp->vendor_id ?? null,
            'vendor_name'                    => $vendor?->vendor_name ?: '—',
            'vendor_company'                 => $vendor?->company_name,
            'amount'                         => (float) ($exp->amount ?? 0),
            'formatted_amount'               => '₹' . number_format((float) ($exp->amount ?? 0), 2),
            'expense_type'                   => $exp->expense_type ?? 'Variable',
            'is_recurring'                   => (bool) ($exp->is_recurring ?? false),
            'recurring_day_of_month'         => $exp->recurring_day_of_month ?? null,
            'recurring_end_date'             => !empty($exp->recurring_end_date) ? Carbon::parse($exp->recurring_end_date)->format('Y-m-d') : null,
            'auto_generated'                 => (bool) ($exp->auto_generated ?? false),
            'parent_expense_id'              => $exp->parent_expense_id ?? null,
            'invoice_no'                     => $exp->invoice_no ?: '—',
            'remarks'                        => $exp->remarks ?: '',
            'created_by'                     => $exp->created_by ?? null,
            'creator_name'                   => $creator?->name ?: 'Admin',
            'attachments_count'              => count($attachList),
            'attachment_list'                => $attachList,
            'is_forecast'                    => (bool) ($exp->is_forecast ?? false),
        ];
    }

    private function formatExpenseDetail(AccountExpense $exp): array
    {
        $item = $this->formatExpenseItem($exp);
        $updater = $exp->updater;
        $parent = $exp->parent;
        $generatedChildren = $exp->generatedChildren;

        $item['updated_by'] = $exp->updated_by;
        $item['updater_name'] = $updater?->name;
        $item['created_at'] = $exp->created_at ? $exp->created_at->format('d M Y h:i A') : null;
        $item['updated_at'] = $exp->updated_at ? $exp->updated_at->format('d M Y h:i A') : null;

        if ($parent) {
            $item['parent_expense'] = [
                'id'             => $parent->id,
                'expense_number' => $parent->expense_number,
                'expense_date'   => $parent->expense_date?->format('d M Y'),
                'amount'         => (float) $parent->amount,
            ];
        }

        if ($generatedChildren && $generatedChildren->isNotEmpty()) {
            $item['generated_children'] = $generatedChildren->map(fn($c) => [
                'id'             => $c->id,
                'expense_number' => $c->expense_number,
                'expense_date'   => $c->expense_date?->format('d M Y'),
                'amount'         => (float) $c->amount,
            ]);
        } else {
            $item['generated_children'] = [];
        }

        return $item;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  8. ACCOUNTS SALES (INVOICES & PAYMENTS)
    // ─────────────────────────────────────────────────────────────────────────

    public function salesInvoices(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;
            $perPage = (int) $request->query('per_page', 15);

            $query = Invoice::query()->with(['customer', 'salesPerson']);
            if ($companyId) {
                $query->where('company_id', $companyId);
            }

            if ($search = trim((string) $request->query('search'))) {
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'LIKE', "%{$search}%")
                      ->orWhere('customer_name', 'LIKE', "%{$search}%")
                      ->orWhere('company_name', 'LIKE', "%{$search}%")
                      ->orWhere('customer_phone', 'LIKE', "%{$search}%")
                      ->orWhere('customer_email', 'LIKE', "%{$search}%");
                });
            }

            if ($status = $request->query('status')) {
                if ($status !== 'all' && $status !== '') {
                    $query->where('status', $status);
                }
            }

            if ($from = $request->query('from_date')) {
                $query->whereDate('invoice_date', '>=', $from);
            }
            if ($to = $request->query('to_date')) {
                $query->whereDate('invoice_date', '<=', $to);
            }

            // KPI aggregates
            $baseQuery = Invoice::query();
            if ($companyId) {
                $baseQuery->where('company_id', $companyId);
            }
            $allInvoices = (clone $baseQuery)->get();
            $stats = [
                'total_count'   => $allInvoices->count(),
                'total_amount'  => (float) $allInvoices->sum('total_amount'),
                'paid_amount'   => (float) $allInvoices->sum('paid_amount'),
                'total_tds'     => (float) $allInvoices->sum('total_tds'),
                'balance_due'   => (float) $allInvoices->sum('balance_due'),
                'paid_count'    => $allInvoices->where('status', 'paid')->count(),
                'partial_count' => $allInvoices->where('status', 'partially_paid')->count(),
                'draft_count'   => $allInvoices->where('status', 'draft')->count(),
                'overdue_count' => $allInvoices->where('status', 'overdue')->count(),
            ];

            $invoices = $query->orderBy('invoice_date', 'desc')
                              ->orderBy('id', 'desc')
                              ->paginate($perPage);

            return response()->json([
                'status' => true,
                'stats'  => $stats,
                'data'   => $invoices->items(),
                'meta'   => [
                    'current_page' => $invoices->currentPage(),
                    'last_page'    => $invoices->lastPage(),
                    'per_page'     => $invoices->perPage(),
                    'total'        => $invoices->total(),
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts salesInvoices error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load sales invoices: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function salesInvoiceMeta(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;

            $invoiceNumber = Invoice::generateNextInvoiceNumber($companyId);
            $setting       = InvoiceSetting::forCompany($companyId);

            $customers = CustomerMaster::query()
                ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->active()
                ->orderBy('customer_name')
                ->get()
                ->map(fn($c) => [
                    'id'                 => $c->id,
                    'customer_name'      => $c->customer_name,
                    'company_name'       => $c->company_name,
                    'mobile_number'      => $c->mobile_number,
                    'email'              => $c->email,
                    'gst_number'         => $c->gst_number,
                    'pan_number'         => $c->pan_number,
                    'payment_terms'      => $c->payment_terms ?: 'Due on Receipt',
                    'billing_attention'  => $c->billing_attention ?: $c->customer_name,
                    'billing_address'    => trim(($c->billing_address_street_1 ?? '') . ' ' . ($c->billing_address_street_2 ?? '')),
                    'billing_city'       => $c->billing_city,
                    'billing_state'      => $c->billing_state,
                    'billing_pincode'    => $c->billing_pin_code,
                    'billing_country'    => $c->billing_country ?: 'India',
                    'shipping_attention' => $c->shipping_attention ?: ($c->billing_attention ?: $c->customer_name),
                    'shipping_address'   => trim(($c->shipping_address_street_1 ?? '') . ' ' . ($c->shipping_address_street_2 ?? '')),
                    'shipping_city'      => $c->shipping_city ?: $c->billing_city,
                    'shipping_state'     => $c->shipping_state ?: $c->billing_state,
                    'shipping_pincode'   => $c->shipping_pin_code ?: $c->billing_pin_code,
                    'shipping_country'   => $c->shipping_country ?: ($c->billing_country ?: 'India'),
                ]);

            $items = ItemMaster::query()
                ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->active()
                ->orderBy('item_name')
                ->get()
                ->map(fn($it) => [
                    'id'           => $it->id,
                    'item_name'    => $it->item_name,
                    'description'  => $it->description,
                    'hsn_sac_code' => $it->hsn_sac_code,
                    'rate'         => (float) $it->rate,
                    'gst'          => (float) $it->gst,
                ]);

            $salesUsers = User::query()
                ->where(function ($query) {
                    $deptIds = Department::where(function ($dq) {
                        $dq->where('name', 'LIKE', '%Sales%')
                           ->orWhere('name', 'LIKE', '%Customer Success%')
                           ->orWhere('name', 'LIKE', '%CST%');
                    })->pluck('id');

                    if ($deptIds->isNotEmpty()) {
                        $query->whereHas('roles', fn ($rq) => $rq->whereIn('department_id', $deptIds));
                    }
                })
                ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'email']);

            if ($salesUsers->isEmpty()) {
                $salesUsers = User::where('is_active', true)->orderBy('name')->take(20)->get(['id', 'name', 'email']);
            }

            $states = [];
            foreach (InvoiceController::$indianStates as $codeName => $stateName) {
                $states[] = [
                    'label' => $codeName,
                    'value' => $codeName,
                    'state' => $stateName,
                ];
            }

            return response()->json([
                'status' => true,
                'data'   => [
                    'invoice_number' => $invoiceNumber,
                    'customers'      => $customers,
                    'items'          => $items,
                    'sales_users'    => $salesUsers,
                    'states'         => $states,
                    'default_terms'  => $setting->terms_conditions ?: InvoiceSetting::defaultTerms(),
                    'default_notes'  => $setting->notes,
                    'payment_terms'  => ['Due on Receipt', 'Net 15', 'Net 30', 'Net 45', 'Net 60'],
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts salesInvoiceMeta error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load invoice meta: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function showSalesInvoice(int $id): JsonResponse
    {
        try {
            $companyId = auth()->user()?->company_id;
            $invoice = Invoice::with(['items.itemMaster', 'customer', 'salesPerson', 'creator', 'payments.recordedBy', 'creditNotes.creator'])
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->findOrFail($id);

            return response()->json([
                'status' => true,
                'data'   => $invoice,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Invoice not found: ' . $e->getMessage(),
            ], 404);
        }
    }

    public function storeSalesInvoice(Request $request): JsonResponse
    {
        try {
            $companyId = $request->user()?->company_id;
            $userId    = $request->user()?->id;

            $validated = $request->validate([
                'customer_id'        => 'nullable|integer',
                'customer_name'      => 'nullable|string|max:255',
                'company_name'       => 'nullable|string|max:255',
                'customer_email'     => 'nullable|email|max:255',
                'customer_phone'     => 'nullable|string|max:50',
                'customer_gstin'     => 'nullable|string|max:50',
                'customer_pan'       => 'nullable|string|max:30',
                'sales_person_id'    => 'nullable|integer',

                'invoice_number'     => 'required|string|max:100',
                'invoice_date'       => 'required|date',
                'due_date'           => 'nullable|date',
                'payment_terms'      => 'nullable|string|max:100',
                'place_of_supply'    => 'nullable|string|max:100',

                'billing_attention'  => 'nullable|string|max:255',
                'billing_address'    => 'nullable|string',
                'billing_city'       => 'nullable|string|max:100',
                'billing_state'      => 'nullable|string|max:100',
                'billing_pincode'    => 'nullable|string|max:20',
                'billing_country'    => 'nullable|string|max:100',
                'billing_gstin'      => 'nullable|string|max:50',

                'shipping_attention' => 'nullable|string|max:255',
                'shipping_address'   => 'nullable|string',
                'shipping_city'      => 'nullable|string|max:100',
                'shipping_state'     => 'nullable|string|max:100',
                'shipping_pincode'   => 'nullable|string|max:20',
                'shipping_country'   => 'nullable|string|max:100',
                'shipping_gstin'     => 'nullable|string|max:50',

                'items'              => 'required|array|min:1',
                'items.*.item_id'    => 'nullable|integer',
                'items.*.item_name'  => 'required|string|max:255',
                'items.*.description'=> 'nullable|string',
                'items.*.hsn_sac_code'=> 'nullable|string|max:50',
                'items.*.qty'        => 'required|numeric|min:0.01',
                'items.*.rate'       => 'required|numeric|min:0',
                'items.*.amount'     => 'required|numeric|min:0',
                'items.*.cgst_percent' => 'nullable|numeric|min:0|max:100',
                'items.*.cgst_amount'  => 'nullable|numeric|min:0',
                'items.*.sgst_percent' => 'nullable|numeric|min:0|max:100',
                'items.*.sgst_amount'  => 'nullable|numeric|min:0',
                'items.*.igst_percent' => 'nullable|numeric|min:0|max:100',
                'items.*.igst_amount'  => 'nullable|numeric|min:0',
                'items.*.total_amount' => 'nullable|numeric|min:0',

                'subtotal'           => 'required|numeric|min:0',
                'total_cgst'         => 'nullable|numeric|min:0',
                'total_sgst'         => 'nullable|numeric|min:0',
                'total_igst'         => 'nullable|numeric|min:0',
                'total_tax'          => 'nullable|numeric|min:0',
                'round_off'          => 'nullable|numeric',
                'total_amount'       => 'required|numeric|min:0',
                'paid_amount'        => 'nullable|numeric|min:0',
                'balance_due'        => 'required|numeric',

                'customer_notes'     => 'nullable|string',
                'terms_conditions'   => 'nullable|string',
                'status'             => 'nullable|string|in:draft,sent,paid,partially_paid,overdue,cancelled',
            ]);

            if (empty($validated['customer_name']) && !empty($request->customer_id)) {
                $cust = CustomerMaster::find($request->customer_id);
                if ($cust) {
                    $validated['customer_name']  = $cust->customer_name;
                    $validated['company_name']   = $validated['company_name'] ?? $cust->company_name;
                    $validated['customer_email'] = $validated['customer_email'] ?? $cust->email;
                    $validated['customer_phone'] = $validated['customer_phone'] ?? $cust->mobile_number;
                    $validated['customer_gstin'] = $validated['customer_gstin'] ?? ($validated['billing_gstin'] ?? $cust->gst_number);
                    $validated['customer_pan']   = $validated['customer_pan'] ?? $cust->pan_number;
                }
            }
            if (empty($validated['customer_name'])) {
                $validated['customer_name'] = 'Valued Customer';
            }

            \Illuminate\Support\Facades\DB::beginTransaction();

            $grandTotal = (float) $validated['total_amount'];
            $paidAmount = (float) ($validated['paid_amount'] ?? 0);
            $balanceDue = max(0, $grandTotal - $paidAmount);

            $status = $validated['status'] ?? 'sent';
            if ($paidAmount >= $grandTotal && $grandTotal > 0) {
                $status = 'paid';
            } elseif ($paidAmount > 0) {
                $status = 'partially_paid';
            }

            $invoiceData = collect($validated)->except(['items'])->toArray();
            $invoiceData['company_id']  = $companyId;
            $invoiceData['created_by']  = $userId;
            $invoiceData['updated_by']  = $userId;
            $invoiceData['paid_amount'] = $paidAmount;
            $invoiceData['balance_due'] = $balanceDue;
            $invoiceData['status']      = $status;

            $invoice = Invoice::create($invoiceData);

            foreach ($validated['items'] as $index => $itemRow) {
                $itemRow['invoice_id']   = $invoice->id;
                $itemRow['sort_order']   = $index + 1;
                $itemRow['total_amount'] = !empty($itemRow['total_amount']) ? $itemRow['total_amount'] : ($itemRow['amount'] ?? 0);
                InvoiceItem::create($itemRow);
            }

            $setting = InvoiceSetting::forCompany($companyId);
            $expectedNumber = $setting->formatInvoiceNumber($setting->next_number);
            if ($invoice->invoice_number === $expectedNumber) {
                $setting->increment('next_number');
            }

            \Illuminate\Support\Facades\DB::commit();

            $invoice->load(['items', 'customer', 'salesPerson']);

            return response()->json([
                'status'  => true,
                'message' => "Invoice #{$invoice->invoice_number} created successfully!",
                'data'    => $invoice,
            ]);
        } catch (Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            Log::error('Mobile Accounts storeSalesInvoice error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to create invoice: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateSalesInvoice(Request $request, int $id): JsonResponse
    {
        try {
            $companyId = $request->user()?->company_id;
            $userId    = $request->user()?->id;

            $invoice = Invoice::when($companyId, fn($q) => $q->where('company_id', $companyId))->findOrFail($id);

            $validated = $request->validate([
                'customer_id'        => 'nullable|integer',
                'customer_name'      => 'nullable|string|max:255',
                'company_name'       => 'nullable|string|max:255',
                'customer_email'     => 'nullable|email|max:255',
                'customer_phone'     => 'nullable|string|max:50',
                'customer_gstin'     => 'nullable|string|max:50',
                'customer_pan'       => 'nullable|string|max:30',
                'sales_person_id'    => 'nullable|integer',

                'invoice_date'       => 'required|date',
                'due_date'           => 'nullable|date',
                'payment_terms'      => 'nullable|string|max:100',
                'place_of_supply'    => 'nullable|string|max:100',

                'billing_attention'  => 'nullable|string|max:255',
                'billing_address'    => 'nullable|string',
                'billing_city'       => 'nullable|string|max:100',
                'billing_state'      => 'nullable|string|max:100',
                'billing_pincode'    => 'nullable|string|max:20',
                'billing_country'    => 'nullable|string|max:100',
                'billing_gstin'      => 'nullable|string|max:50',

                'shipping_attention' => 'nullable|string|max:255',
                'shipping_address'   => 'nullable|string',
                'shipping_city'      => 'nullable|string|max:100',
                'shipping_state'     => 'nullable|string|max:100',
                'shipping_pincode'   => 'nullable|string|max:20',
                'shipping_country'   => 'nullable|string|max:100',
                'shipping_gstin'     => 'nullable|string|max:50',

                'items'              => 'required|array|min:1',
                'items.*.item_id'    => 'nullable|integer',
                'items.*.item_name'  => 'required|string|max:255',
                'items.*.description'=> 'nullable|string',
                'items.*.hsn_sac_code'=> 'nullable|string|max:50',
                'items.*.qty'        => 'required|numeric|min:0.01',
                'items.*.rate'       => 'required|numeric|min:0',
                'items.*.amount'     => 'required|numeric|min:0',
                'items.*.cgst_percent' => 'nullable|numeric|min:0|max:100',
                'items.*.cgst_amount'  => 'nullable|numeric|min:0',
                'items.*.sgst_percent' => 'nullable|numeric|min:0|max:100',
                'items.*.sgst_amount'  => 'nullable|numeric|min:0',
                'items.*.igst_percent' => 'nullable|numeric|min:0|max:100',
                'items.*.igst_amount'  => 'nullable|numeric|min:0',
                'items.*.total_amount' => 'nullable|numeric|min:0',

                'subtotal'           => 'required|numeric|min:0',
                'total_cgst'         => 'nullable|numeric|min:0',
                'total_sgst'         => 'nullable|numeric|min:0',
                'total_igst'         => 'nullable|numeric|min:0',
                'total_tax'          => 'nullable|numeric|min:0',
                'round_off'          => 'nullable|numeric',
                'total_amount'       => 'required|numeric|min:0',

                'customer_notes'     => 'nullable|string',
                'terms_conditions'   => 'nullable|string',
                'status'             => 'nullable|string|in:draft,sent,paid,partially_paid,overdue,cancelled',
            ]);

            \Illuminate\Support\Facades\DB::beginTransaction();

            $invoiceData = collect($validated)->except(['items'])->toArray();
            $invoiceData['updated_by'] = $userId;

            $invoice->update($invoiceData);

            $invoice->items()->delete();
            foreach ($validated['items'] as $index => $itemRow) {
                $itemRow['invoice_id']   = $invoice->id;
                $itemRow['sort_order']   = $index + 1;
                $itemRow['total_amount'] = !empty($itemRow['total_amount']) ? $itemRow['total_amount'] : ($itemRow['amount'] ?? 0);
                InvoiceItem::create($itemRow);
            }

            $invoice->recalculatePaymentStatus();

            \Illuminate\Support\Facades\DB::commit();

            $invoice->load(['items', 'customer', 'salesPerson']);

            return response()->json([
                'status'  => true,
                'message' => "Invoice #{$invoice->invoice_number} updated successfully!",
                'data'    => $invoice,
            ]);
        } catch (Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            Log::error('Mobile Accounts updateSalesInvoice error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to update invoice: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateInvoiceSalesPerson(Request $request, int $id): JsonResponse
    {
        try {
            $companyId = $request->user()?->company_id;
            $invoice = Invoice::when($companyId, fn($q) => $q->where('company_id', $companyId))->findOrFail($id);

            $validated = $request->validate([
                'sales_person_id' => 'nullable|exists:users,id',
            ]);

            $invoice->update([
                'sales_person_id' => $validated['sales_person_id'] ?? null,
                'updated_by'      => $request->user()?->id,
            ]);

            $invoice->load('salesPerson');

            return response()->json([
                'status'            => true,
                'message'           => 'Sales person updated successfully!',
                'sales_person_id'   => $invoice->sales_person_id,
                'sales_person_name' => $invoice->salesPerson?->name ?? '—',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to update sales person: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroySalesInvoice(int $id): JsonResponse
    {
        try {
            $companyId = auth()->user()?->company_id;
            $invoice = Invoice::when($companyId, fn($q) => $q->where('company_id', $companyId))->findOrFail($id);

            $num = $invoice->invoice_number;
            $invoice->items()->delete();
            $invoice->payments()->delete();
            $invoice->delete();

            return response()->json([
                'status'  => true,
                'message' => "Invoice #{$num} deleted successfully.",
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to delete invoice: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function storeInvoicePayment(Request $request, int $id): JsonResponse
    {
        try {
            $companyId = $request->user()?->company_id;
            $invoice = Invoice::when($companyId, fn($q) => $q->where('company_id', $companyId))->findOrFail($id);

            $validated = $request->validate([
                'payment_date'     => 'required|date',
                'amount_received'  => 'required|numeric|min:0.01',
                'is_tds_deducted'  => 'nullable|boolean',
                'tds_percentage'   => 'nullable|numeric|min:0|max:100',
                'tds_amount'       => 'nullable|numeric|min:0',
                'payment_mode'     => 'required|string|max:50',
                'reference_number' => 'nullable|string|max:100',
                'bank_name'        => 'nullable|string|max:100',
                'notes'            => 'nullable|string',
                'attachment'       => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp,doc,docx|max:10240',
            ]);

            $isTds = $request->boolean('is_tds_deducted');
            $amountReceived = (float) $validated['amount_received'];
            $tdsPct = $isTds ? (float) ($validated['tds_percentage'] ?? 0) : 0;
            $tdsAmount = $isTds ? (float) ($validated['tds_amount'] ?? 0) : 0;
            $totalSettled = $amountReceived + $tdsAmount;

            $attachmentPath = null;
            $attachmentName = null;
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $filename = time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
                $destination = public_path('uploads/invoices/payments');
                if (!file_exists($destination)) {
                    mkdir($destination, 0755, true);
                }
                $file->move($destination, $filename);
                $attachmentPath = 'uploads/invoices/payments/' . $filename;
                $attachmentName = $file->getClientOriginalName();
            }

            $payCount = InvoicePayment::where('company_id', $invoice->company_id)->count() + 1;
            $paymentNumber = 'RCP-' . date('ym') . '-' . str_pad($payCount, 4, '0', STR_PAD_LEFT);

            $payment = InvoicePayment::create([
                'company_id'           => $invoice->company_id,
                'invoice_id'           => $invoice->id,
                'customer_id'          => $invoice->customer_id,
                'payment_number'       => $paymentNumber,
                'payment_date'         => $validated['payment_date'],
                'amount_received'      => $amountReceived,
                'is_tds_deducted'      => $isTds,
                'tds_percentage'       => $tdsPct,
                'tds_amount'           => $tdsAmount,
                'total_settled_amount' => $totalSettled,
                'payment_mode'         => $validated['payment_mode'],
                'reference_number'     => $validated['reference_number'] ?? null,
                'bank_name'            => $validated['bank_name'] ?? null,
                'notes'                => $validated['notes'] ?? null,
                'attachment_path'      => $attachmentPath,
                'attachment_name'      => $attachmentName,
                'recorded_by'          => $request->user()?->id,
            ]);

            $invoice->recalculatePaymentStatus();
            $invoice->refresh();

            return response()->json([
                'status'  => true,
                'message' => "Payment of ₹" . number_format($amountReceived, 2) . " recorded successfully!",
                'data'    => [
                    'payment'     => $payment,
                    'paid_amount' => (float) $invoice->paid_amount,
                    'balance_due' => (float) $invoice->balance_due,
                    'status'      => $invoice->status,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts storeInvoicePayment error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to record payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroyInvoicePayment(int $invoiceId, int $paymentId): JsonResponse
    {
        try {
            $companyId = auth()->user()?->company_id;
            $invoice = Invoice::when($companyId, fn($q) => $q->where('company_id', $companyId))->findOrFail($invoiceId);
            $payment = InvoicePayment::where('invoice_id', $invoice->id)->findOrFail($paymentId);

            if ($payment->attachment_path && file_exists(public_path($payment->attachment_path))) {
                @unlink(public_path($payment->attachment_path));
            }

            $payment->delete();
            $invoice->recalculatePaymentStatus();

            return response()->json([
                'status'  => true,
                'message' => 'Payment record deleted and invoice balance successfully updated.',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to delete payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function salesPayments(Request $request): JsonResponse
    {
        try {
            $companyId = $request->user()?->company_id;
            $perPage = (int) $request->query('per_page', 20);

            $query = InvoicePayment::query()
                ->with(['invoice', 'customer', 'recordedBy'])
                ->latest('payment_date')
                ->latest('id');

            if ($companyId) {
                $query->where('company_id', $companyId);
            }

            if ($search = trim((string) $request->query('search'))) {
                $query->where(function ($q) use ($search) {
                    $q->where('payment_number', 'LIKE', "%{$search}%")
                      ->orWhere('reference_number', 'LIKE', "%{$search}%")
                      ->orWhere('bank_name', 'LIKE', "%{$search}%")
                      ->orWhereHas('invoice', function ($iq) use ($search) {
                          $iq->where('invoice_number', 'LIKE', "%{$search}%")
                             ->orWhere('customer_name', 'LIKE', "%{$search}%")
                             ->orWhere('company_name', 'LIKE', "%{$search}%");
                      })
                      ->orWhereHas('customer', function ($cq) use ($search) {
                          $cq->where('customer_name', 'LIKE', "%{$search}%")
                             ->orWhere('company_name', 'LIKE', "%{$search}%");
                      });
                });
            }

            if ($mode = $request->query('payment_mode')) {
                if ($mode !== 'all' && $mode !== '') {
                    $query->where('payment_mode', $mode);
                }
            }

            if ($request->has('has_tds') && $request->query('has_tds') !== '' && $request->query('has_tds') !== 'all') {
                if ($request->boolean('has_tds')) {
                    $query->where(function($q) {
                        $q->where('is_tds_deducted', true)->orWhere('tds_amount', '>', 0);
                    });
                } else {
                    $query->where(function($q) {
                        $q->where('is_tds_deducted', false)->where('tds_amount', '<=', 0);
                    });
                }
            }

            if ($from = $request->query('from_date')) {
                $query->whereDate('payment_date', '>=', $from);
            }
            if ($to = $request->query('to_date')) {
                $query->whereDate('payment_date', '<=', $to);
            }

            $baseQuery = InvoicePayment::query();
            if ($companyId) {
                $baseQuery->where('company_id', $companyId);
            }
            $allPayments = (clone $baseQuery)->get();
            $stats = [
                'total_count'    => $allPayments->count(),
                'total_received' => (float) $allPayments->sum('amount_received'),
                'total_tds'      => (float) $allPayments->sum('tds_amount'),
                'total_settled'  => (float) $allPayments->sum('total_settled_amount'),
                'tds_count'      => $allPayments->where('tds_amount', '>', 0)->count(),
            ];

            $payments = $query->paginate($perPage);

            return response()->json([
                'status' => true,
                'stats'  => $stats,
                'data'   => $payments->items(),
                'meta'   => [
                    'current_page' => $payments->currentPage(),
                    'last_page'    => $payments->lastPage(),
                    'per_page'     => $payments->perPage(),
                    'total'        => $payments->total(),
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts salesPayments error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load payments: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroySalesPaymentStandalone(int $paymentId): JsonResponse
    {
        try {
            $companyId = auth()->user()?->company_id;
            $payment = InvoicePayment::when($companyId, fn($q) => $q->where('company_id', $companyId))->findOrFail($paymentId);

            $invoice = $payment->invoice;

            if ($payment->attachment_path && file_exists(public_path($payment->attachment_path))) {
                @unlink(public_path($payment->attachment_path));
            }

            $paymentNumber = $payment->payment_number;
            $payment->delete();

            if ($invoice) {
                $invoice->recalculatePaymentStatus();
            }

            return response()->json([
                'status'  => true,
                'message' => "Payment record #{$paymentNumber} deleted successfully.",
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to delete payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 14. ACCOUNTS SALES - CREDIT NOTES
    // ─────────────────────────────────────────────────────────────────────────

    public function salesCreditNotes(Request $request): JsonResponse
    {
        try {
            $companyId = $request->user()?->company_id;
            $perPage   = (int) $request->query('per_page', 15);

            $query = CreditNote::with(['invoice', 'customer', 'creator'])
                ->when($companyId, fn($q) => $q->where('company_id', $companyId));

            if ($search = trim((string) $request->query('search'))) {
                $query->where(function ($q) use ($search) {
                    $q->where('credit_note_number', 'LIKE', "%{$search}%")
                      ->orWhere('reason', 'LIKE', "%{$search}%")
                      ->orWhereHas('invoice', function ($iq) use ($search) {
                          $iq->where('invoice_number', 'LIKE', "%{$search}%");
                      })
                      ->orWhereHas('customer', function ($cq) use ($search) {
                          $cq->where('customer_name', 'LIKE', "%{$search}%")
                             ->orWhere('company_name', 'LIKE', "%{$search}%");
                      });
                });
            }

            if ($reason = $request->query('reason')) {
                if ($reason !== 'all' && $reason !== '') {
                    $query->where('reason', $reason);
                }
            }

            if ($status = $request->query('status')) {
                if ($status !== 'all' && $status !== '') {
                    $query->where('status', $status);
                }
            }

            if ($dateFrom = $request->query('date_from')) {
                $query->whereDate('credit_note_date', '>=', $dateFrom);
            }
            if ($dateTo = $request->query('date_to')) {
                $query->whereDate('credit_note_date', '<=', $dateTo);
            }

            // Summary stats matching Screenshot 1
            $allForCompany = CreditNote::query()->when($companyId, fn($q) => $q->where('company_id', $companyId));
            $totalCredited = (float) (clone $allForCompany)->where('status', '!=', 'void')->sum('total_amount');
            $totalCnsIssued = (clone $allForCompany)->count();
            $activeApplied = (clone $allForCompany)->where('status', 'issued')->count();
            $voidedCns = (clone $allForCompany)->where('status', 'void')->count();

            $stats = [
                'total_credited'   => $totalCredited,
                'total_cns_issued' => $totalCnsIssued,
                'active_applied'   => $activeApplied,
                'voided_cns'       => $voidedCns,
            ];

            $paginated = $query->orderBy('credit_note_date', 'desc')
                               ->orderBy('id', 'desc')
                               ->paginate($perPage);

            $data = collect($paginated->items())->map(function ($cn) {
                return [
                    'id'                 => $cn->id,
                    'credit_note_number' => $cn->credit_note_number,
                    'credit_note_date'   => $cn->credit_note_date?->format('Y-m-d'),
                    'invoice_id'         => $cn->invoice_id,
                    'invoice_number'     => $cn->invoice?->invoice_number,
                    'invoice_total'      => (float) ($cn->invoice?->total_amount ?? 0),
                    'customer_id'        => $cn->customer_id,
                    'customer_name'      => $cn->customer?->customer_name ?: ($cn->invoice?->customer_name ?: 'Valued Customer'),
                    'company_name'       => $cn->customer?->company_name ?: $cn->invoice?->company_name,
                    'reason'             => $cn->reason,
                    'reason_label'       => CreditNote::REASONS[$cn->reason] ?? $cn->reason,
                    'subtotal'           => (float) $cn->subtotal,
                    'cgst_amount'        => (float) $cn->cgst_amount,
                    'sgst_amount'        => (float) $cn->sgst_amount,
                    'igst_amount'        => (float) $cn->igst_amount,
                    'total_tax'          => (float) $cn->total_tax,
                    'total_amount'       => (float) $cn->total_amount,
                    'status'             => $cn->status, // 'issued' | 'void'
                    'notes'              => $cn->notes,
                    'attachment_path'    => $cn->attachment_path,
                    'attachment_url'     => $cn->attachment_url,
                    'attachment_name'    => $cn->attachment_name,
                    'created_by'         => $cn->created_by,
                    'creator_name'       => $cn->creator?->name,
                    'created_at'         => $cn->created_at?->toIso8601String(),
                    'created_at_human'   => $cn->created_at?->diffForHumans(),
                ];
            });

            return response()->json([
                'status'  => true,
                'stats'   => $stats,
                'reasons' => CreditNote::REASONS,
                'data'    => $data,
                'meta'    => [
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                    'per_page'     => $paginated->perPage(),
                    'total'        => $paginated->total(),
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts salesCreditNotes error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load credit notes: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function salesCreditNoteMeta(Request $request): JsonResponse
    {
        try {
            $companyId = $request->user()?->company_id;
            $nextCnNumber = CreditNote::generateNextNumber($companyId);

            $invoices = Invoice::query()
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->whereNotIn('status', ['cancelled'])
                ->orderBy('invoice_date', 'desc')
                ->get()
                ->map(function ($inv) {
                    $existingCredits = (float) $inv->creditNotes()->where('status', '!=', 'void')->sum('total_amount');
                    $maxCreditable   = max(0, round((float) $inv->total_amount - $existingCredits, 2));
                    return [
                        'id'                   => $inv->id,
                        'invoice_number'       => $inv->invoice_number,
                        'customer_id'          => $inv->customer_id,
                        'customer_name'        => $inv->customer_name,
                        'company_name'         => $inv->company_name,
                        'total_amount'         => (float) $inv->total_amount,
                        'balance_due'          => (float) $inv->balance_due,
                        'invoice_date'         => $inv->invoice_date?->format('Y-m-d'),
                        'existing_credit'      => $existingCredits,
                        'max_allowable_credit' => $maxCreditable,
                    ];
                });

            $selectedInvoice = null;
            if ($invoiceId = $request->query('invoice_id')) {
                $inv = Invoice::with(['items', 'customer'])
                    ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                    ->find($invoiceId);

                if ($inv) {
                    $existingCredits = (float) $inv->creditNotes()->where('status', '!=', 'void')->sum('total_amount');
                    $maxCreditable   = max(0, round((float) $inv->total_amount - $existingCredits, 2));
                    $selectedInvoice = [
                        'id'                   => $inv->id,
                        'invoice_number'       => $inv->invoice_number,
                        'customer_id'          => $inv->customer_id,
                        'customer_name'        => $inv->customer_name,
                        'company_name'         => $inv->company_name,
                        'total_amount'         => (float) $inv->total_amount,
                        'balance_due'          => (float) $inv->balance_due,
                        'invoice_date'         => $inv->invoice_date?->format('Y-m-d'),
                        'existing_credit'      => $existingCredits,
                        'max_allowable_credit' => $maxCreditable,
                        'items'                => $inv->items->map(fn($item) => [
                            'id'           => $item->id,
                            'item_name'    => $item->item_name,
                            'description'  => $item->description,
                            'hsn_sac_code' => $item->hsn_sac_code,
                            'qty'          => (float) $item->qty,
                            'rate'         => (float) $item->rate,
                            'amount'       => (float) $item->amount,
                            'cgst_percent' => (float) $item->cgst_percent,
                            'sgst_percent' => (float) $item->sgst_percent,
                            'igst_percent' => (float) $item->igst_percent,
                            'total_amount' => (float) $item->total_amount,
                        ]),
                    ];
                }
            }

            return response()->json([
                'status'           => true,
                'next_cn_number'   => $nextCnNumber,
                'reasons'          => CreditNote::REASONS,
                'invoices'         => $invoices,
                'selected_invoice' => $selectedInvoice,
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts salesCreditNoteMeta error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load credit note meta: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function storeSalesCreditNote(Request $request): JsonResponse
    {
        try {
            $companyId = $request->user()?->company_id;
            $userId    = $request->user()?->id;

            $request->validate([
                'invoice_id'       => 'required|integer|exists:invoices,id',
                'credit_note_date' => 'required|date',
                'reason'           => 'required|string|max:100',
                'total_amount'     => 'required|numeric|min:0.01',
                'notes'            => 'nullable|string',
                'attachment'       => 'nullable|file|max:10240',
                'items'            => 'nullable|array',
            ]);

            $invoice = Invoice::when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->findOrFail($request->input('invoice_id'));

            $creditAmount = round((float) $request->input('total_amount'), 2);

            // Calculate maximum allowable credit
            $existingCredits = (float) $invoice->creditNotes()->where('status', '!=', 'void')->sum('total_amount');
            $maxAllowable    = max(0, round((float) $invoice->total_amount - $existingCredits, 2));

            if ($creditAmount > ($maxAllowable + 0.05)) {
                return response()->json([
                    'status'  => false,
                    'message' => "Credit amount (₹" . number_format($creditAmount, 2) . ") exceeds maximum allowable creditable balance of ₹" . number_format($maxAllowable, 2) . " for Invoice #{$invoice->invoice_number}.",
                ], 422);
            }

            // Handle attachment
            $attachmentPath = null;
            $attachmentName = null;
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $filename = time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
                $destination = public_path('uploads/credit_notes');
                if (!file_exists($destination)) {
                    mkdir($destination, 0755, true);
                }
                $file->move($destination, $filename);
                $attachmentPath = 'uploads/credit_notes/' . $filename;
                $attachmentName = $file->getClientOriginalName();
            }

            DB::beginTransaction();

            $cnNumber = CreditNote::generateNextNumber($companyId);

            // Compute proportionate tax breakdown
            $invTotal = (float) $invoice->total_amount;
            $ratio    = $invTotal > 0 ? ($creditAmount / $invTotal) : 1;

            $subtotal   = round((float) $invoice->subtotal * $ratio, 2);
            $cgstAmount = round((float) $invoice->total_cgst * $ratio, 2);
            $sgstAmount = round((float) $invoice->total_sgst * $ratio, 2);
            $igstAmount = round((float) $invoice->total_igst * $ratio, 2);
            $totalTax   = $cgstAmount + $sgstAmount + $igstAmount;

            $creditNote = CreditNote::create([
                'company_id'         => $companyId,
                'invoice_id'         => $invoice->id,
                'customer_id'        => $invoice->customer_id,
                'credit_note_number' => $cnNumber,
                'credit_note_date'   => $request->input('credit_note_date'),
                'reason'             => $request->input('reason'),
                'subtotal'           => $subtotal,
                'cgst_amount'        => $cgstAmount,
                'sgst_amount'        => $sgstAmount,
                'igst_amount'        => $igstAmount,
                'total_tax'          => $totalTax,
                'total_amount'       => $creditAmount,
                'status'             => 'issued',
                'notes'              => $request->input('notes'),
                'attachment_path'    => $attachmentPath,
                'attachment_name'    => $attachmentName,
                'created_by'         => $userId,
            ]);

            // Save items if passed
            $items = $request->input('items', []);
            if (is_array($items) && !empty($items)) {
                foreach ($items as $it) {
                    if (empty($it['item_name']) && empty($it['amount'])) {
                        continue;
                    }
                    $invItem = !empty($it['item_id']) ? $invoice->items()->find($it['item_id']) : null;
                    CreditNoteItem::create([
                        'credit_note_id'  => $creditNote->id,
                        'invoice_item_id' => $invItem?->id,
                        'item_name'       => $it['item_name'] ?? ($invItem?->item_name ?: 'Credit Item'),
                        'description'     => $invItem?->description,
                        'hsn_sac_code'    => $invItem?->hsn_sac_code,
                        'qty'             => (float) ($it['qty'] ?? 1),
                        'rate'            => (float) ($it['rate'] ?? $it['amount'] ?? 0),
                        'amount'          => (float) ($it['amount'] ?? 0),
                        'cgst_percent'    => (float) ($invItem?->cgst_percent ?? 0),
                        'cgst_amount'     => (float) ($invItem?->cgst_amount ?? 0),
                        'sgst_percent'    => (float) ($invItem?->sgst_percent ?? 0),
                        'sgst_amount'     => (float) ($invItem?->sgst_amount ?? 0),
                        'igst_percent'    => (float) ($invItem?->igst_percent ?? 0),
                        'igst_amount'     => (float) ($invItem?->igst_amount ?? 0),
                        'total_amount'    => (float) ($it['amount'] ?? 0),
                    ]);
                }
            }

            // Sync invoice status and balance due
            $invoice->recalculatePaymentStatus();

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => "Credit Note #{$creditNote->credit_note_number} of ₹" . number_format($creditAmount, 2) . " applied successfully to Invoice #{$invoice->invoice_number}!",
                'data'    => $creditNote,
            ]);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Mobile Accounts storeSalesCreditNote error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Error creating Credit Note: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function showSalesCreditNote(int $id, Request $request): JsonResponse
    {
        try {
            $companyId = $request->user()?->company_id;

            $creditNote = CreditNote::with(['invoice.items', 'customer', 'creator', 'items'])
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->findOrFail($id);

            $setting = InvoiceSetting::forCompany($companyId);

            return response()->json([
                'status'  => true,
                'data'    => [
                    'credit_note' => [
                        'id'                 => $creditNote->id,
                        'credit_note_number' => $creditNote->credit_note_number,
                        'credit_note_date'   => $creditNote->credit_note_date?->format('Y-m-d'),
                        'invoice_id'         => $creditNote->invoice_id,
                        'customer_id'        => $creditNote->customer_id,
                        'reason'             => $creditNote->reason,
                        'reason_label'       => CreditNote::REASONS[$creditNote->reason] ?? $creditNote->reason,
                        'subtotal'           => (float) $creditNote->subtotal,
                        'cgst_amount'        => (float) $creditNote->cgst_amount,
                        'sgst_amount'        => (float) $creditNote->sgst_amount,
                        'igst_amount'        => (float) $creditNote->igst_amount,
                        'total_tax'          => (float) $creditNote->total_tax,
                        'total_amount'       => (float) $creditNote->total_amount,
                        'status'             => $creditNote->status,
                        'notes'              => $creditNote->notes,
                        'attachment_path'    => $creditNote->attachment_path,
                        'attachment_url'     => $creditNote->attachment_url,
                        'attachment_name'    => $creditNote->attachment_name,
                        'created_by'         => $creditNote->created_by,
                        'creator_name'       => $creditNote->creator?->name,
                        'created_at'         => $creditNote->created_at?->toIso8601String(),
                        'items'              => $creditNote->items->map(fn($it) => [
                            'id'           => $it->id,
                            'item_name'    => $it->item_name,
                            'description'  => $it->description,
                            'hsn_sac_code' => $it->hsn_sac_code,
                            'qty'          => (float) $it->qty,
                            'rate'         => (float) $it->rate,
                            'amount'       => (float) $it->amount,
                            'total_amount' => (float) $it->total_amount,
                        ]),
                    ],
                    'invoice' => $creditNote->invoice ? [
                        'id'              => $creditNote->invoice->id,
                        'invoice_number'  => $creditNote->invoice->invoice_number,
                        'invoice_date'    => $creditNote->invoice->invoice_date?->format('Y-m-d'),
                        'total_amount'    => (float) $creditNote->invoice->total_amount,
                        'balance_due'     => (float) $creditNote->invoice->balance_due,
                        'status'          => $creditNote->invoice->status,
                        'billing_address' => $creditNote->invoice->billing_address,
                        'billing_gstin'   => $creditNote->invoice->billing_gstin,
                    ] : null,
                    'customer' => [
                        'customer_name' => $creditNote->customer?->customer_name ?: ($creditNote->invoice?->customer_name ?: 'Valued Customer'),
                        'company_name'  => $creditNote->customer?->company_name ?: $creditNote->invoice?->company_name,
                        'address'       => $creditNote->invoice?->billing_address,
                        'gstin'         => $creditNote->invoice?->billing_gstin ?: $creditNote->customer?->gst_number,
                    ],
                    'company_setting' => [
                        'company_name'   => $setting?->company_name ?: 'Sai Techno Solutions Private Limited',
                        'full_address'   => $setting?->full_address ?: 'Plot No 8, Sai Nagar, Thoraipakkam, Chennai - 600097',
                        'company_gstin'  => $setting?->company_gstin ?: '33ABPCS7671C1Z3',
                        'logo_url'       => $setting?->logo_url,
                        'signature_url'  => $setting?->signature_url,
                        'signatory_name' => $setting?->signatory_name ?: ('FOR ' . strtoupper($setting?->company_name ?: 'Sai Techno Solutions Private Limited')),
                    ],
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts showSalesCreditNote error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Credit note not found: ' . $e->getMessage(),
            ], 404);
        }
    }

    public function toggleVoidSalesCreditNote(int $id, Request $request): JsonResponse
    {
        try {
            $companyId = $request->user()?->company_id;

            $creditNote = CreditNote::when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->findOrFail($id);

            $newStatus = $creditNote->status === 'void' ? 'issued' : 'void';
            $creditNote->update(['status' => $newStatus]);

            // Recalculate invoice
            $creditNote->invoice?->recalculatePaymentStatus();

            $action = $newStatus === 'void' ? 'voided (cancelled)' : 'restored to active';

            return response()->json([
                'status'       => true,
                'new_status'   => $newStatus,
                'message'      => "Credit Note #{$creditNote->credit_note_number} has been {$action}. Invoice balance has been updated.",
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts toggleVoidSalesCreditNote error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to update credit note status: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroySalesCreditNote(int $id, Request $request): JsonResponse
    {
        try {
            $companyId = $request->user()?->company_id;

            $creditNote = CreditNote::when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->findOrFail($id);

            $invoice = $creditNote->invoice;
            $cnNum   = $creditNote->credit_note_number;

            if ($creditNote->attachment_path && file_exists(public_path($creditNote->attachment_path))) {
                @unlink(public_path($creditNote->attachment_path));
            }

            $creditNote->delete();

            // Recalculate invoice
            $invoice?->recalculatePaymentStatus();

            return response()->json([
                'status'  => true,
                'message' => "Credit Note #{$cnNum} deleted successfully. Invoice balance has been adjusted.",
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts destroySalesCreditNote error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to delete credit note: ' . $e->getMessage(),
            ], 500);
        }
    }
}
