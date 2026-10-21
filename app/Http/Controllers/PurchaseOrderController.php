<?php

namespace App\Http\Controllers;

use App\Models\InvoiceSetting;
use App\Models\ItemMaster;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SupplierMaster;
use App\Models\VendorMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public static array $indianStates = [
        '[33] Tamil Nadu'                            => 'Tamil Nadu',
        '[29] Karnataka'                             => 'Karnataka',
        '[32] Kerala'                                => 'Kerala',
        '[37] Andhra Pradesh'                        => 'Andhra Pradesh',
        '[36] Telangana'                             => 'Telangana',
        '[27] Maharashtra'                           => 'Maharashtra',
        '[07] Delhi'                                 => 'Delhi',
        '[24] Gujarat'                               => 'Gujarat',
        '[19] West Bengal'                           => 'West Bengal',
        '[09] Uttar Pradesh'                         => 'Uttar Pradesh',
        '[08] Rajasthan'                             => 'Rajasthan',
        '[06] Haryana'                               => 'Haryana',
        '[03] Punjab'                                => 'Punjab',
        '[10] Bihar'                                 => 'Bihar',
        '[21] Odisha'                                => 'Odisha',
        '[23] Madhya Pradesh'                        => 'Madhya Pradesh',
        '[22] Chhattisgarh'                          => 'Chhattisgarh',
        '[20] Jharkhand'                             => 'Jharkhand',
        '[05] Uttarakhand'                           => 'Uttarakhand',
        '[02] Himachal Pradesh'                      => 'Himachal Pradesh',
        '[01] Jammu & Kashmir'                       => 'Jammu & Kashmir',
        '[30] Goa'                                   => 'Goa',
        '[34] Puducherry'                            => 'Puducherry',
        '[04] Chandigarh'                            => 'Chandigarh',
        '[18] Assam'                                 => 'Assam',
        '[11] Sikkim'                                => 'Sikkim',
        '[12] Arunachal Pradesh'                     => 'Arunachal Pradesh',
        '[13] Nagaland'                              => 'Nagaland',
        '[14] Manipur'                               => 'Manipur',
        '[15] Mizoram'                               => 'Mizoram',
        '[16] Tripura'                               => 'Tripura',
        '[17] Meghalaya'                             => 'Meghalaya',
        '[26] Dadra and Nagar Haveli and Daman and Diu' => 'Dadra and Nagar Haveli and Daman and Diu',
        '[31] Lakshadweep'                           => 'Lakshadweep',
        '[35] Andaman and Nicobar Islands'           => 'Andaman and Nicobar Islands',
        '[38] Ladakh'                                => 'Ladakh',
        '[97] Other Territory / SEZ'                 => 'Other Territory / SEZ',
    ];

    /**
     * Display a listing of Purchase Orders.
     */
    public function index(Request $request)
    {
        $companyId = auth()->user()?->company_id;

        $query = PurchaseOrder::query()
            ->with(['supplier', 'creator'])
            ->forCompany($companyId)
            ->latest('po_date')
            ->latest('id');

        // Search
        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('po_number', 'LIKE', "%{$search}%")
                  ->orWhere('supplier_name', 'LIKE', "%{$search}%")
                  ->orWhere('company_name', 'LIKE', "%{$search}%")
                  ->orWhere('supplier_email', 'LIKE', "%{$search}%")
                  ->orWhere('supplier_phone', 'LIKE', "%{$search}%")
                  ->orWhere('supplier_gstin', 'LIKE', "%{$search}%");
            });
        }

        // Status Filter
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Date Range
        if ($from = $request->input('from_date')) {
            $query->whereDate('po_date', '>=', $from);
        }
        if ($to = $request->input('to_date')) {
            $query->whereDate('po_date', '<=', $to);
        }

        // KPI aggregates
        $baseQuery = PurchaseOrder::query()->forCompany($companyId);
        $allPos = (clone $baseQuery)->get();
        $stats = [
            'total_count'    => $allPos->count(),
            'total_amount'   => (float) $allPos->sum('total_amount'),
            'issued_count'   => $allPos->where('status', 'issued')->count(),
            'approved_count' => $allPos->where('status', 'approved')->count(),
            'received_count' => $allPos->where('status', 'received')->count(),
            'draft_count'    => $allPos->where('status', 'draft')->count(),
        ];

        $purchaseOrders = $query->paginate(15)->withQueryString();
        $hasActiveFilters = $request->filled('search') || $request->filled('status') || $request->filled('from_date') || $request->filled('to_date');

        return view('pages.accounts.purchase_orders.index', compact('purchaseOrders', 'stats', 'hasActiveFilters'));
    }

    /**
     * Show form to create a new Purchase Order.
     */
    public function create()
    {
        $companyId = auth()->user()?->company_id;

        $poNumber = PurchaseOrder::generateNextPoNumber($companyId);
        $setting  = InvoiceSetting::forCompany($companyId);

        // Fetch active suppliers from SupplierMaster and VendorMaster
        $suppliers = SupplierMaster::query()
            ->when($companyId, fn ($q) => $q->where(fn ($sq) => $sq->where('company_id', $companyId)->orWhereNull('company_id')))
            ->active()
            ->orderBy('supplier_name')
            ->get();

        // Also fetch from VendorMaster if any vendors exist
        $vendors = VendorMaster::query()
            ->when($companyId, fn ($q) => $q->where(fn ($vq) => $vq->where('company_id', $companyId)->orWhereNull('company_id')))
            ->active()
            ->orderBy('vendor_name')
            ->get();

        // Catalog Items from ItemMaster
        $items = ItemMaster::query()
            ->when($companyId, fn ($q) => $q->where(fn ($iq) => $iq->where('company_id', $companyId)->orWhereNull('company_id')))
            ->active()
            ->orderBy('item_name')
            ->get();

        $states = self::$indianStates;
        $defaultTerms = "1. Goods/Services must be delivered in accordance with the specifications in this Purchase Order.\n" .
                        "2. Invoices must quote this Purchase Order number.\n" .
                        "3. Payment will be released as per agreed payment terms after inspection and acceptance.\n" .
                        "4. Damaged or non-conforming items will be rejected and must be replaced at supplier's expense.";

        return view('pages.accounts.purchase_orders.create', compact(
            'poNumber',
            'setting',
            'suppliers',
            'vendors',
            'items',
            'states',
            'defaultTerms'
        ));
    }

    /**
     * Store a newly created Purchase Order.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id'        => 'nullable|integer',
            'supplier_name'      => 'required|string|max:255',
            'company_name'       => 'nullable|string|max:255',
            'supplier_email'     => 'nullable|email|max:255',
            'supplier_phone'     => 'nullable|string|max:50',
            'supplier_gstin'     => 'nullable|string|max:50',
            'supplier_pan'       => 'nullable|string|max:30',
            'supplier_address'   => 'nullable|string',
            'supplier_city'      => 'nullable|string|max:100',
            'supplier_state'     => 'nullable|string|max:100',
            'supplier_pincode'   => 'nullable|string|max:20',
            'supplier_country'   => 'nullable|string|max:100',

            'po_number'          => 'required|string|max:100',
            'po_date'            => 'required|date',
            'delivery_date'      => 'nullable|date',
            'payment_terms'      => 'nullable|string|max:100',
            'delivery_terms'     => 'nullable|string|max:150',
            'place_of_supply'    => 'nullable|string|max:100',

            // Shipping
            'shipping_attention' => 'nullable|string|max:255',
            'shipping_address'   => 'nullable|string',
            'shipping_city'      => 'nullable|string|max:100',
            'shipping_state'     => 'nullable|string|max:100',
            'shipping_pincode'   => 'nullable|string|max:20',
            'shipping_country'   => 'nullable|string|max:100',

            // Line Items
            'items'              => 'required|array|min:1',
            'items.*.item_id'    => 'nullable|integer',
            'items.*.item_name'  => 'required|string|max:255',
            'items.*.description'=> 'nullable|string',
            'items.*.hsn_sac_code'=> 'nullable|string|max:50',
            'items.*.qty'        => 'required|numeric|min:0.01',
            'items.*.rate'       => 'required|numeric|min:0',
            'items.*.amount'     => 'required|numeric|min:0',
            'items.*.cgst_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.cgst_amount'  => 'nullable|numeric|min:0',
            'items.*.sgst_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.sgst_amount'  => 'nullable|numeric|min:0',
            'items.*.igst_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.igst_amount'  => 'nullable|numeric|min:0',
            'items.*.total_amount' => 'nullable|numeric|min:0',

            // Calculations
            'subtotal'           => 'required|numeric|min:0',
            'total_cgst'         => 'nullable|numeric|min:0',
            'total_sgst'         => 'nullable|numeric|min:0',
            'total_igst'         => 'nullable|numeric|min:0',
            'total_tax'          => 'nullable|numeric|min:0',
            'round_off'          => 'nullable|numeric',
            'total_amount'       => 'required|numeric|min:0',

            'notes'              => 'nullable|string',
            'terms_conditions'   => 'nullable|string',
            'status'             => 'nullable|string|in:draft,issued,approved,received,cancelled',
        ]);

        $companyId = auth()->user()?->company_id;

        DB::beginTransaction();
        try {
            $poData = collect($validated)->except(['items'])->toArray();
            $poData['company_id'] = $companyId;
            $poData['status']     = $validated['status'] ?? 'issued';
            $poData['created_by'] = auth()->id();
            $poData['updated_by'] = auth()->id();

            $po = PurchaseOrder::create($poData);

            foreach ($validated['items'] as $index => $itemRow) {
                $itemRow['purchase_order_id'] = $po->id;
                $itemRow['sort_order']        = $index + 1;
                $itemRow['total_amount']      = !empty($itemRow['total_amount']) ? $itemRow['total_amount'] : ($itemRow['amount'] ?? 0);
                $itemRow['cgst_percent']      = $itemRow['cgst_percent'] ?? 0;
                $itemRow['cgst_amount']       = $itemRow['cgst_amount'] ?? 0;
                $itemRow['sgst_percent']      = $itemRow['sgst_percent'] ?? 0;
                $itemRow['sgst_amount']       = $itemRow['sgst_amount'] ?? 0;
                $itemRow['igst_percent']      = $itemRow['igst_percent'] ?? 0;
                $itemRow['igst_amount']       = $itemRow['igst_amount'] ?? 0;

                PurchaseOrderItem::create($itemRow);
            }

            DB::commit();

            return redirect()->route('accounts.purchase-orders.show', $po->id)
                             ->with('success', "Purchase Order #{$po->po_number} created successfully!");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Failed to create Purchase Order: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified Purchase Order.
     */
    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['items.itemMaster', 'supplier', 'creator', 'updater']);
        $companyId = $purchaseOrder->company_id ?: auth()->user()?->company_id;
        $setting   = InvoiceSetting::forCompany($companyId);

        return view('pages.accounts.purchase_orders.show', compact('purchaseOrder', 'setting'));
    }

    /**
     * Show form to edit the specified Purchase Order.
     */
    public function edit(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['items']);
        $companyId = auth()->user()?->company_id;

        $setting = InvoiceSetting::forCompany($companyId);

        $suppliers = SupplierMaster::query()
            ->when($companyId, fn ($q) => $q->where(fn ($sq) => $sq->where('company_id', $companyId)->orWhereNull('company_id')))
            ->active()
            ->orderBy('supplier_name')
            ->get();

        $vendors = VendorMaster::query()
            ->when($companyId, fn ($q) => $q->where(fn ($vq) => $vq->where('company_id', $companyId)->orWhereNull('company_id')))
            ->active()
            ->orderBy('vendor_name')
            ->get();

        $items = ItemMaster::query()
            ->when($companyId, fn ($q) => $q->where(fn ($iq) => $iq->where('company_id', $companyId)->orWhereNull('company_id')))
            ->active()
            ->orderBy('item_name')
            ->get();

        $states = self::$indianStates;

        return view('pages.accounts.purchase_orders.edit', compact(
            'purchaseOrder',
            'setting',
            'suppliers',
            'vendors',
            'items',
            'states'
        ));
    }

    /**
     * Update the specified Purchase Order in storage.
     */
    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validate([
            'supplier_id'        => 'nullable|integer',
            'supplier_name'      => 'required|string|max:255',
            'company_name'       => 'nullable|string|max:255',
            'supplier_email'     => 'nullable|email|max:255',
            'supplier_phone'     => 'nullable|string|max:50',
            'supplier_gstin'     => 'nullable|string|max:50',
            'supplier_pan'       => 'nullable|string|max:30',
            'supplier_address'   => 'nullable|string',
            'supplier_city'      => 'nullable|string|max:100',
            'supplier_state'     => 'nullable|string|max:100',
            'supplier_pincode'   => 'nullable|string|max:20',
            'supplier_country'   => 'nullable|string|max:100',

            'po_number'          => 'required|string|max:100',
            'po_date'            => 'required|date',
            'delivery_date'      => 'nullable|date',
            'payment_terms'      => 'nullable|string|max:100',
            'delivery_terms'     => 'nullable|string|max:150',
            'place_of_supply'    => 'nullable|string|max:100',

            'shipping_attention' => 'nullable|string|max:255',
            'shipping_address'   => 'nullable|string',
            'shipping_city'      => 'nullable|string|max:100',
            'shipping_state'     => 'nullable|string|max:100',
            'shipping_pincode'   => 'nullable|string|max:20',
            'shipping_country'   => 'nullable|string|max:100',

            'items'              => 'required|array|min:1',
            'items.*.item_id'    => 'nullable|integer',
            'items.*.item_name'  => 'required|string|max:255',
            'items.*.description'=> 'nullable|string',
            'items.*.hsn_sac_code'=> 'nullable|string|max:50',
            'items.*.qty'        => 'required|numeric|min:0.01',
            'items.*.rate'       => 'required|numeric|min:0',
            'items.*.amount'     => 'required|numeric|min:0',
            'items.*.cgst_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.cgst_amount'  => 'nullable|numeric|min:0',
            'items.*.sgst_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.sgst_amount'  => 'nullable|numeric|min:0',
            'items.*.igst_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.igst_amount'  => 'nullable|numeric|min:0',
            'items.*.total_amount' => 'nullable|numeric|min:0',

            'subtotal'           => 'required|numeric|min:0',
            'total_cgst'         => 'nullable|numeric|min:0',
            'total_sgst'         => 'nullable|numeric|min:0',
            'total_igst'         => 'nullable|numeric|min:0',
            'total_tax'          => 'nullable|numeric|min:0',
            'round_off'          => 'nullable|numeric',
            'total_amount'       => 'required|numeric|min:0',

            'notes'              => 'nullable|string',
            'terms_conditions'   => 'nullable|string',
            'status'             => 'nullable|string|in:draft,issued,approved,received,cancelled',
        ]);

        DB::beginTransaction();
        try {
            $poData = collect($validated)->except(['items'])->toArray();
            $poData['updated_by'] = auth()->id();

            $purchaseOrder->update($poData);

            // Re-sync items
            $purchaseOrder->items()->delete();
            foreach ($validated['items'] as $index => $itemRow) {
                $itemRow['purchase_order_id'] = $purchaseOrder->id;
                $itemRow['sort_order']        = $index + 1;
                $itemRow['total_amount']      = !empty($itemRow['total_amount']) ? $itemRow['total_amount'] : ($itemRow['amount'] ?? 0);
                $itemRow['cgst_percent']      = $itemRow['cgst_percent'] ?? 0;
                $itemRow['cgst_amount']       = $itemRow['cgst_amount'] ?? 0;
                $itemRow['sgst_percent']      = $itemRow['sgst_percent'] ?? 0;
                $itemRow['sgst_amount']       = $itemRow['sgst_amount'] ?? 0;
                $itemRow['igst_percent']      = $itemRow['igst_percent'] ?? 0;
                $itemRow['igst_amount']       = $itemRow['igst_amount'] ?? 0;

                PurchaseOrderItem::create($itemRow);
            }

            DB::commit();

            return redirect()->route('accounts.purchase-orders.show', $purchaseOrder->id)
                             ->with('success', "Purchase Order #{$purchaseOrder->po_number} updated successfully!");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Failed to update Purchase Order: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified Purchase Order.
     */
    public function destroy(PurchaseOrder $purchaseOrder)
    {
        $num = $purchaseOrder->po_number;
        $purchaseOrder->items()->delete();
        $purchaseOrder->delete();

        return redirect()->route('accounts.purchase-orders.index')
                         ->with('success', "Purchase Order #{$num} deleted successfully.");
    }

    /**
     * Print layout for Purchase Order.
     */
    public function print(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['items.itemMaster', 'supplier', 'creator']);
        $companyId = $purchaseOrder->company_id ?: auth()->user()?->company_id;
        $setting   = InvoiceSetting::forCompany($companyId);

        return view('pages.accounts.purchase_orders.print', compact('purchaseOrder', 'setting'));
    }

    /**
     * Update PO Status.
     */
    public function updateStatus(Request $request, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:draft,issued,approved,received,cancelled',
        ]);

        $purchaseOrder->update([
            'status'     => $validated['status'],
            'updated_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', "PO #{$purchaseOrder->po_number} status updated to " . ucfirst($validated['status']) . ".");
    }
}
