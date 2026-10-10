<?php

namespace App\Http\Controllers;

use App\Models\DomainRecord;
use App\Models\DomainRenewal;
use App\Models\HostingRecord;
use App\Models\HostingRenewal;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DomainsHostingController extends Controller
{
    /**
     * Display Domains & Hosting Management page.
     */
    public function index(Request $request)
    {
        $activeTab = $request->query('tab', 'domains');
        $user = auth()->user();
        $companyId = $user?->company_id;

        $godaddyKey = config('services.godaddy.key', env('GODADDY_API_KEY'));
        $godaddySecret = config('services.godaddy.secret', env('GODADDY_API_SECRET'));
        $godaddyEnv = config('services.godaddy.environment', env('GODADDY_ENVIRONMENT', 'production'));
        $isGodaddyConfigured = ! empty($godaddyKey) && ! empty($godaddySecret);

        // Fetch Domains
        $domainQuery = DomainRecord::query()
            ->with(['lead', 'renewals'])
            ->withCount('renewals')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($request->filled('search') && $activeTab === 'domains', function ($q) use ($request) {
                $search = trim($request->search);
                $q->where(function ($q2) use ($search) {
                    $q2->where('domain_name', 'like', "%{$search}%")
                       ->orWhere('registrar', 'like', "%{$search}%")
                       ->orWhere('client_name', 'like', "%{$search}%")
                       ->orWhere('notes', 'like', "%{$search}%")
                       ->orWhereHas('lead', function ($lq) use ($search) {
                           $lq->where('contact_name', 'like', "%{$search}%")
                              ->orWhere('company_name', 'like', "%{$search}%")
                              ->orWhere('mobile_number', 'like', "%{$search}%");
                       });
                });
            })
            ->when($request->filled('status') && $activeTab === 'domains', function ($q) use ($request) {
                if ($request->status === 'expiring_soon') {
                    $q->whereBetween('expires_at', [now()->startOfDay(), now()->addDays(30)->endOfDay()]);
                } elseif ($request->status === 'expired') {
                    $q->where('expires_at', '<', now()->startOfDay());
                } else {
                    $q->where('status', $request->status);
                }
            });

        $domains = $domainQuery->orderBy('expires_at', 'asc')->paginate(15, ['*'], 'domains_page')->withQueryString();

        // Calculate Domain Stats
        $allDomains = DomainRecord::when($companyId, fn ($q) => $q->where('company_id', $companyId))->get();
        $domainStats = [
            'total'         => $allDomains->count(),
            'active'        => $allDomains->where('status', 'ACTIVE')->count(),
            'expiring_soon' => $allDomains->filter(fn ($d) => $d->expires_at && $d->days_until_expiration >= 0 && $d->days_until_expiration <= 30)->count(),
            'expired'       => $allDomains->filter(fn ($d) => $d->expires_at && $d->days_until_expiration < 0)->count(),
        ];

        // Fetch Hostings
        $hostingQuery = HostingRecord::query()
            ->with(['lead', 'renewals'])
            ->withCount('renewals')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($request->filled('search') && $activeTab === 'hostings', function ($q) use ($request) {
                $search = trim($request->search);
                $q->where(function ($q2) use ($search) {
                    $q2->where('hosting_name', 'like', "%{$search}%")
                       ->orWhere('provider', 'like', "%{$search}%")
                       ->orWhere('ip_address', 'like', "%{$search}%")
                       ->orWhere('client_name', 'like', "%{$search}%")
                       ->orWhereHas('lead', function ($lq) use ($search) {
                           $lq->where('contact_name', 'like', "%{$search}%")
                              ->orWhere('company_name', 'like', "%{$search}%")
                              ->orWhere('mobile_number', 'like', "%{$search}%");
                       });
                });
            })
            ->when($request->filled('status') && $activeTab === 'hostings', function ($q) use ($request) {
                if ($request->status === 'expiring_soon') {
                    $q->whereBetween('renewal_date', [now()->startOfDay(), now()->addDays(30)->endOfDay()]);
                } elseif ($request->status === 'expired') {
                    $q->where('renewal_date', '<', now()->startOfDay());
                } else {
                    $q->where('status', $request->status);
                }
            });

        $hostings = $hostingQuery->orderBy('renewal_date', 'asc')->paginate(15, ['*'], 'hostings_page')->withQueryString();

        // Calculate Hosting Stats
        $allHostings = HostingRecord::when($companyId, fn ($q) => $q->where('company_id', $companyId))->get();
        $hostingStats = [
            'total'         => $allHostings->count(),
            'active'        => $allHostings->where('status', 'ACTIVE')->count(),
            'renewal_due'   => $allHostings->filter(fn ($h) => $h->renewal_date && $h->days_until_renewal >= 0 && $h->days_until_renewal <= 30)->count(),
            'total_amount'  => $allHostings->sum('renewal_amount'),
        ];

        return view('pages.accounts.domains_hosting.index', compact(
            'activeTab',
            'domains',
            'domainStats',
            'hostings',
            'hostingStats',
            'isGodaddyConfigured'
        ));
    }

    /**
     * Store manual Domain record.
     */
    public function storeDomain(Request $request)
    {
        $validated = $request->validate([
            'domain_name' => 'required|string|max:255',
            'registrar'   => 'required|string|max:100',
            'status'      => 'required|string|in:ACTIVE,EXPIRED,CANCELLED,PENDING_RENEWAL',
            'expires_at'  => 'nullable|date',
            'auto_renew'  => 'nullable|boolean',
            'privacy'     => 'nullable|boolean',
            'client_name' => 'nullable|string|max:255',
            'notes'       => 'nullable|string',
        ]);

        $user = auth()->user();
        $validated['company_id'] = $user?->company_id;
        $validated['created_by'] = $user?->id;
        $validated['auto_renew'] = filter_var($request->input('auto_renew', false), FILTER_VALIDATE_BOOLEAN);
        $validated['privacy']    = filter_var($request->input('privacy', false), FILTER_VALIDATE_BOOLEAN);

        DomainRecord::create($validated);

        return redirect()->route('accounts.domains-hosting.index', ['tab' => 'domains'])
            ->with('success', 'Domain record created successfully!');
    }

    /**
     * Update Domain record.
     */
    public function updateDomain(Request $request, DomainRecord $domain)
    {
        $validated = $request->validate([
            'domain_name' => 'required|string|max:255',
            'registrar'   => 'required|string|max:100',
            'status'      => 'required|string|in:ACTIVE,EXPIRED,CANCELLED,PENDING_RENEWAL',
            'expires_at'  => 'nullable|date',
            'auto_renew'  => 'nullable|boolean',
            'privacy'     => 'nullable|boolean',
            'client_name' => 'nullable|string|max:255',
            'notes'       => 'nullable|string',
        ]);

        $validated['auto_renew'] = filter_var($request->input('auto_renew', false), FILTER_VALIDATE_BOOLEAN);
        $validated['privacy']    = filter_var($request->input('privacy', false), FILTER_VALIDATE_BOOLEAN);

        $domain->update($validated);

        if ($request->has('from_show') || str_contains(url()->previous(), "/domains/{$domain->id}")) {
            return redirect()->route('accounts.domains-hosting.domains.show', $domain->id)
                ->with('success', 'Domain record updated successfully!');
        }

        return redirect()->route('accounts.domains-hosting.index', ['tab' => 'domains'])
            ->with('success', 'Domain record updated successfully!');
    }

    /**
     * Display a specific Domain record details, mapped lead, and renewal history.
     */
    public function showDomain(DomainRecord $domain)
    {
        $domain->load(['lead', 'creator', 'renewals.creator']);
        $renewalsCount = $domain->renewals->count();
        $totalRenewalSpend = $domain->renewals->sum('amount');

        return view('pages.accounts.domains_hosting.show', compact(
            'domain',
            'renewalsCount',
            'totalRenewalSpend'
        ));
    }

    /**
     * Migrate/Map domain to a CRM Lead.
     */
    public function migrateLead(Request $request, DomainRecord $domain)
    {
        $validated = $request->validate([
            'lead_id' => 'required|exists:leads,id',
        ]);

        $lead = Lead::findOrFail($validated['lead_id']);

        $domain->lead_id = $lead->id;
        $displayName = $lead->contact_name ?: ($lead->company_name ?: 'Lead #'.$lead->id);
        $domain->client_name = $displayName;
        $domain->save();

        return redirect()->route('accounts.domains-hosting.domains.show', $domain->id)
            ->with('success', "Domain '{$domain->domain_name}' successfully migrated to lead: {$displayName}!");
    }

    /**
     * Unlink CRM Lead from domain.
     */
    public function unlinkLead(DomainRecord $domain)
    {
        $domain->lead_id = null;
        $domain->save();

        return redirect()->route('accounts.domains-hosting.domains.show', $domain->id)
            ->with('success', "Lead unlinked from domain '{$domain->domain_name}'.");
    }

    /**
     * Store a new domain renewal entry.
     */
    public function storeRenewal(Request $request, DomainRecord $domain)
    {
        $validated = $request->validate([
            'renewal_date' => 'required|date',
            'expires_at'   => 'required|date|after_or_equal:renewal_date',
            'amount'       => 'nullable|numeric|min:0',
            'notes'        => 'nullable|string|max:1000',
        ]);

        $user = auth()->user();

        $domain->renewals()->create([
            'company_id'   => $user?->company_id,
            'renewal_date' => $validated['renewal_date'],
            'expires_at'   => $validated['expires_at'],
            'amount'       => $validated['amount'] ?? null,
            'notes'        => $validated['notes'] ?? null,
            'created_by'   => $user?->id,
        ]);

        // Keep domain expires_at in sync with the renewal's new expiry date
        $domain->expires_at = $validated['expires_at'];
        if ($domain->status === 'EXPIRED' && Carbon::parse($validated['expires_at'])->isFuture()) {
            $domain->status = 'ACTIVE';
        }
        $domain->save();

        return redirect()->route('accounts.domains-hosting.domains.show', $domain->id)
            ->with('success', 'Domain renewal entry recorded successfully!');
    }

    /**
     * Delete a domain renewal entry.
     */
    public function destroyRenewal(DomainRecord $domain, DomainRenewal $renewal)
    {
        if ($renewal->domain_record_id !== $domain->id) {
            abort(403, 'Unauthorized action.');
        }

        $renewal->delete();

        // Re-sync expires_at with latest remaining renewal, if any
        $latest = $domain->renewals()->orderBy('expires_at', 'desc')->first();
        if ($latest) {
            $domain->expires_at = $latest->expires_at;
            $domain->save();
        }

        return redirect()->route('accounts.domains-hosting.domains.show', $domain->id)
            ->with('success', 'Renewal record deleted successfully!');
    }

    /**
     * AJAX search leads by Name or Mobile number for Select2.
     */
    public function searchLeads(Request $request)
    {
        $search = trim($request->input('q', $request->input('term', '')));
        $companyId = auth()->user()?->company_id;

        $query = Lead::query()
            ->when($companyId, fn ($q) => $q->where(function ($cq) use ($companyId) {
                $cq->where('company_id', $companyId)->orWhereNull('company_id');
            }));

        if (!empty($search)) {
            $query->where(function ($sub) use ($search) {
                $sub->where('contact_name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('mobile_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");

                // If searching by lead ID (e.g. 123 or #123)
                $cleanId = ltrim($search, '#');
                if (is_numeric($cleanId)) {
                    $sub->orWhere('id', (int) $cleanId);
                }

                // If user entered phone number with spaces, symbols, or country code
                $digits = preg_replace('/\D+/', '', $search);
                if (strlen($digits) >= 4) {
                    $sub->orWhere('mobile_number', 'like', "%{$digits}%");
                    if (str_starts_with($digits, '91') && strlen($digits) >= 12) {
                        $last10 = substr($digits, -10);
                        $sub->orWhere('mobile_number', 'like', "%{$last10}%");
                    }
                }
            });
        }

        $leads = $query->latest('id')->limit(30)->get(['id', 'contact_name', 'company_name', 'mobile_number', 'email']);

        $results = $leads->map(function ($l) {
            $primary = $l->contact_name ?: ($l->company_name ?: 'Unnamed Lead');
            $secondary = ($l->company_name && $l->company_name !== $primary) ? " ({$l->company_name})" : '';
            $phone = $l->mobile_number ? " • 📞 {$l->mobile_number}" : '';
            return [
                'id'            => $l->id,
                'text'          => "#{$l->id} - {$primary}{$secondary}{$phone}",
                'contact_name'  => $l->contact_name,
                'company_name'  => $l->company_name,
                'mobile_number' => $l->mobile_number,
            ];
        });

        return response()->json([
            'results' => $results,
        ]);
    }

    /**
     * Delete Domain record.
     */
    public function destroyDomain(DomainRecord $domain)
    {
        $domain->delete();

        return redirect()->route('accounts.domains-hosting.index', ['tab' => 'domains'])
            ->with('success', 'Domain record deleted successfully!');
    }

    /**
     * Sync Domains with GoDaddy API.
     */
    public function syncGodaddy(Request $request)
    {
        $godaddyKey = config('services.godaddy.key', env('GODADDY_API_KEY'));
        $godaddySecret = config('services.godaddy.secret', env('GODADDY_API_SECRET'));
        $godaddyEnv = config('services.godaddy.environment', env('GODADDY_ENVIRONMENT', 'production'));

        if (empty($godaddyKey) || empty($godaddySecret)) {
            return redirect()->route('accounts.domains-hosting.index', ['tab' => 'domains'])
                ->with('error', 'GoDaddy API Credentials (GODADDY_API_KEY & GODADDY_API_SECRET) are not configured in environment.');
        }

        $baseUrl = strtolower($godaddyEnv) === 'ote'
            ? 'https://api.ote-godaddy.com/v1/domains'
            : 'https://api.godaddy.com/v1/domains';

        try {
            $godaddyDomains = [];
            $marker = null;
            $limit = 1000;

            do {
                $queryParams = ['limit' => $limit];
                if ($marker) {
                    $queryParams['marker'] = $marker;
                }

                $response = Http::timeout(30)
                    ->withHeaders([
                        'Authorization' => "sso-key {$godaddyKey}:{$godaddySecret}",
                        'Accept'        => 'application/json',
                    ])
                    ->get($baseUrl, $queryParams);

                if ($response->failed()) {
                    Log::error('GoDaddy API Sync Error', ['status' => $response->status(), 'body' => $response->body()]);
                    return redirect()->route('accounts.domains-hosting.index', ['tab' => 'domains'])
                        ->with('error', 'Failed to fetch domains from GoDaddy API: ' . ($response->json('message') ?? 'HTTP ' . $response->status()));
                }

                $batch = $response->json();
                if (! is_array($batch) || empty($batch)) {
                    break;
                }

                $godaddyDomains = array_merge($godaddyDomains, $batch);

                if (count($batch) >= $limit) {
                    $lastItem = end($batch);
                    $marker = $lastItem['domain'] ?? null;
                } else {
                    $marker = null;
                }
            } while ($marker);

            if (empty($godaddyDomains)) {
                return redirect()->route('accounts.domains-hosting.index', ['tab' => 'domains'])
                    ->with('error', 'No domains received from GoDaddy API.');
            }

            $user = auth()->user();
            $companyId = $user?->company_id;
            $syncedCount = 0;

            foreach ($godaddyDomains as $item) {
                if (empty($item['domain'])) {
                    continue;
                }

                $expiresAt = null;
                if (! empty($item['expires'])) {
                    $expiresAt = date('Y-m-d', strtotime($item['expires']));
                }

                DomainRecord::updateOrCreate(
                    [
                        'company_id'  => $companyId,
                        'domain_name' => $item['domain'],
                    ],
                    [
                        'registrar'         => 'GoDaddy',
                        'status'            => strtoupper($item['status'] ?? 'ACTIVE'),
                        'expires_at'        => $expiresAt,
                        'auto_renew'        => ! empty($item['autoRenew']),
                        'privacy'           => ! empty($item['privacy']),
                        'godaddy_domain_id' => (string) ($item['domainId'] ?? ''),
                        'created_by'        => $user?->id,
                    ]
                );
                $syncedCount++;
            }

            return redirect()->route('accounts.domains-hosting.index', ['tab' => 'domains'])
                ->with('success', "Successfully synced {$syncedCount} domain(s) from GoDaddy API!");

        } catch (\Throwable $e) {
            Log::error('GoDaddy Sync Exception', ['error' => $e->getMessage()]);
            return redirect()->route('accounts.domains-hosting.index', ['tab' => 'domains'])
                ->with('error', 'GoDaddy API connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Store manual Hosting record.
     */
    public function storeHosting(Request $request)
    {
        $validated = $request->validate([
            'hosting_name'   => 'required|string|max:255',
            'provider'       => 'required|string|max:100',
            'ip_address'     => 'nullable|string|max:100',
            'plan_type'      => 'required|string|max:100',
            'status'         => 'required|string|in:ACTIVE,EXPIRED,PENDING_RENEWAL',
            'renewal_date'   => 'nullable|date',
            'renewal_amount' => 'nullable|numeric|min:0',
            'client_name'    => 'nullable|string|max:255',
            'notes'          => 'nullable|string',
        ]);

        $user = auth()->user();
        $validated['company_id'] = $user?->company_id;
        $validated['created_by'] = $user?->id;

        HostingRecord::create($validated);

        return redirect()->route('accounts.domains-hosting.index', ['tab' => 'hostings'])
            ->with('success', 'Hosting record created successfully!');
    }

    /**
     * Update Hosting record.
     */
    public function updateHosting(Request $request, HostingRecord $hosting)
    {
        $validated = $request->validate([
            'hosting_name'   => 'required|string|max:255',
            'provider'       => 'required|string|max:100',
            'ip_address'     => 'nullable|string|max:100',
            'plan_type'      => 'required|string|max:100',
            'status'         => 'required|string|in:ACTIVE,EXPIRED,PENDING_RENEWAL',
            'renewal_date'   => 'nullable|date',
            'renewal_amount' => 'nullable|numeric|min:0',
            'client_name'    => 'nullable|string|max:255',
            'notes'          => 'nullable|string',
        ]);

        $hosting->update($validated);

        if ($request->has('from_show') || str_contains(url()->previous(), "/hostings/{$hosting->id}")) {
            return redirect()->route('accounts.domains-hosting.hostings.show', $hosting->id)
                ->with('success', 'Hosting record updated successfully!');
        }

        return redirect()->route('accounts.domains-hosting.index', ['tab' => 'hostings'])
            ->with('success', 'Hosting record updated successfully!');
    }

    /**
     * Display a specific Hosting record details, mapped lead, and renewal history.
     */
    public function showHosting(HostingRecord $hosting)
    {
        $hosting->load(['lead', 'creator', 'renewals.creator']);
        $renewalsCount = $hosting->renewals->count();
        $totalRenewalSpend = $hosting->renewals->sum('amount');

        return view('pages.accounts.domains_hosting.show_hosting', compact(
            'hosting',
            'renewalsCount',
            'totalRenewalSpend'
        ));
    }

    /**
     * Migrate/Map hosting to a CRM Lead.
     */
    public function migrateHostingLead(Request $request, HostingRecord $hosting)
    {
        $validated = $request->validate([
            'lead_id' => 'required|exists:leads,id',
        ]);

        $lead = Lead::findOrFail($validated['lead_id']);

        $hosting->lead_id = $lead->id;
        $displayName = $lead->contact_name ?: ($lead->company_name ?: 'Lead #'.$lead->id);
        $hosting->client_name = $displayName;
        $hosting->save();

        return redirect()->route('accounts.domains-hosting.hostings.show', $hosting->id)
            ->with('success', "Hosting '{$hosting->hosting_name}' successfully migrated to lead: {$displayName}!");
    }

    /**
     * Unlink CRM Lead from hosting.
     */
    public function unlinkHostingLead(HostingRecord $hosting)
    {
        $hosting->lead_id = null;
        $hosting->save();

        return redirect()->route('accounts.domains-hosting.hostings.show', $hosting->id)
            ->with('success', "Lead unlinked from hosting '{$hosting->hosting_name}'.");
    }

    /**
     * Store a new hosting renewal entry.
     */
    public function storeHostingRenewal(Request $request, HostingRecord $hosting)
    {
        $validated = $request->validate([
            'renewal_date' => 'required|date',
            'expires_at'   => 'required|date|after_or_equal:renewal_date',
            'amount'       => 'nullable|numeric|min:0',
            'notes'        => 'nullable|string|max:1000',
        ]);

        $user = auth()->user();

        $hosting->renewals()->create([
            'company_id'   => $user?->company_id,
            'renewal_date' => $validated['renewal_date'],
            'expires_at'   => $validated['expires_at'],
            'amount'       => $validated['amount'] ?? null,
            'notes'        => $validated['notes'] ?? null,
            'created_by'   => $user?->id,
        ]);

        // Keep hosting renewal_date in sync with the renewal's new expiry date
        $hosting->renewal_date = $validated['expires_at'];
        if ($validated['amount'] !== null) {
            $hosting->renewal_amount = $validated['amount'];
        }
        if ($hosting->status === 'EXPIRED' && Carbon::parse($validated['expires_at'])->isFuture()) {
            $hosting->status = 'ACTIVE';
        }
        $hosting->save();

        return redirect()->route('accounts.domains-hosting.hostings.show', $hosting->id)
            ->with('success', 'Hosting renewal entry recorded successfully!');
    }

    /**
     * Delete a hosting renewal entry.
     */
    public function destroyHostingRenewal(HostingRecord $hosting, HostingRenewal $renewal)
    {
        if ($renewal->hosting_record_id !== $hosting->id) {
            abort(403, 'Unauthorized action.');
        }

        $renewal->delete();

        // Re-sync renewal_date and amount with latest remaining renewal, if any
        $latest = $hosting->renewals()->orderBy('expires_at', 'desc')->first();
        if ($latest) {
            $hosting->renewal_date = $latest->expires_at;
            if ($latest->amount !== null) {
                $hosting->renewal_amount = $latest->amount;
            }
            $hosting->save();
        }

        return redirect()->route('accounts.domains-hosting.hostings.show', $hosting->id)
            ->with('success', 'Renewal record deleted successfully!');
    }

    /**
     * Delete Hosting record.
     */
    public function destroyHosting(HostingRecord $hosting)
    {
        $hosting->delete();

        return redirect()->route('accounts.domains-hosting.index', ['tab' => 'hostings'])
            ->with('success', 'Hosting record deleted successfully!');
    }
}
