<?php

namespace App\Http\Controllers;

use App\Models\ServerMaster;
use Illuminate\Http\Request;

class ServerMasterController extends Controller
{
    /**
     * Display a listing of servers with filters and statistics.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $companyId = $user?->company_id;

        $baseQuery = ServerMaster::query()->forCompany($companyId);

        // Stats calculation
        $allServers = (clone $baseQuery)->get(['id', 'status']);
        $stats = [
            'total'       => $allServers->count(),
            'active'      => $allServers->where('status', 'active')->count(),
            'maintenance' => $allServers->where('status', 'maintenance')->count(),
            'inactive'    => $allServers->where('status', 'inactive')->count(),
        ];

        // Search & Filters
        $servers = (clone $baseQuery)
            ->with(['creator', 'updater'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim($request->search);
                $q->where(function ($sub) use ($search) {
                    $sub->where('server_name', 'like', "%{$search}%")
                        ->orWhere('provider_name', 'like', "%{$search}%")
                        ->orWhere('plan_type', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%")
                        ->orWhere('disk_space', 'like', "%{$search}%")
                        ->orWhere('cpu', 'like', "%{$search}%")
                        ->orWhere('remarks', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages.accounts.master.servers', compact('servers', 'stats'));
    }

    /**
     * Store a newly created server in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'server_name'   => 'required|string|max:255',
            'disk_space'    => 'nullable|string|max:100',
            'cpu'           => 'nullable|string|max:100',
            'ip_address'    => 'nullable|string|max:100',
            'provider_name' => 'nullable|string|max:150',
            'plan_type'     => 'nullable|string|max:100',
            'status'        => 'nullable|in:active,inactive,maintenance',
            'remarks'       => 'nullable|string',
        ]);

        $validated['status']     = $validated['status'] ?? 'active';
        $validated['company_id'] = auth()->user()?->company_id;
        $validated['created_by'] = auth()->id();

        ServerMaster::create($validated);

        return redirect()->back()->with('success', 'Server registered successfully!');
    }

    /**
     * Update the specified server in storage.
     */
    public function update(Request $request, ServerMaster $server)
    {
        $validated = $request->validate([
            'server_name'   => 'required|string|max:255',
            'disk_space'    => 'nullable|string|max:100',
            'cpu'           => 'nullable|string|max:100',
            'ip_address'    => 'nullable|string|max:100',
            'provider_name' => 'nullable|string|max:150',
            'plan_type'     => 'nullable|string|max:100',
            'status'        => 'required|in:active,inactive,maintenance',
            'remarks'       => 'nullable|string',
        ]);

        $validated['updated_by'] = auth()->id();

        $server->update($validated);

        return redirect()->back()->with('success', 'Server details updated successfully!');
    }

    /**
     * Toggle the server status between active and inactive.
     */
    public function toggleStatus(ServerMaster $server)
    {
        $newStatus = $server->status === 'active' ? 'inactive' : 'active';
        $server->update([
            'status'     => $newStatus,
            'updated_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', "Server status changed to {$newStatus}.");
    }

    /**
     * Remove the specified server from storage (Soft Delete).
     */
    public function destroy(ServerMaster $server)
    {
        $server->delete();

        return redirect()->back()->with('success', 'Server deleted successfully!');
    }
}
