<?php

namespace App\Http\Controllers\App\HRMS;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\EmployeeFaceProfile;
use App\Models\EmployeeOnboarding;
use App\Services\FaceRecognitionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * HRMS-only "Face Registration" flow (ticket section 2): registering or
 * re-registering an employee's face is deliberately NOT part of the
 * self-service Face Attendance module (FaceAttendanceApiController) —
 * employees can never register or update their own face, only HR/Admin can,
 * on the employee's behalf. Every method here is gated the same way the
 * existing HRMS AttendanceApiController gates its HR-only views.
 */
class FaceRegistrationApiController extends Controller
{
    public function __construct(private readonly FaceRecognitionService $faceService)
    {
    }

    private function isCompanyAdmin($user): bool
    {
        return (bool) ($user && ($user->isSuperAdmin() || $user->isSystemAdmin() || $user->isCompanyAdmin()));
    }

    private function isHrOrAdmin(): bool
    {
        $user = auth()->user();

        return (bool) ($user && (
            $user->isSystemAdmin()
            || $user->belongsToHrDepartment()
            || $user->hasHrLikeRole()
            || $user->isCompanyAdmin()
            || $user->isBranchAdmin()
        ));
    }

    private function forbidden(): JsonResponse
    {
        return response()->json([
            'status'  => false,
            'message' => 'You do not have permission to manage face registrations.',
        ], 403);
    }

    /**
     * Employee picker for the Face Registration screen — every active
     * employee this HR/Admin can see, each flagged with whether they already
     * have a registered face. Branch Admins only see their own branch's
     * employees, mirroring AttendanceApiController::shouldFilterByBranch().
     */
    public function index(Request $request): JsonResponse
    {
        if (! $this->isHrOrAdmin()) {
            return $this->forbidden();
        }

        $user = auth()->user();

        $query = EmployeeOnboarding::query()
            ->where(function ($q) {
                $q->whereNull('portal_user_id')
                    ->orWhereHas('portalUser', fn ($uq) => $uq->where('is_active', true));
            })
            ->active()
            ->whereNotNull('name');

        if (! $this->isCompanyAdmin($user)) {
            $branchId = $user?->branch_id;
            if ($branchId) {
                $branch = Branch::find($branchId);
                $branchCode = $branch?->code;
                $query->where(function (Builder $sub) use ($branchId, $branchCode) {
                    $sub->whereHas('portalUser', fn (Builder $pu) => $pu->where('branch_id', $branchId));
                    if ($branchCode && $branchCode !== 'STS') {
                        $sub->orWhere(function (Builder $q2) use ($branchCode) {
                            $q2->whereNull('portal_user_id')->where('employee_id', 'like', $branchCode . '%');
                        });
                    }
                });
            }
        }

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where('name', 'like', "%{$search}%");
        }

        $employees = $query->orderBy('name')->limit(200)->get(['id', 'name', 'email']);

        $registeredIds = EmployeeFaceProfile::query()
            ->whereIn('employee_id', $employees->pluck('id'))
            ->pluck('registered_at', 'employee_id');

        return response()->json([
            'status'  => true,
            'message' => 'Employees fetched successfully.',
            'data'    => $employees->map(function (EmployeeOnboarding $employee) use ($registeredIds) {
                return [
                    'employee_id'    => $employee->id,
                    'name'           => $employee->name,
                    'email'          => $employee->email,
                    'face_registered' => $registeredIds->has($employee->id),
                    'registered_at'   => optional($registeredIds->get($employee->id))?->toIso8601String(),
                ];
            })->values(),
        ]);
    }

    /**
     * Registers (or, with force=1, re-registers) one employee's face from a
     * single captured photo. The underlying Python service accumulates 5
     * samples before it finalizes — the Flutter screen calls this once per
     * captured sample and keeps going while status is "pending".
     */
    public function register(Request $request): JsonResponse
    {
        if (! $this->isHrOrAdmin()) {
            return $this->forbidden();
        }

        $validator = Validator::make($request->all(), [
            'employee_id' => ['required'],
            'photo'       => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'force'       => ['nullable', 'in:0,1,true,false'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $empId = $request->input('employee_id');
        $employee = EmployeeOnboarding::query()->with('portalUser')->active()
            ->where(function ($q) use ($empId) {
                $q->where('id', $empId)
                  ->orWhere('employee_id', $empId);
            })
            ->first();
        if (! $employee) {
            return response()->json([
                'status'  => false,
                'message' => 'Employee not found or is inactive.',
            ], 404);
        }

        $user = auth()->user();
        if (! $this->isCompanyAdmin($user)) {
            $userBranchId = $user?->branch_id;
            $empBranchId  = $employee->portalUser?->branch_id;
            $branch       = $userBranchId ? Branch::find($userBranchId) : null;
            $branchCode   = $branch?->code;

            $matchesBranch = ($userBranchId && $empBranchId === $userBranchId)
                || ($branchCode && $branchCode !== 'STS' && is_null($employee->portal_user_id) && str_starts_with($employee->employee_id ?? '', $branchCode));

            if (! $matchesBranch) {
                return response()->json([
                    'status'  => false,
                    'message' => 'You do not have permission to register a face for an employee from another branch.',
                ], 403);
            }
        }

        $force = $request->boolean('force');

        // A plain (non-force) call for an employee who already has a face
        // profile is almost certainly a mistake in the calling screen, not a
        // deliberate re-registration — block it early with a clear message
        // instead of letting Python's own duplicate-guard reject it.
        $alreadyRegistered = EmployeeFaceProfile::query()->where('employee_id', $employee->id)->exists();
        if ($alreadyRegistered && ! $force) {
            return response()->json([
                'status'  => false,
                'message' => 'This employee already has a registered face. Re-register with force to replace it.',
            ], 422);
        }

        $result = $this->faceService->register(
            $request->file('photo'),
            (string) $employee->id,
            (string) $employee->name,
            $force,
        );

        if (! ($result['ok'] ?? false)) {
            return response()->json([
                'status'  => false,
                'message' => $result['message'] ?? 'Face registration failed. Please try again.',
            ], 422);
        }

        if ($result['status'] === 'pending') {
            return response()->json([
                'status'  => true,
                'pending' => true,
                'message' => $result['message'],
                'data'    => [
                    'captured_count' => $result['capturedCount'],
                    'required_count' => $result['requiredCount'],
                ],
            ], 202);
        }

        EmployeeFaceProfile::updateOrCreate(
            ['employee_id' => $employee->id],
            ['registered_by' => auth()->id(), 'registered_at' => now()],
        );

        return response()->json([
            'status'  => true,
            'pending' => false,
            'message' => 'Face registered successfully.',
            'data'    => ['employee_id' => $employee->id],
        ], 201);
    }
}
