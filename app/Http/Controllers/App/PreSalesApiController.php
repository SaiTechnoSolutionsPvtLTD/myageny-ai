<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Lead;
use App\Models\User;
use App\Services\DataVisibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Mobile API — Pre-Sales Workspace.
 *
 * Mirrors web's PreSalesController exactly (same visibility rule, same
 * filters, same allocate() semantics) — see app/Http/Controllers/
 * PreSalesController.php. Nothing here re-derives business logic; it's the
 * same Lead query/update shape, just returned as JSON instead of a view.
 */
class PreSalesApiController extends Controller
{
    public function __construct(private readonly DataVisibilityService $visibility) {}

    // ── GET /mobile/pre-sales ────────────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $query = Lead::with(['branch', 'assignedTo', 'preSaleExecutive', 'createdBy'])
                ->latest('lead_date');

            // Company isolation — web's PreSalesController relies on request-
            // level middleware/global scoping for this; that guarantee isn't
            // available here, so it's applied explicitly. Without it, an
            // admin/company-wide user would see every company's pre-sales
            // leads instead of just their own. See "Lead Status & Source –
            // Company and Branch-wise Data Filtering", section 3/4.
            $this->visibility->applyCompanyVisibility($query, $user);

            // Identical scoping rule to web: admins see every lead that has
            // a pre-sales executive set, everyone else only sees leads
            // that are theirs (as pre-sales executive OR as assignee).
            if ($user && !$user->isSystemAdmin() && !$user->isCompanyAdmin() && !$user->isBranchAdmin()) {
                $query->where(function ($q) use ($user) {
                    $q->where('pre_sale_executive_id', $user->id)
                      ->orWhere('assigned_to', $user->id);
                });
            } else {
                $query->whereNotNull('pre_sale_executive_id');
            }

            if ($request->filled('search')) {
                $s = $request->search;
                $query->where(function ($q) use ($s) {
                    $q->where('company_name', 'like', "%{$s}%")
                      ->orWhere('contact_name', 'like', "%{$s}%")
                      ->orWhere('mobile_number', 'like', "%{$s}%")
                      ->orWhere('email', 'like', "%{$s}%");
                });
            }

            if ($request->filled('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            }

            if ($request->filled('priority')) {
                $query->where('priority', $request->priority);
            }

            $totalCount = (clone $query)->count();

            $perPage = min((int) ($request->per_page ?? 20), 50);
            $leads = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'leads' => $leads->map(fn ($lead) => $this->formatLead($lead)),
                    'total_count' => $totalCount,
                    'pagination' => [
                        'current_page' => $leads->currentPage(),
                        'last_page' => $leads->lastPage(),
                        'per_page' => $leads->perPage(),
                        'total' => $leads->total(),
                        'has_more' => $leads->hasMorePages(),
                    ],
                ],
            ]);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Unable to load Pre-Sales leads. Please try again.',
            ], 500);
        }
    }

    // ── GET /mobile/pre-sales/filters ────────────────────────────────────────
    // Branch list + assignable Sales Executives — exactly what web's index()
    // passes to the view for the allocate modal's dropdown ($salesExecutives)
    // and what a branch filter control would use ($branches).
    public function filters(Request $request): JsonResponse
    {
        try {
            $salesExecutives = $this->visibility->visibleAssignableUsers($request->user());
            $branches = Branch::where('is_active', true)
                ->when($request->user()?->company_id, fn($q, $companyId) => $q->where('company_id', $companyId))
                ->orderBy('name')
                ->get(['id', 'name']);

            return response()->json([
                'success' => true,
                'data' => [
                    'sales_executives' => $salesExecutives->map(fn ($u) => [
                        'id' => $u->id,
                        'name' => $u->name,
                        'email' => $u->email,
                    ])->values(),
                    'branches' => $branches->map(fn ($b) => [
                        'id' => $b->id,
                        'name' => $b->name,
                    ])->values(),
                    'priorities' => collect(Lead::PRIORITIES)->map(fn ($label, $key) => [
                        'key' => $key,
                        'label' => $label,
                    ])->values(),
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

    // ── POST /mobile/pre-sales/allocate ──────────────────────────────────────
    // Identical to web's allocate(): bulk-move lead(s) to a Sales Executive.
    public function allocate(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'lead_ids' => 'required|array|min:1',
                'lead_ids.*' => 'exists:leads,id',
                'sales_person_id' => 'required|exists:users,id',
            ]);

            $companyId = $this->visibility->companyIdFor($request->user());

            $salesPersonQuery = User::query()->whereKey($validated['sales_person_id']);
            if ($companyId) {
                $salesPersonQuery->where('company_id', $companyId);
            }
            $salesPerson = $salesPersonQuery->first();
            abort_unless($salesPerson, 403, 'Selected user does not belong to your company.');

            // `exists:leads,id` above only checks existence across every
            // company — re-derive lead_ids against this user's own visible
            // leads so an id from another company can't be allocated here.
            // See "Lead Status & Source – Company and Branch-wise Data
            // Filtering", section 4.
            $leadIdsQuery = Lead::query()->whereIn('id', $validated['lead_ids']);
            $this->visibility->applyCompanyVisibility($leadIdsQuery, $request->user());
            $leadIds = $leadIdsQuery->pluck('id')->all();
            abort_if(empty($leadIds), 403, 'None of the selected leads belong to your company.');

            Lead::whereIn('id', $leadIds)->update([
                'assigned_to' => $salesPerson->id,
                'pre_sale_executive_id' => $request->user()->id,
                'updated_at' => now(),
            ]);

            $count = count($leadIds);

            return response()->json([
                'success' => true,
                'message' => "Successfully moved / allocated {$count} lead(s) to {$salesPerson->name}.",
            ]);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Unable to allocate lead(s). Please try again.',
            ], 500);
        }
    }

    private function formatLead(Lead $lead): array
    {
        return [
            'id' => $lead->id,
            'lead_ref' => 'LD-' . str_pad((string) $lead->id, 4, '0', STR_PAD_LEFT),
            'company_name' => $lead->company_name,
            'contact_name' => $lead->contact_name,
            'mobile_number' => $lead->mobile_number,
            'email' => $lead->email,
            'priority' => $lead->priority,
            'priority_label' => $lead->priority ? (Lead::PRIORITIES[$lead->priority] ?? ucfirst($lead->priority)) : null,
            'branch_id' => $lead->branch_id,
            'branch_name' => $lead->branch?->name,
            'assigned_to' => $lead->assignedTo ? [
                'id' => $lead->assignedTo->id,
                'name' => $lead->assignedTo->name,
            ] : null,
            'pre_sale_executive' => $lead->preSaleExecutive ? [
                'id' => $lead->preSaleExecutive->id,
                'name' => $lead->preSaleExecutive->name,
            ] : null,
            'lead_date' => $lead->lead_date?->format('Y-m-d'),
        ];
    }
}
