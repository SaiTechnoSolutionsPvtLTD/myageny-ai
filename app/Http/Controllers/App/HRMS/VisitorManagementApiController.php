<?php

namespace App\Http\Controllers\App\HRMS;

use App\Http\Controllers\Controller;
use App\Models\VisitorEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VisitorManagementApiController extends Controller
{
    // ── Authorization helper ──────────────────────────────────────────────
    private function authorizeAccess(): void
    {
        $user = auth()->user();
        $allowed = $user && (
            $user->can('visitor_management.menuview')
            || $user->isSystemAdmin()
            || $user->isCompanyAdmin()
            || $user->isBranchAdmin()
            || $user->hasHrLikeRole()
            || $user->belongsToHrDepartment()
        );

        if (! $allowed) {
            abort(403, 'Unauthorized access to Visitor Management.');
        }
    }

    // ── GET /api/mobile/hrms/visitor-management ────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $this->authorizeAccess();

        $query = VisitorEntry::query()
            ->when($request->search, function ($q) use ($request) {
                $s = trim((string) $request->search);
                $q->where(function ($sub) use ($s) {
                    $sub->where('visitor_name',   'like', "%{$s}%")
                        ->orWhere('mobile_number', 'like', "%{$s}%")
                        ->orWhere('person_to_meet','like', "%{$s}%")
                        ->orWhere('email',          'like', "%{$s}%")
                        ->orWhere('applied_position','like', "%{$s}%")
                        ->orWhere('company_name',   'like', "%{$s}%");
                });
            })
            ->when($request->visitor_type, fn ($q) => $q->where('visitor_type', $request->visitor_type))
            ->when($request->visit_date,   fn ($q) => $q->whereDate('visit_date', $request->visit_date))
            ->when($request->status,       fn ($q) => $q->where('status', $request->status))
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

    // ── GET /api/mobile/hrms/visitor-management/qr-code ────────────────────
    public function qrCode(): JsonResponse
    {
        $this->authorizeAccess();

        $visitorFormUrl = route('visitor-entry.create');
        $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=320x320&margin=16&data=' . urlencode($visitorFormUrl);

        return response()->json([
            'success' => true,
            'data'    => [
                'visitor_form_url' => $visitorFormUrl,
                'qr_code_url'      => $qrCodeUrl,
            ],
        ]);
    }

    // ── POST /api/mobile/hrms/visitor-management ───────────────────────────
    public function store(Request $request): JsonResponse
    {
        $this->authorizeAccess();

        $validated = $request->validate([
            'visitor_name'       => 'required|string|max:150',
            'visitor_type'       => ['required', 'string', Rule::in([VisitorEntry::TYPE_CANDIDATE, VisitorEntry::TYPE_CLIENT, VisitorEntry::TYPE_OTHERS])],
            'mobile_number'      => 'required|string|max:30',
            'email'              => ['nullable', Rule::requiredIf($request->visitor_type === VisitorEntry::TYPE_CANDIDATE), 'email', 'max:150'],
            'applied_position'   => ['nullable', Rule::requiredIf($request->visitor_type === VisitorEntry::TYPE_CANDIDATE), 'string', 'max:150'],
            'company_name'       => ['nullable', Rule::requiredIf($request->visitor_type === VisitorEntry::TYPE_CLIENT), 'string', 'max:150'],
            'other_visitor_type' => ['nullable', Rule::requiredIf($request->visitor_type === VisitorEntry::TYPE_OTHERS), 'string', 'max:150'],
            'person_to_meet'     => 'required|string|max:150',
            'visit_date'         => 'required|date',
            'in_time'            => 'required|date_format:H:i',
            'out_time'           => 'nullable|date_format:H:i|after_or_equal:in_time',
            'remarks'            => 'required|string|max:1000',
        ]);

        $visitor = VisitorEntry::create($this->payload($validated));

        return response()->json([
            'success' => true,
            'message' => 'Visitor entry created successfully.',
            'data'    => $this->resource($visitor),
        ], 201);
    }

    // ── GET /api/mobile/hrms/visitor-management/{id} ───────────────────────
    public function show(int $id): JsonResponse
    {
        $this->authorizeAccess();

        $visitor = VisitorEntry::findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $this->resource($visitor),
        ]);
    }

    // ── PUT /api/mobile/hrms/visitor-management/{id} ──────────────────────
    public function update(Request $request, int $id): JsonResponse
    {
        $this->authorizeAccess();

        $visitor = VisitorEntry::findOrFail($id);
        $type = $request->visitor_type ?? $visitor->visitor_type;

        $validated = $request->validate([
            'visitor_name'       => 'sometimes|required|string|max:150',
            'visitor_type'       => ['sometimes', 'required', 'string', Rule::in([VisitorEntry::TYPE_CANDIDATE, VisitorEntry::TYPE_CLIENT, VisitorEntry::TYPE_OTHERS])],
            'mobile_number'      => 'sometimes|required|string|max:30',
            'email'              => ['nullable', Rule::requiredIf($type === VisitorEntry::TYPE_CANDIDATE), 'email', 'max:150'],
            'applied_position'   => ['nullable', Rule::requiredIf($type === VisitorEntry::TYPE_CANDIDATE), 'string', 'max:150'],
            'company_name'       => ['nullable', Rule::requiredIf($type === VisitorEntry::TYPE_CLIENT), 'string', 'max:150'],
            'other_visitor_type' => ['nullable', Rule::requiredIf($type === VisitorEntry::TYPE_OTHERS), 'string', 'max:150'],
            'person_to_meet'     => 'sometimes|required|string|max:150',
            'visit_date'         => 'sometimes|required|date',
            'in_time'            => 'sometimes|required|date_format:H:i',
            'out_time'           => 'nullable|date_format:H:i',
            'remarks'            => 'sometimes|required|string|max:1000',
        ]);

        $visitor->update($this->payload($validated, $visitor));

        return response()->json([
            'success' => true,
            'message' => 'Visitor entry updated successfully.',
            'data'    => $this->resource($visitor->fresh()),
        ]);
    }

    // ── DELETE /api/mobile/hrms/visitor-management/{id} ───────────────────
    public function destroy(int $id): JsonResponse
    {
        $this->authorizeAccess();

        $visitor = VisitorEntry::findOrFail($id);
        $visitor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Visitor entry deleted successfully.',
        ]);
    }

    // ── Payload sanitizer ──────────────────────────────────────────────────
    private function payload(array $validated, ?VisitorEntry $existing = null): array
    {
        $outTime = array_key_exists('out_time', $validated)
            ? $validated['out_time']
            : ($existing?->out_time);

        $validated['status'] = VisitorEntry::statusFor($outTime);

        $type = $validated['visitor_type'] ?? $existing?->visitor_type;

        if ($type !== VisitorEntry::TYPE_CANDIDATE) {
            $validated['email'] = null;
            $validated['applied_position'] = null;
        }

        if ($type !== VisitorEntry::TYPE_CLIENT) {
            $validated['company_name'] = null;
        }

        if ($type !== VisitorEntry::TYPE_OTHERS) {
            $validated['other_visitor_type'] = null;
        }

        return $validated;
    }

    // ── Private resource serialiser ────────────────────────────────────────
    private function resource(VisitorEntry $v): array
    {
        return [
            'id'                 => $v->id,
            'visitor_name'       => $v->visitor_name,
            'visitor_type'       => $v->visitor_type,
            'visitor_type_label' => $v->visitor_type_label,
            'mobile_number'      => $v->mobile_number,
            'email'              => $v->email,
            'applied_position'   => $v->applied_position,
            'company_name'       => $v->company_name,
            'other_visitor_type' => $v->other_visitor_type,
            'person_to_meet'     => $v->person_to_meet,
            'visit_date'         => $v->visit_date?->toDateString(),
            'visit_date_formatted' => $v->visit_date?->format('d M Y'),
            'in_time'            => $v->in_time,
            'in_time_formatted'  => $v->in_time
                ? \Carbon\Carbon::parse($v->in_time)->format('h:i A')
                : null,
            'out_time'           => $v->out_time,
            'out_time_formatted' => $v->out_time
                ? \Carbon\Carbon::parse($v->out_time)->format('h:i A')
                : null,
            'status'             => $v->status,
            'remarks'            => $v->remarks,
            'created_at'         => $v->created_at?->toIso8601String(),
        ];
    }
}