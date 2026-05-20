<?php

namespace App\Http\Controllers;

use App\Http\Requests\FacilityManagementRequest;
use App\Models\FacilityManagement;
use App\Models\FacilityTitle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FacilityManagementController extends Controller
{
    public function index(Request $request): View
    {
        $facilityEntries = FacilityManagement::query()
            ->with('facilityTitle')
            ->when($request->search, function ($query) use ($request) {
                $search = trim((string) $request->search);

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('title', 'like', "%{$search}%")
                        ->orWhereHas('facilityTitle', function ($titleQuery) use ($search) {
                            $titleQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('pages.settings.facility_management.index', compact('facilityEntries'));
    }

    public function create(): View
    {
        return view('pages.settings.facility_management.create', [
            'facilityTitles' => FacilityTitle::orderBy('name')->get(),
            'currentDateTime' => now(),
        ]);
    }

    public function store(FacilityManagementRequest $request): RedirectResponse
    {
        $facilityTitle = FacilityTitle::findOrFail($request->validated('facility_title_id'));

        FacilityManagement::create($this->payload($facilityTitle, $request->validated('remarks')));

        return redirect()
            ->route('facility-management.index')
            ->with('success', "Facility entry '{$facilityTitle->name}' created successfully.");
    }

    public function edit(FacilityManagement $facility_management): View
    {
        return view('pages.settings.facility_management.edit', [
            'facilityEntry' => $facility_management,
            'facilityTitles' => FacilityTitle::orderBy('name')->get(),
        ]);
    }

    public function update(FacilityManagementRequest $request, FacilityManagement $facility_management): RedirectResponse
    {
        $facilityTitle = FacilityTitle::findOrFail($request->validated('facility_title_id'));
        $now = now();

        $facility_management->update([
            'facility_title_id' => $facilityTitle->id,
            'title' => $facilityTitle->name,
            'entry_date' => $facility_management->entry_date?->toDateString() ?? $now->toDateString(),
            'entry_time' => $facility_management->entry_time ?? $now->format('H:i:s'),
            'remarks' => $request->validated('remarks'),
        ]);

        return redirect()
            ->route('facility-management.index')
            ->with('success', "Facility entry '{$facilityTitle->name}' updated successfully.");
    }

    public function qrCode(): View
    {
        $facilityFormUrl = route('facility-entry.create');
        $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=320x320&margin=16&data=' . urlencode($facilityFormUrl);

        return view('pages.settings.facility_management.qr-code', compact('facilityFormUrl', 'qrCodeUrl'));
    }

    public function publicCreate(): View
    {
        return view('pages.public.facility-entry', [
            'facilityTitles' => FacilityTitle::orderBy('name')->get(),
            'currentDateTime' => now(),
        ]);
    }

    public function publicStore(FacilityManagementRequest $request): View
    {
        $facilityTitle = FacilityTitle::findOrFail($request->validated('facility_title_id'));

        $facilityEntry = FacilityManagement::create($this->payload($facilityTitle, $request->validated('remarks')));

        return view('pages.public.facility-entry-success', compact('facilityEntry'));
    }

    public function destroy(FacilityManagement $facility_management): RedirectResponse
    {
        $title = $facility_management->facilityTitle?->name ?? $facility_management->title;

        $facility_management->delete();

        return redirect()
            ->route('facility-management.index')
            ->with('success', "Facility entry '{$title}' deleted successfully.");
    }

    private function payload(FacilityTitle $facilityTitle, ?string $remarks = null): array
    {
        $now = now();

        return [
            'facility_title_id' => $facilityTitle->id,
            'title' => $facilityTitle->name,
            'entry_date' => $now->toDateString(),
            'entry_time' => $now->format('H:i:s'),
            'remarks' => $remarks,
        ];
    }
}
