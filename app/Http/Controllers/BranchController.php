<?php

namespace App\Http\Controllers;

use App\Http\Requests\BranchRequest;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function index(Request $request): View
    {
        Branch::ensureDefaultForCurrentCompany();

        $branches = Branch::query()
            ->when($request->search, function ($query) use ($request) {
                $search = trim((string) $request->search);

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('code', 'like', '%' . $search . '%')
                        ->orWhere('city', 'like', '%' . $search . '%')
                        ->orWhere('state', 'like', '%' . $search . '%');
                });
            })
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('pages.settings.branches.index', compact('branches'));
    }

    public function create(): View
    {
        return view('pages.settings.branches.create');
    }

    public function store(BranchRequest $request): RedirectResponse
    {
        $branch = DB::transaction(function () use ($request) {
            $branch = Branch::create([
                ...$request->validated(),
                'is_active' => $request->boolean('is_active', true),
                'is_default' => $request->boolean('is_default'),
            ]);

            $this->syncDefaultState($branch);

            return $branch;
        });

        return redirect()
            ->route('settings.branches.index')
            ->with('success', "Branch {$branch->name} created successfully.");
    }

    public function edit(Branch $branch): View
    {
        return view('pages.settings.branches.edit', compact('branch'));
    }

    public function update(BranchRequest $request, Branch $branch): RedirectResponse
    {
        DB::transaction(function () use ($request, $branch) {
            $branch->update([
                ...$request->validated(),
                'is_active' => $request->boolean('is_active', false),
                'is_default' => $request->boolean('is_default'),
            ]);

            $this->syncDefaultState($branch);
        });

        return redirect()
            ->route('settings.branches.index')
            ->with('success', "Branch {$branch->name} updated successfully.");
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        $branchName = $branch->name;
        $wasDefault = $branch->is_default;

        $branch->delete();

        if ($wasDefault) {
            Branch::ensureDefaultForCurrentCompany();
        }

        return redirect()
            ->route('settings.branches.index')
            ->with('success', "Branch {$branchName} deleted successfully.");
    }

    private function syncDefaultState(Branch $branch): void
    {
        if ($branch->is_default) {
            Branch::query()
                ->where('company_id', $branch->company_id)
                ->where('id', '!=', $branch->id)
                ->update(['is_default' => false]);

            return;
        }

        Branch::ensureDefaultForCurrentCompany();
    }
}
