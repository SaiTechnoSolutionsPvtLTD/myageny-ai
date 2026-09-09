<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeadRequest;
use App\Http\Requests\UpdateLeadRequest;
use App\Http\Resources\LeadSourceCollection;
use App\Http\Resources\LeadStatusCollection;
use App\Models\Branch;
use App\Models\Lead;
use App\Models\LeadCstUpdate;
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
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
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

        $this->resolveQuickDate($request, $defaultFromDate, $defaultToDate);

        $query = Lead::with(['branch', 'assignedTo', 'createdBy', 'preSaleExecutive', 'products'])
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
            $sourceInput = $request->lead_source;

            $sourceObj = null;
            if (is_numeric($sourceInput)) {
                $sourceObj = LeadSource::find($sourceInput);
            } else {
                $sourceObj = LeadSource::where('name', $sourceInput)
                    ->orWhere('id', $sourceInput)
                    ->first();
            }

            if ($sourceObj) {
                $query->where('lead_source_id', $sourceObj->id);
            } else {
                $query->where(function ($q) use ($sourceInput) {
                    $q->whereHas('leadSource', function ($lsq) use ($sourceInput) {
                        $lsq->where('name', 'like', "%{$sourceInput}%");
                    })
                    ->orWhere('lead_source', 'like', "%{$sourceInput}%");
                });
            }
        }

        if ($request->filled('lead_status')) {
            $statusInput = $request->lead_status;

            $statusObj = null;
            if (is_numeric($statusInput)) {
                $statusObj = LeadStatus::find($statusInput);
            } else {
                $statusObj = LeadStatus::where('name', $statusInput)
                    ->orWhere('id', $statusInput)
                    ->first();
            }

            if ($statusObj) {
                $statusId = $statusObj->id;
                $query->where(function ($q) use ($statusId) {
                    $q->where('lead_status_id', $statusId)
                      ->orWhereHas('products', function ($pq) use ($statusId) {
                          $pq->where('lead_status_id', $statusId);
                      });
                });
            } else {
                $query->where(function ($q) use ($statusInput) {
                    $q->whereHas('leadStatus', function ($lsq) use ($statusInput) {
                        $lsq->where('name', 'like', "%{$statusInput}%");
                    })
                    ->orWhereHas('products', function ($pq) use ($statusInput) {
                        $pq->where('product_status', 'like', "%{$statusInput}%");
                    });
                });
            }
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->filled('pre_sale_executive_id')) {
            $query->where('pre_sale_executive_id', $request->pre_sale_executive_id);
        }

        if ($request->filled('product_name')) {
            $query->where('product_name', 'like', '%' . $request->product_name . '%');
        }

        if ($request->boolean('untouched') || $request->input('is_untouched') === '1' || $request->input('untouched') === '1') {
            $query->whereDoesntHave('callUpdates');
        }

        if ($request->filled('date_from')) {
            $dateFrom = $request->date_from;
            $query->where(function ($dq) use ($dateFrom) {
                $dq->whereDate('lead_date', '>=', $dateFrom)
                   ->orWhereDate('created_at', '>=', $dateFrom);
            });
        }

        if ($request->filled('date_to')) {
            $dateTo = $request->date_to;
            $query->where(function ($dq) use ($dateTo) {
                $dq->whereDate('lead_date', '<=', $dateTo)
                   ->orWhereDate('created_at', '<=', $dateTo);
            });
        }

        $activeLeadIds = (clone $query)->pluck('leads.id');
        $lpProducts    = LeadProduct::whereIn('lead_id', $activeLeadIds)->get();

        $leads    = $query->paginate(15)->withQueryString();
        $branches = $this->visibility->visibleBranches($request->user());
        $users    = $this->visibility->visibleAssignableUsers($request->user())
            ->reject(fn ($u) => $u->hasPreSalesLikeRole())
            ->values();
        
        $preSaleExecutives = User::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereHas('roles', fn ($rq) => $rq->where('name', 'like', '%pre_sale%')->orWhere('display_name', 'like', '%pre%sale%'))
                  ->orWhereIn('id', Lead::query()->whereNotNull('pre_sale_executive_id')->distinct()->pluck('pre_sale_executive_id'));
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $productQuery = Lead::select('product_name')->whereNotNull('product_name')->distinct();
        $this->visibility->applyLeadVisibility($productQuery);
        $products = $productQuery->pluck('product_name');

        $sourceOptions = LeadSource::orderBy('name')->get(['id', 'name']);
        $statusOptions = LeadStatus::orderBy('name')->get(['id', 'name']);

        // Untouched leads count (filtered user-wise and by active filters)
        $untouchedCountQuery = Lead::query();
        $this->visibility->applyLeadVisibility($untouchedCountQuery);
        $untouchedCountQuery
            ->when($request->filled('assigned_to'), fn($q) => $q->where('assigned_to', $request->assigned_to))
            ->when($request->filled('branch_id'), fn($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->filled('pre_sale_executive_id'), fn($q) => $q->where('pre_sale_executive_id', $request->pre_sale_executive_id))
            ->when($request->filled('lead_source'), function($q) use ($request) {
                $sourceInput = $request->lead_source;
                $sourceObj = is_numeric($sourceInput)
                    ? LeadSource::find($sourceInput)
                    : LeadSource::where('name', $sourceInput)->orWhere('id', $sourceInput)->first();
                if ($sourceObj) {
                    $q->where('lead_source_id', $sourceObj->id);
                } else {
                    $q->where(function ($sq) use ($sourceInput) {
                        $sq->whereHas('leadSource', fn ($lsq) => $lsq->where('name', 'like', "%{$sourceInput}%"))
                           ->orWhere('lead_source', 'like', "%{$sourceInput}%");
                    });
                }
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->search;
                $q->where(function ($sq) use ($s) {
                    $sq->where('company_name',  'like', "%{$s}%")
                      ->orWhere('contact_name','like', "%{$s}%")
                      ->orWhere('mobile_number','like',"%{$s}%")
                      ->orWhere('email',        'like', "%{$s}%")
                      ->orWhere('product_name', 'like', "%{$s}%");
                });
            })
            ->when($request->filled('date_from'), fn($q) => $q->where(function($dq) use ($request) {
                $dq->whereDate('lead_date', '>=', $request->date_from)->orWhereDate('created_at', '>=', $request->date_from);
            }))
            ->when($request->filled('date_to'), fn($q) => $q->where(function($dq) use ($request) {
                $dq->whereDate('lead_date', '<=', $request->date_to)->orWhereDate('created_at', '<=', $request->date_to);
            }));

        $untouchedCount = $untouchedCountQuery->whereDoesntHave('callUpdates')->count();

        $convertedStatusIds = LeadStatus::query()
            ->where(function ($q) {
                $q->whereRaw('LOWER(name) in (?, ?)', ['converted', 'won'])
                  ->orWhere('name', 'like', '%convert%');
            })
            ->pluck('id')
            ->toArray();

        $isConvertedProduct = function (LeadProduct $lp) use ($convertedStatusIds) {
            $status = strtolower(trim((string) $lp->product_status));
            return in_array($status, ['converted', 'won'])
                || ($lp->lead_status_id && in_array($lp->lead_status_id, $convertedStatusIds));
        };

        $convertedProducts = $lpProducts->filter($isConvertedProduct);
        $nonConvertedProducts = $lpProducts->reject($isConvertedProduct);

        $convertedProductsCount = $convertedProducts->count();
        $upcomingAmount = (float) $nonConvertedProducts->sum('total_price');
        $convertedValue = (float) $convertedProducts->sum('total_price');
        $totalProductsCount = $lpProducts->count();
        $totalPipeline = (float) $lpProducts->sum('total_price');
        $wonLeadsCount = (clone $query)->converted()->count();

        // Stats for top cards
        $stats = [
            'total'              => $activeLeadIds->count(),
            'active_customers'   => $wonLeadsCount,
            'total_products'     => $totalProductsCount,
            'converted_products' => $convertedProductsCount,
            'upcoming_amount'    => $upcomingAmount,
            'converted_value'    => $convertedValue,
            'pipeline'           => $totalPipeline,
            'new'                => $untouchedCount,
        ];

        $filterPanelOpen = !$request->has('reset') && (
            $request->filled('search')
            || $request->filled('branch_id')
            || $request->filled('mobile_number')
            || $request->filled('lead_source')
            || $request->filled('lead_status')
            || $request->filled('priority')
            || $request->filled('assigned_to')
            || $request->filled('pre_sale_executive_id')
            || $request->filled('product_name')
            || ($request->has('date_from') && $request->input('date_from') !== $defaultFromDate)
            || ($request->has('date_to') && $request->input('date_to') !== $defaultToDate)
        );

        return view('pages.leads.index', compact('leads', 'branches', 'users', 'preSaleExecutives', 'products', 'stats', 'defaultFromDate', 'defaultToDate', 'filterPanelOpen', 'sourceOptions', 'statusOptions'));
    }

    /**
     * Dedicated page for Untouched Leads (overall, without date limits by default).
     */
    public function untouchedIndex(Request $request)
    {
        $defaultFromDate = now()->startOfMonth()->toDateString();
        $defaultToDate   = now()->endOfMonth()->toDateString();

        if ($request->has('reset')) {
            $request->merge([
                'date_from'  => null,
                'date_to'    => null,
                'quick_date' => 'all',
            ]);
        } else {
            $quickDate = $request->input('quick_date') ?? $request->input('quick_select');
            if ($quickDate && $quickDate !== 'all' && $quickDate !== 'custom') {
                $dates = match ($quickDate) {
                    'today'               => [now()->toDateString(), now()->toDateString()],
                    'week', 'this_week'   => [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()],
                    'month', 'this_month' => [$defaultFromDate, $defaultToDate],
                    'quarter'             => [now()->startOfQuarter()->toDateString(), now()->endOfQuarter()->toDateString()],
                    'year', 'this_year'   => [now()->startOfYear()->toDateString(), now()->endOfYear()->toDateString()],
                    default               => null,
                };
                if ($dates) {
                    $request->merge([
                        'date_from'  => $dates[0],
                        'date_to'    => $dates[1],
                        'quick_date' => $quickDate,
                    ]);
                }
            } elseif ($quickDate === 'all') {
                $request->merge([
                    'date_from'  => null,
                    'date_to'    => null,
                    'quick_date' => 'all',
                ]);
            } elseif ($request->filled('date_from') || $request->filled('date_to')) {
                $parsedFrom = $this->parseDateInput($request->input('date_from'));
                $parsedTo   = $this->parseDateInput($request->input('date_to'));
                $request->merge([
                    'date_from'  => $parsedFrom,
                    'date_to'    => $parsedTo,
                    'quick_date' => 'custom',
                ]);
            }
        }

        $query = Lead::with(['branch', 'assignedTo', 'createdBy', 'preSaleExecutive', 'products'])
            ->whereDoesntHave('callUpdates')
            ->latest('lead_date');

        $this->visibility->applyLeadVisibility($query);

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
            $sourceInput = $request->lead_source;
            $sourceObj = is_numeric($sourceInput)
                ? LeadSource::find($sourceInput)
                : LeadSource::where('name', $sourceInput)->orWhere('id', $sourceInput)->first();

            if ($sourceObj) {
                $query->where('lead_source_id', $sourceObj->id);
            } else {
                $query->where(function ($q) use ($sourceInput) {
                    $q->whereHas('leadSource', fn ($lsq) => $lsq->where('name', 'like', "%{$sourceInput}%"))
                      ->orWhere('lead_source', 'like', "%{$sourceInput}%");
                });
            }
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->filled('pre_sale_executive_id')) {
            $query->where('pre_sale_executive_id', $request->pre_sale_executive_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('lead_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('lead_date', '<=', $request->date_to);
        }

        $activeLeadIds = (clone $query)->pluck('leads.id');
        $leads    = $query->paginate(15)->withQueryString();
        $branches = $this->visibility->visibleBranches($request->user());
        $users    = $this->visibility->visibleAssignableUsers($request->user())
            ->reject(fn ($u) => $u->hasPreSalesLikeRole())
            ->values();

        $preSaleExecutives = User::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereHas('roles', fn ($rq) => $rq->where('name', 'like', '%pre_sale%')->orWhere('display_name', 'like', '%pre%sale%'))
                  ->orWhereIn('id', Lead::query()->whereNotNull('pre_sale_executive_id')->distinct()->pluck('pre_sale_executive_id'));
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $productQuery = Lead::select('product_name')->whereNotNull('product_name')->distinct();
        $this->visibility->applyLeadVisibility($productQuery);
        $products = $productQuery->pluck('product_name');

        $sourceOptions = LeadSource::orderBy('name')->get(['id', 'name']);
        $statusOptions = LeadStatus::orderBy('name')->get(['id', 'name']);

        $stats = [
            'total'         => $activeLeadIds->count(),
            'total_products'=> LeadProduct::whereIn('lead_id', $activeLeadIds)->count(),
            'pipeline'      => LeadProduct::whereIn('lead_id', $activeLeadIds)->sum('total_price'),
            'new'           => $activeLeadIds->count(),
        ];

        $filterPanelOpen = !$request->has('reset') && (
            $request->filled('search')
            || $request->filled('branch_id')
            || $request->filled('mobile_number')
            || $request->filled('lead_source')
            || $request->filled('assigned_to')
            || $request->filled('pre_sale_executive_id')
            || ($request->filled('quick_date') && $request->input('quick_date') !== 'all')
            || $request->filled('date_from')
            || $request->filled('date_to')
        );

        return view('pages.leads.untouched', compact(
            'leads',
            'branches',
            'users',
            'preSaleExecutives',
            'products',
            'stats',
            'defaultFromDate',
            'defaultToDate',
            'filterPanelOpen',
            'sourceOptions',
            'statusOptions'
        ));
    }

    /**
     * List lead products with lead/customer/payment filter support.
     */
    public function productsIndex(Request $request)
    {
        $defaultFromDate = now()->startOfMonth()->toDateString();
        $defaultToDate = now()->endOfMonth()->toDateString();

        $this->resolveQuickDate($request, $defaultFromDate, $defaultToDate);

        $query = LeadProduct::query()
            ->with(['lead.branch', 'lead.assignedTo', 'product', 'leadStatus'])
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
            $statusVal = $request->product_status;
            if (is_numeric($statusVal)) {
                $statusRecord = LeadStatus::find($statusVal);
                $statusName = $statusRecord ? strtolower($statusRecord->name) : null;
                $query->where(function ($q) use ($statusVal, $statusName) {
                    $q->where('lead_status_id', (int) $statusVal);
                    if ($statusName) {
                        $q->orWhere('product_status', $statusName);
                    }
                });
            } else {
                $statusRecord = LeadStatus::where('name', 'like', $statusVal)->first();
                $statusId = $statusRecord?->id;
                $query->where(function ($q) use ($statusVal, $statusId) {
                    $q->where('product_status', $statusVal);
                    if ($statusId) {
                        $q->orWhere('lead_status_id', $statusId);
                    }
                });
            }
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $cleanId = preg_replace('/[^0-9]/', '', $search);

            $query->whereHas('lead', function ($leadQuery) use ($search, $cleanId) {
                $leadQuery->where(function ($q) use ($search, $cleanId) {
                    $q->where('contact_name', 'like', '%' . $search . '%')
                      ->orWhere('company_name', 'like', '%' . $search . '%')
                      ->orWhere('mobile_number', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%');

                    if ($cleanId !== '' && is_numeric($cleanId)) {
                        $q->orWhere('id', (int) $cleanId);
                    }
                });
            });
        }

        if ($request->filled('mobile_number')) {
            $mobileNumber = $request->mobile_number;
            $query->whereHas('lead', fn ($leadQuery) => $leadQuery->where('mobile_number', 'like', '%' . $mobileNumber . '%'));
        }

        if ($request->filled('lead_id')) {
            $query->where('lead_id', $request->lead_id);
        }

        if ($request->filled('date_from')) {
            $dateFrom = $request->date_from;
            $query->whereHas('lead', function ($lq) use ($dateFrom) {
                $lq->whereDate('lead_date', '>=', $dateFrom)
                   ->orWhereDate('created_at', '>=', $dateFrom);
            });
        }

        if ($request->filled('date_to')) {
            $dateTo = $request->date_to;
            $query->whereHas('lead', function ($lq) use ($dateTo) {
                $lq->whereDate('lead_date', '<=', $dateTo)
                   ->orWhereDate('created_at', '<=', $dateTo);
            });
        }

        $statsBase = (clone $query)->with('payments');
        $leadProducts = $query->paginate(15)->withQueryString();

        $statsRows = $statsBase->get();

        $stats = [
            'total_products' => $statsRows->count(),
            'total_value' => (float) $statsRows->sum('total_price'),
            'received' => (float) $statsRows->sum(fn (LeadProduct $leadProduct) => $leadProduct->amount_paid),
            'pending' => (float) $statsRows->sum(fn (LeadProduct $leadProduct) => $leadProduct->amount_pending),
        ];

        $branches = $this->visibility->visibleBranches($request->user());
        $users = $this->visibility->visibleAssignableUsers($request->user());
        $productOptions = Product::query()->orderBy('package_name');
        $this->visibility->applyProductVisibility($productOptions);
        $products = $productOptions->get(['id', 'package_name', 'product_name']);
        $statusOptions = LeadStatus::orderBy('name')->get(['id', 'name']);
        $filterPanelOpen = !$request->has('reset') && (
            $request->filled('search')
            || $request->filled('lead_id')
            || $request->filled('mobile_number')
            || $request->filled('product_id')
            || $request->filled('product_status')
            || $request->filled('branch_id')
            || $request->filled('assigned_to')
            || ($request->has('date_from') && $request->input('date_from') !== $defaultFromDate)
            || ($request->has('date_to') && $request->input('date_to') !== $defaultToDate)
        );

        return view('pages.leads.products.index', [
            'leadProducts' => $leadProducts,
            'branches' => $branches,
            'users' => $users,
            'products' => $products,
            'stats' => $stats,
            'defaultFromDate' => $defaultFromDate,
            'defaultToDate' => $defaultToDate,
            'filterPanelOpen' => $filterPanelOpen,
            'statusOptions' => $statusOptions,
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

        $leadData = array_merge($request->validated(), [
            'company_id' => auth()->user()?->company_id,
        ]);

        if ($assignedUser->belongsToCustomerSupportDepartment() || $assignedUser->hasCustomerSupportLikeRole()) {
            $mappedTl = $assignedUser->mappedManagers()
                ->where(function ($q) {
                    $q->whereHas('roles', fn ($rq) => $rq->where('name', 'like', '%tl%')->orWhere('name', 'like', '%lead%'))
                      ->orWhereHas('roles.department', fn ($dq) => $dq->where('name', 'like', '%support%')->orWhere('name', 'like', '%cst%'));
                })
                ->first();

            $leadData['customer_support_tl_id'] = $mappedTl?->id ?: $assignedUser->id;
            $leadData['customer_support_executive_id'] = $assignedUser->id;
            $leadData['customer_support_allocated_at'] = now();
        }

        $lead = Lead::create($leadData);
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
        // abort_unless($this->visibility->canAccessLead($lead), 403);

        $lead->load([
            'branch',
            'assignedTo',
            'preSaleExecutive',
            'createdBy',
            'callUpdates.user',
            'reminders',
            'products.product',
            'products.payments',
            'products.latestProductionInitiation.department',
            'products.latestProductionInitiation.initiatedBy',
            'products.latestProductionInitiation.reviewedBy',
            'products.latestProductionInitiation.productionApprovalReviewedBy',
            'products.latestProductionInitiation.projectAllocatedBy',
            'products.latestProductionInitiation.employeeAllocatedBy',
            'products.latestProductionInitiation.projectUpdates.createdBy',
            'quotations',
            'cstUpdates.user',
            'cstUpdates.product.product',
            'customFieldValues.field' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order')->orderBy('label');
            },
        ]);
        $userCompanyId = auth()->user()?->company_id;
        $outcomes = OutcomeCategory::when($userCompanyId, function ($q) use ($userCompanyId) {
            $q->where(function ($q2) use ($userCompanyId) {
                $q2->where('company_id', $userCompanyId)->orWhereNull('company_id');
            });
        })->get();
        $pendingPriceRequestCount = LeadProductPriceRequest::where('lead_id', $lead->id)
            ->where('status', 'pending')
            ->count();

        $cstUpdatesCount = $lead->cstUpdates->count();
        $cstOnlyCount = $lead->cstUpdates->where('update_type', 'cst_update')->count();
        $weeklyOnlyCount = $lead->cstUpdates->where('update_type', 'weekly_update')->count();
        $reviewOnlyCount = $lead->cstUpdates->where('update_type', 'review')->count();
        $escalationOnlyCount = $lead->cstUpdates->where('update_type', 'escalation')->count();

        $convertedProducts = $lead->products
            ->filter(fn ($p) => strtolower((string) $p->product_status) === 'converted' || $p->product_status_key === 'converted')
            ->values();

        if ($convertedProducts->isEmpty() && $lead->products->isNotEmpty()) {
            $convertedProducts = $lead->products;
        }

        $salesExecutives = $this->visibility->visibleAssignableUsers();

        return view('pages.leads.show', compact(
            'lead',
            'outcomes',
            'pendingPriceRequestCount',
            'cstUpdatesCount',
            'cstOnlyCount',
            'weeklyOnlyCount',
            'reviewOnlyCount',
            'escalationOnlyCount',
            'convertedProducts',
            'salesExecutives'
        ));
    }

    /**
     * Reassign lead to a Sales Executive (for Pre Sales Executive).
     */
    public function reassign(Request $request, Lead $lead): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user?->hasPreSalesLikeRole() || $user?->isSystemAdmin() || $user?->isCompanyAdmin(), 403, 'Only Pre Sales Executives can reassign leads.');

        $request->validate([
            'assigned_to' => ['required', 'exists:users,id'],
        ]);

        $assignedUser = User::findOrFail($request->assigned_to);

        $lead->update([
            'assigned_to' => $assignedUser->id,
            'pre_sale_executive_id' => $user->id,
        ]);

        return redirect()->back()->with('success', "Lead for <strong>{$lead->company_name}</strong> successfully reassigned to <strong>{$assignedUser->name}</strong>.");
    }

    public function storeCstUpdate(Request $request, Lead $lead): RedirectResponse
    {
        abort_unless(auth()->user()?->isCustomerSuccessUser(), 403, 'Only Customer Success Team members can add CST updates.');

        $validated = $request->validate([
            'update_type' => ['required', Rule::in(['cst_update', 'weekly_update', 'review', 'escalation'])],
            'lead_product_id' => ['nullable', 'exists:lead_products,id'],
            'notes' => ['required', 'string', 'max:5000'],
        ]);

        $lead->cstUpdates()->create([
            'company_id' => auth()->user()?->company_id,
            'lead_product_id' => $validated['lead_product_id'] ?? null,
            'update_type' => $validated['update_type'],
            'notes' => $validated['notes'],
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'CST Update added successfully.');
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

        $updateData = $request->validated();
        if (auth()->user()?->hasPreSalesLikeRole()) {
            $updateData['pre_sale_executive_id'] = auth()->id();
        }

        if ($assignedUser->belongsToCustomerSupportDepartment() || $assignedUser->hasCustomerSupportLikeRole()) {
            $mappedTl = $assignedUser->mappedManagers()
                ->where(function ($q) {
                    $q->whereHas('roles', fn ($rq) => $rq->where('name', 'like', '%tl%')->orWhere('name', 'like', '%lead%'))
                      ->orWhereHas('roles.department', fn ($dq) => $dq->where('name', 'like', '%support%')->orWhere('name', 'like', '%cst%'));
                })
                ->first();

            $updateData['customer_support_tl_id'] = $mappedTl?->id ?: $assignedUser->id;
            $updateData['customer_support_executive_id'] = $assignedUser->id;
            if (empty($lead->customer_support_allocated_at)) {
                $updateData['customer_support_allocated_at'] = now();
            }
        }

        $lead->update($updateData);
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
            'lead_status_id' => ['required', 'integer', 'exists:lead_statuses,id'],
        ]);

        $lead->update(['lead_status_id' => $request->lead_status_id]);

        return back()->with('success', 'Lead status updated successfully.');
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
            $normalizedValue = null;

            if ($field->field_type === 'file') {
                $fileInputKey = "custom_fields.{$field->id}";
                if (request()->hasFile($fileInputKey)) {
                    $uploadedFile = request()->file($fileInputKey);
                    $targetDir = public_path('uploads/custom_fields');
                    if (!file_exists($targetDir)) {
                        mkdir($targetDir, 0777, true);
                    }
                    $extension = $uploadedFile->getClientOriginalExtension();
                    $filename = time() . '_' . uniqid('cf_') . ($extension ? '.' . $extension : '');
                    $uploadedFile->move($targetDir, $filename);
                    $normalizedValue = 'uploads/custom_fields/' . $filename;
                } else {
                    $existingFile = request()->input("existing_custom_files.{$field->id}");
                    $normalizedValue = $existingFile ?: ($submittedValues[$field->id] ?? null);
                }
            } else {
                $submittedValue = $submittedValues[$field->id] ?? null;

                if (is_array($submittedValue)) {
                    $submittedValue = array_values(array_filter($submittedValue, fn ($value) => $value !== null && $value !== ''));
                }

                $normalizedValue = is_array($submittedValue)
                    ? json_encode($submittedValue)
                    : ($submittedValue !== null ? trim((string) $submittedValue) : null);
            }

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

    private function resolveQuickDate(Request $request, string $defaultFromDate, string $defaultToDate): void
    {
        // If user clicked Reset, clear date filters
        if ($request->has('reset')) {
            $request->merge([
                'date_from'  => null,
                'date_to'    => null,
                'quick_date' => null,
            ]);
            return;
        }

        $quickDate = $request->input('quick_date') ?? $request->input('quick_select');

        // If user explicitly selected 'Show All'
        if ($quickDate === 'all') {
            $request->merge([
                'date_from'  => null,
                'date_to'    => null,
                'quick_date' => 'all',
            ]);
            return;
        }

        // If user selected 'custom'
        if ($quickDate === 'custom') {
            $parsedFrom = $this->parseDateInput($request->input('date_from'));
            $parsedTo   = $this->parseDateInput($request->input('date_to'));
            $request->merge([
                'date_from'  => $parsedFrom,
                'date_to'    => $parsedTo,
                'quick_date' => 'custom',
            ]);
            return;
        }

        // If quick_date was requested for standard presets
        if ($quickDate) {
            $dates = match ($quickDate) {
                'today'               => [now()->toDateString(), now()->toDateString()],
                'week', 'this_week'   => [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()],
                'month', 'this_month' => [$defaultFromDate, $defaultToDate],
                'quarter'             => [now()->startOfQuarter()->toDateString(), now()->endOfQuarter()->toDateString()],
                'year', 'this_year'   => [now()->startOfYear()->toDateString(), now()->endOfYear()->toDateString()],
                default               => null,
            };

            if ($dates) {
                $request->merge([
                    'date_from'  => $dates[0],
                    'date_to'    => $dates[1],
                    'quick_date' => $quickDate,
                ]);
                return;
            }
        }

        // If user provided custom date_from or date_to without explicit quick_date
        $hasFrom = $request->filled('date_from');
        $hasTo   = $request->filled('date_to');
        if ($hasFrom || $hasTo) {
            $parsedFrom = $this->parseDateInput($request->input('date_from'));
            $parsedTo   = $this->parseDateInput($request->input('date_to'));
            $request->merge([
                'date_from'  => $parsedFrom,
                'date_to'    => $parsedTo,
                'quick_date' => 'custom',
            ]);
            return;
        }

        // DEFAULT (Initial page load): apply default current month
        $request->merge([
            'date_from'  => $defaultFromDate,
            'date_to'    => $defaultToDate,
            'quick_date' => 'month',
        ]);
    }

    private function parseDateInput(?string $dateStr): ?string
    {
        if (empty($dateStr)) {
            return null;
        }

        $dateStr = trim($dateStr);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
            return $dateStr;
        }

        try {
            if (preg_match('/^(\d{1,2})[\/\.-](\d{1,2})[\/\.-](\d{4})$/', $dateStr, $matches)) {
                return Carbon::createFromDate((int)$matches[3], (int)$matches[2], (int)$matches[1])->toDateString();
            }

            return Carbon::parse($dateStr)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }
}