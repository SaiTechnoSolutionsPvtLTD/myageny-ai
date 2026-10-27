<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\ZohoExpense;
use App\Models\ZohoIntegration;
use App\Models\ZohoInvoice;
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
        $totalExpenseAmount = (float) ZohoExpense::sum('total');

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
            'totalExpenseAmount',
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
            'client_id'     => 'required|string|max:255',
            'client_secret' => 'required|string',
            'region'        => 'required|string|in:' . implode(',', array_keys(ZohoBooksService::REGIONS)),
            'redirect_uri'  => 'nullable|url',
        ]);

        $integration = ZohoIntegration::current();
        $regionKey = $request->input('region');
        $regionConfig = ZohoBooksService::REGIONS[$regionKey] ?? ZohoBooksService::REGIONS['in'];

        $integration->update([
            'client_id'     => trim($request->input('client_id')),
            'client_secret' => trim($request->input('client_secret')),
            'region'        => $regionKey,
            'accounts_url'  => $regionConfig['accounts_url'],
            'api_url'       => $regionConfig['api_url'],
            'redirect_uri'  => $request->filled('redirect_uri') ? trim($request->input('redirect_uri')) : url('/accounts/zoho/callback'),
            'updated_by'    => auth()->id(),
        ]);

        return redirect()->route('accounts.zoho.settings')
            ->with('success', 'Zoho Books credentials saved successfully. You can now click "Authorize with Zoho" to connect.');
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
        $integration = ZohoIntegration::current();

        if (! $integration->is_connected || empty($integration->refresh_token)) {
            $msg = 'Zoho Books is not connected. Please connect via Settings first.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 400);
            }

            return redirect()->route('accounts.zoho.settings')->with('error', $msg);
        }

        try {
            $result = $this->zohoService->syncAllInvoices($integration, true);
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

        $query = ZohoExpense::query();

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
}
