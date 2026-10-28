<?php

namespace App\Services;

use App\Models\AccountExpense;
use App\Models\AccountExpenseCategory;
use App\Models\AccountExpenseSubcategory;
use App\Models\CustomerMaster;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\VendorMaster;
use App\Models\ZohoExpense;
use App\Models\ZohoIntegration;
use App\Models\ZohoInvoice;
use App\Models\ZohoPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ZohoBooksService
{
    /**
     * Map of supported Zoho data centers and their auth/API base URLs.
     */
    public const REGIONS = [
        'in' => [
            'label'        => 'India (.in) - accounts.zoho.in',
            'accounts_url' => 'https://accounts.zoho.in',
            'api_url'      => 'https://www.zohoapis.in',
        ],
        'com' => [
            'label'        => 'United States / Global (.com) - accounts.zoho.com',
            'accounts_url' => 'https://accounts.zoho.com',
            'api_url'      => 'https://www.zohoapis.com',
        ],
        'eu' => [
            'label'        => 'Europe (.eu) - accounts.zoho.eu',
            'accounts_url' => 'https://accounts.zoho.eu',
            'api_url'      => 'https://www.zohoapis.eu',
        ],
        'com.au' => [
            'label'        => 'Australia (.com.au) - accounts.zoho.com.au',
            'accounts_url' => 'https://accounts.zoho.com.au',
            'api_url'      => 'https://www.zohoapis.com.au',
        ],
        'jp' => [
            'label'        => 'Japan (.jp) - accounts.zoho.jp',
            'accounts_url' => 'https://accounts.zoho.jp',
            'api_url'      => 'https://www.zohoapis.jp',
        ],
        'ca' => [
            'label'        => 'Canada (.ca) - accounts.zohocloud.ca',
            'accounts_url' => 'https://accounts.zohocloud.ca',
            'api_url'      => 'https://www.zohoapis.ca',
        ],
    ];

    /**
     * Required Zoho Books OAuth Scopes.
     */
    public const SCOPES = 'ZohoBooks.fullaccess.all,ZohoBooks.invoices.all,ZohoBooks.expenses.all,ZohoBooks.customerpayments.all,ZohoBooks.settings.READ,ZohoBooks.contacts.READ';

    /**
     * Build the Zoho OAuth authorization redirect URL.
     */
    public function getAuthorizationUrl(ZohoIntegration $integration, ?string $redirectUri = null): string
    {
        $redirectUri = $redirectUri ?: $integration->redirect_uri ?: url('/accounts/zoho/callback');
        $accountsUrl = rtrim($integration->accounts_url ?: 'https://accounts.zoho.in', '/');

        $params = http_build_query([
            'scope'         => self::SCOPES,
            'client_id'     => $integration->client_id,
            'response_type' => 'code',
            'redirect_uri'  => $redirectUri,
            'access_type'   => 'offline',
            'prompt'        => 'consent',
        ]);

        return "{$accountsUrl}/oauth/v2/auth?{$params}";
    }

    /**
     * Exchange the short-lived authorization code for access and refresh tokens.
     */
    public function handleAuthorizationCode(ZohoIntegration $integration, string $code, ?string $redirectUri = null): array
    {
        $redirectUri = $redirectUri ?: $integration->redirect_uri ?: url('/accounts/zoho/callback');
        $accountsUrl = rtrim($integration->accounts_url ?: 'https://accounts.zoho.in', '/');

        $response = Http::asForm()->post("{$accountsUrl}/oauth/v2/token", [
            'code'          => trim($code),
            'client_id'     => trim($integration->client_id),
            'client_secret' => trim($integration->client_secret),
            'redirect_uri'  => $redirectUri,
            'grant_type'    => 'authorization_code',
        ]);

        if (! $response->successful()) {
            $err = $response->json('error') ?? $response->body();
            $integration->update(['last_error' => "OAuth Token Exchange Failed: {$err}"]);
            throw new \Exception("Zoho Token Exchange failed: " . ($response->json('error') ?: $response->body()));
        }

        $data = $response->json();

        if (isset($data['error'])) {
            $integration->update(['last_error' => "OAuth Error: {$data['error']}"]);
            throw new \Exception("Zoho OAuth Error: {$data['error']}");
        }

        $expiresIn = (int) ($data['expires_in'] ?? 3600);
        $updateData = [
            'access_token'     => $data['access_token'] ?? null,
            'token_expires_at' => Carbon::now()->addSeconds(max(0, $expiresIn - 120)),
            'is_connected'     => true,
            'last_error'       => null,
        ];

        if (! empty($data['refresh_token'])) {
            $updateData['refresh_token'] = $data['refresh_token'];
        }

        if (! empty($data['api_domain'])) {
            $updateData['api_url'] = rtrim($data['api_domain'], '/');
        }

        $integration->update($updateData);

        // Immediately fetch organizations
        try {
            $this->fetchOrganizations($integration);
        } catch (\Throwable $e) {
            Log::warning('Zoho fetch organizations warning: ' . $e->getMessage());
        }

        return $data;
    }

    /**
     * Set refresh token manually (e.g. from Zoho Self-Client).
     */
    public function setManualRefreshToken(ZohoIntegration $integration, string $refreshToken): bool
    {
        $integration->refresh_token = trim($refreshToken);
        $integration->save();

        return $this->refreshAccessToken($integration);
    }

    /**
     * Refresh the access token using the stored refresh token.
     */
    public function refreshAccessToken(ZohoIntegration $integration): bool
    {
        if (empty($integration->refresh_token)) {
            throw new \Exception("No refresh token available to refresh access token.");
        }

        $accountsUrl = rtrim($integration->accounts_url ?: 'https://accounts.zoho.in', '/');

        $response = Http::asForm()->post("{$accountsUrl}/oauth/v2/token", [
            'refresh_token' => trim($integration->refresh_token),
            'client_id'     => trim($integration->client_id),
            'client_secret' => trim($integration->client_secret),
            'grant_type'    => 'refresh_token',
        ]);

        if (! $response->successful()) {
            $err = $response->json('error') ?? $response->body();
            $integration->update([
                'is_connected' => false,
                'last_error'   => "Token refresh failed: {$err}",
            ]);
            throw new \Exception("Failed to refresh Zoho token: {$err}");
        }

        $data = $response->json();

        if (isset($data['error'])) {
            $integration->update([
                'is_connected' => false,
                'last_error'   => "Token refresh error: {$data['error']}",
            ]);
            throw new \Exception("Zoho Token refresh error: {$data['error']}");
        }

        $expiresIn = (int) ($data['expires_in'] ?? 3600);
        $update = [
            'access_token'     => $data['access_token'],
            'token_expires_at' => Carbon::now()->addSeconds(max(0, $expiresIn - 120)),
            'is_connected'     => true,
            'last_error'       => null,
        ];

        if (! empty($data['api_domain'])) {
            $update['api_url'] = rtrim($data['api_domain'], '/');
        }

        $integration->update($update);

        return true;
    }

    /**
     * Ensure integration has an active, valid access token.
     */
    public function ensureValidAccessToken(ZohoIntegration $integration): string
    {
        if ($integration->isTokenExpired()) {
            $this->refreshAccessToken($integration);
            $integration->refresh();
        }

        return (string) $integration->access_token;
    }

    /**
     * Fetch list of organizations the user has access to in Zoho Books.
     */
    public function fetchOrganizations(ZohoIntegration $integration): array
    {
        $token = $this->ensureValidAccessToken($integration);
        $apiUrl = rtrim($integration->api_url ?: 'https://www.zohoapis.in', '/');

        $response = Http::withHeaders([
            'Authorization' => "Zoho-oauthtoken {$token}",
        ])->get("{$apiUrl}/books/v3/organizations");

        if (! $response->successful()) {
            $err = $response->json('message') ?? $response->body();
            throw new \Exception("Failed to fetch Zoho organizations: {$err}");
        }

        $data = $response->json();

        if (isset($data['code']) && (int) $data['code'] !== 0) {
            $err = $data['message'] ?? 'Zoho Error Code ' . $data['code'];
            throw new \Exception("Zoho organizations error: {$err}");
        }

        $orgs = $data['organizations'] ?? [];

        $update = [
            'organizations_data' => $orgs,
            'last_error'         => null,
        ];

        // Auto-select default/first organization if none is active
        if (empty($integration->organization_id) && count($orgs) > 0) {
            $defaultOrg = collect($orgs)->firstWhere('is_default_org', true) ?: $orgs[0];
            $update['organization_id']   = (string) ($defaultOrg['organization_id'] ?? '');
            $update['organization_name'] = (string) ($defaultOrg['name'] ?? '');
        }

        $integration->update($update);

        return $orgs;
    }

    /**
     * Fetch a paginated list of invoices from Zoho Books.
     */
    public function fetchInvoices(ZohoIntegration $integration, int $page = 1, int $perPage = 200, array $filters = []): array
    {
        if (empty($integration->organization_id)) {
            $this->fetchOrganizations($integration);
            $integration->refresh();
            if (empty($integration->organization_id)) {
                throw new \Exception("Please select an active Zoho Books Organization in Settings before fetching invoices.");
            }
        }

        $token = $this->ensureValidAccessToken($integration);
        $apiUrl = rtrim($integration->api_url ?: 'https://www.zohoapis.in', '/');

        $queryParams = array_merge([
            'organization_id' => $integration->organization_id,
            'page'            => $page,
            'per_page'        => $perPage,
        ], $filters);

        $response = Http::withHeaders([
            'Authorization' => "Zoho-oauthtoken {$token}",
        ])->get("{$apiUrl}/books/v3/invoices", $queryParams);

        if (! $response->successful()) {
            $msg = $response->json('message') ?: $response->body();
            throw new \Exception("Zoho API error: {$msg}");
        }

        return $response->json();
    }

    /**
     * Fetch details of a single invoice (includes full line items and billing addresses).
     */
    public function fetchInvoiceDetails(ZohoIntegration $integration, string $zohoInvoiceId): array
    {
        $token = $this->ensureValidAccessToken($integration);
        $apiUrl = rtrim($integration->api_url ?: 'https://www.zohoapis.in', '/');

        $response = Http::withHeaders([
            'Authorization' => "Zoho-oauthtoken {$token}",
        ])->get("{$apiUrl}/books/v3/invoices/{$zohoInvoiceId}", [
            'organization_id' => $integration->organization_id,
        ]);

        if (! $response->successful()) {
            $msg = $response->json('message') ?: $response->body();
            throw new \Exception("Zoho invoice detail error for ID {$zohoInvoiceId}: {$msg}");
        }

        $data = $response->json();

        return $data['invoice'] ?? [];
    }

    /**
     * Sync and import all invoices from Zoho Books into `zoho_invoices` table.
     */
    public function syncAllInvoices(ZohoIntegration $integration, bool $fetchDetailedItems = true): array
    {
        $page = 1;
        $hasMore = true;
        $totalSynced = 0;
        $newCount = 0;
        $updatedCount = 0;

        while ($hasMore) {
            $result = $this->fetchInvoices($integration, $page, 200);
            $invoices = $result['invoices'] ?? [];

            if (empty($invoices)) {
                break;
            }

            foreach ($invoices as $invSummary) {
                $zohoId = (string) ($invSummary['invoice_id'] ?? '');
                if (! $zohoId) {
                    continue;
                }

                $fullDetail = $invSummary;
                if ($fetchDetailedItems) {
                    try {
                        $fetched = $this->fetchInvoiceDetails($integration, $zohoId);
                        if (! empty($fetched)) {
                            $fullDetail = $fetched;
                        }
                    } catch (\Throwable $e) {
                        Log::warning("Could not fetch detailed line items for Zoho invoice {$zohoId}: " . $e->getMessage());
                    }
                }

                $existing = ZohoInvoice::where('zoho_invoice_id', $zohoId)->first();
                $isNew = ! $existing;

                $data = [
                    'company_id'       => $integration->company_id,
                    'zoho_invoice_id'  => $zohoId,
                    'invoice_number'   => (string) ($fullDetail['invoice_number'] ?? $invSummary['invoice_number'] ?? ''),
                    'customer_id'      => (string) ($fullDetail['customer_id'] ?? $invSummary['customer_id'] ?? ''),
                    'customer_name'    => (string) ($fullDetail['customer_name'] ?? $invSummary['customer_name'] ?? 'Unknown Customer'),
                    'company_name'     => (string) ($fullDetail['company_name'] ?? ''),
                    'invoice_date'     => ! empty($fullDetail['date']) ? Carbon::parse($fullDetail['date'])->toDateString() : now()->toDateString(),
                    'due_date'         => ! empty($fullDetail['due_date']) ? Carbon::parse($fullDetail['due_date'])->toDateString() : null,
                    'status'           => strtolower((string) ($fullDetail['status'] ?? $invSummary['status'] ?? 'draft')),
                    'currency_code'    => (string) ($fullDetail['currency_code'] ?? 'INR'),
                    'currency_symbol'  => (string) ($fullDetail['currency_symbol'] ?? '₹'),
                    'exchange_rate'    => (float) ($fullDetail['exchange_rate'] ?? 1.0),
                    'sub_total'        => (float) ($fullDetail['sub_total'] ?? 0.0),
                    'tax_total'        => (float) ($fullDetail['tax_total'] ?? 0.0),
                    'total'            => (float) ($fullDetail['total'] ?? $invSummary['total'] ?? 0.0),
                    'balance'          => (float) ($fullDetail['balance'] ?? $invSummary['balance'] ?? 0.0),
                    'payment_terms'    => (string) ($fullDetail['payment_terms_label'] ?? $fullDetail['payment_terms'] ?? ''),
                    'reference_number' => (string) ($fullDetail['reference_number'] ?? ''),
                    'billing_address'  => $fullDetail['billing_address'] ?? null,
                    'shipping_address' => $fullDetail['shipping_address'] ?? null,
                    'line_items'       => $fullDetail['line_items'] ?? [],
                    'raw_data'         => $fullDetail,
                    'synced_at'        => now(),
                ];

                ZohoInvoice::updateOrCreate(
                    ['zoho_invoice_id' => $zohoId],
                    $data
                );

                $totalSynced++;
                if ($isNew) {
                    $newCount++;
                } else {
                    $updatedCount++;
                }
            }

            $pageContext = $result['page_context'] ?? [];
            $hasMore = ! empty($pageContext['has_more_page']);
            $page++;
        }

        $integration->update([
            'last_synced_at' => now(),
            'last_error'     => null,
        ]);

        return [
            'total_synced'  => $totalSynced,
            'new_count'     => $newCount,
            'updated_count' => $updatedCount,
        ];
    }

    /**
     * Migrate / Convert a ZohoInvoice into a native application Invoice (`invoices` & `invoice_items`).
     */
    public function migrateInvoiceToNative(ZohoInvoice $zohoInvoice, ?int $userId = null): Invoice
    {
        return DB::transaction(function () use ($zohoInvoice, $userId) {
            $companyId = $zohoInvoice->company_id ?: 1;

            // 1. Find or create matching Customer in CustomerMaster
            $customerName = trim($zohoInvoice->customer_name ?: 'Zoho Customer');
            $customer = CustomerMaster::firstOrCreate(
                [
                    'company_name' => $customerName,
                ],
                [
                    'contact_person'   => $zohoInvoice->billing_address['attention'] ?? $customerName,
                    'email'            => $zohoInvoice->raw_data['email'] ?? null,
                    'phone'            => $zohoInvoice->raw_data['phone'] ?? null,
                    'billing_address'  => $zohoInvoice->billing_address['address'] ?? null,
                    'billing_city'     => $zohoInvoice->billing_address['city'] ?? null,
                    'billing_state'    => $zohoInvoice->billing_address['state'] ?? null,
                    'billing_pincode'  => $zohoInvoice->billing_address['zip'] ?? null,
                    'billing_country'  => $zohoInvoice->billing_address['country'] ?? 'India',
                    'shipping_address' => $zohoInvoice->shipping_address['address'] ?? null,
                    'shipping_city'    => $zohoInvoice->shipping_address['city'] ?? null,
                    'shipping_state'   => $zohoInvoice->shipping_address['state'] ?? null,
                    'shipping_pincode' => $zohoInvoice->shipping_address['zip'] ?? null,
                    'shipping_country' => $zohoInvoice->shipping_address['country'] ?? 'India',
                    'gstin'            => $zohoInvoice->raw_data['gst_no'] ?? null,
                    'pan'              => $zohoInvoice->raw_data['pan_no'] ?? null,
                    'created_by'       => $userId,
                ]
            );

            // Determine status
            $zohoStatus = strtolower($zohoInvoice->status);
            $nativeStatus = match ($zohoStatus) {
                'paid'           => 'paid',
                'partially_paid' => 'partially_paid',
                'overdue'        => 'overdue',
                'void'           => 'cancelled',
                'sent'           => 'sent',
                default          => 'draft',
            };

            // Check if native invoice with this number already exists
            $existingInvoice = Invoice::where('invoice_number', $zohoInvoice->invoice_number)->first();

            $totalAmount = (float) $zohoInvoice->total;
            $balanceDue  = (float) $zohoInvoice->balance;
            $paidAmount  = max(0.00, $totalAmount - $balanceDue);

            $billing = $zohoInvoice->billing_address ?? [];
            $shipping = $zohoInvoice->shipping_address ?? [];

            $invoiceData = [
                'company_id'         => $companyId,
                'customer_id'        => $customer->id,
                'invoice_number'     => $zohoInvoice->invoice_number,
                'invoice_date'       => $zohoInvoice->invoice_date,
                'due_date'           => $zohoInvoice->due_date,
                'payment_terms'      => $zohoInvoice->payment_terms,
                'place_of_supply'    => $zohoInvoice->raw_data['place_of_supply'] ?? null,
                'customer_name'      => $customerName,
                'company_name'       => $zohoInvoice->company_name ?: $customerName,
                'customer_email'     => $zohoInvoice->raw_data['email'] ?? $customer->email,
                'customer_phone'     => $zohoInvoice->raw_data['phone'] ?? $customer->phone,
                'customer_gstin'     => $zohoInvoice->raw_data['gst_no'] ?? $customer->gstin,
                'customer_pan'       => $zohoInvoice->raw_data['pan_no'] ?? $customer->pan,
                'billing_attention'  => $billing['attention'] ?? null,
                'billing_address'    => $billing['address'] ?? null,
                'billing_city'       => $billing['city'] ?? null,
                'billing_state'      => $billing['state'] ?? null,
                'billing_pincode'    => $billing['zip'] ?? null,
                'billing_country'    => $billing['country'] ?? 'India',
                'shipping_attention' => $shipping['attention'] ?? null,
                'shipping_address'   => $shipping['address'] ?? null,
                'shipping_city'      => $shipping['city'] ?? null,
                'shipping_state'     => $shipping['state'] ?? null,
                'shipping_pincode'   => $shipping['zip'] ?? null,
                'shipping_country'   => $shipping['country'] ?? 'India',
                'subtotal'           => (float) $zohoInvoice->sub_total,
                'total_tax'          => (float) $zohoInvoice->tax_total,
                'total_amount'       => $totalAmount,
                'paid_amount'        => $paidAmount,
                'balance_due'        => $balanceDue,
                'customer_notes'     => $zohoInvoice->raw_data['notes'] ?? null,
                'terms_conditions'   => $zohoInvoice->raw_data['terms'] ?? null,
                'status'             => $nativeStatus,
                'created_by'         => $userId,
                'updated_by'         => $userId,
            ];

            if ($existingInvoice) {
                $existingInvoice->update($invoiceData);
                $nativeInvoice = $existingInvoice;
                // Delete existing items to replace with fresh ones
                $nativeInvoice->items()->delete();
            } else {
                $nativeInvoice = Invoice::create($invoiceData);
            }

            // Create line items
            $lineItems = $zohoInvoice->line_items ?? [];
            if (! empty($lineItems) && is_array($lineItems)) {
                $order = 1;
                foreach ($lineItems as $item) {
                    $qty = (float) ($item['quantity'] ?? 1);
                    $rate = (float) ($item['rate'] ?? 0);
                    $amount = (float) ($item['item_total'] ?? ($qty * $rate));

                    InvoiceItem::create([
                        'invoice_id'   => $nativeInvoice->id,
                        'item_name'    => $item['name'] ?? 'Item',
                        'description'  => $item['description'] ?? null,
                        'hsn_sac_code' => $item['hsn_or_sac'] ?? null,
                        'qty'          => $qty,
                        'rate'         => $rate,
                        'amount'       => $amount,
                        'total_amount' => $amount,
                        'sort_order'   => $order++,
                    ]);
                }
            } else {
                // Single default line item if none specified
                InvoiceItem::create([
                    'invoice_id'   => $nativeInvoice->id,
                    'item_name'    => 'Consulting / Services',
                    'description'  => 'Imported from Zoho Books',
                    'qty'          => 1,
                    'rate'         => (float) $zohoInvoice->sub_total,
                    'amount'       => (float) $zohoInvoice->sub_total,
                    'total_amount' => (float) $zohoInvoice->total,
                    'sort_order'   => 1,
                ]);
            }

            // Mark ZohoInvoice as migrated
            $zohoInvoice->update([
                'is_migrated_to_native'      => true,
                'migrated_native_invoice_id' => $nativeInvoice->id,
            ]);

            return $nativeInvoice;
        });
    }

    /**
     * Fetch a paginated list of expenses from Zoho Books.
     */
    public function fetchExpenses(ZohoIntegration $integration, int $page = 1, int $perPage = 200): array
    {
        $token = $this->ensureValidAccessToken($integration);
        $apiUrl = rtrim($integration->api_url ?: 'https://www.zohoapis.in', '/');

        $response = Http::withHeaders([
            'Authorization' => "Zoho-oauthtoken {$token}",
        ])->get("{$apiUrl}/books/v3/expenses", [
            'organization_id' => $integration->organization_id,
            'page'            => $page,
            'per_page'        => $perPage,
        ]);

        if (! $response->successful()) {
            $msg = $response->json('message') ?: $response->body();
            throw new \Exception("Zoho API Expenses error: {$msg}");
        }

        return $response->json();
    }

    /**
     * Sync and import all expenses from Zoho Books into `zoho_expenses` table.
     */
    public function syncAllExpenses(ZohoIntegration $integration): array
    {
        $page = 1;
        $hasMore = true;
        $totalSynced = 0;
        $newCount = 0;
        $updatedCount = 0;

        while ($hasMore) {
            $result = $this->fetchExpenses($integration, $page, 200);
            $expenses = $result['expenses'] ?? [];

            if (empty($expenses)) {
                break;
            }

            foreach ($expenses as $exp) {
                $zohoId = (string) ($exp['expense_id'] ?? '');
                if (! $zohoId) {
                    continue;
                }

                $existing = ZohoExpense::where('zoho_expense_id', $zohoId)->first();
                $isNew = ! $existing;

                ZohoExpense::updateOrCreate(
                    ['zoho_expense_id' => $zohoId],
                    [
                        'company_id'               => $integration->company_id,
                        'zoho_expense_id'          => $zohoId,
                        'expense_date'             => ! empty($exp['date']) ? Carbon::parse($exp['date'])->toDateString() : now()->toDateString(),
                        'account_id'               => (string) ($exp['account_id'] ?? ''),
                        'account_name'             => (string) ($exp['account_name'] ?? ''),
                        'paid_through_account_id'  => (string) ($exp['paid_through_account_id'] ?? ''),
                        'paid_through_account_name' => (string) ($exp['paid_through_account_name'] ?? ''),
                        'vendor_id'                => (string) ($exp['vendor_id'] ?? ''),
                        'vendor_name'              => (string) ($exp['vendor_name'] ?? ''),
                        'customer_id'              => (string) ($exp['customer_id'] ?? ''),
                        'customer_name'            => (string) ($exp['customer_name'] ?? ''),
                        'total'                    => (float) ($exp['total'] ?? 0.0),
                        'tax_total'                => (float) ($exp['tax_total'] ?? 0.0),
                        'currency_code'            => (string) ($exp['currency_code'] ?? 'INR'),
                        'status'                   => (string) ($exp['status'] ?? ''),
                        'reference_number'         => (string) ($exp['reference_number'] ?? ''),
                        'description'              => (string) ($exp['description'] ?? ''),
                        'raw_data'                 => $exp,
                        'synced_at'                => now(),
                    ]
                );

                $totalSynced++;
                if ($isNew) {
                    $newCount++;
                } else {
                    $updatedCount++;
                }
            }

            $pageContext = $result['page_context'] ?? [];
            $hasMore = ! empty($pageContext['has_more_page']);
            $page++;
        }

        $integration->update([
            'last_synced_at' => now(),
            'last_error'     => null,
        ]);

        return [
            'total_synced'  => $totalSynced,
            'new_count'     => $newCount,
            'updated_count' => $updatedCount,
        ];
    }

    /**
     * Migrate / Convert a ZohoExpense into a native CRM AccountExpense.
     */
    public function migrateExpenseToNative(ZohoExpense $zohoExpense, ?int $userId = null): AccountExpense
    {
        return DB::transaction(function () use ($zohoExpense, $userId) {
            $companyId = $zohoExpense->company_id ?: 1;

            // 1. Find or create AccountExpenseCategory matching account_name
            $catName = trim($zohoExpense->account_name ?: 'General Expense');
            $category = AccountExpenseCategory::firstOrCreate(
                [
                    'name' => $catName,
                ],
                [
                    'company_id'  => $companyId,
                    'code'        => strtoupper(Str::slug($catName, '_')),
                    'description' => 'Imported from Zoho Books',
                    'status'      => 'active',
                    'created_by'  => $userId,
                ]
            );

            // 2. Find or create AccountExpenseSubcategory
            $subcategory = AccountExpenseSubcategory::firstOrCreate(
                [
                    'account_expense_category_id' => $category->id,
                    'name'                        => $catName,
                ],
                [
                    'company_id'  => $companyId,
                    'code'        => strtoupper(Str::slug($catName, '_')),
                    'description' => 'Imported from Zoho Books',
                    'status'      => 'active',
                    'created_by'  => $userId,
                ]
            );

            // 3. Find or create VendorMaster if vendor_name present
            $vendorId = null;
            if (! empty($zohoExpense->vendor_name)) {
                $vName = trim($zohoExpense->vendor_name);
                $vendor = VendorMaster::firstOrCreate(
                    [
                        'vendor_name' => $vName,
                    ],
                    [
                        'company_id'   => $companyId,
                        'company_name' => $vName,
                        'status'       => 'active',
                        'created_by'   => $userId,
                    ]
                );
                $vendorId = $vendor->id;
            }

            // 4. Create or update AccountExpense
            $expense = AccountExpense::create([
                'company_id'                     => $companyId,
                'expense_date'                   => $zohoExpense->expense_date,
                'account_expense_category_id'    => $category->id,
                'account_expense_subcategory_id' => $subcategory->id,
                'vendor_id'                      => $vendorId,
                'amount'                         => (float) $zohoExpense->total,
                'expense_type'                   => 'company',
                'invoice_no'                     => $zohoExpense->reference_number,
                'remarks'                        => $zohoExpense->description ?: ('Imported from Zoho Books (ID: ' . $zohoExpense->zoho_expense_id . ')'),
                'created_by'                     => $userId,
                'updated_by'                     => $userId,
            ]);

            // 5. Mark ZohoExpense as migrated
            $zohoExpense->update([
                'is_migrated_to_native'      => true,
                'migrated_native_expense_id' => $expense->id,
            ]);

            return $expense;
        });
    }

    /**
     * Fetch a paginated list of Customer Payments (Payment Collections) from Zoho Books.
     */
    public function fetchPayments(ZohoIntegration $integration, int $page = 1, int $perPage = 200, array $filters = []): array
    {
        $token = $this->ensureValidAccessToken($integration);
        $apiUrl = rtrim($integration->api_url ?: 'https://www.zohoapis.in', '/');

        $params = array_merge([
            'organization_id' => $integration->organization_id,
            'page'            => $page,
            'per_page'        => $perPage,
        ], $filters);

        $response = Http::withHeaders([
            'Authorization' => "Zoho-oauthtoken {$token}",
        ])->get("{$apiUrl}/books/v3/customerpayments", $params);

        if (! $response->successful()) {
            $msg = $response->json('message') ?: $response->body();
            throw new \Exception("Zoho API Customer Payments error: {$msg}");
        }

        return $response->json();
    }

    /**
     * Fetch details of a single Customer Payment (includes applied invoice details).
     */
    public function fetchPaymentDetails(ZohoIntegration $integration, string $zohoPaymentId): array
    {
        $token = $this->ensureValidAccessToken($integration);
        $apiUrl = rtrim($integration->api_url ?: 'https://www.zohoapis.in', '/');

        $response = Http::withHeaders([
            'Authorization' => "Zoho-oauthtoken {$token}",
        ])->get("{$apiUrl}/books/v3/customerpayments/{$zohoPaymentId}", [
            'organization_id' => $integration->organization_id,
        ]);

        if (! $response->successful()) {
            $msg = $response->json('message') ?: $response->body();
            throw new \Exception("Zoho payment detail error for ID {$zohoPaymentId}: {$msg}");
        }

        $data = $response->json();

        return $data['customer_payment'] ?? [];
    }

    /**
     * Sync and import all customer payments from Zoho Books into `zoho_payments` table.
     */
    public function syncAllPayments(ZohoIntegration $integration, bool $fetchDetails = true): array
    {
        $page = 1;
        $hasMore = true;
        $totalSynced = 0;
        $newCount = 0;
        $updatedCount = 0;

        while ($hasMore) {
            $result = $this->fetchPayments($integration, $page, 200);
            $payments = $result['customer_payments'] ?? [];

            if (empty($payments)) {
                break;
            }

            foreach ($payments as $summary) {
                $zohoId = (string) ($summary['payment_id'] ?? '');
                if (! $zohoId) {
                    continue;
                }

                $full = $summary;
                if ($fetchDetails) {
                    try {
                        $fetched = $this->fetchPaymentDetails($integration, $zohoId);
                        if (! empty($fetched)) {
                            $full = $fetched;
                        }
                    } catch (\Throwable $e) {
                        Log::warning("Could not fetch detailed payment {$zohoId}: " . $e->getMessage());
                    }
                }

                $existing = ZohoPayment::where('zoho_payment_id', $zohoId)->first();
                $isNew = ! $existing;

                ZohoPayment::updateOrCreate(
                    ['zoho_payment_id' => $zohoId],
                    [
                        'company_id'        => $integration->company_id,
                        'zoho_payment_id'   => $zohoId,
                        'payment_number'    => (string) ($full['payment_number'] ?? $summary['payment_number'] ?? ''),
                        'customer_id'       => (string) ($full['customer_id'] ?? $summary['customer_id'] ?? ''),
                        'customer_name'     => (string) ($full['customer_name'] ?? $summary['customer_name'] ?? ''),
                        'payment_date'      => ! empty($full['date']) ? Carbon::parse($full['date'])->toDateString() : now()->toDateString(),
                        'payment_mode'      => (string) ($full['payment_mode'] ?? $summary['payment_mode'] ?? ''),
                        'amount'            => (float) ($full['amount'] ?? $summary['amount'] ?? 0.0),
                        'unused_amount'     => (float) ($full['unused_amount'] ?? $summary['unused_amount'] ?? 0.0),
                        'reference_number'  => (string) ($full['reference_number'] ?? $summary['reference_number'] ?? ''),
                        'bank_account_id'   => (string) ($full['account_id'] ?? ''),
                        'bank_account_name' => (string) ($full['account_name'] ?? ''),
                        'description'       => (string) ($full['description'] ?? ''),
                        'status'            => (string) ($full['status'] ?? $summary['status'] ?? ''),
                        'invoices_applied'  => $full['invoices'] ?? [],
                        'raw_data'          => $full,
                        'synced_at'         => now(),
                    ]
                );

                $totalSynced++;
                if ($isNew) {
                    $newCount++;
                } else {
                    $updatedCount++;
                }
            }

            $pageContext = $result['page_context'] ?? [];
            $hasMore = ! empty($pageContext['has_more_page']);
            $page++;
        }

        $integration->update([
            'last_synced_at' => now(),
            'last_error'     => null,
        ]);

        return [
            'total_synced'  => $totalSynced,
            'new_count'     => $newCount,
            'updated_count' => $updatedCount,
        ];
    }

    /**
     * Migrate / Convert a ZohoPayment into a native CRM InvoicePayment.
     */
    public function migratePaymentToNative(ZohoPayment $zohoPayment, ?int $userId = null): InvoicePayment
    {
        return DB::transaction(function () use ($zohoPayment, $userId) {
            $companyId = $zohoPayment->company_id ?: 1;

            // 1. Identify applied invoice
            $appliedInvoices = $zohoPayment->invoices_applied ?? [];
            $targetInvoice = null;
            $amountApplied = (float) $zohoPayment->amount;

            if (! empty($appliedInvoices) && is_array($appliedInvoices)) {
                $firstApplied = $appliedInvoices[0];
                $invNumber = $firstApplied['invoice_number'] ?? null;
                $zohoInvId = (string) ($firstApplied['invoice_id'] ?? '');
                $amountApplied = (float) ($firstApplied['amount_applied'] ?? $zohoPayment->amount);

                if ($invNumber) {
                    $targetInvoice = Invoice::where('invoice_number', $invNumber)->first();
                }

                if (! $targetInvoice && $zohoInvId) {
                    $zohoInv = ZohoInvoice::where('zoho_invoice_id', $zohoInvId)->first();
                    if ($zohoInv) {
                        if ($zohoInv->migrated_native_invoice_id) {
                            $targetInvoice = Invoice::find($zohoInv->migrated_native_invoice_id);
                        } else {
                            // Auto-migrate the related Zoho invoice first!
                            $targetInvoice = $this->migrateInvoiceToNative($zohoInv, $userId);
                        }
                    }
                }
            }

            // Fallback: If no invoice applied, find latest invoice of matching customer
            if (! $targetInvoice && ! empty($zohoPayment->customer_name)) {
                $targetInvoice = Invoice::where('customer_name', $zohoPayment->customer_name)
                    ->orderBy('id', 'desc')
                    ->first();
            }

            if (! $targetInvoice) {
                throw new \Exception("Cannot find corresponding CRM Invoice for this payment. Please migrate the related Invoice first.");
            }

            // Map payment mode
            $mode = strtolower(trim((string) ($zohoPayment->payment_mode ?? '')));
            $nativeMode = match (true) {
                str_contains($mode, 'cash')                      => 'cash',
                str_contains($mode, 'cheque') || str_contains($mode, 'check') => 'cheque',
                str_contains($mode, 'card')                      => 'card',
                str_contains($mode, 'upi')                       => 'upi',
                str_contains($mode, 'bank') || str_contains($mode, 'transfer') || str_contains($mode, 'neft') || str_contains($mode, 'rtgs') => 'bank_transfer',
                default                                          => 'other',
            };

            // 2. Generate or use payment number
            $pNumber = $zohoPayment->payment_number ?: ('PAY-ZH-' . str_pad($zohoPayment->id, 4, '0', STR_PAD_LEFT));

            // Check if payment already exists
            $existingPayment = InvoicePayment::where('payment_number', $pNumber)->first();
            $paymentData = [
                'company_id'           => $companyId,
                'invoice_id'           => $targetInvoice->id,
                'customer_id'          => $targetInvoice->customer_id,
                'payment_number'       => $pNumber,
                'payment_date'         => $zohoPayment->payment_date,
                'amount_received'      => $amountApplied,
                'total_settled_amount' => $amountApplied,
                'payment_mode'         => $nativeMode,
                'reference_number'     => $zohoPayment->reference_number,
                'bank_name'            => $zohoPayment->bank_account_name,
                'notes'                => $zohoPayment->description ?: ('Imported from Zoho Books (Payment ID: ' . $zohoPayment->zoho_payment_id . ')'),
                'recorded_by'          => $userId,
            ];

            if ($existingPayment) {
                $existingPayment->update($paymentData);
                $invoicePayment = $existingPayment;
            } else {
                $invoicePayment = InvoicePayment::create($paymentData);
            }

            // 3. Recalculate Invoice payment status & balance
            $targetInvoice->recalculatePaymentStatus();

            // 4. Mark ZohoPayment as migrated
            $zohoPayment->update([
                'is_migrated_to_native'       => true,
                'migrated_native_payment_id'  => $invoicePayment->id,
            ]);

            return $invoicePayment;
        });
    }
}
