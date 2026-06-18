<?php

namespace App\Http\Controllers;

use App\Http\Requests\HouseKeepingWorkRequest;
use App\Models\HouseKeepingCategory;
use App\Models\HouseKeepingWork;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HouseKeepingWorkController extends Controller
{
    public function index(Request $request): View
    {
        $works = HouseKeepingWork::query()
            ->with('category')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->search);

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('work_name', 'like', '%' . $search . '%')
                        ->orWhere('notes', 'like', '%' . $search . '%');
                });
            })
            ->when($request->filled('house_keeping_category_id'), function ($query) use ($request) {
                $query->where('house_keeping_category_id', $request->integer('house_keeping_category_id'));
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(12)
            ->withQueryString();

        $categories = HouseKeepingCategory::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('pages.settings.house-keeping-works.index', compact('works', 'categories'));
    }

    public function create(): View
    {
        $categories = HouseKeepingCategory::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('pages.settings.house-keeping-works.create', compact('categories'));
    }

    public function store(HouseKeepingWorkRequest $request): RedirectResponse
    {
        $work = HouseKeepingWork::create($request->validated());

        return redirect()
            ->route('settings.house-keeping-works.index')
            ->with('success', "House keeping work {$work->work_name} created successfully.");
    }

    public function edit(HouseKeepingWork $houseKeepingWork): View
    {
        $categories = HouseKeepingCategory::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('pages.settings.house-keeping-works.edit', compact('houseKeepingWork', 'categories'));
    }

    public function update(HouseKeepingWorkRequest $request, HouseKeepingWork $houseKeepingWork): RedirectResponse
    {
        $houseKeepingWork->update($request->validated());

        return redirect()
            ->route('settings.house-keeping-works.index')
            ->with('success', "House keeping work {$houseKeepingWork->work_name} updated successfully.");
    }

    public function destroy(HouseKeepingWork $houseKeepingWork): RedirectResponse
    {
        $name = $houseKeepingWork->work_name;
        $houseKeepingWork->delete();

        return redirect()
            ->route('settings.house-keeping-works.index')
            ->with('success', "House keeping work {$name} deleted successfully.");
    }
}
