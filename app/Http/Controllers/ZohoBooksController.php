<?php

namespace App\Http\Controllers;

use App\Models\AccountExpense;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Quotation;
use App\Models\ZohoExpense;
use App\Models\ZohoIntegration;
use App\Models\ZohoInvoice;
use App\Models\ZohoPayment;
use App\Models\ZohoQuotation;
use App\Services\ZohoBooksService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ZohoBooksController extends Controller
{
    public function __construct(
        protected ZohoBooksService $zohoService
    ) {}

    /**
     * Zoho Books Migration Hub Overview.
     */
    public function index(): View
    {
        $integration = ZohoIntegration::current();

        $totalInvoicesCount = ZohoInvoice::count();
        $migratedInvoicesCount = ZohoInvoice::where('is_migrated_to_native', true)->count();
        $pendingInvoicesCount = ZohoInvoice::where('is_migrated_to_native', false)->count();

        $totalInvoiceAmount = (float) ZohoInvoice::sum('total');
        $totalBalanceDue = (float) ZohoInvoice::sum('balance');
        $totalPaidAmount = max(0.0, $totalInvoiceAmount - $totalBalanceDue);

        $totalExpensesCount = ZohoExpense::count();
        $migratedExpensesCount = ZohoExpense::where('is_migrated_to_native', true)->count();
        $pendingExpensesCount = ZohoExpense::where('is_migrated_to_native', false)->count();
        $totalExpenseAmount = (float) ZohoExpense::sum('total');

        $totalPaymentsCount = ZohoPayment::count();
        $migratedPaymentsCount = ZohoPayment::where('is_migrated_to_native', true)->count();
        $pendingPaymentsCount = ZohoPayment::where('is_migrated_to_native', false)->count();
        $totalPaymentAmount = (float) ZohoPayment::sum('amount');

        $totalQuotationsCount = ZohoQuotation::count();
        $migratedQuotationsCount = ZohoQuotation::where('is_migrated_to_native', true)->count();
        $pendingQuotationsCount = ZohoQuotation::where('is_migrated_to_native', false)->count();
        $totalQuotationAmount = (float) ZohoQuotation::sum('total');

        $recentInvoices = ZohoInvoice::with('migratedInvoice')
            ->orderBy('invoice_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        return view('pages.accounts.zoho.index', compact(
            'integration',
            'totalInvoicesCount',
            'migratedInvoicesCount',
            'pendingInvoicesCount',
            'totalInvoiceAmount',
            'totalBalanceDue',
            'totalPaidAmount',
            'totalExpensesCount',
            'migratedExpensesCount',
            'pendingExpensesCount',
            'totalExpenseAmount',
            'totalPaymentsCount',
            'migratedPaymentsCount',
            'pendingPaymentsCount',
            'totalPaymentAmount',
            'totalQuotationsCount',
            'migratedQuotationsCount',
            'pendingQuotationsCount',
            'totalQuotationAmount',
            'recentInvoices'
        ));
    }

    /**
     * Settings & OAuth Setup Page.
     */
    public function settings(): View
    {
        $integration = ZohoIntegration::current();
        $regions = ZohoBooksService::REGIONS;

        return view('pages.accounts.zoho.settings', compact('integration', 'regions'));
    }

    /**
     * Save OAuth Credentials & Settings.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'client_id'       => 'required|string|max:255',
            'client_secret'   => 'required|string',
            'region'          => 'required|string|in:' . implode(',', array_keys(ZohoBooksService::REGIONS)),
            'redirect_uri'    => 'nullable|url',
            'organization_id' => 'nullable|string|max:100',
        ]);

        $integration = ZohoIntegration::current();
        $regionKey = $request->input('region');
        $regionConfig = ZohoBooksService::REGIONS[$regionKey] ?? ZohoBooksService::REGIONS['in'];

        $clientId = trim((string) $request->input('client_id'));
        // Auto-fix if "1000." prefix was accidentally pasted twice (e.g. 1000.1000.XXX -> 1000.XXX)
        if (preg_match('/^(1000\.)+/', $clientId)) {
            $clientId = preg_replace('/^(1000\.)+/', '1000.', $clientId);
        }

        $updateData = [
            'client_id'     => $clientId,
            'client_secret' => trim((string) $request->input('client_secret')),
            'region'        => $regionKey,
            'accounts_url'  => $regionConfig['accounts_url'],
            'api_url'       => $regionConfig['api_url'],
            'redirect_uri'  => $request->filled('redirect_uri') ? trim($request->input('redirect_uri')) : url('/accounts/zoho/callback'),
            'last_error'    => null,
            'updated_by'    => auth()->id(),
        ];

        if ($request->filled('organization_id')) {
            $updateData['organization_id'] = trim($request->input('organization_id'));
        }

        $integration->update($updateData);

        return redirect()->route('accounts.zoho.settings')
            ->with('success', 'Zoho Books credentials saved successfully.');
    }

    /**
     * Redirect to Zoho OAuth authorization page.
     */
    public function oauthRedirect(Request $request): RedirectResponse
    {
        $integration = ZohoIntegration::current();

        if (empty($integration->client_id) || empty($integration->client_secret)) {
            return redirect()->route('accounts.zoho.settings')
                ->with('error', 'Please configure your Zoho Client ID and Client Secret before connecting.');
        }

        $authUrl = $this->zohoService->getAuthorizationUrl($integration);

        return redirect()->away($authUrl);
    }

    /**
     * Zoho OAuth Redirect Callback Handler.
     */
    public function oauthCallback(Request $request): RedirectResponse
    {
        $integration = ZohoIntegration::current();

        if ($request->has('error')) {
            $error = $request->query('error');
            $integration->update(['last_error' => "Zoho authorization failed: {$error}"]);

            return redirect()->route('accounts.zoho.settings')
                ->with('error', "Zoho authorization error: {$error}");
        }

        $code = $request->query('code');
        if (empty($code)) {
            return redirect()->route('accounts.zoho.settings')
                ->with('error', 'No authorization code received from Zoho.');
        }

        try {
            $this->zohoService->handleAuthorizationCode($integration, $code);

            return redirect()->route('accounts.zoho.index')
                ->with('success', 'Successfully connected with Zoho Books! You can now start syncing invoices.');
        } catch (\Throwable $e) {
            return redirect()->route('accounts.zoho.settings')
                ->with('error', 'OAuth Token Exchange Failed: ' . $e->getMessage());
        }
    }

    /**
     * Manually save a Refresh Token (Self-Client flow).
     */
    public function manualToken(Request $request): RedirectResponse
    {
        $request->validate([
            'refresh_token' => 'required|string',
        ]);

        $integration = ZohoIntegration::current();

        if (empty($integration->client_id) || empty($integration->client_secret)) {
            return redirect()->route('accounts.zoho.settings')
                ->with('error', 'Please ensure Client ID and Client Secret are saved before applying Refresh Token.');
        }

        try {
            $this->zohoService->setManualRefreshToken($integration, $request->input('refresh_token'));
            $this->zohoService->fetchOrganizations($integration);

            return redirect()->route('accounts.zoho.index')
                ->with('success', 'Zoho Books Refresh Token saved and verified successfully!');
        } catch (\Throwable $e) {
            return redirect()->route('accounts.zoho.settings')
                ->with('error', 'Failed to connect using Refresh Token: ' . $e->getMessage());
        }
    }

    /**
     * Change active Organization ID.
     */
    public function selectOrganization(Request $request): RedirectResponse
    {
        $request->validate([
            'organization_id' => 'required|string',
        ]);

        $integration = ZohoIntegration::current();
        $orgId = $request->input('organization_id');

        $orgs = $integration->organizations_data ?? [];
        $orgName = $integration->organization_name;

        foreach ($orgs as $org) {
            if ((string) ($org['organization_id'] ?? '') === (string) $orgId) {
                $orgName = $org['name'] ?? $orgName;
                break;
            }
        }

        $integration->update([
            'organization_id'   => $orgId,
            'organization_name' => $orgName,
        ]);

        return redirect()->back()->with('success', "Active organization changed to '{$orgName}' ({$orgId}).");
    }

    /**
     * Refresh organizations list from Zoho Books.
     */
    public function fetchOrganizations(Request $request): RedirectResponse
    {
        $integration = ZohoIntegration::current();

        if (! $integration->is_connected) {
            return redirect()->route('accounts.zoho.settings')
                ->with('error', 'Zoho Books is not connected. Please authorize with Zoho first.');
        }

        try {
            $orgs = $this->zohoService->fetchOrganizations($integration);
            $count = count($orgs);

            return redirect()->route('accounts.zoho.settings')
                ->with('success', "Found and synced {$count} organization(s) from Zoho Books! Active Organization: " . ($integration->organization_name ?: $integration->organization_id));
        } catch (\Throwable $e) {
            return redirect()->route('accounts.zoho.settings')
                ->with('error', 'Failed to fetch organizations: ' . $e->getMessage());
        }
    }

    /**
     * Test connection to Zoho Books.
     */
    public function testConnection(): JsonResponse
    {
        $integration = ZohoIntegration::current();

        if (! $integration->is_connected || empty($integration->access_token)) {
            return response()->json([
                'success' => false,
                'message' => 'Zoho Books is not connected. Please authorize first.',
            ], 400);
        }

        try {
            $orgs = $this->zohoService->fetchOrganizations($integration);

            return response()->json([
                'success'       => true,
                'message'       => 'Connection verified successfully! Found ' . count($orgs) . ' organization(s).',
                'organizations' => $orgs,
                'active_org_id' => $integration->organization_id,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Disconnect Zoho Integration.
     */
    public function disconnect(): RedirectResponse
    {
        $integration = ZohoIntegration::current();
        $integration->update([
            'is_connected'       => false,
            'access_token'       => null,
            'refresh_token'      => null,
            'token_expires_at'   => null,
            'organizations_data' => null,
            'last_error'         => null,
        ]);

        return redirect()->route('accounts.zoho.settings')
            ->with('success', 'Zoho Books integration disconnected.');
    }

    /**
     * View Zoho Invoices List.
     */
    public function invoices(Request $request): View
    {
        $integration = ZohoIntegration::current();

        $query = ZohoInvoice::with('migratedInvoice');

        // Status Filter
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        // Migration status
        if ($request->filled('migration_status')) {
            if ($request->input('migration_status') === 'migrated') {
                $query->where('is_migrated_to_native', true);
            } elseif ($request->input('migration_status') === 'pending') {
                $query->where('is_migrated_to_native', false);
            }
        }

        // Search
        if ($request->filled('search')) {
            $s = trim($request->input('search'));
            $query->where(function ($q) use ($s) {
                $q->where('invoice_number', 'like', "%{$s}%")
                    ->orWhere('customer_name', 'like', "%{$s}%")
                    ->orWhere('company_name', 'like', "%{$s}%")
                    ->orWhere('reference_number', 'like', "%{$s}%");
            });
        }

        // Date range
        if ($request->filled('from_date')) {
            $query->whereDate('invoice_date', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('invoice_date', '<=', $request->input('to_date'));
        }

        // Summary totals of current query
        $totalCount  = (clone $query)->count();
        $totalAmount = (float) (clone $query)->sum('total');
        $totalBalance = (float) (clone $query)->sum('balance');
        $totalPaid = max(0.0, $totalAmount - $totalBalance);

        $invoices = $query->orderBy('invoice_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(25)
            ->withQueryString();

        return view('pages.accounts.zoho.invoices', compact(
            'invoices',
            'integration',
            'totalCount',
            'totalAmount',
            'totalBalance',
            'totalPaid'
        ));
    }

    /**
     * Show single Zoho Invoice details.
     */
    public function showInvoice(int $id): View
    {
        $invoice = ZohoInvoice::with('migratedInvoice.items')->findOrFail($id);
        $integration = ZohoIntegration::current();

        return view('pages.accounts.zoho.show_invoice', compact('invoice', 'integration'));
    }

    /**
     * Sync invoices from Zoho Books API.
     */
    public function syncInvoices(Request $request): RedirectResponse|JsonResponse
    {
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');
        @ini_set('memory_limit', '512M');

        $integration = ZohoIntegration::current();

        if (! $integration->is_connected || empty($integration->refresh_token)) {
            $msg = 'Zoho Books is not connected. Please connect via Settings first.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 400);
            }

            return redirect()->route('accounts.zoho.settings')->with('error', $msg);
        }

        try {
            // Fast page mode: fetch 200 invoices per page (takes ~3s for 2,000 invoices instead of 10 minutes)
            $result = $this->zohoService->syncAllInvoices($integration, false);
            $msg = "Invoices sync completed! Synced: {$result['total_synced']} ({$result['new_count']} new, {$result['updated_count']} updated).";

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'result'  => $result,
                ]);
            }

            return redirect()->route('accounts.zoho.invoices')->with('success', $msg);
        } catch (\Throwable $e) {
            $err = 'Invoice sync failed: ' . $e->getMessage();
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $err], 500);
            }

            return redirect()->back()->with('error', $err);
        }
    }

    /**
     * Batch migrate unmigrated Zoho invoices (safe chunked migration with AJAX progress bar).
     */
    public function batchMigrateInvoices(Request $request): JsonResponse
    {
        @set_time_limit(180);
        @ini_set('max_execution_time', '180');
        $limit = max(10, min(100, (int) $request->input('limit', 50)));

        $totalUnmigrated = ZohoInvoice::where('is_migrated_to_native', false)->count();
        $totalAll = ZohoInvoice::count();

        if ($totalUnmigrated === 0) {
            return response()->json([
                'success'        => true,
                'completed'      => true,
                'batch_migrated' => 0,
                'remaining'      => 0,
                'total_all'      => $totalAll,
                'total_migrated' => $totalAll,
                'progress_pct'   => 100,
                'message'        => 'All invoices have been migrated to CRM.',
            ]);
        }

        $invoices = ZohoInvoice::where('is_migrated_to_native', false)
            ->limit($limit)
            ->get();

        $migratedCount = 0;
        $errors = [];

        foreach ($invoices as $inv) {
            try {
                $this->zohoService->migrateInvoiceToNative($inv, auth()->id());
                $migratedCount++;
            } catch (\Throwable $e) {
                $errors[] = "#{$inv->invoice_number}: " . $e->getMessage();
            }
        }

        $remaining = ZohoInvoice::where('is_migrated_to_native', false)->count();
        $migratedTotal = $totalAll - $remaining;
        $progressPct = $totalAll > 0 ? round(($migratedTotal / $totalAll) * 100, 1) : 100;

        return response()->json([
            'success'        => true,
            'completed'      => $remaining === 0,
            'batch_migrated' => $migratedCount,
            'remaining'      => $remaining,
            'total_all'      => $totalAll,
            'total_migrated' => $migratedTotal,
            'progress_pct'   => $progressPct,
            'errors'         => $errors,
        ]);
    }

    /**
     * Migrate single Zoho invoice into the CRM Invoice table.
     */
    public function migrateInvoice(int $id): RedirectResponse
    {
        $zohoInvoice = ZohoInvoice::findOrFail($id);

        try {
            $nativeInvoice = $this->zohoService->migrateInvoiceToNative($zohoInvoice, auth()->id());

            return redirect()->route('accounts.zoho.show-invoice', $zohoInvoice->id)
                ->with('success', "Zoho invoice #{$zohoInvoice->invoice_number} successfully migrated into CRM Invoice #{$nativeInvoice->invoice_number}!");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Migration failed: ' . $e->getMessage());
        }
    }

    /**
     * Bulk migrate unmigrated Zoho invoices to CRM invoices.
     */
    public function bulkMigrateInvoices(Request $request): RedirectResponse
    {
        $ids = $request->input('selected_ids', []);

        if (empty($ids) || ! is_array($ids)) {
            // If no specific IDs selected, migrate all unmigrated
            $zohoInvoices = ZohoInvoice::where('is_migrated_to_native', false)->get();
        } else {
            $zohoInvoices = ZohoInvoice::whereIn('id', $ids)->get();
        }

        if ($zohoInvoices->isEmpty()) {
            return redirect()->back()->with('info', 'No unmigrated invoices to process.');
        }

        $migratedCount = 0;
        $errors = [];

        foreach ($zohoInvoices as $inv) {
            try {
                $this->zohoService->migrateInvoiceToNative($inv, auth()->id());
                $migratedCount++;
            } catch (\Throwable $e) {
                $errors[] = "#{$inv->invoice_number}: " . $e->getMessage();
            }
        }

        $msg = "{$migratedCount} invoices successfully migrated to CRM Invoices.";
        if (! empty($errors)) {
            $msg .= ' ' . count($errors) . ' invoices encountered errors.';
        }

        return redirect()->route('accounts.zoho.invoices')->with('success', $msg);
    }

    /**
     * View Zoho Expenses List.
     */
    public function expenses(Request $request): View
    {
        $integration = ZohoIntegration::current();

        $query = ZohoExpense::with('migratedExpense');

        if ($request->filled('search')) {
            $s = trim($request->input('search'));
            $query->where(function ($q) use ($s) {
                $q->where('account_name', 'like', "%{$s}%")
                    ->orWhere('vendor_name', 'like', "%{$s}%")
                    ->orWhere('customer_name', 'like', "%{$s}%")
                    ->orWhere('reference_number', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%");
            });
        }

        if ($request->filled('migration_status')) {
            if ($request->input('migration_status') === 'migrated') {
                $query->where('is_migrated_to_native', true);
            } elseif ($request->input('migration_status') === 'pending') {
                $query->where('is_migrated_to_native', false);
            }
        }

        if ($request->filled('from_date')) {
            $query->whereDate('expense_date', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('expense_date', '<=', $request->input('to_date'));
        }

        $totalCount  = (clone $query)->count();
        $totalAmount = (float) (clone $query)->sum('total');

        $expenses = $query->orderBy('expense_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(25)
            ->withQueryString();

        return view('pages.accounts.zoho.expenses', compact(
            'expenses',
            'integration',
            'totalCount',
            'totalAmount'
        ));
    }

    /**
     * Sync expenses from Zoho Books API.
     */
    public function syncExpenses(Request $request): RedirectResponse|JsonResponse
    {
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');
        @ini_set('memory_limit', '512M');

        $integration = ZohoIntegration::current();

        if (! $integration->is_connected || empty($integration->refresh_token)) {
            $msg = 'Zoho Books is not connected. Please connect via Settings first.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 400);
            }

            return redirect()->route('accounts.zoho.settings')->with('error', $msg);
        }

        try {
            $result = $this->zohoService->syncAllExpenses($integration);
            $msg = "Expenses sync completed! Synced: {$result['total_synced']} ({$result['new_count']} new, {$result['updated_count']} updated).";

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'result'  => $result,
                ]);
            }

            return redirect()->route('accounts.zoho.expenses')->with('success', $msg);
        } catch (\Throwable $e) {
            $err = 'Expense sync failed: ' . $e->getMessage();
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $err], 500);
            }

            return redirect()->back()->with('error', $err);
        }
    }

    /**
     * Migrate single Zoho Expense to native CRM AccountExpense.
     */
    public function migrateExpense(int $id): RedirectResponse
    {
        $zohoExpense = ZohoExpense::findOrFail($id);

        try {
            $nativeExpense = $this->zohoService->migrateExpenseToNative($zohoExpense, auth()->id());

            return redirect()->route('accounts.zoho.expenses')
                ->with('success', "Zoho expense successfully migrated to CRM Expense #{$nativeExpense->expense_number}!");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Expense migration failed: ' . $e->getMessage());
        }
    }

    /**
     * Bulk migrate unmigrated Zoho expenses to CRM AccountExpenses.
     */
    public function bulkMigrateExpenses(Request $request): RedirectResponse
    {
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');

        $ids = $request->input('selected_ids', []);

        if (empty($ids) || ! is_array($ids)) {
            $zohoExpenses = ZohoExpense::where('is_migrated_to_native', false)->get();
        } else {
            $zohoExpenses = ZohoExpense::whereIn('id', $ids)->get();
        }

        if ($zohoExpenses->isEmpty()) {
            return redirect()->back()->with('info', 'No unmigrated expenses to process.');
        }

        $migratedCount = 0;
        $errors = [];

        foreach ($zohoExpenses as $exp) {
            try {
                $this->zohoService->migrateExpenseToNative($exp, auth()->id());
                $migratedCount++;
            } catch (\Throwable $e) {
                $errors[] = "#{$exp->zoho_expense_id}: " . $e->getMessage();
            }
        }

        $msg = "{$migratedCount} expenses successfully migrated to CRM Expenses.";
        if (! empty($errors)) {
            $msg .= ' ' . count($errors) . ' expenses encountered errors.';
        }

        return redirect()->route('accounts.zoho.expenses')->with('success', $msg);
    }

    /**
     * Batch migrate unmigrated Zoho expenses (safe chunked migration with AJAX progress bar).
     */
    public function batchMigrateExpenses(Request $request): JsonResponse
    {
        @set_time_limit(180);
        @ini_set('max_execution_time', '180');
        $limit = max(10, min(100, (int) $request->input('limit', 50)));

        $totalUnmigrated = ZohoExpense::where('is_migrated_to_native', false)->count();
        $totalAll = ZohoExpense::count();

        if ($totalUnmigrated === 0) {
            return response()->json([
                'success'        => true,
                'completed'      => true,
                'batch_migrated' => 0,
                'remaining'      => 0,
                'total_all'      => $totalAll,
                'total_migrated' => $totalAll,
                'progress_pct'   => 100,
                'message'        => 'All expenses have been migrated to CRM.',
            ]);
        }

        $expenses = ZohoExpense::where('is_migrated_to_native', false)
            ->limit($limit)
            ->get();

        $migratedCount = 0;
        $errors = [];

        foreach ($expenses as $exp) {
            try {
                $this->zohoService->migrateExpenseToNative($exp, auth()->id());
                $migratedCount++;
            } catch (\Throwable $e) {
                $errors[] = "#{$exp->zoho_expense_id}: " . $e->getMessage();
            }
        }

        $remaining = ZohoExpense::where('is_migrated_to_native', false)->count();
        $migratedTotal = $totalAll - $remaining;
        $progressPct = $totalAll > 0 ? round(($migratedTotal / $totalAll) * 100, 1) : 100;

        return response()->json([
            'success'        => true,
            'completed'      => $remaining === 0,
            'batch_migrated' => $migratedCount,
            'remaining'      => $remaining,
            'total_all'      => $totalAll,
            'total_migrated' => $migratedTotal,
            'progress_pct'   => $progressPct,
            'errors'         => $errors,
        ]);
    }

    /**
     * View Zoho Customer Payments (Payment Collections).
     */
    public function payments(Request $request): View
    {
        $integration = ZohoIntegration::current();

        $query = ZohoPayment::with('migratedPayment');

        if ($request->filled('search')) {
            $s = trim($request->input('search'));
            $query->where(function ($q) use ($s) {
                $q->where('payment_number', 'like', "%{$s}%")
                    ->orWhere('customer_name', 'like', "%{$s}%")
                    ->orWhere('reference_number', 'like', "%{$s}%")
                    ->orWhere('bank_account_name', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%");
            });
        }

        if ($request->filled('migration_status')) {
            if ($request->input('migration_status') === 'migrated') {
                $query->where('is_migrated_to_native', true);
            } elseif ($request->input('migration_status') === 'pending') {
                $query->where('is_migrated_to_native', false);
            }
        }

        if ($request->filled('from_date')) {
            $query->whereDate('payment_date', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('payment_date', '<=', $request->input('to_date'));
        }

        $totalCount  = (clone $query)->count();
        $totalAmount = (float) (clone $query)->sum('amount');

        $payments = $query->orderBy('payment_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(25)
            ->withQueryString();

        return view('pages.accounts.zoho.payments', compact(
            'payments',
            'integration',
            'totalCount',
            'totalAmount'
        ));
    }

    /**
     * Sync Customer Payments from Zoho Books API.
     */
    public function syncPayments(Request $request): RedirectResponse|JsonResponse
    {
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');
        @ini_set('memory_limit', '512M');

        $integration = ZohoIntegration::current();

        if (! $integration->is_connected || empty($integration->refresh_token)) {
            $msg = 'Zoho Books is not connected. Please connect via Settings first.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 400);
            }

            return redirect()->route('accounts.zoho.settings')->with('error', $msg);
        }

        try {
            $result = $this->zohoService->syncAllPayments($integration, false);
            $msg = "Payments sync completed! Synced: {$result['total_synced']} ({$result['new_count']} new, {$result['updated_count']} updated).";

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'result'  => $result,
                ]);
            }

            return redirect()->route('accounts.zoho.payments')->with('success', $msg);
        } catch (\Throwable $e) {
            $err = 'Payment sync failed: ' . $e->getMessage();
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $err], 500);
            }

            return redirect()->back()->with('error', $err);
        }
    }

    /**
     * Migrate single Zoho Payment into native CRM InvoicePayment.
     */
    public function migratePayment(int $id): RedirectResponse
    {
        $zohoPayment = ZohoPayment::findOrFail($id);

        try {
            $nativePayment = $this->zohoService->migratePaymentToNative($zohoPayment, auth()->id());

            return redirect()->route('accounts.zoho.payments')
                ->with('success', "Zoho payment successfully migrated into CRM Invoice Payment #{$nativePayment->payment_number}!");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Payment migration failed: ' . $e->getMessage());
        }
    }

    /**
     * Bulk migrate unmigrated Zoho payments to CRM InvoicePayments.
     */
    public function bulkMigratePayments(Request $request): RedirectResponse
    {
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');

        $ids = $request->input('selected_ids', []);

        if (empty($ids) || ! is_array($ids)) {
            $zohoPayments = ZohoPayment::where('is_migrated_to_native', false)->get();
        } else {
            $zohoPayments = ZohoPayment::whereIn('id', $ids)->get();
        }

        if ($zohoPayments->isEmpty()) {
            return redirect()->back()->with('info', 'No unmigrated payments to process.');
        }

        $migratedCount = 0;
        $errors = [];

        foreach ($zohoPayments as $pmt) {
            try {
                $this->zohoService->migratePaymentToNative($pmt, auth()->id());
                $migratedCount++;
            } catch (\Throwable $e) {
                $errors[] = "#" . ($pmt->payment_number ?: $pmt->id) . ": " . $e->getMessage();
            }
        }

        if ($migratedCount > 0 && empty($errors)) {
            return redirect()->route('accounts.zoho.payments')
                ->with('success', "All {$migratedCount} payments successfully migrated to CRM Payments.");
        }

        if ($migratedCount > 0 && ! empty($errors)) {
            return redirect()->route('accounts.zoho.payments')
                ->with('warning', "{$migratedCount} payments migrated successfully, but " . count($errors) . " encountered issues: " . implode('; ', array_slice($errors, 0, 3)));
        }

        return redirect()->route('accounts.zoho.payments')
            ->with('error', "No payments could be migrated. Issues: " . implode('; ', array_slice($errors, 0, 3)));
    }

    /**
     * Batch migrate unmigrated Zoho payments (safe chunked migration with AJAX progress bar).
     */
    public function batchMigratePayments(Request $request): JsonResponse
    {
        @set_time_limit(180);
        @ini_set('max_execution_time', '180');
        $limit = max(10, min(100, (int) $request->input('limit', 50)));

        $totalUnmigrated = ZohoPayment::where('is_migrated_to_native', false)->count();
        $totalAll = ZohoPayment::count();

        if ($totalUnmigrated === 0) {
            return response()->json([
                'success'        => true,
                'completed'      => true,
                'batch_migrated' => 0,
                'remaining'      => 0,
                'total_all'      => $totalAll,
                'total_migrated' => $totalAll,
                'progress_pct'   => 100,
                'message'        => 'All customer payments have been migrated to CRM.',
            ]);
        }

        $payments = ZohoPayment::where('is_migrated_to_native', false)
            ->limit($limit)
            ->get();

        $migratedCount = 0;
        $errors = [];

        foreach ($payments as $pmt) {
            try {
                $this->zohoService->migratePaymentToNative($pmt, auth()->id());
                $migratedCount++;
            } catch (\Throwable $e) {
                $errors[] = "#" . ($pmt->payment_number ?: $pmt->id) . ": " . $e->getMessage();
            }
        }

        $remaining = ZohoPayment::where('is_migrated_to_native', false)->count();
        $migratedTotal = $totalAll - $remaining;
        $progressPct = $totalAll > 0 ? round(($migratedTotal / $totalAll) * 100, 1) : 100;

        return response()->json([
            'success'        => true,
            'completed'      => $remaining === 0,
            'batch_migrated' => $migratedCount,
            'remaining'      => $remaining,
            'total_all'      => $totalAll,
            'total_migrated' => $migratedTotal,
            'progress_pct'   => $progressPct,
            'errors'         => $errors,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // ESTIMATES / QUOTATIONS ACTIONS
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * List synced Zoho Quotations/Estimates.
     */
    public function quotations(Request $request): View
    {
        $integration = ZohoIntegration::current();

        $query = ZohoQuotation::with('migratedQuotation.lead');

        // Status Filter
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        // Migration status
        if ($request->filled('migration_status')) {
            if ($request->input('migration_status') === 'migrated') {
                $query->where('is_migrated_to_native', true);
            } elseif ($request->input('migration_status') === 'pending') {
                $query->where('is_migrated_to_native', false);
            }
        }

        // Search
        if ($request->filled('search')) {
            $s = trim($request->input('search'));
            $query->where(function ($q) use ($s) {
                $q->where('estimate_number', 'like', "%{$s}%")
                    ->orWhere('customer_name', 'like', "%{$s}%")
                    ->orWhere('company_name', 'like', "%{$s}%")
                    ->orWhere('reference_number', 'like', "%{$s}%");
            });
        }

        // Date range
        if ($request->filled('from_date')) {
            $query->whereDate('estimate_date', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('estimate_date', '<=', $request->input('to_date'));
        }

        // Summary totals of current query
        $totalCount  = (clone $query)->count();
        $totalAmount = (float) (clone $query)->sum('total');
        $migratedCount = (clone $query)->where('is_migrated_to_native', true)->count();
        $pendingCount  = $totalCount - $migratedCount;

        $quotations = $query->orderBy('estimate_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(25)
            ->withQueryString();

        return view('pages.accounts.zoho.quotations', compact(
            'quotations',
            'integration',
            'totalCount',
            'totalAmount',
            'migratedCount',
            'pendingCount'
        ));
    }

    /**
     * Show single Zoho Estimate details.
     */
    public function showQuotation(int $id): View
    {
        $quotation = ZohoQuotation::with('migratedQuotation.items', 'migratedQuotation.lead')->findOrFail($id);
        return view('pages.accounts.zoho.show_quotation', compact('quotation'));
    }

    /**
     * Sync Quotations / Estimates from Zoho Books API.
     * Supports both single-page AJAX chunked mode (?page=1) and full fast-sync mode.
     */
    public function syncQuotations(Request $request): RedirectResponse|JsonResponse
    {
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');
        @ini_set('memory_limit', '512M');

        $integration = ZohoIntegration::current();
        if (! $integration->is_connected) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Zoho Books is not connected.'], 400);
            }
            return redirect()->route('accounts.zoho.settings')
                ->with('error', 'Zoho Books is not connected. Please connect first.');
        }

        try {
            // Chunked AJAX mode: sync a single page at a time (< 1.5s per request)
            if ($request->has('page')) {
                $page = max(1, (int) $request->input('page', 1));
                $result = $this->zohoService->syncEstimatesPage($integration, $page, 200);

                return response()->json([
                    'success'    => true,
                    'page'       => $result['page'],
                    'has_more'   => $result['has_more'],
                    'synced_new' => $result['synced_new'],
                    'updated'    => $result['updated'],
                    'total'      => $result['total'],
                    'message'    => "Page {$page} synced: {$result['total']} quotations ({$result['synced_new']} new, {$result['updated']} updated).",
                ]);
            }

            // Fast full sync (bulk-optimized queries)
            $result = $this->zohoService->syncAllEstimates($integration, false);

            $msg = "Synced {$result['total']} quotations from Zoho Books ({$result['synced_new']} new, {$result['updated']} updated).";

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'data'    => $result,
                ]);
            }

            return redirect()->back()->with('success', $msg);
        } catch (\Throwable $e) {
            $err = "Sync failed: " . $e->getMessage();
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $err], 500);
            }
            return redirect()->back()->with('error', $err);
        }
    }

    /**
     * Migrate a single Zoho Quotation to native Quotation and link to Lead.
     */
    public function migrateQuotation(int $id): RedirectResponse
    {
        @set_time_limit(120);
        @ini_set('max_execution_time', '120');

        $zohoQuotation = ZohoQuotation::findOrFail($id);

        try {
            $nativeQuotation = $this->zohoService->migrateQuotationToNative($zohoQuotation, auth()->id());

            $leadMsg = $nativeQuotation->lead ? " & mapped to Lead '{$nativeQuotation->lead->name}'" : "";
            return redirect()->back()->with('success', "Quotation #{$nativeQuotation->quotation_no} migrated successfully{$leadMsg}!");
        } catch (\Throwable $e) {
            Log::error("Failed to migrate Zoho quotation #{$id}: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', "Failed to migrate quotation: " . $e->getMessage());
        }
    }

    /**
     * Bulk migrate selected Zoho Quotations.
     */
    public function bulkMigrateQuotations(Request $request): RedirectResponse
    {
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');
        @ini_set('memory_limit', '512M');

        $ids = $request->input('selected_ids', []);
        if (empty($ids)) {
            return redirect()->back()->with('error', 'No quotations were selected for migration.');
        }

        if (count($ids) > 15) {
            return redirect()->back()->with('error', 'To prevent Webuzo server timeout, bulk migration is limited to 15 records at a time. Please select up to 15 quotations or use "Batch Migrate All (Auto Runner)" for unlimited automatic background migration.');
        }

        $quotations = ZohoQuotation::whereIn('id', $ids)->get();
        $migrated = 0;
        $failed = 0;

        foreach ($quotations as $quotation) {
            try {
                $this->zohoService->migrateQuotationToNative($quotation, auth()->id());
                $migrated++;
            } catch (\Throwable $e) {
                Log::error("Bulk migrate error on quotation #{$quotation->estimate_number}: " . $e->getMessage());
                $failed++;
            }
        }

        $msg = "Bulk migration complete: {$migrated} quotations migrated successfully.";
        if ($failed > 0) {
            $msg .= " ({$failed} failed).";
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Batch migrate unmigrated Zoho quotations (safe chunked migration with AJAX progress bar).
     */
    public function batchMigrateQuotations(Request $request): JsonResponse
    {
        @set_time_limit(180);
        @ini_set('max_execution_time', '180');
        @ini_set('memory_limit', '512M');

        // Smaller, highly optimized batch size (default 5) to complete in < 2s and avoid any 50X timeout
        $limit = max(3, min(15, (int) $request->input('limit', 5)));

        $totalUnmigrated = ZohoQuotation::where('is_migrated_to_native', false)->count();
        $totalAll = ZohoQuotation::count();

        if ($totalUnmigrated === 0) {
            return response()->json([
                'success'        => true,
                'completed'      => true,
                'batch_migrated' => 0,
                'remaining'      => 0,
                'total_all'      => $totalAll,
                'total_migrated' => $totalAll,
                'progress_pct'   => 100,
                'message'        => 'All quotations have been migrated to CRM.',
            ]);
        }

        $quotations = ZohoQuotation::where('is_migrated_to_native', false)
            ->limit($limit)
            ->get();

        $migratedCount = 0;
        $errors = [];

        foreach ($quotations as $quotation) {
            try {
                $this->zohoService->migrateQuotationToNative($quotation, auth()->id());
                $migratedCount++;
            } catch (\Throwable $e) {
                Log::error("Batch migrate error on quotation #{$quotation->estimate_number}: " . $e->getMessage());
                $errors[] = "#{$quotation->estimate_number}: " . $e->getMessage();
            }
        }

        $remaining = ZohoQuotation::where('is_migrated_to_native', false)->count();
        $migratedTotal = $totalAll - $remaining;
        $progressPct = $totalAll > 0 ? round(($migratedTotal / $totalAll) * 100, 1) : 100;

        return response()->json([
            'success'        => true,
            'completed'      => $remaining === 0,
            'batch_migrated' => $migratedCount,
            'remaining'      => $remaining,
            'total_all'      => $totalAll,
            'total_migrated' => $migratedTotal,
            'progress_pct'   => $progressPct,
            'errors'         => $errors,
        ]);
    }

    /**
     * Auto-scan and map all unmapped native invoices & quotations to matching Leads in CRM.
     */
    public function autoMapLeads(): RedirectResponse
    {
        try {
            $stats = $this->zohoService->autoMapAllUnmappedRecords();
            $msg = "Auto-mapping complete: {$stats['invoices_mapped']} invoices and {$stats['quotations_mapped']} quotations successfully mapped to Leads!";
            return redirect()->back()->with('success', $msg);
        } catch (\Throwable $e) {
            Log::error("Failed to auto-map leads: " . $e->getMessage());
            return redirect()->back()->with('error', "Auto-mapping failed: " . $e->getMessage());
        }
    }
}
