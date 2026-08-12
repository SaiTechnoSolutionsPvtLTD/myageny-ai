<?php

namespace App\Http\Controllers;

use App\Models\ExpensePipeline;
use App\Models\Role;
use Illuminate\Http\Request;

class ExpensePipelineController extends Controller
{
    /**
     * Display a listing of expense approval pipelines.
     */
    public function index(Request $request)
    {
        $companyId = auth()->user()?->company_id;

        $pipelines = ExpensePipeline::with('role')
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->get();

        $roles = Role::withoutGlobalScope('company')
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->orderBy('name')
            ->get();

        if ($roles->isEmpty()) {
            $roles = Role::orderBy('name')->get();
        }

        return view('pages.settings.expense-pipeline.index', compact('pipelines', 'roles'));
    }

    /**
     * Store a newly created expense approval pipeline.
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

        ExpensePipeline::updateOrCreate(
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
            ->route('settings.expense-pipeline.index')
            ->with('success', 'Expense approval pipeline saved successfully.');
    }

    /**
     * Update the specified expense approval pipeline.
     */
    public function update(Request $request, ExpensePipeline $expensePipeline)
    {
        $validated = $request->validate([
            'role_id'        => 'required|exists:roles,id',
            'approval_chain' => 'required|array|min:1',
            'approval_chain.*' => 'required|exists:roles,id',
            'is_active'      => 'nullable|boolean',
            'notes'          => 'nullable|string|max:1000',
        ]);

        $approvalChain = array_values(array_map('intval', $validated['approval_chain']));

        $expensePipeline->update([
            'role_id'        => $validated['role_id'],
            'approval_chain' => $approvalChain,
            'is_active'      => $request->has('is_active') ? $request->boolean('is_active') : $expensePipeline->is_active,
            'notes'          => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('settings.expense-pipeline.index')
            ->with('success', 'Expense approval pipeline updated successfully.');
    }

    /**
     * Remove the specified expense approval pipeline.
     */
    public function destroy(ExpensePipeline $expensePipeline)
    {
        $expensePipeline->delete();

        return redirect()
            ->route('settings.expense-pipeline.index')
            ->with('success', 'Expense approval pipeline deleted successfully.');
    }

    /**
     * Toggle active status of pipeline.
     */
    public function toggleStatus(ExpensePipeline $expensePipeline)
    {
        $expensePipeline->update([
            'is_active' => !$expensePipeline->is_active,
        ]);

        return redirect()
            ->route('settings.expense-pipeline.index')
            ->with('success', 'Pipeline status updated successfully.');
    }
}
