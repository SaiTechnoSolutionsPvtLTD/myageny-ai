<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use App\Models\ExpensePipeline;
use App\Models\ExpenseRequest;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Mobile API — Expense Reimbursements.
 *
 * Mirrors web's ExpenseRequestController exactly: same visibility scoping
 * (HR/Admin see all company requests, everyone else sees their own +
 * anything they're the current approver for), same ExpensePipeline
 * role-chain resolution, same approve/reject semantics and email
 * notifications (emails.expense_request_notification /
 * emails.hrms.approval-flow — reused, not duplicated). See
 * app/Http/Controllers/ExpenseRequestController.php for the source of
 * truth this mirrors. Nothing here changes web behaviour or web files.
 *
 * Mobile-only addition on top of that mirror: in-app (database) Notification
 * Center rows via NotificationService, alongside the existing emails — see
 * sendApproverNotification()/sendApplicantStatusNotification() below. Web
 * never created these either (this was a pre-existing gap on both
 * platforms); adding it here only, not to the web controller, per the
 * "mobile app only" scope of this fix.
 */
class ExpenseRequestApiController extends Controller
{
    private const STATUSES = ['pending', 'approved', 'rejected'];

    public function __construct(private readonly NotificationService $notifications) {}

    // ── GET /mobile/expense-requests ─────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;
            $userRoleIds = $user->roles->pluck('id')->toArray();
            $isHrOrAdmin = $user->isHrOrAdmin();

            $scopedQuery = ExpenseRequest::with(['user.roles', 'user.branch', 'category', 'approver', 'currentApproverRole'])
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId));

            if (! $isHrOrAdmin) {
                $scopedQuery->where(function ($q) use ($user, $userRoleIds) {
                    $q->where('user_id', $user->id)
                      ->orWhere('approver_id', $user->id)
                      ->orWhere(function ($q2) use ($userRoleIds) {
                          $q2->whereIn('current_approver_role_id', $userRoleIds)
                             ->where('status', 'pending');
                      });
                });
            }

            $totalCount    = (clone $scopedQuery)->count();
            $pendingCount  = (clone $scopedQuery)->where('status', 'pending')->count();
            $approvedCount = (clone $scopedQuery)->where('status', 'approved')->count();
            $rejectedCount = (clone $scopedQuery)->where('status', 'rejected')->count();

            $requests = $scopedQuery
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
                ->when($request->filled('expense_category_id'), fn ($q) => $q->where('expense_category_id', $request->expense_category_id))
                ->when($request->filled('search'), function ($query) use ($request) {
                    $search = trim((string) $request->search);
                    $query->where(function ($q) use ($search) {
                        $q->where('description', 'like', '%' . $search . '%')
                          ->orWhere('amount', 'like', '%' . $search . '%')
                          ->orWhereHas('user', fn ($u) => $u->where('name', 'like', '%' . $search . '%'))
                          ->orWhereHas('category', fn ($c) => $c->where('name', 'like', '%' . $search . '%'));
                    });
                })
                ->latest()
                ->paginate(min((int) ($request->per_page ?? 20), 50));

            return response()->json([
                'success' => true,
                'data' => [
                    'requests' => $requests->map(fn ($r) => $this->formatExpenseRequest($r, $user)),
                    'counts' => [
                        'total' => $totalCount,
                        'pending' => $pendingCount,
                        'approved' => $approvedCount,
                        'rejected' => $rejectedCount,
                    ],
                    'pagination' => [
                        'current_page' => $requests->currentPage(),
                        'last_page' => $requests->lastPage(),
                        'per_page' => $requests->perPage(),
                        'total' => $requests->total(),
                        'has_more' => $requests->hasMorePages(),
                    ],
                ],
            ]);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Unable to load expense requests. Please try again.',
            ], 500);
        }
    }

    // ── GET /mobile/expense-requests/filters ─────────────────────────────────
    public function filters(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;

            $categories = ExpenseCategory::query()
                ->when($companyId, fn ($q) => $q->where(fn ($q2) => $q2->where('company_id', $companyId)->orWhereNull('company_id')))
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']);

            return response()->json([
                'success' => true,
                'data' => [
                    'categories' => $categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values(),
                    'statuses' => collect(self::STATUSES)->map(fn ($s) => ['key' => $s, 'label' => ucfirst($s)])->values(),
                ],
            ]);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Unable to load filters. Please try again.',
            ], 500);
        }
    }

    // ── POST /mobile/expense-requests ────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
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

            $pipeline = $this->resolvePipelineForUser($user, $companyId);
            $approvalChain = $pipeline->approval_chain ?? [];
            $currentStep = 1;
            $currentApproverRoleId = ! empty($approvalChain) ? (int) $approvalChain[0] : null;

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
            ]);

            $this->sendApproverNotification($expenseRequest);

            $expenseRequest->load(['user.roles', 'user.branch', 'category', 'approver', 'currentApproverRole']);

            return response()->json([
                'success' => true,
                'message' => 'Expense request submitted successfully.',
                'data' => $this->formatExpenseRequest($expenseRequest, $user),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first() ?: 'Please check the form and try again.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Unable to submit expense request. Please try again.',
            ], 500);
        }
    }

    // ── POST /mobile/expense-requests/{expenseRequest}/approve ──────────────
    public function approve(Request $request, ExpenseRequest $expenseRequest): JsonResponse
    {
        try {
            $user = $request->user();

            if ($expenseRequest->company_id && $user->company_id && $expenseRequest->company_id !== $user->company_id) {
                return response()->json(['success' => false, 'message' => 'Expense request not found.'], 404);
            }

            $userRoleIds = $user->roles->pluck('id')->toArray();
            $canApprove = $user->isHrOrAdmin() || in_array($expenseRequest->current_approver_role_id, $userRoleIds);

            if (! $canApprove) {
                return response()->json(['success' => false, 'message' => 'You are not authorized to approve this expense request.'], 403);
            }

            if ($expenseRequest->status !== 'pending') {
                return response()->json(['success' => false, 'message' => "This expense request is already {$expenseRequest->status}."], 422);
            }

            $applicantUser = $expenseRequest->user;
            $applicantCompanyId = $applicantUser?->company_id ?: $expenseRequest->company_id;
            $pipeline = $this->resolvePipelineForUser($applicantUser, $applicantCompanyId);

            $approvalChain = $pipeline->approval_chain ?? [];
            $currentStep = $expenseRequest->current_step;
            $nextStepIndex = $currentStep;

            if (isset($approvalChain[$nextStepIndex])) {
                $expenseRequest->update([
                    'current_step'             => $currentStep + 1,
                    'current_approver_role_id' => (int) $approvalChain[$nextStepIndex],
                ]);

                $this->sendApproverNotification($expenseRequest);
                $msg = "Approved for Stage {$currentStep}. Notified Stage " . ($currentStep + 1) . " approver(s).";
            } else {
                $expenseRequest->update([
                    'status'      => 'approved',
                    'approver_id' => $user->id,
                    'actioned_at' => now(),
                ]);

                $this->sendApplicantStatusNotification($expenseRequest, 'approved');
                $msg = 'Expense request fully approved across all pipeline stages!';
            }

            $expenseRequest->load(['user.roles', 'user.branch', 'category', 'approver', 'currentApproverRole']);

            return response()->json([
                'success' => true,
                'message' => $msg,
                'data' => $this->formatExpenseRequest($expenseRequest, $user),
            ]);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Unable to approve expense request. Please try again.',
            ], 500);
        }
    }

    // ── POST /mobile/expense-requests/{expenseRequest}/reject ───────────────
    public function reject(Request $request, ExpenseRequest $expenseRequest): JsonResponse
    {
        try {
            $user = $request->user();

            if ($expenseRequest->company_id && $user->company_id && $expenseRequest->company_id !== $user->company_id) {
                return response()->json(['success' => false, 'message' => 'Expense request not found.'], 404);
            }

            $userRoleIds = $user->roles->pluck('id')->toArray();
            $canReject = $user->isHrOrAdmin() || in_array($expenseRequest->current_approver_role_id, $userRoleIds);

            if (! $canReject) {
                return response()->json(['success' => false, 'message' => 'You are not authorized to reject this expense request.'], 403);
            }

            if ($expenseRequest->status !== 'pending') {
                return response()->json(['success' => false, 'message' => "This expense request is already {$expenseRequest->status}."], 422);
            }

            $validated = $request->validate([
                'rejection_reason' => 'required|string|max:1000',
            ]);

            $expenseRequest->update([
                'status'           => 'rejected',
                'approver_id'      => $user->id,
                'rejection_reason' => $validated['rejection_reason'],
                'actioned_at'      => now(),
            ]);

            $this->sendApplicantStatusNotification($expenseRequest, 'rejected', $validated['rejection_reason']);

            $expenseRequest->load(['user.roles', 'user.branch', 'category', 'approver', 'currentApproverRole']);

            return response()->json([
                'success' => true,
                'message' => 'Expense request rejected.',
                'data' => $this->formatExpenseRequest($expenseRequest, $user),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first() ?: 'Please provide a rejection reason.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Unable to reject expense request. Please try again.',
            ], 500);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Same pipeline lookup web's store()/approve() perform inline — the
     * applicant's first active ExpensePipeline matching any of their roles,
     * scoped to their company. No caching/service extraction here since web
     * doesn't have one either (kept as a 1:1 mirror, not a refactor).
     */
    private function resolvePipelineForUser(?User $user, $companyId): ?ExpensePipeline
    {
        if (! $user) {
            return null;
        }
        $roleIds = $user->roles->pluck('id')->toArray();
        if (empty($roleIds)) {
            return null;
        }
        return ExpensePipeline::whereIn('role_id', $roleIds)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('is_active', true)
            ->first();
    }

    private function roleLabel(?\App\Models\Role $role): ?string
    {
        if (! $role) {
            return null;
        }
        return $role->display_name ?: ucfirst(str_replace('_', ' ', $role->name));
    }

    private function formatExpenseRequest(ExpenseRequest $r, User $currentUser): array
    {
        $applicant = $r->user;
        $applicantCompanyId = $applicant?->company_id ?: $r->company_id;
        $pipeline = $this->resolvePipelineForUser($applicant, $applicantCompanyId);
        $chain = $pipeline->approval_chain ?? [];

        $roles = ! empty($chain)
            ? \App\Models\Role::whereIn('id', $chain)->get()->keyBy('id')
            : collect();

        $stages = [];
        foreach ($chain as $index => $roleId) {
            $stepNumber = $index + 1;
            $stages[] = [
                'step'       => $stepNumber,
                'role_id'    => (int) $roleId,
                'role_label' => $this->roleLabel($roles->get($roleId)),
                'is_current' => $r->status === 'pending' && $r->current_step === $stepNumber,
                'is_completed' => $r->status === 'approved' || $r->current_step > $stepNumber,
            ];
        }

        $userRoleIds = $currentUser->roles->pluck('id')->toArray();
        $canAction = $r->status === 'pending'
            && ($currentUser->isHrOrAdmin() || in_array($r->current_approver_role_id, $userRoleIds));

        return [
            'id' => $r->id,
            'applicant' => $applicant ? [
                'id' => $applicant->id,
                'name' => $applicant->name,
                'role_label' => $this->roleLabel($applicant->roles->first()),
                'branch_name' => $applicant->branch?->name,
            ] : null,
            'category' => $r->category ? ['id' => $r->category->id, 'name' => $r->category->name] : null,
            'amount' => (float) $r->amount,
            'description' => $r->description,
            'attachment_url' => $r->attachment ? Storage::disk('public')->url($r->attachment) : null,
            'status' => $r->status,
            'status_label' => ucfirst($r->status),
            'current_step' => $r->current_step,
            'total_steps' => count($chain) ?: null,
            'current_approver_role' => $r->currentApproverRole ? [
                'id' => $r->currentApproverRole->id,
                'label' => $this->roleLabel($r->currentApproverRole),
            ] : null,
            'pipeline_stages' => $stages,
            'approver' => $r->approver ? ['id' => $r->approver->id, 'name' => $r->approver->name] : null,
            'rejection_reason' => $r->rejection_reason,
            'actioned_at' => $r->actioned_at?->toIso8601String(),
            'created_at' => $r->created_at?->toIso8601String(),
            'can_approve' => $canAction,
            'can_reject' => $canAction,
        ];
    }

    /**
     * Identical to web's private sendApproverNotification() — same
     * branch/department/fallback priority for finding candidate approvers,
     * same mail view. Duplicated here (not extracted into a shared service)
     * because web's method is private and web files must not be touched;
     * this keeps the mobile controller a self-contained 1:1 mirror, matching
     * the existing CstAllocationApiController / PreSalesApiController pattern.
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

        $query = User::whereHas('roles', function ($q) use ($approverRoleId) {
            $q->where('id', $approverRoleId);
        })->when($companyId, fn ($q) => $q->where('company_id', $companyId));

        $branchApprovers = clone $query;
        if ($applicantBranchId) {
            $branchApprovers->where(function ($q) use ($applicantBranchId) {
                $q->where('branch_id', $applicantBranchId)
                  ->orWhereHas('branches', fn ($b) => $b->where('branches.id', $applicantBranchId));
            });
        }

        $approverUsers = $branchApprovers->get();

        if ($approverUsers->isEmpty() && $applicantDepartmentId) {
            $deptApprovers = clone $query;
            $deptApprovers->where('department_id', $applicantDepartmentId);
            $approverUsers = $deptApprovers->get();
        }

        if ($approverUsers->isEmpty()) {
            $approverUsers = $query->get();
        }

        $categoryName = $expenseRequest->category?->name ?? 'General Expense';
        $applicantName = $applicantUser->name;
        $applicantRole = $this->roleLabel($applicantUser->roles->first()) ?? 'Employee';
        $applicantBranch = $applicantUser->branch?->name ?? 'Main Branch';
        $amount = $expenseRequest->amount;
        $description = $expenseRequest->description;
        $actionUrl = route('hrms.expense-requests.index');
        $approveUrl = route('hrms.expense-requests.email-approve', $expenseRequest);
        $rejectUrl  = route('hrms.expense-requests.email-reject', $expenseRequest);
        $stepNumber = $expenseRequest->current_step ?? 1;

        // In-app Notification Center row — database channel only (AppNotification
        // only adds a mail channel for MAIL_ENABLED_TYPES, which doesn't include
        // expense_request_* types), so this never duplicates the Mail::send below.
        $this->notifications->notifyMany($approverUsers, 'hrms', 'expense_request_pending', [
            'title' => "Expense Approval Request (Stage {$stepNumber})",
            'message' => "{$applicantName} submitted an expense request of ₹" . number_format($amount, 2) . " for {$categoryName} — awaiting your approval.",
            'detail' => $description,
            'action_url' => $actionUrl,
            'action_label' => 'Review Request',
            'priority' => 'medium',
            'request_type' => 'expense',
            'request_id' => $expenseRequest->id,
            'actor_name' => $applicantName,
            'requester_name' => $applicantName,
            'status' => 'pending',
        ]);

        $emails = $approverUsers->pluck('email')->filter()->toArray();
        if (empty($emails)) {
            return;
        }

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
        } catch (Throwable $e) {
            \Log::error('Expense request email failed: ' . $e->getMessage());
        }
    }

    private function sendApplicantStatusNotification(ExpenseRequest $expenseRequest, string $status, ?string $rejectionReason = null): void
    {
        $applicantUser = $expenseRequest->user;
        if (! $applicantUser) {
            return;
        }

        $approverUser = auth()->user();
        $categoryName = $expenseRequest->category?->name ?? 'General Expense';
        $amount = $expenseRequest->amount;
        $actionUrl = route('hrms.expense-requests.index');

        $message = $status === 'approved'
            ? "Your expense request of ₹" . number_format($amount, 2) . " for {$categoryName} has been fully approved."
            : "Your expense request of ₹" . number_format($amount, 2) . " for {$categoryName} was rejected.";
        $detail = $status === 'rejected'
            ? 'Reason: ' . ($rejectionReason ?: 'No reason provided')
            : "Category: {$categoryName} | Amount: ₹" . number_format($amount, 2);

        // In-app Notification Center row — fires regardless of whether the
        // applicant has an email on file (the email below still requires
        // one, same as before); database-only channel, same reasoning as
        // sendApproverNotification() above.
        $this->notifications->notify($applicantUser, 'hrms', "expense_request_{$status}", [
            'title' => 'Expense Request ' . ucfirst($status),
            'message' => $message,
            'detail' => $detail,
            'action_url' => $actionUrl,
            'action_label' => 'View Details',
            'priority' => 'medium',
            'request_type' => 'expense',
            'request_id' => $expenseRequest->id,
            'actor_name' => $approverUser?->name ?? 'Approver',
            'requester_name' => $applicantUser->name,
            'status' => $status,
        ]);

        if (! $applicantUser->email) {
            return;
        }

        try {
            Mail::send('emails.hrms.approval-flow', [
                'notifiable' => $applicantUser,
                'payload'    => [
                    'title'        => 'Expense Request ' . ucfirst($status),
                    'message'      => $status === 'approved'
                        ? "Your expense request of ₹" . number_format($amount, 2) . " for {$categoryName} has been fully approved."
                        : "Your expense request of ₹" . number_format($amount, 2) . " for {$categoryName} was rejected.",
                    'status'       => $status,
                    'request_type' => 'Expense Request',
                    'detail'       => $status === 'rejected' ? 'Reason: ' . ($rejectionReason ?: 'No reason provided') : "Category: {$categoryName} | Amount: ₹" . number_format($amount, 2),
                    'action_url'   => $actionUrl,
                    'actor_name'   => $approverUser?->name ?? 'Approver',
                ],
            ], function ($message) use ($applicantUser, $status) {
                $message->to($applicantUser->email)
                        ->subject('Your Expense Request has been ' . ucfirst($status) . ' - myAgenci.ai HRMS');
            });
        } catch (Throwable $e) {
            \Log::error('Expense applicant status email failed: ' . $e->getMessage());
        }
    }
}
