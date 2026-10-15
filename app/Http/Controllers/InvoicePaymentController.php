<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InvoicePaymentController extends Controller
{
    /**
     * Store a new payment and TDS deduction record for the specified invoice.
     */
    public function store(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'payment_date'     => 'required|date',
            'amount_received'  => 'required|numeric|min:0',
            'is_tds_deducted'  => 'nullable|boolean',
            'tds_percentage'   => 'nullable|numeric|min:0|max:100',
            'tds_amount'       => 'nullable|numeric|min:0',
            'payment_mode'     => 'required|string|max:50',
            'reference_number' => 'nullable|string|max:100',
            'bank_name'        => 'nullable|string|max:100',
            'notes'            => 'nullable|string',
            'attachment'       => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp,doc,docx|max:10240',
        ]);

        $isTds = $request->boolean('is_tds_deducted');
        $amountReceived = (float) $validated['amount_received'];
        $tdsPct = $isTds ? (float) ($validated['tds_percentage'] ?? 0) : 0;
        $tdsAmount = $isTds ? (float) ($validated['tds_amount'] ?? 0) : 0;
        $totalSettled = $amountReceived + $tdsAmount;

        if ($totalSettled <= 0) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Total payment amount (Amount Received + TDS) must be greater than zero.');
        }

        // Handle attachment upload
        $attachmentPath = null;
        $attachmentName = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $destination = public_path('uploads/invoices/payments');
            if (!file_exists($destination)) {
                mkdir($destination, 0755, true);
            }
            $file->move($destination, $filename);
            $attachmentPath = 'uploads/invoices/payments/' . $filename;
            $attachmentName = $file->getClientOriginalName();
        }

        $payCount = InvoicePayment::where('company_id', $invoice->company_id)->count() + 1;
        $paymentNumber = 'RCP-' . date('ym') . '-' . str_pad($payCount, 4, '0', STR_PAD_LEFT);

        InvoicePayment::create([
            'company_id'           => $invoice->company_id,
            'invoice_id'           => $invoice->id,
            'customer_id'          => $invoice->customer_id,
            'payment_number'       => $paymentNumber,
            'payment_date'         => $validated['payment_date'],
            'amount_received'      => $amountReceived,
            'is_tds_deducted'      => $isTds,
            'tds_percentage'       => $tdsPct,
            'tds_amount'           => $tdsAmount,
            'total_settled_amount' => $totalSettled,
            'payment_mode'         => $validated['payment_mode'],
            'reference_number'     => $validated['reference_number'] ?? null,
            'bank_name'            => $validated['bank_name'] ?? null,
            'notes'                => $validated['notes'] ?? null,
            'attachment_path'      => $attachmentPath,
            'attachment_name'      => $attachmentName,
            'recorded_by'          => auth()->id(),
        ]);

        // Recalculate invoice totals and status
        $invoice->recalculatePaymentStatus();

        return redirect()->back()->with('success', 'Payment of ₹' . number_format($amountReceived, 2) . ($isTds ? ' (+ ₹' . number_format($tdsAmount, 2) . ' TDS)' : '') . ' recorded successfully against Invoice #' . $invoice->invoice_number . '!');
    }

    /**
     * Delete a payment record and recalculate invoice balance.
     */
    public function destroy(Invoice $invoice, InvoicePayment $payment)
    {
        if ($payment->invoice_id !== $invoice->id) {
            abort(404);
        }

        if ($payment->attachment_path && file_exists(public_path($payment->attachment_path))) {
            @unlink(public_path($payment->attachment_path));
        }

        $payment->delete();

        // Recalculate invoice totals and status
        $invoice->recalculatePaymentStatus();

        return redirect()->back()->with('success', 'Payment record deleted and invoice balance successfully updated.');
    }

    /**
     * Display a listing of all payments received against sales invoices.
     */
    public function index(Request $request)
    {
        $companyId = auth()->user()?->company_id;

        $query = InvoicePayment::query()
            ->with(['invoice', 'customer', 'recordedBy'])
            ->latest('payment_date')
            ->latest('id');

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        // Search by payment #, invoice #, customer name, reference #, bank name
        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('payment_number', 'LIKE', "%{$search}%")
                  ->orWhere('reference_number', 'LIKE', "%{$search}%")
                  ->orWhere('bank_name', 'LIKE', "%{$search}%")
                  ->orWhereHas('invoice', function ($iq) use ($search) {
                      $iq->where('invoice_number', 'LIKE', "%{$search}%")
                         ->orWhere('customer_name', 'LIKE', "%{$search}%")
                         ->orWhere('company_name', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('customer_name', 'LIKE', "%{$search}%")
                         ->orWhere('company_name', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Payment Mode Filter
        if ($mode = $request->input('payment_mode')) {
            $query->where('payment_mode', $mode);
        }

        // TDS Filter
        if ($request->has('has_tds') && $request->input('has_tds') !== '') {
            if ($request->boolean('has_tds')) {
                $query->where(function($q) {
                    $q->where('is_tds_deducted', true)->orWhere('tds_amount', '>', 0);
                });
            } else {
                $query->where(function($q) {
                    $q->where('is_tds_deducted', false)->where('tds_amount', '<=', 0);
                });
            }
        }

        // Date Range Filter
        if ($from = $request->input('from_date')) {
            $query->whereDate('payment_date', '>=', $from);
        }
        if ($to = $request->input('to_date')) {
            $query->whereDate('payment_date', '<=', $to);
        }

        // KPI aggregates
        $baseQuery = InvoicePayment::query();
        if ($companyId) {
            $baseQuery->where('company_id', $companyId);
        }

        $allPayments = (clone $baseQuery)->get();
        $stats = [
            'total_count'    => $allPayments->count(),
            'total_received' => (float) $allPayments->sum('amount_received'),
            'total_tds'      => (float) $allPayments->sum('tds_amount'),
            'total_settled'  => (float) $allPayments->sum('total_settled_amount'),
            'tds_count'      => $allPayments->where('tds_amount', '>', 0)->count(),
        ];

        $payments = $query->paginate(20)->withQueryString();

        return view('pages.accounts.sales.payments.index', compact('payments', 'stats'));
    }

    /**
     * Delete a payment record directly from the payments list and recalculate invoice balance.
     */
    public function destroyStandalone(InvoicePayment $payment)
    {
        $invoice = $payment->invoice;

        if ($payment->attachment_path && file_exists(public_path($payment->attachment_path))) {
            @unlink(public_path($payment->attachment_path));
        }

        $paymentNumber = $payment->payment_number;
        $payment->delete();

        if ($invoice) {
            $invoice->recalculatePaymentStatus();
        }

        return redirect()->back()->with('success', 'Payment record #' . $paymentNumber . ' deleted successfully.');
    }
}
