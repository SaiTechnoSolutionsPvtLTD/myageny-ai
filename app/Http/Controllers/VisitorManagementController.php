<?php

namespace App\Http\Controllers;

use App\Http\Requests\VisitorEntryRequest;
use App\Models\VisitorEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VisitorManagementController extends Controller
{
    public function index(Request $request): View
    {
        $visitors = VisitorEntry::query()
            ->when($request->search, function ($query) use ($request) {
                $search = trim((string) $request->search);

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('visitor_name', 'like', '%' . $search . '%')
                        ->orWhere('mobile_number', 'like', '%' . $search . '%')
                        ->orWhere('person_to_meet', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('applied_position', 'like', '%' . $search . '%')
                        ->orWhere('company_name', 'like', '%' . $search . '%');
                });
            })
            ->when($request->visitor_type, fn ($query) => $query->where('visitor_type', $request->visitor_type))
            ->when($request->visit_date, fn ($query) => $query->whereDate('visit_date', $request->visit_date))
            ->when($request->status, fn ($query) => $query->where('status', $request->status))
            ->latest('visit_date')
            ->latest('in_time')
            ->paginate(10)
            ->withQueryString();

        return view('pages.hrms.visitor_management.index', compact('visitors'));
    }

    public function create(): View
    {
        return view('pages.hrms.visitor_management.create', [
            'visitor' => new VisitorEntry([
                'visitor_type' => VisitorEntry::TYPE_OTHERS,
                'visit_date' => now()->toDateString(),
                'in_time' => now()->format('H:i'),
            ]),
        ]);
    }

    public function store(VisitorEntryRequest $request): RedirectResponse
    {
        $visitor = VisitorEntry::create($this->payload($request->validated()));

        return redirect()
            ->route('visitor-management.show', $visitor)
            ->with('success', 'Visitor entry created successfully.');
    }

    public function show(VisitorEntry $visitorEntry): View
    {
        return view('pages.hrms.visitor_management.show', ['visitor' => $visitorEntry]);
    }

    public function edit(VisitorEntry $visitorEntry): View
    {
        return view('pages.hrms.visitor_management.edit', ['visitor' => $visitorEntry]);
    }

    public function update(VisitorEntryRequest $request, VisitorEntry $visitorEntry): RedirectResponse
    {
        $visitorEntry->update($this->payload($request->validated()));

        return redirect()
            ->route('visitor-management.show', $visitorEntry)
            ->with('success', 'Visitor entry updated successfully.');
    }

    public function destroy(VisitorEntry $visitorEntry): RedirectResponse
    {
        $visitorEntry->delete();

        return redirect()
            ->route('visitor-management.index')
            ->with('success', 'Visitor entry deleted successfully.');
    }

    public function qrCode(): View
    {
        $visitorFormUrl = route('visitor-entry.create');
        $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=320x320&margin=16&data=' . urlencode($visitorFormUrl);

        return view('pages.hrms.visitor_management.qr-code', compact('visitorFormUrl', 'qrCodeUrl'));
    }

    public function publicCreate(): View
    {
        return view('pages.public.visitor-entry', [
            'visitor' => new VisitorEntry([
                'visitor_type' => VisitorEntry::TYPE_OTHERS,
                'visit_date' => now()->toDateString(),
                'in_time' => now()->format('H:i'),
            ]),
        ]);
    }

    public function publicStore(VisitorEntryRequest $request): View
    {
        $visitor = VisitorEntry::create($this->payload($request->validated()));

        return view('pages.public.visitor-entry-success', compact('visitor'));
    }

    private function payload(array $validated): array
    {
        $validated['status'] = VisitorEntry::statusFor($validated['out_time'] ?? null);

        if (($validated['visitor_type'] ?? '') !== VisitorEntry::TYPE_CANDIDATE) {
            $validated['email'] = null;
            $validated['applied_position'] = null;
        }

        if (($validated['visitor_type'] ?? '') !== VisitorEntry::TYPE_CLIENT) {
            $validated['company_name'] = null;
        }

        return $validated;
    }
}
