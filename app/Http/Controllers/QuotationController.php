<?php

namespace App\Http\Controllers;

use App\Http\Resources\QuotationCollection;
use App\Models\Lead;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\QuotationSetting;
use App\Models\User;
use App\Services\DataVisibilityService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
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

        $defaults = [
            'quotation_no'   => Quotation::generateQuotationNo(),
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

            $quotation = Quotation::create([
                'quotation_no'    => Quotation::generateQuotationNo(),
                'quotation_date'  => $validated['quotation_date'],
                'valid_until'     => $validated['valid_until'],
                'tax'             => $taxBreakup['tax_rate'],
                'subtotal'        => $subtotal,
                'tax_amount'      => $taxBreakup['tax_amount'],
                'total_amount'    => $taxBreakup['total_amount'],
                'lead_id'         => $validated['lead_id'] ?? null,
                'company_id'      => auth()->user()?->company_id,
                'notes'           => $validated['notes'] ?? null,
                'is_approved'     => false,
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

        $quotation->load(['lead', 'items.product', 'approver']);

        return view('pages.quotations.show', compact('quotation'));
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
                'quotation' => $quotation,
                'lead' => $lead,
                'agreeUrl' => $agreeUrl,
                'disagreeUrl' => $disagreeUrl,
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

    public function customerResponse(Quotation $quotation, string $response): View
    {
        abort_unless(in_array($response, [
            Quotation::CUSTOMER_RESPONSE_AGREE,
            Quotation::CUSTOMER_RESPONSE_DISAGREE,
        ], true), 404);

        $quotation->update([
            'customer_response' => $response,
            'customer_responded_at' => now(),
        ]);

        return view('pages.quotations.customer_response', [
            'quotation' => $quotation,
            'response' => $response,
            'responseLabel' => Quotation::CUSTOMER_RESPONSES[$response],
        ]);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function destroy(Quotation $quotation): RedirectResponse
    {
        abort_unless($this->visibility->canAccessQuotation($quotation), 403);

        $quotation->delete(); // items cascade via FK

        return redirect()->route('quotations.index')
            ->with('success', 'Quotation deleted.');
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

            $quotation = Quotation::create([
                'quotation_no'   => Quotation::generateQuotationNo(),
                'quotation_date' => $request->quotation_date,
                'valid_until'    => $request->valid_until,
                'tax'            => $taxBreakup['tax_rate'],
                'subtotal'       => $subtotal,
                'tax_amount'     => $taxBreakup['tax_amount'],
                'total_amount'   => $taxBreakup['total_amount'],
                'lead_id'        => $request->lead_id,
                'company_id'     => $request->user()?->company_id,
                'notes'          => $request->notes,
                'is_approved'    => false,
                'created_by'     => $request->user_id,
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
            ]);
    }

    private function quotationSettingsFor(Quotation $quotation): array
    {
        $branchId = auth()->user()?->branch_id
            ?? $quotation->lead?->createdBy?->branch_id;

        $settings = QuotationSetting::where('branch_id', $branchId)->get();

        return [
            'logo' => $settings->where('key', 'logo')->first()?->value,
            'theme_color' => $settings->where('key', 'theme_color')->first()?->value,
            'secondary_color' => $settings->where('key', 'secondary_color')->first()?->value,
            'header_text_color' => $settings->where('key', 'header_text_color')->first()?->value,
            'prefix' => $settings->where('key', 'prefix')->first()?->value,
            'number_padding' => $settings->where('key', 'number_padding')->first()?->value,
            'terms' => $settings->where('key', 'terms')->first()?->value,
            'company_address' => $settings->where('key', 'company_address')->first()?->value,
            'company_name' => $settings->where('key', 'company_name')->first()?->value,
            'company_phone' => $settings->where('key', 'company_phone')->first()?->value,
            'company_email' => $settings->where('key', 'company_email')->first()?->value,
            'company_gstin' => $settings->where('key', 'company_gstin')->first()?->value,
            'bank_name' => $settings->where('key', 'bank_name')->first()?->value,
            'bank_account' => $settings->where('key', 'bank_account')->first()?->value,
            'bank_ifsc' => $settings->where('key', 'bank_ifsc')->first()?->value,
            'watermark_text' => $settings->where('key', 'watermark_text')->first()?->value,
            'signature' => $settings->where('key', 'signature')->first()?->value,
            'account_name' => $settings->where('key', 'account_name')->first()?->value,
            'bank_branch' => $settings->where('key', 'bank_branch')->first()?->value,
            'bank_upi' => $settings->where('key', 'bank_upi')->first()?->value,
        ];
    }

    private function quotationPdfFilename(Quotation $quotation): string
    {
        return 'QT-' . str_pad((string) $quotation->id, 6, '0', STR_PAD_LEFT) . '.pdf';
    }

    public function getLeadQuotations($leadId)
    {
        $lead = Lead::findOrFail($leadId);
        abort_unless($this->visibility->canAccessLead($lead), 403);

        $getQuotations = Quotation::with('items')->where('lead_id', $leadId)->get();

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
}
