<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\LeadCallUpdate;
use App\Models\User;
use App\Services\DataVisibilityService;
use Illuminate\Http\Request;

class LeadCallUpdateController extends Controller
{
    public function __construct(private readonly DataVisibilityService $visibility) {}

    public function index(Request $request)
    {
        if ($request->has('date_from') && ($request->input('date_from') === '' || $request->input('date_from') === 'all' || $request->input('date_from') === null)) {
            $dateFrom = null;
            $dateTo = null;
        } else {
            $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
            $dateTo = $request->input('date_to', now()->endOfMonth()->toDateString());
        }

        $query = LeadCallUpdate::with([
            'lead:id,company_name,contact_name,mobile_number,email',
            'user:id,name',
            'outCome:id,name',
            'outComeSubCategory:id,name',
        ])->latest('called_at');

        $this->visibility->applyLeadRelationVisibility($query);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                    ->orWhereHas('lead', function ($leadQuery) use ($search) {
                        $leadQuery->where('id', $search)
                            ->orWhere('company_name', 'like', "%{$search}%")
                            ->orWhere('contact_name', 'like', "%{$search}%")
                            ->orWhere('mobile_number', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('branch_id')) {
            $query->whereHas('lead', function ($leadQuery) use ($request) {
                $leadQuery->where('branch_id', $request->branch_id);
            });
        }

        if ($dateFrom) {
            $query->whereDate('called_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('called_at', '<=', $dateTo);
        }

        $callUpdates = $query->paginate(15)->withQueryString();
        $branches = Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $users = $this->visibility->visibleAssignableUsers();

        return view('pages.leads.call_updates.index', compact(
            'callUpdates',
            'branches',
            'users',
            'dateFrom',
            'dateTo'
        ));
    }
}
