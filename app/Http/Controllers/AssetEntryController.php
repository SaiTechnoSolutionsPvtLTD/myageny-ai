<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssetEntryRequest;
use App\Http\Requests\UpdateAssetEntryRequest;
use App\Models\AssetCategory;
use App\Models\AssetEntry;
use App\Models\EmployeeOnboarding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AssetEntryController extends Controller
{
    public function index(Request $request): View
    {
        $assets = AssetEntry::query()
            ->with('assignedEmployee')
            ->when($request->search, function ($query) use ($request) {
                $search = trim((string) $request->search);

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('asset_code', 'like', '%' . $search . '%')
                        ->orWhere('asset_name', 'like', '%' . $search . '%')
                        ->orWhere('brand', 'like', '%' . $search . '%')
                        ->orWhere('model_name', 'like', '%' . $search . '%')
                        ->orWhere('serial_number', 'like', '%' . $search . '%')
                        ->orWhere('vendor_name', 'like', '%' . $search . '%')
                        ->orWhere('location', 'like', '%' . $search . '%');
                });
            })
            ->when($request->asset_category, fn ($query) => $query->where('asset_category', $request->asset_category))
            ->when($request->asset_status, fn ($query) => $query->where('asset_status', $request->asset_status))
            ->when($request->assigned_employee_id, fn ($query) => $query->where('assigned_employee_id', $request->assigned_employee_id))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('pages.hrms.assets.index', [
            'assets' => $assets,
            'categories' => AssetCategory::query()->orderBy('name')->pluck('name'),
            'employees' => EmployeeOnboarding::query()->active()->orderBy('name')->get(['id', 'employee_id', 'name']),
            'stats' => [
                'total' => AssetEntry::count(),
                'assigned' => AssetEntry::where('asset_status', 'assigned')->count(),
                'available' => AssetEntry::where('asset_status', 'available')->count(),
                'in_service' => AssetEntry::where('asset_status', 'in_service')->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('pages.hrms.assets.create', [
            'asset' => null,
            'generatedAssetCode' => $this->generateNextAssetCode(),
            'assetCategories' => AssetCategory::query()->orderBy('name')->get(['id', 'name', 'description', 'custom_fields']),
            'employees' => EmployeeOnboarding::query()->active()->orderBy('name')->get(['id', 'employee_id', 'name']),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function store(StoreAssetEntryRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $customValues = $this->processCustomFieldValues($request);

        $assetCode = $this->generateNextAssetCode();

        $assetName = trim((string) ($validated['asset_name'] ?? ''));
        if ($assetName === '') {
            $categoryName = $validated['asset_category'] ?? 'Asset';
            $nameCandidate = $customValues['sim_name'] ?? $customValues['asset_name'] ?? $customValues['name'] ?? $customValues['host_name'] ?? null;
            if ($nameCandidate && is_string($nameCandidate) && trim($nameCandidate) !== '') {
                $assetName = trim($nameCandidate) . ' (' . $assetCode . ')';
            } else {
                $assetName = $categoryName . ' (' . $assetCode . ')';
            }
        }

        $attributes = $this->extractAttributes($validated);

        $asset = new AssetEntry();
        $asset->fill($attributes);
        $asset->asset_name = $assetName;
        $asset->asset_code = $assetCode;
        $asset->custom_field_values = $customValues;
        $asset->created_by = auth()->id();
        $asset->updated_by = auth()->id();
        $asset->save();

        return redirect()
            ->route('assets.show', $asset)
            ->with('success', "Asset <strong>{$asset->asset_name}</strong> created successfully.");
    }

    public function show(AssetEntry $asset): View
    {
        $asset->load(['assignedEmployee', 'creator', 'updater']);
        $category = AssetCategory::where('name', $asset->asset_category)->first();

        return view('pages.hrms.assets.show', compact('asset', 'category'));
    }

    public function edit(AssetEntry $asset): View
    {
        return view('pages.hrms.assets.edit', [
            'asset' => $asset,
            'generatedAssetCode' => $asset->asset_code,
            'assetCategories' => AssetCategory::query()->orderBy('name')->get(['id', 'name', 'description', 'custom_fields']),
            'employees' => EmployeeOnboarding::query()->active()->orderBy('name')->get(['id', 'employee_id', 'name']),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function update(UpdateAssetEntryRequest $request, AssetEntry $asset): RedirectResponse
    {
        $validated = $request->validated();
        $customValues = $this->processCustomFieldValues($request, $asset);

        $assetName = trim((string) ($validated['asset_name'] ?? ''));
        if ($assetName === '') {
            $categoryName = $validated['asset_category'] ?? ($asset->asset_category ?: 'Asset');
            $nameCandidate = $customValues['sim_name'] ?? $customValues['asset_name'] ?? $customValues['name'] ?? $customValues['host_name'] ?? null;
            if ($nameCandidate && is_string($nameCandidate) && trim($nameCandidate) !== '') {
                $assetName = trim($nameCandidate) . ' (' . $asset->asset_code . ')';
            } else {
                $assetName = $categoryName . ' (' . $asset->asset_code . ')';
            }
        }

        $attributes = $this->extractAttributes($validated, $asset);

        $asset->fill($attributes);
        $asset->asset_name = $assetName;
        $asset->custom_field_values = $customValues;
        $asset->updated_by = auth()->id();
        $asset->save();

        return redirect()
            ->route('assets.show', $asset)
            ->with('success', "Asset <strong>{$asset->asset_name}</strong> updated successfully.");
    }

    public function destroy(AssetEntry $asset): RedirectResponse
    {
        $assetName = $asset->asset_name;
        $asset->delete();

        return redirect()
            ->route('assets.index')
            ->with('success', "Asset <strong>{$assetName}</strong> deleted successfully.");
    }

    private function processCustomFieldValues(Request $request, ?AssetEntry $existingAsset = null): array
    {
        $customValues = $request->input('custom_fields', []);
        if (! is_array($customValues)) {
            $customValues = [];
        }

        // Retain existing files if not re-uploaded
        $existingValues = $existingAsset?->custom_field_values ?? [];
        if (is_array($existingValues)) {
            foreach ($existingValues as $k => $v) {
                if (! array_key_exists($k, $customValues) && ! is_null($v)) {
                    $customValues[$k] = $v;
                }
            }
        }

        // Handle file uploads in custom_files
        if ($request->hasFile('custom_files')) {
            $destinationPath = public_path('uploads/assets/custom_fields');
            if (! file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            foreach ($request->file('custom_files') as $fieldName => $file) {
                if ($file && $file->isValid()) {
                    $filename = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
                    $file->move($destinationPath, $filename);
                    $customValues[$fieldName] = 'uploads/assets/custom_fields/' . $filename;
                }
            }
        }

        return $customValues;
    }

    private function extractAttributes(array $validated, ?AssetEntry $existingAsset = null): array
    {
        unset($validated['custom_fields'], $validated['custom_files']);

        $hasEmployee = ! empty($validated['assigned_employee_id']);

        if ($hasEmployee) {
            $validated['asset_status'] = $validated['asset_status'] ?? 'assigned';
            if (empty($validated['assigned_date'])) {
                $validated['assigned_date'] = now()->toDateString();
            }
        } else {
            $currentStatus = $validated['asset_status'] ?? ($existingAsset?->asset_status ?: 'available');
            if ($currentStatus === 'assigned') {
                $currentStatus = 'available';
            }
            $validated['asset_status'] = $currentStatus;
            $validated['assigned_employee_id'] = null;
            $validated['assigned_date'] = null;
        }

        return $validated;
    }

    private function statusOptions(): array
    {
        return [
            'available' => 'Available',
            'assigned' => 'Assigned',
            'in_service' => 'In Service',
            'damaged' => 'Damaged',
            'retired' => 'Retired',
        ];
    }

    private function generateNextAssetCode(): string
    {
        $maxNum = AssetEntry::query()
            ->where('asset_code', 'like', 'AST%')
            ->selectRaw('MAX(CAST(SUBSTRING(asset_code, 4) AS UNSIGNED)) as max_code')
            ->value('max_code');

        $nextNum = ($maxNum ? (int) $maxNum : 0) + 1;

        return 'AST' . str_pad((string) $nextNum, 4, '0', STR_PAD_LEFT);
    }
}
