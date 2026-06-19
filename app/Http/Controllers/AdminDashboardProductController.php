<?php

namespace App\Http\Controllers;

use App\Services\AdminDashboardService;
use App\Services\DataVisibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDashboardProductController extends Controller
{
    public function __construct(
        protected AdminDashboardService $service,
        private readonly DataVisibilityService $visibility
    ) {}



    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'nullable|integer|exists:products,id',
            'lead_id'    => 'nullable|integer|exists:leads,id',
            'branch_id'  => 'nullable|integer|exists:branches,id',
            'user_id'    => 'nullable|integer|exists:users,id',
            'source'     => 'nullable|string|max:100',
            'status'     => 'nullable|string|in:new,hot,cold,warm,converted,lost',
            'from_date'  => 'nullable|date_format:Y-m-d',
            'to_date'    => 'nullable|date_format:Y-m-d|after_or_equal:from_date',
        ]);

        $filters = $request->only([
            'product_id', 'lead_id', 'branch_id',
            'user_id', 'source', 'status',
            'from_date', 'to_date',
        ]);

        if (empty($filters['from_date']) && empty($filters['to_date'])) {
            $filters['from_date'] = now()->startOfMonth()->toDateString();
            $filters['to_date'] = now()->endOfMonth()->toDateString();
        }

        try {
            $data = $this->service->getDashboardData($filters);

            return response()->json([
                'status' => true,
                'data'   => $data,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load dashboard data.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * GET /api/admin/dashboard/filters
     * Returns dropdown options for filter selects.
     */
    public function filterOptions(Request $request): JsonResponse
    {
        try {
            $products = \App\Models\Product::query();
            $this->visibility->applyProductVisibility($products, $request->user());
            $products = $products->select('id', 'product_name')->orderBy('product_name')->get();
            $branches = $this->visibility->visibleBranches($request->user());
            $users    = $this->visibility->visibleAssignableUsers($request->user())->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
            ])->values();
            $sources  = $this->visibility->visibleLeadSources($request->user());

            return response()->json([
                'status' => true,
                'data'   => compact('products', 'branches', 'users', 'sources'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load filter options.',
            ], 500);
        }
    }
}
