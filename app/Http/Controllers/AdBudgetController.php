<?php

namespace App\Http\Controllers;

use App\Models\AccountExpense;
use App\Models\AccountExpenseCategory;
use App\Models\AccountExpenseSubcategory;
use App\Models\AdAccountMaster;
use App\Models\AdBudgetRequest;
use App\Models\Branch;
use App\Models\LeadProductPayment;
use App\Models\User;
use App\Mail\AdBudgetNotificationMail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class AdBudgetController extends Controller
{
    /**
     * Display Ad Budget requests for clients.
     */
    public function clients(Request $request)
    {
        $cutoffInfo = $this->getCutoffInfo();
        $isRequestAllowed = $cutoffInfo['is_request_allowed'];
        $isApprovalAllowed = $cutoffInfo['is_approval_allowed'];
        $query = AdBudgetRequest::with(['adAccount', 'requester', 'tlApprover', 'approver'])
            ->where('type', 'client')
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($q2) use ($request) {
                    $q2->where('client_name', 'like', '%' . $request->search . '%')
                       ->orWhere('remarks', 'like', '%' . $request->search . '%')
                       ->orWhere('accounts_remarks', 'like', '%' . $request->search . '%')
                       ->orWhereHas('adAccount', function ($q3) use ($request) {
                           $q3->where('account_name', 'like', '%' . $request->search . '%')
                              ->orWhere('account_id', 'like', '%' . $request->search . '%');
                       });
                });
            })
            ->when($request->status, function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when($request->ad_account_id, function ($q) use ($request) {
                $q->where('ad_account_id', $request->ad_account_id);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $adAccounts = AdAccountMaster::active()->orderBy('account_name')->get();

        $totalRequested  = AdBudgetRequest::where('type', 'client')->sum('amount');
        $totalApproved   = AdBudgetRequest::where('type', 'client')->where('status', 'approved')->sum('approved_amount');
        $pendingTlCount  = AdBudgetRequest::where('type', 'client')->where('status', 'tl_pending')->count();
        $pendingAccCount = AdBudgetRequest::where('type', 'client')->where('status', 'accounts_pending')->count();

        $statusCounts = [
            'all'              => AdBudgetRequest::where('type', 'client')->count(),
            'tl_pending'       => AdBudgetRequest::where('type', 'client')->where('status', 'tl_pending')->count(),
            'accounts_pending' => AdBudgetRequest::where('type', 'client')->where('status', 'accounts_pending')->count(),
            'approved'         => AdBudgetRequest::where('type', 'client')->where('status', 'approved')->count(),
            'rejected'         => AdBudgetRequest::where('type', 'client')->where('status', 'rejected')->count(),
        ];

        return view('pages.accounts.ad_budget.clients', compact(
            'query',
            'adAccounts',
            'totalRequested',
            'totalApproved',
            'pendingTlCount',
            'pendingAccCount',
            'statusCounts',
            'isRequestAllowed',
            'isApprovalAllowed',
            'cutoffInfo'
        ));
    }

    /**
     * Store new Ad Budget request for clients.
     */
    public function storeClientRequest(Request $request)
    {
        if (! $this->isRequestAllowed()) {
            return redirect()->back()->withInput()->with('error', 'Daily Ad Budget Request creation is closed for today. Requests must be submitted before 11:30 AM IST.');
        }

        $validated = $request->validate([
            'ad_account_id'          => 'required|exists:ad_account_masters,id',
            'amount'                 => 'required|numeric|min:1',
            'selected_dates'         => 'required',
            'number_of_days'         => 'nullable|integer|min:1',
            'average_per_day_budget' => 'nullable|numeric|min:0',
            'client_name'            => 'nullable|string|max:255',
            'remarks'                => 'nullable|string',
        ]);

        $dates = [];
        if (is_array($request->selected_dates)) {
            $dates = array_values(array_filter($request->selected_dates));
        } else if (is_string($request->selected_dates)) {
            $dates = array_values(array_filter(array_map('trim', explode(',', $request->selected_dates))));
        }

        if (empty($dates)) {
            return redirect()->back()->withInput()->with('error', 'Please select at least one valid date.');
        }

        $numberOfDays = count($dates);
        $amount = (float) $validated['amount'];
        $avgPerDay = $numberOfDays > 0 ? round($amount / $numberOfDays, 2) : 0;

        $user = auth()->user();
        $initialStatus = ($user->hasAdminLikeRole() || $user->hasTlLikeRole()) ? 'accounts_pending' : 'tl_pending';

        $validated['selected_dates']         = $dates;
        $validated['number_of_days']         = $numberOfDays;
        $validated['average_per_day_budget'] = $avgPerDay;
        $validated['type']                   = 'client';
        $validated['requested_by']           = auth()->id();
        $validated['company_id']             = $user?->company_id;
        $validated['branch_id']              = $user?->branch_id;
        $validated['status']                 = $initialStatus;

        if ($initialStatus === 'accounts_pending') {
            $validated['tl_approved_by'] = auth()->id();
            $validated['tl_approved_at'] = now();
        }

        $item = AdBudgetRequest::create($validated);

        $this->sendNotificationMail($item, 'request_created');

        return redirect()->back()->with('success', 'Ad Budget Request raised successfully!');
    }

    /**
     * Display Partner Ad Budget payments recorded via Leads.
     */
    /**
     * Display Partner Ad Budget payments recorded via Leads.
     */
    public function partners(Request $request)
    {
        $user = auth()->user();
        $companyId = $user?->company_id;

        $isCompanyAdminOrCbo = $user && (
            $user->isSuperAdmin()
            || $user->isSystemAdmin()
            || $user->isCompanyAdminRole()
            || $user->isCbo()
        );

        $canRedirectLead = $isCompanyAdminOrCbo;

        $query = LeadProductPayment::with(['lead', 'product', 'branch', 'recordedBy'])
            ->whereIn('payment_type', ['ad_budget_partner', 'ad_budget_for_partner', 'Ad Budget for partner'])
            ->when($companyId, function ($q) use ($companyId) {
                $q->where(function ($q2) use ($companyId) {
                    $q2->whereHas('lead', fn($l) => $l->where('company_id', $companyId))
                       ->orWhereHas('branch', fn($b) => $b->where('company_id', $companyId));
                });
            });

        // Non-Company-Admin / Non-CBO users can ONLY see their own branch data
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

        // Summary stats before pagination
        $statsQuery = clone $query;
        $totalAmount  = (float) $statsQuery->sum('amount');
        $totalCount   = $statsQuery->count();

        $payments = $query->orderBy('payment_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Branches list for single-row filter dropdown
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
        $branches = $branchesQuery->orderBy('name')->get();

        return view('pages.accounts.ad_budget.partners', compact(
            'payments',
            'branches',
            'totalAmount',
            'totalCount',
            'canRedirectLead'
        ));
    }

    /**
     * Store new Ad Budget request for partners.
     */
    public function storePartnerRequest(Request $request)
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
        } else if (is_string($request->selected_dates)) {
            $dates = array_values(array_filter(array_map('trim', explode(',', $request->selected_dates))));
        }

        if (empty($dates)) {
            return redirect()->back()->withInput()->with('error', 'Please select at least one valid date.');
        }

        $user = auth()->user();
        $initialStatus = ($user->hasAdminLikeRole() || $user->hasTlLikeRole()) ? 'accounts_pending' : 'tl_pending';

        $validated['selected_dates'] = $dates;
        $validated['client_name']    = $request->partner_name ?? $request->client_name;
        $validated['type']           = 'partner';
        $validated['requested_by']   = auth()->id();
        $validated['company_id']     = $user?->company_id;
        $validated['branch_id']      = $user?->branch_id;
        $validated['status']         = $initialStatus;

        if ($initialStatus === 'accounts_pending') {
            $validated['tl_approved_by'] = auth()->id();
            $validated['tl_approved_at'] = now();
        }

        $item = AdBudgetRequest::create($validated);

        $this->sendNotificationMail($item, 'request_created');

        return redirect()->back()->with('success', 'Partner Ad Budget Request raised successfully!');
    }

    /**
     * Step 1: DM TL Approval
     */
    public function tlApprove(Request $request, AdBudgetRequest $adBudgetRequest)
    {
        if (! $this->isApprovalAllowed()) {
            return redirect()->back()->with('error', 'Ad Budget Approval window is closed for today. Approvals are allowed only until 12:30 PM IST.');
        }

        $action = $request->input('action', 'approve');

        $validated = $request->validate([
            'tl_remarks' => 'required|string|max:1000',
        ], [
            'tl_remarks.required' => 'Please enter remarks before submitting your approval/rejection.',
        ]);

        if ($action === 'reject') {
            $adBudgetRequest->update([
                'status'         => 'rejected',
                'tl_approved_by' => auth()->id(),
                'tl_approved_at' => now(),
                'tl_remarks'     => $validated['tl_remarks'],
            ]);

            $this->sendNotificationMail($adBudgetRequest, 'rejected');

            return redirect()->back()->with('success', 'Request rejected by DM TL.');
        }

        $adBudgetRequest->update([
            'status'         => 'accounts_pending',
            'tl_approved_by' => auth()->id(),
            'tl_approved_at' => now(),
            'tl_remarks'     => $validated['tl_remarks'],
        ]);

        $this->sendNotificationMail($adBudgetRequest, 'tl_approved');

        return redirect()->back()->with('success', 'Request approved by DM TL and sent to Accounts Team (hr@saitechnosolutions.net).');
    }

    /**
     * Step 2: Accounts Team Approval (hr@saitechnosolutions.net) with details & attachments
     */
    public function accountsApprove(Request $request, AdBudgetRequest $adBudgetRequest)
    {
        if (! $this->isApprovalAllowed()) {
            return redirect()->back()->with('error', 'Ad Budget Approval window is closed for today. Approvals are allowed only until 12:30 PM IST.');
        }

        $action = $request->input('action', 'approve');

        if ($action === 'reject') {
            $adBudgetRequest->update([
                'status'      => 'rejected',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            $this->removeOrCancelAccountExpense($adBudgetRequest);

            $this->sendNotificationMail($adBudgetRequest, 'rejected');

            return redirect()->back()->with('success', 'Request rejected by Accounts Team.');
        }

        $validated = $request->validate([
            'payment_date'    => 'required|date',
            'ad_account_id'   => 'required|exists:ad_account_masters,id',
            'approved_amount' => 'required|numeric|min:0.01',
            'accounts_remarks' => 'nullable|string',
            'attachments.*'   => 'nullable|file|mimes:jpeg,png,jpg,pdf,doc,docx,xlsx,xls|max:10240',
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
            'approved_by'      => auth()->id(),
            'approved_at'      => now(),
            'payment_date'     => $validated['payment_date'],
            'ad_account_id'    => $validated['ad_account_id'],
            'approved_amount'  => $validated['approved_amount'],
            'accounts_remarks' => $validated['accounts_remarks'] ?? null,
            'attachments'      => $uploadedPaths,
        ]);

        // Automatically record this approved amount into Accounts Expense
        $this->syncToAccountExpense($adBudgetRequest, auth()->user());

        $this->sendNotificationMail($adBudgetRequest, 'accounts_approved');

        return redirect()->back()->with('success', 'Ad Budget Request approved and recorded in Accounts Expense successfully!');
    }

    /**
     * Automatically synchronize an approved Ad Budget Request into the Accounts Module (AccountExpense).
     */
    private function syncToAccountExpense(AdBudgetRequest $adBudgetRequest, ?User $user = null): ?AccountExpense
    {
        try {
            $user = $user ?: auth()->user();
            $companyId = $adBudgetRequest->company_id ?: ($adBudgetRequest->requester?->company_id ?: $user?->company_id);

            // 1. Resolve or Create Category: "Ad Budget"
            $categoryName = 'Ad Budget';
            $accCategory = AccountExpenseCategory::withoutGlobalScopes()
                ->where(function ($q) use ($companyId) {
                    if ($companyId) {
                        $q->where('company_id', $companyId)->orWhereNull('company_id');
                    }
                })
                ->where(function ($q) {
                    $q->whereRaw('LOWER(TRIM(name)) = ?', ['ad budget'])
                      ->orWhereRaw('LOWER(TRIM(name)) = ?', ['advertising & marketing'])
                      ->orWhereRaw('LOWER(TRIM(name)) = ?', ['advertising']);
                })
                ->first();

            if (! $accCategory) {
                $accCategory = AccountExpenseCategory::create([
                    'company_id'  => $companyId,
                    'name'        => $categoryName,
                    'code'        => 'ADBD',
                    'status'      => 'active',
                    'description' => 'Auto-created category for Ad Budget requests',
                    'created_by'  => $user?->id,
                ]);
            }

            // 2. Resolve or Create Subcategory: e.g. "Meta Ads", "Google Ads", or Account Name
            $adAccount = $adBudgetRequest->adAccount;
            $subcategoryName = 'Digital Ads';
            if ($adAccount) {
                if (!empty($adAccount->platform)) {
                    $subcategoryName = trim($adAccount->platform);
                    if (!str_ends_with(strtolower($subcategoryName), 'ads')) {
                        $subcategoryName .= ' Ads';
                    }
                } elseif (!empty($adAccount->account_name)) {
                    $subcategoryName = trim($adAccount->account_name);
                }
            } elseif (!empty($adBudgetRequest->type)) {
                $subcategoryName = ucfirst($adBudgetRequest->type) . ' Ad Spend';
            }

            $accSubcategory = AccountExpenseSubcategory::withoutGlobalScopes()
                ->where('account_expense_category_id', $accCategory->id)
                ->where(function ($q) use ($companyId) {
                    if ($companyId) {
                        $q->where('company_id', $companyId)->orWhereNull('company_id');
                    }
                })
                ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($subcategoryName)])
                ->first();

            if (! $accSubcategory) {
                $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $subcategoryName), 0, 4)) ?: 'ADSB';
                $accSubcategory = AccountExpenseSubcategory::create([
                    'company_id'                  => $companyId,
                    'account_expense_category_id' => $accCategory->id,
                    'name'                        => $subcategoryName,
                    'code'                        => $code,
                    'status'                      => 'active',
                    'description'                 => 'Auto-created subcategory for Ad Budget platform: ' . $subcategoryName,
                    'created_by'                  => $user?->id,
                ]);
            }

            // 3. Format Attachments
            $accAttachments = [];
            if (!empty($adBudgetRequest->attachments) && is_array($adBudgetRequest->attachments)) {
                foreach ($adBudgetRequest->attachments as $att) {
                    $filePath = is_array($att) ? ($att['path'] ?? '') : (string) $att;
                    if (empty($filePath)) {
                        continue;
                    }

                    $cleanPath = ltrim($filePath, '/');
                    if (!str_starts_with($cleanPath, 'storage/') && !str_starts_with($cleanPath, 'uploads/')) {
                        $cleanPath = 'storage/' . $cleanPath;
                    }

                    $fullPath = public_path($cleanPath);
                    $exists = file_exists($fullPath);
                    $fileName = is_array($att) ? ($att['name'] ?? basename($cleanPath)) : basename($cleanPath);
                    $fileSize = is_array($att) && isset($att['size']) ? $att['size'] : ($exists ? @filesize($fullPath) : null);
                    $mime = $exists ? @mime_content_type($fullPath) : null;

                    $accAttachments[] = [
                        'path'      => $cleanPath,
                        'name'      => $fileName,
                        'size'      => $fileSize,
                        'mime_type' => $mime,
                    ];
                }
            }

            // 4. Build Rich Remarks
            $effectiveAmount = (float) $adBudgetRequest->approved_amount;
            $effectiveDate = $adBudgetRequest->payment_date
                ? Carbon::parse($adBudgetRequest->payment_date)->toDateString()
                : now()->toDateString();

            $remarksLines = [];
            $remarksLines[] = "[Ad Budget Request #{$adBudgetRequest->id}]";
            $remarksLines[] = "Requester: " . ($adBudgetRequest->requester?->name ?? 'User') . ($adBudgetRequest->requester?->email ? " ({$adBudgetRequest->requester->email})" : '');
            $remarksLines[] = "Type: " . ucfirst($adBudgetRequest->type ?? 'client');
            if (!empty($adBudgetRequest->client_name)) {
                $remarksLines[] = "Client: " . $adBudgetRequest->client_name;
            }
            if ($adAccount) {
                $accountInfo = $adAccount->account_name;
                if ($adAccount->account_id) {
                    $accountInfo .= " ({$adAccount->account_id})";
                }
                if ($adAccount->platform) {
                    $accountInfo .= " - " . $adAccount->platform;
                }
                $remarksLines[] = "Ad Account: " . $accountInfo;
            }
            if (!empty($adBudgetRequest->number_of_days)) {
                $remarksLines[] = "Duration: " . $adBudgetRequest->number_of_days . " day(s)";
            }
            if (!empty($adBudgetRequest->remarks)) {
                $remarksLines[] = "Request Notes: " . trim($adBudgetRequest->remarks);
            }
            if (!empty($adBudgetRequest->tl_remarks)) {
                $remarksLines[] = "TL Remarks: " . trim($adBudgetRequest->tl_remarks);
            }
            if (!empty($adBudgetRequest->accounts_remarks)) {
                $remarksLines[] = "Accounts Remarks: " . trim($adBudgetRequest->accounts_remarks);
            }
            $remarksLines[] = "Approved By: " . ($user?->name ?? 'Accounts Team') . " on " . now()->format('d M Y, h:i A');
            $remarksLines[] = "Approved Amount: ₹" . number_format($effectiveAmount, 2);

            $finalRemarks = implode("\n", $remarksLines);
            $invoiceRef = "AD-BUDGET-{$adBudgetRequest->id}";

            // 5. Check existing AccountExpense to update or create
            $accountExpense = AccountExpense::withoutGlobalScopes()
                ->where(function ($q) use ($invoiceRef, $adBudgetRequest) {
                    $q->where('invoice_no', $invoiceRef)
                      ->orWhere('remarks', 'like', "%[Ad Budget Request #{$adBudgetRequest->id}]%");
                })
                ->first();

            if ($accountExpense) {
                $accountExpense->update([
                    'company_id'                     => $companyId,
                    'expense_date'                   => $effectiveDate,
                    'account_expense_category_id'    => $accCategory->id,
                    'account_expense_subcategory_id' => $accSubcategory?->id,
                    'amount'                         => $effectiveAmount,
                    'expense_type'                   => 'Variable',
                    'remarks'                        => $finalRemarks,
                    'attachments'                    => !empty($accAttachments) ? $accAttachments : $accountExpense->attachments,
                    'updated_by'                     => $user?->id,
                ]);
            } else {
                $accountExpense = AccountExpense::create([
                    'company_id'                     => $companyId,
                    'expense_date'                   => $effectiveDate,
                    'account_expense_category_id'    => $accCategory->id,
                    'account_expense_subcategory_id' => $accSubcategory?->id,
                    'amount'                         => $effectiveAmount,
                    'expense_type'                   => 'Variable',
                    'is_recurring'                   => false,
                    'invoice_no'                     => $invoiceRef,
                    'remarks'                        => $finalRemarks,
                    'attachments'                    => $accAttachments,
                    'created_by'                     => $user?->id,
                    'updated_by'                     => $user?->id,
                ]);
            }

            return $accountExpense;
        } catch (\Throwable $e) {
            Log::error("Failed to sync AdBudgetRequest #{$adBudgetRequest->id} to AccountExpense: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Remove or cancel synchronized AccountExpense if request is rejected or cancelled.
     */
    private function removeOrCancelAccountExpense(AdBudgetRequest $adBudgetRequest): void
    {
        try {
            $invoiceRef = "AD-BUDGET-{$adBudgetRequest->id}";
            $accountExpense = AccountExpense::withoutGlobalScopes()
                ->where(function ($q) use ($invoiceRef, $adBudgetRequest) {
                    $q->where('invoice_no', $invoiceRef)
                      ->orWhere('remarks', 'like', "%[Ad Budget Request #{$adBudgetRequest->id}]%");
                })
                ->first();

            if ($accountExpense) {
                $accountExpense->delete();
            }
        } catch (\Throwable $e) {
            Log::error("Failed to remove AccountExpense for AdBudgetRequest #{$adBudgetRequest->id}: " . $e->getMessage());
        }
    }

    /**
     * Helper to dispatch email notifications
     */
    private function sendNotificationMail(AdBudgetRequest $requestItem, string $event)
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
        } catch (\Exception $e) {
            Log::error('Ad Budget Mail Dispatch Error: ' . $e->getMessage());
        }
    }

    /**
     * Check if client ad budget request creation is allowed today.
     * Allowed only until 11:30 AM daily (Asia/Kolkata timezone).
     */
    protected function isRequestAllowed(): bool
    {
        $now = Carbon::now('Asia/Kolkata');
        $cutoff = $now->copy()->setTime(11, 30, 0);
        return $now->lessThan($cutoff);
    }

    /**
     * Check if ad budget approval (TL / Accounts) is allowed today.
     * Allowed only until 12:30 PM daily (Asia/Kolkata timezone).
     */
    protected function isApprovalAllowed(): bool
    {
        $now = Carbon::now('Asia/Kolkata');
        $cutoff = $now->copy()->setTime(12, 30, 0);
        return $now->lessThan($cutoff);
    }

    /**
     * Get detailed cutoff and countdown information for ad budget client requests & approvals.
     */
    protected function getCutoffInfo(): array
    {
        $now = Carbon::now('Asia/Kolkata');

        // Request cutoff: 11:30 AM
        $requestCutoff = $now->copy()->setTime(11, 30, 0);
        $isRequestAllowed = $now->lessThan($requestCutoff);
        $reqSec = $isRequestAllowed ? $now->diffInSeconds($requestCutoff, false) : 0;
        $reqMin = $isRequestAllowed ? (int) ceil($reqSec / 60) : 0;

        // Approval cutoff: 12:30 PM
        $approvalCutoff = $now->copy()->setTime(12, 30, 0);
        $isApprovalAllowed = $now->lessThan($approvalCutoff);
        $appSec = $isApprovalAllowed ? $now->diffInSeconds($approvalCutoff, false) : 0;
        $appMin = $isApprovalAllowed ? (int) ceil($appSec / 60) : 0;

        return [
            'is_request_allowed'   => $isRequestAllowed,
            'request_cutoff_time'  => '11:30 AM',
            'request_seconds'      => max(0, $reqSec),
            'request_minutes'      => max(0, $reqMin),
            'formatted_request_remaining' => $reqMin > 60
                ? floor($reqMin / 60) . 'h ' . ($reqMin % 60) . 'm'
                : $reqMin . 'm',

            'is_approval_allowed'  => $isApprovalAllowed,
            'approval_cutoff_time' => '12:30 PM',
            'approval_seconds'     => max(0, $appSec),
            'approval_minutes'     => max(0, $appMin),
            'formatted_approval_remaining' => $appMin > 60
                ? floor($appMin / 60) . 'h ' . ($appMin % 60) . 'm'
                : $appMin . 'm',

            'current_time'         => $now->format('h:i A'),
        ];
    }
}
