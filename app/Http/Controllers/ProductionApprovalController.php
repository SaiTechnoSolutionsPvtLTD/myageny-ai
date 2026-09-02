<?php

namespace App\Http\Controllers;

use App\Models\ProductionInitiation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Services\ProductionUpdateRecorder;

class ProductionApprovalController extends Controller
{
    public function index(Request $request): View
    {
        $query = ProductionInitiation::query()
            ->with([
                'lead:id,company_name,contact_name',
                'department:id,name',
                'reviewedBy:id,name',
                'productionApprovalReviewedBy:id,name',
                'product:id,product_name,is_budget_approval_needed',
            ])
            ->whereIn('status', ['approval', 'approved'])
            ->whereIn('production_approval_status', ['pending', 'approval', 'approved', 'rejected', 'reject']);

        // Apply filters
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->filled('status')) {
            $query->where('production_approval_status', $request->status);
        }
        if ($request->filled('user_id')) {
            $query->where('production_approval_reviewed_by', $request->user_id);
        }
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        $initiations = $query->latest()->get();

        $buckets = [
            'pending' => [
                'title' => 'Pending',
                'status_label' => 'Pending',
                'count' => 0,
                'items' => collect(),
            ],
            'approval' => [
                'title' => 'Approval',
                'status_label' => 'Approval',
                'count' => 0,
                'items' => collect(),
            ],
            'rejected' => [
                'title' => 'Rejects',
                'status_label' => 'Rejected',
                'count' => 0,
                'items' => collect(),
            ],
        ];

        foreach ($initiations as $initiation) {
            $bucket = $this->resolveBucket((string) $initiation->production_approval_status);

            if (! $bucket) {
                continue;
            }

            /** @var Collection $items */
            $items = $buckets[$bucket]['items'];
            $items->push($initiation);

            $buckets[$bucket]['items'] = $items;
            $buckets[$bucket]['count']++;
        }

        $selectedBucket = (string) $request->query('bucket', 'pending');

        if (! array_key_exists($selectedBucket, $buckets)) {
            $selectedBucket = 'pending';
        }

        $products = \App\Models\Product::orderBy('product_name')->get(['id', 'product_name']);
        $departments = \App\Models\Department::orderBy('name')->get(['id', 'name']);
        $users = \App\Models\User::where('user_status', 'active')->orderBy('name')->get(['id', 'name']);
        $companies = \App\Models\Company::orderBy('company_name')->get(['id', 'company_name']);

        return view('pages.production_approvals.index', [
            'cards' => $buckets,
            'selectedBucket' => $selectedBucket,
            'selectedCard' => $buckets[$selectedBucket],
            'products' => $products,
            'departments' => $departments,
            'users' => $users,
            'companies' => $companies,
        ]);
    }

    public function review(Request $request, ProductionInitiation $productionInitiation): RedirectResponse
    {
        abort_unless($this->canReview($productionInitiation), 403);

        $rules = [
            'decision' => ['required', 'in:approval,rejected'],
            'production_approval_remarks' => ['required', 'string', 'max:5000'],
        ];

        $product = $productionInitiation->product;
        if ($product && $product->is_budget_approval_needed && $request->input('decision') === 'approval') {
            $rules['lead_budget_amount'] = ['required', 'numeric', 'min:0'];
            $rules['budget_amount_type'] = ['required', 'string', 'max:255'];
            if ($request->input('budget_amount_type') === 'custom') {
                $rules['budget_amount_type_custom'] = ['required', 'string', 'max:255'];
            }
        }

        $validated = $request->validate($rules);

        $updateData = [
            'production_approval_status' => $validated['decision'],
            'production_approval_remarks' => trim($validated['production_approval_remarks']),
            'production_approval_reviewed_at' => Carbon::now(),
            'production_approval_reviewed_by' => auth()->id(),
            'project_allocation_status' => $validated['decision'] === 'approval' ? 'allocation_pending' : null,
            'project_allocated_at' => null,
            'project_allocated_by' => null,
        ];

        if ($product && $product->is_budget_approval_needed && $validated['decision'] === 'approval') {
            $updateData['lead_budget_amount'] = $validated['lead_budget_amount'];
            if ($validated['budget_amount_type'] === 'custom') {
                $updateData['budget_amount_type'] = $validated['budget_amount_type_custom'];
            } else {
                $updateData['budget_amount_type'] = $validated['budget_amount_type'];
            }
        }

        $productionInitiation->update($updateData);

        try {
            $budgetData = null;
            if ($product && $product->is_budget_approval_needed && $validated['decision'] === 'approval') {
                $budgetData = [
                    'lead_budget_amount' => $updateData['lead_budget_amount'] ?? null,
                    'budget_amount_type' => $updateData['budget_amount_type'] ?? null,
                ];
            }
            app(ProductionUpdateRecorder::class)->recordProductionApproval(
                $productionInitiation->fresh(),
                $validated['decision'],
                $updateData['production_approval_remarks'] ?? null,
                auth()->user(),
                $budgetData
            );
        } catch (\Throwable $e) {
            Log::error('Failed to record production approval update: ' . $e->getMessage());
        }

        if ($validated['decision'] === 'approval') {
            try {
                $productionInitiation->loadMissing(['lead.assignedTo', 'lead.createdBy', 'leadProduct', 'department']);
                $salesPerson = $productionInitiation->lead?->assignedTo ?: $productionInitiation->lead?->createdBy;
                $salesPersonEmail = $salesPerson?->email;

                $deptName = strtolower(trim((string) ($productionInitiation->department?->name ?? '')));
                if (! $deptName && $productionInitiation->product_name) {
                    $deptName = strtolower(trim((string) $productionInitiation->product_name));
                }

                $isDigitalMarketing = str_contains($deptName, 'digital')
                    || str_contains($deptName, 'marketing')
                    || str_contains($deptName, 'dm');

                $departmentEmail = $isDigitalMarketing ? 'dm@saitechnosolutions.net' : 'projects@saitechnosolutions.net';

                $toEmails = array_values(array_filter(array_unique([
                    $salesPersonEmail,
                    $departmentEmail,
                ])));

                $reviewedBy = auth()->user();

                Mail::send('emails.production_approval_approved', [
                    'initiation' => $productionInitiation,
                    'lead' => $productionInitiation->lead,
                    'leadProduct' => $productionInitiation->leadProduct,
                    'departmentName' => $productionInitiation->department?->name ?? 'Production',
                    'reviewedBy' => $reviewedBy,
                    'salesPerson' => $salesPerson,
                    'departmentEmail' => $departmentEmail,
                ], function ($message) use ($toEmails, $productionInitiation) {
                    $message->to($toEmails)
                        ->subject('Production Approval Approved - Lead #' . $productionInitiation->lead_id . ' (' . ($productionInitiation->product_name ?: 'Product') . ')');
                });
            } catch (\Throwable $exception) {
                Log::error('Failed to send production approval email.', [
                    'initiation_id' => $productionInitiation->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        } elseif ($validated['decision'] === 'rejected' || $validated['decision'] === 'reject') {
            try {
                $productionInitiation->loadMissing(['lead.assignedTo', 'lead.createdBy', 'leadProduct', 'department']);
                $salesPerson = $productionInitiation->lead?->assignedTo ?: $productionInitiation->lead?->createdBy;
                $salesPersonEmail = $salesPerson?->email;

                $toEmails = array_values(array_filter(array_unique([
                    'customersuccessteam.sts@gmail.com',
                    'customersuccess@saitechnosolutions.net',
                    $salesPersonEmail,
                ])));

                $reviewedBy = auth()->user();

                Mail::send('emails.production_approval_rejected', [
                    'initiation' => $productionInitiation,
                    'lead' => $productionInitiation->lead,
                    'leadProduct' => $productionInitiation->leadProduct,
                    'departmentName' => $productionInitiation->department?->name ?? 'Production',
                    'reviewedBy' => $reviewedBy,
                    'salesPerson' => $salesPerson,
                ], function ($message) use ($toEmails, $productionInitiation) {
                    $message->to($toEmails)
                        ->subject('Production Approval Rejected - Lead #' . $productionInitiation->lead_id . ' (' . ($productionInitiation->product_name ?: 'Product') . ')');
                });
            } catch (\Throwable $exception) {
                Log::error('Failed to send production approval rejection email.', [
                    'initiation_id' => $productionInitiation->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return redirect()
            ->route('production-approvals.index', ['bucket' => $validated['decision'] === 'approval' ? 'approval' : 'rejected'])
            ->with('success', $validated['decision'] === 'approval'
                ? 'Production approval completed successfully.'
                : 'Production approval item moved to rejected.');
    }

    private function resolveBucket(string $status): ?string
    {
        return match (strtolower(trim($status))) {
            'pending' => 'pending',
            'approval', 'approved' => 'approval',
            'rejected', 'reject' => 'rejected',
            default => null,
        };
    }

    private function canReview(ProductionInitiation $productionInitiation): bool
    {
        $ovpStatus = strtolower(trim((string) $productionInitiation->status));
        $productionApprovalStatus = strtolower(trim((string) $productionInitiation->production_approval_status));

        return in_array($ovpStatus, ['approval', 'approved'], true)
            && $productionApprovalStatus === 'pending';
    }
}
