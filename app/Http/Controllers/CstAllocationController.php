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

        // 3. Apply Filters (Branch, Product, CST User)
        $fBranch = $request->get('branch_id');
        $fProduct = $request->get('product_id');
        $fCstUser = $request->get('cst_user_id');

        if ($fBranch) {
            $eligibleLeads = $eligibleLeads->where('branch_id', $fBranch);
        }
        if ($fProduct) {
            $eligibleLeads = $eligibleLeads->filter(function($lead) use ($fProduct) {
                return $lead->products->contains('product_id', $fProduct);
            });
        }
        if ($fCstUser) {
            $eligibleLeads = $eligibleLeads->filter(function($lead) use ($fCstUser) {
                return $lead->customer_support_executive_id == $fCstUser || $lead->customer_support_tl_id == $fCstUser;
            });
        }

        // 4. Partition based on assignment state and role visibility
        $isAdmin = $currentUser->isSuperAdmin() || $currentUser->isCompanyAdmin() || $currentUser->hasAdminLikeRole();

        if ($isAdmin) {
            $pendingLeads = $eligibleLeads->whereNull('customer_support_executive_id');
            $completedLeads = $eligibleLeads->whereNotNull('customer_support_executive_id');
        } else {
            $pendingLeads = collect();
            $completedLeads = $eligibleLeads->where('customer_support_executive_id', $currentUser->id);
        }

        // Paginate both collections separately (so pagination links don't conflict)
        $pendingLeadsPaginated = self::paginateCollection($pendingLeads, 15, 'page_pending');
        $completedLeadsPaginated = self::paginateCollection($completedLeads, 15, 'page_completed');

        // 5. Retrieve active support Users for assignment dropdowns
        $cstUsers = User::where('user_status', '=', 'active')
            ->where(function($query) {
                $query->whereHas('roles.department', function($q) {
                    $q->where('name', 'like', '%customer support%')
                      ->orWhere('name', 'like', '%customer success%')
                      ->orWhere('id', 5);
                })->orWhereHas('employeeOnboarding', function($q) {
                    $q->where('department_id', 5);
                });
            })->orderBy('name')->get(['id', 'name']);

        // Fetch Branches and Products for filter bars
        $branches = $this->visibility->visibleBranches($currentUser);
        $products = Product::query();
        $this->visibility->applyProductVisibility($products, $currentUser);
        $products = $products->select('id', 'product_name')->orderBy('product_name')->get();

        return view('pages.dashboard.cst-allocation', [
            'pendingLeads'      => $pendingLeadsPaginated,
            'completedLeads'    => $completedLeadsPaginated,
            'cstUsers'          => $cstUsers,
            'branches'          => $branches,
            'products'          => $products,
            'isAdmin'           => $isAdmin
        ]);
    }

    /**
     * Allocate a lead to a Customer Support TL.
     */
    public function allocateTl(Request $request, Lead $lead): RedirectResponse
    {
        $currentUser = Auth::user();
        $isAdmin = $currentUser->isSuperAdmin() || $currentUser->isCompanyAdmin() || $currentUser->hasAdminLikeRole();

        // Verify authorization
        if (!$isAdmin) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'cst_user_id' => 'required|exists:users,id',
        ]);

        $userId = $request->cst_user_id;

        $lead->update([
            'customer_support_tl_id'        => $userId,
            'customer_support_executive_id' => $userId,
            'customer_support_allocated_at' => now()
        ]);

        $user = User::find($userId);

        return back()->with('success', "Lead successfully allocated to Support User: <strong>{$user->name}</strong>.");
    }

    /**
     * Allocate a lead to a Customer Support Executive (by the TL).
     */
    public function allocateExecutive(Request $request, Lead $lead): RedirectResponse
    {
        $currentUser = Auth::user();
        $isAdmin = $currentUser->isSuperAdmin() || $currentUser->isCompanyAdmin() || $currentUser->hasAdminLikeRole();

        // Verify authorization
        if (!$isAdmin) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'cst_user_id' => 'required|exists:users,id'
        ]);

        $userId = $request->cst_user_id;

        $lead->update([
            'customer_support_tl_id'        => $userId,
            'customer_support_executive_id' => $userId,
            'customer_support_allocated_at' => now()
        ]);

        $user = User::find($userId);

        return back()->with('success', "Lead successfully allocated to Support User: <strong>{$user->name}</strong>.");
    }
}