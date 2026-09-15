<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\CustomerCampaign;
use App\Models\Department;
use App\Models\Lead;
use App\Models\ProductionInitiation;
use App\Models\User;
use App\Mail\CampaignStoppedRefundNotificationMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CampaignApiController extends Controller
{
    /**
     * Check if user is authorized to access Paid Campaigns module.
     */
    private function authorizeCampaignAccess(User $user): void
    {
        abort_unless(
            $user->belongsToDigitalMarketingDepartment() || $user->hasAdminLikeRole(),
            403,
            'Unauthorized access to Paid Campaigns module.'
        );
    }

    /**
     * GET /mobile/projects/campaigns/meta
     * Returns filter dropdown options (employees) and campaign choice constants.
     */
    public function meta(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->authorizeCampaignAccess($user);

        $employees = User::query()
            ->where('is_active', true)
            ->when($user->company_id, fn ($q) => $q->where('company_id', $user->company_id))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $platforms = [
            'Meta / Facebook',
            'Google Ads',
            'Instagram',
            'LinkedIn',
            'YouTube',
            'Twitter / X',
            'Other',
        ];

        $budgetTypes = [
            'Monthly',
            'Daily',
            'Total',
            'Custom',
        ];

        $statuses = [
            'active'    => 'Active',
            'paused'    => 'Paused',
            'inactive'  => 'Inactive',
            'completed' => 'Completed',
            'expired'   => 'Expired',
            'stopped'   => 'Stopped',
        ];

        return response()->json([
            'status' => true,
            'data'   => [
                'employees'    => $employees,
                'platforms'    => $platforms,
                'budget_types' => $budgetTypes,
                'statuses'     => $statuses,
            ],
        ]);
    }

    /**
     * GET /mobile/projects/campaigns
     * Supports both Campaign-Wise View (view=campaign, default) and Account-Wise View (view=account)
     * with 6 KPI stats, search, renewal filters, and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->authorizeCampaignAccess($user);

        $viewMode = (string) $request->query('view', 'campaign');
        if (! in_array($viewMode, ['campaign', 'account'], true)) {
            $viewMode = 'campaign';
        }

        $search = trim((string) $request->query('search', $request->query('company_name', '')));
        $employeeId = $request->filled('employee_id') ? (int) $request->query('employee_id') : null;
        $status = trim((string) $request->query('status', ''));
        $renewalFilter = trim((string) $request->query('renewal_filter', ''));
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $cmStart = Carbon::now()->startOfMonth()->toDateString();
        $cmEnd   = Carbon::now()->endOfMonth()->toDateString();
        $nowDate = Carbon::now()->toDateString();

        // Extended parent campaign IDs (to detect renewals)
        $extendedParentIds = CustomerCampaign::query()
            ->when($user->company_id, fn ($q) => $q->where('company_id', $user->company_id))
            ->whereNotNull('extended_from_id')
            ->pluck('extended_from_id')
            ->filter()
            ->unique()
            ->toArray();

        // Overall stats calculated across company campaigns
        $allCampaignsForStats = CustomerCampaign::query()
            ->when($user->company_id, fn ($q) => $q->where('customer_campaigns.company_id', $user->company_id))
            ->get();

        // Digital Marketing department IDs for Leads calculation
        $dmDepartmentIds = Department::query()
            ->where(function ($q) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%digital%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%marketing%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%dm%']);
            })
            ->pluck('id')
            ->toArray();

        // Query ProductionInitiations for Digital Marketing products needing budget approval
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

        if (! $user->hasAdminLikeRole() && ! $user->hasTlLikeRole()) {
            $initiationsQuery->where(function ($q) use ($user) {
                $q->whereJsonContains('project_allocated_employee_user_ids', $user->id)
                    ->orWhereJsonContains('project_allocated_tl_user_ids', $user->id)
                    ->orWhere('initiated_by', $user->id);
            });
        }

        if ($search !== '') {
            $initiationsQuery->where(function ($q) use ($search) {
                $q->where('company_name', 'LIKE', "%{$search}%")
                    ->orWhere('client_name', 'LIKE', "%{$search}%")
                    ->orWhereHas('lead', function ($lq) use ($search) {
                        $lq->where('company_name', 'LIKE', "%{$search}%")
                            ->orWhere('contact_name', 'LIKE', "%{$search}%")
                            ->orWhere('mobile_number', 'LIKE', "%{$search}%")
                            ->orWhere('email', 'LIKE', "%{$search}%");
                    });
            });
        }

        if ($employeeId) {
            $initiationsQuery->where(function ($q) use ($employeeId) {
                $q->whereJsonContains('project_allocated_employee_user_ids', $employeeId)
                    ->orWhereJsonContains('project_allocated_tl_user_ids', $employeeId)
                    ->orWhere('initiated_by', $employeeId)
                    ->orWhere('tl_employee_allocations', 'LIKE', '%"' . $employeeId . '"%');
            });
        }

        $allInitiations = $initiationsQuery->latest()->get();
        $groupedByLead = $allInitiations->groupBy('lead_id');
        $leadIds = $groupedByLead->keys()->filter()->unique();

        $leads = Lead::query()
            ->with(['customerCampaigns'])
            ->whereIn('id', $leadIds)
            ->get()
            ->map(function (Lead $lead) use ($groupedByLead) {
                $initiations = $groupedByLead->get($lead->id, collect());

                $allAllocatedUserIds = collect();
                $tlUserIds = collect();
                $empUserIds = collect();

                foreach ($initiations as $initiation) {
                    $tls = Arr::wrap($initiation->project_allocated_tl_user_ids);
                    foreach ($tls as $tlId) {
                        if ((int) $tlId > 0) {
                            $tlUserIds->push((int) $tlId);
                            $allAllocatedUserIds->push((int) $tlId);
                        }
                    }

                    $emps = Arr::wrap($initiation->project_allocated_employee_user_ids);
                    foreach ($emps as $empId) {
                        if ((int) $empId > 0) {
                            $empUserIds->push((int) $empId);
                            $allAllocatedUserIds->push((int) $empId);
                        }
                    }

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

                $allocatedUsers = $allAllocatedUserIds->isNotEmpty()
                    ? User::whereIn('id', $allAllocatedUserIds)->get(['id', 'name', 'email'])
                    : collect();

                $tls = $allocatedUsers->whereIn('id', $tlUserIds)->values()->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])->all();
                $employees = $allocatedUsers->whereIn('id', $empUserIds)->values()->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])->all();

                $campaigns = $lead->customerCampaigns ?? collect();
                $totalCampaigns = $campaigns->count();
                $activeCampaigns = $campaigns->where('status', 'active')->count();

                $budgetAmounts = $initiations->pluck('lead_budget_amount')->filter(fn ($b) => (float) $b > 0);
                $latestBudget = $budgetAmounts->first() ? (float) $budgetAmounts->first() : null;
                $latestBudgetType = $initiations->pluck('budget_amount_type')->filter()->first() ?? 'Standard';

                $formattedInitiations = $initiations->map(function ($init) {
                    $prodName = $init->product?->name ?: ($init->leadProduct?->product?->name ?: 'Digital Marketing');
                    return [
                        'id'            => $init->id,
                        'product_name'  => $prodName,
                        'budget_amount' => (float) ($init->lead_budget_amount ?? 0),
                        'budget_type'   => $init->budget_amount_type ?? 'Standard',
                        'created_at'    => $init->created_at?->toIso8601String(),
                    ];
                })->all();

                return [
                    'id'                     => $lead->id,
                    'company_name'           => $lead->company_name ?: ($lead->contact_name ?: '—'),
                    'customer_name'          => $lead->contact_name ?: '—',
                    'mobile_number'          => $lead->mobile_number,
                    'email'                  => $lead->email,
                    'allocated_tls'          => $tls,
                    'allocated_employees'    => $employees,
                    'allocated_user_ids'     => $allAllocatedUserIds->all(),
                    'no_of_campaigns'        => $totalCampaigns,
                    'no_of_active_campaigns' => $activeCampaigns,
                    'lead_budget_amount'     => $latestBudget,
                    'budget_amount_type'     => $latestBudgetType,
                    'dm_initiations'         => $formattedInitiations,
                    'raw_campaigns'          => $campaigns,
                ];
            })
            ->sortBy('company_name')
            ->values();

        if ($employeeId) {
            $leads = $leads->filter(fn ($item) => in_array($employeeId, $item['allocated_user_ids'] ?? [], true))->values();
        }

        if ($status === 'active') {
            $leads = $leads->filter(fn ($item) => (int) $item['no_of_active_campaigns'] > 0)->values();
        } elseif ($status === 'inactive') {
            $leads = $leads->filter(fn ($item) => (int) $item['no_of_active_campaigns'] === 0)->values();
        }

        if ($startDate) {
            $parsedStart = Carbon::parse($startDate)->startOfDay();
            $leads = $leads->filter(function ($item) use ($parsedStart) {
                $camps = collect($item['raw_campaigns'] ?? []);
                $hasCampaign = $camps->contains(function ($c) use ($parsedStart) {
                    $cDate = $c->start_date ? Carbon::parse($c->start_date)->startOfDay() : ($c->created_at ? Carbon::parse($c->created_at)->startOfDay() : null);
                    return $cDate && $cDate->gte($parsedStart);
                });
                $inits = collect($item['dm_initiations'] ?? []);
                $hasInitiation = $inits->contains(function ($init) use ($parsedStart) {
                    return !empty($init['created_at']) && Carbon::parse($init['created_at'])->startOfDay()->gte($parsedStart);
                });
                return $hasCampaign || $hasInitiation;
            })->values();
        }

        if ($endDate) {
            $parsedEnd = Carbon::parse($endDate)->endOfDay();
            $leads = $leads->filter(function ($item) use ($parsedEnd) {
                $camps = collect($item['raw_campaigns'] ?? []);
                $hasCampaign = $camps->contains(function ($c) use ($parsedEnd) {
                    $cDate = $c->start_date ? Carbon::parse($c->start_date)->startOfDay() : ($c->created_at ? Carbon::parse($c->created_at)->startOfDay() : null);
                    return $cDate && $cDate->lte($parsedEnd);
                });
                $inits = collect($item['dm_initiations'] ?? []);
                $hasInitiation = $inits->contains(function ($init) use ($parsedEnd) {
                    return !empty($init['created_at']) && Carbon::parse($init['created_at'])->startOfDay()->lte($parsedEnd);
                });
                return $hasCampaign || $hasInitiation;
            })->values();
        }

        // Compute 6 KPI Stats matching web
        $stats = [
            'total_campaigns'        => $allCampaignsForStats->count(),
            'total_active_campaigns' => $allCampaignsForStats->filter(fn ($c) => $c->status === 'active' && ! $c->isExpired() && ! $c->isStopped())->count(),
            'total_cm_not_renewed'   => $allCampaignsForStats->filter(function ($c) use ($extendedParentIds, $cmStart, $cmEnd) {
                $endDate = $c->end_date ? Carbon::parse($c->end_date)->toDateString() : null;
                return $endDate && $endDate >= $cmStart && $endDate <= $cmEnd && ! in_array($c->id, $extendedParentIds, true);
            })->count(),
            'total_cm_renewed'       => $allCampaignsForStats->filter(function ($c) use ($extendedParentIds, $cmStart, $cmEnd) {
                $endDate = $c->end_date ? Carbon::parse($c->end_date)->toDateString() : null;
                $createdAt = Carbon::parse($c->created_at)->toDateString();
                return (in_array($c->id, $extendedParentIds, true) && $endDate && $endDate >= $cmStart && $endDate <= $cmEnd)
                    || (! empty($c->extended_from_id) && $createdAt >= $cmStart && $createdAt <= $cmEnd);
            })->count(),
            'total_expired_campaigns'=> $allCampaignsForStats->filter(fn ($c) => $c->isExpired())->count(),
            'total_leads'            => $leads->count(),
            'total_budget'           => (float) $leads->sum(fn ($l) => (float) ($l['lead_budget_amount'] ?? 0)),
        ];

        $perPage = (int) $request->query('per_page', 15);
        if ($perPage < 5 || $perPage > 100) {
            $perPage = 15;
        }
        $currentPage = max(1, (int) $request->query('page', 1));

        if ($viewMode === 'campaign') {
            // ── Campaign-Wise Query ──
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

            if ($search !== '') {
                $campaignsQuery->where(function ($q) use ($search) {
                    $q->where('customer_campaigns.campaign_name', 'LIKE', "%{$search}%")
                      ->orWhere('customer_campaigns.ad_account_name', 'LIKE', "%{$search}%")
                      ->orWhere('customer_campaigns.platform', 'LIKE', "%{$search}%")
                      ->orWhereHas('lead', function ($lq) use ($search) {
                            $lq->where('company_name', 'LIKE', "%{$search}%")
                               ->orWhere('contact_name', 'LIKE', "%{$search}%")
                               ->orWhere('mobile_number', 'LIKE', "%{$search}%")
                               ->orWhere('email', 'LIKE', "%{$search}%");
                      });
                });
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
                      ->orWhereHas('productionInitiation', function ($piq) use ($employeeId) {
                            $piq->whereJsonContains('project_allocated_employee_user_ids', $employeeId)
                                ->orWhereJsonContains('project_allocated_tl_user_ids', $employeeId)
                                ->orWhere('initiated_by', $employeeId)
                                ->orWhere('tl_employee_allocations', 'LIKE', '%"' . $employeeId . '"%');
                      })
                      ->orWhereHas('lead', function ($lq) use ($employeeId) {
                            $lq->where('assigned_to', $employeeId)
                               ->orWhere('customer_support_executive_id', $employeeId)
                               ->orWhere('customer_support_tl_id', $employeeId)
                               ->orWhereHas('productionInitiations', function ($piq) use ($employeeId) {
                                    $piq->whereJsonContains('project_allocated_employee_user_ids', $employeeId)
                                        ->orWhereJsonContains('project_allocated_tl_user_ids', $employeeId)
                                        ->orWhere('initiated_by', $employeeId)
                                        ->orWhere('tl_employee_allocations', 'LIKE', '%"' . $employeeId . '"%');
                               });
                      });
                });
            }

            // Renewal & Status Filters
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

            $totalCount = $campaignsQuery->count();
            $lastPage = max(1, (int) ceil($totalCount / $perPage));

            $paginatedCampaigns = $campaignsQuery
                ->orderByDesc('customer_campaigns.created_at')
                ->forPage($currentPage, $perPage)
                ->get();

            $formattedCampaigns = $paginatedCampaigns->map(function (CustomerCampaign $c) use ($extendedParentIds, $cmStart, $cmEnd) {
                $lead = $c->lead;
                $renewalStatus = 'Ongoing';
                $cEndDate = $c->end_date ? Carbon::parse($c->end_date)->toDateString() : null;
                $cCreatedAt = Carbon::parse($c->created_at)->toDateString();

                if ($c->isExpired()) {
                    $renewalStatus = 'Expired';
                } elseif (
                    (in_array($c->id, $extendedParentIds, true) && $cEndDate && $cEndDate >= $cmStart && $cEndDate <= $cmEnd)
                    || (! empty($c->extended_from_id) && $cCreatedAt >= $cmStart && $cCreatedAt <= $cmEnd)
                ) {
                    $renewalStatus = 'CM Renewed';
                } elseif ($cEndDate && $cEndDate >= $cmStart && $cEndDate <= $cmEnd && ! in_array($c->id, $extendedParentIds, true)) {
                    $renewalStatus = 'CM Not Renewed';
                }

                return [
                    'id'                 => (int) $c->id,
                    'lead_id'            => (int) $c->lead_id,
                    'lead_company_name'  => $lead?->company_name ?: ($lead?->contact_name ?: '—'),
                    'lead_customer_name' => $lead?->contact_name ?: '—',
                    'lead_mobile_number' => $lead?->mobile_number,
                    'lead_email'         => $lead?->email,
                    'campaign_name'      => $c->campaign_name,
                    'platform'           => $c->platform,
                    'ad_account_name'    => $c->ad_account_name,
                    'status'             => $c->status,
                    'is_expired'         => $c->isExpired(),
                    'is_stopped'         => $c->isStopped(),
                    'budget_amount'      => (float) ($c->budget_amount ?? 0),
                    'budget_type'        => $c->budget_type ?? 'Monthly',
                    'daily_budget'       => (float) ($c->daily_budget ?? 0),
                    'start_date'         => $c->start_date ? Carbon::parse($c->start_date)->toDateString() : null,
                    'end_date'           => $c->end_date ? Carbon::parse($c->end_date)->toDateString() : null,
                    'run_days'           => (int) ($c->run_days ?? 0),
                    'renewal_status'     => $renewalStatus,
                    'extended_from_id'   => $c->extended_from_id,
                    'extended_from_name' => $c->extendedFrom?->campaign_name,
                    'extensions_count'   => $c->extensions->count(),
                    'created_at'         => $c->created_at?->toIso8601String(),
                ];
            });

            return response()->json([
                'status' => true,
                'data'   => [
                    'view'       => 'campaign',
                    'campaigns'  => $formattedCampaigns,
                    'leads'      => [],
                    'stats'      => $stats,
                    'pagination' => [
                        'current_page' => $currentPage,
                        'per_page'     => $perPage,
                        'total'        => $totalCount,
                        'last_page'    => $lastPage,
                        'has_more'     => $currentPage < $lastPage,
                    ],
                ],
            ]);
        }

        // ── Account-Wise View (view=account) ──
        $cleanedLeads = $leads->map(function ($item) {
            unset($item['raw_campaigns'], $item['allocated_user_ids']);
            return $item;
        });

        $totalCount = $cleanedLeads->count();
        $lastPage = max(1, (int) ceil($totalCount / $perPage));
        $itemsForCurrentPage = $cleanedLeads->slice(($currentPage - 1) * $perPage, $perPage)->values();

        return response()->json([
            'status' => true,
            'data'   => [
                'view'       => 'account',
                'campaigns'  => [],
                'leads'      => $itemsForCurrentPage,
                'stats'      => $stats,
                'pagination' => [
                    'current_page' => $currentPage,
                    'per_page'     => $perPage,
                    'total'        => $totalCount,
                    'last_page'    => $lastPage,
                    'has_more'     => $currentPage < $lastPage,
                ],
            ],
        ]);
    }

    /**
     * GET /mobile/projects/campaigns/{lead}
     * Customer details, DM initiations, stats, and campaigns list.
     */
    public function show(Request $request, $lead): JsonResponse
    {
        $resolvedLead = $lead instanceof Lead && $lead->exists ? $lead : Lead::findOrFail($lead);
        $user = $request->user();
        $this->authorizeCampaignAccess($user);

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
        $tlUserIds = collect();
        $empUserIds = collect();

        foreach ($dmInitiations as $initiation) {
            foreach (Arr::wrap($initiation->project_allocated_tl_user_ids) as $tlId) {
                if ((int) $tlId > 0) {
                    $tlUserIds->push((int) $tlId);
                    $allAllocatedUserIds->push((int) $tlId);
                }
            }
            foreach (Arr::wrap($initiation->project_allocated_employee_user_ids) as $empId) {
                if ((int) $empId > 0) {
                    $empUserIds->push((int) $empId);
                    $allAllocatedUserIds->push((int) $empId);
                }
            }
        }

        $allocatedUsers = $allAllocatedUserIds->isNotEmpty()
            ? User::whereIn('id', $allAllocatedUserIds->unique())->get(['id', 'name', 'email'])
            : collect();

        $tls = $allocatedUsers->whereIn('id', $tlUserIds->unique())->values()->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])->all();
        $employees = $allocatedUsers->whereIn('id', $empUserIds->unique())->values()->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])->all();

        // Load all CustomerCampaigns for this Lead
        $allCampaigns = CustomerCampaign::query()
            ->with(['creator', 'updater', 'productionInitiation.product', 'extendedFrom', 'extensions'])
            ->where('lead_id', $resolvedLead->id)
            ->latest()
            ->get();

        $extendedParentIds = $allCampaigns->pluck('extended_from_id')->filter()->unique()->toArray();
        $campaigns = $allCampaigns->reject(fn ($c) => in_array($c->id, $extendedParentIds, true))->values();

        $stats = [
            'total'                => $campaigns->count(),
            'active'               => $campaigns->filter(fn ($c) => $c->status === 'active' && ! $c->isExpired() && ! $c->isStopped())->count(),
            'paused'               => $campaigns->where('status', 'paused')->count(),
            'expired'              => $campaigns->filter(fn ($c) => $c->isExpired() && ! $c->isStopped())->count(),
            'stopped'              => $campaigns->where('status', 'stopped')->count(),
            'all_historical_total' => $allCampaigns->count(),
        ];

        $formattedInitiations = $dmInitiations->map(function ($init) {
            $prodName = $init->product?->name ?: ($init->leadProduct?->product?->name ?: 'Digital Marketing');
            return [
                'id'            => $init->id,
                'product_name'  => $prodName,
                'budget_amount' => (float) ($init->lead_budget_amount ?? 0),
                'budget_type'   => $init->budget_amount_type ?? 'Standard',
            ];
        })->all();

        $formattedCampaigns = $campaigns->map(fn (CustomerCampaign $c) => $this->formatCampaignItem($c))->all();

        $budgetAmounts = $dmInitiations->pluck('lead_budget_amount')->filter(fn ($b) => (float) $b > 0);
        $leadBudget = $budgetAmounts->first() ? (float) $budgetAmounts->first() : null;
        $leadBudgetType = $dmInitiations->pluck('budget_amount_type')->filter()->first() ?? 'Standard';

        return response()->json([
            'status' => true,
            'data'   => [
                'lead' => [
                    'id'                 => $resolvedLead->id,
                    'company_name'       => $resolvedLead->company_name ?: ($resolvedLead->contact_name ?: '—'),
                    'customer_name'      => $resolvedLead->contact_name ?: '—',
                    'mobile_number'      => $resolvedLead->mobile_number,
                    'email'              => $resolvedLead->email,
                    'lead_budget_amount' => $leadBudget,
                    'budget_amount_type' => $leadBudgetType,
                    'allocated_tls'      => $tls,
                    'allocated_employees'=> $employees,
                ],
                'dm_initiations' => $formattedInitiations,
                'stats'          => $stats,
                'campaigns'      => $formattedCampaigns,
            ],
        ]);
    }

    /**
     * Format a single CustomerCampaign model into API JSON structure.
     */
    private function formatCampaignItem(CustomerCampaign $c): array
    {
        $prodName = $c->productionInitiation?->product?->name
            ?: ($c->productionInitiation?->leadProduct?->product?->name ?: null);

        return [
            'id'                       => $c->id,
            'lead_id'                  => $c->lead_id,
            'production_initiation_id' => $c->production_initiation_id,
            'product_name'             => $prodName,
            'campaign_name'            => $c->campaign_name,
            'ad_account_name'          => $c->ad_account_name,
            'platform'                 => $c->platform,
            'status'                   => $c->status,
            'is_expired'               => $c->isExpired(),
            'is_stopped'               => $c->isStopped(),
            'budget_amount'            => $c->budget_amount !== null ? (float) $c->budget_amount : null,
            'budget_type'              => $c->budget_type,
            'daily_budget'             => $c->calculateDailyBudget(),
            'start_date'               => $c->start_date ? $c->start_date->format('Y-m-d') : null,
            'end_date'                 => $c->end_date ? $c->end_date->format('Y-m-d') : null,
            'run_days'                 => $c->calculateRunDays(),
            'paused_at'                => $c->paused_at?->toIso8601String(),
            'total_paused_days'        => (int) ($c->total_paused_days ?? 0),
            'current_paused_days'      => $c->currentPausedDays(),
            'pause_history'            => $c->pause_history ?? [],
            'last_resumed_at'          => $c->last_resumed_at?->toIso8601String(),
            'stop_date'                => $c->stop_date ? $c->stop_date->format('Y-m-d') : null,
            'stopped_at'               => $c->stopped_at?->toIso8601String(),
            'refund_amount'            => $c->refund_amount !== null ? (float) $c->refund_amount : null,
            'stop_reason'              => $c->stop_reason,
            'extended_from_id'         => $c->extended_from_id,
            'extended_from_name'       => $c->extendedFrom?->campaign_name,
            'extensions_count'         => $c->extensions()->count(),
            'remarks'                  => $c->remarks,
            'created_by'               => $c->created_by,
            'creator_name'             => $c->creator?->name,
            'updated_by'               => $c->updated_by,
            'updater_name'             => $c->updater?->name,
            'created_at'               => $c->created_at?->toIso8601String(),
            'updated_at'               => $c->updated_at?->toIso8601String(),
        ];
    }

    /**
     * POST /mobile/projects/campaigns/{lead}
     * Store a newly created Customer Campaign.
     */
    public function store(Request $request, $lead): JsonResponse
    {
        $resolvedLead = $lead instanceof Lead && $lead->exists ? $lead : Lead::findOrFail($lead);
        $user = $request->user();
        $this->authorizeCampaignAccess($user);

        $validated = $request->validate([
            'campaign_name'            => ['required', 'string', 'max:255'],
            'ad_account_name'          => ['nullable', 'string', 'max:255'],
            'platform'                 => ['nullable', 'string', 'max:100'],
            'status'                   => ['required', 'string', 'in:active,paused,expired,completed,inactive'],
            'budget_amount'            => ['nullable', 'numeric', 'min:0'],
            'budget_type'              => ['nullable', 'string', 'max:50'],
            'start_date'               => ['nullable', 'date'],
            'end_date'                 => ['nullable', 'date', 'after_or_equal:start_date'],
            'remarks'                  => ['nullable', 'string', 'max:5000'],
            'production_initiation_id' => ['nullable', 'integer', 'exists:production_initiations,id'],
        ]);

        $status = $validated['status'];
        $pausedAt = $status === 'paused' ? Carbon::now() : null;

        $campaign = CustomerCampaign::create([
            'company_id'               => $resolvedLead->company_id ?: $user->company_id,
            'lead_id'                  => $resolvedLead->id,
            'production_initiation_id' => $validated['production_initiation_id'] ?? null,
            'campaign_name'            => $validated['campaign_name'],
            'ad_account_name'          => $validated['ad_account_name'] ?? null,
            'platform'                 => $validated['platform'] ?? null,
            'status'                   => $status,
            'budget_amount'            => $validated['budget_amount'] ?? null,
            'budget_type'              => $validated['budget_type'] ?? null,
            'start_date'               => $validated['start_date'] ?? null,
            'end_date'                 => $validated['end_date'] ?? null,
            'paused_at'                => $pausedAt,
            'total_paused_days'        => 0,
            'pause_history'            => [],
            'remarks'                  => $validated['remarks'] ?? null,
            'created_by'               => $user->id,
        ]);

        return response()->json([
            'status'   => true,
            'message'  => 'Campaign created successfully.',
            'campaign' => $this->formatCampaignItem($campaign->fresh()),
        ], 201);
    }

    /**
     * PUT /mobile/projects/campaigns/{campaign}
     * Update an existing Customer Campaign.
     */
    public function update(Request $request, $campaign): JsonResponse
    {
        $resolvedCampaign = $campaign instanceof CustomerCampaign && $campaign->exists ? $campaign : CustomerCampaign::findOrFail($campaign);
        $user = $request->user();
        $this->authorizeCampaignAccess($user);

        $validated = $request->validate([
            'campaign_name'            => ['required', 'string', 'max:255'],
            'ad_account_name'          => ['nullable', 'string', 'max:255'],
            'platform'                 => ['nullable', 'string', 'max:100'],
            'status'                   => ['required', 'string', 'in:active,paused,expired,completed,inactive'],
            'budget_amount'            => ['nullable', 'numeric', 'min:0'],
            'budget_type'              => ['nullable', 'string', 'max:50'],
            'start_date'               => ['nullable', 'date'],
            'end_date'                 => ['nullable', 'date', 'after_or_equal:start_date'],
            'remarks'                  => ['nullable', 'string', 'max:5000'],
            'production_initiation_id' => ['nullable', 'integer', 'exists:production_initiations,id'],
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
                'paused_at'    => $pausedAt ? $pausedAt->toDateTimeString() : null,
                'resumed_at'   => $now->toDateTimeString(),
                'paused_date'  => $pausedAt ? $pausedAt->format('d M Y') : '—',
                'resumed_date' => $now->format('d M Y'),
                'days'         => $diffDays,
            ];

            $updateData['last_resumed_at']   = $now;
            $updateData['total_paused_days'] = ((int) $resolvedCampaign->total_paused_days) + $diffDays;
            $updateData['pause_history']     = $history;
            $updateData['paused_at']         = null;
        }

        $resolvedCampaign->update($updateData);

        return response()->json([
            'status'   => true,
            'message'  => 'Campaign updated successfully.',
            'campaign' => $this->formatCampaignItem($resolvedCampaign->fresh()),
        ]);
    }

    /**
     * DELETE /mobile/projects/campaigns/{campaign}
     * Soft delete a campaign.
     */
    public function destroy(Request $request, $campaign): JsonResponse
    {
        $resolvedCampaign = $campaign instanceof CustomerCampaign && $campaign->exists ? $campaign : CustomerCampaign::findOrFail($campaign);
        $user = $request->user();
        $this->authorizeCampaignAccess($user);

        $resolvedCampaign->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Campaign deleted successfully.',
        ]);
    }

    /**
     * POST /mobile/projects/campaigns/{campaign}/pause
     * Pause campaign with date and remarks.
     */
    public function pause(Request $request, $campaign): JsonResponse
    {
        $resolvedCampaign = $campaign instanceof CustomerCampaign && $campaign->exists ? $campaign : CustomerCampaign::findOrFail($campaign);
        $user = $request->user();
        $this->authorizeCampaignAccess($user);

        $validated = $request->validate([
            'pause_date' => ['nullable', 'date'],
            'remarks'    => ['nullable', 'string', 'max:5000'],
        ]);

        $pauseDate = $validated['pause_date'] ? Carbon::parse($validated['pause_date']) : Carbon::now();

        $updateData = [
            'status'     => 'paused',
            'paused_at'  => $pauseDate,
            'updated_by' => $user->id,
        ];

        if (! empty($validated['remarks'])) {
            $updateData['remarks'] = $validated['remarks'];
        }

        $resolvedCampaign->update($updateData);
        $msg = 'Campaign paused on ' . $pauseDate->format('d M Y') . '.';

        return response()->json([
            'status'   => true,
            'message'  => $msg,
            'campaign' => $this->formatCampaignItem($resolvedCampaign->fresh()),
        ]);
    }

    /**
     * POST /mobile/projects/campaigns/{campaign}/resume
     * Resume campaign and calculate paused duration.
     */
    public function resume(Request $request, $campaign): JsonResponse
    {
        $resolvedCampaign = $campaign instanceof CustomerCampaign && $campaign->exists ? $campaign : CustomerCampaign::findOrFail($campaign);
        $user = $request->user();
        $this->authorizeCampaignAccess($user);

        $validated = $request->validate([
            'resume_date' => ['nullable', 'date'],
            'remarks'     => ['nullable', 'string', 'max:5000'],
        ]);

        $resumeDate = $validated['resume_date'] ? Carbon::parse($validated['resume_date']) : Carbon::now();
        $pausedAt = $resolvedCampaign->paused_at ?: ($resolvedCampaign->updated_at ?: Carbon::now());

        $diffDays = max(1, (int) $pausedAt->diffInDays($resumeDate));

        $history = $resolvedCampaign->pause_history ?? [];
        $history[] = [
            'paused_at'    => $pausedAt->toDateTimeString(),
            'resumed_at'   => $resumeDate->toDateTimeString(),
            'paused_date'  => $pausedAt->format('d M Y'),
            'resumed_date' => $resumeDate->format('d M Y'),
            'days'         => $diffDays,
        ];

        $totalDays = ((int) $resolvedCampaign->total_paused_days) + $diffDays;

        $updateData = [
            'status'            => 'active',
            'last_resumed_at'   => $resumeDate,
            'total_paused_days' => $totalDays,
            'pause_history'     => $history,
            'paused_at'         => null,
            'updated_by'        => $user->id,
        ];

        if (! empty($validated['remarks'])) {
            $updateData['remarks'] = $validated['remarks'];
        }

        $resolvedCampaign->update($updateData);
        $msg = "Campaign activated! Was paused for {$diffDays} " . ($diffDays === 1 ? 'day' : 'days') . " (from " . $pausedAt->format('d M Y') . " to " . $resumeDate->format('d M Y') . ").";

        return response()->json([
            'status'   => true,
            'message'  => $msg,
            'campaign' => $this->formatCampaignItem($resolvedCampaign->fresh()),
        ]);
    }

    /**
     * POST /mobile/projects/campaigns/{campaign}/extend
     * Extend/renew campaign as a new row linked via extended_from_id.
     */
    public function extend(Request $request, $campaign): JsonResponse
    {
        $resolvedCampaign = $campaign instanceof CustomerCampaign && $campaign->exists ? $campaign : CustomerCampaign::findOrFail($campaign);
        $user = $request->user();
        $this->authorizeCampaignAccess($user);

        $validated = $request->validate([
            'campaign_name'   => ['required', 'string', 'max:255'],
            'ad_account_name' => ['nullable', 'string', 'max:255'],
            'platform'        => ['nullable', 'string', 'max:100'],
            'status'          => ['required', 'string', 'in:active,paused,expired,inactive'],
            'budget_amount'   => ['nullable', 'numeric', 'min:0'],
            'budget_type'     => ['nullable', 'string', 'max:50'],
            'start_date'      => ['nullable', 'date'],
            'end_date'        => ['nullable', 'date', 'after_or_equal:start_date'],
            'remarks'         => ['nullable', 'string', 'max:5000'],
        ]);

        $status = $validated['status'];
        $pausedAt = $status === 'paused' ? Carbon::now() : null;

        $newCampaign = CustomerCampaign::create([
            'company_id'               => $resolvedCampaign->company_id ?: $user->company_id,
            'lead_id'                  => $resolvedCampaign->lead_id,
            'production_initiation_id' => $resolvedCampaign->production_initiation_id,
            'extended_from_id'         => $resolvedCampaign->id,
            'campaign_name'            => $validated['campaign_name'],
            'ad_account_name'          => $validated['ad_account_name'] ?? $resolvedCampaign->ad_account_name,
            'platform'                 => $validated['platform'] ?? $resolvedCampaign->platform,
            'status'                   => $status,
            'budget_amount'            => $validated['budget_amount'] ?? null,
            'budget_type'              => $validated['budget_type'] ?? 'Monthly',
            'start_date'               => $validated['start_date'] ?? null,
            'end_date'                 => $validated['end_date'] ?? null,
            'paused_at'                => $pausedAt,
            'total_paused_days'        => 0,
            'pause_history'            => [],
            'remarks'                  => $validated['remarks'] ?? null,
            'created_by'               => $user->id,
        ]);

        return response()->json([
            'status'   => true,
            'message'  => "Campaign extended/renewed successfully from {$resolvedCampaign->campaign_name}.",
            'campaign' => $this->formatCampaignItem($newCampaign->fresh()),
        ], 201);
    }

    /**
     * POST /mobile/projects/campaigns/{campaign}/stop
     * Stop campaign, record stop date/refund/reason, and notify CBO via email.
     */
    public function stop(Request $request, $campaign): JsonResponse
    {
        $resolvedCampaign = $campaign instanceof CustomerCampaign && $campaign->exists ? $campaign : CustomerCampaign::findOrFail($campaign);
        $user = $request->user();
        $this->authorizeCampaignAccess($user);

        $validated = $request->validate([
            'stop_date'     => ['required', 'date'],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
            'stop_reason'   => ['nullable', 'string', 'max:5000'],
        ]);

        $stopDate = Carbon::parse($validated['stop_date']);

        $resolvedCampaign->update([
            'status'        => 'stopped',
            'stop_date'     => $stopDate,
            'stopped_at'    => $stopDate,
            'refund_amount' => $validated['refund_amount'] ?? null,
            'stop_reason'   => $validated['stop_reason'] ?? null,
            'updated_by'    => $user->id,
        ]);

        // Send email notification to Chief Business Officer / Company Admin (mirrors web stop() logic)
        try {
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
                        } else {
                            $q->whereRaw('1 = 0');
                        }
                    })
                    ->get();

                $recipients = $fallbackUsers->pluck('email')->filter()->values()->all();
            }

            if (!empty($recipients)) {
                $lead = $resolvedCampaign->lead;
                $runDays = $resolvedCampaign->calculateRunDays();
                $totalDays = $resolvedCampaign->start_date && $resolvedCampaign->end_date
                    ? Carbon::parse($resolvedCampaign->start_date)->diffInDays(Carbon::parse($resolvedCampaign->end_date)) + 1
                    : 0;

                Mail::to($recipients)->send(new CampaignStoppedRefundNotificationMail(
                    campaign: $resolvedCampaign,
                    lead: $lead,
                    stoppedByUser: $user,
                    runDays: $runDays,
                    totalDays: $totalDays,
                    refundAmount: $validated['refund_amount'] ?? null,
                    stopReason: $validated['stop_reason'] ?? null
                ));
            }
        } catch (\Throwable $e) {
            Log::error('Failed to send Campaign Stopped email notification on mobile: ' . $e->getMessage());
        }

        return response()->json([
            'status'   => true,
            'message'  => 'Campaign stopped successfully.',
            'campaign' => $this->formatCampaignItem($resolvedCampaign->fresh()),
        ]);
    }
}
