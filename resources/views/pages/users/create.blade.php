@extends('layouts.app')

@section('title', 'Add New User')

@push('styles')
@include('pages.users.form_styles')
@endpush

@section('content')
<div class="ufrm-page">

    {{-- Topbar --}}
    <div class="ufrm-topbar">
        <div>
            <div class="ufrm-page-title">Add New User</div>
            <div class="ufrm-breadcrumb">
                <a href="{{ route('users.index') }}">Users</a> › Create
            </div>
        </div>
        <div class="ufrm-topbar-right">
            <a href="{{ route('users.index') }}" class="ufrm-btn ufrm-btn-outline">
                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                Back
            </a>
        </div>
    </div>

    <div class="ufrm-body">
    <form method="POST" action="{{ route('users.store') }}" enctype="multipart/form-data" id="createUserForm">
        @csrf
        <div class="ufrm-layout">

            {{-- ── Left: Main Form ── --}}
            <div style="display:flex;flex-direction:column;gap:16px;">

                {{-- Personal Info --}}
                <div class="ufrm-card">
                    <div class="ufrm-card-head">
                        <div class="ufrm-card-head-icon" style="background:#fff0e6">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#fe5f04" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </div>
                        <div>
                            <div class="ufrm-card-title">Personal Information</div>
                            <div class="ufrm-card-sub">Basic details about the user</div>
                        </div>
                    </div>
                    <div class="ufrm-card-body">
                        <div class="ufrm-form-row">
                            <div class="ufrm-form-group">
                                <label class="ufrm-label">Full Name <span class="ufrm-required">*</span></label>
                                <div class="ufrm-input-wrap">
                                    <svg class="ufrm-input-ico" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                    <input type="text" name="name" class="ufrm-input {{ $errors->has('name') ? 'is-invalid' : '' }}"
                                           placeholder="Vinothini S." value="{{ old('name') }}" required>
                                </div>
                                @error('name')<div class="ufrm-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="ufrm-form-group">
                                <label class="ufrm-label">Phone Number</label>
                                <div class="ufrm-input-wrap">
                                    <svg class="ufrm-input-ico" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13.6 19.79 19.79 0 0 1 1.61 5c-.01-1.1.81-2 1.92-2h3a2 2 0 0 1 2 1.72c.13 1 .38 1.98.73 2.92a2 2 0 0 1-.45 2.11L8 10.38a16 16 0 0 0 5.62 5.62l.75-.75a2 2 0 0 1 2.11-.45c.94.35 1.92.6 2.92.73A2 2 0 0 1 22 16.92z"/></svg>
                                    <input type="text" name="phone" class="ufrm-input {{ $errors->has('phone') ? 'is-invalid' : '' }}"
                                           placeholder="+91 98765 43210" value="{{ old('phone') }}">
                                </div>
                                @error('phone')<div class="ufrm-error">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="ufrm-form-group full">
                            <label class="ufrm-label">Email Address <span class="ufrm-required">*</span></label>
                            <div class="ufrm-input-wrap">
                                <svg class="ufrm-input-ico" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                <input type="email" name="email" class="ufrm-input {{ $errors->has('email') ? 'is-invalid' : '' }}"
                                       placeholder="vinothini@myagenci.ai" value="{{ old('email') }}" required>
                            </div>
                            @error('email')<div class="ufrm-error">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                {{-- Access --}}
                <div class="ufrm-card">
                    <div class="ufrm-card-head">
                        <div class="ufrm-card-head-icon" style="background:#eff6ff">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#2563eb" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </div>
                        <div>
                            <div class="ufrm-card-title">Login & Access</div>
                            <div class="ufrm-card-sub">Branch assignment and credentials</div>
                        </div>
                    </div>
                    <div class="ufrm-card-body">
                        <div class="ufrm-form-group">
                            <label class="ufrm-label">Branch</label>
                            <div class="ufrm-input-wrap">
                                <svg class="ufrm-input-ico" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                                <select name="branch_id" class="ufrm-select {{ $errors->has('branch_id') ? 'is-invalid' : '' }}">
                                    <option value="">— No Branch Assigned —</option>
                                    @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                    @endforeach
                                </select>
                                <svg class="ufrm-select-caret" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                            </div>
                            @error('branch_id')<div class="ufrm-error">{{ $message }}</div>@enderror
                        </div>

                        {{-- Additional Branches (Multi-Select) --}}
                        <div class="ufrm-form-group" style="margin-top: 15px;">
                            <label class="ufrm-label">Additional Branches (Multiple Select)</label>
                            <div style="max-height: 150px; overflow-y: auto; border: 1px solid #dbe1e8; border-radius: 12px; padding: 12px; background: #fafafa; display: grid; gap: 8px;">
                                @foreach($branches as $branch)
                                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #374151; font-weight: 500; cursor: pointer;">
                                        <input type="checkbox" name="branches[]" value="{{ $branch->id }}" {{ is_array(old('branches')) && in_array($branch->id, old('branches')) ? 'checked' : '' }} style="width: 16px; height: 16px; accent-color: #2563eb;">
                                        {{ $branch->name }}
                                    </label>
                                @endforeach
                            </div>
                            <small style="color: #6b7280; font-size: 11px; margin-top: 4px; display: block;">Select additional branches this user can access (e.g. for Branch Manager, etc.).</small>
                            @error('branches')<div class="ufrm-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="ufrm-form-row">
                            <div class="ufrm-form-group">
                                <label class="ufrm-label">Password <span class="ufrm-required">*</span></label>
                                <div class="ufrm-input-wrap ufrm-pw-wrap">
                                    <svg class="ufrm-input-ico" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                    <input type="password" name="password" id="passwordInput"
                                           class="ufrm-input {{ $errors->has('password') ? 'is-invalid' : '' }}"
                                           placeholder="Min 8 characters" oninput="checkStrength(this.value)">
                                    <button type="button" class="ufrm-pw-toggle" onclick="togglePw('passwordInput', this)">
                                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </button>
                                </div>
                                <div class="ufrm-pw-strength">
                                    <div class="ufrm-pw-bars">
                                        <div class="ufrm-pw-bar" id="bar1"></div>
                                        <div class="ufrm-pw-bar" id="bar2"></div>
                                        <div class="ufrm-pw-bar" id="bar3"></div>
                                        <div class="ufrm-pw-bar" id="bar4"></div>
                                    </div>
                                    <div class="ufrm-pw-label" id="pwLabel">Enter a password</div>
                                </div>
                                @error('password')<div class="ufrm-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="ufrm-form-group">
                                <label class="ufrm-label">Confirm Password <span class="ufrm-required">*</span></label>
                                <div class="ufrm-input-wrap ufrm-pw-wrap">
                                    <svg class="ufrm-input-ico" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                    <input type="password" name="password_confirmation" id="confirmInput"
                                           class="ufrm-input" placeholder="Repeat password">
                                    <button type="button" class="ufrm-pw-toggle" onclick="togglePw('confirmInput', this)">
                                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Active status --}}
                        <div class="ufrm-toggle-row">
                            <div>
                                <div class="ufrm-toggle-label">Account Active</div>
                                <div class="ufrm-toggle-sub">User can log in immediately</div>
                            </div>
                            <label class="ufrm-toggle">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                                <div class="ufrm-toggle-slider"></div>
                            </label>
                        </div>
                    </div>
                    <div class="ufrm-submit-area">
                        <button type="submit" class="ufrm-btn ufrm-btn-primary" style="flex:1;">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                            Create User
                        </button>
                        <a href="{{ route('users.index') }}" class="ufrm-btn ufrm-btn-outline">Cancel</a>
                    </div>
                </div>
            </div>

            {{-- ── Right: Avatar + Role ── --}}
            <div style="display:flex;flex-direction:column;gap:16px;">

                {{-- Avatar --}}
                <div class="ufrm-card">
                    <div class="ufrm-card-head">
                        <div>
                            <div class="ufrm-card-title">Profile Photo</div>
                            <div class="ufrm-card-sub">Optional · JPG, PNG ≤ 2MB</div>
                        </div>
                    </div>
                    <div class="ufrm-card-body">
                        <div class="ufrm-avatar-area">
                            <div class="ufrm-avatar-preview" onclick="document.getElementById('avatarFileInput').click()" id="avatarPreview">
                                <span id="avatarInitial">?</span>
                                <div class="ufrm-avatar-overlay">
                                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#fff" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                                </div>
                            </div>
                            <input type="file" name="avatar" id="avatarFileInput" accept="image/*" onchange="previewAvatar(this)">
                            <button type="button" class="ufrm-avatar-upload-btn" onclick="document.getElementById('avatarFileInput').click()">
                                Upload Photo
                            </button>
                            <button type="button" class="ufrm-avatar-remove" id="removeAvatarBtn" style="display:none" onclick="removeAvatar()">
                                Remove
                            </button>
                        </div>
                        @error('avatar')<div class="ufrm-error" style="text-align:center">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- Role --}}
                <div class="ufrm-card">
                    <div class="ufrm-card-head">
                        <div class="ufrm-card-head-icon" style="background:#faf5ff">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#7c3aed" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        </div>
                        <div>
                            <div class="ufrm-card-title">Assign Role <span style="color:#dc2626">*</span></div>
                            <div class="ufrm-card-sub">Controls what the user can access</div>
                        </div>
                    </div>
                    <div class="ufrm-card-body">
                        @error('role')<div class="ufrm-error" style="margin-bottom:8px">{{ $message }}</div>@enderror
                        <div class="ufrm-role-grid">
                            @php
                                $roleColors = [
                                    'super_admin'      => '#f59e0b',
                                    'company_admin'    => '#dc2626',
                                    'admin'            => '#ea580c',
                                    'team_leader'      => '#16a34a',
                                    'bde'              => '#2563eb',
                                    'hr_manager'       => '#7c3aed',
                                    'accounts_manager' => '#0f766e',
                                    'project_manager'  => '#be123c',
                                    'staff'            => '#7c7c7c',
                                ];
                                $roleDescriptions = [
                                    'super_admin'      => 'Full system access',
                                    'company_admin'    => 'Company super admin access',
                                    'admin'            => 'Branch-level admin',
                                    'team_leader'      => 'Team management',
                                    'bde'              => 'Lead management',
                                    'hr_manager'       => 'HR operations',
                                    'accounts_manager' => 'Finance & billing',
                                    'project_manager'  => 'Project tracking',
                                    'staff'            => 'Read-only access',
                                ];
                            @endphp
                            @foreach($roles as $role)
                            @php
                                $roleKey = \Illuminate\Support\Str::contains($role->name, '__')
                                    ? \Illuminate\Support\Str::afterLast($role->name, '__')
                                    : $role->name;
                                $color = $roleColors[$roleKey] ?? '#9e9e9e';
                                $desc  = $roleDescriptions[$roleKey] ?? '';
                                $isSelected = old('role') === $role->name;
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

            </div>
        </div>
    </form>
    </div>

</div>
@endsection

@push('scripts')
<script>
// ── Avatar preview ────────────────────────────────────────────────
function previewAvatar(input) {
    if (!input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        const prev = document.getElementById('avatarPreview');
        const init = document.getElementById('avatarInitial');
        init.style.display = 'none';
        let img = prev.querySelector('img');
        if (!img) { img = document.createElement('img'); prev.insertBefore(img, prev.firstChild); }
        img.src = e.target.result;
        document.getElementById('removeAvatarBtn').style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
}
function removeAvatar() {
    document.getElementById('avatarFileInput').value = '';
    const prev = document.getElementById('avatarPreview');
    const img = prev.querySelector('img');
    if (img) img.remove();
    document.getElementById('avatarInitial').style.display = '';
    document.getElementById('removeAvatarBtn').style.display = 'none';
}

// Update initial from name field
document.querySelector('[name="name"]')?.addEventListener('input', function() {
    const init = this.value.trim().charAt(0).toUpperCase() || '?';
    const el = document.getElementById('avatarInitial');
    if (el) el.textContent = init;
});

// ── Password toggle ───────────────────────────────────────────────
function togglePw(id, btn) {
    const inp = document.getElementById(id);
    inp.type = inp.type === 'password' ? 'text' : 'password';
}

// ── Password strength ─────────────────────────────────────────────
function checkStrength(pw) {
    const bars  = [1,2,3,4].map(i => document.getElementById('bar'+i));
    const label = document.getElementById('pwLabel');
    const levels = { weak:1, fair:2, good:3, strong:4 };
    let score = 0;
    if (pw.length >= 8)   score++;
    if (/[A-Z]/.test(pw)) score++;
    if (/[0-9]/.test(pw)) score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;
    const names = ['','weak','fair','good','strong'];
    const labels = ['','Weak','Fair','Good','Strong'];
    const colors = ['','#dc2626','#f59e0b','#16a34a','#059669'];
    bars.forEach((b,i) => { b.className = 'ufrm-pw-bar'; b.style.background = i < score ? colors[score] : ''; if(i < score) b.classList.add(names[score]); });
    label.textContent = pw.length === 0 ? 'Enter a password' : labels[score];
    label.style.color = pw.length === 0 ? '#9e9e9e' : colors[score];
}

// ── Role selection ────────────────────────────────────────────────
function selectRole(el) {
    document.querySelectorAll('.ufrm-role-option').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
    el.querySelector('input[type="radio"]').checked = true;
}
</script>
@endpush
