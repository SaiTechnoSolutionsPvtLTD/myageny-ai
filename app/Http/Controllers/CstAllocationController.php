<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\User;
use App\Models\Product;
use App\Services\DataVisibilityService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class CstAllocationController extends Controller
{
    public function __construct(private readonly DataVisibilityService $visibility) {}

    /**
     * Helper to paginate a Collection in Laravel.
     */
    private static function paginateCollection($collection, $perPage, $pageName)
    {
        $page = request()->get($pageName, 1);
        return new \Illuminate\Pagination\LengthAwarePaginator(
            $collection->forPage($page, $perPage)->values(),
            $collection->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
                'pageName' => $pageName
            ]
        );
    }

    /**
     * Display the CST Allocation page.
     */
    public function index(Request $request): View
    {
        $currentUser = $request->user();

        // 1. Fetch leads that have at least one converted product
        $leads = Lead::with([
            'products' => function($q) {
                $q->where('product_status', '=', 'converted');
            },
            'customerSupportTl',
            'customerSupportExecutive'
        ])->get();

        // 2. Filter in PHP: leads with overall payment progress >= 40%
        $eligibleLeads = $leads->filter(function($lead) {
            if ($lead->products->isEmpty()) {
                return false;
            }
            
            $totalPrice = $lead->products->sum('total_price');
            if ($totalPrice <= 0) return false;
            
            $totalPaid = $lead->products->sum('amount_paid');
            $progress = ($totalPaid / $totalPrice) * 100;
            
            $lead->payment_progress_pct = round($progress, 1);
            $lead->payment_total_price = $totalPrice;
            $lead->payment_amount_paid = $totalPaid;
            
            return $progress >= 40;
        });

        // 3. Apply Filters (Branch, Product, TL)
        $fBranch = $request->get('branch_id');
        $fProduct = $request->get('product_id');
        $fTl = $request->get('tl_id');

        if ($fBranch) {
            $eligibleLeads = $eligibleLeads->where('branch_id', $fBranch);
        }
        if ($fProduct) {
            $eligibleLeads = $eligibleLeads->filter(function($lead) use ($fProduct) {
                return $lead->products->contains('product_id', $fProduct);
            });
        }
        if ($fTl) {
            $eligibleLeads = $eligibleLeads->where('customer_support_tl_id', $fTl);
        }

        // 4. Partition based on assignment state and role visibility
        $isSupportTl = $currentUser->hasCustomerSupportLikeRole() && $currentUser->hasTlLikeRole();
        $isSupportExec = $currentUser->hasCustomerSupportLikeRole() && !$currentUser->hasTlLikeRole();
        $isAdmin = $currentUser->isSuperAdmin() || $currentUser->isCompanyAdmin() || $currentUser->hasAdminLikeRole();

        // 4. Partition based on assignment state and role visibility
        $isSupportTl = $currentUser->hasCustomerSupportLikeRole() && $currentUser->hasTlLikeRole();
        $isSupportExec = $currentUser->hasCustomerSupportLikeRole() && !$currentUser->hasTlLikeRole();
        $isAdmin = $currentUser->isSuperAdmin() || $currentUser->isCompanyAdmin() || $currentUser->hasAdminLikeRole();

        if ($isSupportTl && !$isAdmin) {
            // For Support TL:
            // - Pending: Lead allocated to this TL, but no executive is assigned yet.
            // - Completed: Lead allocated to this TL, and an executive is assigned.
            $pendingLeads = $eligibleLeads
                ->where('customer_support_tl_id', $currentUser->id)
                ->whereNull('customer_support_executive_id');

            $completedLeads = $eligibleLeads
                ->where('customer_support_tl_id', $currentUser->id)
                ->whereNotNull('customer_support_executive_id');
        } elseif ($isSupportExec && !$isAdmin) {
            // For Support Executive:
            // - Pending: none (they do not allocate).
            // - Completed: Lead allocated to them.
            $pendingLeads = collect();
            $completedLeads = $eligibleLeads->where('customer_support_executive_id', $currentUser->id);
        } else {
            // For Admins:
            // - Pending: No Support TL assigned.
            // - Completed: Support TL assigned.
            $pendingLeads = $eligibleLeads->whereNull('customer_support_tl_id');
            $completedLeads = $eligibleLeads->whereNotNull('customer_support_tl_id');
        }

        // Paginate both collections separately (so pagination links don't conflict)
        $pendingLeadsPaginated = self::paginateCollection($pendingLeads, 15, 'page_pending');
        $completedLeadsPaginated = self::paginateCollection($completedLeads, 15, 'page_completed');

        // 5. Retrieve active support TLs and Executives for assignment dropdowns
        $supportTls = User::where('user_status', '=', 'active')
            ->where(function($query) {
                $query->whereHas('roles.department', function($q) {
                    $q->where('name', 'like', '%customer support%')
                      ->orWhere('name', 'like', '%customer success%')
                      ->orWhere('id', 5);
                })->orWhereHas('employeeOnboarding', function($q) {
                    $q->where('department_id', 5);
                });
            })->where(function($query) {
                $query->whereHas('roles', function($q) {
                    $q->where('name', 'like', '%tl%')
                      ->orWhere('name', 'like', '%leader%')
                      ->orWhere('name', 'like', '%manager%');
                })->orWhere('designation', 'like', '%TL%')
                  ->orWhere('designation', 'like', '%Leader%')
                  ->orWhere('designation', 'like', '%Manager%');
            })->get(['id', 'name']);

        $supportExecutives = User::where('user_status', '=', 'active')
            ->where(function($query) {
                $query->whereHas('roles.department', function($q) {
                    $q->where('name', 'like', '%customer support%')
                      ->orWhere('name', 'like', '%customer success%')
                      ->orWhere('id', 5);
                })->orWhereHas('employeeOnboarding', function($q) {
                    $q->where('department_id', 5);
                });
            })->where(function($query) {
                $query->whereHas('roles', function($q) {
                    $q->where('name', 'like', '%executive%')
                      ->orWhere('name', 'like', '%intern%')
                      ->orWhere('name', 'like', '%agent%');
                })->orWhere('designation', 'like', '%Executive%')
                  ->orWhere('designation', 'like', '%Agent%')
                  ->orWhere('designation', 'like', '%Intern%');
            })->get(['id', 'name']);

        // Fetch Branches and Products for filter bars
        $branches = $this->visibility->visibleBranches($currentUser);
        $products = Product::query();
        $this->visibility->applyProductVisibility($products, $currentUser);
        $products = $products->select('id', 'product_name')->orderBy('product_name')->get();

        return view('pages.dashboard.cst-allocation', [
            'pendingLeads'      => $pendingLeadsPaginated,
            'completedLeads'    => $completedLeadsPaginated,
            'supportTls'        => $supportTls,
            'supportExecutives' => $supportExecutives,
            'branches'          => $branches,
            'products'          => $products,
            'isSupportTl'       => $isSupportTl,
            'isAdmin'           => $isAdmin
        ]);
    }

    /**
     * Allocate a lead to a Customer Support TL.
     */
    public function allocateTl(Request $request, Lead $lead): RedirectResponse
    {
        $currentUser = Auth::user();
        $isSupportTl = $currentUser->hasCustomerSupportLikeRole() && $currentUser->hasTlLikeRole();
        $isAdmin = $currentUser->isSuperAdmin() || $currentUser->isCompanyAdmin() || $currentUser->hasAdminLikeRole();

        // Verify authorization
        if (!$isAdmin && !$isSupportTl) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'customer_support_tl_id' => 'required_without:self_allocate|nullable|exists:users,id',
            'self_allocate'          => 'nullable|boolean'
        ]);

        $tlId = $request->boolean('self_allocate') && $isSupportTl
            ? $currentUser->id
            : $request->customer_support_tl_id;

        if (!$tlId) {
            return back()->with('error', 'Please select a Customer Support TL.');
        }

        $lead->update([
            'customer_support_tl_id'       => $tlId,
            'customer_support_allocated_at' => now()
        ]);

        $tlUser = User::find($tlId);

        return back()->with('success', "Lead successfully allocated to Support TL: <strong>{$tlUser->name}</strong>.");
    }

    /**
     * Allocate a lead to a Customer Support Executive (by the TL).
     */
    public function allocateExecutive(Request $request, Lead $lead): RedirectResponse
    {
        $currentUser = Auth::user();
        $isSupportTl = $currentUser->hasCustomerSupportLikeRole() && $currentUser->hasTlLikeRole();
        $isAdmin = $currentUser->isSuperAdmin() || $currentUser->isCompanyAdmin() || $currentUser->hasAdminLikeRole();

        // Verify authorization (only allocated TL or Admin can allocate executive)
        if (!$isAdmin && (!$isSupportTl || $lead->customer_support_tl_id !== $currentUser->id)) {
            abort(403, 'Unauthorized action. Only the assigned Support TL can allocate executives.');
        }

        $request->validate([
            'customer_support_executive_id' => 'required|exists:users,id'
        ]);

        $lead->update([
            'customer_support_executive_id' => $request->customer_support_executive_id
        ]);

        $execUser = User::find($request->customer_support_executive_id);

        return back()->with('success', "Lead successfully allocated to Executive: <strong>{$execUser->name}</strong>.");
    }
}
