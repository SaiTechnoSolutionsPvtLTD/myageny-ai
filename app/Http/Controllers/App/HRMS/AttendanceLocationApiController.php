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
            'status' => true,
            'data'   => [
                'branch_id' => $branch->id,
                'name'      => $branch->name,
                'latitude'  => (float) $branch->latitude,
                'longitude' => (float) $branch->longitude,
                'radius_m'                 => (int) ($branch->attendance_radius_meters ?? config('hrms.attendance_radius_meters', 50)),
                'attendance_radius_meters' => (float) ($branch->attendance_radius_meters ?? config('hrms.attendance_radius_meters', 50)),
                'address'                  => $branch->address,
            ],
        ]);
    }
}
