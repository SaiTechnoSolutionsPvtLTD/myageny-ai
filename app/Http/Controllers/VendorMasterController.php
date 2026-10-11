<?php

namespace App\Http\Controllers;

use App\Models\VendorMaster;
use Illuminate\Http\Request;

class VendorMasterController extends Controller
{
    /**
     * Display a listing of vendors with filters and statistics.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $companyId = $user?->company_id;

        $baseQuery = VendorMaster::query()
            ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')));

        // Stats calculation
        $allVendors = (clone $baseQuery)->get(['id', 'status']);
        $stats = [
            'total'    => $allVendors->count(),
            'active'   => $allVendors->where('status', 'active')->count(),
            'inactive' => $allVendors->where('status', 'inactive')->count(),
        ];

        // Search & Filters
        $vendors = (clone $baseQuery)
            ->with(['creator'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim($request->search);
                $q->where(function ($sub) use ($search) {
                    $sub->where('vendor_name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('mobile_number', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('gst_number', 'like', "%{$search}%")
                        ->orWhere('bank_name', 'like', "%{$search}%")
                        ->orWhere('account_number', 'like', "%{$search}%")
                        ->orWhere('ifsc_code', 'like', "%{$search}%")
                        ->orWhere('branch_name', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages.accounts.master.vendors', compact('vendors', 'stats'));
    }

    /**
     * Store a newly created vendor in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vendor_name'    => 'required|string|max:255',
            'company_name'   => 'nullable|string|max:255',
            'mobile_number'  => 'nullable|string|max:50',
            'email'          => 'nullable|email|max:255',
            'address'        => 'nullable|string',
            'gst_number'     => 'nullable|string|max:50',
            'bank_name'      => 'nullable|string|max:255',
            'account_name'   => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:100',
            'ifsc_code'      => 'nullable|string|max:50',
            'branch_name'    => 'nullable|string|max:255',
        ]);

        $validated['company_id'] = auth()->user()?->company_id;
        $validated['status']     = 'active';
        $validated['created_by'] = auth()->id();

        VendorMaster::create($validated);

        return redirect()->back()->with('success', 'Vendor registered successfully!');
    }

    /**
     * Update the specified vendor in storage.
     */
    public function update(Request $request, VendorMaster $vendor)
    {
        $validated = $request->validate([
            'vendor_name'    => 'required|string|max:255',
            'company_name'   => 'nullable|string|max:255',
            'mobile_number'  => 'nullable|string|max:50',
            'email'          => 'nullable|email|max:255',
            'address'        => 'nullable|string',
            'gst_number'     => 'nullable|string|max:50',
            'bank_name'      => 'nullable|string|max:255',
            'account_name'   => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:100',
            'ifsc_code'      => 'nullable|string|max:50',
            'branch_name'    => 'nullable|string|max:255',
            'status'         => 'required|in:active,inactive',
        ]);

        $vendor->update($validated);

        return redirect()->back()->with('success', 'Vendor updated successfully!');
    }

    /**
     * Toggle the status between active and inactive.
     */
    public function toggleStatus(VendorMaster $vendor)
    {
        $newStatus = $vendor->status === 'active' ? 'inactive' : 'active';
        $vendor->update(['status' => $newStatus]);

        return redirect()->back()->with('success', "Vendor status changed to {$newStatus}.");
    }

    /**
     * Remove the specified vendor from storage (Soft Delete).
     */
    public function destroy(VendorMaster $vendor)
    {
        $vendor->delete();

        return redirect()->back()->with('success', 'Vendor deleted successfully!');
    }
}
