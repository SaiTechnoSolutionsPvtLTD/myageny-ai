<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use App\Models\ExpensePipeline;
use App\Models\ExpenseRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'attachment'          => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp,doc,docx|max:5120',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('expense_attachments', 'public');
        }

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
        $currentRoleId = $expenseRequest->current_approver_role_id ?: ($approvalChain[$currentStep - 1] ?? null);
        $currentRole = Role::withoutGlobalScopes()->find($currentRoleId);
        $currentRoleName = $currentRole?->display_name ?: ($currentRole ? ucfirst(str_replace('_', ' ', preg_replace('/^company_\d+__/', '', $currentRole->name))) : "Stage {$currentStep}");

        // Record stage approval in history
        $history = $expenseRequest->stage_history ?? [];
        $history[] = [
            'step'        => $currentStep,
            'role_id'     => (int) $currentRoleId,
            'role_name'   => $currentRoleName,
            'action'      => 'approved',
            'user_id'     => $user->id,
            'user_name'   => $user->name,
            'actioned_at' => now()->toDateTimeString(),
            'remarks'     => $request->input('remarks'),
        ];

        $nextStepIndex = $currentStep; // 1-indexed currentStep matches next 0-indexed element in chain

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
                'stage_history' => $history,
                'actioned_at'   => now(),
            ]);

            // Notify applicant of final approval
            $this->sendApplicantStatusNotification($expenseRequest, 'approved');

            $msg = "Expense request fully approved across all hierarchy stages!";
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
                    'detail'       => $status === 'rejected' ? "Reason: " . ($rejectionReason ?: 'No reason provided') : "Category: {$categoryName} | Amount: ₹" . number_format($amount, 2),
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
}
