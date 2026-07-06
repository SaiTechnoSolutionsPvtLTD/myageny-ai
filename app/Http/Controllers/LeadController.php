<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeadRequest;
use App\Http\Requests\UpdateLeadRequest;
use App\Http\Resources\LeadSourceCollection;
use App\Http\Resources\LeadStatusCollection;
use App\Models\Branch;
use App\Models\Lead;
use App\Models\LeadFieldValue;
use App\Models\LeadFormField;
use App\Models\LeadProduct;
use App\Models\LeadProductPriceRequest;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\OutcomeCategory;
use App\Models\Product;
use App\Models\User;
use App\Services\DataVisibilityService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    public function __construct(private readonly DataVisibilityService $visibility) {}

    /**
     * List all leads with full filter support.
     */
    public function index(Request $request)
    {
        $defaultFromDate = now()->startOfMonth()->toDateString();
        $defaultToDate = now()->endOfMonth()->toDateString();

        if (!$request->filled('date_from') && !$request->filled('date_to')) {
            $request->merge([
                'date_from' => $defaultFromDate,
                'date_to' => $defaultToDate,
            ]);
        }

        $query = Lead::with(['branch', 'assignedTo', 'createdBy', 'products'])
            ->latest('lead_date');

        $this->visibility->applyLeadVisibility($query);

        // ── Filters ──────────────────────────────────────────────
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('company_name',  'like', "%{$s}%")
                  ->orWhere('contact_name','like', "%{$s}%")
                  ->orWhere('mobile_number','like',"%{$s}%")
                  ->orWhere('email',        'like', "%{$s}%")
                  ->orWhere('product_name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('mobile_number')) {
            $query->where('mobile_number', 'like', '%' . $request->mobile_number . '%');
        }

        if ($request->filled('lead_source')) {
            $query->where('lead_source', $request->lead_source);
        }

        if ($request->filled('lead_status')) {
            $query->where('lead_status', $request->lead_status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->filled('product_name')) {
            $query->where('product_name', 'like', '%' . $request->product_name . '%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('lead_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('lead_date', '<=', $request->date_to);
        }

        $activeLeadIds = (clone $query)->pluck('leads.id');

        $leads    = $query->paginate(15)->withQueryString();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $users    = $this->visibility->visibleAssignableUsers();
        $productQuery = Lead::select('product_name')->whereNotNull('product_name')->distinct();
        $this->visibility->applyLeadVisibility($productQuery);
        $products = $productQuery->pluck('product_name');

        // Stats for top cards
        $stats = [
            'total'         => $activeLeadIds->count(),
            'total_products'=> LeadProduct::whereIn('lead_id', $activeLeadIds)->count(),
            'pipeline'      => LeadProduct::whereIn('lead_id', $activeLeadIds)->sum('total_price'),
            'new'           => Lead::whereIn('id', $activeLeadIds)->whereDoesntHave('callUpdates')->count(),
        ];

        $filterPanelOpen =
            $request->filled('search')
            || $request->filled('branch_id')
            || $request->filled('mobile_number')
            || $request->filled('lead_source')
            || $request->filled('lead_status')
            || $request->filled('priority')
            || $request->filled('assigned_to')
            || $request->filled('product_name')
            || $request->input('date_from') !== $defaultFromDate
            || $request->input('date_to') !== $defaultToDate;

        return view('pages.leads.index', compact('leads', 'branches', 'users', 'products', 'stats', 'defaultFromDate', 'defaultToDate', 'filterPanelOpen'));
    }

    /**
     * List lead products with lead/customer/payment filter support.
     */
    public function productsIndex(Request $request)
    {
        $defaultFromDate = now()->startOfMonth()->toDateString();
        $defaultToDate = now()->endOfMonth()->toDateString();

        if (!$request->filled('date_from') && !$request->filled('date_to')) {
            $request->merge([
                'date_from' => $defaultFromDate,
                'date_to' => $defaultToDate,
            ]);
        }

        $query = LeadProduct::query()
            ->with(['lead.branch', 'lead.assignedTo', 'product'])
            ->whereHas('lead')
            ->latest('created_at');

        $this->visibility->applyLeadRelationVisibility($query);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);

            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($productQuery) use ($search) {
                        $productQuery->where('product_name', 'like', "%{$search}%")
                            ->orWhere('package_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('lead', function ($leadQuery) use ($search) {
                        $leadQuery->where('company_name', 'like', "%{$search}%")
                            ->orWhere('contact_name', 'like', "%{$search}%")
                            ->orWhere('mobile_number', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('branch_id')) {
            $branchId = $request->branch_id;
            $query->whereHas('lead', fn ($leadQuery) => $leadQuery->where('branch_id', $branchId));
        }

        if ($request->filled('assigned_to')) {
            $assignedTo = $request->assigned_to;
            $query->whereHas('lead', fn ($leadQuery) => $leadQuery->where('assigned_to', $assignedTo));
        }

        if ($request->filled('product_status')) {
            $query->where('product_status', $request->product_status);
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('mobile_number')) {
            $mobileNumber = $request->mobile_number;
            $query->whereHas('lead', fn ($leadQuery) => $leadQuery->where('mobile_number', 'like', '%' . $mobileNumber . '%'));
        }

        if ($request->filled('lead_id')) {
            $query->where('lead_id', $request->lead_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $leadProducts = $query->paginate(15)->withQueryString();

        $statsBase = clone $query;
        $statsRows = $statsBase->get();

        $stats = [
            'total_products' => $statsRows->count(),
            'total_value' => (float) $statsRows->sum('total_price'),
            'received' => (float) $statsRows->sum('amount_paid'),
            'pending' => (float) $statsRows->sum(fn (LeadProduct $leadProduct) => $leadProduct->amount_pending),
        ];

        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $users = $this->visibility->visibleAssignableUsers();
        $productOptions = Product::query()->orderBy('package_name');
        $this->visibility->applyProductVisibility($productOptions);
        $products = $productOptions->get(['id', 'package_name', 'product_name']);
        $filterPanelOpen =
            $request->filled('lead_id')
            || $request->filled('mobile_number')
            || $request->filled('product_id')
            || $request->filled('product_status')
            || $request->filled('branch_id')
            || $request->filled('assigned_to')
            || $request->input('date_from') !== $defaultFromDate
            || $request->input('date_to') !== $defaultToDate;

        return view('pages.leads.products.index', [
            'leadProducts' => $leadProducts,
            'branches' => $branches,
            'users' => $users,
            'products' => $products,
            'stats' => $stats,
            'defaultFromDate' => $defaultFromDate,
            'defaultToDate' => $defaultToDate,
            'filterPanelOpen' => $filterPanelOpen,
        ]);
    }

    /**
     * Show create form.
     */
    public function create()
    {
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $users    = $this->visibility->visibleAssignableUsers();
        $customFields = $this->customFieldsForForm();

        return view('pages.leads.create', compact('branches', 'users', 'customFields'));
    }

    /**
     * Store new lead.
     */
    public function store(StoreLeadRequest $request)
    {
        $assignedUser = User::findOrFail($request->assigned_to);

        if (auth()->user()?->company_id !== null && (int) $assignedUser->company_id !== (int) auth()->user()->company_id) {
            abort(403);
        }

        abort_unless($this->visibility->canAssignTo($assignedUser->id), 403);

        $lead = Lead::create(array_merge($request->validated(), [
            'company_id' => auth()->user()?->company_id,
        ]));
        $this->syncCustomFieldValues($lead, $request->input('custom_fields', []));

        return redirect()
            ->route('leads.show', $lead)
            ->with('success', "Lead for <strong>{$lead->company_name}</strong> created successfully.");
    }

    /**
     * Show single lead.
     */
    public function show(Lead $lead)
    {
        abort_unless($this->visibility->canAccessLead($lead), 403);

        $lead->load([
            'branch',
            'assignedTo',
            'createdBy',
            'callUpdates.user',
            'reminders',
            'products.payments',
            'products.latestProductionInitiation.department',
            'products.latestProductionInitiation.initiatedBy',
            'products.latestProductionInitiation.reviewedBy',
            'products.latestProductionInitiation.productionApprovalReviewedBy',
            'products.latestProductionInitiation.projectAllocatedBy',
            'products.latestProductionInitiation.employeeAllocatedBy',
            'products.latestProductionInitiation.projectUpdates.createdBy',
            'quotations',
            'customFieldValues.field' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order')->orderBy('label');
            },
        ]);
        $outcomes = OutcomeCategory::get();
        $pendingPriceRequestCount = LeadProductPriceRequest::where('lead_id', $lead->id)
            ->where('status', 'pending')
            ->count();

        return view('pages.leads.show', compact('lead', 'outcomes', 'pendingPriceRequestCount'));
    }

    /**
     * Show edit form.
     */
    public function edit(Lead $lead)
    {
        abort_unless($this->visibility->canAccessLead($lead), 403);

        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $users    = $this->visibility->visibleAssignableUsers();
        $customFields = $this->customFieldsForForm();
        $lead->load('customFieldValues');

        return view('pages.leads.edit', compact('lead', 'branches', 'users', 'customFields'));
    }

    /**
     * Update lead.
     */
    public function update(UpdateLeadRequest $request, Lead $lead)
    {
        $assignedUser = User::findOrFail($request->assigned_to);

        if (auth()->user()?->company_id !== null && (int) $assignedUser->company_id !== (int) auth()->user()->company_id) {
            abort(403);
        }

        abort_unless($this->visibility->canAccessLead($lead), 403);
        abort_unless($this->visibility->canAssignTo($assignedUser->id), 403);

        $lead->update($request->validated());
        $this->syncCustomFieldValues($lead, $request->input('custom_fields', []));

        return redirect()
            ->route('leads.show', $lead)
            ->with('success', "Lead <strong>{$lead->company_name}</strong> updated successfully.");
    }

    /**
     * Soft-delete lead.
     */
    public function destroy(Lead $lead)
    {
        abort_unless($this->visibility->canAccessLead($lead), 403);

        $name = $lead->company_name;
        $lead->delete();

        return redirect()
            ->route('leads.index')
            ->with('success', "Lead <strong>{$name}</strong> has been removed.");
    }

    /**
     * Quick status update (AJAX-friendly PATCH).
     */
    public function updateStatus(Request $request, Lead $lead)
    {
        abort_unless($this->visibility->canAccessLead($lead), 403);

        $request->validate([
            'lead_status' => ['required', 'string', Rule::in(Lead::statusKeys())],
        ]);

        $lead->update(['lead_status' => $request->lead_status]);

        return back()->with('success', "Lead status updated to <strong>{$lead->status_label}</strong>.");
    }

    public function leadStatus()
    {
        $leadStatus = LeadStatus::get();

        return new LeadStatusCollection($leadStatus);
    }

    public function leadSource()
    {
        $leadSource = LeadSource::get();

        return new LeadSourceCollection($leadSource);
    }

    protected function customFieldsForForm()
    {
        return LeadFormField::query()
            ->where('is_active', true)
            ->where('show_on_lead_create', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();
    }

    protected function syncCustomFieldValues(Lead $lead, array $submittedValues): void
    {
        $fields = LeadFormField::query()
            ->where('is_active', true)
            ->where('show_on_lead_create', true)
            ->where(function ($query) use ($lead) {
                $query->whereNull('branch_id')
                    ->orWhere('branch_id', $lead->branch_id);
            })
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();
        $allowedFieldIds = $fields->pluck('id')->all();

        LeadFieldValue::query()
            ->where('lead_id', $lead->id)
            ->whereNotIn('lead_form_field_id', $allowedFieldIds)
            ->delete();

        foreach ($fields as $field) {
            $submittedValue = $submittedValues[$field->id] ?? null;

            if (is_array($submittedValue)) {
                $submittedValue = array_values(array_filter($submittedValue, fn ($value) => $value !== null && $value !== ''));
            }

            $normalizedValue = is_array($submittedValue)
                ? json_encode($submittedValue)
                : ($submittedValue !== null ? trim((string) $submittedValue) : null);

            if ($normalizedValue === null || $normalizedValue === '' || $normalizedValue === '[]') {
                LeadFieldValue::query()
                    ->where('lead_id', $lead->id)
                    ->where('lead_form_field_id', $field->id)
                    ->delete();
                continue;
            }

            LeadFieldValue::updateOrCreate(
                [
                    'lead_id' => $lead->id,
                    'lead_form_field_id' => $field->id,
                ],
                [
                    'value' => $normalizedValue,
                ]
            );
        }
    }
}
