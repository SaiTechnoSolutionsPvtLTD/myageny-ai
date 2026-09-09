<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepartmentRequest;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(Request $request): View
    {
        $departments = Department::query()
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

        return view('pages.settings.department.index', compact('departments'));
    }

    public function create(): View
    {
        return view('pages.settings.department.create', [
            'dashboardRouteOptions' => Department::dashboardRouteOptions(),
        ]);
    }

    public function store(DepartmentRequest $request): RedirectResponse
    {
        $department = Department::create($request->validated());

        return redirect()
            ->route('hrms.masters.departments.index')
            ->with('success', "Department {$department->name} created successfully.");
    }

    public function edit(Department $department): View
    {
        return view('pages.settings.department.edit', [
            'department' => $department,
            'dashboardRouteOptions' => Department::dashboardRouteOptions(),
        ]);
    }

    public function update(DepartmentRequest $request, Department $department): RedirectResponse
    {
        $department->update($request->validated());

        return redirect()
            ->route('hrms.masters.departments.index')
            ->with('success', "Department {$department->name} updated successfully.");
    }

    public function destroy(Department $department): RedirectResponse
    {
        $departmentName = $department->name;
        $department->delete();

        return redirect()
            ->route('hrms.masters.departments.index')
            ->with('success', "Department {$departmentName} deleted successfully.");
    }
}
