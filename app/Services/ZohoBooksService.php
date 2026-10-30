<?php

namespace App\Services;

use App\Models\AccountExpense;
use App\Models\AccountExpenseCategory;
use App\Models\AccountExpenseSubcategory;
use App\Models\CustomerMaster;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\Lead;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\User;
use App\Models\VendorMaster;
use App\Models\ZohoExpense;
use App\Models\ZohoIntegration;
use App\Models\ZohoInvoice;
use App\Models\ZohoPayment;
use App\Models\ZohoQuotation;
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
     * Note: Zoho Books requires uppercase ALL/READ for module operations, and lowercase all for fullaccess.
     */
    public const SCOPES = 'ZohoBooks.fullaccess.all,ZohoBooks.invoices.ALL,ZohoBooks.invoices.READ,ZohoBooks.estimates.ALL,ZohoBooks.estimates.READ,ZohoBooks.expenses.ALL,ZohoBooks.customerpayments.ALL,ZohoBooks.settings.READ,ZohoBooks.contacts.READ';

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
     * Note: $fetchDetailedItems is false by default so that 2,000+ invoices sync in seconds without HTTP timeouts.
     */
    public function syncAllInvoices(ZohoIntegration $integration, bool $fetchDetailedItems = false): array
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

                $sTotal = (float) ($fullDetail['sub_total'] ?? $fullDetail['bcy_sub_total'] ?? $invSummary['sub_total'] ?? $invSummary['bcy_sub_total'] ?? 0.0);
                $tTax   = (float) ($fullDetail['tax_total'] ?? $fullDetail['bcy_tax_total'] ?? $invSummary['tax_total'] ?? $invSummary['bcy_tax_total'] ?? 0.0);
                $gTotal = (float) ($fullDetail['total'] ?? $fullDetail['bcy_total'] ?? $invSummary['total'] ?? $invSummary['bcy_total'] ?? 0.0);

                if ($sTotal <= 0 && $gTotal > 0) {
                    $sTotal = $tTax > 0 ? max(0.00, round($gTotal - $tTax, 2)) : $gTotal;
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
                    'sub_total'        => $sTotal,
                    'tax_total'        => $tTax,
                    'total'            => $gTotal,
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

                // Auto-capture payments recorded directly on this invoice into zoho_payments
                $invPayments = $fullDetail['payments'] ?? [];
                if (! empty($invPayments) && is_array($invPayments)) {
                    foreach ($invPayments as $pmt) {
                        $pId = (string) ($pmt['payment_id'] ?? '');
                        if ($pId) {
                            ZohoPayment::updateOrCreate(
                                ['zoho_payment_id' => $pId],
                                [
                                    'company_id'        => $integration->company_id,
                                    'zoho_payment_id'   => $pId,
                                    'payment_number'    => (string) ($pmt['payment_number'] ?? ''),
                                    'customer_id'       => (string) ($invSummary['customer_id'] ?? $fullDetail['customer_id'] ?? ''),
                                    'customer_name'     => (string) ($invSummary['customer_name'] ?? $fullDetail['customer_name'] ?? ''),
                                    'payment_date'      => ! empty($pmt['date']) ? Carbon::parse($pmt['date'])->toDateString() : now()->toDateString(),
                                    'payment_mode'      => (string) ($pmt['payment_mode'] ?? ''),
                                    'amount'            => (float) ($pmt['amount'] ?? 0.0),
                                    'reference_number'  => (string) ($pmt['reference_number'] ?? ''),
                                    'status'            => 'paid',
                                    'invoices_applied'  => [
                                        [
                                            'invoice_id'     => $zohoId,
                                            'invoice_number' => (string) ($fullDetail['invoice_number'] ?? $invSummary['invoice_number'] ?? ''),
                                            'amount_applied' => (float) ($pmt['amount'] ?? 0.0),
                                        ]
                                    ],
                                    'raw_data'          => $pmt,
                                    'synced_at'         => now(),
                                ]
                            );
                        }
                    }
                }

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

            // Auto-fetch detailed line items and pricing on demand if not yet loaded or if sub_total is zero
            $lineItems = $zohoInvoice->line_items ?? [];
            if (empty($lineItems) || (float) $zohoInvoice->sub_total <= 0) {
                $integration = ZohoIntegration::first();
                if ($integration && $integration->is_connected && ! empty($zohoInvoice->zoho_invoice_id)) {
                    try {
                        $fetched = $this->fetchInvoiceDetails($integration, $zohoInvoice->zoho_invoice_id);
                        if (! empty($fetched)) {
                            $updateData = [
                                'raw_data' => $fetched,
                            ];
                            if (! empty($fetched['line_items'])) {
                                $updateData['line_items'] = $fetched['line_items'];
                                $lineItems = $fetched['line_items'];
                            }
                            if (isset($fetched['sub_total']) && (float) $fetched['sub_total'] > 0) {
                                $updateData['sub_total'] = (float) $fetched['sub_total'];
                            } elseif (isset($fetched['bcy_sub_total']) && (float) $fetched['bcy_sub_total'] > 0) {
                                $updateData['sub_total'] = (float) $fetched['bcy_sub_total'];
                            }
                            if (isset($fetched['tax_total'])) {
                                $updateData['tax_total'] = (float) $fetched['tax_total'];
                            }
                            if (isset($fetched['total']) && (float) $fetched['total'] > 0) {
                                $updateData['total'] = (float) $fetched['total'];
                            }
                            if (isset($fetched['balance'])) {
                                $updateData['balance'] = (float) $fetched['balance'];
                            }
                            if (! empty($fetched['billing_address'])) {
                                $updateData['billing_address'] = $fetched['billing_address'];
                            }
                            if (! empty($fetched['shipping_address'])) {
                                $updateData['shipping_address'] = $fetched['shipping_address'];
                            }
                            $zohoInvoice->update($updateData);
                            $zohoInvoice->refresh();
                        }
                    } catch (\Throwable $e) {
                        Log::warning("Could not on-demand fetch line items/details for invoice {$zohoInvoice->zoho_invoice_id}: " . $e->getMessage());
                    }
                }
            }

            // 1. Resolve Customer in CustomerMaster with accurate schema columns
            $customerName = trim((string) ($zohoInvoice->customer_name ?: ($zohoInvoice->company_name ?: ($zohoInvoice->raw_data['customer_name'] ?? ($zohoInvoice->raw_data['company_name'] ?? 'Zoho Customer')))));
            $companyName = trim((string) ($zohoInvoice->company_name ?: ($zohoInvoice->raw_data['company_name'] ?? $customerName)));
            $billing = $zohoInvoice->billing_address ?? [];
            $shipping = $zohoInvoice->shipping_address ?? [];

            $customer = CustomerMaster::firstOrCreate(
                [
                    'company_name' => $companyName ?: $customerName,
                ],
                [
                    'company_id'                => $companyId,
                    'customer_name'             => $customerName,
                    'company_name'              => $companyName ?: $customerName,
                    'mobile_number'             => $zohoInvoice->raw_data['phone'] ?? ($zohoInvoice->raw_data['mobile'] ?? null),
                    'email'                     => $zohoInvoice->raw_data['email'] ?? null,
                    'pan_number'                => $zohoInvoice->raw_data['pan_no'] ?? null,
                    'gst_number'                => $zohoInvoice->raw_data['gst_no'] ?? null,
                    'billing_attention'         => $billing['attention'] ?? $customerName,
                    'billing_address_street_1'  => $billing['address'] ?? null,
                    'billing_city'              => $billing['city'] ?? null,
                    'billing_state'             => $billing['state'] ?? null,
                    'billing_pin_code'          => $billing['zip'] ?? null,
                    'billing_country'           => $billing['country'] ?? 'India',
                    'shipping_attention'        => $shipping['attention'] ?? null,
                    'shipping_address_street_1' => $shipping['address'] ?? null,
                    'shipping_city'             => $shipping['city'] ?? null,
                    'shipping_state'            => $shipping['state'] ?? null,
                    'shipping_pin_code'         => $shipping['zip'] ?? null,
                    'shipping_country'          => $shipping['country'] ?? 'India',
                    'created_by'                => $userId,
                ]
            );

            // 2. Resolve Sales Person from Zoho Books salesperson_name
            $salesPersonId = null;
            $zohoSalespersonName = trim((string) ($zohoInvoice->raw_data['salesperson_name'] ?? ''));
            if ($zohoSalespersonName !== '') {
                $matchedUser = User::where('name', 'LIKE', "%{$zohoSalespersonName}%")->first();
                if (! $matchedUser && str_contains($zohoSalespersonName, ' ')) {
                    $firstWord = explode(' ', $zohoSalespersonName)[0];
                    $matchedUser = User::where('name', 'LIKE', "%{$firstWord}%")->first();
                }
                if ($matchedUser) {
                    $salesPersonId = $matchedUser->id;
                }
            }
            if (! $salesPersonId && $userId) {
                $salesPersonId = $userId;
            }

            // 3. Resolve Totals, Subtotal, and GST Tax Breakdown
            $totalAmount = (float) $zohoInvoice->total;
            $balanceDue  = (float) $zohoInvoice->balance;
            $totalTax    = (float) $zohoInvoice->tax_total;
            if ($totalTax <= 0 && isset($zohoInvoice->raw_data['tax_total'])) {
                $totalTax = (float) $zohoInvoice->raw_data['tax_total'];
            }

            // Calculate item amounts sum from line items if present
            $itemsSum = 0.0;
            if (! empty($lineItems) && is_array($lineItems)) {
                foreach ($lineItems as $item) {
                    $iQty = (float) ($item['quantity'] ?? 1);
                    $iRate = (float) ($item['rate'] ?? 0);
                    $iAmt = (float) ($item['item_total'] ?? ($iQty * $iRate));
                    if ($iAmt == 0 && $iRate > 0 && $iQty > 0) {
                        $iAmt = $iQty * $iRate;
                    }
                    $itemsSum += $iAmt;
                }
            }

            // Determine accurate subtotal:
            $subTotal = (float) $zohoInvoice->sub_total;
            if ($subTotal <= 0 && isset($zohoInvoice->raw_data['sub_total'])) {
                $subTotal = (float) $zohoInvoice->raw_data['sub_total'];
            }
            if ($subTotal <= 0 && isset($zohoInvoice->raw_data['bcy_sub_total'])) {
                $subTotal = (float) $zohoInvoice->raw_data['bcy_sub_total'];
            }
            if ($subTotal <= 0 && $itemsSum > 0) {
                $subTotal = $itemsSum;
            }
            if ($subTotal <= 0 && $totalAmount > 0) {
                $subTotal = $totalTax > 0 ? max(0.00, round($totalAmount - $totalTax, 2)) : $totalAmount;
            }
            if ($totalAmount <= 0 && $subTotal > 0) {
                $totalAmount = round($subTotal + $totalTax, 2);
                $balanceDue  = $totalAmount;
            }

            // Update zohoInvoice sub_total and tax_total if they were 0
            if (($zohoInvoice->sub_total <= 0 && $subTotal > 0) || ($zohoInvoice->tax_total <= 0 && $totalTax > 0)) {
                $zohoInvoice->update([
                    'sub_total' => $subTotal,
                    'tax_total' => $totalTax,
                    'total'     => $totalAmount,
                ]);
            }

            $placeOfSupply = strtolower((string) ($zohoInvoice->raw_data['place_of_supply'] ?? ''));
            $isInterState = str_contains($placeOfSupply, 'inter') || (
                ! empty($placeOfSupply) && ! str_contains($placeOfSupply, 'tamil') && ! str_contains($placeOfSupply, '33') && ! str_contains($placeOfSupply, 'tn')
            );

            $zohoTaxes = $zohoInvoice->raw_data['taxes'] ?? [];
            $extractedCgst = 0.0;
            $extractedSgst = 0.0;
            $extractedIgst = 0.0;

            if (! empty($zohoTaxes) && is_array($zohoTaxes)) {
                foreach ($zohoTaxes as $taxEntry) {
                    $tName = strtoupper((string) ($taxEntry['tax_name'] ?? ''));
                    $tAmt = (float) ($taxEntry['tax_amount'] ?? 0);
                    if (str_contains($tName, 'CGST')) {
                        $extractedCgst += $tAmt;
                    } elseif (str_contains($tName, 'SGST')) {
                        $extractedSgst += $tAmt;
                    } elseif (str_contains($tName, 'IGST')) {
                        $extractedIgst += $tAmt;
                    }
                }
            }

            if ($extractedCgst == 0 && $extractedSgst == 0 && $extractedIgst == 0 && $totalTax > 0) {
                if ($isInterState) {
                    $extractedIgst = $totalTax;
                } else {
                    $extractedCgst = round($totalTax / 2, 2);
                    $extractedSgst = round($totalTax - $extractedCgst, 2);
                }
            }

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

            // 2b. Validate Customer against Leads (Phone -> Email -> Company Name -> Customer Name)
            $contactPersonPhones = [];
            $contactPersonEmails = [];
            if (! empty($zohoInvoice->raw_data['contact_persons']) && is_array($zohoInvoice->raw_data['contact_persons'])) {
                foreach ($zohoInvoice->raw_data['contact_persons'] as $cp) {
                    if (! empty($cp['phone'])) {
                        $contactPersonPhones[] = $cp['phone'];
                    }
                    if (! empty($cp['mobile'])) {
                        $contactPersonPhones[] = $cp['mobile'];
                    }
                    if (! empty($cp['email'])) {
                        $contactPersonEmails[] = $cp['email'];
                    }
                }
            }

            $candidatePhones = array_merge([
                $zohoInvoice->raw_data['phone'] ?? null,
                $zohoInvoice->raw_data['mobile'] ?? null,
                $billing['phone'] ?? null,
                $shipping['phone'] ?? null,
                $customer->mobile_number ?? null,
            ], $contactPersonPhones);

            $candidateEmails = array_merge([
                $zohoInvoice->raw_data['email'] ?? null,
                $customer->email ?? null,
            ], $contactPersonEmails);

            $matchedLead = $this->findMatchingLead($candidatePhones, $candidateEmails, $companyName, $customerName);

            $leadId = $matchedLead?->id;
            $branchId = $matchedLead?->branch_id ?? null;
            if ($matchedLead && empty($customer->lead_id)) {
                $customer->update(['lead_id' => $matchedLead->id]);
            }
            if ($matchedLead && empty($salesPersonId) && $matchedLead->assigned_to) {
                $salesPersonId = $matchedLead->assigned_to;
            }

            // Check if native invoice with this number already exists
            $existingInvoice = Invoice::where('invoice_number', $zohoInvoice->invoice_number)->first();

            $paidAmount  = max(0.00, $totalAmount - $balanceDue);

            $invoiceData = [
                'company_id'         => $companyId,
                'branch_id'          => $branchId,
                'customer_id'        => $customer->id,
                'lead_id'            => $leadId,
                'sales_person_id'    => $salesPersonId,
                'invoice_number'     => $zohoInvoice->invoice_number,
                'invoice_date'       => $zohoInvoice->invoice_date,
                'due_date'           => $zohoInvoice->due_date,
                'payment_terms'      => $zohoInvoice->payment_terms,
                'place_of_supply'    => $zohoInvoice->raw_data['place_of_supply'] ?? null,
                'customer_name'      => $customerName,
                'company_name'       => $companyName ?: $customerName,
                'customer_email'     => $zohoInvoice->raw_data['email'] ?? $customer->email,
                'customer_phone'     => $zohoInvoice->raw_data['phone'] ?? $customer->mobile_number,
                'customer_gstin'     => $zohoInvoice->raw_data['gst_no'] ?? $customer->gst_number,
                'customer_pan'       => $zohoInvoice->raw_data['pan_no'] ?? $customer->pan_number,
                'billing_attention'  => $billing['attention'] ?? $customerName,
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
                'subtotal'           => $subTotal,
                'total_cgst'         => $extractedCgst,
                'total_sgst'         => $extractedSgst,
                'total_igst'         => $extractedIgst,
                'total_tax'          => $totalTax,
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

            // Create line items with detailed CGST, SGST, IGST breakdown
            if (! empty($lineItems) && is_array($lineItems)) {
                $order = 1;
                $runningSubtotal = 0.0;
                foreach ($lineItems as $item) {
                    $qty = (float) ($item['quantity'] ?? 1);
                    $rate = (float) ($item['rate'] ?? 0);
                    $amount = (float) ($item['item_total'] ?? ($qty * $rate));
                    if ($amount == 0 && $rate > 0 && $qty > 0) {
                        $amount = $qty * $rate;
                    }
                    $runningSubtotal += $amount;

                    $taxPercent = (float) ($item['tax_percentage'] ?? 0);
                    if ($taxPercent <= 0 && $subTotal > 0 && $totalTax > 0) {
                        $taxPercent = round(($totalTax / $subTotal) * 100, 2);
                    }

                    $itemTaxName = strtoupper((string) ($item['tax_name'] ?? ''));
                    $itemIsIgst = str_contains($itemTaxName, 'IGST') || ($extractedIgst > 0 && $extractedCgst == 0);

                    if ($itemIsIgst) {
                        $igstPercent = $taxPercent;
                        $igstAmount  = round($amount * ($igstPercent / 100), 2);
                        $cgstPercent = 0.0;
                        $cgstAmount  = 0.0;
                        $sgstPercent = 0.0;
                        $sgstAmount  = 0.0;
                    } else {
                        $cgstPercent = round($taxPercent / 2, 2);
                        $sgstPercent = round($taxPercent / 2, 2);
                        $cgstAmount  = round($amount * ($cgstPercent / 100), 2);
                        $sgstAmount  = round($amount * ($sgstPercent / 100), 2);
                        $igstPercent = 0.0;
                        $igstAmount  = 0.0;
                    }

                    $itemTotalAmount = $amount + $cgstAmount + $sgstAmount + $igstAmount;

                    InvoiceItem::create([
                        'invoice_id'   => $nativeInvoice->id,
                        'item_name'    => $item['name'] ?? 'Item',
                        'description'  => $item['description'] ?? null,
                        'hsn_sac_code' => $item['hsn_or_sac'] ?? null,
                        'qty'          => $qty,
                        'rate'         => $rate ?: ($qty > 0 ? round($amount / $qty, 2) : $amount),
                        'amount'       => $amount,
                        'cgst_percent' => $cgstPercent,
                        'cgst_amount'  => $cgstAmount,
                        'sgst_percent' => $sgstPercent,
                        'sgst_amount'  => $sgstAmount,
                        'igst_percent' => $igstPercent,
                        'igst_amount'  => $igstAmount,
                        'total_amount' => $itemTotalAmount,
                        'sort_order'   => $order++,
                    ]);
                }

                // If running subtotal from items differs from native invoice subtotal, update it
                if ($runningSubtotal > 0 && abs($runningSubtotal - (float) $nativeInvoice->subtotal) > 0.01) {
                    $nativeInvoice->update(['subtotal' => $runningSubtotal]);
                }
            } else {
                // Single default line item if none specified
                $taxPercent = $subTotal > 0 ? round(($totalTax / $subTotal) * 100, 2) : 18.0;
                if ($extractedIgst > 0 && $extractedCgst == 0) {
                    $igstPercent = $taxPercent;
                    $igstAmount  = $totalTax;
                    $cgstPercent = 0.0;
                    $cgstAmount  = 0.0;
                    $sgstPercent = 0.0;
                    $sgstAmount  = 0.0;
                } else {
                    $cgstPercent = round($taxPercent / 2, 2);
                    $sgstPercent = round($taxPercent / 2, 2);
                    $cgstAmount  = $extractedCgst ?: round($totalTax / 2, 2);
                    $sgstAmount  = $extractedSgst ?: round($totalTax - $cgstAmount, 2);
                    $igstPercent = 0.0;
                    $igstAmount  = 0.0;
                }

                InvoiceItem::create([
                    'invoice_id'   => $nativeInvoice->id,
                    'item_name'    => 'Consulting / Services',
                    'description'  => 'Imported from Zoho Books',
                    'qty'          => 1,
                    'rate'         => $subTotal,
                    'amount'       => $subTotal,
                    'cgst_percent' => $cgstPercent,
                    'cgst_amount'  => $cgstAmount,
                    'sgst_percent' => $sgstPercent,
                    'sgst_amount'  => $sgstAmount,
                    'igst_percent' => $igstPercent,
                    'igst_amount'  => $igstAmount,
                    'total_amount' => (float) $totalAmount,
                    'sort_order'   => 1,
                ]);
            }

            // 4. Synchronize and Record Payments against this Invoice
            $rawPayments = $zohoInvoice->raw_data['payments'] ?? [];
            $hasProcessedAnyPayment = false;

            if (! empty($rawPayments) && is_array($rawPayments)) {
                $pIndex = 1;
                foreach ($rawPayments as $pmt) {
                    $pId = (string) ($pmt['payment_id'] ?? ($pmt['invoice_payment_id'] ?? ''));
                    $pmtNum = trim((string) ($pmt['payment_number'] ?? ''));
                    if (empty($pmtNum)) {
                        $pmtNum = $pId ? ('PAY-ZH-' . $pId) : ('PAY-ZH-' . $nativeInvoice->invoice_number . '-' . $pIndex++);
                    }

                    $pmtDate = ! empty($pmt['date']) ? Carbon::parse($pmt['date'])->toDateString() : ($nativeInvoice->invoice_date ?: now()->toDateString());
                    $pmtAmount = (float) ($pmt['amount'] ?? 0.0);
                    $pmtModeRaw = strtolower((string) ($pmt['payment_mode'] ?? ''));
                    if (str_contains($pmtModeRaw, 'cash')) {
                        $pmtMode = 'cash';
                    } elseif (str_contains($pmtModeRaw, 'cheque') || str_contains($pmtModeRaw, 'check')) {
                        $pmtMode = 'cheque';
                    } elseif (str_contains($pmtModeRaw, 'upi')) {
                        $pmtMode = 'upi';
                    } elseif (str_contains($pmtModeRaw, 'card')) {
                        $pmtMode = 'card';
                    } else {
                        $pmtMode = 'bank_transfer';
                    }

                    if ($pmtAmount > 0) {
                        InvoicePayment::updateOrCreate(
                            [
                                'invoice_id'     => $nativeInvoice->id,
                                'payment_number' => $pmtNum,
                            ],
                            [
                                'company_id'           => $companyId,
                                'customer_id'          => $customer->id,
                                'payment_date'         => $pmtDate,
                                'amount_received'      => $pmtAmount,
                                'is_tds_deducted'      => false,
                                'tds_percentage'       => 0,
                                'tds_amount'           => 0,
                                'total_settled_amount' => $pmtAmount,
                                'payment_mode'         => $pmtMode,
                                'reference_number'     => (string) ($pmt['reference_number'] ?? ''),
                                'bank_name'            => (string) ($pmt['account_name'] ?? ($pmt['bank_name'] ?? null)),
                                'notes'                => (string) ($pmt['description'] ?? ($pmt['notes'] ?? ('Imported from Zoho Books payment #' . $pmtNum))),
                                'recorded_by'          => $userId,
                            ]
                        );
                        $hasProcessedAnyPayment = true;
                    }
                }
            }

            // Also check zoho_payments table for any payments applied to this invoice
            $appliedZohoPayments = ZohoPayment::where('invoices_applied', 'LIKE', "%{$zohoInvoice->zoho_invoice_id}%")
                ->orWhere('invoices_applied', 'LIKE', "%{$zohoInvoice->invoice_number}%")
                ->get();

            foreach ($appliedZohoPayments as $zp) {
                $appliedAmt = 0.0;
                foreach ($zp->invoices_applied ?? [] as $invApp) {
                    if (($invApp['invoice_id'] ?? '') === $zohoInvoice->zoho_invoice_id || ($invApp['invoice_number'] ?? '') === $zohoInvoice->invoice_number) {
                        $appliedAmt += (float) ($invApp['amount_applied'] ?? 0);
                    }
                }
                if ($appliedAmt <= 0) {
                    $appliedAmt = (float) $zp->amount;
                }

                if ($appliedAmt > 0) {
                    $zpNum = $zp->payment_number ?: ('PAY-ZH-' . $zp->zoho_payment_id);
                    $exists = InvoicePayment::where('invoice_id', $nativeInvoice->id)
                        ->where('payment_number', $zpNum)
                        ->first();
                    if (! $exists) {
                        InvoicePayment::create([
                            'company_id'           => $companyId,
                            'invoice_id'           => $nativeInvoice->id,
                            'customer_id'          => $customer->id,
                            'payment_number'       => $zpNum,
                            'payment_date'         => $zp->payment_date ?: ($nativeInvoice->invoice_date ?: now()->toDateString()),
                            'amount_received'      => $appliedAmt,
                            'is_tds_deducted'      => false,
                            'tds_percentage'       => 0,
                            'tds_amount'           => 0,
                            'total_settled_amount' => $appliedAmt,
                            'payment_mode'         => $zp->payment_mode ?: 'bank_transfer',
                            'reference_number'     => $zp->reference_number ?: '',
                            'notes'                => 'Imported from Zoho Payment #' . $zpNum,
                            'recorded_by'          => $userId,
                        ]);
                        $zp->update(['is_migrated_to_native' => true]);
                        $hasProcessedAnyPayment = true;
                    }
                }
            }

            // Fallback: If no explicit payment items were captured but invoice has paid_amount > 0
            if (! $hasProcessedAnyPayment && $paidAmount > 0) {
                $fallbackPmtNum = 'PAY-ZH-' . $nativeInvoice->invoice_number;
                $exists = InvoicePayment::where('invoice_id', $nativeInvoice->id)
                    ->where('payment_number', $fallbackPmtNum)
                    ->first();
                if (! $exists) {
                    InvoicePayment::create([
                        'company_id'           => $companyId,
                        'invoice_id'           => $nativeInvoice->id,
                        'customer_id'          => $customer->id,
                        'payment_number'       => $fallbackPmtNum,
                        'payment_date'         => $nativeInvoice->invoice_date ?: now()->toDateString(),
                        'amount_received'      => $paidAmount,
                        'is_tds_deducted'      => false,
                        'tds_percentage'       => 0,
                        'tds_amount'           => 0,
                        'total_settled_amount' => $paidAmount,
                        'payment_mode'         => 'bank_transfer',
                        'reference_number'     => $zohoInvoice->reference_number ?: ('ZOHO-' . $zohoInvoice->invoice_number),
                        'notes'                => 'Imported payment from Zoho Books for Invoice #' . $zohoInvoice->invoice_number,
                        'recorded_by'          => $userId,
                    ]);
                }
            }

            // Recalculate invoice payment status and balances accurately
            $nativeInvoice->load('payments');
            $nativeInvoice->recalculatePaymentStatus();

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
            $companyId = auth()->user()?->company_id ?: ($zohoExpense->company_id ?: 1);

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
            if ($category->status !== 'active') {
                $category->update(['status' => 'active']);
            }

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
            if ($subcategory->status !== 'active') {
                $subcategory->update(['status' => 'active']);
            }

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
                if ($vendor->status !== 'active') {
                    $vendor->update(['status' => 'active']);
                }
                $vendorId = $vendor->id;
            }

            // 4. Resolve Amount and Expense Date safely
            $rawAmount = $zohoExpense->total;
            if (empty($rawAmount) || (float) $rawAmount == 0.0) {
                $rawAmount = $zohoExpense->raw_data['amount'] ?? ($zohoExpense->raw_data['total'] ?? ($zohoExpense->raw_data['bcy_total'] ?? 0.0));
            }
            $amount = (float) $rawAmount;
            $expenseDate = $zohoExpense->expense_date ?: now()->toDateString();

            // 5. Create or update AccountExpense
            $expense = AccountExpense::create([
                'company_id'                     => $companyId,
                'expense_date'                   => $expenseDate,
                'account_expense_category_id'    => $category->id,
                'account_expense_subcategory_id' => $subcategory->id,
                'vendor_id'                      => $vendorId,
                'amount'                         => $amount,
                'expense_type'                   => 'Variable',
                'invoice_no'                     => $zohoExpense->reference_number,
                'remarks'                        => $zohoExpense->description ?: ('Imported from Zoho Books (ID: ' . $zohoExpense->zoho_expense_id . ')'),
                'created_by'                     => $userId,
                'updated_by'                     => $userId,
            ]);

            // 6. Mark ZohoExpense as migrated
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
    public function syncAllPayments(ZohoIntegration $integration, bool $fetchDetails = false): array
    {
        $page = 1;
        $hasMore = true;
        $totalSynced = 0;
        $newCount = 0;
        $updatedCount = 0;

        try {
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
        } catch (\Throwable $apiEx) {
            Log::warning("fetchPayments API error: " . $apiEx->getMessage() . ". Attempting fallback extraction from synced invoices.");
        }

        // Fallback & Supplement: Also extract payments from synced invoices
        $invoices = ZohoInvoice::whereNotNull('raw_data')->get();
        foreach ($invoices as $zInv) {
            $invPayments = $zInv->raw_data['payments'] ?? [];
            if (! empty($invPayments) && is_array($invPayments)) {
                foreach ($invPayments as $pmt) {
                    $pId = (string) ($pmt['payment_id'] ?? '');
                    if (! $pId) {
                        continue;
                    }
                    $existing = ZohoPayment::where('zoho_payment_id', $pId)->first();
                    $isNew = ! $existing;

                    ZohoPayment::updateOrCreate(
                        ['zoho_payment_id' => $pId],
                        [
                            'company_id'        => $integration->company_id,
                            'zoho_payment_id'   => $pId,
                            'payment_number'    => (string) ($pmt['payment_number'] ?? ''),
                            'customer_id'       => (string) ($zInv->customer_id ?? ''),
                            'customer_name'     => (string) ($zInv->customer_name ?? ''),
                            'payment_date'      => ! empty($pmt['date']) ? Carbon::parse($pmt['date'])->toDateString() : ($zInv->invoice_date ?? now()->toDateString()),
                            'payment_mode'      => (string) ($pmt['payment_mode'] ?? 'bank_transfer'),
                            'amount'            => (float) ($pmt['amount'] ?? 0.0),
                            'reference_number'  => (string) ($pmt['reference_number'] ?? ''),
                            'status'            => 'paid',
                            'invoices_applied'  => [
                                [
                                    'invoice_id'     => $zInv->zoho_invoice_id,
                                    'invoice_number' => $zInv->invoice_number,
                                    'amount_applied' => (float) ($pmt['amount'] ?? 0.0),
                                ]
                            ],
                            'raw_data'          => $pmt,
                            'synced_at'         => now(),
                        ]
                    );

                    if ($isNew) {
                        $totalSynced++;
                        $newCount++;
                    }
                }
            }
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
            $integration = ZohoIntegration::first();

            // Auto-enrich: If payment has no invoices_applied yet, attempt to fetch detail from Zoho Books API
            if (empty($zohoPayment->invoices_applied) && $integration && $integration->is_connected && ! empty($zohoPayment->zoho_payment_id)) {
                try {
                    $detailed = $this->fetchPaymentDetails($integration, $zohoPayment->zoho_payment_id);
                    if (! empty($detailed['invoices'])) {
                        $zohoPayment->update([
                            'invoices_applied' => $detailed['invoices'],
                            'raw_data'         => $detailed,
                        ]);
                        $zohoPayment->refresh();
                    }
                } catch (\Throwable $e) {
                    Log::warning("Could not fetch detailed payment for {$zohoPayment->zoho_payment_id}: " . $e->getMessage());
                }
            }

            // Ensure customer exists in CustomerMaster
            $customerName = trim($zohoPayment->customer_name ?: 'Zoho Customer');
            $customer = CustomerMaster::firstOrCreate(
                ['company_name' => $customerName],
                [
                    'contact_person' => $customerName,
                    'created_by'     => $userId,
                ]
            );

            // Map payment mode
            $mode = strtolower(trim((string) ($zohoPayment->payment_mode ?? '')));
            $nativeMode = match (true) {
                str_contains($mode, 'cash')                                                                    => 'cash',
                str_contains($mode, 'cheque') || str_contains($mode, 'check')                                => 'cheque',
                str_contains($mode, 'card')                                                                    => 'card',
                str_contains($mode, 'upi')                                                                     => 'upi',
                str_contains($mode, 'bank') || str_contains($mode, 'transfer') || str_contains($mode, 'neft') || str_contains($mode, 'rtgs') => 'bank_transfer',
                default                                                                                        => 'other',
            };

            $appliedInvoices = $zohoPayment->invoices_applied ?? [];
            $createdPayment = null;

            if (! empty($appliedInvoices) && is_array($appliedInvoices)) {
                $subIndex = 0;
                foreach ($appliedInvoices as $appliedItem) {
                    $subIndex++;
                    $invNumber = $appliedItem['invoice_number'] ?? null;
                    $zohoInvId = (string) ($appliedItem['invoice_id'] ?? '');
                    $amountApplied = (float) ($appliedItem['amount_applied'] ?? 0);

                    if ($amountApplied <= 0 && count($appliedInvoices) === 1) {
                        $amountApplied = (float) $zohoPayment->amount;
                    }

                    $targetInvoice = null;

                    // 1. Check existing native Invoice by number
                    if ($invNumber) {
                        $targetInvoice = Invoice::where('invoice_number', $invNumber)->first();
                    }

                    // 2. Check if invoice is in zoho_invoices table
                    if (! $targetInvoice && $zohoInvId) {
                        $zohoInv = ZohoInvoice::where('zoho_invoice_id', $zohoInvId)->first();
                        if ($zohoInv) {
                            if ($zohoInv->migrated_native_invoice_id) {
                                $targetInvoice = Invoice::find($zohoInv->migrated_native_invoice_id);
                            } else {
                                $targetInvoice = $this->migrateInvoiceToNative($zohoInv, $userId);
                            }
                        }
                    }

                    // 3. Auto-fetch invoice directly from Zoho API if connected
                    if (! $targetInvoice && $zohoInvId && $integration && $integration->is_connected) {
                        try {
                            $invDetail = $this->fetchInvoiceDetails($integration, $zohoInvId);
                            if (! empty($invDetail)) {
                                $zohoInv = ZohoInvoice::updateOrCreate(
                                    ['zoho_invoice_id' => $zohoInvId],
                                    [
                                        'company_id'      => $integration->company_id,
                                        'zoho_invoice_id' => $zohoInvId,
                                        'invoice_number'  => (string) ($invDetail['invoice_number'] ?? ''),
                                        'customer_id'     => (string) ($invDetail['customer_id'] ?? ''),
                                        'customer_name'   => (string) ($invDetail['customer_name'] ?? ''),
                                        'invoice_date'    => ! empty($invDetail['date']) ? Carbon::parse($invDetail['date'])->toDateString() : now()->toDateString(),
                                        'due_date'        => ! empty($invDetail['due_date']) ? Carbon::parse($invDetail['due_date'])->toDateString() : null,
                                        'status'          => (string) ($invDetail['status'] ?? ''),
                                        'total'           => (float) ($invDetail['total'] ?? 0.0),
                                        'balance'         => (float) ($invDetail['balance'] ?? 0.0),
                                        'sub_total'       => (float) ($invDetail['sub_total'] ?? 0.0),
                                        'tax_total'       => (float) ($invDetail['tax_total'] ?? 0.0),
                                        'line_items'      => $invDetail['line_items'] ?? [],
                                        'raw_data'        => $invDetail,
                                        'synced_at'       => now(),
                                    ]
                                );
                                $targetInvoice = $this->migrateInvoiceToNative($zohoInv, $userId);
                            }
                        } catch (\Throwable $e) {
                            Log::warning("Could not auto-fetch Zoho invoice {$zohoInvId}: " . $e->getMessage());
                        }
                    }

                    // 4. Fallback: Search invoice by customer name with pending balance
                    if (! $targetInvoice && ! empty($zohoPayment->customer_name)) {
                        $targetInvoice = Invoice::where('customer_name', $zohoPayment->customer_name)
                            ->where('balance_due', '>', 0)
                            ->orderBy('id', 'asc')
                            ->first()
                            ?? Invoice::where('customer_name', $zohoPayment->customer_name)
                                ->orderBy('id', 'desc')
                                ->first();
                    }

                    // 5. If still no invoice found, auto-create matching placeholder invoice for this payment
                    if (! $targetInvoice) {
                        $targetInvNumber = $invNumber ?: ('INV-ZH-' . ($zohoPayment->payment_number ?: $zohoPayment->id));
                        if (Invoice::where('invoice_number', $targetInvNumber)->exists()) {
                            $targetInvNumber .= '-' . rand(10, 99);
                        }

                        $targetInvoice = Invoice::create([
                            'company_id'       => $companyId,
                            'customer_id'      => $customer->id,
                            'invoice_number'   => $targetInvNumber,
                            'invoice_date'     => $appliedItem['date'] ?? $zohoPayment->payment_date ?: now()->toDateString(),
                            'customer_name'    => $customerName,
                            'company_name'     => $customerName,
                            'subtotal'         => (float) ($appliedItem['invoice_amount'] ?? $amountApplied),
                            'total_tax'        => 0,
                            'total_amount'     => (float) ($appliedItem['invoice_amount'] ?? $amountApplied),
                            'paid_amount'      => 0,
                            'balance_due'      => (float) ($appliedItem['invoice_amount'] ?? $amountApplied),
                            'status'           => 'sent',
                            'customer_notes'   => 'Imported from Zoho Books for Payment #' . ($zohoPayment->payment_number ?: $zohoPayment->zoho_payment_id),
                            'created_by'       => $userId,
                            'updated_by'       => $userId,
                        ]);

                        InvoiceItem::create([
                            'invoice_id'   => $targetInvoice->id,
                            'item_name'    => 'Consulting / Invoice ' . ($invNumber ?: $targetInvNumber),
                            'description'  => 'Imported from Zoho Books Customer Payment #' . ($zohoPayment->payment_number ?: $zohoPayment->zoho_payment_id),
                            'qty'          => 1,
                            'rate'         => $targetInvoice->total_amount,
                            'amount'       => $targetInvoice->total_amount,
                            'total_amount' => $targetInvoice->total_amount,
                            'sort_order'   => 1,
                        ]);
                    }

                    // Generate distinct payment number if multiple applied invoices exist
                    $basePNumber = $zohoPayment->payment_number ?: ('PAY-ZH-' . str_pad($zohoPayment->id, 4, '0', STR_PAD_LEFT));
                    $pNumber = (count($appliedInvoices) > 1) ? "{$basePNumber}-{$subIndex}" : $basePNumber;

                    $existingPayment = InvoicePayment::where('payment_number', $pNumber)
                        ->where('invoice_id', $targetInvoice->id)
                        ->first()
                        ?? InvoicePayment::where('payment_number', $pNumber)->first();

                    $paymentData = [
                        'company_id'           => $companyId,
                        'invoice_id'           => $targetInvoice->id,
                        'customer_id'          => $targetInvoice->customer_id ?: $customer->id,
                        'payment_number'       => $pNumber,
                        'payment_date'         => $zohoPayment->payment_date ?: now()->toDateString(),
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

                    $targetInvoice->recalculatePaymentStatus();

                    if (! $createdPayment) {
                        $createdPayment = $invoicePayment;
                    }
                }
            } else {
                // Payment has NO applied invoice (Advance / On-Account payment)
                $amountApplied = (float) $zohoPayment->amount;

                // Try to find any unpaid invoice for this customer
                $targetInvoice = Invoice::where('customer_name', $zohoPayment->customer_name)
                    ->where('balance_due', '>', 0)
                    ->orderBy('id', 'asc')
                    ->first();

                // If no pending invoice, auto-create an Advance / On-Account invoice
                if (! $targetInvoice) {
                    $advNumber = 'ADV-' . ($zohoPayment->payment_number ?: ('ZH' . $zohoPayment->id));
                    if (Invoice::where('invoice_number', $advNumber)->exists()) {
                        $advNumber .= '-' . rand(10, 99);
                    }

                    $targetInvoice = Invoice::create([
                        'company_id'       => $companyId,
                        'customer_id'      => $customer->id,
                        'invoice_number'   => $advNumber,
                        'invoice_date'     => $zohoPayment->payment_date ?: now()->toDateString(),
                        'customer_name'    => $customerName,
                        'company_name'     => $customerName,
                        'subtotal'         => $amountApplied,
                        'total_tax'        => 0,
                        'total_amount'     => $amountApplied,
                        'paid_amount'      => 0,
                        'balance_due'      => $amountApplied,
                        'status'           => 'sent',
                        'customer_notes'   => 'Advance / On-Account payment imported from Zoho Books #' . ($zohoPayment->payment_number ?: $zohoPayment->zoho_payment_id),
                        'created_by'       => $userId,
                        'updated_by'       => $userId,
                    ]);

                    InvoiceItem::create([
                        'invoice_id'   => $targetInvoice->id,
                        'item_name'    => 'Advance / On-Account Payment Receipt',
                        'description'  => 'Imported from Zoho Books Customer Payment #' . ($zohoPayment->payment_number ?: $zohoPayment->zoho_payment_id),
                        'qty'          => 1,
                        'rate'         => $amountApplied,
                        'amount'       => $amountApplied,
                        'total_amount' => $amountApplied,
                        'sort_order'   => 1,
                    ]);
                }

                $pNumber = $zohoPayment->payment_number ?: ('PAY-ZH-' . str_pad($zohoPayment->id, 4, '0', STR_PAD_LEFT));
                $existingPayment = InvoicePayment::where('payment_number', $pNumber)->first();

                $paymentData = [
                    'company_id'           => $companyId,
                    'invoice_id'           => $targetInvoice->id,
                    'customer_id'          => $targetInvoice->customer_id ?: $customer->id,
                    'payment_number'       => $pNumber,
                    'payment_date'         => $zohoPayment->payment_date ?: now()->toDateString(),
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
                    $createdPayment = $existingPayment;
                } else {
                    $createdPayment = InvoicePayment::create($paymentData);
                }

                $targetInvoice->recalculatePaymentStatus();
            }

            // Mark ZohoPayment as migrated
            $zohoPayment->update([
                'is_migrated_to_native'      => true,
                'migrated_native_payment_id' => $createdPayment->id,
            ]);

            return $createdPayment;
        });
    }

    // ──────────────────────────────────────────────────────────────────────────
    // ESTIMATES / QUOTATIONS API
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Fetch a paginated list of estimates (quotations) from Zoho Books.
     */
    public function fetchEstimates(ZohoIntegration $integration, int $page = 1, int $perPage = 200, array $filters = []): array
    {
        $accessToken = $this->ensureValidAccessToken($integration);
        $apiUrl = rtrim($integration->api_url ?: 'https://www.zohoapis.in', '/');
        $orgId = $integration->organization_id;

        $params = array_merge([
            'organization_id' => $orgId,
            'page'            => $page,
            'per_page'        => $perPage,
        ], $filters);

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->timeout(60)
            ->get("{$apiUrl}/books/v3/estimates", $params);

        if (! $response->successful()) {
            $err = $response->json('message') ?: $response->body();
            Log::error("ZohoBooks fetchEstimates failed: {$err}", ['status' => $response->status()]);
            throw new \RuntimeException("Failed to fetch estimates from Zoho Books: {$err}");
        }

        return $response->json();
    }

    /**
     * Fetch complete details of a single estimate (including line items and addresses).
     */
    public function fetchEstimateDetails(ZohoIntegration $integration, string $zohoEstimateId): array
    {
        $accessToken = $this->ensureValidAccessToken($integration);
        $apiUrl = rtrim($integration->api_url ?: 'https://www.zohoapis.in', '/');
        $orgId = $integration->organization_id;

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->timeout(15)
            ->connectTimeout(5)
            ->get("{$apiUrl}/books/v3/estimates/{$zohoEstimateId}", [
                'organization_id' => $orgId,
            ]);

        if (! $response->successful()) {
            $err = $response->json('message') ?: $response->body();
            Log::error("ZohoBooks fetchEstimateDetails failed for ID {$zohoEstimateId}: {$err}");
            throw new \RuntimeException("Failed to fetch estimate details ({$zohoEstimateId}): {$err}");
        }

        return $response->json('estimate') ?? [];
    }

    /**
     * Sync a single page of estimates/quotations from Zoho Books into the local zoho_quotations table.
     * Ultra-fast & bulk-optimized with whereIn lookup to run in < 1.5 seconds.
     */
    public function syncEstimatesPage(ZohoIntegration $integration, int $page = 1, int $perPage = 200): array
    {
        $payload = $this->fetchEstimates($integration, $page, $perPage);
        $estimates = $payload['estimates'] ?? [];

        if (empty($estimates)) {
            return [
                'page'       => $page,
                'has_more'   => false,
                'synced_new' => 0,
                'updated'    => 0,
                'total'      => 0,
            ];
        }

        // Collect all estimate IDs in this page for a single bulk query
        $zohoEstIds = [];
        foreach ($estimates as $est) {
            $id = (string) ($est['estimate_id'] ?? '');
            if ($id !== '') {
                $zohoEstIds[] = $id;
            }
        }

        $existingRecords = ZohoQuotation::whereIn('zoho_estimate_id', $zohoEstIds)
            ->get()
            ->keyBy('zoho_estimate_id');

        $syncedCount = 0;
        $updatedCount = 0;

        foreach ($estimates as $est) {
            $zohoEstId = (string) ($est['estimate_id'] ?? '');
            if ($zohoEstId === '') {
                continue;
            }

            $subTotal = (float) ($est['sub_total'] ?? $est['bcy_sub_total'] ?? 0);
            $taxTotal = (float) ($est['tax_total'] ?? $est['bcy_tax_total'] ?? 0);
            $total    = (float) ($est['total'] ?? $est['bcy_total'] ?? 0);

            if ($subTotal == 0 && $total > 0) {
                $subTotal = $taxTotal > 0 ? max(0.0, round($total - $taxTotal, 2)) : $total;
            }

            $estimateNumber = (string) ($est['estimate_number'] ?? ('EST-' . $zohoEstId));

            $attrs = [
                'company_id'       => $integration->company_id,
                'estimate_number'  => $estimateNumber,
                'customer_id'      => (string) ($est['customer_id'] ?? ''),
                'customer_name'    => $est['customer_name'] ?? 'Unnamed Customer',
                'company_name'     => $est['company_name'] ?? null,
                'estimate_date'    => $est['date'] ?? now()->toDateString(),
                'expiry_date'      => ! empty($est['expiry_date']) ? $est['expiry_date'] : Carbon::parse($est['date'] ?? now())->addDays(7)->toDateString(),
                'status'           => strtolower($est['status'] ?? 'draft'),
                'currency_code'    => $est['currency_code'] ?? 'INR',
                'currency_symbol'  => $est['currency_symbol'] ?? '₹',
                'exchange_rate'    => (float) ($est['exchange_rate'] ?? 1.0000),
                'sub_total'        => $subTotal,
                'tax_total'        => $taxTotal,
                'total'            => $total,
                'reference_number' => $est['reference_number'] ?? null,
                'billing_address'  => $est['billing_address'] ?? null,
                'shipping_address' => $est['shipping_address'] ?? null,
                'line_items'       => $est['line_items'] ?? null,
                'raw_data'         => $est,
                'synced_at'        => now(),
            ];

            $existing = $existingRecords->get($zohoEstId);
            if ($existing) {
                $existing->update($attrs);
                $updatedCount++;
            } else {
                $attrs['zoho_estimate_id'] = $zohoEstId;
                ZohoQuotation::create($attrs);
                $syncedCount++;
            }
        }

        $pageContext = $payload['page_context'] ?? [];
        $hasMore = (bool) ($pageContext['has_more_page'] ?? false);

        $integration->update(['last_synced_at' => now(), 'last_error' => null]);

        return [
            'page'       => $page,
            'has_more'   => $hasMore,
            'synced_new' => $syncedCount,
            'updated'    => $updatedCount,
            'total'      => $syncedCount + $updatedCount,
        ];
    }

    /**
     * Sync all estimates/quotations from Zoho Books into the local zoho_quotations table.
     * Uses syncEstimatesPage in fast-mode (no detailed item loops) to avoid any timeouts.
     */
    public function syncAllEstimates(ZohoIntegration $integration, bool $fetchDetailedItems = false): array
    {
        $page = 1;
        $perPage = 200;
        $totalSynced = 0;
        $totalNew = 0;
        $totalUpdated = 0;
        $hasMore = true;

        while ($hasMore) {
            $pageRes = $this->syncEstimatesPage($integration, $page, $perPage);
            $totalSynced += $pageRes['total'];
            $totalNew += $pageRes['synced_new'];
            $totalUpdated += $pageRes['updated'];
            $hasMore = $pageRes['has_more'];
            $page++;

            if ($page > 50) {
                break;
            }
        }

        return [
            'synced_new' => $totalNew,
            'updated'    => $totalUpdated,
            'total'      => $totalSynced,
        ];
    }

    /**
     * Migrate a ZohoQuotation record into native CRM Quotation.
     * Validates customer phone / email, matches and maps to corresponding Lead.
     */
    public function migrateQuotationToNative(ZohoQuotation $zohoQuotation, ?int $userId = null): Quotation
    {
        $companyId = $zohoQuotation->company_id ?: 1;

        // 1. External Network Fetch OUTSIDE DB transaction (and only if not fetched previously)
        $raw = is_array($zohoQuotation->raw_data) ? $zohoQuotation->raw_data : [];
        $hasFetchedDetails = (bool) ($raw['_details_fetched'] ?? false);
        $hasLineItems = ! empty($zohoQuotation->line_items) || ! empty($raw['line_items']);

        if (! $hasLineItems) {
            $integ = ZohoIntegration::current($companyId);
            if ($integ && $integ->is_connected && ! empty($zohoQuotation->zoho_estimate_id)) {
                try {
                    $details = $this->fetchEstimateDetails($integ, $zohoQuotation->zoho_estimate_id);
                    if (! empty($details)) {
                        $details['_details_fetched'] = true;
                        $subTotal = (float) ($details['sub_total'] ?? $details['bcy_sub_total'] ?? 0);
                        $taxTotal = (float) ($details['tax_total'] ?? $details['bcy_tax_total'] ?? 0);
                        $total    = (float) ($details['total'] ?? $details['bcy_total'] ?? 0);
                        if ($subTotal == 0 && $total > 0) {
                            $subTotal = $taxTotal > 0 ? max(0.0, round($total - $taxTotal, 2)) : $total;
                        }

                        $zohoQuotation->update([
                            'sub_total'        => $subTotal,
                            'tax_total'        => $taxTotal,
                            'total'            => $total,
                            'billing_address'  => $details['billing_address'] ?? $zohoQuotation->billing_address,
                            'shipping_address' => $details['shipping_address'] ?? $zohoQuotation->shipping_address,
                            'line_items'       => $details['line_items'] ?? $zohoQuotation->line_items,
                            'raw_data'         => $details,
                        ]);
                        $zohoQuotation->refresh();
                    } else {
                        $raw['_details_fetched'] = true;
                        $zohoQuotation->update(['raw_data' => $raw]);
                    }
                } catch (\Throwable $e) {
                    Log::warning("Could not fetch detailed estimate for #{$zohoQuotation->estimate_number}: " . $e->getMessage());
                    $raw['_details_fetched'] = true;
                    $zohoQuotation->update(['raw_data' => $raw]);
                }
            }
        }

        // 2. High-speed Database Transaction
        return DB::transaction(function () use ($zohoQuotation, $userId, $companyId) {
            static $leadPhoneCache = [];
            static $leadEmailCache = [];
            static $cachedProducts = null;

            if ($cachedProducts === null) {
                $cachedProducts = \App\Models\Product::all();
            }

            $customerName = trim($zohoQuotation->customer_name ?: 'Unnamed Customer');
            $companyName  = trim((string) ($zohoQuotation->company_name ?: $customerName));

            $billing = $zohoQuotation->billing_address ?: ($zohoQuotation->raw_data['billing_address'] ?? []);
            $shipping = $zohoQuotation->shipping_address ?: ($zohoQuotation->raw_data['shipping_address'] ?? []);

            // Find or create native CustomerMaster
            $customer = CustomerMaster::where('company_name', $companyName)
                ->orWhere('customer_name', $customerName)
                ->first();

            $contactPersonPhones = [];
            $contactPersonEmails = [];
            if (! empty($zohoQuotation->raw_data['contact_persons']) && is_array($zohoQuotation->raw_data['contact_persons'])) {
                foreach ($zohoQuotation->raw_data['contact_persons'] as $cp) {
                    if (! empty($cp['phone'])) {
                        $contactPersonPhones[] = $cp['phone'];
                    }
                    if (! empty($cp['mobile'])) {
                        $contactPersonPhones[] = $cp['mobile'];
                    }
                    if (! empty($cp['email'])) {
                        $contactPersonEmails[] = $cp['email'];
                    }
                }
            }

            $primaryPhone = $zohoQuotation->raw_data['phone'] ?? ($zohoQuotation->raw_data['mobile'] ?? ($billing['phone'] ?? ($contactPersonPhones[0] ?? null)));
            $primaryEmail = $zohoQuotation->raw_data['email'] ?? ($contactPersonEmails[0] ?? null);

            if (! $customer) {
                $customer = CustomerMaster::create([
                    'company_id'               => $companyId,
                    'customer_name'            => $customerName,
                    'company_name'             => $companyName,
                    'email'                    => $primaryEmail,
                    'mobile_number'            => $primaryPhone,
                    'gst_number'               => $zohoQuotation->raw_data['gst_no'] ?? null,
                    'pan_number'               => $zohoQuotation->raw_data['pan_no'] ?? null,
                    'billing_address_street_1' => $billing['address'] ?? null,
                    'billing_city'             => $billing['city'] ?? null,
                    'billing_state'            => $billing['state'] ?? 'Tamil Nadu',
                    'billing_pin_code'         => $billing['zip'] ?? null,
                    'billing_country'          => $billing['country'] ?? 'India',
                    'shipping_address_street_1'=> $shipping['address'] ?? null,
                    'shipping_city'            => $shipping['city'] ?? null,
                    'shipping_state'           => $shipping['state'] ?? null,
                    'shipping_pin_code'        => $shipping['zip'] ?? null,
                    'shipping_country'         => $shipping['country'] ?? null,
                    'status'                   => 'active',
                    'created_by'               => $userId,
                ]);
            }

            // Lead validation and mapping using customer mobile number / email / company / contact name
            $candidatePhones = array_merge([
                $primaryPhone,
                $zohoQuotation->raw_data['phone'] ?? null,
                $zohoQuotation->raw_data['mobile'] ?? null,
                $zohoQuotation->raw_data['contact_phone'] ?? null,
                $billing['phone'] ?? null,
                $customer->mobile_number ?? null,
            ], $contactPersonPhones);

            $candidateEmails = array_merge([
                $primaryEmail,
                $customer->email ?? null,
            ], $contactPersonEmails);

            $matchedLead = $this->findMatchingLead($candidatePhones, $candidateEmails, $companyName, $customerName);

            $leadId = $matchedLead?->id;
            if ($matchedLead && empty($customer->lead_id)) {
                $customer->update(['lead_id' => $matchedLead->id]);
            }

            // Amounts and tax calculation
            $subtotal    = (float) $zohoQuotation->sub_total;
            $taxAmount   = (float) $zohoQuotation->tax_total;
            $totalAmount = (float) $zohoQuotation->total;

            if ($subtotal == 0 && $totalAmount > 0) {
                $subtotal = $taxAmount > 0 ? max(0.0, round($totalAmount - $taxAmount, 2)) : $totalAmount;
            }

            $billingState = $billing['state'] ?? 'Tamil Nadu';
            $sellerState  = 'Tamil Nadu';
            $isInterstate = strcasecmp(trim($billingState), trim($sellerState)) !== 0;

            $taxRate    = 18.0;
            $cgstRate   = 0.0;
            $sgstRate   = 0.0;
            $igstRate   = 0.0;
            $cgstAmount = 0.0;
            $sgstAmount = 0.0;
            $igstAmount = 0.0;

            if ($taxAmount > 0) {
                if ($isInterstate) {
                    $igstRate   = 18.0;
                    $igstAmount = $taxAmount;
                    $taxType    = 'igst';
                } else {
                    $cgstRate   = 9.0;
                    $sgstRate   = 9.0;
                    $cgstAmount = round($taxAmount / 2, 2);
                    $sgstAmount = round($taxAmount - $cgstAmount, 2);
                    $taxType    = 'cgst_sgst';
                }
            } else {
                $taxType = 'none';
            }

            // Map status & customer response
            $zStatus = strtolower($zohoQuotation->status ?? 'draft');
            if (in_array($zStatus, ['accepted', 'invoiced'])) {
                $customerResponse = Quotation::CUSTOMER_RESPONSE_AGREE;
                $isApproved = true;
            } elseif ($zStatus === 'declined') {
                $customerResponse = Quotation::CUSTOMER_RESPONSE_DISAGREE;
                $isApproved = false;
            } else {
                $customerResponse = Quotation::CUSTOMER_RESPONSE_PENDING;
                $isApproved = false;
            }

            $quotationNumber = substr(trim((string) $zohoQuotation->estimate_number), 0, 95);
            $quotationDate   = $zohoQuotation->estimate_date ? $zohoQuotation->estimate_date->toDateString() : now()->toDateString();
            // Due date calculated as 7 days from the Quotation creation date
            $validUntil      = Carbon::parse($quotationDate)->addDays(7)->toDateString();

            // Format addresses
            $billToLines = array_filter([
                $companyName ?: $customerName,
                $billing['address'] ?? null,
                $billing['city'] ?? null,
                $billing['state'] ?? null,
                $billing['zip'] ?? null,
                $billing['phone'] ?? null,
            ]);
            $billToAddress = ! empty($billToLines) ? implode("\n", $billToLines) : null;

            $shipToLines = array_filter([
                $companyName ?: $customerName,
                $shipping['address'] ?? null,
                $shipping['city'] ?? null,
                $shipping['state'] ?? null,
                $shipping['zip'] ?? null,
            ]);
            $shipToAddress = ! empty($shipToLines) ? implode("\n", $shipToLines) : $billToAddress;

            $quotationData = [
                'company_id'        => $companyId,
                'quotation_no'      => $quotationNumber,
                'quotation_date'    => $quotationDate,
                'valid_until'       => $validUntil,
                'tax'               => $taxRate,
                'cgst_rate'         => $cgstRate,
                'sgst_rate'         => $sgstRate,
                'igst_rate'         => $igstRate,
                'subtotal'          => $subtotal,
                'tax_amount'        => $taxAmount,
                'cgst_amount'       => $cgstAmount,
                'sgst_amount'       => $sgstAmount,
                'igst_amount'       => $igstAmount,
                'total_amount'      => $totalAmount,
                'lead_id'           => $leadId,
                'is_approved'       => $isApproved,
                'customer_response' => $customerResponse,
                'approved_by'       => $isApproved ? ($userId ?: auth()->id()) : null,
                'approved_at'       => $isApproved ? now() : null,
                'notes'             => $zohoQuotation->raw_data['notes'] ?? ($zohoQuotation->raw_data['terms'] ?? null),
                'bill_to_address'   => $billToAddress,
                'ship_to_address'   => $shipToAddress,
                'gst_number'        => $zohoQuotation->raw_data['gst_no'] ?? ($customer->gst_number ?? null),
                'customer_state'    => $billingState,
                'seller_state'      => $sellerState,
                'tax_type'          => $taxType,
                'created_by'        => $userId ?: (auth()->id() ?: 1),
            ];

            // Check if quotation with this number already exists (ignoring global scopes and including soft-deleted)
            $existingQuotation = Quotation::withoutGlobalScopes()->withTrashed()->where('quotation_no', $quotationNumber)->first();

            if ($existingQuotation) {
                if ($existingQuotation->trashed()) {
                    $existingQuotation->restore();
                }
                $existingQuotation->update($quotationData);
                $quotation = $existingQuotation;
            } else {
                $quotation = Quotation::create($quotationData);
            }

            // Sync Line Items
            QuotationItem::withoutGlobalScopes()->where('quotation_id', $quotation->id)->forceDelete();
            $lineItems = $zohoQuotation->line_items ?? ($zohoQuotation->raw_data['line_items'] ?? []);

            if (! empty($lineItems) && is_array($lineItems)) {
                foreach ($lineItems as $item) {
                    $qty       = (float) ($item['quantity'] ?? 1);
                    $rate      = (float) ($item['rate'] ?? 0);
                    $discount  = (float) ($item['discount'] ?? 0);
                    $itemTotal = (float) ($item['item_total'] ?? ($qty * $rate));

                    if ($itemTotal == 0 && $rate > 0 && $qty > 0) {
                        $itemTotal = $qty * $rate;
                    }

                    $rawItemName = trim((string) ($item['name'] ?? ($item['item_name'] ?? ($item['product_name'] ?? ''))));
                    $rawDescription = trim((string) ($item['description'] ?? ''));

                    // If item name is empty but description is present, extract first line as item name
                    if ($rawItemName === '' && $rawDescription !== '') {
                        $lines = preg_split('/\r\n|\r|\n/', $rawDescription, 2);
                        $rawItemName = trim($lines[0]);
                        $rawDescription = isset($lines[1]) ? trim($lines[1]) : '';
                    }

                    if ($rawItemName === '') {
                        $rawItemName = 'Custom Service';
                    }

                    // 1. Exact match (case-insensitive) against existing products
                    $matchedProduct = $cachedProducts->first(function ($p) use ($rawItemName, $companyId) {
                        if ($p->company_id && $p->company_id != $companyId) {
                            return false;
                        }
                        return strcasecmp(trim($p->product_name ?? ''), $rawItemName) === 0
                            || strcasecmp(trim($p->package_name ?? ''), $rawItemName) === 0;
                    });

                    // 2. If no exact match, try matching normalized alphanumeric name
                    if (! $matchedProduct) {
                        $cleanItem = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($rawItemName));
                        if ($cleanItem !== '') {
                            $matchedProduct = $cachedProducts->first(function ($p) use ($cleanItem, $companyId) {
                                if ($p->company_id && $p->company_id != $companyId) {
                                    return false;
                                }
                                $cleanP = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($p->product_name ?? ''));
                                $cleanPkg = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($p->package_name ?? ''));
                                return ($cleanP !== '' && $cleanP === $cleanItem)
                                    || ($cleanPkg !== '' && $cleanPkg === $cleanItem);
                            });
                        }
                    }

                    // 3. If STILL no match, create a dedicated Product with the exact Zoho item name
                    if (! $matchedProduct) {
                        $catId = $this->guessProductCategoryId($rawItemName, $companyId);

                        $matchedProduct = Product::create([
                            'company_id'          => $companyId,
                            'product_category_id' => $catId,
                            'product_name'        => $rawItemName,
                            'package_name'        => $rawItemName,
                            'base_price'          => $rate > 0 ? $rate : 0,
                            'tax_type'            => 'percentage',
                            'tax_value'           => 18.0,
                            'status'              => 'active',
                            'created_by'          => $userId ?: (auth()->id() ?: 1),
                        ]);

                        $cachedProducts->push($matchedProduct);
                    }

                    // Clean description: do not duplicate item name if identical or redundant
                    $finalDesc = null;
                    if ($rawDescription !== '' && strcasecmp($rawDescription, $rawItemName) !== 0) {
                        $finalDesc = $rawDescription;
                    }

                    QuotationItem::create([
                        'company_id'   => $companyId,
                        'quotation_id' => $quotation->id,
                        'product_id'   => $matchedProduct->id,
                        'description'  => $finalDesc,
                        'qty'          => $qty > 0 ? $qty : 1,
                        'unit_price'   => $rate,
                        'discount'     => $discount,
                        'total'        => $itemTotal,
                    ]);
                }
            } else {
                // If no detailed line items, find or create generic Professional Services product
                $serviceProductName = 'Professional Services';
                $serviceProduct = $cachedProducts->first(function ($p) use ($serviceProductName, $companyId) {
                    if ($p->company_id && $p->company_id != $companyId) {
                        return false;
                    }
                    return strcasecmp(trim($p->product_name ?? ''), $serviceProductName) === 0;
                });

                if (! $serviceProduct) {
                    $serviceProduct = Product::create([
                        'company_id'          => $companyId,
                        'product_category_id' => 2,
                        'product_name'        => $serviceProductName,
                        'package_name'        => $serviceProductName,
                        'base_price'          => $subtotal > 0 ? $subtotal : 0,
                        'tax_type'            => 'percentage',
                        'tax_value'           => 18.0,
                        'status'              => 'active',
                        'created_by'          => $userId ?: (auth()->id() ?: 1),
                    ]);
                    $cachedProducts->push($serviceProduct);
                }

                QuotationItem::create([
                    'company_id'   => $companyId,
                    'quotation_id' => $quotation->id,
                    'product_id'   => $serviceProduct->id,
                    'description'  => 'Zoho Estimate #' . $quotationNumber . ' Services',
                    'qty'          => 1,
                    'unit_price'   => $subtotal,
                    'discount'     => 0,
                    'total'        => $subtotal,
                ]);
            }

            // Mark ZohoQuotation as migrated
            $zohoQuotation->update([
                'is_migrated_to_native'        => true,
                'migrated_native_quotation_id' => $quotation->id,
                'expiry_date'                  => $validUntil,
            ]);

            return $quotation;
        });
    }

    /**
     * Smartly match a Lead from candidate phones, candidate emails, company name, or customer name.
     */
    public function findMatchingLead(array $candidatePhones, array $candidateEmails = [], ?string $companyName = null, ?string $customerName = null): ?Lead
    {
        static $phoneLeadCache = [];
        static $emailLeadCache = [];
        static $companyLeadCache = [];
        static $nameLeadCache = [];

        // 1. Match by Phone (last 10 digits) - Highest accuracy
        $candidatePhones = array_filter(array_unique($candidatePhones));
        foreach ($candidatePhones as $phoneCandidate) {
            $digits = preg_replace('/[^0-9]/', '', (string) $phoneCandidate);
            if (strlen($digits) >= 10) {
                $last10 = substr($digits, -10);
                if (array_key_exists($last10, $phoneLeadCache)) {
                    if ($phoneLeadCache[$last10]) {
                        return $phoneLeadCache[$last10];
                    }
                } else {
                    $matched = Lead::withoutGlobalScopes()
                        ->where(function ($q) use ($last10) {
                            $q->where('mobile_number', $last10)
                              ->orWhere('mobile_number', '+91' . $last10)
                              ->orWhere('mobile_number', '91' . $last10)
                              ->orWhere('mobile_number', '0' . $last10)
                              ->orWhere('mobile_number', 'LIKE', "%{$last10}");
                        })
                        ->latest('id')
                        ->first();

                    if (! $matched) {
                        $matched = Lead::withoutGlobalScopes()
                            ->where('mobile_number', 'LIKE', "%{$last10}%")
                            ->latest('id')
                            ->first();
                    }

                    $phoneLeadCache[$last10] = $matched;
                    if ($matched) {
                        return $matched;
                    }
                }
            }
        }

        // 2. Match by Email
        $candidateEmails = array_filter(array_unique($candidateEmails));
        foreach ($candidateEmails as $emailCandidate) {
            $email = strtolower(trim((string) $emailCandidate));
            if (! empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                if (array_key_exists($email, $emailLeadCache)) {
                    if ($emailLeadCache[$email]) {
                        return $emailLeadCache[$email];
                    }
                } else {
                    $matched = Lead::withoutGlobalScopes()
                        ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
                        ->latest('id')
                        ->first();
                    $emailLeadCache[$email] = $matched;
                    if ($matched) {
                        return $matched;
                    }
                }
            }
        }

        // 3. Match by Company Name (if valid & not generic)
        $cleanCompany = trim((string) $companyName);
        if (mb_strlen($cleanCompany) >= 3 && ! in_array(strtolower($cleanCompany), ['self', 'individual', 'cash', 'unnamed customer', 'client', 'valued customer'])) {
            $compKey = strtolower($cleanCompany);
            if (array_key_exists($compKey, $companyLeadCache)) {
                if ($companyLeadCache[$compKey]) {
                    return $companyLeadCache[$compKey];
                }
            } else {
                $matched = Lead::withoutGlobalScopes()
                    ->where(function ($q) use ($compKey) {
                        $q->whereRaw('LOWER(TRIM(company_name)) = ?', [$compKey])
                          ->orWhereRaw('LOWER(TRIM(contact_name)) = ?', [$compKey]);
                    })
                    ->latest('id')
                    ->first();
                $companyLeadCache[$compKey] = $matched;
                if ($matched) {
                    return $matched;
                }
            }
        }

        // 4. Match by Customer / Contact Name
        $cleanContact = trim((string) $customerName);
        if (mb_strlen($cleanContact) >= 3 && ! in_array(strtolower($cleanContact), ['self', 'individual', 'cash', 'unnamed customer', 'client', 'valued customer'])) {
            $nameKey = strtolower($cleanContact);
            if (array_key_exists($nameKey, $nameLeadCache)) {
                if ($nameLeadCache[$nameKey]) {
                    return $nameLeadCache[$nameKey];
                }
            } else {
                $matched = Lead::withoutGlobalScopes()
                    ->where(function ($q) use ($nameKey) {
                        $q->whereRaw('LOWER(TRIM(contact_name)) = ?', [$nameKey])
                          ->orWhereRaw('LOWER(TRIM(company_name)) = ?', [$nameKey]);
                    })
                    ->latest('id')
                    ->first();
                $nameLeadCache[$nameKey] = $matched;
                if ($matched) {
                    return $matched;
                }
            }
        }

        return null;
    }

    /**
     * Guess product category ID based on item name keywords.
     */
    private function guessProductCategoryId(string $name, int $companyId): int
    {
        static $catCache = [];
        if (! isset($catCache[$companyId])) {
            $catCache[$companyId] = ProductCategory::withoutGlobalScopes()
                ->where(function ($q) use ($companyId) {
                    $q->where('company_id', $companyId)->orWhereNull('company_id');
                })
                ->get();
        }

        $lower = strtolower($name);
        $categories = $catCache[$companyId];

        // 1. Direct match with existing category name
        foreach ($categories as $cat) {
            $cName = strtolower(trim($cat->name ?? ''));
            if ($cName !== '' && (str_contains($lower, $cName) || str_contains($cName, $lower))) {
                return (int) $cat->id;
            }
        }

        // 2. Keyword-based matching
        if (str_contains($lower, 'domain')) {
            $c = $categories->first(fn ($c) => str_contains(strtolower($c->name ?? ''), 'domain'));
            if ($c) return (int) $c->id;
        }
        if (str_contains($lower, 'host') || str_contains($lower, 'server') || str_contains($lower, 'ssl')) {
            $c = $categories->first(fn ($c) => str_contains(strtolower($c->name ?? ''), 'host'));
            if ($c) return (int) $c->id;
        }
        if (str_contains($lower, 'design') || str_contains($lower, 'logo') || str_contains($lower, 'banner') || str_contains($lower, 'flyer') || str_contains($lower, 'ui') || str_contains($lower, 'ux')) {
            $c = $categories->first(fn ($c) => str_contains(strtolower($c->name ?? ''), 'design'));
            if ($c) return (int) $c->id;
        }
        if (str_contains($lower, 'seo') || str_contains($lower, 'marketing') || str_contains($lower, 'social') || str_contains($lower, 'ad') || str_contains($lower, 'google')) {
            $c = $categories->first(fn ($c) => str_contains(strtolower($c->name ?? ''), 'marketing') || str_contains(strtolower($c->name ?? ''), 'seo'));
            if ($c) return (int) $c->id;
        }
        if (str_contains($lower, 'web') || str_contains($lower, 'site') || str_contains($lower, 'portal')) {
            $c = $categories->first(fn ($c) => str_contains(strtolower($c->name ?? ''), 'web'));
            if ($c) return (int) $c->id;
        }
        if (str_contains($lower, 'app') || str_contains($lower, 'mobile') || str_contains($lower, 'android') || str_contains($lower, 'ios')) {
            $c = $categories->first(fn ($c) => str_contains(strtolower($c->name ?? ''), 'app'));
            if ($c) return (int) $c->id;
        }

        return (int) ($categories->first()?->id ?? 2);
    }

    /**
     * Automatically scan and map all unmapped native invoices & quotations to matching Leads.
     */
    public function autoMapAllUnmappedRecords(): array
    {
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');
        @ini_set('memory_limit', '512M');

        $invoicesUpdated = 0;
        $quotationsUpdated = 0;

        // Auto-map unmapped native invoices
        $unmappedInvoices = Invoice::whereNull('lead_id')->with('customer')->get();
        foreach ($unmappedInvoices as $inv) {
            $cust = $inv->customer;
            $phones = array_filter([$cust?->mobile_number, $inv->billing_phone ?? null]);
            $emails = array_filter([$cust?->email, $inv->billing_email ?? null]);
            $lead = $this->findMatchingLead($phones, $emails, $cust?->company_name, $cust?->customer_name);
            if ($lead) {
                $inv->update(['lead_id' => $lead->id]);
                if ($cust && empty($cust->lead_id)) {
                    $cust->update(['lead_id' => $lead->id]);
                }
                $invoicesUpdated++;
            }
        }

        // Auto-map unmapped native quotations
        $unmappedQuotations = Quotation::withoutGlobalScopes()->whereNull('lead_id')->get();
        foreach ($unmappedQuotations as $quote) {
            $address = (string) $quote->bill_to_address;
            preg_match_all('/[0-9]{10}/', $address, $matches);
            $phones = $matches[0] ?? [];
            preg_match_all('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $address, $eMatches);
            $emails = $eMatches[0] ?? [];
            $firstLine = trim(explode("\n", $address)[0] ?? '');

            $lead = $this->findMatchingLead($phones, $emails, $firstLine, $firstLine);
            if ($lead) {
                $quote->update(['lead_id' => $lead->id]);
                $quotationsUpdated++;
            }
        }

        return [
            'invoices_mapped'   => $invoicesUpdated,
            'quotations_mapped' => $quotationsUpdated,
            'total_mapped'      => $invoicesUpdated + $quotationsUpdated,
        ];
    }

    /**
     * Find an existing Zoho Contact ID or create a new Contact in Zoho Books.
     */
    public function findOrCreateZohoContact(ZohoIntegration $integration, array $contactData): string
    {
        $contactName = trim((string) ($contactData['contact_name'] ?? ($contactData['customer_name'] ?? '')));
        $companyName = trim((string) ($contactData['company_name'] ?? ''));
        $email       = trim((string) ($contactData['email'] ?? ''));
        $phone       = trim((string) ($contactData['phone'] ?? ($contactData['mobile_number'] ?? '')));
        $gstin       = trim((string) ($contactData['gstin'] ?? ($contactData['gst_number'] ?? '')));

        // 1. Check local synced records first
        $existingZohoId = null;
        if (! empty($contactName) || ! empty($companyName)) {
            $existingZohoId = ZohoInvoice::whereNotNull('customer_id')
                ->where('customer_id', '!=', '')
                ->where(function ($q) use ($contactName, $companyName) {
                    if ($contactName) {
                        $q->where('customer_name', $contactName);
                    }
                    if ($companyName) {
                        $q->orWhere('company_name', $companyName);
                    }
                })
                ->value('customer_id');

            if (! $existingZohoId) {
                $existingZohoId = ZohoQuotation::whereNotNull('customer_id')
                    ->where('customer_id', '!=', '')
                    ->where(function ($q) use ($contactName, $companyName) {
                        if ($contactName) {
                            $q->where('customer_name', $contactName);
                        }
                        if ($companyName) {
                            $q->orWhere('company_name', $companyName);
                        }
                    })
                    ->value('customer_id');
            }
        }

        if ($existingZohoId) {
            return (string) $existingZohoId;
        }

        $token  = $this->ensureValidAccessToken($integration);
        $apiUrl = rtrim($integration->api_url ?: 'https://www.zohoapis.in', '/');
        $orgId  = $integration->organization_id;

        // 2. Search Contacts in Zoho Books API
        $searchKey = $contactName ?: ($companyName ?: $email);
        if (! empty($searchKey)) {
            try {
                $searchRes = Http::withHeaders([
                    'Authorization' => "Zoho-oauthtoken {$token}",
                ])->get("{$apiUrl}/books/v3/contacts", [
                    'organization_id' => $orgId,
                    'search_text'     => $searchKey,
                    'per_page'        => 5,
                ]);

                if ($searchRes->successful()) {
                    $contacts = $searchRes->json('contacts') ?? [];
                    if (! empty($contacts[0]['contact_id'])) {
                        return (string) $contacts[0]['contact_id'];
                    }
                }
            } catch (\Throwable $se) {
                Log::warning('Zoho contact search error: ' . $se->getMessage());
            }
        }

        // 3. Create a new Contact in Zoho Books API
        $primaryName = $contactName ?: ($companyName ?: 'Valued Customer');
        $createPayload = [
            'contact_name'      => $primaryName,
            'company_name'      => $companyName ?: $primaryName,
            'contact_type'      => 'customer',
            'customer_sub_type' => ! empty($gstin) ? 'business' : 'individual',
            'email'             => $email ?: null,
            'phone'             => $phone ?: null,
            'gst_no'            => $gstin ?: null,
            'gst_treatment'     => ! empty($gstin) ? 'business_gst' : 'consumer',
            'billing_address'   => [
                'attention' => $primaryName,
                'address'   => $contactData['billing_address'] ?? null,
                'city'      => $contactData['billing_city'] ?? null,
                'state'     => $contactData['billing_state'] ?? 'Tamil Nadu',
                'zip'       => $contactData['billing_pincode'] ?? null,
                'country'   => $contactData['billing_country'] ?? 'India',
            ],
            'shipping_address'  => [
                'attention' => $primaryName,
                'address'   => $contactData['shipping_address'] ?? ($contactData['billing_address'] ?? null),
                'city'      => $contactData['shipping_city'] ?? ($contactData['billing_city'] ?? null),
                'state'     => $contactData['shipping_state'] ?? ($contactData['billing_state'] ?? 'Tamil Nadu'),
                'zip'       => $contactData['shipping_pincode'] ?? ($contactData['billing_pincode'] ?? null),
                'country'   => $contactData['shipping_country'] ?? 'India',
            ],
        ];

        $createRes = Http::withHeaders([
            'Authorization' => "Zoho-oauthtoken {$token}",
        ])->post("{$apiUrl}/books/v3/contacts?organization_id={$orgId}", $createPayload);

        if ($createRes->successful() && ! empty($createRes->json('contact.contact_id'))) {
            return (string) $createRes->json('contact.contact_id');
        }

        $errMsg = $createRes->json('message') ?: $createRes->body();
        Log::warning("Zoho contact create response: {$errMsg}");

        // If duplicate contact error, try searching again
        if (stripos($errMsg, 'already exists') !== false) {
            $searchRes = Http::withHeaders([
                'Authorization' => "Zoho-oauthtoken {$token}",
            ])->get("{$apiUrl}/books/v3/contacts", [
                'organization_id' => $orgId,
                'search_text'     => $primaryName,
            ]);
            if ($searchRes->successful() && ! empty($searchRes->json('contacts.0.contact_id'))) {
                return (string) $searchRes->json('contacts.0.contact_id');
            }
        }

        throw new \RuntimeException("Failed to resolve or create customer in Zoho Books: {$errMsg}");
    }

    /**
     * Create an Invoice directly in Zoho Books API and sync to local `zoho_invoices` table.
     */
    public function createInvoiceInZoho(Invoice $invoice, ?ZohoIntegration $integration = null): ?array
    {
        $integration = $integration ?: ZohoIntegration::current($invoice->company_id);
        if (! $integration || ! $integration->is_connected) {
            return null;
        }

        $invoice->loadMissing(['items', 'customer']);

        // 1. Resolve Zoho Customer ID
        $customerData = [
            'contact_name'     => $invoice->customer_name ?: ($invoice->customer?->customer_name ?? 'Valued Customer'),
            'company_name'     => $invoice->company_name ?: ($invoice->customer?->company_name ?? ''),
            'email'            => $invoice->customer_email ?: ($invoice->customer?->email ?? ''),
            'phone'            => $invoice->customer_phone ?: ($invoice->customer?->mobile_number ?? ''),
            'gstin'            => $invoice->customer_gstin ?: ($invoice->customer?->gst_number ?? ''),
            'billing_address'  => $invoice->billing_address,
            'billing_city'     => $invoice->billing_city,
            'billing_state'    => $invoice->billing_state,
            'billing_pincode'  => $invoice->billing_pincode,
            'billing_country'  => $invoice->billing_country,
            'shipping_address' => $invoice->shipping_address,
            'shipping_city'    => $invoice->shipping_city,
            'shipping_state'   => $invoice->shipping_state,
            'shipping_pincode' => $invoice->shipping_pincode,
            'shipping_country' => $invoice->shipping_country,
        ];

        $zohoCustomerId = $this->findOrCreateZohoContact($integration, $customerData);

        // 2. Prepare line items
        $lineItems = [];
        foreach ($invoice->items as $item) {
            $lineItems[] = [
                'name'        => $item->item_name ?: 'Services',
                'description' => $item->description ?: null,
                'rate'        => (float) $item->rate,
                'quantity'    => (float) ($item->qty ?: 1),
                'hsn_or_sac'  => $item->hsn_sac_code ?: null,
            ];
        }

        if (empty($lineItems)) {
            $lineItems[] = [
                'name'     => 'Services',
                'rate'     => (float) $invoice->total_amount,
                'quantity' => 1,
            ];
        }

        $token  = $this->ensureValidAccessToken($integration);
        $apiUrl = rtrim($integration->api_url ?: 'https://www.zohoapis.in', '/');
        $orgId  = $integration->organization_id;

        $payload = [
            'customer_id'      => $zohoCustomerId,
            'invoice_number'   => $invoice->invoice_number,
            'date'             => $invoice->invoice_date ? Carbon::parse($invoice->invoice_date)->toDateString() : now()->toDateString(),
            'due_date'         => $invoice->due_date ? Carbon::parse($invoice->due_date)->toDateString() : now()->toDateString(),
            'line_items'       => $lineItems,
            'notes'            => $invoice->customer_notes ?: null,
            'terms'            => $invoice->terms_conditions ?: null,
            'is_inclusive_tax' => false,
        ];

        $response = Http::withHeaders([
            'Authorization' => "Zoho-oauthtoken {$token}",
        ])->post("{$apiUrl}/books/v3/invoices?organization_id={$orgId}", $payload);

        if (! $response->successful()) {
            $msg = $response->json('message') ?: $response->body();
            Log::error("Zoho Books createInvoice API failed: {$msg}", ['payload' => $payload]);
            throw new \RuntimeException("Zoho Books Invoice Creation failed: {$msg}");
        }

        $zohoInvData = $response->json('invoice') ?? [];
        $zohoInvId   = (string) ($zohoInvData['invoice_id'] ?? '');

        if ($zohoInvId) {
            ZohoInvoice::updateOrCreate(
                ['zoho_invoice_id' => $zohoInvId],
                [
                    'company_id'                 => $invoice->company_id,
                    'invoice_number'             => $zohoInvData['invoice_number'] ?? $invoice->invoice_number,
                    'customer_id'                => $zohoCustomerId,
                    'customer_name'              => $invoice->customer_name ?: ($invoice->company_name ?: 'Valued Customer'),
                    'company_name'               => $invoice->company_name,
                    'invoice_date'               => $invoice->invoice_date ? Carbon::parse($invoice->invoice_date)->toDateString() : now()->toDateString(),
                    'due_date'                   => $invoice->due_date ? Carbon::parse($invoice->due_date)->toDateString() : null,
                    'status'                     => (string) ($zohoInvData['status'] ?? 'draft'),
                    'sub_total'                  => (float) ($invoice->subtotal ?? 0),
                    'tax_total'                  => (float) ($invoice->total_tax ?? 0),
                    'total'                      => (float) ($invoice->total_amount ?? 0),
                    'balance'                    => (float) ($invoice->balance_due ?? 0),
                    'raw_data'                   => $zohoInvData,
                    'is_migrated_to_native'      => true,
                    'migrated_native_invoice_id' => $invoice->id,
                    'synced_at'                  => now(),
                ]
            );
        }

        return $zohoInvData;
    }

    /**
     * Create an Estimate / Quotation directly in Zoho Books API and sync to local `zoho_quotations` table.
     */
    public function createEstimateInZoho(Quotation $quotation, ?ZohoIntegration $integration = null): ?array
    {
        $integration = $integration ?: ZohoIntegration::current($quotation->company_id);
        if (! $integration || ! $integration->is_connected) {
            return null;
        }

        $quotation->loadMissing(['items.product', 'lead']);

        // 1. Resolve Zoho Customer ID
        $lead = $quotation->lead;
        $customerName = $lead?->company_name ?: ($lead?->contact_name ?: 'Valued Customer');
        $customerData = [
            'contact_name'     => $customerName,
            'company_name'     => $lead?->company_name ?: $customerName,
            'email'            => $lead?->email ?? '',
            'phone'            => $lead?->mobile_number ?? '',
            'gstin'            => $quotation->gst_number ?? '',
            'billing_address'  => $quotation->bill_to_address,
            'billing_state'    => $quotation->customer_state ?: 'Tamil Nadu',
            'shipping_address' => $quotation->ship_to_address ?: $quotation->bill_to_address,
            'shipping_state'   => $quotation->customer_state ?: 'Tamil Nadu',
        ];

        $zohoCustomerId = $this->findOrCreateZohoContact($integration, $customerData);

        // 2. Prepare line items
        $lineItems = [];
        foreach ($quotation->items as $item) {
            $pName = $item->product?->product_name ?: ($item->description ?: 'Product');
            $lineItems[] = [
                'name'        => $pName,
                'description' => $item->description ?: null,
                'rate'        => (float) $item->unit_price,
                'quantity'    => (float) ($item->qty ?: 1),
                'discount'    => (float) ($item->discount ?? 0),
            ];
        }

        if (empty($lineItems)) {
            $lineItems[] = [
                'name'     => 'Service / Product',
                'rate'     => (float) $quotation->total_amount,
                'quantity' => 1,
            ];
        }

        $token  = $this->ensureValidAccessToken($integration);
        $apiUrl = rtrim($integration->api_url ?: 'https://www.zohoapis.in', '/');
        $orgId  = $integration->organization_id;

        $payload = [
            'customer_id'      => $zohoCustomerId,
            'estimate_number'  => $quotation->quotation_no,
            'date'             => $quotation->quotation_date ? Carbon::parse($quotation->quotation_date)->toDateString() : now()->toDateString(),
            'expiry_date'      => $quotation->valid_until ? Carbon::parse($quotation->valid_until)->toDateString() : null,
            'line_items'       => $lineItems,
            'notes'            => $quotation->notes ?: null,
            'is_inclusive_tax' => false,
        ];

        $response = Http::withHeaders([
            'Authorization' => "Zoho-oauthtoken {$token}",
        ])->post("{$apiUrl}/books/v3/estimates?organization_id={$orgId}", $payload);

        if (! $response->successful()) {
            $msg = $response->json('message') ?: $response->body();
            Log::error("Zoho Books createEstimate API failed: {$msg}", ['payload' => $payload]);
            throw new \RuntimeException("Zoho Books Quotation (Estimate) Creation failed: {$msg}");
        }

        $zohoEstData = $response->json('estimate') ?? [];
        $zohoEstId   = (string) ($zohoEstData['estimate_id'] ?? '');

        if ($zohoEstId) {
            ZohoQuotation::updateOrCreate(
                ['zoho_estimate_id' => $zohoEstId],
                [
                    'company_id'                   => $quotation->company_id,
                    'estimate_number'              => $zohoEstData['estimate_number'] ?? $quotation->quotation_no,
                    'customer_id'                  => $zohoCustomerId,
                    'customer_name'                => $customerName,
                    'company_name'                 => $quotation->lead?->company_name,
                    'estimate_date'                => $quotation->quotation_date ? Carbon::parse($quotation->quotation_date)->toDateString() : now()->toDateString(),
                    'expiry_date'                  => $quotation->valid_until ? Carbon::parse($quotation->valid_until)->toDateString() : null,
                    'status'                       => (string) ($zohoEstData['status'] ?? 'draft'),
                    'sub_total'                    => (float) ($quotation->subtotal ?? 0),
                    'tax_total'                    => (float) ($quotation->tax_amount ?? 0),
                    'total'                        => (float) ($quotation->total_amount ?? 0),
                    'raw_data'                     => $zohoEstData,
                    'is_migrated_to_native'        => true,
                    'migrated_native_quotation_id' => $quotation->id,
                    'synced_at'                    => now(),
                ]
            );
        }

        return $zohoEstData;
    }
}
