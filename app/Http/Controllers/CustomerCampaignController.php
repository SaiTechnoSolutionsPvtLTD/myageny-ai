<?php

namespace App\Http\Controllers;

use App\Models\CustomerCampaign;
use App\Models\Department;
use App\Models\Lead;
use App\Models\ProductionInitiation;
use App\Models\User;
use App\Mail\CampaignStoppedRefundNotificationMail;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Services\DataVisibilityService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CustomerCampaignController extends Controller
{
    public function __construct(
        protected DataVisibilityService $visibility
    ) {}
    /**
     * Display a Lead-wise list of Digital Marketing products moved to production
     * where product budget approval is needed (is_budget_approval_needed = true).
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        abort_unless(
            $user->belongsToDigitalMarketingDepartment() || $user->hasAdminLikeRole() || $user->canAccessProjectsModule(),
            403,
            'Unauthorized access to Campaigns module.'
        );

        // Fetch Digital Marketing department IDs
        $dmDepartmentIds = Department::query()
            ->where(function ($q) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%digital%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%marketing%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%dm%']);
            })
            ->pluck('id')
            ->toArray();

        // Filters
        $companyName = trim((string) $request->query('company_name', $request->query('search', '')));
        $employeeId = $request->filled('employee_id') ? (int) $request->query('employee_id') : null;
        $status = trim((string) $request->query('status', ''));
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        // Query ProductionInitiations for Digital Marketing products with budget approval needed
        $initiationsQuery = ProductionInitiation::query()
            ->with([
                'lead',
                'product',
                'leadProduct.product',
                'department',
            ])
            ->where(function ($q) use ($dmDepartmentIds) {
                $q->whereIn('department_id', $dmDepartmentIds)
                    ->orWhereHas('department', function ($dq) {
                        $dq->whereRaw('LOWER(name) LIKE ?', ['%digital%'])
                            ->orWhereRaw('LOWER(name) LIKE ?', ['%marketing%'])
                            ->orWhereRaw('LOWER(name) LIKE ?', ['%dm%']);
                    });
            })
            ->where(function ($q) {
                $q->whereHas('product', function ($pq) {
                    $pq->where('is_budget_approval_needed', true);
                })->orWhereHas('leadProduct.product', function ($pq) {
                    $pq->where('is_budget_approval_needed', true);
                });
            });

        // Filter by assigned user if standard executive
        if (! $user->hasAdminLikeRole() && ! $user->hasTlLikeRole()) {
            $initiationsQuery->where(function ($q) use ($user) {
                $q->whereJsonContains('project_allocated_employee_user_ids', $user->id)
                    ->orWhereJsonContains('project_allocated_tl_user_ids', $user->id)
                    ->orWhere('initiated_by', $user->id);
            });
        }

        // Apply Company Name search filter
        if ($companyName !== '') {
            $initiationsQuery->where(function ($q) use ($companyName) {
                $q->where('company_name', 'LIKE', "%{$companyName}%")
                    ->orWhere('client_name', 'LIKE', "%{$companyName}%")
                    ->orWhereHas('lead', function ($lq) use ($companyName) {
                        $lq->where('company_name', 'LIKE', "%{$companyName}%")
                            ->orWhere('contact_name', 'LIKE', "%{$companyName}%")
                            ->orWhere('mobile_number', 'LIKE', "%{$companyName}%")
                            ->orWhere('email', 'LIKE', "%{$companyName}%");
                    });
            });
        }

        // Apply Employee filter on initiations
        if ($employeeId) {
            $initiationsQuery->where(function ($q) use ($employeeId) {
                $q->whereJsonContains('project_allocated_employee_user_ids', $employeeId)
                    ->orWhereJsonContains('project_allocated_tl_user_ids', $employeeId)
                    ->orWhere('initiated_by', $employeeId)
                    ->orWhere('tl_employee_allocations', 'LIKE', '%"' . $employeeId . '"%');
            });
        }

        $allInitiations = $initiationsQuery->latest()->get();

        // Group by Lead ID
        $groupedByLead = $allInitiations->groupBy('lead_id');
        $leadIds = $groupedByLead->keys()->filter()->unique();

        $leads = Lead::query()
            ->with(['customerCampaigns'])
            ->whereIn('id', $leadIds)
            ->get()
            ->map(function (Lead $lead) use ($groupedByLead) {
                $initiations = $groupedByLead->get($lead->id, collect());

                // Extract all allocated team members across all DM initiations for this lead
                $allAllocatedUserIds = collect();
                $tlUserIds = collect();
                $empUserIds = collect();

                foreach ($initiations as $initiation) {
                    // TLs
                    $tls = Arr::wrap($initiation->project_allocated_tl_user_ids);
                    foreach ($tls as $tlId) {
                        if ((int) $tlId > 0) {
                            $tlUserIds->push((int) $tlId);
                            $allAllocatedUserIds->push((int) $tlId);
                        }
                    }

                    // Direct Employees
                    $emps = Arr::wrap($initiation->project_allocated_employee_user_ids);
                    foreach ($emps as $empId) {
                        if ((int) $empId > 0) {
                            $empUserIds->push((int) $empId);
                            $allAllocatedUserIds->push((int) $empId);
                        }
                    }

                    // tl_employee_allocations JSON breakdown
                    if (! empty($initiation->tl_employee_allocations)) {
                        $allocs = is_array($initiation->tl_employee_allocations)
                            ? $initiation->tl_employee_allocations
                            : (json_decode((string) $initiation->tl_employee_allocations, true) ?? []);

                        foreach ($allocs as $tlKey => $alloc) {
                            if (is_numeric($tlKey) && (int) $tlKey > 0) {
                                $tlUserIds->push((int) $tlKey);
                                $allAllocatedUserIds->push((int) $tlKey);
                            }
                            if (is_array($alloc) && ! empty($alloc['employee_user_ids'])) {
                                foreach ($alloc['employee_user_ids'] as $eId) {
                                    if ((int) $eId > 0) {
                                        $empUserIds->push((int) $eId);
                                        $allAllocatedUserIds->push((int) $eId);
                                    }
                                }
                            }
                        }
                    }
                }

                $tlUserIds = $tlUserIds->unique()->values();
                $empUserIds = $empUserIds->unique()->values();
                $allAllocatedUserIds = $allAllocatedUserIds->unique()->values();

                // Load user objects
                $allocatedUsers = $allAllocatedUserIds->isNotEmpty()
                    ? User::whereIn('id', $allAllocatedUserIds)->get(['id', 'name', 'email'])
                    : collect();

                $tls = $allocatedUsers->whereIn('id', $tlUserIds)->values();
                $employees = $allocatedUsers->whereIn('id', $empUserIds)->values();

                // Calculate campaigns breakdown
                $campaigns = $lead->customerCampaigns ?? collect();
                $totalCampaigns = $campaigns->count();

                $activeCampaigns = $campaigns->filter(function ($c) {
                    return $c->status === 'active' && ! $c->isExpired() && ! $c->isStopped();
                })->count();

                $pausedCampaigns = $campaigns->where('status', 'paused')->count();

                $expiredCampaigns = $campaigns->filter(function ($c) {
                    return $c->status === 'expired' || $c->isExpired();
                })->count();

                $inactiveCampaigns = $campaigns->filter(function ($c) {
                    return in_array($c->status, ['inactive', 'stopped'], true) || (! $c->isExpired() && $c->status !== 'active' && $c->status !== 'paused');
                })->count();

                // Budget information
                $budgetAmounts = $initiations->pluck('lead_budget_amount')->filter(fn ($b) => (float) $b > 0);
                $latestBudget = $budgetAmounts->first() ?? null;
                $latestBudgetType = $initiations->pluck('budget_amount_type')->filter()->first() ?? 'Standard';

                $lead->dm_initiations = $initiations;
                $lead->allocated_tls = $tls;
                $lead->allocated_employees = $employees;
                $lead->allocated_users = $allocatedUsers;
                $lead->no_of_campaigns = $totalCampaigns;
                $lead->no_of_active_campaigns = $activeCampaigns;
                $lead->no_of_paused_campaigns = $pausedCampaigns;
                $lead->no_of_expired_campaigns = $expiredCampaigns;
                $lead->no_of_inactive_campaigns = $inactiveCampaigns;
                $lead->lead_budget_amount = $latestBudget;
                $lead->budget_amount_type = $latestBudgetType;

                return $lead;
            })
            ->sortBy('company_name')
            ->values();

        $nowDate = Carbon::today()->toDateString();
        $cmStart = Carbon::today()->startOfMonth()->toDateString();
        $cmEnd = Carbon::today()->endOfMonth()->toDateString();
        $extendedParentIds = CustomerCampaign::whereNotNull('extended_from_id')->pluck('extended_from_id')->unique()->toArray();

        $viewMode = $request->query('view', 'campaign'); // default to 'campaign'
        $renewalFilter = $request->query('renewal_filter') ?: $request->query('type');
        if (in_array($status, ['cm_renewed', 'cm_not_renewed'], true)) {
            $renewalFilter = $status;
        }

        // Filter by Employee if specified
        if ($employeeId) {
            $leads = $leads->filter(function ($lead) use ($employeeId) {
                return $lead->allocated_users->pluck('id')->contains($employeeId);
            })->values();
        }

        // Filter by Branch if specified
        $branchId = $request->query('branch_id');
        if ($branchId) {
            $leads = $leads->filter(fn ($lead) => (int) ($lead->branch_id ?? 0) === (int) $branchId)->values();
        }

        // Filter by Source if specified
        $source = $request->query('source') ?: $request->query('lead_source');
        if ($source) {
            $leads = $leads->filter(fn ($lead) => ($lead->lead_source ?? '') === $source)->values();
        }

        // Filter by Status & Renewal Filters (Active, Paused, Inactive, Expired, CM Renewed, CM Not Renewed)
        if ($renewalFilter === 'cm_not_renewed') {
            $leads = $leads->filter(function ($lead) use ($extendedParentIds, $cmStart, $cmEnd) {
                return $lead->customerCampaigns->contains(function ($c) use ($extendedParentIds, $cmStart, $cmEnd) {
                    $endDate = $c->end_date ? Carbon::parse($c->end_date)->toDateString() : null;
                    return $endDate && $endDate >= $cmStart && $endDate <= $cmEnd && !in_array($c->id, $extendedParentIds, true);
                });
            })->values();
        } elseif ($renewalFilter === 'cm_renewed') {
            $leads = $leads->filter(function ($lead) use ($extendedParentIds, $cmStart, $cmEnd) {
                return $lead->customerCampaigns->contains(function ($c) use ($extendedParentIds, $cmStart, $cmEnd) {
                    $endDate = $c->end_date ? Carbon::parse($c->end_date)->toDateString() : null;
                    $createdAt = Carbon::parse($c->created_at)->toDateString();
                    return (in_array($c->id, $extendedParentIds, true) && $endDate && $endDate >= $cmStart && $endDate <= $cmEnd)
                        || (!empty($c->extended_from_id) && $createdAt >= $cmStart && $createdAt <= $cmEnd);
                });
            })->values();
        } elseif ($renewalFilter === 'expired' || $status === 'expired') {
            $leads = $leads->filter(fn ($lead) => (int) $lead->no_of_expired_campaigns > 0)->values();
        } elseif ($status === 'active') {
            $leads = $leads->filter(fn ($lead) => (int) $lead->no_of_active_campaigns > 0)->values();
        } elseif ($status === 'paused') {
            $leads = $leads->filter(fn ($lead) => (int) $lead->no_of_paused_campaigns > 0)->values();
        } elseif ($status === 'inactive') {
            $leads = $leads->filter(fn ($lead) => (int) $lead->no_of_inactive_campaigns > 0 || (int) $lead->no_of_campaigns === 0)->values();
        }

        // Filter by Date Range (Start Date / End Date)
        if ($startDate) {
            $parsedStart = Carbon::parse($startDate)->startOfDay();
            $leads = $leads->filter(function ($lead) use ($parsedStart) {
                // Check if any campaign has start_date or created_at >= startDate, or initiation created_at >= startDate
                $hasCampaign = $lead->customerCampaigns->contains(function ($c) use ($parsedStart) {
                    $cDate = $c->start_date ? Carbon::parse($c->start_date)->startOfDay() : ($c->created_at ? Carbon::parse($c->created_at)->startOfDay() : null);
                    return $cDate && $cDate->gte($parsedStart);
                });
                $hasInitiation = $lead->dm_initiations->contains(function ($init) use ($parsedStart) {
                    return $init->created_at && Carbon::parse($init->created_at)->startOfDay()->gte($parsedStart);
                });
                return $hasCampaign || $hasInitiation;
            })->values();
        }

        if ($endDate) {
            $parsedEnd = Carbon::parse($endDate)->endOfDay();
            $leads = $leads->filter(function ($lead) use ($parsedEnd) {
                // Check if any campaign has start_date, end_date, or created_at <= endDate, or initiation created_at <= endDate
                $hasCampaign = $lead->customerCampaigns->contains(function ($c) use ($parsedEnd) {
                    $cDate = $c->start_date ? Carbon::parse($c->start_date)->startOfDay() : ($c->created_at ? Carbon::parse($c->created_at)->startOfDay() : null);
                    return $cDate && $cDate->lte($parsedEnd);
                });
                $hasInitiation = $lead->dm_initiations->contains(function ($init) use ($parsedEnd) {
                    return $init->created_at && Carbon::parse($init->created_at)->startOfDay()->lte($parsedEnd);
                });
                return $hasCampaign || $hasInitiation;
            })->values();
        }

        // Overall statistics based on filtered leads
        $stats = [
            'total_leads' => $leads->count(),
            'total_campaigns' => $leads->sum('no_of_campaigns'),
            'total_active_campaigns' => $leads->sum('no_of_active_campaigns'),
            'total_paused_campaigns' => $leads->sum('no_of_paused_campaigns'),
            'total_expired_campaigns' => $leads->sum('no_of_expired_campaigns'),
            'total_inactive_campaigns' => $leads->sum('no_of_inactive_campaigns'),
            'total_budget' => $leads->sum(fn ($l) => (float) ($l->lead_budget_amount ?? 0)),
        ];

        // Fetch Employees for Filter Dropdown
        $employees = User::query()
            ->where('is_active', true)
            ->when($user->company_id, fn ($q) => $q->where('company_id', $user->company_id))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $branches = $this->visibility->visibleBranches($user);

        // ── Campaign-wise Query ──
        $campaignsQuery = CustomerCampaign::query()
            ->with(['lead', 'extensions:id,extended_from_id', 'extendedFrom:id,campaign_name'])
            ->when($user->company_id, fn ($q) => $q->where('customer_campaigns.company_id', $user->company_id));

        if (! $user->hasAdminLikeRole() && ! $user->hasTlLikeRole()) {
            $campaignsQuery->where(function ($q) use ($user) {
                $q->where('customer_campaigns.created_by', $user->id)
                  ->orWhereHas('lead', fn ($lq) => $lq->where('assigned_to', $user->id)
                        ->orWhere('customer_support_executive_id', $user->id)
                        ->orWhere('customer_support_tl_id', $user->id)
                  )->orWhereHas('productionInitiation', function ($piq) use ($user) {
                        $piq->whereJsonContains('project_allocated_employee_user_ids', $user->id)
                            ->orWhereJsonContains('project_allocated_tl_user_ids', $user->id)
                            ->orWhere('initiated_by', $user->id);
                  });
            });
        }

        if ($companyName !== '') {
            $campaignsQuery->where(function ($q) use ($companyName) {
                $q->where('customer_campaigns.campaign_name', 'LIKE', "%{$companyName}%")
                  ->orWhere('customer_campaigns.ad_account_name', 'LIKE', "%{$companyName}%")
                  ->orWhere('customer_campaigns.platform', 'LIKE', "%{$companyName}%")
                  ->orWhereHas('lead', function ($lq) use ($companyName) {
                      $lq->where('company_name', 'LIKE', "%{$companyName}%")
                         ->orWhere('contact_name', 'LIKE', "%{$companyName}%")
                         ->orWhere('mobile_number', 'LIKE', "%{$companyName}%")
                         ->orWhere('email', 'LIKE', "%{$companyName}%");
                  });
            });
        }

        if ($branchId) {
            $campaignsQuery->whereHas('lead', fn ($lq) => $lq->where('branch_id', $branchId));
        }

        if ($source) {
            $campaignsQuery->whereHas('lead', fn ($lq) => $lq->where('lead_source', $source));
        }

        if ($startDate) {
            $campaignsQuery->whereDate('customer_campaigns.start_date', '>=', $startDate);
        }

        if ($endDate) {
            $campaignsQuery->whereDate('customer_campaigns.end_date', '<=', $endDate);
        }

        if ($employeeId) {
            $campaignsQuery->where(function ($q) use ($employeeId) {
                $q->where('customer_campaigns.created_by', $employeeId)
                  ->orWhereHas('lead', fn ($lq) => $lq->where('assigned_to', $employeeId)
                        ->orWhere('customer_support_executive_id', $employeeId)
                        ->orWhere('customer_support_tl_id', $employeeId)
                  );
            });
        }

        // Apply Renewal & Status Filters
        if ($renewalFilter === 'cm_not_renewed') {
            $campaignsQuery->whereBetween('customer_campaigns.end_date', [$cmStart, $cmEnd])
                ->whereNotIn('customer_campaigns.id', $extendedParentIds);
        } elseif ($renewalFilter === 'cm_renewed') {
            $campaignsQuery->where(function ($q) use ($extendedParentIds, $cmStart, $cmEnd) {
                $q->where(function ($sub) use ($extendedParentIds, $cmStart, $cmEnd) {
                    $sub->whereIn('customer_campaigns.id', $extendedParentIds)->whereBetween('customer_campaigns.end_date', [$cmStart, $cmEnd]);
                })->orWhere(function ($sub) use ($cmStart, $cmEnd) {
                    $sub->whereNotNull('customer_campaigns.extended_from_id')->whereBetween('customer_campaigns.created_at', [$cmStart, $cmEnd]);
                });
            });
        } elseif ($renewalFilter === 'expired' || $status === 'expired') {
            $campaignsQuery->where(function ($q) use ($nowDate) {
                $q->whereIn('customer_campaigns.status', ['expired', 'completed'])
                  ->orWhereDate('customer_campaigns.end_date', '<', $nowDate);
            });
        } elseif ($status === 'active') {
            $campaignsQuery->where('customer_campaigns.status', 'active')
                ->where(fn ($q) => $q->whereNull('customer_campaigns.end_date')->orWhereDate('customer_campaigns.end_date', '>=', $nowDate));
        } elseif ($status === 'paused') {
            $campaignsQuery->where('customer_campaigns.status', 'paused');
        } elseif ($status === 'inactive') {
            $campaignsQuery->whereIn('customer_campaigns.status', ['inactive', 'stopped']);
        }

        // Overall stats calculated on campaigns
        $allCampaignsForStats = CustomerCampaign::query()
            ->when($user->company_id, fn ($q) => $q->where('customer_campaigns.company_id', $user->company_id))
            ->get();

        $stats['total_campaigns'] = $allCampaignsForStats->count();
        $stats['total_active_campaigns'] = $allCampaignsForStats->filter(fn ($c) => $c->status === 'active' && !$c->isExpired() && !$c->isStopped())->count();
        $stats['total_paused_campaigns'] = $allCampaignsForStats->where('status', 'paused')->count();
        $stats['total_expired_campaigns'] = $allCampaignsForStats->filter(fn ($c) => $c->isExpired())->count();
        $stats['total_cm_renewed'] = $allCampaignsForStats->filter(function ($c) use ($extendedParentIds, $cmStart, $cmEnd) {
            $endDate = $c->end_date ? Carbon::parse($c->end_date)->toDateString() : null;
            $createdAt = Carbon::parse($c->created_at)->toDateString();
            return (in_array($c->id, $extendedParentIds, true) && $endDate && $endDate >= $cmStart && $endDate <= $cmEnd)
                || (!empty($c->extended_from_id) && $createdAt >= $cmStart && $createdAt <= $cmEnd);
        })->count();
        $stats['total_cm_not_renewed'] = $allCampaignsForStats->filter(function ($c) use ($extendedParentIds, $cmStart, $cmEnd) {
            $endDate = $c->end_date ? Carbon::parse($c->end_date)->toDateString() : null;
            return $endDate && $endDate >= $cmStart && $endDate <= $cmEnd && !in_array($c->id, $extendedParentIds, true);
        })->count();

        $filters = [
            'company_name' => $companyName,
            'employee_id' => $employeeId,
            'branch_id' => $branchId,
            'source' => $source,
            'status' => $status,
            'renewal_filter' => $renewalFilter,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'view' => $viewMode,
        ];

        $hasActiveFilters = !empty($companyName) || !empty($employeeId) || !empty($branchId) || !empty($source) || !empty($status) || !empty($renewalFilter) || !empty($startDate) || !empty($endDate);

        // Pagination
        $perPage = (int) $request->query('per_page', 10);
        if ($perPage < 5 || $perPage > 100) {
            $perPage = 10;
        }

        // Paginate Campaigns (for campaign-wise view)
        $paginatedCampaigns = $campaignsQuery->orderByDesc('customer_campaigns.created_at')->paginate($perPage)->withQueryString();

        // Paginate Leads (for account-wise view)
        $currentPage = LengthAwarePaginator::resolveCurrentPage() ?: 1;
        $itemsForCurrentPage = $leads->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $paginatedLeads = new LengthAwarePaginator(
            $itemsForCurrentPage,
            $leads->count(),
            $perPage,
            $currentPage,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'query' => $request->query(),
            ]
        );

        return view('pages.projects.campaigns.index', [
            'campaigns' => $paginatedCampaigns,
            'leads' => $paginatedLeads,
            'stats' => $stats,
            'search' => $companyName,
            'employees' => $employees,
            'branches' => $branches,
            'filters' => $filters,
            'hasActiveFilters' => $hasActiveFilters,
            'viewMode' => $viewMode,
            'renewalFilter' => $renewalFilter,
            'extendedParentIds' => $extendedParentIds,
            'cmStart' => $cmStart,
            'cmEnd' => $cmEnd,
            'nowDate' => $nowDate,
        ]);
    }

    /**
     * Show campaigns details for a specific Lead/Customer.
     */
    public function show($lead): View
    {
        $resolvedLead = $lead instanceof Lead && $lead->exists ? $lead : Lead::findOrFail($lead);
        $user = auth()->user();
        abort_unless(
            $user->belongsToDigitalMarketingDepartment() || $user->hasAdminLikeRole() || $user->canAccessProjectsModule(),
            403,
            'Unauthorized access to Campaigns module.'
        );

        // Fetch Digital Marketing department IDs
        $dmDepartmentIds = Department::query()
            ->where(function ($q) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%digital%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%marketing%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%dm%']);
            })
            ->pluck('id')
            ->toArray();

        // Load DM Production Initiations for this Lead
        $dmInitiations = ProductionInitiation::query()
            ->with(['product', 'leadProduct.product', 'department'])
            ->where('lead_id', $resolvedLead->id)
            ->where(function ($q) use ($dmDepartmentIds) {
                $q->whereIn('department_id', $dmDepartmentIds)
                    ->orWhereHas('department', function ($dq) {
                        $dq->whereRaw('LOWER(name) LIKE ?', ['%digital%'])
                            ->orWhereRaw('LOWER(name) LIKE ?', ['%marketing%'])
                            ->orWhereRaw('LOWER(name) LIKE ?', ['%dm%']);
                    });
            })
            ->where(function ($q) {
                $q->whereHas('product', function ($pq) {
                    $pq->where('is_budget_approval_needed', true);
                })->orWhereHas('leadProduct.product', function ($pq) {
                    $pq->where('is_budget_approval_needed', true);
                });
            })
            ->latest()
            ->get();

        // Extract allocated team members
        $allAllocatedUserIds = collect();
        foreach ($dmInitiations as $initiation) {
            foreach (Arr::wrap($initiation->project_allocated_tl_user_ids) as $tlId) {
                if ((int) $tlId > 0) $allAllocatedUserIds->push((int) $tlId);
            }
            foreach (Arr::wrap($initiation->project_allocated_employee_user_ids) as $empId) {
                if ((int) $empId > 0) $allAllocatedUserIds->push((int) $empId);
            }
        }
        $allocatedUsers = $allAllocatedUserIds->isNotEmpty()
            ? User::whereIn('id', $allAllocatedUserIds->unique())->get(['id', 'name', 'email'])
            : collect();

        // Load all CustomerCampaigns for this Lead
        $allCampaigns = CustomerCampaign::query()
            ->with(['creator', 'updater', 'productionInitiation', 'extendedFrom', 'extensions'])
            ->where('lead_id', $resolvedLead->id)
            ->latest()
            ->get();

        // Find IDs of campaigns that have already been extended into a newer campaign
        $extendedParentIds = $allCampaigns->pluck('extended_from_id')->filter()->unique()->toArray();

        // Show only the latest/current active campaign of each chain in the table (1 entry per campaign lineage)
        $campaigns = $allCampaigns->reject(fn ($c) => in_array($c->id, $extendedParentIds, true))->values();

        $stats = [
            'total' => $campaigns->count(),
            'active' => $campaigns->filter(fn ($c) => $c->status === 'active' && ! $c->isExpired() && ! $c->isStopped())->count(),
            'paused' => $campaigns->where('status', 'paused')->count(),
            'expired' => $campaigns->filter(fn ($c) => $c->isExpired() && ! $c->isStopped())->count(),
            'stopped' => $campaigns->where('status', 'stopped')->count(),
            'all_historical_total' => $allCampaigns->count(),
        ];

        return view('pages.projects.campaigns.show', [
            'lead' => $resolvedLead,
            'dmInitiations' => $dmInitiations,
            'allocatedUsers' => $allocatedUsers,
            'campaigns' => $campaigns,
            'allCampaigns' => $allCampaigns,
            'stats' => $stats,
        ]);
    }

    /**
     * Store a newly created Customer Campaign in storage.
     */
    public function store(Request $request, $lead): RedirectResponse|JsonResponse
    {
        $resolvedLead = $lead instanceof Lead && $lead->exists ? $lead : Lead::findOrFail($lead);
        $user = auth()->user();
        abort_unless(
            $user->belongsToDigitalMarketingDepartment() || $user->hasAdminLikeRole() || $user->canAccessProjectsModule(),
            403
        );

        $validated = $request->validate([
            'campaign_name' => ['required', 'string', 'max:255'],
            'ad_account_name' => ['nullable', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'string', 'in:active,paused,expired,completed,inactive'],
            'budget_amount' => ['nullable', 'numeric', 'min:0'],
            'budget_type' => ['nullable', 'string', 'max:50'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'remarks' => ['nullable', 'string', 'max:5000'],
            'production_initiation_id' => ['nullable', 'integer', 'exists:production_initiations,id'],
        ]);

        $status = $validated['status'];
        $pausedAt = $status === 'paused' ? Carbon::now() : null;

        $campaign = CustomerCampaign::create([
            'company_id' => $resolvedLead->company_id ?: $user->company_id,
            'lead_id' => $resolvedLead->id,
            'production_initiation_id' => $validated['production_initiation_id'] ?? null,
            'campaign_name' => $validated['campaign_name'],
            'ad_account_name' => $validated['ad_account_name'] ?? null,
            'platform' => $validated['platform'] ?? null,
            'status' => $status,
            'budget_amount' => $validated['budget_amount'] ?? null,
            'budget_type' => $validated['budget_type'] ?? null,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'paused_at' => $pausedAt,
            'total_paused_days' => 0,
            'pause_history' => [],
            'remarks' => $validated['remarks'] ?? null,
            'created_by' => $user->id,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Campaign created successfully.',
                'campaign' => $campaign,
            ]);
        }

        return redirect()->route('projects.campaigns.show', $resolvedLead->id)
            ->with('success', 'Campaign created successfully.');
    }

    /**
     * Update the specified Customer Campaign in storage.
     */
    public function update(Request $request, $campaign): RedirectResponse|JsonResponse
    {
        $resolvedCampaign = $campaign instanceof CustomerCampaign && $campaign->exists ? $campaign : CustomerCampaign::findOrFail($campaign);
        $user = auth()->user();
        abort_unless(
            $user->belongsToDigitalMarketingDepartment() || $user->hasAdminLikeRole() || $user->canAccessProjectsModule(),
            403
        );

        $validated = $request->validate([
            'campaign_name' => ['required', 'string', 'max:255'],
            'ad_account_name' => ['nullable', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'string', 'in:active,paused,expired,completed,inactive'],
            'budget_amount' => ['nullable', 'numeric', 'min:0'],
            'budget_type' => ['nullable', 'string', 'max:50'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'remarks' => ['nullable', 'string', 'max:5000'],
        ]);

        $newStatus = $validated['status'];
        $oldStatus = $resolvedCampaign->status;
        $now = Carbon::now();

        $updateData = array_merge($validated, [
            'updated_by' => $user->id,
        ]);

        if ($newStatus === 'paused' && $oldStatus !== 'paused') {
            $updateData['paused_at'] = $now;
        } elseif ($newStatus === 'active' && $oldStatus === 'paused') {
            $pausedAt = $resolvedCampaign->paused_at ?: $resolvedCampaign->updated_at;
            $diffDays = $pausedAt ? max(1, (int) $pausedAt->diffInDays($now)) : 1;

            $history = $resolvedCampaign->pause_history ?? [];
            $history[] = [
                'paused_at' => $pausedAt ? $pausedAt->toDateTimeString() : null,
                'resumed_at' => $now->toDateTimeString(),
                'paused_date' => $pausedAt ? $pausedAt->format('d M Y') : '—',
                'resumed_date' => $now->format('d M Y'),
                'days' => $diffDays,
            ];

            $updateData['last_resumed_at'] = $now;
            $updateData['total_paused_days'] = ((int) $resolvedCampaign->total_paused_days) + $diffDays;
            $updateData['pause_history'] = $history;
            $updateData['paused_at'] = null;
        }

        $resolvedCampaign->update($updateData);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Campaign updated successfully.',
                'campaign' => $resolvedCampaign,
            ]);
        }

        return redirect()->route('projects.campaigns.show', $resolvedCampaign->lead_id)
            ->with('success', 'Campaign updated successfully.');
    }

    /**
     * Remove the specified Customer Campaign from storage.
     */
    public function destroy($campaign): RedirectResponse|JsonResponse
    {
        $resolvedCampaign = $campaign instanceof CustomerCampaign && $campaign->exists ? $campaign : CustomerCampaign::findOrFail($campaign);
        $user = auth()->user();
        abort_unless(
            $user->belongsToDigitalMarketingDepartment() || $user->hasAdminLikeRole() || $user->canAccessProjectsModule(),
            403
        );

        $leadId = $resolvedCampaign->lead_id;
        $resolvedCampaign->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Campaign deleted successfully.',
            ]);
        }

        return redirect()->route('projects.campaigns.show', $leadId)
            ->with('success', 'Campaign deleted successfully.');
    }

    /**
     * Pause a Customer Campaign with explicit date selection and confirmation.
     */
    public function pause(Request $request, $campaign): RedirectResponse|JsonResponse
    {
        $resolvedCampaign = $campaign instanceof CustomerCampaign && $campaign->exists ? $campaign : CustomerCampaign::findOrFail($campaign);
        $user = auth()->user();
        abort_unless(
            $user->belongsToDigitalMarketingDepartment() || $user->hasAdminLikeRole() || $user->canAccessProjectsModule(),
            403
        );

        $validated = $request->validate([
            'pause_date' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string', 'max:5000'],
        ]);

        $pauseDate = $validated['pause_date'] ? Carbon::parse($validated['pause_date']) : Carbon::now();

        $updateData = [
            'status' => 'paused',
            'paused_at' => $pauseDate,
            'updated_by' => $user->id,
        ];

        if (!empty($validated['remarks'])) {
            $updateData['remarks'] = $validated['remarks'];
        }

        $resolvedCampaign->update($updateData);

        $message = 'Campaign paused on ' . $pauseDate->format('d M Y') . '.';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'campaign' => $resolvedCampaign->fresh(),
            ]);
        }

        return redirect()->route('projects.campaigns.show', $resolvedCampaign->lead_id)
            ->with('success', $message);
    }

    /**
     * Resume / Activate a Customer Campaign with explicit date selection and duration calculation.
     */
    public function resume(Request $request, $campaign): RedirectResponse|JsonResponse
    {
        $resolvedCampaign = $campaign instanceof CustomerCampaign && $campaign->exists ? $campaign : CustomerCampaign::findOrFail($campaign);
        $user = auth()->user();
        abort_unless(
            $user->belongsToDigitalMarketingDepartment() || $user->hasAdminLikeRole() || $user->canAccessProjectsModule(),
            403
        );

        $validated = $request->validate([
            'resume_date' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string', 'max:5000'],
        ]);

        $resumeDate = $validated['resume_date'] ? Carbon::parse($validated['resume_date']) : Carbon::now();
        $pausedAt = $resolvedCampaign->paused_at ?: ($resolvedCampaign->updated_at ?: Carbon::now());

        $diffDays = max(1, (int) $pausedAt->diffInDays($resumeDate));

        $history = $resolvedCampaign->pause_history ?? [];
        $history[] = [
            'paused_at' => $pausedAt->toDateTimeString(),
            'resumed_at' => $resumeDate->toDateTimeString(),
            'paused_date' => $pausedAt->format('d M Y'),
            'resumed_date' => $resumeDate->format('d M Y'),
            'days' => $diffDays,
        ];

        $totalDays = ((int) $resolvedCampaign->total_paused_days) + $diffDays;

        $updateData = [
            'status' => 'active',
            'last_resumed_at' => $resumeDate,
            'total_paused_days' => $totalDays,
            'pause_history' => $history,
            'paused_at' => null,
            'updated_by' => $user->id,
        ];

        if (!empty($validated['remarks'])) {
            $updateData['remarks'] = $validated['remarks'];
        }

        $resolvedCampaign->update($updateData);

        $message = "Campaign activated! Was paused for {$diffDays} " . ($diffDays === 1 ? 'day' : 'days') . " (from " . $pausedAt->format('d M Y') . " to " . $resumeDate->format('d M Y') . ").";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'campaign' => $resolvedCampaign->fresh(),
            ]);
        }

        return redirect()->route('projects.campaigns.show', $resolvedCampaign->lead_id)
            ->with('success', $message);
    }

    /**
     * Extend / Renew an existing campaign as a new row linked to the parent campaign.
     */
    public function extend(Request $request, $campaign): RedirectResponse|JsonResponse
    {
        $resolvedCampaign = $campaign instanceof CustomerCampaign && $campaign->exists ? $campaign : CustomerCampaign::findOrFail($campaign);
        $user = auth()->user();
        abort_unless(
            $user->belongsToDigitalMarketingDepartment() || $user->hasAdminLikeRole() || $user->canAccessProjectsModule(),
            403
        );

        $validated = $request->validate([
            'campaign_name' => ['required', 'string', 'max:255'],
            'ad_account_name' => ['nullable', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'string', 'in:active,paused,expired,inactive'],
            'budget_amount' => ['nullable', 'numeric', 'min:0'],
            'budget_type' => ['nullable', 'string', 'max:50'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'remarks' => ['nullable', 'string', 'max:5000'],
        ]);

        $status = $validated['status'];
        $pausedAt = $status === 'paused' ? Carbon::now() : null;

        $newCampaign = CustomerCampaign::create([
            'company_id' => $resolvedCampaign->company_id ?: $user->company_id,
            'lead_id' => $resolvedCampaign->lead_id,
            'production_initiation_id' => $resolvedCampaign->production_initiation_id,
            'extended_from_id' => $resolvedCampaign->id,
            'campaign_name' => $validated['campaign_name'],
            'ad_account_name' => $validated['ad_account_name'] ?? $resolvedCampaign->ad_account_name,
            'platform' => $validated['platform'] ?? $resolvedCampaign->platform,
            'status' => $status,
            'budget_amount' => $validated['budget_amount'] ?? null,
            'budget_type' => $validated['budget_type'] ?? 'Monthly',
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'paused_at' => $pausedAt,
            'total_paused_days' => 0,
            'pause_history' => [],
            'remarks' => $validated['remarks'] ?? null,
            'created_by' => $user->id,
        ]);

        $message = "Campaign extended/renewed successfully from {$resolvedCampaign->campaign_name}.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'campaign' => $newCampaign,
            ]);
        }

        return redirect()->route('projects.campaigns.show', $resolvedCampaign->lead_id)
            ->with('success', $message);
    }

    /**
     * Stop campaign and send refund / run metrics email to Chief Business Officer.
     */
    public function stop(Request $request, $campaign): RedirectResponse|JsonResponse
    {
        $resolvedCampaign = $campaign instanceof CustomerCampaign && $campaign->exists ? $campaign : CustomerCampaign::findOrFail($campaign);
        $user = auth()->user();
        abort_unless(
            $user->belongsToDigitalMarketingDepartment() || $user->hasAdminLikeRole() || $user->canAccessProjectsModule(),
            403
        );

        $validated = $request->validate([
            'stop_date' => ['required', 'date'],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
            'stop_reason' => ['nullable', 'string', 'max:5000'],
        ]);

        $stopDate = Carbon::parse($validated['stop_date']);

        $resolvedCampaign->update([
            'status' => 'stopped',
            'stop_date' => $stopDate,
            'stopped_at' => $stopDate,
            'refund_amount' => $validated['refund_amount'] ?? null,
            'stop_reason' => $validated['stop_reason'] ?? null,
            'updated_by' => $user->id,
        ]);

        // Send Email Notification to Chief Business Officer
        $companyId = $resolvedCampaign->company_id ?: $user->company_id;
        $hasDesignationCol = \Illuminate\Support\Facades\Schema::hasColumn('users', 'designation');

        $cboRoleIds = DB::table('roles')
            ->where(function ($rq) {
                $rq->whereRaw('LOWER(name) LIKE ?', ['%chief_business_officer%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%business_officer%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%cbo%']);
            })
            ->pluck('id')
            ->toArray();

        $cboUserIds = !empty($cboRoleIds)
            ? DB::table('model_has_roles')->whereIn('role_id', $cboRoleIds)->pluck('model_id')->toArray()
            : [];

        $cboUsers = User::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where(function ($q) use ($cboUserIds, $hasDesignationCol) {
                if (!empty($cboUserIds)) {
                    $q->whereIn('id', $cboUserIds);
                } else {
                    $q->whereRaw('1 = 0');
                }

                if ($hasDesignationCol) {
                    $q->orWhereRaw('LOWER(designation) LIKE ?', ['%chief business officer%'])
                      ->orWhereRaw('LOWER(designation) LIKE ?', ['%cbo%']);
                }
            })
            ->where('is_active', true)
            ->get();

        $recipients = $cboUsers->pluck('email')->filter()->values()->all();

        if (empty($recipients)) {
            // Fallback to Company Admin
            $adminRoleIds = DB::table('roles')
                ->where(function ($rq) {
                    $rq->whereRaw('LOWER(name) LIKE ?', ['%company_admin%'])
                        ->orWhereRaw('LOWER(name) LIKE ?', ['%admin%']);
                })
                ->pluck('id')
                ->toArray();

            $adminUserIds = !empty($adminRoleIds)
                ? DB::table('model_has_roles')->whereIn('role_id', $adminRoleIds)->pluck('model_id')->toArray()
                : [];

            $fallbackUsers = User::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->where('is_active', true)
                ->where(function ($q) use ($adminUserIds) {
                    if (!empty($adminUserIds)) {
                        $q->whereIn('id', $adminUserIds);
                    }
                })
                ->get();
            $recipients = $fallbackUsers->pluck('email')->filter()->values()->all();
        }

        if (empty($recipients) && $user->email) {
            $recipients = [$user->email];
        }

        if (!empty($recipients)) {
            try {
                Mail::to($recipients)->send(new CampaignStoppedRefundNotificationMail(
                    $resolvedCampaign->fresh(['lead']),
                    $user
                ));
            } catch (\Throwable $e) {
                Log::error('Failed to send Campaign Stopped Refund Notification: ' . $e->getMessage());
            }
        }

        $runDays = $resolvedCampaign->calculateRunDays();
        $message = "Campaign stopped successfully (Total run: {$runDays} days). Notification email sent to Chief Business Officer.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'campaign' => $resolvedCampaign->fresh(),
            ]);
        }

        return redirect()->route('projects.campaigns.show', $resolvedCampaign->lead_id)
            ->with('success', $message);
    }
}

