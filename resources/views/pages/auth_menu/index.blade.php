@extends('layouts.app')

@section('title', 'Authentication - myAgenci.ai')

@push('styles')
<style>
.authdash-page {
    min-height: 100%;
    padding: 28px;
    background:
        radial-gradient(circle at top left, rgba(254, 95, 4, 0.08), transparent 28%),
        linear-gradient(180deg, #f8f7f5 0%, #f2f4f7 100%);
}
.authdash-hero {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 20px;
    margin-bottom: 22px;
    padding: 26px 28px;
    border: 1px solid #e5e7eb;
    border-radius: 20px;
    background: linear-gradient(135deg, #fffaf6 0%, #ffffff 55%, #f7f9fc 100%);
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
}
.authdash-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 10px;
    border-radius: 999px;
    background: #fff1e8;
    color: #c2410c;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .6px;
    text-transform: uppercase;
    margin-bottom: 12px;
}
.authdash-title {
    margin: 0;
    font-size: 28px;
    font-weight: 800;
    line-height: 1.1;
    color: #111827;
}
.authdash-sub {
    margin: 10px 0 0;
    max-width: 620px;
    font-size: 14px;
    line-height: 1.7;
    color: #6b7280;
}
.authdash-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}
.authdash-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 12px;
    border-radius: 14px;
    background: #ffffff;
    border: 1px solid #ece7e3;
    color: #4b5563;
    font-size: 12px;
    font-weight: 700;
}
.authdash-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 18px;
}
.authdash-card {
    position: relative;
    overflow: hidden;
    display: block;
    padding: 22px;
    border-radius: 18px;
    border: 1px solid #e5e7eb;
    background: #ffffff;
    color: inherit;
    text-decoration: none;
    transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
}
.authdash-card:hover {
    transform: translateY(-3px);
    border-color: #fed7aa;
    box-shadow: 0 16px 36px rgba(254, 95, 4, 0.10);
}
.authdash-card::after {
    content: '';
    position: absolute;
    inset: auto -24px -24px auto;
    width: 92px;
    height: 92px;
    border-radius: 50%;
    opacity: .16;
}
.authdash-card.companies::after { background: #f59e0b; }
.authdash-card.users::after { background: #f97316; }
.authdash-card.roles::after { background: #ec4899; }
.authdash-card.permissions::after { background: #06b6d4; }
.authdash-card.mapping::after { background: #22c55e; }
.authdash-icon {
    width: 52px;
    height: 52px;
    border-radius: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
}
.authdash-icon svg {
    width: 24px;
    height: 24px;
}
.authdash-icon.companies {
    background: linear-gradient(135deg, #fff7ed, #fef3c7);
    color: #b45309;
}
.authdash-icon.users {
    background: linear-gradient(135deg, #fff7ed, #ffedd5);
    color: #ea580c;
}
.authdash-icon.roles {
    background: linear-gradient(135deg, #fdf2f8, #fce7f3);
    color: #db2777;
}
.authdash-icon.permissions {
    background: linear-gradient(135deg, #ecfeff, #cffafe);
    color: #0891b2;
}
.authdash-icon.mapping {
    background: linear-gradient(135deg, #ecfdf5, #dcfce7);
    color: #15803d;
}
.authdash-card-title {
    font-size: 17px;
    font-weight: 800;
    color: #111827;
    margin: 0 0 8px;
}
.authdash-card-text {
    margin: 0;
    font-size: 13px;
    line-height: 1.7;
    color: #6b7280;
    max-width: 270px;
}
.authdash-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-top: 16px;
    font-size: 12px;
    font-weight: 800;
    color: #111827;
}
@media (max-width: 768px) {
    .authdash-page {
        padding: 18px;
    }
    .authdash-hero {
        padding: 20px;
        flex-direction: column;
        align-items: flex-start;
    }
    .authdash-title {
        font-size: 24px;
    }
}
</style>
@endpush

@section('content')
<div class="authdash-page">
    <div class="authdash-hero">
        <div>
            <div class="authdash-eyebrow">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
                Access Control
            </div>
            <h2 class="authdash-title">Authentication Management</h2>
            <p class="authdash-sub">Control companies, users, roles, and permissions from one place with a cleaner access-management workflow.</p>
        </div>

        <div class="authdash-badges">
            <div class="authdash-badge">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                User Access
            </div>
            <div class="authdash-badge">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M9 12l2 2 4-4"/>
                    <path d="M21 12c0 4.97-4.03 9-9 9S3 16.97 3 12 7.03 3 12 3s9 4.03 9 9z"/>
                </svg>
                Role Mapping
            </div>
            <div class="authdash-badge">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M12 1v22"/>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14.5a3.5 3.5 0 0 1 0 7H6"/>
                </svg>
                Permission Control
            </div>
        </div>
    </div>

    <div class="authdash-grid">
        @if(auth()->user()->isSystemAdmin())
        <a href="/companies" class="authdash-card companies">
            <div class="authdash-icon companies">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M3 21h18"/>
                    <path d="M5 21V7l8-4v18"/>
                    <path d="M19 21V11l-6-3"/>
                    <path d="M9 9h.01M9 13h.01M9 17h.01M13 13h.01M13 17h.01"/>
                </svg>
            </div>
            <h4 class="authdash-card-title">Companies</h4>
            <p class="authdash-card-text">Manage company accounts, account limits, Facebook credentials, and organization-level status.</p>
            <span class="authdash-link">
                Open Companies
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>
        @endif

        @can('users.view')
        <a href="{{ route('users.index') }}" class="authdash-card users">
            <div class="authdash-icon users">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <h4 class="authdash-card-title">Users</h4>
            <p class="authdash-card-text">Create users, update profile access, assign roles, and control active or inactive login status.</p>
            <span class="authdash-link">
                Open Users
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>
        @endcan

        @can('roles.view')
        <a href="{{ route('auth.roles.index') }}" class="authdash-card roles">
            <div class="authdash-icon roles">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    <path d="M9 12l2 2 4-4"/>
                </svg>
            </div>
            <h4 class="authdash-card-title">Roles</h4>
            <p class="authdash-card-text">Create business roles like Developer, BDE, or Admin and map the right permission set to each one.</p>
            <span class="authdash-link">
                Open Roles
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>
        @endcan

        @can('permissions.view')
        <a href="{{ route('auth.permissions.index') }}" class="authdash-card permissions">
            <div class="authdash-icon permissions">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M9 12l2 2 4-4"/>
                    <path d="M21 12c0 4.97-4.03 9-9 9S3 16.97 3 12 7.03 3 12 3s9 4.03 9 9z"/>
                </svg>
            </div>
            <h4 class="authdash-card-title">Permissions</h4>
            <p class="authdash-card-text">Define module-wise actions, create bulk permissions, and extend them with custom actions when required.</p>
            <span class="authdash-link">
                Open Permissions
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>
        @endcan

        @can('roles.view')
        <a href="{{ route('auth.role-mappings.index') }}" class="authdash-card mapping">
            <div class="authdash-icon mapping">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M12 3v18"/>
                    <path d="M6 8h12"/>
                    <path d="M8 16h8"/>
                    <circle cx="12" cy="8" r="3"/>
                    <circle cx="8" cy="16" r="2"/>
                    <circle cx="16" cy="16" r="2"/>
                </svg>
            </div>
            <h4 class="authdash-card-title">Role Mapping</h4>
            <p class="authdash-card-text">Set role-wise data scope for company admins, team leads, and employees.</p>
            <span class="authdash-link">
                Open Role Mapping
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>
        @endcan

        @can('users.view')
        <a href="{{ route('auth.user-mappings.index') }}" class="authdash-card mapping">
            <div class="authdash-icon mapping">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    <path d="M13 12h5"/>
                </svg>
            </div>
            <h4 class="authdash-card-title">User Mapping</h4>
            <p class="authdash-card-text">Map employees under TLs or managers for team-wise lead visibility.</p>
            <span class="authdash-link">
                Open User Mapping
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>
        @endcan

        @can('production_mapping.menuview')
        <a href="{{ route('auth.production-mappings.index') }}" class="authdash-card mapping">
            <div class="authdash-icon mapping">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M3 7h18"/>
                    <path d="M6 12h12"/>
                    <path d="M9 17h6"/>
                    <path d="M5 5h14v14H5z"/>
                </svg>
            </div>
            <h4 class="authdash-card-title">Production Mapping</h4>
            <p class="authdash-card-text">Plan department-wise production ownership using product master department mappings as the base.</p>
            <span class="authdash-link">
                Open Production Mapping
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>
        @endcan
    </div>
</div>
@endsection
