@extends('layouts.app')

@section('title', 'Zoho Books API & OAuth Settings - Accounts')

@push('styles')
<style>
.zh-page { display:flex; flex-direction:column; min-height:100%; background:#f4f5f7; font-family:'Inter',sans-serif; }
.zh-topbar { display:flex; justify-content:space-between; align-items:center; gap:16px; padding:0 28px; min-height:64px; background:#fff; border-bottom:1px solid #e1dee3; position:sticky; top:0; z-index:30; }
.zh-title { font-size:18px; font-weight:800; color:#121212; display:flex; align-items:center; gap:8px; line-height:1.2; }
.zh-breadcrumbs { font-size:12px; color:#9e9e9e; margin-top:2px; display:flex; align-items:center; gap:6px; }
.zh-breadcrumbs a { color:#9e9e9e; text-decoration:none; }
.zh-breadcrumbs a:hover { color:#fe5f04; }
.zh-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.zh-body { padding:22px 28px 34px; display:flex; flex-direction:column; gap:20px; flex:1; max-width:1180px; margin:0 auto; width:100%; }

.acm-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none; transition:all .2s; border:none; cursor:pointer; }
.acm-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; box-shadow:0 4px 14px rgba(254,95,4,.25); }
.acm-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(254,95,4,.35); color:#fff; }
.acm-btn-secondary { background:#f1f5f9; color:#334155; }
.acm-btn-secondary:hover { background:#e2e8f0; }
.acm-btn-outline { background:#fff; border:1px solid #cbd5e1; color:#334155; }
.acm-btn-outline:hover { border-color:#fe5f04; color:#fe5f04; }
.acm-btn-danger { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }
.acm-btn-danger:hover { background:#fca5a5; }

.zh-card { background:#fff; border-radius:12px; border:1px solid #e2e8f0; padding:24px; box-shadow:0 1px 3px rgba(0,0,0,0.03); }
.zh-form-group { margin-bottom:18px; }
.zh-label { display:block; font-size:13px; font-weight:700; color:#334155; margin-bottom:6px; }
.zh-input { width:100%; padding:10px 14px; border:1px solid #cbd5e1; border-radius:8px; font-size:13.5px; color:#0f172a; outline:none; transition:border-color .2s; font-family:inherit; }
.zh-input:focus { border-color:#fe5f04; box-shadow:0 0 0 3px rgba(254,95,4,0.12); }
.zh-help { font-size:12px; color:#64748b; margin-top:5px; }

.zh-term-item { padding:14px; border-radius:8px; background:#f8fafc; border:1px solid #f1f5f9; margin-bottom:10px; }
.zh-term-title { font-weight:800; font-size:13px; color:#0f172a; display:flex; align-items:center; gap:8px; }
.zh-term-desc { font-size:12.5px; color:#475569; margin-top:4px; line-height:1.5; }
</style>
@endpush

@section('content')
<div class="zh-page">
    <div class="zh-topbar">
        <div>
            <div class="zh-title">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fe5f04" stroke-width="2.2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                Zoho Books API & OAuth Settings
            </div>
            <div class="zh-breadcrumbs">
                <a href="{{ route('accounts.dashboard') }}">Accounts</a>
                <span>/</span>
                <a href="{{ route('accounts.zoho.index') }}">Zoho Books</a>
                <span>/</span>
                <span>Settings</span>
            </div>
        </div>
        <div class="zh-actions">
            <a href="{{ route('accounts.zoho.index') }}" class="acm-btn acm-btn-secondary">
                &larr; Back to Migration Hub
            </a>
            @if($integration->is_connected)
                <button onclick="testConnection()" class="acm-btn acm-btn-outline">Test Connection</button>
                <form action="{{ route('accounts.zoho.disconnect') }}" method="POST" onsubmit="return confirm('Are you sure you want to disconnect Zoho Books?');" style="margin:0;">
                    @csrf
                    <button type="submit" class="acm-btn acm-btn-danger">Disconnect</button>
                </form>
            @endif
        </div>
    </div>

    <div class="zh-body">
        @if(session('success'))
            <div style="background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; padding:14px 18px; border-radius:10px; font-size:13.5px; font-weight:600;">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error') || $integration->last_error)
            <div style="background:#fef2f2; border:1px solid #fecaca; color:#991b1b; padding:14px 18px; border-radius:10px; font-size:13.5px; font-weight:600;">
                {{ session('error') ?: $integration->last_error }}
            </div>
        @endif

        <!-- Connection State Card -->
        <div class="zh-card" style="border-left:4px solid {{ $integration->is_connected ? '#16a34a' : '#ea580c' }};">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;">
                <div>
                    <h3 style="font-size:16px; font-weight:800; color:#0f172a; margin:0;">
                        Current Status:
                        @if($integration->is_connected)
                            <span style="color:#16a34a;">Connected with Zoho Books</span>
                        @else
                            <span style="color:#dc2626;">Not Connected</span>
                        @endif
                    </h3>
                    <div style="font-size:13px; color:#64748b; margin-top:4px;">
                        @if($integration->is_connected)
                            Connected to Organization: <strong>{{ $integration->organization_name ?: 'Default' }}</strong> (ID: {{ $integration->organization_id }})
                            &bull; Token valid until: {{ $integration->token_expires_at ? $integration->token_expires_at->format('d M Y, h:i A') : 'N/A' }}
                        @else
                            Configure your Zoho Developer credentials below and click <strong>"Authorize with Zoho"</strong>.
                        @endif
                    </div>
                </div>

                @if($integration->client_id && $integration->client_secret)
                    <div>
                        <a href="{{ route('accounts.zoho.oauth.redirect') }}" class="acm-btn acm-btn-primary" style="padding:10px 20px; font-size:13.5px;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                            {{ $integration->is_connected ? 'Re-Authorize with Zoho' : 'Authorize with Zoho' }}
                        </a>
                    </div>
                @endif
            </div>

            <!-- Organization Selector if multiple orgs exist -->
            @if(!empty($integration->organizations_data) && count($integration->organizations_data) > 0)
                <div style="margin-top:18px; padding-top:16px; border-top:1px solid #f1f5f9;">
                    <form action="{{ route('accounts.zoho.select-organization') }}" method="POST" style="display:flex; align-items:flex-end; gap:12px; flex-wrap:wrap;">
                        @csrf
                        <div style="flex:1; min-width:260px;">
                            <label class="zh-label">Select Active Zoho Books Organization:</label>
                            <select name="organization_id" class="zh-input">
                                @foreach($integration->organizations_data as $org)
                                    <option value="{{ $org['organization_id'] }}" {{ $integration->organization_id == $org['organization_id'] ? 'selected' : '' }}>
                                        {{ $org['name'] }} (ID: {{ $org['organization_id'] }} - {{ $org['currency_code'] ?? 'INR' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="acm-btn acm-btn-outline" style="height:42px;">Switch Organization</button>
                    </form>
                </div>
            @endif
        </div>

        <div style="display:grid; grid-template-columns:1.2fr 0.8fr; gap:20px; align-items:flex-start;">
            <!-- OAuth Form -->
            <div class="zh-card">
                <h3 style="font-size:16px; font-weight:800; color:#0f172a; margin-top:0; margin-bottom:16px;">1. Zoho Developer Console Credentials</h3>

                <form action="{{ route('accounts.zoho.settings.update') }}" method="POST">
                    @csrf

                    <div class="zh-form-group">
                        <label class="zh-label">Zoho Data Center Region *</label>
                        <select name="region" class="zh-input" onchange="updateAccountsUrl(this.value)">
                            @foreach($regions as $key => $reg)
                                <option value="{{ $key }}" {{ ($integration->region ?? 'in') === $key ? 'selected' : '' }}>
                                    {{ $reg['label'] }}
                                </option>
                            @endforeach
                        </select>
                        <div class="zh-help">Select the region where your Zoho Books account is hosted (most Indian businesses use <strong>India .in</strong>).</div>
                    </div>

                    <div class="zh-form-group">
                        <label class="zh-label">Client ID *</label>
                        <input type="text" name="client_id" class="zh-input" value="{{ old('client_id', $integration->client_id) }}" placeholder="e.g. 1000.XXXXXXXXXXXXXXXXXXXXXXXX" required>
                        <div class="zh-help">Generated in Zoho Developer Console (Client Type: <em>Server-based Applications</em>).</div>
                    </div>

                    <div class="zh-form-group">
                        <label class="zh-label">Client Secret *</label>
                        <input type="password" name="client_secret" class="zh-input" value="{{ old('client_secret', $integration->client_secret) }}" placeholder="e.g. xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" required>
                        <div class="zh-help">Confidential Client Secret from your registered Zoho app.</div>
                    </div>

                    <div class="zh-form-group">
                        <label class="zh-label">Authorized Redirect URI</label>
                        <div style="display:flex; gap:8px;">
                            <input type="text" id="redirectUriInput" name="redirect_uri" class="zh-input" value="{{ old('redirect_uri', $integration->redirect_uri ?: url('/accounts/zoho/callback')) }}" readonly style="background:#f8fafc;">
                            <button type="button" onclick="copyRedirectUri()" class="acm-btn acm-btn-outline" style="white-space:nowrap;">Copy</button>
                        </div>
                        <div class="zh-help">Copy and paste this exact Redirect URI into your Zoho Developer Console application settings.</div>
                    </div>

                    <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:24px;">
                        <button type="submit" class="acm-btn acm-btn-primary">Save Credentials</button>
                    </div>
                </form>

                <hr style="margin:26px 0; border:0; border-top:1px solid #f1f5f9;">

                <!-- Self Client Option -->
                <h4 style="font-size:14.5px; font-weight:800; color:#0f172a; margin-bottom:12px;">Alternative: Manual Refresh Token (Self-Client)</h4>
                <div style="font-size:12.5px; color:#64748b; margin-bottom:14px; line-height:1.5;">
                    If you created a "Self Client" in Zoho Developer Console and generated a Refresh Token, you can paste it directly here:
                </div>
                <form action="{{ route('accounts.zoho.manual-token') }}" method="POST">
                    @csrf
                    <div class="zh-form-group">
                        <label class="zh-label">Zoho Refresh Token</label>
                        <textarea name="refresh_token" rows="2" class="zh-input" placeholder="e.g. 1000.xxxxxxxxx.xxxxxxxxx" required>{{ old('refresh_token', $integration->refresh_token) }}</textarea>
                    </div>
                    <button type="submit" class="acm-btn acm-btn-outline">Save & Verify Token</button>
                </form>
            </div>

            <!-- Zoho OAuth Document & Terminologies Guide -->
            <div class="zh-card">
                <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px;">
                    <div style="width:28px; height:28px; border-radius:6px; background:#e0f2fe; color:#0284c7; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:14px;">i</div>
                    <h3 style="font-size:15px; font-weight:800; color:#0f172a; margin:0;">Zoho Books OAuth Terminology</h3>
                </div>
                <div style="font-size:12.5px; color:#64748b; margin-bottom:14px;">
                    Summary of <a href="https://www.zoho.com/books/api/v3/oauth/#terminologies" target="_blank" style="color:#fe5f04; font-weight:700;">Zoho Books OAuth v3 Document</a>:
                </div>

                <div class="zh-term-item">
                    <div class="zh-term-title">
                        <span style="color:#fe5f04;">●</span> Client ID & Client Secret
                    </div>
                    <div class="zh-term-desc">
                        Zoho Developer Console-la create panra application credentials. Client ID is the public identifier and Client Secret is the secure private key for token exchange.
                    </div>
                </div>

                <div class="zh-term-item">
                    <div class="zh-term-title">
                        <span style="color:#0284c7;">●</span> Redirect URI (Callback URL)
                    </div>
                    <div class="zh-term-desc">
                        Zoho authorization mudinjudha odane user-a thirumba redirect panra endpoint. Zoho code-ah indha URL-ku query param-a anupum.
                    </div>
                </div>

                <div class="zh-term-item">
                    <div class="zh-term-title">
                        <span style="color:#16a34a;">●</span> Authorization Code (Grant Token)
                    </div>
                    <div class="zh-term-desc">
                        User "Accept" kudutha odane Zoho kudukura short-lived temporary token. Indha software adhoda Access Token & Refresh Token-ah exchange pannum.
                    </div>
                </div>

                <div class="zh-term-item">
                    <div class="zh-term-title">
                        <span style="color:#9333ea;">●</span> Access Token & Refresh Token
                    </div>
                    <div class="zh-term-desc">
                        <strong>Access Token:</strong> 1 hour valid. API calls-ku Authorization header-la <code>Zoho-oauthtoken ...</code> poga venum.<br>
                        <strong>Refresh Token:</strong> Permanent token. Access token expire aanalum software automatically pudhu token generate pannikollum.
                    </div>
                </div>

                <div class="zh-term-item">
                    <div class="zh-term-title">
                        <span style="color:#ea580c;">●</span> Organization ID
                    </div>
                    <div class="zh-term-desc">
                        Zoho Books-la oru account-ku multiple company/organization irukalam. Invoices and Expenses fetch panna mandatory-a <code>organization_id</code> query param venum.
                    </div>
                </div>

                <div class="zh-term-item">
                    <div class="zh-term-title">
                        <span style="color:#0d9488;">●</span> Required Scopes
                    </div>
                    <div class="zh-term-desc">
                        <code>ZohoBooks.invoices.all</code>, <code>ZohoBooks.expenses.all</code>, <code>ZohoBooks.settings.READ</code>, <code>ZohoBooks.contacts.READ</code>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyRedirectUri() {
    const input = document.getElementById('redirectUriInput');
    input.select();
    navigator.clipboard.writeText(input.value);
    alert('Redirect URI copied to clipboard!');
}

function testConnection() {
    fetch('{{ route("accounts.zoho.test-connection") }}')
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if(data.success) {
                location.reload();
            }
        })
        .catch(e => alert('Error testing connection: ' + e));
}
</script>
@endsection
