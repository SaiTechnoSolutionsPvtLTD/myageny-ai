<?php

namespace App\Http\Controllers\App\HRMS;

use App\Http\Controllers\Controller;
use App\Models\AssetCategory;
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
        $customValues = $a->custom_field_values ?? [];
        if (is_string($customValues)) {
            $customValues = json_decode($customValues, true) ?: [];
        }
        $brand = $a->brand ?: ($customValues['brand'] ?? $customValues['sim_brand'] ?? '');
        $categoryOrName = $a->asset_category ?: $a->asset_name;
        $avatarInitial = strtoupper(substr(trim((string)$categoryOrName), 0, 2));

        return [
            'id'                     => $a->id,
            'asset_code'             => $a->asset_code             ?? '',
            'asset_name'             => $a->asset_name             ?? '',
            'asset_category'         => $a->asset_category         ?? '',
            'brand'                  => $brand,
            'model_name'             => $a->model_name             ?? '',
            'serial_number'          => $a->serial_number          ?? '',
            'asset_status'           => $a->asset_status           ?? 'available',
            'location'               => $a->location               ?? '',
            'assigned_employee_name' => optional($a->assignedEmployee)->name,
            'assigned_employee_id'   => optional($a->assignedEmployee)->employee_id,
            'avatar_initial'         => $avatarInitial ?: 'AS',
            'created_at'             => optional($a->created_at)->format('d M Y'),
        ];
    }

    // ── Private: detail shape (full) ──────────────────────────────────────────
    private function mapDetail(AssetEntry $a): array
    {
        $category = AssetCategory::where('name', $a->asset_category)->first();
        $customFields = $category?->custom_fields ?? [];
        if (is_string($customFields)) {
            $customFields = json_decode($customFields, true) ?: [];
        }
        $customValues = $a->custom_field_values ?? [];
        if (is_string($customValues)) {
            $customValues = json_decode($customValues, true) ?: [];
        }

        // Build specifications matching web display logic
        $specifications = [];
        if (!empty($customFields)) {
            foreach ($customFields as $cf) {
                $cfName = $cf['name'] ?? '';
                $cfVal = $customValues[$cfName] ?? null;
                $cfType = $cf['type'] ?? 'text';
                $label = $cf['label'] ?? ucwords(str_replace('_', ' ', $cfName));

                $displayVal = '—';
                $fileUrl = null;
                if ($cfType === 'file' && !empty($cfVal)) {
                    $fileUrl = asset($cfVal);
                    $displayVal = 'View Attached File';
                } elseif ($cfType === 'checkbox') {
                    if (is_array($cfVal)) {
                        $displayVal = implode(', ', $cfVal);
                    } elseif (!empty($cfVal) && $cfVal != '0') {
                        $displayVal = '✓ Yes';
                    } else {
                        $displayVal = 'No';
                    }
                } elseif ($cfType === 'date' && !empty($cfVal)) {
                    try {
                        $displayVal = \Carbon\Carbon::parse($cfVal)->format('d M Y');
                    } catch (\Throwable $e) {
                        $displayVal = (string) $cfVal;
                    }
                } elseif (!empty($cfVal)) {
                    $displayVal = is_array($cfVal) ? implode(', ', $cfVal) : (string) $cfVal;
                }

                $specifications[] = [
                    'name'          => $cfName,
                    'label'         => $label,
                    'type'          => $cfType,
                    'value'         => $cfVal,
                    'display_value' => $displayVal,
                    'file_url'      => $fileUrl,
                ];
            }
        } elseif (!empty($customValues)) {
            foreach ($customValues as $k => $v) {
                $isUpload = is_string($v) && (str_starts_with($v, 'uploads/') || str_starts_with($v, '/uploads/'));
                $specifications[] = [
                    'name'          => $k,
                    'label'         => ucwords(str_replace('_', ' ', $k)),
                    'type'          => $isUpload ? 'file' : 'text',
                    'value'         => $v,
                    'display_value' => $isUpload ? 'View Attached File' : (is_array($v) ? implode(', ', $v) : ($v ?: '—')),
                    'file_url'      => $isUpload ? asset($v) : null,
                ];
            }
        }

        // Brand & model fallback
        $brand = $a->brand ?: ($customValues['brand'] ?? $customValues['sim_brand'] ?? '');
        $model = $a->model_name ?: ($customValues['model'] ?? $customValues['model_name'] ?? '');

        // Avatar initial matching web: category first, then asset_name
        $categoryOrName = $a->asset_category ?: $a->asset_name;
        $avatarInitial = strtoupper(substr(trim((string)$categoryOrName), 0, 2));

        // Employee avatar initial
        $empInitial = '';
        if ($a->assignedEmployee && !empty($a->assignedEmployee->name)) {
            $empInitial = strtoupper(substr(trim($a->assignedEmployee->name), 0, 2));
        }

        return [
            'id'                       => $a->id,
            'asset_code'               => $a->asset_code               ?? '',
            'asset_name'               => $a->asset_name               ?? '',
            'asset_category'           => $a->asset_category           ?? '',
            'brand'                    => $brand,
            'model_name'               => $model,
            'serial_number'            => $a->serial_number            ?? '',
            'asset_status'             => $a->asset_status             ?? 'available',
            'location'                 => $a->location                 ?? '',
            'avatar_initial'           => $avatarInitial ?: 'AS',

            // Created Info
            'created_by'               => $a->creator?->name ?? 'System',
            'created_at'               => optional($a->created_at)->format('d M Y') ?? '',

            // Specifications & Custom Fields
            'custom_fields'            => $customFields,
            'custom_field_values'      => $customValues,
            'specifications'           => $specifications,

            // Purchase
            'purchase_date'            => optional($a->purchase_date)->format('d M Y') ?? '',
            'purchase_cost'            => $a->purchase_cost            ? (float) $a->purchase_cost : null,
            'vendor_name'              => $a->vendor_name              ?? '',
            'invoice_number'           => $a->invoice_number           ?? '',
            'warranty_expiry_date'     => optional($a->warranty_expiry_date)->format('d M Y') ?? '',

            // Assignment
            'assigned_employee_name'   => optional($a->assignedEmployee)->name        ?? '',
            'assigned_employee_id'     => optional($a->assignedEmployee)->employee_id  ?? '',
            'assigned_employee_avatar' => $empInitial,
            'assigned_date'            => optional($a->assigned_date)->format('d M Y') ?? '',

            // Notes
            'condition_notes'          => $a->condition_notes          ?? '',
            'description'              => $a->description              ?? '',
        ];
    }
}