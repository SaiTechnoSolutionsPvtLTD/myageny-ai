@php
    $headerUser = auth()->user()?->loadMissing('roles', 'employeeOnboarding');
    $headerRole = $headerUser?->role_display_name ?: ($headerUser?->roles->first()?->display_name ?? $headerUser?->roles->first()?->name ?? 'User');
    $profileUrl = $headerUser?->employeeOnboarding
        ? route('employee-onboarding.show', $headerUser->employeeOnboarding)
        : null;
@endphp

<style>
.site-header {
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:16px;
    padding:18px 32px;
    border-bottom:1px solid #e1dee3;
    background:#fcfcfc;
}
.site-header__identity {
    display:flex;
    flex-direction:column;
    gap:4px;
}
.site-header__name {
    font-size:20px;
    font-weight:700;
    color:#121212;
    line-height:1.1;
}
.site-header__role {
    font-size:13px;
    color:#8b8b8b;
    font-weight:500;
}
.site-header__actions {
    display:flex;
    align-items:center;
    gap:12px;
}
.site-header__btn {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:10px 16px;
    border-radius:12px;
    border:1px solid #e1dee3;
    background:#fff;
    color:#121212;
    font-size:13px;
    font-weight:700;
    text-decoration:none;
}
.site-header__btn:hover {
    border-color:#ffb68b;
    color:#fe5f04;
}
.site-header__btn.is-disabled {
    color:#9e9e9e;
    cursor:default;
    pointer-events:none;
    background:#f7f7f7;
}
@media (max-width: 720px) {
    .site-header {
        padding:16px 20px;
        flex-direction:column;
        align-items:flex-start;
    }
}
</style>

<header class="site-header">
    <div class="site-header__identity">
        <div class="site-header__name">{{ $headerUser?->name ?: 'User' }}</div>
        <div class="site-header__role">{{ $headerRole }}</div>
    </div>
    <div class="site-header__actions">
        @if($profileUrl)
        <a href="{{ $profileUrl }}" class="site-header__btn">My Profile</a>
        @else
        <span class="site-header__btn is-disabled">My Profile</span>
        @endif
    </div>
</header>
