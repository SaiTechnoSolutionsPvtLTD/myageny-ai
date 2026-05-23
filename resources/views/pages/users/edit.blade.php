@extends('layouts.app')

@section('title', 'Edit User — ' . $user->name)

@push('styles')
@include('pages.users.form_styles')
<style>
/* Edit-specific extras */
.ufrm-current-role-note {
    font-size:11px; color:#9e9e9e; margin-top:6px;
    padding:6px 10px; background:#fafafa; border-radius:7px;
    border:1px solid #f0eef2;
}
.ufrm-current-role-note strong { color:#fe5f04; }
.ufrm-danger-zone { border-color:#fecaca !important; }
.ufrm-danger-zone .ufrm-card-head { background:#fff5f5; border-bottom-color:#fecaca; }
.ufrm-danger-zone .ufrm-card-title { color:#dc2626; }
</style>
@endpush

@section('content')
<div class="ufrm-page">

    {{-- Topbar --}}
    <div class="ufrm-topbar">
        <div>
            <div class="ufrm-page-title">Edit User</div>
            <div class="ufrm-breadcrumb">
                <a href="{{ route('users.index') }}">Users</a> ›
                <a href="{{ route('users.show', $user) }}">{{ $user->name }}</a> › Edit
            </div>
        </div>
        <div class="ufrm-topbar-right">
            <a href="{{ route('users.show', $user) }}" class="ufrm-btn ufrm-btn-outline">
                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                View Profile
            </a>
            <a href="{{ route('users.index') }}" class="ufrm-btn ufrm-btn-outline">
                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                Back
            </a>
        </div>
    </div>

    <div class="ufrm-body">

    {{-- Flash Messages --}}
    @if(session('success'))
    <div class="usr-alert usr-alert-success" style="margin-bottom:16px;padding:12px 16px;border-radius:10px;font-size:13px;display:flex;align-items:center;gap:10px;background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        {!! session('success') !!}
    </div>
    @endif

    <form method="POST" action="{{ route('users.update', $user) }}" enctype="multipart/form-data" id="editUserForm">
        @csrf @method('PUT')
        <div class="ufrm-layout">

            {{-- ── Left ── --}}
            <div style="display:flex;flex-direction:column;gap:16px;">

                {{-- Personal Info --}}
                <div class="ufrm-card">
                    <div class="ufrm-card-head">
                        <div class="ufrm-card-head-icon" style="background:#fff0e6">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#fe5f04" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </div>
                        <div>
                            <div class="ufrm-card-title">Personal Information</div>
                            <div class="ufrm-card-sub">Update name, email and phone</div>
                        </div>
                    </div>
                    <div class="ufrm-card-body">
                        <div class="ufrm-form-row">
                            <div class="ufrm-form-group">
                                <label class="ufrm-label">Full Name <span class="ufrm-required">*</span></label>
                                <div class="ufrm-input-wrap">
                                    <svg class="ufrm-input-ico" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                    <input type="text" name="name" class="ufrm-input {{ $errors->has('name') ? 'is-invalid' : '' }}"
                                           value="{{ old('name', $user->name) }}" required>
                                </div>
                                @error('name')<div class="ufrm-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="ufrm-form-group">
                                <label class="ufrm-label">Phone Number</label>
                                <div class="ufrm-input-wrap">
                                    <svg class="ufrm-input-ico" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13.6 19.79 19.79 0 0 1 1.61 5c-.01-1.1.81-2 1.92-2h3a2 2 0 0 1 2 1.72c.13 1 .38 1.98.73 2.92a2 2 0 0 1-.45 2.11L8 10.38a16 16 0 0 0 5.62 5.62l.75-.75a2 2 0 0 1 2.11-.45c.94.35 1.92.6 2.92.73A2 2 0 0 1 22 16.92z"/></svg>
                                    <input type="text" name="phone" class="ufrm-input"
                                           value="{{ old('phone', $user->phone) }}" placeholder="+91 98765 43210">
                                </div>
                            </div>
                        </div>
                        <div class="ufrm-form-group">
                            <label class="ufrm-label">Email Address <span class="ufrm-required">*</span></label>
                            <div class="ufrm-input-wrap">
                                <svg class="ufrm-input-ico" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                <input type="email" name="email" class="ufrm-input {{ $errors->has('email') ? 'is-invalid' : '' }}"
                                       value="{{ old('email', $user->email) }}" required>
                            </div>
                            @error('email')<div class="ufrm-error">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                {{-- Access & Branch --}}
                <div class="ufrm-card">
                    <div class="ufrm-card-head">
                        <div class="ufrm-card-head-icon" style="background:#eff6ff">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#2563eb" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </div>
                        <div>
                            <div class="ufrm-card-title">Access & Branch</div>
                            <div class="ufrm-card-sub">Change branch and account status</div>
                        </div>
                    </div>
                    <div class="ufrm-card-body">
                        <div class="ufrm-form-group">
                            <label class="ufrm-label">Branch</label>
                            <div class="ufrm-input-wrap">
                                <svg class="ufrm-input-ico" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                                <select name="branch_id" class="ufrm-select">
                                    <option value="">— No Branch —</option>
                                    @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id', $user->branch_id) == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                    @endforeach
                                </select>
                                <svg class="ufrm-select-caret" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                            </div>
                        </div>

                        <div class="ufrm-toggle-row">
                            <div>
                                <div class="ufrm-toggle-label">Account Active</div>
                                <div class="ufrm-toggle-sub">Controls login access</div>
                            </div>
                            <label class="ufrm-toggle">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                                <div class="ufrm-toggle-slider"></div>
                            </label>
                        </div>
                    </div>
                    <div class="ufrm-submit-area">
                        <button type="submit" class="ufrm-btn ufrm-btn-primary" style="flex:1;">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                            Save Changes
                        </button>
                        <a href="{{ route('users.show', $user) }}" class="ufrm-btn ufrm-btn-outline">Cancel</a>
                    </div>
                </div>

                {{-- Change Password --}}
                <div class="ufrm-card">
                    <div class="ufrm-card-head">
                        <div class="ufrm-card-head-icon" style="background:#fafafa">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#9e9e9e" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </div>
                        <div>
                            <div class="ufrm-card-title">Change Password</div>
                            <div class="ufrm-card-sub">Leave blank to keep current password</div>
                        </div>
                    </div>
                    <div class="ufrm-card-body">
                        <div class="ufrm-form-row">
                            <div class="ufrm-form-group">
                                <label class="ufrm-label">New Password</label>
                                <div class="ufrm-input-wrap ufrm-pw-wrap">
                                    <svg class="ufrm-input-ico" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                    <input type="password" name="password" id="passwordInput"
                                           class="ufrm-input" placeholder="Leave blank to keep current"
                                           oninput="checkStrength(this.value)">
                                    <button type="button" class="ufrm-pw-toggle" onclick="togglePw('passwordInput',this)">
                                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </button>
                                </div>
                                <div class="ufrm-pw-strength">
                                    <div class="ufrm-pw-bars">
                                        <div class="ufrm-pw-bar" id="bar1"></div><div class="ufrm-pw-bar" id="bar2"></div>
                                        <div class="ufrm-pw-bar" id="bar3"></div><div class="ufrm-pw-bar" id="bar4"></div>
                                    </div>
                                    <div class="ufrm-pw-label" id="pwLabel">Leave blank to keep current password</div>
                                </div>
                                @error('password')<div class="ufrm-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="ufrm-form-group">
                                <label class="ufrm-label">Confirm New Password</label>
                                <div class="ufrm-input-wrap ufrm-pw-wrap">
                                    <svg class="ufrm-input-ico" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                    <input type="password" name="password_confirmation" id="confirmInput"
                                           class="ufrm-input" placeholder="Repeat new password">
                                    <button type="button" class="ufrm-pw-toggle" onclick="togglePw('confirmInput',this)">
                                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- ── Right: Avatar + Role ── --}}
            <div style="display:flex;flex-direction:column;gap:16px;">

                {{-- Avatar --}}
                <div class="ufrm-card">
                    <div class="ufrm-card-head">
                        <div><div class="ufrm-card-title">Profile Photo</div><div class="ufrm-card-sub">JPG, PNG ≤ 2MB</div></div>
                    </div>
                    <div class="ufrm-card-body">
                        <div class="ufrm-avatar-area">
                            <div class="ufrm-avatar-preview" onclick="document.getElementById('avatarFileInput').click()" id="avatarPreview"
                                 style="background:{{ ['#fe5f04','#7c3aed','#2563eb','#16a34a'][$user->id % 4] }}">
                                @if($user->avatar)
                                    <img src="{{ Storage::url($user->avatar) }}" alt="{{ $user->name }}">
                                @else
                                    <span id="avatarInitial">{{ strtoupper(substr($user->name,0,1)) }}</span>
                                @endif
                                <div class="ufrm-avatar-overlay">
                                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#fff" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                                </div>
                            </div>
                            <input type="file" name="avatar" id="avatarFileInput" accept="image/*" onchange="previewAvatar(this)" style="display:none;">
                            <button type="button" class="ufrm-avatar-upload-btn" onclick="document.getElementById('avatarFileInput').click()">Change Photo</button>
                            @if($user->avatar)
                            <button type="button" class="ufrm-avatar-remove" id="removeAvatarBtn" onclick="removeAvatar()">Remove</button>
                            @else
                            <button type="button" class="ufrm-avatar-remove" id="removeAvatarBtn" style="display:none" onclick="removeAvatar()">Remove</button>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Role --}}
                <div class="ufrm-card">
                    <div class="ufrm-card-head">
                        <div class="ufrm-card-head-icon" style="background:#faf5ff">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#7c3aed" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        </div>
                        <div>
                            <div class="ufrm-card-title">Role <span style="color:#dc2626">*</span></div>
                            <div class="ufrm-card-sub">Current: <strong style="color:#fe5f04">{{ $currentRole }}</strong></div>
                        </div>
                    </div>
                    <div class="ufrm-card-body">
                        @error('role')<div class="ufrm-error" style="margin-bottom:8px">{{ $message }}</div>@enderror
                        <div class="ufrm-role-grid">
                            @php
                                $roleColors = ['super_admin'=>'#f59e0b','company_admin'=>'#dc2626','admin'=>'#ea580c','team_leader'=>'#16a34a','bde'=>'#2563eb','hr_manager'=>'#7c3aed','accounts_manager'=>'#0f766e','project_manager'=>'#be123c','staff'=>'#7c7c7c'];
                                $roleDescriptions = ['super_admin'=>'Full system access','company_admin'=>'Company super admin access','admin'=>'Branch-level admin','team_leader'=>'Team management','bde'=>'Lead management','hr_manager'=>'HR operations','accounts_manager'=>'Finance & billing','project_manager'=>'Project tracking','staff'=>'Read-only access'];
                            @endphp
                            @foreach($roles as $role)
                            @php
                                $roleKey = \Illuminate\Support\Str::contains($role->name, '__')
                                    ? \Illuminate\Support\Str::afterLast($role->name, '__')
                                    : $role->name;
                                $color = $roleColors[$roleKey] ?? '#9e9e9e';
                                $desc  = $roleDescriptions[$roleKey] ?? '';
                                $isSelected = old('role', $currentRole) === $role->name;
                            @endphp
                            <label class="ufrm-role-option {{ $isSelected ? 'selected' : '' }}" onclick="selectRole(this)">
                                <input type="radio" name="role" value="{{ $role->name }}" {{ $isSelected ? 'checked' : '' }}>
                                <div class="ufrm-role-radio"></div>
                                <div class="ufrm-role-dot" style="background:{{ $color }}"></div>
                                <div>
                                    <div class="ufrm-role-name">{{ $role->display_name ?? ucfirst(str_replace('_',' ',$role->name)) }}</div>
                                    <div class="ufrm-role-desc">{{ $desc }}</div>
                                </div>
                            </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Danger Zone --}}
                @if($user->id !== auth()->id() && !$user->isSystemAdmin() && !($user->company && $user->company->super_admin_user_id === $user->id))
                <div class="ufrm-card ufrm-danger-zone">
                    <div class="ufrm-card-head">
                        <div>
                            <div class="ufrm-card-title">Danger Zone</div>
                            <div class="ufrm-card-sub">Irreversible actions</div>
                        </div>
                    </div>
                    <div class="ufrm-card-body">
                        <button
                            type="button"
                            class="ufrm-btn"
                            style="width:100%;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;justify-content:center;"
                            onclick="confirmDeleteUser()"
                        >
                            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M9 6V4h6v2"/></svg>
                            Delete This User
                        </button>
                    </div>
                </div>
                @endif

            </div>
        </div>
    </form>

    @if($user->id !== auth()->id() && !$user->isSystemAdmin() && !($user->company && $user->company->super_admin_user_id === $user->id))
    <form
        method="POST"
        action="{{ route('users.destroy', $user) }}"
        id="deleteUserForm"
        onsubmit="return confirm('Permanently delete {{ addslashes($user->name) }}? This cannot be undone.')"
        style="display:none;"
    >
        @csrf
        @method('DELETE')
    </form>
    @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
function previewAvatar(input) {
    if (!input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        const prev = document.getElementById('avatarPreview');
        let img = prev.querySelector('img');
        if (!img) { img = document.createElement('img'); img.style.cssText='position:absolute;inset:0;width:100%;height:100%;object-fit:cover;border-radius:50%;'; prev.appendChild(img); }
        img.src = e.target.result;
        const sp = prev.querySelector('span');
        if (sp) sp.style.display = 'none';
        document.getElementById('removeAvatarBtn').style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
}
function removeAvatar() {
    document.getElementById('avatarFileInput').value = '';
    const prev = document.getElementById('avatarPreview');
    const img = prev.querySelector('img');
    if (img) img.remove();
    const sp = prev.querySelector('span');
    if (sp) sp.style.display = '';
    document.getElementById('removeAvatarBtn').style.display = 'none';
}
function togglePw(id) { const inp=document.getElementById(id); inp.type=inp.type==='password'?'text':'password'; }
function checkStrength(pw) {
    const bars=[1,2,3,4].map(i=>document.getElementById('bar'+i));
    const label=document.getElementById('pwLabel');
    let score=0;
    if(pw.length>=8) score++; if(/[A-Z]/.test(pw)) score++; if(/[0-9]/.test(pw)) score++; if(/[^A-Za-z0-9]/.test(pw)) score++;
    const labels=['','Weak','Fair','Good','Strong'];
    const colors=['','#dc2626','#f59e0b','#16a34a','#059669'];
    bars.forEach((b,i)=>{ b.style.background=i<score?colors[score]:'#f0eef2'; });
    label.textContent=pw.length===0?'Leave blank to keep current password':labels[score];
    label.style.color=pw.length===0?'#9e9e9e':colors[score];
}
function selectRole(el) {
    document.querySelectorAll('.ufrm-role-option').forEach(o=>o.classList.remove('selected'));
    el.classList.add('selected');
    el.querySelector('input[type="radio"]').checked=true;
}
function confirmDeleteUser() {
    document.getElementById('deleteUserForm')?.requestSubmit();
}
</script>
@endpush
