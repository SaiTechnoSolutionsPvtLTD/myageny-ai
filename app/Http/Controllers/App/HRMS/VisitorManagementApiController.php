<?php

namespace App\Http\Controllers\App\HRMS;

use App\Http\Controllers\Controller;
use App\Models\VisitorEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VisitorManagementApiController extends Controller
{
    // ── GET /api/mobile/hrms/visitor-management ────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $query = VisitorEntry::query()
            ->when($request->search, function ($q) use ($request) {
                $s = trim((string) $request->search);
                $q->where(function ($sub) use ($s) {
                    $sub->where('visitor_name',   'like', "%{$s}%")
                        ->orWhere('mobile_number', 'like', "%{$s}%")
                        ->orWhere('person_to_meet','like', "%{$s}%");
                });
            })
            ->when($request->visit_date, fn ($q) => $q->whereDate('visit_date', $request->visit_date))
            ->when($request->status,     fn ($q) => $q->where('status', $request->status))
            ->latest('visit_date')
            ->latest('in_time');

        $perPage   = (int) ($request->per_page ?? 15);
        $paginated = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $paginated->map(fn ($v) => $this->resource($v)),
            'meta'    => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ],
        ]);
    }

    // ── POST /api/mobile/hrms/visitor-management ───────────────────────────
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'visitor_name'   => 'required|string|max:255',
            'mobile_number'  => 'required|string|max:20',
            'person_to_meet' => 'required|string|max:255',
            'visit_date'     => 'required|date',
            'in_time'        => 'required|date_format:H:i',
            'out_time'       => 'nullable|date_format:H:i|after:in_time',
            'remarks'        => 'nullable|string|max:1000',
        ]);

        $validated['status'] = VisitorEntry::statusFor($validated['out_time'] ?? null);

        $visitor = VisitorEntry::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Visitor entry created successfully.',
            'data'    => $this->resource($visitor),
        ], 201);
    }

    // ── GET /api/mobile/hrms/visitor-management/{id} ───────────────────────
    public function show(int $id): JsonResponse
    {
        $visitor = VisitorEntry::findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $this->resource($visitor),
        ]);
    }

    // ── PUT /api/mobile/hrms/visitor-management/{id} ──────────────────────
    public function update(Request $request, int $id): JsonResponse
    {
        $visitor = VisitorEntry::findOrFail($id);

        $validated = $request->validate([
            'visitor_name'   => 'sometimes|required|string|max:255',
            'mobile_number'  => 'sometimes|required|string|max:20',
            'person_to_meet' => 'sometimes|required|string|max:255',
            'visit_date'     => 'sometimes|required|date',
            'in_time'        => 'sometimes|required|date_format:H:i',
            'out_time'       => 'nullable|date_format:H:i',
            'remarks'        => 'nullable|string|max:1000',
        ]);

        $validated['status'] = VisitorEntry::statusFor($validated['out_time'] ?? $visitor->out_time);

        $visitor->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Visitor entry updated successfully.',
            'data'    => $this->resource($visitor->fresh()),
        ]);
    }

    // ── DELETE /api/mobile/hrms/visitor-management/{id} ───────────────────
    public function destroy(int $id): JsonResponse
    {
        $visitor = VisitorEntry::findOrFail($id);
        $visitor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Visitor entry deleted successfully.',
        ]);
    }

    // ── Private resource serialiser ────────────────────────────────────────
    private function resource(VisitorEntry $v): array
    {
        return [
            'id'             => $v->id,
            'visitor_name'   => $v->visitor_name,
            'mobile_number'  => $v->mobile_number,
            'person_to_meet' => $v->person_to_meet,
            'visit_date'     => $v->visit_date?->toDateString(),
            'visit_date_formatted' => $v->visit_date?->format('d M Y'),
            'in_time'        => $v->in_time,
            'in_time_formatted'  => $v->in_time
                ? \Carbon\Carbon::parse($v->in_time)->format('h:i A')
                : null,
            'out_time'       => $v->out_time,
            'out_time_formatted' => $v->out_time
                ? \Carbon\Carbon::parse($v->out_time)->format('h:i A')
                : null,
            'status'         => $v->status,
            'remarks'        => $v->remarks,
            'created_at'     => $v->created_at?->toIso8601String(),
        ];
    }
}