<?php

namespace App\Http\Controllers;

use App\Models\CustomerMaster;
use App\Models\Department;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceSetting;
use App\Models\ItemMaster;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    /**
     * List of Indian States & Union Territories with GST State Codes.
     */
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
     * Display a listing of invoices with KPIs, filters, and pagination.
     */
    public function index(Request $request)
    {
        $companyId = auth()->user()?->company_id;

        $query = Invoice::query()->with(['customer', 'salesPerson']);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        // Search by Invoice #, Customer Name, Mobile, Email
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'LIKE', "%{$search}%")
                  ->orWhere('customer_name', 'LIKE', "%{$search}%")
                  ->orWhere('company_name', 'LIKE', "%{$search}%")
                  ->orWhere('customer_phone', 'LIKE', "%{$search}%")
                  ->orWhere('customer_email', 'LIKE', "%{$search}%");
            });
        }

        // Status Filter
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Date Range
        if ($from = $request->input('from_date')) {
            $query->whereDate('invoice_date', '>=', $from);
        }
        if ($to = $request->input('to_date')) {
            $query->whereDate('invoice_date', '<=', $to);
        }

        // KPI aggregates
        $baseQuery = Invoice::query();
        if ($companyId) {
            $baseQuery->where('company_id', $companyId);
        }

        $allInvoices = (clone $baseQuery)->get();
        $stats = [
            'total_count'   => $allInvoices->count(),
            'total_amount'  => (float) $allInvoices->sum('total_amount'),
            'paid_amount'   => (float) $allInvoices->sum('paid_amount'),
            'total_tds'     => (float) $allInvoices->sum('total_tds'),
            'balance_due'   => (float) $allInvoices->sum('balance_due'),
            'paid_count'    => $allInvoices->where('status', 'paid')->count(),
            'partial_count' => $allInvoices->where('status', 'partially_paid')->count(),
            'draft_count'   => $allInvoices->where('status', 'draft')->count(),
            'overdue_count' => $allInvoices->where('status', 'overdue')->count(),
        ];

        $invoices = $query->orderBy('invoice_date', 'desc')
                          ->orderBy('id', 'desc')
                          ->paginate(15)
                          ->withQueryString();

        return view('pages.accounts.sales.invoices.index', compact('invoices', 'stats'));
    }

    /**
     * Show the form for creating a new invoice.
     */
    public function create()
    {
        $companyId = auth()->user()?->company_id;

        // Auto-generate invoice number based on settings
        $invoiceNumber = Invoice::generateNextInvoiceNumber($companyId);
        $setting       = InvoiceSetting::forCompany($companyId);

        // Fetch Customers from CustomerMaster
        $customers = CustomerMaster::query()
            ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
            ->active()
            ->orderBy('customer_name')
            ->get();

        // Fetch Catalog Items from ItemMaster
        $items = ItemMaster::query()
            ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
            ->active()
            ->orderBy('item_name')
            ->get();

        // Fetch Sales & CST Users
        $salesUsers = $this->getSalesUsers($companyId);

        $states = self::$indianStates;
        $defaultTerms = $setting->terms_conditions ?: InvoiceSetting::defaultTerms();
        $defaultNotes = $setting->notes;

        return view('pages.accounts.sales.invoices.create', compact(
            'invoiceNumber',
            'setting',
            'customers',
            'items',
            'salesUsers',
            'states',
            'defaultTerms',
            'defaultNotes'
        ));
    }

    /**
     * Get Customer details JSON for client-side auto-fill.
     */
    public function getCustomerDetails(CustomerMaster $customer)
    {
        $bStreet = trim(($customer->billing_address_street_1 ?? '') . ' ' . ($customer->billing_address_street_2 ?? ''));
        $sStreet = trim(($customer->shipping_address_street_1 ?? '') . ' ' . ($customer->shipping_address_street_2 ?? ''));

        // Dynamically find sales person from associated Lead or Customer
        $customer->loadMissing('lead');
        $salesPersonId = $customer->lead?->assigned_to
            ?: ($customer->lead?->customer_support_executive_id
            ?: ($customer->lead?->created_by ?: $customer->created_by));
        $salesPerson = $salesPersonId ? User::find($salesPersonId) : null;

        return response()->json([
            'id'                 => $customer->id,
            'customer_name'      => $customer->customer_name,
            'company_name'       => $customer->company_name,
            'mobile_number'      => $customer->mobile_number,
            'email'              => $customer->email,
            'gst_number'         => $customer->gst_number,
            'pan_number'         => $customer->pan_number,
            'payment_terms'      => $customer->payment_terms ?: 'Due on Receipt',

            // Sales Person
            'sales_person_id'    => $salesPerson?->id,
            'sales_person_name'  => $salesPerson?->name,
            'sales_person_email' => $salesPerson?->email,

            // Billing
            'billing_attention'  => $customer->billing_attention ?: $customer->customer_name,
            'billing_address'    => $bStreet,
            'billing_city'       => $customer->billing_city,
            'billing_state'      => $customer->billing_state,
            'billing_pincode'    => $customer->billing_pin_code,
            'billing_country'    => $customer->billing_country ?: 'India',

            // Shipping
            'shipping_attention' => $customer->shipping_attention ?: ($customer->billing_attention ?: $customer->customer_name),
            'shipping_address'   => $sStreet ?: $bStreet,
            'shipping_city'      => $customer->shipping_city ?: $customer->billing_city,
            'shipping_state'     => $customer->shipping_state ?: $customer->billing_state,
            'shipping_pincode'   => $customer->shipping_pin_code ?: $customer->billing_pin_code,
            'shipping_country'   => $customer->shipping_country ?: ($customer->billing_country ?: 'India'),
        ]);
    }

    /**
     * Get Item details JSON for client-side row auto-fill.
     */
    public function getItemDetails(ItemMaster $item)
    {
        return response()->json([
            'id'           => $item->id,
            'item_name'    => $item->item_name,
            'description'  => $item->description,
            'hsn_sac_code' => $item->hsn_sac_code,
            'rate'         => (float) $item->rate,
            'gst'          => (float) $item->gst,
        ]);
    }

    /**
     * Store a newly created invoice in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id'        => 'nullable|integer',
            'customer_name'      => 'nullable|string|max:255',
            'company_name'       => 'nullable|string|max:255',
            'customer_email'     => 'nullable|email|max:255',
            'customer_phone'     => 'nullable|string|max:50',
            'customer_gstin'     => 'nullable|string|max:50',
            'customer_pan'       => 'nullable|string|max:30',
            'sales_person_id'    => 'nullable|integer',

            'invoice_number'     => 'required|string|max:100',
            'invoice_date'       => 'required|date',
            'due_date'           => 'nullable|date',
            'payment_terms'      => 'nullable|string|max:100',
            'place_of_supply'    => 'nullable|string|max:100',

            // Billing
            'billing_attention'  => 'nullable|string|max:255',
            'billing_address'    => 'nullable|string',
            'billing_city'       => 'nullable|string|max:100',
            'billing_state'      => 'nullable|string|max:100',
            'billing_pincode'    => 'nullable|string|max:20',
            'billing_country'    => 'nullable|string|max:100',
            'billing_gstin'      => 'nullable|string|max:50',

            // Shipping
            'shipping_attention' => 'nullable|string|max:255',
            'shipping_address'   => 'nullable|string',
            'shipping_city'      => 'nullable|string|max:100',
            'shipping_state'     => 'nullable|string|max:100',
            'shipping_pincode'   => 'nullable|string|max:20',
            'shipping_country'   => 'nullable|string|max:100',
            'shipping_gstin'     => 'nullable|string|max:50',

            // Items Array
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
            'paid_amount'        => 'nullable|numeric|min:0',
            'balance_due'        => 'required|numeric',

            'customer_notes'     => 'nullable|string',
            'terms_conditions'   => 'nullable|string',
            'status'             => 'nullable|string|in:draft,sent,paid,partially_paid,overdue,cancelled',
        ]);

        if (empty($validated['customer_name']) && !empty($request->customer_id)) {
            $cust = CustomerMaster::find($request->customer_id);
            if ($cust) {
                $validated['customer_name']  = $cust->customer_name;
                $validated['company_name']   = $validated['company_name'] ?? $cust->company_name;
                $validated['customer_email']  = $validated['customer_email'] ?? $cust->email;
                $validated['customer_phone']  = $validated['customer_phone'] ?? $cust->mobile_number;
                $validated['customer_gstin']  = $validated['customer_gstin'] ?? ($validated['billing_gstin'] ?? $cust->gst_number);
                $validated['customer_pan']    = $validated['customer_pan'] ?? $cust->pan_number;
            }
        }
        if (empty($validated['customer_gstin']) && !empty($validated['billing_gstin'])) {
            $validated['customer_gstin'] = $validated['billing_gstin'];
        }
        if (empty($validated['customer_name'])) {
            $validated['customer_name'] = 'Valued Customer';
        }

        $companyId = auth()->user()?->company_id;
        $userId    = auth()->id();

        if (empty($validated['invoice_number'])) {
            $validated['invoice_number'] = Invoice::generateNextInvoiceNumber($companyId);
        }

        // Duplicate guard within 10 seconds for same invoice_number and company
        $existingRecent = Invoice::where('company_id', $companyId)
            ->where('invoice_number', trim($validated['invoice_number']))
            ->where('created_at', '>=', now()->subSeconds(10))
            ->first();

        if ($existingRecent) {
            return redirect()->route('accounts.sales.invoices.show', $existingRecent->id)
                             ->with('success', 'Invoice created successfully!');
        }

        DB::beginTransaction();
        try {
            // Determine automatic status based on payment made
            $grandTotal = (float) $validated['total_amount'];
            $paidAmount = (float) ($validated['paid_amount'] ?? 0);
            $balanceDue = max(0, $grandTotal - $paidAmount);

            $status = $validated['status'] ?? 'draft';
            if ($status === 'draft' || empty($status)) {
                if ($paidAmount >= $grandTotal && $grandTotal > 0) {
                    $status = 'paid';
                } elseif ($paidAmount > 0) {
                    $status = 'partially_paid';
                } else {
                    $status = 'sent';
                }
            }

            $invoiceData = collect($validated)->except(['items'])->toArray();
            $invoiceData['company_id']  = $companyId;
            $invoiceData['created_by']  = $userId;
            $invoiceData['updated_by']  = $userId;
            $invoiceData['paid_amount'] = $paidAmount;
            $invoiceData['balance_due'] = $balanceDue;
            $invoiceData['status']      = $status;

            $invoice = Invoice::create($invoiceData);

            // Create line items
            foreach ($validated['items'] as $index => $itemRow) {
                $itemRow['invoice_id']   = $invoice->id;
                $itemRow['sort_order']   = $index + 1;
                $itemRow['total_amount'] = !empty($itemRow['total_amount']) ? $itemRow['total_amount'] : ($itemRow['amount'] ?? 0);
                $itemRow['cgst_percent'] = $itemRow['cgst_percent'] ?? 0;
                $itemRow['cgst_amount']  = $itemRow['cgst_amount'] ?? 0;
                $itemRow['sgst_percent'] = $itemRow['sgst_percent'] ?? 0;
                $itemRow['sgst_amount']  = $itemRow['sgst_amount'] ?? 0;
                $itemRow['igst_percent'] = $itemRow['igst_percent'] ?? 0;
                $itemRow['igst_amount']  = $itemRow['igst_amount'] ?? 0;

                InvoiceItem::create($itemRow);
            }

            // Auto-increment next_number in invoice settings if this matched format
            $setting = InvoiceSetting::forCompany($companyId);
            $expectedNumber = $setting->formatInvoiceNumber($setting->next_number);
            if ($invoice->invoice_number === $expectedNumber) {
                $setting->increment('next_number');
            }

            DB::commit();

            return redirect()->route('accounts.sales.invoices.show', $invoice->id)
                             ->with('success', "Invoice #{$invoice->invoice_number} created successfully!");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Failed to create invoice: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified invoice with print/download preview.
     */
    public function show(Invoice $invoice)
    {
        $invoice->load(['items.itemMaster', 'customer', 'salesPerson', 'creator', 'payments.recordedBy']);
        $setting = InvoiceSetting::forCompany($invoice->company_id);

        $salesUsers = $this->getSalesUsers($invoice->company_id ?? auth()->user()?->company_id);
        if ($invoice->sales_person_id && !$salesUsers->contains('id', $invoice->sales_person_id) && $invoice->salesPerson) {
            $salesUsers->push($invoice->salesPerson);
        }

        return view('pages.accounts.sales.invoices.show', compact('invoice', 'setting', 'salesUsers'));
    }

    /**
     * Show form for editing the invoice.
     */
    public function edit(Invoice $invoice)
    {
        $invoice->load('items');
        $companyId = auth()->user()?->company_id;

        $setting   = InvoiceSetting::forCompany($companyId);
        $customers = CustomerMaster::query()
            ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
            ->active()
            ->orderBy('customer_name')
            ->get();

        $items = ItemMaster::query()
            ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
            ->active()
            ->orderBy('item_name')
            ->get();

        $salesUsers = $this->getSalesUsers($companyId);
        if ($invoice->sales_person_id && !$salesUsers->contains('id', $invoice->sales_person_id) && $invoice->salesPerson) {
            $salesUsers->push($invoice->salesPerson);
        }

        $states = self::$indianStates;

        return view('pages.accounts.sales.invoices.edit', compact(
            'invoice',
            'setting',
            'customers',
            'items',
            'salesUsers',
            'states'
        ));
    }

    /**
     * Update the specified invoice in storage.
     */
    public function update(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'customer_id'        => 'nullable|integer',
            'customer_name'      => 'nullable|string|max:255',
            'company_name'       => 'nullable|string|max:255',
            'customer_email'     => 'nullable|email|max:255',
            'customer_phone'     => 'nullable|string|max:50',
            'customer_gstin'     => 'nullable|string|max:50',
            'customer_pan'       => 'nullable|string|max:30',
            'sales_person_id'    => 'nullable|integer',

            'invoice_number'     => 'required|string|max:100',
            'invoice_date'       => 'required|date',
            'due_date'           => 'nullable|date',
            'payment_terms'      => 'nullable|string|max:100',
            'place_of_supply'    => 'nullable|string|max:100',

            // Billing
            'billing_attention'  => 'nullable|string|max:255',
            'billing_address'    => 'nullable|string',
            'billing_city'       => 'nullable|string|max:100',
            'billing_state'      => 'nullable|string|max:100',
            'billing_pincode'    => 'nullable|string|max:20',
            'billing_country'    => 'nullable|string|max:100',
            'billing_gstin'      => 'nullable|string|max:50',

            // Shipping
            'shipping_attention' => 'nullable|string|max:255',
            'shipping_address'   => 'nullable|string',
            'shipping_city'      => 'nullable|string|max:100',
            'shipping_state'     => 'nullable|string|max:100',
            'shipping_pincode'   => 'nullable|string|max:20',
            'shipping_country'   => 'nullable|string|max:100',
            'shipping_gstin'     => 'nullable|string|max:50',

            // Items Array
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
            'paid_amount'        => 'nullable|numeric|min:0',
            'balance_due'        => 'required|numeric',

            'customer_notes'     => 'nullable|string',
            'terms_conditions'   => 'nullable|string',
            'status'             => 'nullable|string|in:draft,sent,paid,partially_paid,overdue,cancelled',
        ]);

        if (empty($validated['customer_name']) && !empty($request->customer_id)) {
            $cust = CustomerMaster::find($request->customer_id);
            if ($cust) {
                $validated['customer_name']  = $cust->customer_name;
                $validated['company_name']   = $validated['company_name'] ?? $cust->company_name;
                $validated['customer_email']  = $validated['customer_email'] ?? $cust->email;
                $validated['customer_phone']  = $validated['customer_phone'] ?? $cust->mobile_number;
                $validated['customer_gstin']  = $validated['customer_gstin'] ?? ($validated['billing_gstin'] ?? $cust->gst_number);
                $validated['customer_pan']    = $validated['customer_pan'] ?? $cust->pan_number;
            }
        }
        if (empty($validated['customer_gstin']) && !empty($validated['billing_gstin'])) {
            $validated['customer_gstin'] = $validated['billing_gstin'];
        }
        if (empty($validated['customer_name'])) {
            $validated['customer_name'] = $invoice->customer_name ?: 'Valued Customer';
        }

        DB::beginTransaction();
        try {
            $grandTotal = (float) $validated['total_amount'];
            $paidAmount = (float) ($validated['paid_amount'] ?? 0);
            $balanceDue = max(0, $grandTotal - $paidAmount);

            $status = $validated['status'] ?? $invoice->status;
            if ($paidAmount >= $grandTotal && $grandTotal > 0) {
                $status = 'paid';
            } elseif ($paidAmount > 0 && $paidAmount < $grandTotal) {
                $status = 'partially_paid';
            }

            $invoiceData = collect($validated)->except(['items'])->toArray();
            $invoiceData['updated_by']  = auth()->id();
            $invoiceData['paid_amount'] = $paidAmount;
            $invoiceData['balance_due'] = $balanceDue;
            $invoiceData['status']      = $status;

            $invoice->update($invoiceData);

            // Re-sync line items
            $invoice->items()->delete();
            foreach ($validated['items'] as $index => $itemRow) {
                $itemRow['invoice_id']   = $invoice->id;
                $itemRow['sort_order']   = $index + 1;
                $itemRow['total_amount'] = !empty($itemRow['total_amount']) ? $itemRow['total_amount'] : ($itemRow['amount'] ?? 0);
                $itemRow['cgst_percent'] = $itemRow['cgst_percent'] ?? 0;
                $itemRow['cgst_amount']  = $itemRow['cgst_amount'] ?? 0;
                $itemRow['sgst_percent'] = $itemRow['sgst_percent'] ?? 0;
                $itemRow['sgst_amount']  = $itemRow['sgst_amount'] ?? 0;
                $itemRow['igst_percent'] = $itemRow['igst_percent'] ?? 0;
                $itemRow['igst_amount']  = $itemRow['igst_amount'] ?? 0;

                InvoiceItem::create($itemRow);
            }

            DB::commit();

            return redirect()->route('accounts.sales.invoices.show', $invoice->id)
                             ->with('success', "Invoice #{$invoice->invoice_number} updated successfully!");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Failed to update invoice: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified invoice from storage.
     */
    public function destroy(Invoice $invoice)
    {
        $num = $invoice->invoice_number;
        $invoice->delete();

        return redirect()->route('accounts.sales.invoices.index')
                         ->with('success', "Invoice #{$num} deleted successfully.");
    }

    /**
     * Print preview layout for invoice.
     */
    public function print(Invoice $invoice)
    {
        $invoice->load(['items.itemMaster', 'customer', 'salesPerson', 'creator']);
        $setting = InvoiceSetting::forCompany($invoice->company_id);

        return view('pages.accounts.sales.invoices.print', compact('invoice', 'setting'));
    }

    /**
     * Update only the sales person for the invoice (quick inline/modal update).
     */
    public function updateSalesPerson(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'sales_person_id' => 'nullable|exists:users,id',
        ]);

        $invoice->update([
            'sales_person_id' => $validated['sales_person_id'] ?? null,
            'updated_by'      => auth()->id(),
        ]);

        $invoice->load('salesPerson');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'           => true,
                'message'           => 'Sales Person updated successfully.',
                'sales_person_id'   => $invoice->sales_person_id,
                'sales_person_name' => $invoice->salesPerson?->name ?? '—',
            ]);
        }

        return redirect()->back()->with('success', 'Sales Person updated successfully.');
    }

    /**
     * Fetch Sales & CST Users for assignment dropdown.
     */
    protected function getSalesUsers($companyId = null)
    {
        $salesUsers = User::query()
            ->where(function ($query) {
                $deptIds = Department::where(function ($dq) {
                    $dq->where('name', 'LIKE', '%Sales%')
                       ->orWhere('name', 'LIKE', '%Customer Success%')
                       ->orWhere('name', 'LIKE', '%CST%');
                })->pluck('id');

                if ($deptIds->isNotEmpty()) {
                    $query->whereHas('roles', fn ($rq) => $rq->whereIn('department_id', $deptIds));
                }
            })
            ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        if ($salesUsers->isEmpty()) {
            $salesUsers = User::where('is_active', true)->orderBy('name')->take(20)->get(['id', 'name', 'email']);
        }

        return $salesUsers;
    }
}
