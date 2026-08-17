<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Lead;
use App\Models\User;
use App\Services\DataVisibilityService;
use Illuminate\Http\Request;

class PreSalesController extends Controller
{
    public function __construct(private readonly DataVisibilityService $visibility) {}

    /**
     * Display pre-sales leads list for logged-in user.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Lead::with(['branch', 'assignedTo', 'preSaleExecutive', 'createdBy', 'products'])
            ->latest('lead_date');

        // Default to leads allocated to Pre-Sales
        if ($user && !$user->isSystemAdmin() && !$user->isCompanyAdmin() && !$user->isBranchAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('pre_sale_executive_id', $user->id)
                  ->orWhere('assigned_to', $user->id);
            });
        } else {
            $query->whereNotNull('pre_sale_executive_id');
        }

        // Search Filter
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('company_name',  'like', "%{$s}%")
                  ->orWhere('contact_name','like', "%{$s}%")
                  ->orWhere('mobile_number','like',"%{$s}%")
                  ->orWhere('email',        'like', "%{$s}%");
            });
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $leads = $query->paginate(20)->withQueryString();

        // Assignable Sales Executives
        $salesExecutives = $this->visibility->visibleAssignableUsers();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        $totalCount = (clone $query)->count();

        return view('pages.pre_sales.index', compact('leads', 'salesExecutives', 'branches', 'totalCount'));
    }

    /**
     * Allocate / move leads to a Sales Executive.
     */
    public function allocate(Request $request)
    {
        $request->validate([
            'lead_ids' => 'required|array|min:1',
            'lead_ids.*' => 'exists:leads,id',
            'sales_person_id' => 'required|exists:users,id',
        ]);

        $salesPerson = User::findOrFail($request->sales_person_id);
        $leadIds = $request->lead_ids;

        Lead::whereIn('id', $leadIds)->update([
            'assigned_to' => $salesPerson->id,
            'pre_sale_executive_id' => auth()->id(),
            'updated_at' => now(),
        ]);

        $count = count($leadIds);

        return redirect()->back()->with('success', "Successfully moved / allocated <strong>{$count}</strong> lead(s) to <strong>{$salesPerson->name}</strong>.");
    }
}
