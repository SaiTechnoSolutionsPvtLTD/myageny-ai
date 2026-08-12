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
        $userRoleIds = $user->roles->pluck('id')->toArray();

        $categories = ExpenseCategory::query()
            ->when($companyId, fn($q) => $q->where(fn($q2) => $q2->where('company_id', $companyId)->orWhereNull('company_id')))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $requests = ExpenseRequest::with(['user', 'category', 'approver', 'currentApproverRole'])
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
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

        return view('pages.hrms.expense-requests.index', compact('requests', 'categories', 'userRoleIds'));
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

        // Determine approval pipeline for user's role
        $userRole = $user->roles->first();
        $userRoleId = $userRole?->id;

        $pipeline = null;
        if ($userRoleId) {
            $pipeline = ExpensePipeline::where('role_id', $userRoleId)
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->where('is_active', true)
                ->first();
        }

        $approvalChain = $pipeline->approval_chain ?? [];
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
        ]);

        // Send Email Notification to the mapped Approver(s)
        $this->sendApproverNotification($expenseRequest);

        return redirect()
            ->route('hrms.expense-requests.index')
            ->with('success', 'Expense request submitted successfully. Email notification sent to approver.');
    }

    /**
     * Approve an expense request.
     */
    public function approve(Request $request, ExpenseRequest $expenseRequest): RedirectResponse
    {
        $user = auth()->user();
        $applicantUser = $expenseRequest->user;
        $userRole = $applicantUser?->roles->first();

        $pipeline = null;
        if ($userRole) {
            $pipeline = ExpensePipeline::where('role_id', $userRole->id)
                ->where('is_active', true)
                ->first();
        }

        $approvalChain = $pipeline->approval_chain ?? [];
        $currentStep = $expenseRequest->current_step;
        $nextStepIndex = $currentStep; // 1-indexed next is array index currentStep

        if (isset($approvalChain[$nextStepIndex])) {
            // Move to next approval stage in pipeline
            $expenseRequest->update([
                'current_step'             => $currentStep + 1,
                'current_approver_role_id' => (int) $approvalChain[$nextStepIndex],
            ]);

            $this->sendApproverNotification($expenseRequest);

            $msg = "Expense request approved for Stage {$currentStep}. Moved to Stage " . ($currentStep + 1) . " approval.";
        } else {
            // Final Approval reached!
            $expenseRequest->update([
                'status'      => 'approved',
                'approver_id' => $user->id,
                'actioned_at' => now(),
            ]);

            $msg = "Expense request fully approved!";
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
        $validated = $request->validate([
            'rejection_reason' => 'nullable|string|max:1000',
        ]);

        $expenseRequest->update([
            'status'           => 'rejected',
            'approver_id'      => auth()->id(),
            'rejection_reason' => $validated['rejection_reason'] ?? 'Request rejected by approver.',
            'actioned_at'      => now(),
        ]);

        return redirect()
            ->route('hrms.expense-requests.index')
            ->with('success', 'Expense request rejected.');
    }

    /**
     * Helper to find candidate approver users and trigger email notification.
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

        // Query users matching the approver role
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

        try {
            Mail::send('emails.expense_request_notification', [
                'applicantName'   => $applicantName,
                'applicantRole'   => $applicantRole,
                'applicantBranch' => $applicantBranch,
                'categoryName'    => $categoryName,
                'amount'          => $amount,
                'description'     => $description,
                'actionUrl'       => $actionUrl,
            ], function ($message) use ($emails, $applicantName) {
                $message->to($emails)
                        ->subject("Expense Approval Request from {$applicantName} - myAgenci.ai HRMS");
            });
        } catch (\Throwable $e) {
            \Log::error('Expense request email failed: ' . $e->getMessage());
        }
    }
}
