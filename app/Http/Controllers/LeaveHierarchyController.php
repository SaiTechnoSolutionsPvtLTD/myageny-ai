<?php

namespace App\Http\Controllers;

use App\Models\LeaveHierarchy;
use App\Models\Role;
use Illuminate\Http\Request;

class LeaveHierarchyController extends Controller
{
    /**
     * Display listing of leave approval hierarchies.
     */
    public function index(Request $request)
    {
        $companyId = auth()->user()?->company_id;

        $hierarchies = LeaveHierarchy::with('role')
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->get();

        $roles = Role::withoutGlobalScope('company')
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->orderBy('name')
            ->get();

        if ($roles->isEmpty()) {
            $roles = Role::orderBy('name')->get();
        }

        $formattedRoles = $roles->map(function ($r) {
            return [
                'id'   => $r->id,
                'name' => $r->display_name ?? ucfirst(str_replace('_', ' ', $r->name)),
            ];
        })->values()->toArray();

        return view('pages.settings.leave-hierarchy.index', compact('hierarchies', 'roles', 'formattedRoles'));
    }

    /**
     * Store a newly created leave approval hierarchy.
     */
    public function store(Request $request)
    {
        $companyId = auth()->user()?->company_id;

        $validated = $request->validate([
            'role_id'        => 'required|exists:roles,id',
            'approval_chain' => 'required|array|min:1',
            'approval_chain.*' => 'required|exists:roles,id',
            'is_active'      => 'nullable|boolean',
            'notes'          => 'nullable|string|max:1000',
        ]);

        $approvalChain = array_values(array_map('intval', $validated['approval_chain']));

        LeaveHierarchy::updateOrCreate(
            [
                'company_id' => $companyId,
                'role_id'    => $validated['role_id'],
            ],
            [
                'approval_chain' => $approvalChain,
                'is_active'      => $request->has('is_active') ? $request->boolean('is_active') : true,
                'notes'          => $validated['notes'] ?? null,
            ]
        );

        return redirect()
            ->route('settings.leave-hierarchy.index')
            ->with('success', 'Leave approval hierarchy saved successfully.');
    }

    /**
     * Update specified leave approval hierarchy.
     */
    public function update(Request $request, LeaveHierarchy $leaveHierarchy)
    {
        $validated = $request->validate([
            'role_id'        => 'required|exists:roles,id',
            'approval_chain' => 'required|array|min:1',
            'approval_chain.*' => 'required|exists:roles,id',
            'is_active'      => 'nullable|boolean',
            'notes'          => 'nullable|string|max:1000',
        ]);

        $approvalChain = array_values(array_map('intval', $validated['approval_chain']));

        $leaveHierarchy->update([
            'role_id'        => $validated['role_id'],
            'approval_chain' => $approvalChain,
            'is_active'      => $request->has('is_active') ? $request->boolean('is_active') : $leaveHierarchy->is_active,
            'notes'          => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('settings.leave-hierarchy.index')
            ->with('success', 'Leave approval hierarchy updated successfully.');
    }

    /**
     * Remove specified leave approval hierarchy.
     */
    public function destroy(LeaveHierarchy $leaveHierarchy)
    {
        $leaveHierarchy->delete();

        return redirect()
            ->route('settings.leave-hierarchy.index')
            ->with('success', 'Leave approval hierarchy deleted successfully.');
    }

    /**
     * Toggle active status of hierarchy.
     */
    public function toggleStatus(LeaveHierarchy $leaveHierarchy)
    {
        $leaveHierarchy->update([
            'is_active' => !$leaveHierarchy->is_active,
        ]);

        return redirect()
            ->route('settings.leave-hierarchy.index')
            ->with('success', 'Leave hierarchy status updated successfully.');
    }
}
