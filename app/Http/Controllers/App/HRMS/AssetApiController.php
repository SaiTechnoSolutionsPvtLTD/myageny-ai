<?php

namespace App\Http\Controllers\App\HRMS;

use App\Http\Controllers\Controller;
use App\Models\AssetEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetApiController extends Controller
{
    // ── GET /api/mobile/hrms/assets ───────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $query = AssetEntry::query()
            ->with('assignedEmployee')
            ->when($request->search, function ($q) use ($request) {
                $s = trim((string) $request->search);
                $q->where(function ($sub) use ($s) {
                    $sub->where('asset_code',     'like', "%$s%")
                        ->orWhere('asset_name',   'like', "%$s%")
                        ->orWhere('brand',         'like', "%$s%")
                        ->orWhere('model_name',    'like', "%$s%")
                        ->orWhere('serial_number', 'like', "%$s%")
                        ->orWhere('vendor_name',   'like', "%$s%")
                        ->orWhere('location',      'like', "%$s%");
                });
            })
            ->when($request->asset_status,   fn ($q) => $q->where('asset_status',   $request->asset_status))
            ->when($request->asset_category, fn ($q) => $q->where('asset_category', $request->asset_category))
            ->latest();

        $perPage = (int) ($request->per_page ?? 15);
        $assets  = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => [
                'assets'     => $assets->map(fn ($a) => $this->mapList($a)),
                'pagination' => [
                    'current_page' => $assets->currentPage(),
                    'last_page'    => $assets->lastPage(),
                    'per_page'     => $assets->perPage(),
                    'total'        => $assets->total(),
                    'has_more'     => $assets->hasMorePages(),
                ],
            ],
        ]);
    }

    // ── GET /api/mobile/hrms/assets/{id} ──────────────────────────────────────
    public function show(int $id): JsonResponse
    {
        $asset = AssetEntry::with(['assignedEmployee', 'creator', 'updater'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $this->mapDetail($asset),
        ]);
    }

    // ── GET /api/mobile/hrms/assets/meta ──────────────────────────────────────
    public function meta(): JsonResponse
    {
        $categories = AssetEntry::query()
            ->whereNotNull('asset_category')
            ->distinct()
            ->orderBy('asset_category')
            ->pluck('asset_category');

        $statuses = ['available', 'assigned', 'in_service', 'damaged', 'retired'];

        $stats = [
            'total'      => AssetEntry::count(),
            'assigned'   => AssetEntry::where('asset_status', 'assigned')->count(),
            'available'  => AssetEntry::where('asset_status', 'available')->count(),
            'in_service' => AssetEntry::where('asset_status', 'in_service')->count(),
            'damaged'    => AssetEntry::where('asset_status', 'damaged')->count(),
            'retired'    => AssetEntry::where('asset_status', 'retired')->count(),
        ];

        return response()->json([
            'success' => true,
            'data'    => [
                'categories' => $categories,
                'statuses'   => $statuses,
                'stats'      => $stats,
            ],
        ]);
    }

    // ── Private: list shape (compact) ─────────────────────────────────────────
    private function mapList(AssetEntry $a): array
    {
        return [
            'id'                     => $a->id,
            'asset_code'             => $a->asset_code             ?? '',
            'asset_name'             => $a->asset_name             ?? '',
            'asset_category'         => $a->asset_category         ?? '',
            'brand'                  => $a->brand                  ?? '',
            'model_name'             => $a->model_name             ?? '',
            'serial_number'          => $a->serial_number          ?? '',
            'asset_status'           => $a->asset_status           ?? 'available',
            'location'               => $a->location               ?? '',
            'assigned_employee_name' => optional($a->assignedEmployee)->name,
            'assigned_employee_id'   => optional($a->assignedEmployee)->employee_id,
            'avatar_initial'         => strtoupper(substr($a->asset_name ?? 'A', 0, 1)),
            'created_at'             => optional($a->created_at)->format('d M Y'),
        ];
    }

    // ── Private: detail shape (full) ──────────────────────────────────────────
    private function mapDetail(AssetEntry $a): array
    {
        return [
            'id'                     => $a->id,
            'asset_code'             => $a->asset_code             ?? '',
            'asset_name'             => $a->asset_name             ?? '',
            'asset_category'         => $a->asset_category         ?? '',
            'brand'                  => $a->brand                  ?? '',
            'model_name'             => $a->model_name             ?? '',
            'serial_number'          => $a->serial_number          ?? '',
            'asset_status'           => $a->asset_status           ?? 'available',
            'location'               => $a->location               ?? '',
            'avatar_initial'         => strtoupper(substr($a->asset_name ?? 'A', 0, 1)),

            // Purchase
            'purchase_date'          => optional($a->purchase_date)->format('d M Y') ?? '',
            'purchase_cost'          => $a->purchase_cost          ? (float) $a->purchase_cost : null,
            'vendor_name'            => $a->vendor_name            ?? '',
            'invoice_number'         => $a->invoice_number         ?? '',
            'warranty_expiry_date'   => optional($a->warranty_expiry_date)->format('d M Y') ?? '',

            // Assignment
            'assigned_employee_name' => optional($a->assignedEmployee)->name      ?? '',
            'assigned_employee_id'   => optional($a->assignedEmployee)->employee_id ?? '',
            'assigned_date'          => optional($a->assigned_date)->format('d M Y') ?? '',

            // Notes
            'condition_notes'        => $a->condition_notes        ?? '',
            'description'            => $a->description            ?? '',

            'created_at'             => optional($a->created_at)->format('d M Y'),
        ];
    }
}