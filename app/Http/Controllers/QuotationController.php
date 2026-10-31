<?php

namespace App\Http\Controllers;

use App\Http\Resources\QuotationCollection;
use App\Models\CustomerMaster;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceSetting;
use App\Models\ItemMaster;
use App\Models\Lead;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\QuotationSetting;
use App\Models\User;
use App\Models\ZohoIntegration;
use App\Services\DataVisibilityService;
use App\Services\ZohoBooksService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class QuotationController extends Controller
{
    public function __construct(private readonly DataVisibilityService $visibility) {}

    // ── List ──────────────────────────────────────────────────────────────────

    /**
     * List all quotations (optionally filtered by lead).
     */
    public function index(Request $request): View
    {
        $query = Quotation::with(['lead', 'approver'])
            ->latest();

        $this->visibility->applyQuotationVisibility($query);

        if ($request->filled('lead_id')) {
            $query->where('lead_id', $request->lead_id);
        }

        if ($request->filled('quotation_no')) {
            $query->where('quotation_no', 'like', '%' . trim($request->quotation_no) . '%');
        }

        if ($request->filled('status')) {
            if ($request->status === 'approved') {
                $query->where('is_approved', true);
            } elseif ($request->status === 'pending') {
                $query->where('is_approved', false);
            }
        }

        if ($request->filled('approved_by')) {
            $query->where('approved_by', $request->approved_by);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('quotation_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('quotation_date', '<=', $request->end_date);
        }

        if ($request->filled('customer_response')) {
            if ($request->customer_response === 'pending') {
                $query->where(function ($q) {
                    $q->whereNull('customer_response')->orWhere('customer_response', 'pending');
                });
            } else {
                $query->where('customer_response', $request->customer_response);
            }
        }

        $quotations = $query->paginate(15)->withQueryString();
        $approvers = $this->visibility->visibleAssignableUsers();

        return view('pages.quotations.index', compact('quotations', 'approvers'));
    }

    // ── Create ────────────────────────────────────────────────────────────────

    /**
     * Show create form, optionally pre-selecting a lead.
     */
    public function create($leadId = null): View
    {
        $lead = $leadId ? Lead::with('branch')->findOrFail($leadId) : null;
        if ($lead) {
            abort_unless($this->visibility->canAccessLead($lead), 403);
        }

        $leadQuery = Lead::orderBy('contact_name');
        $this->visibility->applyLeadVisibility($leadQuery);
        $leads = $leadQuery->get(['id', 'contact_name', 'company_name']);

        $productQuery = Product::orderBy('product_name');
        $this->visibility->applyProductVisibility($productQuery);
        $products = $productQuery->get([
            'id',
            'product_name',
            'description',
            'final_price',
            'base_price',
            'discount_type',
            'discount_value',
        ]);

        $selectedLeadId = $leadId;
        $companyId = $lead?->company_id ?? auth()->user()?->company_id;

        $defaults = [
            'quotation_no'   => Quotation::generateQuotationNo($companyId),
            'quotation_date' => now()->toDateString(),
            'valid_until'    => now()->addDays(7)->toDateString(),
            'tax'            => Quotation::GST_RATE,
            'seller_state'   => $lead?->branch?->state ?: Quotation::DEFAULT_SELLER_STATE,
            'customer_state' => 'Tamil Nadu',
        ];

        return view('pages.quotations.create', compact('leads', 'products', 'selectedLeadId', 'defaults', 'lead', 'leadId'));
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    /**
     * Persist quotation + items inside a DB transaction.
     */

    public function store(Request $request)
    {
        $validated = $request->validate([
            'lead_id'        => ['nullable'],
            'quotation_date' => ['required', 'date'],
            'valid_until'    => ['required', 'date', 'after_or_equal:quotation_date'],
            'gst_number'     => ['nullable', 'string', 'max:20'],
            'customer_state' => ['required', 'string', 'max:100'],
            'notes'          => ['nullable', 'string'],

            // Line items
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.product_id'     => ['required', 'exists:products,id'],
            'items.*.description'    => ['nullable', 'string'],
            'items.*.qty'            => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price'     => ['required', 'numeric', 'min:0'],
            'items.*.discount'       => ['required', 'numeric', 'min:0'],
        ]);

        $quotation = DB::transaction(function () use ($validated, $request) {
            $lead = ! empty($validated['lead_id'])
                ? Lead::with('branch')->findOrFail($validated['lead_id'])
                : null;

            if ($lead) {
                abort_unless($this->visibility->canAccessLead($lead), 403);
            }

            $companyId = $lead?->company_id ?? auth()->user()?->company_id;

            foreach ($validated['items'] as $item) {
                Product::findOrFail($item['product_id']);
            }

            $subtotal = 0;

            foreach ($validated['items'] as $item) {

                $rowTotal = ($item['qty'] * $item['unit_price']) - $item['discount'];

                $subtotal += max($rowTotal, 0);
            }

            $customerState = Quotation::normalizeState($validated['customer_state'])
                ?: Quotation::inferStateFromGstin($validated['gst_number'])
                ?: 'Tamil Nadu';
            $sellerState = Quotation::normalizeState($lead?->branch?->state ?: Quotation::DEFAULT_SELLER_STATE)
                ?: Quotation::DEFAULT_SELLER_STATE;
            $taxBreakup = Quotation::calculateTaxBreakup($subtotal, $customerState, $sellerState);

            $creator = auth()->user();
            $autoApprove = (bool) ($creator?->canAutoApproveQuotation());

            $quotation = Quotation::create([
                'quotation_no'    => Quotation::generateQuotationNo($companyId),
                'quotation_date'  => $validated['quotation_date'],
                'valid_until'     => $validated['valid_until'],
                'tax'             => $taxBreakup['tax_rate'],
                'subtotal'        => $subtotal,
                'tax_amount'      => $taxBreakup['tax_amount'],
                'total_amount'    => $taxBreakup['total_amount'],
                'lead_id'         => $validated['lead_id'] ?? null,
                'company_id'      => $companyId,
                'notes'           => $validated['notes'] ?? null,
                'is_approved'     => $autoApprove,
                'approved_by'     => $autoApprove ? $creator->id : null,
                'approved_at'     => $autoApprove ? now() : null,
                'created_by'      => auth()->id(),
                'bill_to_address' => $request->bill_to_address,
                'ship_to_address' => $request->ship_to_address,
                'gst_number'      => strtoupper((string) ($validated['gst_number'] ?? '')) ?: null,
                'customer_state'  => $taxBreakup['customer_state'],
                'seller_state'    => $taxBreakup['seller_state'],
                'tax_type'        => $taxBreakup['tax_type'],
                'cgst_rate'       => $taxBreakup['cgst_rate'],
                'sgst_rate'       => $taxBreakup['sgst_rate'],
                'igst_rate'       => $taxBreakup['igst_rate'],
                'cgst_amount'     => $taxBreakup['cgst_amount'],
                'sgst_amount'     => $taxBreakup['sgst_amount'],
                'igst_amount'     => $taxBreakup['igst_amount'],
            ]);

            foreach ($validated['items'] as $item) {

                $rowTotal = max(
                    ($item['qty'] * $item['unit_price']) - $item['discount'],
                    0
                );

                QuotationItem::create([
                    'quotation_id' => $quotation->id,
                    'company_id'   => $quotation->company_id,
                    'product_id'   => $item['product_id'],
                    'description'  => $item['description'] ?? null,
                    'qty'          => $item['qty'],
                    'unit_price'   => $item['unit_price'],
                    'discount'     => $item['discount'],
                    'total'        => $rowTotal,
                ]);
            }

            return $quotation;
        });

        // API Response
        if ($request->expectsJson() || $request->is('api/*')) {

            return response()->json([
                'status' => true,
                'message' => 'Quotation created successfully.',
                'data' => $quotation->load('items')
            ], 201);
        }

        // Web Response
        return redirect()
            ->route('quotations.index')
            ->with('success', 'Quotation created successfully.');
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function show(Quotation $quotation): View
    {
        abort_unless($this->visibility->canAccessQuotation($quotation), 403);

        $quotation->load(['lead', 'items.product', 'approver', 'createdBy', 'lead.createdBy', 'lead.assignedTo']);
        $quoteSetting = $this->quotationSettingsFor($quotation);

        return view('pages.quotations.show', compact('quotation', 'quoteSetting'));
    }

    public function print(Quotation $quotation): View
    {
        abort_unless($this->visibility->canAccessQuotation($quotation), 403);

        $quotation->load(['lead', 'items.product', 'approver', 'createdBy', 'lead.createdBy', 'lead.assignedTo']);
        $quoteSetting = $this->quotationSettingsFor($quotation);

        return view('pages.quotations.print', compact('quotation', 'quoteSetting'));
    }

    // ── Edit & Update ─────────────────────────────────────────────────────────

    public function edit(Quotation $quotation): View
    {
        abort_unless($this->visibility->canAccessQuotation($quotation), 403);

        $quotation->load(['lead.branch', 'items.product']);

        $leadQuery = Lead::orderBy('contact_name');
        $this->visibility->applyLeadVisibility($leadQuery);
        $leads = $leadQuery->get(['id', 'contact_name', 'company_name']);

        $productQuery = Product::orderBy('product_name');
        $this->visibility->applyProductVisibility($productQuery);
        $products = $productQuery->get([
            'id',
            'product_name',
            'description',
            'final_price',
            'base_price',
            'discount_type',
            'discount_value',
        ]);

        $lead = $quotation->lead;

        return view('pages.quotations.edit', compact('quotation', 'leads', 'products', 'lead'));
    }

    public function update(Request $request, Quotation $quotation): RedirectResponse
    {
        abort_unless($this->visibility->canAccessQuotation($quotation), 403);

        $validated = $request->validate([
            'lead_id'        => ['nullable'],
            'quotation_date' => ['required', 'date'],
            'valid_until'    => ['required', 'date', 'after_or_equal:quotation_date'],
            'gst_number'     => ['nullable', 'string', 'max:20'],
            'customer_state' => ['required', 'string', 'max:100'],
            'notes'          => ['nullable', 'string'],

            // Line items
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.product_id'     => ['required', 'exists:products,id'],
            'items.*.description'    => ['nullable', 'string'],
            'items.*.qty'            => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price'     => ['required', 'numeric', 'min:0'],
            'items.*.discount'       => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($validated, $request, $quotation) {
            $lead = ! empty($validated['lead_id'])
                ? Lead::with('branch')->find($validated['lead_id'])
                : $quotation->lead;

            if ($lead) {
                abort_unless($this->visibility->canAccessLead($lead), 403);
            }

            foreach ($validated['items'] as $item) {
                Product::findOrFail($item['product_id']);
            }

            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $rowTotal = ($item['qty'] * $item['unit_price']) - $item['discount'];
                $subtotal += max($rowTotal, 0);
            }

            $customerState = Quotation::normalizeState($validated['customer_state'])
                ?: Quotation::inferStateFromGstin($validated['gst_number'])
                ?: 'Tamil Nadu';
            $sellerState = Quotation::normalizeState($lead?->branch?->state ?: Quotation::DEFAULT_SELLER_STATE)
                ?: Quotation::DEFAULT_SELLER_STATE;
            $taxBreakup = Quotation::calculateTaxBreakup($subtotal, $customerState, $sellerState);

            $quotation->update([
                'lead_id'         => $validated['lead_id'] ?? $quotation->lead_id,
                'quotation_date'  => $validated['quotation_date'],
                'valid_until'     => $validated['valid_until'],
                'tax'             => $taxBreakup['tax_rate'],
                'subtotal'        => $subtotal,
                'tax_amount'      => $taxBreakup['tax_amount'],
                'total_amount'    => $taxBreakup['total_amount'],
                'notes'           => $validated['notes'] ?? null,
                'bill_to_address' => $request->bill_to_address,
                'ship_to_address' => $request->ship_to_address,
                'gst_number'      => strtoupper((string) ($validated['gst_number'] ?? '')) ?: null,
                'customer_state'  => $taxBreakup['customer_state'],
                'seller_state'    => $taxBreakup['seller_state'],
                'tax_type'        => $taxBreakup['tax_type'],
                'cgst_rate'       => $taxBreakup['cgst_rate'],
                'sgst_rate'       => $taxBreakup['sgst_rate'],
                'igst_rate'       => $taxBreakup['igst_rate'],
                'cgst_amount'     => $taxBreakup['cgst_amount'],
                'sgst_amount'     => $taxBreakup['sgst_amount'],
                'igst_amount'     => $taxBreakup['igst_amount'],
            ]);

            $quotation->items()->delete();

            foreach ($validated['items'] as $item) {
                $rowTotal = max(
                    ($item['qty'] * $item['unit_price']) - $item['discount'],
                    0
                );

                QuotationItem::create([
                    'quotation_id' => $quotation->id,
                    'company_id'   => $quotation->company_id,
                    'product_id'   => $item['product_id'],
                    'description'  => $item['description'] ?? null,
                    'qty'          => $item['qty'],
                    'unit_price'   => $item['unit_price'],
                    'discount'     => $item['discount'],
                    'total'        => $rowTotal,
                ]);
            }
        });

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('success', 'Quotation updated successfully.');
    }

    // ── Approve ───────────────────────────────────────────────────────────────

    public function approve(Quotation $quotation): RedirectResponse
    {
        abort_unless($this->visibility->canAccessQuotation($quotation), 403);

        $quotation->update([
            'is_approved' => true,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Quotation approved successfully.');
    }

    // ── Convert to Invoice ────────────────────────────────────────────────────

    public function convertToInvoice(Request $request, Quotation $quotation): RedirectResponse
    {
        abort_unless($this->visibility->canAccessQuotation($quotation), 403);

        $quotation->load(['lead', 'items.product']);
        $companyId = $quotation->company_id ?: auth()->user()?->company_id;
        $userId    = auth()->id();
        $lead      = $quotation->lead;

        // 1. Resolve or create CustomerMaster
        $customer = null;
        if ($lead) {
            $customer = CustomerMaster::where('lead_id', $lead->id)->first();
            if (! $customer && ! empty($lead->company_name)) {
                $customer = CustomerMaster::where(function ($q) use ($companyId) {
                    if ($companyId) {
                        $q->where('company_id', $companyId)->orWhereNull('company_id');
                    }
                })
                ->whereRaw('LOWER(TRIM(company_name)) = ?', [strtolower(trim($lead->company_name))])
                ->first();
            }
        }

        if (! $customer) {
            $customerName = $lead?->contact_name ?: ($lead?->company_name ?: 'Valued Customer');
            $customer = CustomerMaster::create([
                'company_id'                => $companyId,
                'lead_id'                   => $lead?->id,
                'customer_name'             => $customerName,
                'company_name'              => $lead?->company_name ?: $customerName,
                'mobile_number'             => $lead?->mobile_number,
                'email'                     => $lead?->email,
                'gst_number'                => $quotation->gst_number,
                'payment_terms'             => $request->input('payment_terms', 'Due on Receipt'),
                'billing_attention'         => $customerName,
                'billing_country'           => 'India',
                'billing_address_street_1'  => $quotation->bill_to_address,
                'billing_state'             => $quotation->customer_state ?: 'Tamil Nadu',
                'shipping_attention'        => $customerName,
                'shipping_country'          => 'India',
                'shipping_address_street_1' => $quotation->ship_to_address ?: $quotation->bill_to_address,
                'shipping_state'            => $quotation->customer_state ?: 'Tamil Nadu',
                'status'                    => 'active',
                'created_by'                => $userId,
            ]);
        }

        // 2. Resolve place of supply
        $placeOfSupply = '[33] Tamil Nadu';
        if (! empty($quotation->customer_state)) {
            foreach (InvoiceController::$indianStates as $codeName => $stateName) {
                if (strcasecmp($stateName, trim($quotation->customer_state)) === 0 || stripos($codeName, trim($quotation->customer_state)) !== false) {
                    $placeOfSupply = $codeName;
                    break;
                }
            }
        }

        // 3. Resolve terms & conditions
        $setting = InvoiceSetting::forCompany($companyId);
        $terms   = $setting?->terms_conditions ?: InvoiceSetting::defaultTerms();

        // 4. Generate next invoice number
        $invoiceNumber = Invoice::generateNextInvoiceNumber($companyId);

        $invoiceDate   = $request->input('invoice_date', now()->toDateString());
        $dueDate       = $request->input('due_date', ($quotation->valid_until ? Carbon::parse($quotation->valid_until)->toDateString() : now()->addDays(7)->toDateString()));
        $paymentTerms  = $request->input('payment_terms', 'Due on Receipt');

        DB::beginTransaction();
        try {
            $invoice = Invoice::create([
                'company_id'         => $companyId,
                'branch_id'          => $lead?->branch_id,
                'customer_id'        => $customer->id,
                'sales_person_id'    => $lead?->assigned_to ?: ($lead?->created_by ?: $userId),
                'invoice_number'     => $invoiceNumber,
                'invoice_date'       => $invoiceDate,
                'due_date'           => $dueDate,
                'payment_terms'      => $paymentTerms,
                'place_of_supply'    => $placeOfSupply,
                'customer_name'      => $customer->customer_name,
                'company_name'       => $customer->company_name,
                'customer_email'     => $customer->email,
                'customer_phone'     => $customer->mobile_number,
                'customer_gstin'     => $quotation->gst_number ?: $customer->gst_number,
                'customer_pan'       => $customer->pan_number,
                'billing_attention'  => $customer->customer_name,
                'billing_address'    => $quotation->bill_to_address ?: $customer->billing_address_street_1,
                'billing_city'       => $customer->billing_city,
                'billing_state'      => $quotation->customer_state ?: $customer->billing_state,
                'billing_pincode'    => $customer->billing_pin_code,
                'billing_country'    => 'India',
                'billing_gstin'      => $quotation->gst_number ?: $customer->gst_number,
                'shipping_attention' => $customer->customer_name,
                'shipping_address'   => $quotation->ship_to_address ?: $quotation->bill_to_address,
                'shipping_city'      => $customer->shipping_city ?: $customer->billing_city,
                'shipping_state'     => $quotation->customer_state ?: $customer->shipping_state,
                'shipping_pincode'   => $customer->shipping_pin_code ?: $customer->billing_pin_code,
                'shipping_country'   => 'India',
                'shipping_gstin'     => $quotation->gst_number ?: $customer->gst_number,
                'subtotal'           => (float) $quotation->subtotal,
                'total_cgst'         => (float) $quotation->cgst_amount,
                'total_sgst'         => (float) $quotation->sgst_amount,
                'total_igst'         => (float) $quotation->igst_amount,
                'total_tax'          => (float) $quotation->tax_amount,
                'round_off'          => 0.00,
                'total_amount'       => (float) $quotation->total_amount,
                'paid_amount'        => 0.00,
                'total_tds'          => 0.00,
                'total_credited'     => 0.00,
                'balance_due'        => (float) $quotation->total_amount,
                'customer_notes'     => "Converted from Quotation #{$quotation->quotation_no}." . ($quotation->notes ? "\n" . $quotation->notes : ''),
                'terms_conditions'   => $terms,
                'status'             => 'draft',
                'created_by'         => $userId,
                'updated_by'         => $userId,
            ]);

            // Create items
            $cgstPct = (float) ($quotation->cgst_rate ?: 0);
            $sgstPct = (float) ($quotation->sgst_rate ?: 0);
            $igstPct = (float) ($quotation->igst_rate ?: 0);

            foreach ($quotation->items as $index => $qItem) {
                $rate   = (float) $qItem->unit_price;
                $qty    = (float) ($qItem->qty ?: 1);
                $amount = (float) ($qItem->total ?: ($rate * $qty));

                $lineCgst  = round(($amount * $cgstPct) / 100, 2);
                $lineSgst  = round(($amount * $sgstPct) / 100, 2);
                $lineIgst  = round(($amount * $igstPct) / 100, 2);
                $lineTotal = $amount + $lineCgst + $lineSgst + $lineIgst;

                $itemName = $qItem->product?->product_name ?: ($qItem->product?->package_name ?: ($qItem->description ?: 'Product / Service'));

                $itemMaster = ItemMaster::where(function ($q) use ($companyId) {
                    if ($companyId) {
                        $q->where('company_id', $companyId)->orWhereNull('company_id');
                    }
                })
                ->whereRaw('LOWER(TRIM(item_name)) = ?', [strtolower(trim($itemName))])
                ->first();

                if (! $itemMaster) {
                    $itemMaster = ItemMaster::create([
                        'company_id'   => $companyId,
                        'item_name'    => $itemName,
                        'description'  => $qItem->description ?: ($qItem->product?->description ?: null),
                        'hsn_sac_code' => null,
                        'rate'         => $rate,
                        'gst'          => $cgstPct + $sgstPct + $igstPct,
                        'status'       => 'active',
                        'created_by'   => $userId,
                    ]);
                }

                InvoiceItem::create([
                    'invoice_id'    => $invoice->id,
                    'item_id'       => $itemMaster?->id,
                    'item_name'     => $itemName,
                    'description'   => $qItem->description,
                    'hsn_sac_code'  => $itemMaster?->hsn_sac_code,
                    'qty'           => $qty,
                    'rate'          => $rate,
                    'amount'        => $amount,
                    'cgst_percent'  => $cgstPct,
                    'cgst_amount'   => $lineCgst,
                    'sgst_percent'  => $sgstPct,
                    'sgst_amount'   => $lineSgst,
                    'igst_percent'  => $igstPct,
                    'igst_amount'   => $lineIgst,
                    'total_amount'  => $lineTotal,
                    'sort_order'    => $index + 1,
                ]);
            }

            DB::commit();

            return redirect()->route('accounts.sales.invoices.show', $invoice->id)
                ->with('success', "Quotation #{$quotation->quotation_no} was successfully converted to Invoice #{$invoice->invoice_number}!");
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Failed to convert quotation #{$quotation->id} to invoice: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Failed to convert quotation to invoice: ' . $e->getMessage());
        }
    }

    public function sendEmail(Quotation $quotation): RedirectResponse
    {
        abort_unless($this->visibility->canAccessQuotation($quotation), 403);

        $quotation->loadMissing(['items.product', 'createdBy', 'lead.createdBy', 'lead.assignedTo']);

        $lead = $quotation->lead;
        $email = trim((string) $lead?->email);

        if (! $lead || $email === '') {
            return back()->with('error', 'Lead email not available for this quotation.');
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return back()->with('error', 'Lead email is invalid. Please update the lead email and try again.');
        }

        try {
            $pdf = $this->makeQuotationPdf($quotation);
            $filename = $this->quotationPdfFilename($quotation);
            $agreeUrl = URL::signedRoute('quotations.customer-response', [
                'quotation' => $quotation->id,
                'response' => Quotation::CUSTOMER_RESPONSE_AGREE,
            ]);
            $disagreeUrl = URL::signedRoute('quotations.customer-response', [
                'quotation' => $quotation->id,
                'response' => Quotation::CUSTOMER_RESPONSE_DISAGREE,
            ]);

            Mail::send('emails.quotation', [
                'quotation'   => $quotation,
                'lead'        => $lead,
                'agreeUrl'    => $agreeUrl,
                'disagreeUrl' => $disagreeUrl,
                'approveUrl'  => $agreeUrl,
                'rejectUrl'   => $disagreeUrl,
            ], function ($message) use ($quotation, $lead, $email, $pdf, $filename) {
                $message
                    ->to($email, $lead->contact_name ?: null)
                    ->subject('Quotation ' . $quotation->quotation_no)
                    ->attachData($pdf->output(), $filename, [
                        'mime' => 'application/pdf',
                    ]);
            });
        } catch (Throwable $exception) {
            Log::error('Quotation email send failed.', [
                'quotation_id' => $quotation->id,
                'email' => $email,
                'error' => $exception->getMessage(),
            ]);

            return back()->with('error', 'Quotation mail could not be sent. Please check mail settings and try again.');
        }

        return back()->with('success', 'Quotation ' . $quotation->quotation_no . ' sent to ' . $email . '.');
    }

    public function customerResponse(Request $request, Quotation $quotation, string $response): View
    {
        $normalizedResponse = match (strtolower(trim($response))) {
            'agree', 'approved', 'approve' => Quotation::CUSTOMER_RESPONSE_AGREE,
            'disagree', 'rejected', 'reject' => Quotation::CUSTOMER_RESPONSE_DISAGREE,
            default => null,
        };

        abort_unless($normalizedResponse !== null, 404);

        $quotation->loadMissing(['lead', 'items.product', 'company']);

        // If already responded
        if ($quotation->customer_responded_at && $quotation->customer_response !== Quotation::CUSTOMER_RESPONSE_PENDING) {
            $label = match ($quotation->customer_response) {
                Quotation::CUSTOMER_RESPONSE_AGREE, 'approved' => 'Approved',
                Quotation::CUSTOMER_RESPONSE_DISAGREE, 'rejected' => 'Rejected',
                default => ucfirst((string) $quotation->customer_response),
            };

            return view('pages.quotations.customer_response', [
                'quotation'     => $quotation,
                'response'      => $quotation->customer_response,
                'responseLabel' => $label,
                'alreadyDone'   => true,
            ]);
        }

        if ($normalizedResponse === Quotation::CUSTOMER_RESPONSE_AGREE) {
            $submitUrl = URL::signedRoute('quotations.customer-response.submit', [
                'quotation' => $quotation->id,
                'response'  => Quotation::CUSTOMER_RESPONSE_AGREE,
            ]);

            return view('pages.quotations.customer_approve', [
                'quotation' => $quotation,
                'lead'      => $quotation->lead,
                'submitUrl' => $submitUrl,
            ]);
        }

        $submitUrl = URL::signedRoute('quotations.customer-response.submit', [
            'quotation' => $quotation->id,
            'response'  => Quotation::CUSTOMER_RESPONSE_DISAGREE,
        ]);

        return view('pages.quotations.customer_reject', [
            'quotation' => $quotation,
            'lead'      => $quotation->lead,
            'submitUrl' => $submitUrl,
        ]);
    }

    public function submitCustomerResponse(Request $request, Quotation $quotation, string $response): View
    {
        $normalizedResponse = match (strtolower(trim($response))) {
            'agree', 'approved', 'approve' => Quotation::CUSTOMER_RESPONSE_AGREE,
            'disagree', 'rejected', 'reject' => Quotation::CUSTOMER_RESPONSE_DISAGREE,
            default => null,
        };

        abort_unless($normalizedResponse !== null, 404);

        $isApproved = ($normalizedResponse === Quotation::CUSTOMER_RESPONSE_AGREE);
        $signaturePath = null;

        if ($isApproved) {
            $request->validate([
                'signature_data' => ['required', 'string'],
                'location'       => ['nullable', 'string', 'max:255'],
                'latitude'       => ['nullable', 'string', 'max:50'],
                'longitude'      => ['nullable', 'string', 'max:50'],
            ]);

            $signatureData = (string) $request->input('signature_data');
            $signaturePath = $this->saveSignatureData($quotation, $signatureData);
        }

        $location = $request->input('location') ?: null;
        $latitude = $request->input('latitude') ?: null;
        $longitude = $request->input('longitude') ?: null;
        $ip = $request->ip();

        $updateData = [
            'customer_response'     => $normalizedResponse,
            'customer_responded_at' => now(),
            'is_approved'           => $isApproved,
            'approved_at'           => $isApproved ? ($quotation->approved_at ?: now()) : null,
            'customer_ip'           => $ip,
        ];

        if ($isApproved) {
            if ($signaturePath) {
                $updateData['customer_signature'] = $signaturePath;
            }
            if ($location) {
                $updateData['customer_location'] = $location;
            }
            if ($latitude) {
                $updateData['customer_latitude'] = $latitude;
            }
            if ($longitude) {
                $updateData['customer_longitude'] = $longitude;
            }
        }

        $quotation->update($updateData);

        Log::info('Customer quotation response submitted.', [
            'quotation_id' => $quotation->id,
            'quotation_no' => $quotation->quotation_no,
            'response'     => $normalizedResponse,
            'is_approved'  => $isApproved,
            'signature'    => $signaturePath,
            'location'     => $location,
            'ip'           => $ip,
        ]);

        $responseLabel = $isApproved ? 'Approved' : 'Rejected';

        return view('pages.quotations.customer_response', [
            'quotation'     => $quotation->fresh(['lead']),
            'response'      => $normalizedResponse,
            'responseLabel' => $responseLabel,
            'justSubmitted' => true,
        ]);
    }

    protected function saveSignatureData(Quotation $quotation, string $signatureData): ?string
    {
        if (! preg_match('/^data:image\/(\w+);base64,/', $signatureData)) {
            return null;
        }

        $data = substr($signatureData, strpos($signatureData, ',') + 1);
        $decoded = base64_decode($data);

        if ($decoded === false) {
            return null;
        }

        $dir = public_path('uploads/quotations/signatures');
        if (! file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = 'sig_q' . $quotation->id . '_' . time() . '_' . Str::random(8) . '.png';
        file_put_contents($dir . '/' . $filename, $decoded);

        return 'uploads/quotations/signatures/' . $filename;
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function destroy(Quotation $quotation): RedirectResponse
    {
        abort_unless($this->visibility->canAccessQuotation($quotation), 403);

        $quotation->delete();

        return back()->with('success', 'Quotation deleted successfully.');
    }

    // ── API: Products for Select2 ─────────────────────────────────────────────

    /**
     * Return products as JSON (used by Select2 AJAX if preferred).
     */
    public function productsApi(Request $request): JsonResponse
    {
        $query = Product::when($request->filled('q'), function ($query) use ($request) {
            $query->where(function ($productQuery) use ($request) {
                $productQuery
                    ->where('product_name', 'like', '%' . $request->q . '%')
                    ->orWhere('package_name', 'like', '%' . $request->q . '%');
            });
        });
        $this->visibility->applyProductVisibility($query);

        $products = $query
            ->limit(30)
            ->get(['id', 'product_name as name', 'description', 'final_price as price']);

        return response()->json($products);
    }

    // ── API: Quotation list for mobile ────────────────────────────────────────

    public function apiIndex(Request $request): JsonResponse
    {
        $query = Quotation::with(['lead:id,name', 'approver:id,name'])
            ->when($request->filled('lead_id'), fn($q) => $q->where('lead_id', $request->lead_id))
            ->latest();

        $this->visibility->applyQuotationVisibility($query);

        $quotations = $query->paginate(20);

        return response()->json($quotations);
    }

    public function apiStore(Request $request): JsonResponse
    {
        $request->validate([
            'lead_id'        => ['required', 'exists:leads,id'],
            'quotation_date' => ['required', 'date'],
            'valid_until'    => ['required', 'date', 'after_or_equal:quotation_date'],
            'gst_number'     => ['nullable', 'string', 'max:20'],
            'customer_state' => ['required', 'string', 'max:100'],
            'items'          => ['required', 'array', 'min:1'],
            'items.*.product_id'  => ['required', 'exists:products,id'],
            'items.*.qty'         => ['required', 'numeric'],
            'items.*.unit_price'  => ['required', 'numeric'],
            'items.*.discount'    => ['required', 'numeric'],
        ]);

        $quotation = null;

        DB::transaction(function () use ($request, &$quotation) {
            $lead = Lead::with('branch')->findOrFail($request->lead_id);
            abort_unless($this->visibility->canAccessLead($lead), 403);

            $companyId = $lead->company_id ?? $request->user()?->company_id;

            foreach ($request->items as $item) {
                Product::findOrFail($item['product_id']);
            }

            $subtotal = 0;
            foreach ($request->items as $item) {
                $subtotal += max(($item['qty'] * $item['unit_price']) - $item['discount'], 0);
            }

            $customerState = Quotation::normalizeState($request->customer_state)
                ?: Quotation::inferStateFromGstin($request->gst_number)
                ?: 'Tamil Nadu';
            $sellerState = Quotation::normalizeState($lead->branch?->state ?: Quotation::DEFAULT_SELLER_STATE)
                ?: Quotation::DEFAULT_SELLER_STATE;
            $taxBreakup = Quotation::calculateTaxBreakup($subtotal, $customerState, $sellerState);

            $creator = $request->user() ?: ($request->user_id ? User::find($request->user_id) : auth()->user());
            $autoApprove = (bool) ($creator?->canAutoApproveQuotation());

            $quotation = Quotation::create([
                'quotation_no'   => Quotation::generateQuotationNo($companyId),
                'quotation_date' => $request->quotation_date,
                'valid_until'    => $request->valid_until,
                'tax'            => $taxBreakup['tax_rate'],
                'subtotal'       => $subtotal,
                'tax_amount'     => $taxBreakup['tax_amount'],
                'total_amount'   => $taxBreakup['total_amount'],
                'lead_id'        => $request->lead_id,
                'company_id'     => $companyId,
                'notes'          => $request->notes,
                'is_approved'    => $autoApprove,
                'approved_by'    => $autoApprove ? $creator->id : null,
                'approved_at'    => $autoApprove ? now() : null,
                'created_by'     => $creator?->id ?? $request->user_id,
                'bill_to_address' => $request->bill_to_address,
                'ship_to_address' => $request->ship_to_address,
                'gst_number'     => strtoupper((string) ($request->gst_number ?? '')) ?: null,
                'customer_state' => $taxBreakup['customer_state'],
                'seller_state'   => $taxBreakup['seller_state'],
                'tax_type'       => $taxBreakup['tax_type'],
                'cgst_rate'      => $taxBreakup['cgst_rate'],
                'sgst_rate'      => $taxBreakup['sgst_rate'],
                'igst_rate'      => $taxBreakup['igst_rate'],
                'cgst_amount'    => $taxBreakup['cgst_amount'],
                'sgst_amount'    => $taxBreakup['sgst_amount'],
                'igst_amount'    => $taxBreakup['igst_amount'],
            ]);

            foreach ($request->items as $item) {
                QuotationItem::create([
                    'quotation_id' => $quotation->id,
                    'company_id'   => $quotation->company_id,
                    'product_id'   => $item['product_id'],
                    'description'  => $item['description'] ?? null,
                    'qty'          => $item['qty'],
                    'unit_price'   => $item['unit_price'],
                    'discount'     => $item['discount'],
                    'total'        => max(($item['qty'] * $item['unit_price']) - $item['discount'], 0),
                ]);
            }
        });

        return response()->json([
            'status'    => true,
            'message'   => 'Quotation created.',
            'quotation' => $quotation->load('items'),
        ], 201);
    }

    public function apiShow(Quotation $quotation): JsonResponse
    {
        abort_unless($this->visibility->canAccessQuotation($quotation), 403);

        return response()->json([
            'status' => true,
            'quotation' => $quotation->load(['lead', 'approver', 'createdBy', 'items.product']),
        ]);
    }

    public function apiUpdate(Request $request, Quotation $quotation): JsonResponse
    {
        abort_unless($this->visibility->canAccessQuotation($quotation), 403);

        $validated = $request->validate([
            'lead_id'        => ['required', 'exists:leads,id'],
            'quotation_date' => ['required', 'date'],
            'valid_until'    => ['required', 'date', 'after_or_equal:quotation_date'],
            'gst_number'     => ['nullable', 'string', 'max:20'],
            'customer_state' => ['required', 'string', 'max:100'],
            'notes'          => ['nullable', 'string'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.product_id'     => ['required', 'exists:products,id'],
            'items.*.description'    => ['nullable', 'string'],
            'items.*.qty'            => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price'     => ['required', 'numeric', 'min:0'],
            'items.*.discount'       => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($validated, $request, $quotation) {
            $lead = Lead::with('branch')->findOrFail($validated['lead_id']);
            abort_unless($this->visibility->canAccessLead($lead), 403);

            foreach ($validated['items'] as $item) {
                Product::findOrFail($item['product_id']);
            }

            $subtotal = 0;

            foreach ($validated['items'] as $item) {
                $subtotal += max(($item['qty'] * $item['unit_price']) - $item['discount'], 0);
            }

            $customerState = Quotation::normalizeState($validated['customer_state'])
                ?: Quotation::inferStateFromGstin($validated['gst_number'])
                ?: 'Tamil Nadu';
            $sellerState = Quotation::normalizeState($lead->branch?->state ?: Quotation::DEFAULT_SELLER_STATE)
                ?: Quotation::DEFAULT_SELLER_STATE;
            $taxBreakup = Quotation::calculateTaxBreakup($subtotal, $customerState, $sellerState);

            $quotation->update([
                'lead_id'         => $validated['lead_id'],
                'quotation_date'  => $validated['quotation_date'],
                'valid_until'     => $validated['valid_until'],
                'tax'             => $taxBreakup['tax_rate'],
                'subtotal'        => $subtotal,
                'tax_amount'      => $taxBreakup['tax_amount'],
                'total_amount'    => $taxBreakup['total_amount'],
                'notes'           => $validated['notes'] ?? null,
                'bill_to_address' => $request->bill_to_address,
                'ship_to_address' => $request->ship_to_address,
                'gst_number'      => strtoupper((string) ($validated['gst_number'] ?? '')) ?: null,
                'customer_state'  => $taxBreakup['customer_state'],
                'seller_state'    => $taxBreakup['seller_state'],
                'tax_type'        => $taxBreakup['tax_type'],
                'cgst_rate'       => $taxBreakup['cgst_rate'],
                'sgst_rate'       => $taxBreakup['sgst_rate'],
                'igst_rate'       => $taxBreakup['igst_rate'],
                'cgst_amount'     => $taxBreakup['cgst_amount'],
                'sgst_amount'     => $taxBreakup['sgst_amount'],
                'igst_amount'     => $taxBreakup['igst_amount'],
            ]);

            $quotation->items()->delete();

            foreach ($validated['items'] as $item) {
                QuotationItem::create([
                    'quotation_id' => $quotation->id,
                    'company_id'   => $quotation->company_id,
                    'product_id'   => $item['product_id'],
                    'description'  => $item['description'] ?? null,
                    'qty'          => $item['qty'],
                    'unit_price'   => $item['unit_price'],
                    'discount'     => $item['discount'],
                    'total'        => max(($item['qty'] * $item['unit_price']) - $item['discount'], 0),
                ]);
            }
        });

        return response()->json([
            'status' => true,
            'message' => 'Quotation updated successfully.',
            'quotation' => $quotation->load(['lead', 'approver', 'createdBy', 'items.product']),
        ]);
    }

    public function downloadPdf($id)
    {
        $quotation = Quotation::with('items', 'createdBy', 'lead.createdBy', 'lead.assignedTo')->findOrFail($id);
        // abort_unless(auth()->check() && $this->visibility->canAccessQuotation($quotation), 403);

        $pdf = $this->makeQuotationPdf($quotation);
        $filename = $this->quotationPdfFilename($quotation);

        return $pdf->stream($filename);
    }

    private function makeQuotationPdf(Quotation $quotation)
    {
        $quotation->loadMissing(['items.product', 'createdBy', 'lead.createdBy', 'lead.assignedTo']);
        $quoteSetting = $this->quotationSettingsFor($quotation);

        return Pdf::loadView('pages.quotations.quotation_format_1', compact('quotation', 'quoteSetting'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isPhpEnabled' => true,
            ]);
    }

    private function quotationSettingsFor(Quotation $quotation): array
    {
        return QuotationSetting::forQuotation($quotation);
    }

    private function quotationPdfFilename(Quotation $quotation): string
    {
        return 'QT-' . str_pad((string) $quotation->id, 6, '0', STR_PAD_LEFT) . '.pdf';
    }

    public function getLeadQuotations($leadId)
    {
        $lead = Lead::findOrFail($leadId);
        abort_unless($this->visibility->canAccessLead($lead), 403);

        $getQuotations = Quotation::with(['items', 'lead', 'approver'])->where('lead_id', $leadId)->latest()->get();

        return new QuotationCollection($getQuotations);
    }

    public function getAllQuotations(Request $request)
    {
        $query = Quotation::with(['items', 'lead', 'approver'])->latest();
        $this->visibility->applyQuotationVisibility($query);

        if ($request->filled('lead_id')) {
            $query->where('lead_id', $request->lead_id);
        }
        if ($request->filled('quotation_no')) {
            $query->where('quotation_no', 'like', '%' . trim($request->quotation_no) . '%');
        }
        if ($request->filled('status')) {
            if ($request->status === 'approved') {
                $query->where('is_approved', true);
            } elseif ($request->status === 'pending') {
                $query->where('is_approved', false);
            }
        }
        if ($request->filled('approved_by')) {
            $query->where('approved_by', $request->approved_by);
        }
        if ($request->filled('start_date')) {
            $query->whereDate('quotation_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('quotation_date', '<=', $request->end_date);
        }
        if ($request->filled('customer_response')) {
            if ($request->customer_response === 'pending') {
                $query->where(function ($q) {
                    $q->whereNull('customer_response')->orWhere('customer_response', 'pending');
                });
            } else {
                $query->where('customer_response', $request->customer_response);
            }
        }

        $quotations = $query->paginate(15)->withQueryString();
        $approvers  = $this->visibility->visibleAssignableUsers();

        return response()->json([
            'status'    => true,
            'data'      => $quotations->items(),
            'approvers' => $approvers->map(fn($u) => ['id' => $u->id, 'name' => $u->name]),
            'pagination' => [
                'current_page' => $quotations->currentPage(),
                'last_page'    => $quotations->lastPage(),
                'per_page'     => $quotations->perPage(),
                'total'        => $quotations->total(),
            ],
        ]);
    }

    /**
     * Map or change the Lead linked to this quotation.
     */
    public function mapLead(Request $request, Quotation $quotation)
    {
        $request->validate([
            'lead_id' => 'required|exists:leads,id',
        ]);

        $lead = Lead::withoutGlobalScopes()->findOrFail($request->lead_id);

        $quotation->update([
            'lead_id' => $lead->id,
        ]);

        $leadName = $lead->contact_name ?: ($lead->company_name ?: 'Lead #' . $lead->id);

        return redirect()->back()->with('success', "Quotation #{$quotation->quotation_no} successfully mapped to Lead #{$lead->id} ({$leadName})!");
    }

    /**
     * Manually post / push this quotation (estimate) to Zoho Books API.
     */
    public function postToZoho(int $id, ZohoBooksService $zohoService)
    {
        $quotation = Quotation::findOrFail($id);
        $companyId = $quotation->company_id ?: auth()->user()?->company_id;
        $integration = ZohoIntegration::current($companyId);

        if (! $integration || ! $integration->is_connected) {
            $msg = 'Zoho Books is not connected. Please connect via Settings first.';
            if (request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 400);
            }
            return redirect()->back()->with('error', $msg);
        }

        try {
            $result = $zohoService->createEstimateInZoho($quotation, $integration);
            $msg = "Quotation #{$quotation->quotation_no} successfully posted to Zoho Books!";

            if (request()->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'data' => $result]);
            }
            return redirect()->back()->with('success', $msg);
        } catch (\Throwable $e) {
            $err = 'Failed to post to Zoho Books: ' . $e->getMessage();
            Log::error("[ZohoManualPost] Quotation #{$quotation->quotation_no} error: " . $e->getMessage());

            if (request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => $err], 500);
            }
            return redirect()->back()->with('error', $err);
        }
    }
}
