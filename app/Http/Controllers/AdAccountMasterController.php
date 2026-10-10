<?php

namespace App\Http\Controllers;

use App\Models\AdAccountMaster;
use App\Models\Branch;
use Illuminate\Http\Request;

class AdAccountMasterController extends Controller
{
    public function index(Request $request)
    {
        $query = AdAccountMaster::with(['branch', 'creator'])
            ->when($request->search, function ($q) use ($request) {
                $q->where('account_name', 'like', '%' . $request->search . '%')
                  ->orWhere('account_id', 'like', '%' . $request->search . '%')
                  ->orWhere('platform', 'like', '%' . $request->search . '%');
            })
            ->when($request->status, function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when($request->platform, function ($q) use ($request) {
                $q->where('platform', $request->platform);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $branches = Branch::active()->get();

        return view('pages.accounts.ad_accounts_master.index', compact('query', 'branches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_name' => 'required|string|max:255',
            'account_id'   => 'nullable|string|max:255',
            'platform'     => 'required|string|max:100',
            'branch_id'    => 'nullable|exists:branches,id',
            'notes'        => 'nullable|string',
        ]);

        $validated['company_id'] = auth()->user()?->company_id;
        $validated['status']     = 'active';
        $validated['created_by'] = auth()->id();

        AdAccountMaster::create($validated);

        return redirect()->back()->with('success', 'Ad Account created successfully!');
    }

    public function update(Request $request, AdAccountMaster $adAccount)
    {
        $validated = $request->validate([
            'account_name' => 'required|string|max:255',
            'account_id'   => 'nullable|string|max:255',
            'platform'     => 'required|string|max:100',
            'branch_id'    => 'nullable|exists:branches,id',
            'notes'        => 'nullable|string',
            'status'       => 'required|in:active,inactive',
        ]);

        $adAccount->update($validated);

        return redirect()->back()->with('success', 'Ad Account updated successfully!');
    }

    public function toggleStatus(AdAccountMaster $adAccount)
    {
        $newStatus = $adAccount->status === 'active' ? 'inactive' : 'active';
        $adAccount->update(['status' => $newStatus]);

        return redirect()->back()->with('success', "Ad Account status set to {$newStatus}.");
    }

    public function destroy(AdAccountMaster $adAccount)
    {
        $adAccount->delete();

        return redirect()->back()->with('success', 'Ad Account deleted successfully!');
    }
}
