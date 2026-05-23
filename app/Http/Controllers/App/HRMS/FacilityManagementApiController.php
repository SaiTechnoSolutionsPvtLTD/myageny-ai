<?php

namespace App\Http\Controllers\App\HRMS;

use App\Http\Controllers\Controller;
use App\Models\FacilityManagement;
use App\Models\FacilityTitle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FacilityManagementApiController extends Controller
{
    // ── GET /api/mobile/hrms/facility-management ──────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $query = FacilityManagement::query()
            ->with('facilityTitle')
            ->when($request->search, function ($q) use ($request) {
                $search = trim((string) $request->search);
                $q->where(function ($sub) use ($search) {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhereHas('facilityTitle', fn ($t) => $t->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->date, fn ($q) => $q->whereDate('entry_date', $request->date))
            ->latest();

        $perPage = (int) ($request->per_page ?? 15);
        $entries = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $entries->map(fn ($e) => $this->formatEntry($e)),
            'meta'    => [
                'current_page' => $entries->currentPage(),
                'last_page'    => $entries->lastPage(),
                'per_page'     => $entries->perPage(),
                'total'        => $entries->total(),
            ],
        ]);
    }

    // ── GET /api/mobile/hrms/facility-management/{id} ────────────────────────
    public function show(FacilityManagement $facilityManagement): JsonResponse
    {
        $facilityManagement->load('facilityTitle');

        return response()->json([
            'success' => true,
            'data'    => $this->formatEntry($facilityManagement),
        ]);
    }

    // ── POST /api/mobile/hrms/facility-management ────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'facility_title_id' => ['required', 'integer', 'exists:facility_titles,id'],
            'remarks'           => ['nullable', 'string', 'max:1000'],
        ]);

        $facilityTitle = FacilityTitle::findOrFail($validated['facility_title_id']);
        $now = now();

        $entry = FacilityManagement::create([
            'facility_title_id' => $facilityTitle->id,
            'title'             => $facilityTitle->name,
            'entry_date'        => $now->toDateString(),
            'entry_time'        => $now->format('H:i:s'),
            'remarks'           => $validated['remarks'] ?? null,
        ]);

        $entry->load('facilityTitle');

        return response()->json([
            'success' => true,
            'message' => "Facility entry '{$facilityTitle->name}' created successfully.",
            'data'    => $this->formatEntry($entry),
        ], 201);
    }

    // ── PUT /api/mobile/hrms/facility-management/{id} ────────────────────────
    public function update(Request $request, FacilityManagement $facilityManagement): JsonResponse
    {
        $validated = $request->validate([
            'facility_title_id' => ['required', 'integer', 'exists:facility_titles,id'],
            'remarks'           => ['nullable', 'string', 'max:1000'],
        ]);

        $facilityTitle = FacilityTitle::findOrFail($validated['facility_title_id']);
        $now = now();

        $facilityManagement->update([
            'facility_title_id' => $facilityTitle->id,
            'title'             => $facilityTitle->name,
            'entry_date'        => $facilityManagement->entry_date?->toDateString() ?? $now->toDateString(),
            'entry_time'        => $facilityManagement->entry_time ?? $now->format('H:i:s'),
            'remarks'           => $validated['remarks'] ?? null,
        ]);

        $facilityManagement->load('facilityTitle');

        return response()->json([
            'success' => true,
            'message' => "Facility entry '{$facilityTitle->name}' updated successfully.",
            'data'    => $this->formatEntry($facilityManagement),
        ]);
    }

    // ── DELETE /api/mobile/hrms/facility-management/{id} ─────────────────────
    public function destroy(FacilityManagement $facilityManagement): JsonResponse
    {
        $title = $facilityManagement->facilityTitle?->name ?? $facilityManagement->title;
        $facilityManagement->delete();

        return response()->json([
            'success' => true,
            'message' => "Facility entry '{$title}' deleted successfully.",
        ]);
    }

    // ── GET /api/mobile/hrms/facility-titles ─────────────────────────────────
    public function titles(): JsonResponse
    {
        $titles = FacilityTitle::orderBy('name')->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data'    => $titles->map(fn ($t) => [
                'id'   => $t->id,
                'name' => $t->name,
            ]),
        ]);
    }

    // ── Private helper ────────────────────────────────────────────────────────
    private function formatEntry(FacilityManagement $entry): array
    {
        return [
            'id'                => $entry->id,
            'facility_title_id' => $entry->facility_title_id,
            'title'             => $entry->title,
            'facility_title'    => $entry->facilityTitle ? [
                'id'   => $entry->facilityTitle->id,
                'name' => $entry->facilityTitle->name,
            ] : null,
            'entry_date'        => $entry->entry_date?->toDateString(),
            'entry_date_formatted' => $entry->entry_date?->format('d M Y'),
            'entry_time'        => $entry->entry_time,
            'entry_time_formatted' => $entry->entry_time
                ? \Carbon\Carbon::createFromTimeString($entry->entry_time)->format('h:i A')
                : null,
            'remarks'           => $entry->remarks,
            'created_at'        => $entry->created_at?->toIso8601String(),
        ];
    }
}