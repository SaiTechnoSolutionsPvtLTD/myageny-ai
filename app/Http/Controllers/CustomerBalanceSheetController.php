<?php

namespace App\Http\Controllers;

use App\Models\CreditNote;
use App\Models\CustomerMaster;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\InvoiceSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerBalanceSheetController extends Controller
{
    /**
     * Display the Customer Balance Sheet / Statement of Accounts.
     */
    public function index(Request $request): View
    {
        $data = $this->prepareBalanceSheetData($request);

        return view('pages.accounts.reports.customer_balance_sheet', $data);
    }

    /**
     * Printable view of the Customer Balance Sheet (with auto print preview).
     */
    public function print(Request $request): View
    {
        $data = $this->prepareBalanceSheetData($request);

        return view('pages.accounts.reports.customer_balance_sheet_print', $data);
    }

    /**
     * Download or stream Customer Balance Sheet as PDF.
     */
    public function exportPdf(Request $request)
    {
        $data = $this->prepareBalanceSheetData($request);

        $customerSlug = $data['selectedCustomer']
            ? preg_replace('/[^A-Za-z0-9_\-]/', '_', $data['selectedCustomer']->company_name ?: $data['selectedCustomer']->customer_name)
            : 'All_Customers';

        $filename = 'Customer_Balance_Sheet_' . $customerSlug . '_' . Carbon::parse($data['dateFrom'])->format('Ymd') . '_to_' . Carbon::parse($data['dateTo'])->format('Ymd') . '.pdf';

        $pdf = Pdf::loadView('pages.accounts.reports.customer_balance_sheet_pdf', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true)
            ->setOption('isPhpEnabled', true);

        return $pdf->download($filename);
    }

    /**
     * Helper to compute unified statement/balance sheet calculations.
     */
    private function prepareBalanceSheetData(Request $request): array
    {
        $companyId = auth()->user()?->company_id;
        $setting   = InvoiceSetting::forCompany($companyId);

        // All active customers for selection dropdown
        $customers = CustomerMaster::query()
            ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
            ->active()
            ->orderBy('customer_name')
            ->get();

        // Financial Year default calculation (Starts April 1st)
        $now = Carbon::now();
        $fyStartYear = $now->month >= 4 ? $now->year : $now->year - 1;
        $defaultFrom = Carbon::create($fyStartYear, 4, 1)->format('Y-m-d');
        $defaultTo   = $now->format('Y-m-d');

        $dateFrom   = $request->input('date_from', $defaultFrom);
        $dateTo     = $request->input('date_to', $defaultTo);
        $customerId = $request->input('customer_id');

        $selectedCustomer = null;
        if (!empty($customerId) && $customerId !== 'all') {
            $selectedCustomer = CustomerMaster::query()
                ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->find($customerId);
        }

        // Mode 1: Detailed Statement for a Specific Customer
        if ($selectedCustomer) {
            return $this->getSingleCustomerStatement(
                $selectedCustomer,
                $dateFrom,
                $dateTo,
                $customers,
                $setting,
                $companyId
            );
        }

        // Mode 2: Consolidated Summary Across All Customers
        return $this->getAllCustomersSummary(
            $customers,
            $dateFrom,
            $dateTo,
            $setting,
            $companyId
        );
    }

    /**
     * Statement Calculation for a single customer.
     */
    private function getSingleCustomerStatement(
        CustomerMaster $customer,
        string $dateFrom,
        string $dateTo,
        $customers,
        $setting,
        $companyId
    ): array {
        $cId = $customer->id;

        // 1. Opening Balance calculation (Transactions strictly before $dateFrom)
        $invoicesBefore = (float) Invoice::query()
            ->where('customer_id', $cId)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('invoice_date', '<', $dateFrom)
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->sum('total_amount');

        $paymentsBefore = (float) InvoicePayment::query()
            ->where('customer_id', $cId)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('payment_date', '<', $dateFrom)
            ->sum(DB::raw('COALESCE(NULLIF(total_settled_amount, 0), amount_received + tds_amount)'));

        $creditNotesBefore = (float) CreditNote::query()
            ->where('customer_id', $cId)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('credit_note_date', '<', $dateFrom)
            ->where('status', '!=', 'void')
            ->sum('total_amount');

        $openingBalance = $invoicesBefore - $paymentsBefore - $creditNotesBefore;

        // 2. Fetch Period Invoices ($dateFrom to $dateTo)
        $periodInvoices = Invoice::query()
            ->where('customer_id', $cId)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->orderBy('invoice_date', 'asc')
            ->get();

        // 3. Fetch Period Payments ($dateFrom to $dateTo)
        $periodPayments = InvoicePayment::with('invoice')
            ->where('customer_id', $cId)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->orderBy('payment_date', 'asc')
            ->get();

        // 4. Fetch Period Credit Notes ($dateFrom to $dateTo)
        $periodCreditNotes = CreditNote::with('invoice')
            ->where('customer_id', $cId)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereBetween('credit_note_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'void')
            ->orderBy('credit_note_date', 'asc')
            ->get();

        // 5. Merge into unified chronological rows
        $rows = [];

        foreach ($periodInvoices as $inv) {
            $invDate = $inv->invoice_date ? Carbon::parse($inv->invoice_date) : Carbon::parse($inv->created_at);
            $rows[] = [
                'date'         => $invDate,
                'sort_key'     => $invDate->format('Y-m-d') . '_1_' . str_pad($inv->id, 8, '0', STR_PAD_LEFT),
                'type'         => 'invoice',
                'type_label'   => 'Tax Invoice',
                'badge_style'  => 'background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe;',
                'ref_no'       => $inv->invoice_number,
                'url'          => route('accounts.sales.invoices.show', $inv->id),
                'particulars'  => 'Tax Invoice #' . $inv->invoice_number . ($inv->due_date ? ' (Due: ' . Carbon::parse($inv->due_date)->format('d M, Y') . ')' : ''),
                'details'      => $inv->customer_notes ?: '',
                'status'       => $inv->status,
                'debit'        => (float) $inv->total_amount,
                'credit'       => 0.0,
            ];
        }

        foreach ($periodPayments as $pay) {
            $payDate = $pay->payment_date ? Carbon::parse($pay->payment_date) : Carbon::parse($pay->created_at);
            $settled = (float) ($pay->total_settled_amount > 0 ? $pay->total_settled_amount : ($pay->amount_received + $pay->tds_amount));
            $modeLabel = InvoicePayment::PAYMENT_MODES[$pay->payment_mode] ?? ucfirst($pay->payment_mode ?: 'Payment');

            $part = 'Payment Received (' . $modeLabel . ')';
            if ($pay->reference_number) {
                $part .= ' — Ref: ' . $pay->reference_number;
            }
            if ($pay->bank_name) {
                $part .= ' [' . $pay->bank_name . ']';
            }
            if ($pay->invoice) {
                $part .= ' for Inv #' . $pay->invoice->invoice_number;
            }

            $details = '';
            if ($pay->is_tds_deducted && (float) $pay->tds_amount > 0) {
                $details .= 'TDS: ₹' . number_format($pay->tds_amount, 2) . ' (' . ($pay->tds_percentage ?: '0') . '%) ';
            }
            if ($pay->notes) {
                $details .= ($details ? '| ' : '') . $pay->notes;
            }

            $rows[] = [
                'date'         => $payDate,
                'sort_key'     => $payDate->format('Y-m-d') . '_2_' . str_pad($pay->id, 8, '0', STR_PAD_LEFT),
                'type'         => 'payment',
                'type_label'   => 'Payment',
                'badge_style'  => 'background:#ecfdf5; color:#047857; border:1px solid #a7f3d0;',
                'ref_no'       => $pay->payment_number ?: ('REC-' . str_pad($pay->id, 5, '0', STR_PAD_LEFT)),
                'url'          => null,
                'particulars'  => $part,
                'details'      => $details,
                'status'       => 'Settled',
                'debit'        => 0.0,
                'credit'       => $settled,
            ];
        }

        foreach ($periodCreditNotes as $cn) {
            $cnDate = $cn->credit_note_date ? Carbon::parse($cn->credit_note_date) : Carbon::parse($cn->created_at);
            $part = 'Credit Note (' . ($cn->reason ?: 'Adjustment') . ')';
            if ($cn->invoice) {
                $part .= ' for Inv #' . $cn->invoice->invoice_number;
            }

            $rows[] = [
                'date'         => $cnDate,
                'sort_key'     => $cnDate->format('Y-m-d') . '_3_' . str_pad($cn->id, 8, '0', STR_PAD_LEFT),
                'type'         => 'credit_note',
                'type_label'   => 'Credit Note',
                'badge_style'  => 'background:#fffbeb; color:#b45309; border:1px solid #fde68a;',
                'ref_no'       => $cn->credit_note_number,
                'url'          => route('accounts.sales.credit-notes.show', $cn->id),
                'particulars'  => $part,
                'details'      => $cn->notes ?: '',
                'status'       => $cn->status ?: 'Issued',
                'debit'        => 0.0,
                'credit'       => (float) $cn->total_amount,
            ];
        }

        // Sort chronologically by date and type order
        usort($rows, function ($a, $b) {
            return strcmp($a['sort_key'], $b['sort_key']);
        });

        // Compute running balance
        $runningBalance = $openingBalance;
        $totalPeriodDebit = 0.0;
        $totalPeriodCredit = 0.0;
        $totalPeriodPaid = 0.0;
        $totalPeriodCredited = 0.0;

        foreach ($rows as &$r) {
            $runningBalance += ($r['debit'] - $r['credit']);
            $r['running_balance'] = $runningBalance;
            $totalPeriodDebit += $r['debit'];
            $totalPeriodCredit += $r['credit'];

            if ($r['type'] === 'payment') {
                $totalPeriodPaid += $r['credit'];
            } elseif ($r['type'] === 'credit_note') {
                $totalPeriodCredited += $r['credit'];
            }
        }
        unset($r);

        $closingBalance = $runningBalance;

        // Lifetime figures for this customer
        $lifetimeInvoiced = (float) Invoice::query()
            ->where('customer_id', $cId)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->sum('total_amount');

        $lifetimePaid = (float) InvoicePayment::query()
            ->where('customer_id', $cId)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->sum(DB::raw('COALESCE(NULLIF(total_settled_amount, 0), amount_received + tds_amount)'));

        $lifetimeCredited = (float) CreditNote::query()
            ->where('customer_id', $cId)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', '!=', 'void')
            ->sum('total_amount');

        $lifetimeBalanceDue = (float) Invoice::query()
            ->where('customer_id', $cId)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->sum('balance_due');

        $invoiceCount = Invoice::query()
            ->where('customer_id', $cId)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->count();

        return [
            'mode'                => 'single',
            'customers'           => $customers,
            'selectedCustomer'    => $customer,
            'dateFrom'            => $dateFrom,
            'dateTo'              => $dateTo,
            'setting'             => $setting,
            'openingBalance'      => $openingBalance,
            'rows'                => $rows,
            'closingBalance'      => $closingBalance,
            'totalPeriodDebit'    => $totalPeriodDebit,
            'totalPeriodCredit'   => $totalPeriodCredit,
            'totalPeriodPaid'     => $totalPeriodPaid,
            'totalPeriodCredited' => $totalPeriodCredited,
            'lifetimeInvoiced'    => $lifetimeInvoiced,
            'lifetimePaid'        => $lifetimePaid,
            'lifetimeCredited'    => $lifetimeCredited,
            'lifetimeBalanceDue'  => $lifetimeBalanceDue,
            'invoiceCount'        => $invoiceCount,
        ];
    }

    /**
     * Consolidated Summary for all customers.
     */
    private function getAllCustomersSummary(
        $customers,
        string $dateFrom,
        string $dateTo,
        $setting,
        $companyId
    ): array {
        $customerRows = [];
        $totalReceivables = 0.0;
        $totalPeriodInvoiced = 0.0;
        $totalPeriodCollected = 0.0;
        $totalPeriodCredited = 0.0;
        $customersWithBalance = 0;

        foreach ($customers as $c) {
            $invoicedPeriod = (float) Invoice::query()
                ->where('customer_id', $c->id)
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereBetween('invoice_date', [$dateFrom, $dateTo])
                ->whereNotIn('status', ['cancelled', 'draft'])
                ->sum('total_amount');

            $collectedPeriod = (float) InvoicePayment::query()
                ->where('customer_id', $c->id)
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereBetween('payment_date', [$dateFrom, $dateTo])
                ->sum(DB::raw('COALESCE(NULLIF(total_settled_amount, 0), amount_received + tds_amount)'));

            $creditedPeriod = (float) CreditNote::query()
                ->where('customer_id', $c->id)
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereBetween('credit_note_date', [$dateFrom, $dateTo])
                ->where('status', '!=', 'void')
                ->sum('total_amount');

            // Current live balance due across active invoices
            $currentBalance = (float) Invoice::query()
                ->where('customer_id', $c->id)
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereNotIn('status', ['cancelled', 'draft'])
                ->sum('balance_due');

            $invoiceCount = Invoice::query()
                ->where('customer_id', $c->id)
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereNotIn('status', ['cancelled', 'draft'])
                ->count();

            $lastInvoice = Invoice::query()
                ->where('customer_id', $c->id)
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->orderBy('invoice_date', 'desc')
                ->first();

            if ($currentBalance > 0.01) {
                $customersWithBalance++;
            }

            $totalReceivables      += $currentBalance;
            $totalPeriodInvoiced   += $invoicedPeriod;
            $totalPeriodCollected  += $collectedPeriod;
            $totalPeriodCredited   += $creditedPeriod;

            $customerRows[] = [
                'customer'         => $c,
                'invoiced_period'  => $invoicedPeriod,
                'collected_period' => $collectedPeriod,
                'credited_period'  => $creditedPeriod,
                'current_balance'  => $currentBalance,
                'invoice_count'    => $invoiceCount,
                'last_invoice_date'=> $lastInvoice?->invoice_date,
            ];
        }

        return [
            'mode'                 => 'all',
            'customers'            => $customers,
            'selectedCustomer'     => null,
            'dateFrom'             => $dateFrom,
            'dateTo'               => $dateTo,
            'setting'              => $setting,
            'customerRows'         => $customerRows,
            'totalReceivables'     => $totalReceivables,
            'totalPeriodInvoiced'  => $totalPeriodInvoiced,
            'totalPeriodCollected' => $totalPeriodCollected,
            'totalPeriodCredited'  => $totalPeriodCredited,
            'customersWithBalance' => $customersWithBalance,
        ];
    }
}
