<?php

namespace App\Http\Controllers;

use App\Models\BankMaster;
use Illuminate\Http\Request;

class BankMasterController extends Controller
{
    /**
     * Display a listing of banks with filters and statistics.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $companyId = $user?->company_id;

        $baseQuery = BankMaster::query()->forCompany($companyId);

        // Stats calculation
        $allBanks = (clone $baseQuery)->get();
        $stats = [
            'total'    => $allBanks->count(),
            'active'   => $allBanks->where('status', 'active')->count(),
            'inactive' => $allBanks->where('status', 'inactive')->count(),
            'primary'  => $allBanks->firstWhere('is_primary', true),
        ];

        // Search & Filters
        $banks = (clone $baseQuery)
            ->with(['creator', 'updater'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim($request->search);
                $q->where(function ($sub) use ($search) {
                    $sub->where('bank_name', 'like', "%{$search}%")
                        ->orWhere('account_name', 'like', "%{$search}%")
                        ->orWhere('account_number', 'like', "%{$search}%")
                        ->orWhere('ifsc_code', 'like', "%{$search}%")
                        ->orWhere('branch_name', 'like', "%{$search}%")
                        ->orWhere('upi_id', 'like', "%{$search}%")
                        ->orWhere('swift_code', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->orderByDesc('is_primary')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages.accounts.master.banks', compact('banks', 'stats'));
    }

    /**
     * Store a newly created bank account.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'bank_name'      => 'required|string|max:255',
            'account_name'   => 'required|string|max:255',
            'account_number' => 'required|string|max:100',
            'ifsc_code'      => 'required|string|max:50',
            'branch_name'    => 'nullable|string|max:255',
            'upi_id'         => 'nullable|string|max:150',
            'swift_code'     => 'nullable|string|max:50',
            'is_primary'     => 'nullable|boolean',
            'remarks'        => 'nullable|string',
        ]);

        $companyId = auth()->user()?->company_id;

        // Auto uppercase IFSC & SWIFT
        $validated['ifsc_code']  = strtoupper(trim($validated['ifsc_code']));
        $validated['swift_code'] = $validated['swift_code'] ? strtoupper(trim($validated['swift_code'])) : null;
        $validated['company_id'] = $companyId;
        $validated['created_by'] = auth()->id();
        $validated['status']     = 'active';

        // Check if there are any existing banks for this company
        $existingCount = BankMaster::query()->forCompany($companyId)->count();
        $isPrimary = $request->boolean('is_primary') || $existingCount === 0;

        if ($isPrimary) {
            BankMaster::query()->forCompany($companyId)->update(['is_primary' => false]);
            $validated['is_primary'] = true;
        } else {
            $validated['is_primary'] = false;
        }

        BankMaster::create($validated);

        return redirect()->back()->with('success', 'Bank account registered successfully!');
    }

    /**
     * Update the specified bank account.
     */
    public function update(Request $request, BankMaster $bank)
    {
        $validated = $request->validate([
            'bank_name'      => 'required|string|max:255',
            'account_name'   => 'required|string|max:255',
            'account_number' => 'required|string|max:100',
            'ifsc_code'      => 'required|string|max:50',
            'branch_name'    => 'nullable|string|max:255',
            'upi_id'         => 'nullable|string|max:150',
            'swift_code'     => 'nullable|string|max:50',
            'is_primary'     => 'nullable|boolean',
            'status'         => 'required|in:active,inactive',
            'remarks'        => 'nullable|string',
        ]);

        $companyId = auth()->user()?->company_id;

        // Auto uppercase IFSC & SWIFT
        $validated['ifsc_code']  = strtoupper(trim($validated['ifsc_code']));
        $validated['swift_code'] = $validated['swift_code'] ? strtoupper(trim($validated['swift_code'])) : null;
        $validated['updated_by'] = auth()->id();

        $isPrimary = $request->boolean('is_primary');
        if ($isPrimary) {
            BankMaster::query()->forCompany($companyId)->where('id', '!=', $bank->id)->update(['is_primary' => false]);
            $validated['is_primary'] = true;
            $validated['status']     = 'active';
        } else {
            // If this was the only bank, keep it primary
            $otherBanks = BankMaster::query()->forCompany($companyId)->where('id', '!=', $bank->id)->count();
            if ($otherBanks === 0) {
                $validated['is_primary'] = true;
            } else {
                $validated['is_primary'] = false;
            }
        }

        $bank->update($validated);

        return redirect()->back()->with('success', 'Bank account updated successfully!');
    }

    /**
     * Set the bank account as primary settlement account.
     */
    public function setPrimary(BankMaster $bank)
    {
        $companyId = auth()->user()?->company_id;

        BankMaster::query()->forCompany($companyId)->update(['is_primary' => false]);
        $bank->update([
            'is_primary' => true,
            'status'     => 'active',
            'updated_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', "{$bank->bank_name} ({$bank->account_number}) is now set as the primary settlement account.");
    }

    /**
     * Toggle the bank account status between active and inactive.
     */
    public function toggleStatus(BankMaster $bank)
    {
        $newStatus = $bank->status === 'active' ? 'inactive' : 'active';

        // If making inactive and it's primary, ensure user is aware
        if ($newStatus === 'inactive' && $bank->is_primary) {
            $bank->update(['status' => 'inactive', 'is_primary' => false, 'updated_by' => auth()->id()]);
            return redirect()->back()->with('success', "Bank status set to inactive. Primary status was cleared.");
        }

        $bank->update(['status' => $newStatus, 'updated_by' => auth()->id()]);

        return redirect()->back()->with('success', "Bank status changed to {$newStatus}.");
    }

    /**
     * Remove the specified bank account (Soft Delete).
     */
    public function destroy(BankMaster $bank)
    {
        $wasPrimary = $bank->is_primary;
        $companyId = auth()->user()?->company_id;
        $bank->delete();

        // If the deleted bank was primary, assign another active bank as primary if one exists
        if ($wasPrimary) {
            $nextBank = BankMaster::query()->forCompany($companyId)->active()->first();
            if ($nextBank) {
                $nextBank->update(['is_primary' => true]);
            }
        }

        return redirect()->back()->with('success', 'Bank account deleted successfully!');
    }
}
