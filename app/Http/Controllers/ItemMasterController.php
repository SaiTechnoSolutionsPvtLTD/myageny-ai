<?php

namespace App\Http\Controllers;

use App\Models\ItemMaster;
use Illuminate\Http\Request;

class ItemMasterController extends Controller
{
    /**
     * Display a listing of items with filters and statistics.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $companyId = $user?->company_id;

        $baseQuery = ItemMaster::query()
            ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')));

        // Stats calculation
        $allItems = (clone $baseQuery)->get(['id', 'status']);
        $stats = [
            'total'    => $allItems->count(),
            'active'   => $allItems->where('status', 'active')->count(),
            'inactive' => $allItems->where('status', 'inactive')->count(),
        ];

        // Search & Filters
        $items = (clone $baseQuery)
            ->with(['creator'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim($request->search);
                $q->where(function ($sub) use ($search) {
                    $sub->where('item_name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('hsn_sac_code', 'like', "%{$search}%")
                        ->orWhere('remarks', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages.accounts.master.items', compact('items', 'stats'));
    }

    /**
     * Store a newly created item in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_name'    => 'required|string|max:255',
            'description'  => 'nullable|string',
            'hsn_sac_code' => 'nullable|string|max:50',
            'rate'         => 'nullable|numeric|min:0',
            'gst'          => 'nullable|numeric|min:0|max:100',
            'remarks'      => 'nullable|string',
        ]);

        $validated['rate']       = $request->filled('rate') ? (float) $request->rate : 0.00;
        $validated['gst']        = $request->filled('gst') ? (float) $request->gst : 0.00;
        $validated['company_id'] = auth()->user()?->company_id;
        $validated['status']     = 'active';
        $validated['created_by'] = auth()->id();

        ItemMaster::create($validated);

        return redirect()->back()->with('success', 'Item registered successfully!');
    }

    /**
     * Update the specified item in storage.
     */
    public function update(Request $request, ItemMaster $item)
    {
        $validated = $request->validate([
            'item_name'    => 'required|string|max:255',
            'description'  => 'nullable|string',
            'hsn_sac_code' => 'nullable|string|max:50',
            'rate'         => 'nullable|numeric|min:0',
            'gst'          => 'nullable|numeric|min:0|max:100',
            'remarks'      => 'nullable|string',
            'status'       => 'required|in:active,inactive',
        ]);

        $validated['rate'] = $request->filled('rate') ? (float) $request->rate : 0.00;
        $validated['gst']  = $request->filled('gst') ? (float) $request->gst : 0.00;

        $item->update($validated);

        return redirect()->back()->with('success', 'Item updated successfully!');
    }

    /**
     * Toggle the item status between active and inactive.
     */
    public function toggleStatus(ItemMaster $item)
    {
        $newStatus = $item->status === 'active' ? 'inactive' : 'active';
        $item->update(['status' => $newStatus]);

        return redirect()->back()->with('success', "Item status changed to {$newStatus}.");
    }

    /**
     * Remove the specified item from storage (Soft Delete).
     */
    public function destroy(ItemMaster $item)
    {
        $item->delete();

        return redirect()->back()->with('success', 'Item deleted successfully!');
    }
}
