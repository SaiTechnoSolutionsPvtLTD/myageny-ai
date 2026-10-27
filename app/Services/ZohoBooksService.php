<?php

namespace App\Services;

use App\Models\CustomerMaster;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\ZohoExpense;
use App\Models\ZohoIntegration;
use App\Models\ZohoInvoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
    public const SCOPES = 'ZohoBooks.fullaccess.all,ZohoBooks.invoices.all,ZohoBooks.expenses.all,ZohoBooks.settings.READ,ZohoBooks.contacts.READ';

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
        $orgs = $data['organizations'] ?? [];

        $update = [
            'organizations_data' => $orgs,
            'last_error'         => null,
        ];

        // Auto-select first organization if none is active
        if (empty($integration->organization_id) && count($orgs) > 0) {
            $firstOrg = $orgs[0];
            $update['organization_id']   = (string) ($firstOrg['organization_id'] ?? '');
            $update['organization_name'] = (string) ($firstOrg['name'] ?? '');
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
}
