<?php

namespace App\Http\Controllers\App\HRMS;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveTypeApiController extends Controller
{
    // ── GET /api/mobile/hrms/leave-types ──────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $leaveTypes = LeaveType::query()
            ->when($request->search, function ($q) use ($request) {
                $s = trim((string) $request->search);
                $q->where('name', 'like', "%$s%")
                  ->orWhere('description', 'like', "%$s%");
            })
            ->orderBy('name')
            ->get()
            ->map(fn (LeaveType $lt) => $this->mapLeaveType($lt));

        return response()->json([
            'success' => true,
            'data'    => ['leave_types' => $leaveTypes],
        ]);
    }

    // ── GET /api/mobile/hrms/leave-types/{id} ─────────────────────────────────
    public function show(LeaveType $leaveType): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->mapLeaveType($leaveType),
        ]);
    }

    // ── Private mapper ────────────────────────────────────────────────────────
    private function mapLeaveType(LeaveType $lt): array
    {
        return [
            'id'          => $lt->id,
            'name'        => $lt->name,
            'description' => $lt->description ?? '',
            'created_at'  => $lt->created_at?->format('Y-m-d'),
        ];
    }
}