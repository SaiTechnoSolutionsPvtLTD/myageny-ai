<?php

namespace App\Http\Controllers;

use App\Http\Requests\FacilityTitleRequest;
use App\Models\FacilityTitle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FacilityTitleController extends Controller
{
    public function index(Request $request): View
    {
        $facilityTitles = FacilityTitle::query()
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

        return view('pages.settings.facility-title.index', compact('facilityTitles'));
    }

    public function create(): View
    {
        return view('pages.settings.facility-title.create');
    }

    public function store(FacilityTitleRequest $request): RedirectResponse
    {
        $facilityTitle = FacilityTitle::create($request->validated());

        return redirect()
            ->route('settings.facility-titles.index')
            ->with('success', "Facility title {$facilityTitle->name} created successfully.");
    }

    public function edit(FacilityTitle $facilityTitle): View
    {
        return view('pages.settings.facility-title.edit', compact('facilityTitle'));
    }

    public function update(FacilityTitleRequest $request, FacilityTitle $facilityTitle): RedirectResponse
    {
        $facilityTitle->update($request->validated());

        return redirect()
            ->route('settings.facility-titles.index')
            ->with('success', "Facility title {$facilityTitle->name} updated successfully.");
    }

    public function destroy(FacilityTitle $facilityTitle): RedirectResponse
    {
        $facilityTitleName = $facilityTitle->name;
        $facilityTitle->delete();

        return redirect()
            ->route('settings.facility-titles.index')
            ->with('success', "Facility title {$facilityTitleName} deleted successfully.");
    }
}
