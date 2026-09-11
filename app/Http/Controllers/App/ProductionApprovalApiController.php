<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\ProductionInitiation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Services\NotificationService;
use App\Services\ProductionUpdateRecorder;

class ProductionApprovalApiController extends Controller
{
    public function __construct(private readonly NotificationService $notifications) {}

    // Bucket key -> underlying production_approval_status values. 'approval'
    // covers both 'approval' and 'approved' since different points in the
    // app have historically written either spelling; same for 'rejected'/
    // 'reject'. Keeping this map as the single source of truth avoids the
    // count query and the list query ever disagreeing about what belongs in
    // a bucket.
    private const BUCKET_STATUSES = [
        'pending'  => ['pending'],
        'approval' => ['approval', 'approved'],
        'rejected' => ['rejected', 'reject'],
    ];

    /**
     * GET /mobile/production-approvals?bucket=pending|approval|rejected&page=&per_page=
     *
     * Previously this endpoint ran `->get()` with no LIMIT at all — every
     * production initiation in the approval workflow (across the entire
     * company's history) was pulled from the DB, fully hydrated with 5
     * eager-loaded relations, formatted (including looping each row's
     * custom_form_data JSON blob), and shipped to the app in one response,
     * every single time the screen opened. That's what made the screen slow
     * as the table grew — this mirrors LeadController::index()'s approach
     * instead: paginate at the database level, and only fetch the bucket
     * the user is actually looking at.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'     => ['nullable', 'integer', 'min:1'],
        ]);

        $user = auth()->user();

        $bucket = $request->query('bucket');
        if (! array_key_exists($bucket, self::BUCKET_STATUSES)) {
            $bucket = 'pending';
        }

        $applyFilters = function ($query) use ($request) {
            $query->whereIn('status', ['approval', 'approved']);

            // Filters — mirrors web ProductionApprovalController::index() exactly.
            if ($request->filled('start_date')) {
                $query->whereDate('created_at', '>=', $request->query('start_date'));
            }
            if ($request->filled('end_date')) {
                $query->whereDate('created_at', '<=', $request->query('end_date'));
            }
            if ($request->filled('product_id')) {
                $query->where('product_id', $request->query('product_id'));
            }
            if ($request->filled('user_id')) {
                $query->where('production_approval_reviewed_by', $request->query('user_id'));
            }
            if ($request->filled('company_id')) {
                $query->where('company_id', $request->query('company_id'));
            }
            if ($request->filled('department_id')) {
                $query->where('department_id', $request->query('department_id'));
            }

            return $query;
        };

        // Counts for all 3 tab badges — cheap aggregate COUNT(*) queries
        // (no rows/relations hydrated), not a side effect of fetching
        // everything like the old implementation. Same filters apply to
        // every count so the badges stay consistent with whatever's active.
        $counts = [];
        foreach (self::BUCKET_STATUSES as $key => $statuses) {
            $countQuery = ProductionInitiation::query();
            $applyFilters($countQuery);
            $countQuery->whereIn('production_approval_status', $statuses);
            $counts[$key] = $countQuery->count();
        }

        $query = ProductionInitiation::query()
            ->with([
                'lead:id,company_name,contact_name',
                'department:id,name',
                'reviewedBy:id,name',
                'productionApprovalReviewedBy:id,name',
                'product:id,product_name,is_budget_approval_needed',
            ])
            ->whereIn('production_approval_status', self::BUCKET_STATUSES[$bucket]);
        $applyFilters($query);

        $perPage     = (int) $request->input('per_page', 15);
        $initiations = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'bucket' => $bucket,
                'items'  => $initiations->getCollection()
                    ->map(fn ($i) => $this->formatItem($i, $user))
                    ->values(),
                'pagination' => [
                    'current_page' => $initiations->currentPage(),
                    'last_page'    => $initiations->lastPage(),
                    'per_page'     => $initiations->perPage(),
                    'total'        => $initiations->total(),
                ],
                'counts' => $counts,
            ],
        ]);
    }

    /**
     * GET /mobile/production-approvals/filters
     *
     * Dropdown data for the mobile filter sheet — same four lists web's
     * ProductionApprovalController::index() passes into the Blade view.
     * Split out of index() so the app fetches (and caches) this once
     * instead of re-querying all products/departments/active users/
     * companies on every single list request.
     */
    public function filters(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'products' => \App\Models\Product::orderBy('product_name')
                    ->get(['id', 'product_name'])
                    ->map(fn($p) => ['id' => $p->id, 'name' => $p->product_name])
                    ->all(),
                'departments' => \App\Models\Department::orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn($d) => ['id' => $d->id, 'name' => $d->name])
                    ->all(),
                'users' => \App\Models\User::with(['roles.department'])
                    ->where('user_status', 'active')
                    ->orderBy('name')
                    ->get()
                    ->filter(function ($user) {
                        return $user->belongsToSalesDepartment()
                            || $user->hasSalesLikeRole()
                            || $user->belongsToCustomerSupportDepartment()
                            || $user->hasCustomerSupportLikeRole();
                    })
                    ->values()
                    ->map(fn($u) => ['id' => $u->id, 'name' => $u->name])
                    ->all(),
            ],
        ]);
    }

    public function review(Request $request, ProductionInitiation $productionInitiation): JsonResponse
    {
        if (! $this->canReview($productionInitiation)) {
            return response()->json(['success' => false, 'message' => 'You are not allowed to review this item.'], 403);
        }

        $rules = [
            'decision'                    => ['required', 'in:approval,rejected'],
            'production_approval_remarks' => ['required', 'string', 'max:5000'],
        ];

        // Budget approval — mirrors web ProductionApprovalController::review()
        // exactly: only required when the product needs it AND the decision
        // is an approval (rejecting never needs budget details).
        $product = $productionInitiation->product;
        $needsBudget = $product && $product->is_budget_approval_needed && $request->input('decision') === 'approval';
        if ($needsBudget) {
            $rules['lead_budget_amount'] = ['required', 'numeric', 'min:0'];
            $rules['budget_amount_type'] = ['required', 'string', 'max:255'];
            if ($request->input('budget_amount_type') === 'custom') {
                $rules['budget_amount_type_custom'] = ['required', 'string', 'max:255'];
            }
        }

        $validated = $request->validate($rules);

        $updateData = [
            'production_approval_status'       => $validated['decision'],
            'production_approval_remarks'      => trim($validated['production_approval_remarks']),
            'production_approval_reviewed_at'  => Carbon::now(),
            'production_approval_reviewed_by'  => auth()->id(),
            'project_allocation_status'        => $validated['decision'] === 'approval' ? 'allocation_pending' : null,
            'project_allocated_at'             => null,
            'project_allocated_by'             => null,
        ];

        if ($needsBudget) {
            $updateData['lead_budget_amount'] = $validated['lead_budget_amount'];
            $updateData['budget_amount_type'] = $validated['budget_amount_type'] === 'custom'
                ? $validated['budget_amount_type_custom']
                : $validated['budget_amount_type'];
        }

        $productionInitiation->update($updateData);

        try {
            $budgetData = null;
            if ($needsBudget && $validated['decision'] === 'approval') {
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

        $this->notifications->notify(
            $productionInitiation->initiatedBy,
            'crm',
            $validated['decision'] === 'approval' ? 'production_approval_approved' : 'production_approval_rejected',
            [
                'title' => $validated['decision'] === 'approval' ? 'Production Approved' : 'Production Approval Rejected',
                'message' => $validated['decision'] === 'approval'
                    ? 'Your production request was approved.'
                    : 'Your production request was rejected.',
                'detail' => $productionInitiation->production_approval_remarks,
                'action_url' => route('projects.show', $productionInitiation),
                'priority' => 'medium',
                'request_type' => 'production_approval',
                'request_id' => $productionInitiation->id,
                'actor_name' => auth()->user()?->name,
                'status' => $validated['decision'] === 'approval' ? 'approved' : 'rejected',
            ]
        );

        return response()->json([
            'success' => true,
            'message' => $validated['decision'] === 'approval'
                ? 'Production approval completed successfully.'
                : 'Production approval item moved to rejected.',
        ]);
    }

    private function formatItem(ProductionInitiation $i, ?\App\Models\User $user = null): array
    {
        $customFormData = [];
        if (is_array($i->custom_form_data)) {
            foreach ($i->custom_form_data as $field) {
                $label = $field['label'] ?? ($field['key'] ?? '');
                $value = $field['value'] ?? '';
                if ($label) {
                    $customFormData[] = [
                        'label'    => $label,
                        'value'    => is_array($value) ? implode(', ', $value) : (string) $value,
                        'is_file'  => ($field['type'] ?? '') === 'file',
                        'file_url' => ($field['type'] ?? '') === 'file' ? ($field['file_url'] ?? null) : null,
                    ];
                }
            }
        }

        // Budget approval — mirrors the fields/permission web's Blade uses:
        // `is_budget_approval_needed` gates whether the review form must
        // collect budget details, `canViewBudgetApprovalDetails()` gates
        // whether the already-collected amount is shown at all.
        $canViewBudget = $user?->canViewBudgetApprovalDetails() ?? false;

        return [
            'id'                              => $i->id,
            // Lets the mobile app deep-link straight to this record's Lead
            // Details screen without a separate lookup — the `lead`
            // relation is already eager-loaded in index(), so this is free.
            'lead_id'                         => $i->lead_id,
            'product_name'                    => $i->product_name ?? '',
            'total_working_days'              => $i->total_working_days ?? 0,
            'department'                      => $i->department?->name ?? '',
            'company_name'                    => $i->lead?->company_name ?? $i->company_name ?? '',
            'client_name'                     => $i->lead?->contact_name ?? $i->client_name ?? '',
            'production_approval_status'      => $i->production_approval_status,
            'bucket'                          => $this->resolveBucket((string) $i->production_approval_status),
            'ovp_reviewed_by'                 => $i->reviewedBy?->name,
            'ovp_reviewed_at'                 => $i->reviewed_at?->toIso8601String(),
            'actioned_by'                     => $i->productionApprovalReviewedBy?->name,
            'actioned_at'                     => $i->production_approval_reviewed_at?->toIso8601String(),
            'approval_remarks'                => $i->production_approval_remarks,
            'custom_form_data'                => $customFormData,
            'can_review'                      => $this->canReview($i),
            'is_budget_approval_needed'       => (bool) ($i->product?->is_budget_approval_needed ?? false),
            'can_view_budget_details'         => $canViewBudget,
            'lead_budget_amount'              => $canViewBudget ? $i->lead_budget_amount : null,
            'budget_amount_type'              => $canViewBudget ? $i->budget_amount_type : null,
        ];
    }

    private function resolveBucket(string $status): ?string
    {
        return match (strtolower(trim($status))) {
            'pending'              => 'pending',
            'approval', 'approved' => 'approval',
            'rejected', 'reject'   => 'rejected',
            default                => null,
        };
    }

    private function canReview(ProductionInitiation $i): bool
    {
        return in_array(strtolower(trim((string) $i->status)), ['approval', 'approved'], true)
            && strtolower(trim((string) $i->production_approval_status)) === 'pending';
    }
}
