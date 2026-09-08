<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\App\Concerns\RestrictsEmployeesToOwnBranch;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\User;
use App\Models\Product;
use App\Services\DataVisibilityService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Throwable;

class CstAllocationApiController extends Controller
{
    use RestrictsEmployeesToOwnBranch;

    public function __construct(private readonly DataVisibilityService $visibility) {}

    private function isAdmin($user): bool
    {
        return $user->isSuperAdmin() || $user->isCompanyAdmin() || $user->hasAdminLikeRole();
    }

    /**
     * GET /mobile/cst-allocation?tab=pending|completed&branch_id=&product_id=&cst_user_id=&page=
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $currentUser = $request->user();
            $isAdmin = $this->isAdmin($currentUser);
            $tab = $request->get('tab', 'pending') === 'completed' ? 'completed' : 'pending';

            // 1. Query leads that have at least one converted product
            $leadsQuery = Lead::with([
                'products' => fn ($q) => $q->where('product_status', 'converted'),
                'customerSupportTl:id,name',
                'customerSupportExecutive:id,name',
                'assignedTo:id,name',
                'branch:id,name',
            ])
            ->whereHas('products', fn ($q) => $q->where('product_status', 'converted'))
            ->select('leads.*')
            ->selectSub(function ($q) {
                $q->from('lead_products')
                    ->whereColumn('lead_id', 'leads.id')
                    ->where('product_status', 'converted')
                    ->whereNull('deleted_at')
                    ->selectRaw('COALESCE(SUM(total_price), 0)');
            }, 'payment_total_price')
            ->selectSub(function ($q) {
                $q->from('lead_products')
                    ->whereColumn('lead_id', 'leads.id')
                    ->where('product_status', 'converted')
                    ->whereNull('deleted_at')
                    ->selectRaw('COALESCE(SUM(amount_paid), 0)');
            }, 'payment_amount_paid');

            // Company isolation — this query previously had no visibility
            // scoping applied at all, so it returned converted leads across
            // every company sharing the database. Deliberately using
            // applyCompanyVisibility() here, not applyLeadVisibility(): the
            // latter would also restrict CST-role users to only leads
            // already assigned to them (customer_support_tl_id /
            // customer_support_executive_id), which would break the TL
            // partition logic below (TLs must see every unassigned
            // "pending" lead in their company, not just their own). See
            // "Lead Status & Source – Company and Branch-wise Data
            // Filtering", section 3/4.
            $this->visibility->applyCompanyVisibility($leadsQuery, $currentUser);

            // 2. Filters — identical semantics to web
            if ($branchId = $request->get('branch_id')) {
                $leadsQuery->where('branch_id', $branchId);
            }
            if ($productId = $request->get('product_id')) {
                $leadsQuery->whereHas('products', function ($q) use ($productId) {
                    $q->where('product_status', 'converted')->where('product_id', $productId);
                });
            }
            if ($cstUserId = $request->get('cst_user_id')) {
                $leadsQuery->where(function ($q) use ($cstUserId) {
                    $q->where('customer_support_executive_id', $cstUserId)
                      ->orWhere('customer_support_tl_id', $cstUserId);
                });
            }
            if ($leadId = $request->get('lead_id')) {
                $leadsQuery->where('leads.id', $leadId);
            }
            if ($leadAccount = $request->get('lead_account')) {
                $leadsQuery->where(function ($q) use ($leadAccount) {
                    $q->where('company_name', 'like', "%{$leadAccount}%")
                      ->orWhere('contact_name', 'like', "%{$leadAccount}%");
                });
            }

            $eligibleLeads = $leadsQuery->get();

            $eligibleLeads->each(function ($lead) {
                $total = (float) $lead->payment_total_price;
                $paid = (float) $lead->payment_amount_paid;
                $lead->payment_progress_pct = round($total > 0 ? ($paid / $total) * 100 : 0, 1);
            });

            $isTl = $isAdmin || ($currentUser->hasCustomerSupportLikeRole() && $currentUser->hasTlLikeRole()) || $currentUser->hasTlLikeRole();

            // 3. Partition — identical rule to web
            if ($isTl) {
                $pending = $eligibleLeads->whereNull('customer_support_executive_id')->values();
                $completed = $eligibleLeads->whereNotNull('customer_support_executive_id')->values();
            } else {
                $pending = collect();
                $completed = $eligibleLeads->where('customer_support_executive_id', $currentUser->id)->values();
            }

            $counts = ['pending' => $pending->count(), 'completed' => $completed->count()];
            $active = $tab === 'completed' ? $completed : $pending;

            // 4. Paginate the selected tab only (mobile fetches one tab at
            // a time — no need to paginate both like the web's two visible
            // tables do simultaneously).
            $perPage = 15;
            $page = max(1, (int) $request->get('page', 1));
            $slice = $active->forPage($page, $perPage)->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'tab' => $tab,
                    'is_admin' => $isAdmin,
                    'can_allocate' => $isAdmin,
                    'counts' => $counts,
                    'leads' => $slice->map(fn ($lead) => $this->formatLead($lead))->all(),
                    'pagination' => [
                        'current_page' => $page,
                        'last_page' => (int) ceil($active->count() / $perPage) ?: 1,
                        'total' => $active->count(),
                        'per_page' => $perPage,
                    ],
                ],
            ]);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Unable to load CST allocation list. Please try again.',
            ], 500);
        }
    }

    /**
     * GET /mobile/cst-allocation/filters — branch/product/CST-user dropdown
     * data, exactly what the web filter bar populates from.
     */
    public function filters(Request $request): JsonResponse
    {
        try {
            $currentUser = $request->user();

            $cstUsersQuery = User::where('user_status', 'active')
                ->where(function ($query) {
                    $query->whereHas('roles.department', function ($q) {
                        $q->where('name', 'like', '%customer support%')
                          ->orWhere('name', 'like', '%customer success%')
                          ->orWhere('id', 5);
                    })->orWhereHas('employeeOnboarding', function ($q) {
                        $q->where('department_id', 5);
                    });
                });
            $cstUsers = $this->scopeEmployeeQueryToOwnBranch($cstUsersQuery, $currentUser)
                ->orderBy('name')->get(['id', 'name']);

            $branches = $this->visibility->visibleBranches($currentUser);

            $products = Product::query();
            $this->visibility->applyProductVisibility($products, $currentUser);
            $products = $products->select('id', 'product_name')->orderBy('product_name')->get();

            $leadAccounts = Lead::whereHas('products', fn ($q) => $q->where('product_status', 'converted'))
                ->select('id', 'company_name', 'contact_name')
                ->orderBy('company_name')
                ->get()
                ->map(fn ($l) => [
                    'id' => $l->id,
                    'name' => $l->company_name ?: ($l->contact_name ?: 'Lead #' . $l->id),
                ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'branches' => $branches,
                    'products' => $products,
                    'cst_users' => $cstUsers,
                    'lead_accounts' => $leadAccounts,
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

    /**
     * POST /mobile/cst-allocation/{lead}/allocate-tl
     * POST /mobile/cst-allocation/{lead}/allocate-executive
     * Both routes exist to mirror the web's two routes 1:1 — as on web,
     * they currently perform the identical update. If the web ever
     * diverges these two (e.g. TL-only vs executive-only assignment),
     * update both sides together.
     */
    public function allocateTl(Request $request, Lead $lead): JsonResponse
    {
        return $this->allocate($request, $lead);
    }

    public function allocateExecutive(Request $request, Lead $lead): JsonResponse
    {
        return $this->allocate($request, $lead);
    }

    private function allocate(Request $request, Lead $lead): JsonResponse
    {
        try {
            $currentUser = $request->user();

            if (! $this->isAdmin($currentUser)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to allocate CST users.',
                ], 403);
            }

            $validated = $request->validate([
                'cst_user_id' => 'required|exists:users,id',
            ]);

            $lead->update([
                'customer_support_tl_id' => $validated['cst_user_id'],
                'customer_support_executive_id' => $validated['cst_user_id'],
                'customer_support_allocated_at' => now(),
            ]);

            $user = User::find($validated['cst_user_id']);
            $lead->load(['customerSupportTl:id,name', 'customerSupportExecutive:id,name']);

            return response()->json([
                'success' => true,
                'message' => "Lead successfully allocated to {$user->name}.",
                'data' => $this->formatLead($lead),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Unable to allocate CST user. Please try again.',
            ], 500);
        }
    }

    private function formatLead(Lead $lead): array
    {
        return [
            'id' => $lead->id,
            'company_name' => $lead->company_name,
            'contact_name' => $lead->contact_name,
            'display_name' => $lead->company_name ?: ($lead->contact_name ?: 'N/A'),
            'branch_id' => $lead->branch_id,
            'branch_name' => $lead->branch?->name,
            'assigned_to' => $lead->assignedTo ? [
                'id' => $lead->assignedTo->id,
                'name' => $lead->assignedTo->name,
            ] : null,
            'products' => $lead->products->map(fn ($p) => [
                'id' => $p->id,
                'product_name' => $p->product_name,
            ])->values(),
            'products_count' => $lead->products->count(),
            'payment_total_price' => (float) $lead->payment_total_price,
            'payment_amount_paid' => (float) $lead->payment_amount_paid,
            'payment_progress_pct' => (float) $lead->payment_progress_pct,
            'customer_support_tl' => $lead->customerSupportTl ? [
                'id' => $lead->customerSupportTl->id,
                'name' => $lead->customerSupportTl->name,
            ] : null,
            'customer_support_executive' => $lead->customerSupportExecutive ? [
                'id' => $lead->customerSupportExecutive->id,
                'name' => $lead->customerSupportExecutive->name,
            ] : null,
            'customer_support_allocated_at' => $lead->customer_support_allocated_at?->format('Y-m-d H:i:s'),
        ];
    }
}