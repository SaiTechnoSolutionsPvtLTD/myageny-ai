<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeaveTypeRequest;
use App\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveTypeController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = auth()->user()?->company_id;

        $leaveTypes = LeaveType::query()
            ->ownedByCompany($companyId)
            ->when($request->search, function ($query) use ($request) {
                $search = trim((string) $request->search);

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%');
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('pages.settings.leave-type.index', compact('leaveTypes'));
    }

    public function create(): View
    {
        return view('pages.settings.leave-type.create');
    }

    public function store(LeaveTypeRequest $request): RedirectResponse
    {
        $leaveType = LeaveType::create($request->validated());

        return redirect()
            ->route('hrms.masters.leave-types.index')
            ->with('success', "Leave type {$leaveType->name} created successfully.");
    }

    public function edit(LeaveType $leaveType): View
    {
        $this->authorizeCompanyOwnership($leaveType);

        return view('pages.settings.leave-type.edit', compact('leaveType'));
    }

    public function update(LeaveTypeRequest $request, LeaveType $leaveType): RedirectResponse
    {
        $this->authorizeCompanyOwnership($leaveType);
        $leaveType->update($request->validated());

        return redirect()
            ->route('hrms.masters.leave-types.index')
            ->with('success', "Leave type {$leaveType->name} updated successfully.");
    }

    public function destroy(LeaveType $leaveType): RedirectResponse
    {
        $this->authorizeCompanyOwnership($leaveType);
        $leaveTypeName = $leaveType->name;
        $leaveType->delete();

        return redirect()
            ->route('hrms.masters.leave-types.index')
            ->with('success', "Leave type {$leaveTypeName} deleted successfully.");
    }

    private function authorizeCompanyOwnership(LeaveType $leaveType): void
    {
        $companyId = auth()->user()?->company_id;

        if ($companyId === null) {
            return;
        }

        abort_unless((int) $leaveType->company_id === (int) $companyId, 403);
    }
}
