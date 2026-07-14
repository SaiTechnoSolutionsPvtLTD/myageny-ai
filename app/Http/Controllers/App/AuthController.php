<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    // =========================================================================
    // REGISTER (Admin only)
    // =========================================================================

    #[OA\Post(
        path: "/api/mobile/auth/register",
        summary: "Register a new developer user (Admin only)",
        description: "Creates a new developer user account. Requires an authenticated admin user (super_admin role).",
        tags: ["Auth"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "email", "password", "password_confirmation"],
                properties: [
                    new OA\Property(property: "name",                  type: "string",  example: "Jane Developer"),
                    new OA\Property(property: "email",                 type: "string",  format: "email", example: "dev@myagenci.ai"),
                    new OA\Property(property: "password",              type: "string",  format: "password", example: "secret123"),
                    new OA\Property(property: "password_confirmation", type: "string",  format: "password", example: "secret123"),
                    new OA\Property(property: "branch_id",             type: "integer", nullable: true, example: 2),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "User registered successfully",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "status",  type: "boolean", example: true),
                    new OA\Property(property: "message", type: "string",  example: "Developer account created successfully."),
                    new OA\Property(property: "user",    ref: "#/components/schemas/AuthUser"),
                ])
            ),
            new OA\Response(
                response: 401,
                description: "Unauthenticated",
                content: new OA\JsonContent(ref: "#/components/schemas/UnauthenticatedResponse")
            ),
            new OA\Response(
                response: 403,
                description: "Forbidden — caller is not a super_admin",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            ),
            new OA\Response(
                response: 422,
                description: "Validation error",
                content: new OA\JsonContent(ref: "#/components/schemas/ValidationErrorResponse")
            ),
        ]
    )]
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
            'branch_id'             => ['nullable', 'integer', 'exists:branches,id'],
        ]);

        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'branch_id' => $request->branch_id,
            'is_active' => true,
        ]);

        // $user->assignRole('developer');

        return response()->json([
            'status'  => true,
            'message' => 'Developer account created successfully.',
            'user'    => $this->formatUser($user->load('employeeOnboarding')),
        ], 201);
    }

    // =========================================================================
    // LOGIN
    // =========================================================================

    #[OA\Post(
        path: "/api/mobile/auth/login",
        summary: "Mobile user login",
        description: "Authenticates a mobile user with email and password. Returns a Sanctum bearer token on success.",
        tags: ["Auth"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "password"],
                properties: [
                    new OA\Property(property: "email",       type: "string", format: "email",    example: "dev@myagenci.ai"),
                    new OA\Property(property: "password",    type: "string", format: "password", example: "secret123"),
                    new OA\Property(property: "device_name", type: "string", nullable: true,     example: "iPhone 15 Pro"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Login successful",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "status",     type: "boolean", example: true),
                    new OA\Property(property: "message",    type: "string",  example: "Login successful"),
                    new OA\Property(property: "token",      type: "string",  example: "1|abc123xyz..."),
                    new OA\Property(property: "token_type", type: "string",  example: "Bearer"),
                    new OA\Property(property: "user",       ref: "#/components/schemas/AuthUser"),
                ])
            ),
            new OA\Response(
                response: 401,
                description: "Invalid credentials",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            ),
            new OA\Response(
                response: 403,
                description: "Account inactive",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            ),
            new OA\Response(
                response: 422,
                description: "Validation error",
                content: new OA\JsonContent(ref: "#/components/schemas/ValidationErrorResponse")
            ),
            new OA\Response(
                response: 429,
                description: "Too many login attempts",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            ),
        ]
    )]
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'       => ['required', 'string', 'email'],
            'password'    => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $this->ensureIsNotRateLimited($request);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            RateLimiter::hit($this->throttleKey($request));

            return response()->json([
                'status'  => false,
                'message' => __('auth.failed'),
                'errors'  => ['email' => [__('auth.failed')]],
            ], 401);
        }

        RateLimiter::clear($this->throttleKey($request));

        if (! $user->is_active) {
            return response()->json([
                'status'  => false,
                'message' => 'Your account has been deactivated. Please contact your administrator.',
            ], 403);
        }

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        $deviceName = $request->input('device_name', 'mobile-app');
        $token      = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'status'     => true,
            'message'    => 'Login successful',
            'token'      => $token,
            'token_type' => 'Bearer',
            'user' => $this->formatUser($user->load(
                'employeeOnboarding.department',
                'employeeOnboarding.role',
                'roles.department',
                'internJoiningForm.department',
                'internJoiningForm.role',
            )),
        ]);
    }

    // =========================================================================
    // LOGOUT
    // =========================================================================

    #[OA\Post(
        path: "/api/mobile/auth/logout",
        summary: "Logout — revoke current token",
        description: "Revokes the bearer token used in this request.",
        security: [["sanctum" => []]],
        tags: ["Auth"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Logged out successfully",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "status",  type: "boolean", example: true),
                    new OA\Property(property: "message", type: "string",  example: "You have been signed out successfully."),
                ])
            ),
            new OA\Response(
                response: 401,
                description: "Unauthenticated",
                content: new OA\JsonContent(ref: "#/components/schemas/UnauthenticatedResponse")
            ),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status'  => true,
            'message' => 'You have been signed out successfully.',
        ]);
    }

    // =========================================================================
    // PROFILE
    // =========================================================================

    #[OA\Get(
        path: "/api/mobile/auth/me",
        summary: "Get authenticated user profile",
        description: "Returns the profile of the currently authenticated user, including their assigned role and branch.",
        security: [["sanctum" => []]],
        tags: ["Auth"],
        responses: [
            new OA\Response(
                response: 200,
                description: "User profile retrieved",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "status", type: "boolean", example: true),
                    new OA\Property(property: "data",   ref: "#/components/schemas/AuthUser"),
                ])
            ),
            new OA\Response(
                response: 401,
                description: "Unauthenticated",
                content: new OA\JsonContent(ref: "#/components/schemas/UnauthenticatedResponse")
            ),
        ]
    )]
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('branch', 'roles.department', 'employeeOnboarding.department', 'employeeOnboarding.role');

        return response()->json([
            'status' => true,
            'data'   => $this->formatUser($user),
        ]);
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->string('email')) . '|' . $request->ip());
    }

    protected function formatUser(User $user, mixed $activeBranchId = null): array
    {
        $emp    = $user->employeeOnboarding;
        $intern = $user->internJoiningForm;   // ← ADD

        $departmentRoute = null;
        if ($user->relationLoaded('roles') || $user->roles !== null) {
            $roles = $user->relationLoaded('roles')
                ? $user->roles->loadMissing('department')
                : $user->roles()->with('department')->get();

            $departmentRoute = $roles
                ->map(fn($role) => $role->department?->dashboard_route)
                ->filter()
                ->first();
        }

        $mobileRoute = match ($departmentRoute) {
            'hrms.dashboard'  => '/hrms-dashboard',
            'dashboard.admin' => '/',
            default           => null,
        };

        return [
            'id'              => $user->id,
            'name'            => $user->name,
            'email'           => $user->email,
            'role'            => $user->roles->first()?->name ?? null,
            'role_display'    => $user->role_display_name,
            'dashboard_route' => $mobileRoute,
            'is_active'       => $user->is_active,
            'branch_id'       => $activeBranchId ?? $user->branch_id,
            'branch' => $user->branch ? [
                'id'                       => $user->branch->id,
                'name'                     => $user->branch->name,
                'latitude'                 => $user->branch->latitude,
                'longitude'                => $user->branch->longitude,
                'latitude_2'               => 11.0364872,
                'longitude_2'              => 77.0161404,
                'attendance_radius_meters' => 50,
            ] : null,
            'last_login_at'   => $user->last_login_at?->toIso8601String(),
            'profile_photo'   => $user->photo ?? null,

            'employee' => $emp ? [
                'employee_id'     => $emp->id,
                'mobile'          => $emp->mobile,
                'date_of_birth'   => $emp->date_of_birth?->toDateString(),
                'blood_group'     => $emp->blood_group,
                'marital_status'  => $emp->marital_status,
                'date_of_joining' => $emp->salary_effective_from?->toDateString(),
                'gross_salary'    => $emp->gross_salary,
                'net_salary'      => $emp->net_salary,
                'status'          => $emp->status,
                'department'      => $emp->department ? [
                    'id'   => $emp->department->id,
                    'name' => $emp->department->name,
                ] : null,
                'designation'     => $emp->role ? [
                    'id'   => $emp->role->id,
                    'name' => $emp->role->name,
                ] : null,
            ] : null,

            // ── ADD intern block ──────────────────────────────────────────
            'intern' => $intern ? [
                'intern_id'       => $intern->id,
                'intern_code'     => $intern->intern_id,   // e.g. STSINT001
                'name'            => $intern->name,
                'mobile'          => $intern->mobile,
                'date_of_birth'   => $intern->date_of_birth?->toDateString(),
                'blood_group'     => $intern->blood_group,
                'marital_status'  => $intern->marital_status,
                'date_of_joining' => $intern->internship_start_date?->toDateString(),
                'date_of_ending'  => $intern->internship_end_date?->toDateString(),
                'status'          => $intern->internship_status,
                'department'      => $intern->department ? [
                    'id'   => $intern->department->id,
                    'name' => $intern->department->name,
                ] : null,
                'designation'     => $intern->role ? [
                    'id'   => $intern->role->id,
                    'name' => $intern->role->name,
                ] : null,
            ] : null,
        ];
    }
}
