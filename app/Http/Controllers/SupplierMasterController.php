<?php

namespace App\Http\Controllers;

use App\Models\SupplierMaster;
use Illuminate\Http\Request;

class SupplierMasterController extends Controller
{
    /**
     * Display a listing of suppliers with filters and statistics.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $companyId = $user?->company_id;

        $baseQuery = SupplierMaster::query()->forCompany($companyId);

        // Stats calculation
        $allSuppliers = (clone $baseQuery)->get(['id', 'status']);
        $stats = [
            'total'    => $allSuppliers->count(),
            'active'   => $allSuppliers->where('status', 'active')->count(),
            'inactive' => $allSuppliers->where('status', 'inactive')->count(),
        ];

        // Search & Filters
        $suppliers = (clone $baseQuery)
            ->with(['creator'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim($request->search);
                $q->where(function ($sub) use ($search) {
                    $sub->where('supplier_name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('mobile_number', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('gst_number', 'like', "%{$search}%")
                        ->orWhere('bank_name', 'like', "%{$search}%")
                        ->orWhere('account_name', 'like', "%{$search}%")
                        ->orWhere('account_number', 'like', "%{$search}%")
                        ->orWhere('ifsc_code', 'like', "%{$search}%")
                        ->orWhere('branch_name', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages.accounts.master.suppliers', compact('suppliers', 'stats'));
    }

    /**
     * Store a newly created supplier in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_name'  => 'required|string|max:255',
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

        SupplierMaster::create($validated);

        return redirect()->back()->with('success', 'Supplier registered successfully!');
    }

    /**
     * Update the specified supplier in storage.
     */
    public function update(Request $request, SupplierMaster $supplier)
    {
        $validated = $request->validate([
            'supplier_name'  => 'required|string|max:255',
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

        $validated['updated_by'] = auth()->id();

        $supplier->update($validated);

        return redirect()->back()->with('success', 'Supplier updated successfully!');
    }

    /**
     * Toggle the supplier status between active and inactive.
     */
    public function toggleStatus(SupplierMaster $supplier)
    {
        $newStatus = $supplier->status === 'active' ? 'inactive' : 'active';
        $supplier->update([
            'status'     => $newStatus,
            'updated_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', "Supplier status changed to {$newStatus}.");
    }

    /**
     * Remove the specified supplier from storage (Soft Delete).
     */
    public function destroy(SupplierMaster $supplier)
    {
        $supplier->delete();

        return redirect()->back()->with('success', 'Supplier deleted successfully!');
    }
}
