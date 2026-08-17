<?php

namespace App\Http\Controllers;

use App\Models\HouseKeepingEmployee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HouseKeepingEmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $query = HouseKeepingEmployee::query();

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('mobile_number', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $employees = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('pages.hrms.house-keeping.employees.index', compact('employees'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:150',
            'mobile_number' => 'nullable|string|max:50',
            'address'       => 'nullable|string',
            'salary'        => 'nullable|numeric|min:0',
            'status'        => 'required|in:Active,Inactive',
            'remarks'       => 'nullable|string',
        ]);

        $employee = HouseKeepingEmployee::create($validated);

        return redirect()
            ->route('house-keeping.employees.index')
            ->with('success', "Housekeeping employee '{$employee->name}' added successfully.");
    }

    public function update(Request $request, HouseKeepingEmployee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:150',
            'mobile_number' => 'nullable|string|max:50',
            'address'       => 'nullable|string',
            'salary'        => 'nullable|numeric|min:0',
            'status'        => 'required|in:Active,Inactive',
            'remarks'       => 'nullable|string',
        ]);

        $employee->update($validated);

        return redirect()
            ->route('house-keeping.employees.index')
            ->with('success', "Housekeeping employee '{$employee->name}' updated successfully.");
    }

    public function destroy(HouseKeepingEmployee $employee): RedirectResponse
    {
        $name = $employee->name;
        $employee->delete();

        return redirect()
            ->route('house-keeping.employees.index')
            ->with('success', "Housekeeping employee '{$name}' deleted successfully.");
    }
}
