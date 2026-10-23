<?php

namespace App\Http\Controllers;

use App\Models\AccountExpense;
use App\Models\AccountExpenseCategory;
use App\Models\AccountExpenseSubcategory;
use App\Models\ExpenseCategory;
use App\Models\ExpensePipeline;
use App\Models\ExpenseRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ExpenseRequestController extends Controller
{
    /**
     * Display listing of expense requests.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $companyId = $user?->company_id;

        $userRoleIds = \DB::table('model_has_roles')
            ->where('model_type', User::class)
            ->where('model_id', $user->id)
            ->pluck('role_id')
            ->toArray();

        if (empty($userRoleIds) && $user->relationLoaded('roles')) {
            $userRoleIds = $user->roles->pluck('id')->toArray();
        }

        $isHrOrAdmin = $user->isHrOrAdmin();

        $categories = ExpenseCategory::query()
            ->when($companyId, fn($q) => $q->where(fn($q2) => $q2->where('company_id', $companyId)->orWhereNull('company_id')))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Base query scoped to company and user role/permissions
        $scopedQuery = ExpenseRequest::with(['user.roles', 'category', 'approver', 'currentApproverRole'])
            ->when($companyId, fn($q) => $q->where('company_id', $companyId));

        // Company Admin & HR see all requests; Regular users see own submitted requests + requests where they are stage approver
        if (! $isHrOrAdmin) {
            $userRoleNames = Role::withoutGlobalScopes()->whereIn('id', $userRoleIds)->pluck('name')->map(fn($n) => preg_replace('/^company_\d+__/', '', $n))->toArray();
            $matchingRoleIds = Role::withoutGlobalScopes()->where(function($q) use ($userRoleIds, $userRoleNames) {
                $q->whereIn('id', $userRoleIds);
                foreach ($userRoleNames as $rn) {
                    $q->orWhere('name', 'like', "%{$rn}%");
                }
            })->pluck('id')->toArray();

            $scopedQuery->where(function ($q) use ($user, $matchingRoleIds) {
                $q->where('user_id', $user->id)
                  ->orWhere('approver_id', $user->id)
                  ->orWhere(function ($q2) use ($matchingRoleIds) {
                      $q2->whereIn('current_approver_role_id', $matchingRoleIds)
                         ->where('status', 'pending');
                  });
            });
        }

        // Calculate card counts based on scoped query
        $totalCount    = (clone $scopedQuery)->count();
        $pendingCount  = (clone $scopedQuery)->where('status', 'pending')->count();
        $approvedCount = (clone $scopedQuery)->where('status', 'approved')->count();
        $rejectedCount = (clone $scopedQuery)->where('status', 'rejected')->count();

        // Fetch Accounts Expense Categories for final approval mapping
        $accountCategories = AccountExpenseCategory::withoutGlobalScopes()
            ->when($user->company_id, function ($q) use ($user) {
                $q->where(fn($sq) => $sq->where('company_id', $user->company_id)->orWhereNull('company_id'));
            })
            ->active()
            ->with(['activeSubcategories' => function ($q) {
                $q->orderBy('name');
            }])
            ->orderBy('name')
            ->get();

        // Apply search and status filters for main paginated table
        $requests = $scopedQuery
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('expense_category_id'), fn($q) => $q->where('expense_category_id', $request->expense_category_id))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->search);
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', '%' . $search . '%')
                      ->orWhere('amount', 'like', '%' . $search . '%')
                      ->orWhereHas('user', fn($u) => $u->where('name', 'like', '%' . $search . '%'))
                      ->orWhereHas('category', fn($c) => $c->where('name', 'like', '%' . $search . '%'));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages.hrms.expense-requests.index', compact(
            'requests',
            'categories',
            'accountCategories',
            'userRoleIds',
            'totalCount',
            'pendingCount',
            'approvedCount',
            'rejectedCount',
            'isHrOrAdmin'
        ));
    }

    /**
     * Store a newly submitted expense request.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $companyId = $user?->company_id;

        $validated = $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'amount'              => 'required|numeric|min:0.01',
            'description'         => 'required|string|max:2000',
            'attachment'          => 'nullable',
            'attachments'         => 'nullable|array',
            'attachments.*'       => 'file|mimes:pdf,jpg,jpeg,png,webp,doc,docx|max:5120',
        ]);

        $uploadedPaths = [];
        $targetDir = public_path('uploads/expense-requests');
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $allFiles = [];
        if ($request->hasFile('attachments')) {
            $f = $request->file('attachments');
            $allFiles = is_array($f) ? $f : [$f];
        } elseif ($request->hasFile('attachment')) {
            $f = $request->file('attachment');
            $allFiles = is_array($f) ? $f : [$f];
        }

        foreach ($allFiles as $file) {
            if ($file && $file->isValid()) {
                $fileName = time() . '_' . \Illuminate\Support\Str::random(10) . '.' . $file->getClientOriginalExtension();
                $file->move($targetDir, $fileName);
                $uploadedPaths[] = 'uploads/expense-requests/' . $fileName;
            }
        }

        $attachmentPath = !empty($uploadedPaths)
            ? (count($uploadedPaths) === 1 ? $uploadedPaths[0] : json_encode($uploadedPaths))
            : null;

        // Determine approval pipeline for applicant user's role hierarchy
        $userRoleIds = \DB::table('model_has_roles')
            ->where('model_type', User::class)
            ->where('model_id', $user->id)
            ->pluck('role_id')
            ->toArray();

        if (empty($userRoleIds)) {
            $userRoleIds = $user->roles->pluck('id')->toArray();
        }

        $pipeline = null;
        if (!empty($userRoleIds)) {
            $pipeline = ExpensePipeline::withoutGlobalScopes()
                ->whereIn('role_id', $userRoleIds)
                ->when($companyId, fn($q) => $q->where(fn($q2) => $q2->where('company_id', $companyId)->orWhereNull('company_id')))
                ->where('is_active', true)
                ->first();
        }

        // Fallback matching by role base name
        if (! $pipeline && !empty($userRoleIds)) {
            $userRoles = Role::withoutGlobalScopes()->whereIn('id', $userRoleIds)->get();
            foreach ($userRoles as $uRole) {
                $baseName = strtolower(preg_replace('/^company_\d+__/', '', $uRole->name));
                $matchingRoleIds = Role::withoutGlobalScopes()
                    ->where('name', 'like', "%{$baseName}%")
                    ->pluck('id')
                    ->toArray();

                $pipeline = ExpensePipeline::withoutGlobalScopes()
                    ->whereIn('role_id', $matchingRoleIds)
                    ->when($companyId, fn($q) => $q->where(fn($q2) => $q2->where('company_id', $companyId)->orWhereNull('company_id')))
                    ->where('is_active', true)
                    ->first();

                if ($pipeline) {
                    break;
                }
            }
        }

        // Fallback default: if developer role and no pipeline, default to Dev Project Coordinator (8) -> HR (36)
        $approvalChain = $pipeline->approval_chain ?? [];
        if (empty($approvalChain)) {
            $devCoordinatorRole = Role::withoutGlobalScopes()->where('name', 'like', '%development_project_coordinator%')->first();
            $hrRole = Role::withoutGlobalScopes()->where('name', 'like', '%human_resource%')->orWhere('name', 'like', '%hr%')->first();
            if ($devCoordinatorRole && $hrRole) {
                $approvalChain = [$devCoordinatorRole->id, $hrRole->id];
            }
        }

        $currentStep = 1;
        $currentApproverRoleId = !empty($approvalChain) ? (int) $approvalChain[0] : null;

        $expenseRequest = ExpenseRequest::create([
            'company_id'               => $companyId,
            'user_id'                  => $user->id,
            'expense_category_id'      => $validated['expense_category_id'],
            'amount'                   => $validated['amount'],
            'description'              => $validated['description'],
            'attachment'               => $attachmentPath,
            'current_step'             => $currentStep,
            'current_approver_role_id' => $currentApproverRoleId,
            'status'                   => 'pending',
            'stage_history'            => [],
        ]);

        // Send Email Notification to the Stage 1 Approver(s)
        $this->sendApproverNotification($expenseRequest);

        $stageName = $expenseRequest->currentApproverRole?->display_name ?: ($expenseRequest->currentApproverRole ? ucfirst(str_replace('_', ' ', preg_replace('/^company_\d+__/', '', $expenseRequest->currentApproverRole->name))) : 'Approver');

        return redirect()
            ->route('hrms.expense-requests.index')
            ->with('success', "Expense request submitted successfully. Stage 1 approval notification sent to {$stageName}.");
    }

    /**
     * Direct email approve action link.
     */
    public function emailApprove(Request $request, ExpenseRequest $expenseRequest): RedirectResponse
    {
        $user = auth()->user();
        if (! $expenseRequest->canUserAction($user)) {
            return redirect()
                ->route('hrms.expense-requests.index')
                ->with('error', 'You are not authorized to approve this expense request at its current stage.');
        }

        if ($expenseRequest->status !== 'pending') {
            return redirect()
                ->route('hrms.expense-requests.index')
                ->with('error', "This expense request is already {$expenseRequest->status}.");
        }

        if (! $request->filled('remarks')) {
            $request->merge(['remarks' => 'Approved via email notification link']);
        }
        if (! $request->filled('paid_amount')) {
            $request->merge(['paid_amount' => $expenseRequest->amount]);
        }
        if (! $request->filled('paid_date')) {
            $request->merge(['paid_date' => now()->toDateString()]);
        }

        return $this->approve($request, $expenseRequest);
    }

    /**
     * Direct email reject action page or 1-click preset reason rejection.
     */
    public function emailRejectPage(Request $request, ExpenseRequest $expenseRequest)
    {
        $user = auth()->user();
        if (! $expenseRequest->canUserAction($user)) {
            return redirect()
                ->route('hrms.expense-requests.index')
                ->with('error', 'You are not authorized to reject this expense request at its current stage.');
        }

        if ($expenseRequest->status !== 'pending') {
            return redirect()
                ->route('hrms.expense-requests.index')
                ->with('error', "This expense request is already {$expenseRequest->status}.");
        }

        // Handle 1-click preset reason or inline email form GET submission
        if ($request->has('reason') && !empty(trim($request->query('reason')))) {
            $reason = trim($request->query('reason'));

            $history = $expenseRequest->stage_history ?? [];
            $currentStep = $expenseRequest->current_step;
            $currentRole = Role::withoutGlobalScopes()->find($expenseRequest->current_approver_role_id);
            $currentRoleName = $currentRole?->display_name ?: ($currentRole ? ucfirst(str_replace('_', ' ', preg_replace('/^company_\d+__/', '', $currentRole->name))) : "Stage {$currentStep}");

            $history[] = [
                'step'        => $currentStep,
                'role_id'     => (int) $expenseRequest->current_approver_role_id,
                'role_name'   => $currentRoleName,
                'action'      => 'rejected',
                'user_id'     => $user->id,
                'user_name'   => $user->name,
                'actioned_at' => now()->toDateTimeString(),
                'remarks'     => $reason,
            ];

            $expenseRequest->update([
                'status'           => 'rejected',
                'approver_id'      => $user->id,
                'rejection_reason' => $reason,
                'stage_history'    => $history,
                'actioned_at'      => now(),
            ]);

            // Notify applicant of rejection
            $this->sendApplicantStatusNotification($expenseRequest, 'rejected', $reason);

            return redirect()
                ->route('hrms.expense-requests.index')
                ->with('success', "Expense request rejected with reason: '{$reason}'.");
        }

        return view('pages.hrms.expense-requests.reject_page', compact('expenseRequest'));
    }

    /**
     * Approve an expense request through the pipeline hierarchy.
     */
    public function approve(Request $request, ExpenseRequest $expenseRequest): RedirectResponse
    {
        $user = auth()->user();
        if (! $expenseRequest->canUserAction($user)) {
            return redirect()
                ->route('hrms.expense-requests.index')
                ->with('error', 'You are not authorized to approve this expense request at its current stage.');
        }

        $applicantUser = $expenseRequest->user;
        $applicantCompanyId = $applicantUser?->company_id ?: $expenseRequest->company_id;

        $pipeline = $expenseRequest->pipeline;
        $approvalChain = $pipeline->approval_chain ?? [];

        if (empty($approvalChain)) {
            $devCoordinatorRole = Role::withoutGlobalScopes()->where('name', 'like', '%development_project_coordinator%')->first();
            $hrRole = Role::withoutGlobalScopes()->where('name', 'like', '%human_resource%')->orWhere('name', 'like', '%hr%')->first();
            if ($devCoordinatorRole && $hrRole) {
                $approvalChain = [$devCoordinatorRole->id, $hrRole->id];
            }
        }

        $currentStep = $expenseRequest->current_step;
        $nextStepIndex = $currentStep; // 1-indexed currentStep matches next 0-indexed element in chain
        $isFinalStage = ! isset($approvalChain[$nextStepIndex]);

        $rules = [
            'remarks' => 'required|string|max:1000',
        ];
        $messages = [
            'remarks.required' => 'Approval remarks are mandatory.',
        ];

        if ($isFinalStage) {
            $rules['paid_amount'] = 'required|numeric|min:0.01';
            $rules['paid_date']   = 'required|date';
            $rules['account_expense_category_id'] = 'nullable|integer|exists:account_expense_categories,id';
            $rules['account_expense_subcategory_id'] = 'nullable|integer|exists:account_expense_subcategories,id';
            $rules['payment_receipt'] = 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:25600';
            $messages['paid_amount.required'] = 'Payment Amount is mandatory for final approval.';
            $messages['paid_amount.numeric']  = 'Payment Amount must be a valid number.';
            $messages['paid_amount.min']      = 'Payment Amount must be greater than zero.';
            $messages['paid_date.required']   = 'Payment Date is mandatory for final approval.';
            $messages['paid_date.date']       = 'Payment Date must be a valid date.';
        }

        $validated = $request->validate($rules, $messages);

        $remarks = trim($validated['remarks']);
        $paidAmount = $isFinalStage ? $validated['paid_amount'] : null;
        $paidDate = $isFinalStage ? $validated['paid_date'] : null;

        // Handle optional payment voucher / receipt file upload on final approval
        $receiptPath = null;
        if ($isFinalStage && $request->hasFile('payment_receipt')) {
            $file = $request->file('payment_receipt');
            $filename = 'pay_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $targetDir = public_path('uploads/expense_receipts');
            if (!file_exists($targetDir)) {
                @mkdir($targetDir, 0755, true);
            }
            $file->move($targetDir, $filename);
            $receiptPath = 'uploads/expense_receipts/' . $filename;
        }

        $currentRoleId = $expenseRequest->current_approver_role_id ?: ($approvalChain[$currentStep - 1] ?? null);
        $currentRole = Role::withoutGlobalScopes()->find($currentRoleId);
        $currentRoleName = $currentRole?->display_name ?: ($currentRole ? ucfirst(str_replace('_', ' ', preg_replace('/^company_\d+__/', '', $currentRole->name))) : "Stage {$currentStep}");

        // Record stage approval in history
        $history = $expenseRequest->stage_history ?? [];
        $historyEntry = [
            'step'        => $currentStep,
            'role_id'     => (int) $currentRoleId,
            'role_name'   => $currentRoleName,
            'action'      => 'approved',
            'user_id'     => $user->id,
            'user_name'   => $user->name,
            'actioned_at' => now()->toDateTimeString(),
            'remarks'     => $remarks,
        ];

        if ($isFinalStage) {
            $historyEntry['paid_amount'] = $paidAmount;
            $historyEntry['paid_date']   = $paidDate;
            if ($receiptPath) {
                $historyEntry['payment_receipt'] = $receiptPath;
            }
        }

        $history[] = $historyEntry;

        if (isset($approvalChain[$nextStepIndex])) {
            $nextRoleId = (int) $approvalChain[$nextStepIndex];
            $nextRole = Role::withoutGlobalScopes()->find($nextRoleId);
            $nextRoleName = $nextRole?->display_name ?: ($nextRole ? ucfirst(str_replace('_', ' ', preg_replace('/^company_\d+__/', '', $nextRole->name))) : "Stage " . ($currentStep + 1));

            // Advance to next approval stage in the pipeline hierarchy
            $expenseRequest->update([
                'current_step'             => $currentStep + 1,
                'current_approver_role_id' => $nextRoleId,
                'stage_history'            => $history,
            ]);

            // Notify next stage approvers via email
            $this->sendApproverNotification($expenseRequest);

            $msg = "Stage {$currentStep} approved by {$user->name}. Request has moved to Stage " . ($currentStep + 1) . " ({$nextRoleName}).";
        } else {
            // Final Stage Approval reached (last level role in pipeline chain)
            $expenseRequest->update([
                'status'        => 'approved',
                'approver_id'   => $user->id,
                'paid_amount'   => $paidAmount,
                'paid_date'     => $paidDate,
                'stage_history' => $history,
                'actioned_at'   => now(),
            ]);

            // Sync to Accounts Module Expense (Variable Expense with auto/selected category & subcategory)
            $this->syncToAccountExpense(
                $expenseRequest,
                $user,
                $remarks,
                $paidAmount,
                $paidDate,
                $request->filled('account_expense_category_id') ? (int) $request->account_expense_category_id : null,
                $request->filled('account_expense_subcategory_id') ? (int) $request->account_expense_subcategory_id : null,
                $receiptPath
            );

            // Notify applicant of final approval
            $this->sendApplicantStatusNotification($expenseRequest, 'approved', $remarks);

            $msg = "Expense request fully approved, payment details saved, and recorded under Accounts Expenses (Variable)!";
        }

        return redirect()
            ->route('hrms.expense-requests.index')
            ->with('success', $msg);
    }

    /**
     * Reject an expense request.
     */
    public function reject(Request $request, ExpenseRequest $expenseRequest): RedirectResponse
    {
        $user = auth()->user();
        if (! $expenseRequest->canUserAction($user)) {
            return redirect()
                ->route('hrms.expense-requests.index')
                ->with('error', 'You are not authorized to reject this expense request at its current stage.');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $reason = $validated['rejection_reason'];

        $history = $expenseRequest->stage_history ?? [];
        $currentStep = $expenseRequest->current_step;
        $currentRole = Role::withoutGlobalScopes()->find($expenseRequest->current_approver_role_id);
        $currentRoleName = $currentRole?->display_name ?: ($currentRole ? ucfirst(str_replace('_', ' ', preg_replace('/^company_\d+__/', '', $currentRole->name))) : "Stage {$currentStep}");

        $history[] = [
            'step'        => $currentStep,
            'role_id'     => (int) $expenseRequest->current_approver_role_id,
            'role_name'   => $currentRoleName,
            'action'      => 'rejected',
            'user_id'     => $user->id,
            'user_name'   => $user->name,
            'actioned_at' => now()->toDateTimeString(),
            'remarks'     => $reason,
        ];

        $expenseRequest->update([
            'status'           => 'rejected',
            'approver_id'      => $user->id,
            'rejection_reason' => $reason,
            'stage_history'    => $history,
            'actioned_at'      => now(),
        ]);

        // Notify applicant of rejection
        $this->sendApplicantStatusNotification($expenseRequest, 'rejected', $reason);

        return redirect()
            ->route('hrms.expense-requests.index')
            ->with('success', 'Expense request rejected successfully with remarks saved.');
    }

    /**
     * Send email notification to the active stage approver(s).
     */
    private function sendApproverNotification(ExpenseRequest $expenseRequest): void
    {
        if (! $expenseRequest->current_approver_role_id) {
            return;
        }

        $applicantUser = $expenseRequest->user;
        if (! $applicantUser) {
            return;
        }

        $approverRoleId = $expenseRequest->current_approver_role_id;
        $applicantBranchId = $applicantUser->branch_id;
        $applicantDepartmentId = $applicantUser->department_id;
        $companyId = $expenseRequest->company_id;

        // Query users matching the current approver role
        $query = User::whereHas('roles', function ($q) use ($approverRoleId) {
            $q->where('id', $approverRoleId);
        })->when($companyId, fn($q) => $q->where('company_id', $companyId));

        // Priority 1: Same branch as applicant
        $branchApprovers = clone $query;
        if ($applicantBranchId) {
            $branchApprovers->where(function ($q) use ($applicantBranchId) {
                $q->where('branch_id', $applicantBranchId)
                  ->orWhereHas('branches', fn($b) => $b->where('branches.id', $applicantBranchId));
            });
        }

        $approverUsers = $branchApprovers->get();

        // Priority 2: Same department if branch yields empty
        if ($approverUsers->isEmpty() && $applicantDepartmentId) {
            $deptApprovers = clone $query;
            $deptApprovers->where('department_id', $applicantDepartmentId);
            $approverUsers = $deptApprovers->get();
        }

        // Priority 3: Fallback to all users with that approver role
        if ($approverUsers->isEmpty()) {
            $approverUsers = $query->get();
        }

        $emails = $approverUsers->pluck('email')->filter()->toArray();

        if (empty($emails)) {
            return;
        }

        $categoryName = $expenseRequest->category?->name ?? 'General Expense';
        $applicantName = $applicantUser->name;
        $applicantRole = $applicantUser->roles->first()?->display_name ?? ucfirst(str_replace('_', ' ', $applicantUser->roles->first()?->name ?? 'Employee'));
        $applicantBranch = $applicantUser->branch?->name ?? 'Main Branch';
        $amount = $expenseRequest->amount;
        $description = $expenseRequest->description;
        $actionUrl = route('hrms.expense-requests.index');
        $approveUrl = route('hrms.expense-requests.email-approve', $expenseRequest);
        $rejectUrl  = route('hrms.expense-requests.email-reject', $expenseRequest);
        $stepNumber = $expenseRequest->current_step ?? 1;

        try {
            Mail::send('emails.expense_request_notification', [
                'applicantName'   => $applicantName,
                'applicantRole'   => $applicantRole,
                'applicantBranch' => $applicantBranch,
                'categoryName'    => $categoryName,
                'amount'          => $amount,
                'description'     => $description,
                'actionUrl'       => $actionUrl,
                'approveUrl'      => $approveUrl,
                'rejectUrl'       => $rejectUrl,
                'stepNumber'      => $stepNumber,
                'attachmentUrl'   => $expenseRequest->attachment_url,
                'attachmentUrls'  => $expenseRequest->attachment_urls,
            ], function ($message) use ($emails, $applicantName, $stepNumber) {
                $message->to($emails)
                        ->subject("Expense Approval Request (Stage {$stepNumber}) from {$applicantName} - myAgenci.ai HRMS");
            });
        } catch (\Throwable $e) {
            \Log::error('Expense request email failed: ' . $e->getMessage());
        }
    }

    /**
     * Helper to notify applicant when request status is finalized (Approved or Rejected).
     */
    private function sendApplicantStatusNotification(ExpenseRequest $expenseRequest, string $status, ?string $rejectionReason = null): void
    {
        $applicantUser = $expenseRequest->user;
        if (! $applicantUser || ! $applicantUser->email) {
            return;
        }

        $approverUser = auth()->user();
        $categoryName = $expenseRequest->category?->name ?? 'General Expense';
        $amount = $expenseRequest->amount;
        $actionUrl = route('hrms.expense-requests.index');

        try {
            Mail::send('emails.hrms.approval-flow', [
                'notifiable' => $applicantUser,
                'payload'    => [
                    'title'        => "Expense Request " . ucfirst($status),
                    'message'      => $status === 'approved' 
                        ? "Your expense request of ₹" . number_format($amount, 2) . " for {$categoryName} has been fully approved."
                        : "Your expense request of ₹" . number_format($amount, 2) . " for {$categoryName} was rejected.",
                    'status'       => $status,
                    'request_type' => 'Expense Request',
                    'detail'       => $status === 'rejected'
                        ? "Reason: " . ($rejectionReason ?: 'No reason provided')
                        : ($rejectionReason ? "Remarks: {$rejectionReason} | " : '') . "Category: {$categoryName} | Amount: ₹" . number_format($amount, 2),
                    'action_url'   => $actionUrl,
                    'actor_name'   => $approverUser?->name ?? 'Approver',
                ],
            ], function ($message) use ($applicantUser, $status) {
                $message->to($applicantUser->email)
                        ->subject("Your Expense Request has been " . ucfirst($status) . " - myAgenci.ai HRMS");
            });
        } catch (\Throwable $e) {
            \Log::error('Expense applicant status email failed: ' . $e->getMessage());
        }
    }

    /**
     * Automatically synchronize an approved HRMS Expense Request into the Accounts Module (AccountExpense).
     */
    private function syncToAccountExpense(
        ExpenseRequest $expenseRequest,
        User $approver,
        string $approvalRemarks,
        $paidAmount,
        $paidDate,
        ?int $selectedCategoryId = null,
        ?int $selectedSubcategoryId = null,
        ?string $receiptPath = null
    ): ?AccountExpense {
        try {
            $companyId = $expenseRequest->company_id ?: ($expenseRequest->user?->company_id ?: $approver->company_id);
            $applicant = $expenseRequest->user;
            $applicantName = $applicant ? $applicant->name : 'Employee';
            $applicantEmail = $applicant?->email;

            // 1. Resolve Account Expense Category
            $accCategory = null;
            if ($selectedCategoryId) {
                $accCategory = AccountExpenseCategory::withoutGlobalScopes()->find($selectedCategoryId);
            }

            if (! $accCategory) {
                $categoryName = $expenseRequest->category ? trim($expenseRequest->category->name) : 'Reimbursement';
                if (empty($categoryName)) {
                    $categoryName = 'Employee Reimbursement';
                }

                $accCategory = AccountExpenseCategory::withoutGlobalScopes()
                    ->where(function ($q) use ($companyId) {
                        if ($companyId) {
                            $q->where('company_id', $companyId)->orWhereNull('company_id');
                        }
                    })
                    ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($categoryName)])
                    ->first();

                if (! $accCategory) {
                    $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $categoryName), 0, 4));
                    if (empty($code)) {
                        $code = 'EXP';
                    }

                    $accCategory = AccountExpenseCategory::create([
                        'company_id'  => $companyId,
                        'name'        => $categoryName,
                        'code'        => $code,
                        'status'      => 'active',
                        'description' => 'Auto-created from HRMS Expense Category: ' . $categoryName,
                        'created_by'  => $approver->id,
                    ]);
                }
            }

            // 2. Resolve Account Expense Subcategory
            $accSubcategory = null;
            if ($selectedSubcategoryId) {
                $accSubcategory = AccountExpenseSubcategory::withoutGlobalScopes()->find($selectedSubcategoryId);
            }

            if (! $accSubcategory) {
                $accSubcategory = AccountExpenseSubcategory::withoutGlobalScopes()
                    ->where('account_expense_category_id', $accCategory->id)
                    ->where('status', 'active')
                    ->first();

                if (! $accSubcategory) {
                    $accSubcategory = AccountExpenseSubcategory::create([
                        'company_id'                  => $companyId,
                        'account_expense_category_id' => $accCategory->id,
                        'name'                        => 'General Reimbursement',
                        'code'                        => 'REIMB',
                        'status'                      => 'active',
                        'description'                 => 'Auto-created subcategory for ' . $accCategory->name,
                        'created_by'                  => $approver->id,
                    ]);
                }
            }

            // 3. Format Attachments (Approver payment receipt + applicant receipts)
            $accAttachments = [];
            if (!empty($receiptPath)) {
                $cleanReceipt = ltrim($receiptPath, '/');
                $fullReceipt = public_path($cleanReceipt);
                $exists = file_exists($fullReceipt);
                $accAttachments[] = [
                    'path'      => $cleanReceipt,
                    'name'      => basename($cleanReceipt),
                    'size'      => $exists ? @filesize($fullReceipt) : null,
                    'mime_type' => $exists ? @mime_content_type($fullReceipt) : null,
                ];
            }

            if (!empty($expenseRequest->attachments_list)) {
                foreach ($expenseRequest->attachments_list as $path) {
                    if (empty($path)) {
                        continue;
                    }
                    $cleanPath = ltrim($path, '/');
                    $fullPath = public_path($cleanPath);
                    $exists = file_exists($fullPath);
                    $accAttachments[] = [
                        'path'      => $cleanPath,
                        'name'      => basename($cleanPath),
                        'size'      => $exists ? @filesize($fullPath) : null,
                        'mime_type' => $exists ? @mime_content_type($fullPath) : null,
                    ];
                }
            }

            // 4. Build rich remarks
            $effectiveAmount = (float) ($paidAmount ?: ($expenseRequest->paid_amount ?: $expenseRequest->amount));
            $effectiveDate = $paidDate ?: ($expenseRequest->paid_date ? $expenseRequest->paid_date->toDateString() : now()->toDateString());

            $remarksLines = [];
            $remarksLines[] = "[HRMS Expense Request #{$expenseRequest->id}]";
            $remarksLines[] = "Applicant: {$applicantName}" . ($applicantEmail ? " ({$applicantEmail})" : '');
            $remarksLines[] = "Category: {$accCategory->name}" . ($accSubcategory ? " / {$accSubcategory->name}" : '');
            if (!empty($expenseRequest->description)) {
                $remarksLines[] = "Request Description: " . trim($expenseRequest->description);
            }
            if (!empty($approvalRemarks)) {
                $remarksLines[] = "Approver Remarks: " . trim($approvalRemarks);
            }
            $remarksLines[] = "Approved By: {$approver->name} on " . now()->format('d M Y, h:i A');
            $remarksLines[] = "Approved Paid Amount: ₹" . number_format($effectiveAmount, 2);

            $finalRemarks = implode("\n", $remarksLines);
            $invoiceRef = "HRMS-REQ-{$expenseRequest->id}";

            // 5. Check for existing Accounts Expense to prevent duplication
            $accountExpense = AccountExpense::withoutGlobalScopes()
                ->where(function ($q) use ($invoiceRef, $expenseRequest) {
                    $q->where('invoice_no', $invoiceRef)
                      ->orWhere('remarks', 'like', "%[HRMS Expense Request #{$expenseRequest->id}]%");
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
                    'updated_by'                     => $approver->id,
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
                    'created_by'                     => $approver->id,
                    'updated_by'                     => $approver->id,
                ]);
            }

            return $accountExpense;
        } catch (\Throwable $e) {
            Log::error("Failed to sync ExpenseRequest #{$expenseRequest->id} to AccountExpense: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }
}
