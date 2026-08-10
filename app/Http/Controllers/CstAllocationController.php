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

        // 1. Query leads that have at least one converted product and payment progress >= 40%
        $leadsQuery = Lead::with([
            'products' => function($q) {
                $q->where('product_status', '=', 'converted');
            },
            'customerSupportTl',
            'customerSupportExecutive'
        ])
        ->whereHas('products', function($q) {
            $q->where('product_status', '=', 'converted');
        })
        ->select('leads.*')
        ->selectSub(function($q) {
            $q->from('lead_products')
                ->whereColumn('lead_id', 'leads.id')
                ->where('product_status', 'converted')
                ->whereNull('deleted_at')
                ->selectRaw('COALESCE(SUM(total_price), 0)');
        }, 'payment_total_price')
        ->selectSub(function($q) {
            $q->from('lead_products')
                ->whereColumn('lead_id', 'leads.id')
                ->where('product_status', 'converted')
                ->whereNull('deleted_at')
                ->selectRaw('COALESCE(SUM(amount_paid), 0)');
        }, 'payment_amount_paid');

        // 2. Apply Filters in SQL (Branch, Product, CST User, Lead Account)
        $fBranch      = $request->get('branch_id');
        $fProduct     = $request->get('product_id');
        $fCstUser     = $request->get('cst_user_id');
        $fLeadId      = $request->get('lead_id');
        $fLeadAccount = $request->get('lead_account');

        if ($fBranch) {
            $leadsQuery->where('branch_id', $fBranch);
        }
        if ($fProduct) {
            $leadsQuery->whereHas('products', function($q) use ($fProduct) {
                $q->where('product_status', '=', 'converted')
                  ->where('product_id', $fProduct);
            });
        }
        if ($fCstUser) {
            $leadsQuery->where(function($q) use ($fCstUser) {
                $q->where('customer_support_executive_id', $fCstUser)
                  ->orWhere('customer_support_tl_id', $fCstUser);
            });
        }
        if ($fLeadId) {
            $leadsQuery->where('leads.id', $fLeadId);
        }
        if ($fLeadAccount) {
            $leadsQuery->where(function($q) use ($fLeadAccount) {
                $q->where('company_name', 'like', "%{$fLeadAccount}%")
                  ->orWhere('contact_name', 'like', "%{$fLeadAccount}%");
            });
        }

        $eligibleLeads = $leadsQuery->get();

        // 3. Set computed attributes on eligible leads
        $eligibleLeads->each(function($lead) {
            $totalPrice = (float) $lead->payment_total_price;
            $totalPaid = (float) $lead->payment_amount_paid;
            $progress = $totalPrice > 0 ? ($totalPaid / $totalPrice) * 100 : 0;

            $lead->payment_progress_pct = round($progress, 1);
        });

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

        // Fetch Lead Accounts for filter dropdown
        $leadAccounts = Lead::whereHas('products', function($q) {
                $q->where('product_status', '=', 'converted');
            })
            ->select('id', 'company_name', 'contact_name')
            ->orderBy('company_name')
            ->orderBy('contact_name')
            ->get();

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
            'leadAccounts'      => $leadAccounts,
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