<?php

namespace App\Http\Controllers\App\HRMS;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceLocationApiController extends Controller
{
    public function myBranch(Request $request): JsonResponse
    {
        $user = $request->user()->fresh(['branch']);

        if (! $user->branch_id || ! $user->branch) {
            return response()->json([
                'status'  => false,
                'message' => 'No branch is assigned to your account yet. Please contact your administrator.',
            ], 422);
        }

        $branch = $user->branch;

        return response()->json([
            'status'  => true,
            'message' => 'Branch location fetched successfully.',
            'data'    => [
                'employee_id'              => $user->employeeOnboarding?->id,
                'intern_id'                => $user->internJoiningForm?->id,
                'branch_id'                => $branch->id,
                'branch_name'              => $branch->name,
                'latitude'                 => $branch->latitude,
                'longitude'                => $branch->longitude,
                'attendance_radius_meters' => $branch->attendance_radius_meters ?? 50,
            ],
        ]);
    }
}
