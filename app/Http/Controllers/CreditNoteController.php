<?php

namespace App\Http\Controllers;

use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\Invoice;
use App\Models\InvoiceSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreditNoteController extends Controller
{
    /**
     * Display a listing of Credit Notes.
     */
    public function index(Request $request)
    {
        $companyId = auth()->user()?->company_id;

        $query = CreditNote::with(['invoice', 'customer', 'creator'])
            ->forCompany($companyId);

        // Search
        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('credit_note_number', 'LIKE', "%{$search}%")
                  ->orWhere('reason', 'LIKE', "%{$search}%")
                  ->orWhereHas('invoice', function ($iq) use ($search) {
                      $iq->where('invoice_number', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('customer_name', 'LIKE', "%{$search}%")
                         ->orWhere('company_name', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Reason filter
        if ($reason = $request->input('reason')) {
            $query->where('reason', $reason);
        }

        // Status filter
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Date range
        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('credit_note_date', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('credit_note_date', '<=', $dateTo);
        }

        // Summary metrics
        $allForCompany = CreditNote::forCompany($companyId);
        $totalIssuedAmount = (float) (clone $allForCompany)->where('status', '!=', 'void')->sum('total_amount');
        $totalCnCount = (clone $allForCompany)->count();
        $activeCnCount = (clone $allForCompany)->where('status', 'issued')->count();
        $voidCnCount = (clone $allForCompany)->where('status', 'void')->count();

        $creditNotes = $query->orderBy('credit_note_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        $reasons = CreditNote::REASONS;

        return view('pages.accounts.sales.credit_notes.index', compact(
            'creditNotes',
            'totalIssuedAmount',
            'totalCnCount',
            'activeCnCount',
            'voidCnCount',
            'reasons'
        ));
    }

    /**
     * Show form to create a new Credit Note.
     */
    public function create(Request $request)
    {
        $companyId = auth()->user()?->company_id;

        $invoiceId = $request->input('invoice_id');
        $selectedInvoice = null;
        if ($invoiceId) {
            $selectedInvoice = Invoice::with(['items', 'customer', 'creditNotes'])
                ->forCompany($companyId)
                ->find($invoiceId);
        }

        // List eligible invoices that are not cancelled
        $invoices = Invoice::forCompany($companyId)
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('invoice_date', 'desc')
            ->get(['id', 'invoice_number', 'customer_name', 'total_amount', 'balance_due', 'invoice_date']);

        $nextCnNumber = CreditNote::generateNextNumber($companyId);
        $reasons = CreditNote::REASONS;

        return view('pages.accounts.sales.credit_notes.create', compact(
            'invoices',
            'selectedInvoice',
            'nextCnNumber',
            'reasons'
        ));
    }

    /**
     * Store a new credit note against a specific invoice (called from Invoice Details modal).
     */
    public function storeForInvoice(Request $request, Invoice $invoice)
    {
        $companyId = auth()->user()?->company_id;
        if ($invoice->company_id && $invoice->company_id !== $companyId) {
            abort(403);
        }

        return $this->processStore($request, $invoice);
    }

    /**
     * Store a new credit note from standalone create page.
     */
    public function storeStandalone(Request $request)
    {
        $companyId = auth()->user()?->company_id;

        $request->validate([
            'invoice_id' => 'required|integer|exists:invoices,id',
        ]);

        $invoice = Invoice::forCompany($companyId)->findOrFail($request->input('invoice_id'));

        return $this->processStore($request, $invoice);
    }

    /**
     * Common logic to validate, calculate, and create a Credit Note.
     */
    protected function processStore(Request $request, Invoice $invoice)
    {
        $companyId = auth()->user()?->company_id;
        $userId    = auth()->id();

        $validated = $request->validate([
            'credit_note_date' => 'required|date',
            'reason'           => 'required|string|max:100',
            'total_amount'     => 'required|numeric|min:0.01',
            'notes'            => 'nullable|string',
            'attachment'       => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp,doc,docx|max:10240',
            'items'            => 'nullable|array',
            'items.*.item_id'  => 'nullable|integer',
            'items.*.item_name'=> 'nullable|string|max:255',
            'items.*.qty'      => 'nullable|numeric|min:0',
            'items.*.rate'     => 'nullable|numeric|min:0',
            'items.*.amount'   => 'nullable|numeric|min:0',
        ]);

        $creditAmount = round((float) $validated['total_amount'], 2);

        // Calculate maximum allowable credit for this invoice
        $existingCredits = (float) $invoice->creditNotes()->where('status', '!=', 'void')->sum('total_amount');
        $maxAllowable = max(0, round((float) $invoice->total_amount - $existingCredits, 2));

        if ($creditAmount > ($maxAllowable + 0.05)) { // allow 5 cents margin for rounding
            return redirect()->back()
                ->withInput()
                ->with('error', "Credit amount (₹" . number_format($creditAmount, 2) . ") exceeds maximum allowable creditable balance of ₹" . number_format($maxAllowable, 2) . " for Invoice #{$invoice->invoice_number}.");
        }

        // Handle attachment upload
        $attachmentPath = null;
        $attachmentName = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $destination = public_path('uploads/credit_notes');
            if (!file_exists($destination)) {
                mkdir($destination, 0755, true);
            }
            $file->move($destination, $filename);
            $attachmentPath = 'uploads/credit_notes/' . $filename;
            $attachmentName = $file->getClientOriginalName();
        }

        DB::beginTransaction();
        try {
            $cnNumber = CreditNote::generateNextNumber($companyId);

            // Compute proportionate tax breakdown if not provided
            $invTotal = (float) $invoice->total_amount;
            $ratio = $invTotal > 0 ? ($creditAmount / $invTotal) : 1;

            $subtotal   = round((float) $invoice->subtotal * $ratio, 2);
            $cgstAmount = round((float) $invoice->total_cgst * $ratio, 2);
            $sgstAmount = round((float) $invoice->total_sgst * $ratio, 2);
            $igstAmount = round((float) $invoice->total_igst * $ratio, 2);
            $totalTax   = $cgstAmount + $sgstAmount + $igstAmount;

            $creditNote = CreditNote::create([
                'company_id'         => $companyId,
                'invoice_id'         => $invoice->id,
                'customer_id'        => $invoice->customer_id,
                'credit_note_number' => $cnNumber,
                'credit_note_date'   => $validated['credit_note_date'],
                'reason'             => $validated['reason'],
                'subtotal'           => $subtotal,
                'cgst_amount'        => $cgstAmount,
                'sgst_amount'        => $sgstAmount,
                'igst_amount'        => $igstAmount,
                'total_tax'          => $totalTax,
                'total_amount'       => $creditAmount,
                'status'             => 'issued',
                'notes'              => $validated['notes'] ?? null,
                'attachment_path'    => $attachmentPath,
                'attachment_name'    => $attachmentName,
                'created_by'         => $userId,
            ]);

            // If item breakdown is provided, save item rows
            if (!empty($validated['items'])) {
                foreach ($validated['items'] as $it) {
                    if (empty($it['item_name']) && empty($it['amount'])) {
                        continue;
                    }
                    $invItem = !empty($it['item_id']) ? $invoice->items()->find($it['item_id']) : null;
                    CreditNoteItem::create([
                        'credit_note_id'  => $creditNote->id,
                        'invoice_item_id' => $invItem?->id,
                        'item_name'       => $it['item_name'] ?? ($invItem?->item_name ?: 'Credit Item'),
                        'description'     => $invItem?->description,
                        'hsn_sac_code'    => $invItem?->hsn_sac_code,
                        'qty'             => (float) ($it['qty'] ?? 1),
                        'rate'            => (float) ($it['rate'] ?? $it['amount'] ?? 0),
                        'amount'          => (float) ($it['amount'] ?? 0),
                        'cgst_percent'    => (float) ($invItem?->cgst_percent ?? 0),
                        'cgst_amount'     => (float) ($invItem?->cgst_amount ?? 0),
                        'sgst_percent'    => (float) ($invItem?->sgst_percent ?? 0),
                        'sgst_amount'     => (float) ($invItem?->sgst_amount ?? 0),
                        'igst_percent'    => (float) ($invItem?->igst_percent ?? 0),
                        'igst_amount'     => (float) ($invItem?->igst_amount ?? 0),
                        'total_amount'    => (float) ($it['amount'] ?? 0),
                    ]);
                }
            }

            // Sync invoice status and balance due
            $invoice->recalculatePaymentStatus();

            DB::commit();

            return redirect()->route('accounts.sales.invoices.show', $invoice->id)
                ->with('success', "Credit Note #{$creditNote->credit_note_number} of ₹" . number_format($creditAmount, 2) . " applied successfully to Invoice #{$invoice->invoice_number}!");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error creating Credit Note: ' . $e->getMessage());
        }
    }

    /**
     * Show Credit Note details.
     */
    public function show(CreditNote $creditNote)
    {
        $companyId = auth()->user()?->company_id;
        if ($creditNote->company_id && $creditNote->company_id !== $companyId) {
            abort(403);
        }

        $creditNote->load(['invoice.items', 'customer', 'creator', 'items']);
        $invoice = $creditNote->invoice;
        $setting = InvoiceSetting::forCompany($companyId);

        return view('pages.accounts.sales.credit_notes.show', compact('creditNote', 'invoice', 'setting'));
    }

    /**
     * Printable view of Credit Note.
     */
    public function print(CreditNote $creditNote)
    {
        $companyId = auth()->user()?->company_id;
        if ($creditNote->company_id && $creditNote->company_id !== $companyId) {
            abort(403);
        }

        $creditNote->load(['invoice.items', 'customer', 'creator', 'items']);
        $invoice = $creditNote->invoice;
        $setting = InvoiceSetting::forCompany($companyId);

        return view('pages.accounts.sales.credit_notes.print', compact('creditNote', 'invoice', 'setting'));
    }

    /**
     * Toggle void status on Credit Note.
     */
    public function toggleVoid(CreditNote $creditNote)
    {
        $companyId = auth()->user()?->company_id;
        if ($creditNote->company_id && $creditNote->company_id !== $companyId) {
            abort(403);
        }

        $newStatus = $creditNote->status === 'void' ? 'issued' : 'void';
        $creditNote->update(['status' => $newStatus]);

        // Recalculate invoice
        $creditNote->invoice?->recalculatePaymentStatus();

        $action = $newStatus === 'void' ? 'voided (cancelled)' : 'restored to active';
        return redirect()->back()->with('success', "Credit Note #{$creditNote->credit_note_number} has been {$action}. Invoice balance has been updated.");
    }

    /**
     * Delete a Credit Note and readjust invoice totals.
     */
    public function destroy(CreditNote $creditNote)
    {
        $companyId = auth()->user()?->company_id;
        if ($creditNote->company_id && $creditNote->company_id !== $companyId) {
            abort(403);
        }

        $invoice = $creditNote->invoice;
        $cnNum   = $creditNote->credit_note_number;

        if ($creditNote->attachment_path && file_exists(public_path($creditNote->attachment_path))) {
            @unlink(public_path($creditNote->attachment_path));
        }

        $creditNote->delete();

        // Recalculate invoice
        $invoice?->recalculatePaymentStatus();

        return redirect()->back()->with('success', "Credit Note #{$cnNum} deleted successfully. Invoice balance has been adjusted.");
    }
}
