<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssetEntryRequest;
use App\Http\Requests\UpdateAssetEntryRequest;
use App\Models\AssetCategory;
use App\Models\AssetEntry;
use App\Models\EmployeeOnboarding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'assetCategories' => AssetCategory::query()->orderBy('name')->get(['name', 'description']),
            'employees' => EmployeeOnboarding::query()->active()->orderBy('name')->get(['id', 'employee_id', 'name']),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function store(StoreAssetEntryRequest $request): RedirectResponse
    {
        $asset = new AssetEntry();
        $asset->fill($this->extractAttributes($request->validated()));
        $asset->asset_code = $this->generateNextAssetCode();
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

        return view('pages.hrms.assets.show', compact('asset'));
    }

    public function edit(AssetEntry $asset): View
    {
        return view('pages.hrms.assets.edit', [
            'asset' => $asset,
            'generatedAssetCode' => $asset->asset_code,
            'assetCategories' => AssetCategory::query()->orderBy('name')->get(['name', 'description']),
            'employees' => EmployeeOnboarding::query()->active()->orderBy('name')->get(['id', 'employee_id', 'name']),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function update(UpdateAssetEntryRequest $request, AssetEntry $asset): RedirectResponse
    {
        $asset->fill($this->extractAttributes($request->validated()));
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

    private function extractAttributes(array $validated): array
    {
        if (($validated['asset_status'] ?? null) !== 'assigned') {
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
        $latestAssetCode = AssetEntry::query()
            ->where('asset_code', 'like', 'AST%')
            ->orderByDesc('asset_code')
            ->value('asset_code');

        if ($latestAssetCode && preg_match('/^AST(\d+)$/', $latestAssetCode, $matches)) {
            return 'AST' . str_pad((string) (((int) $matches[1]) + 1), 4, '0', STR_PAD_LEFT);
        }

        return 'AST0001';
    }
}
