@php
    $editing = isset($role);
@endphp

@csrf
@if($editing)
    @method('PUT')
@endif

<div class="auth-form-grid">
    <div class="auth-form-main">
        <div class="auth-panel">
            <div class="auth-panel-title">Role Details</div>

            <div class="auth-field">
                <label>Role Key</label>
                <input
                    type="text"
                    name="name"
                    value="{{ old('name', $role->name ?? '') }}"
                    placeholder="example: sales_manager"
                    {{ $editing ? 'readonly' : '' }}
                >
                @error('name')<div class="auth-error">{{ $message }}</div>@enderror
            </div>

            <div class="auth-field">
                <label>Display Name</label>
                <input type="text" name="display_name" value="{{ old('display_name', $role->display_name ?? '') }}" placeholder="Sales Manager">
                @error('display_name')<div class="auth-error">{{ $message }}</div>@enderror
            </div>

            <div class="auth-field">
                <label>Department</label>
                <select name="department_id">
                    <option value="">Select Department</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) old('department_id', $role->department_id ?? '') === (string) $department->id)>
                            {{ $department->name }}
                        </option>
                    @endforeach
                </select>
                @error('department_id')<div class="auth-error">{{ $message }}</div>@enderror
            </div>

            <div class="auth-field">
                <label>Description</label>
                <textarea name="description" rows="4" placeholder="Short description for this role">{{ old('description', $role->description ?? '') }}</textarea>
                @error('description')<div class="auth-error">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    @if(!$editing)
        <div class="auth-panel">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <div class="auth-panel-title" style="margin-bottom:0;">Assign Permissions</div>
                <div style="display:flex; gap:8px;">
                    <button type="button" class="auth-btn" style="padding:6px 10px; font-size:11px; cursor:pointer;" onclick="toggleAllPermissions(true)">Select All</button>
                    <button type="button" class="auth-btn" style="padding:6px 10px; font-size:11px; cursor:pointer;" onclick="toggleAllPermissions(false)">Clear All</button>
                </div>
            </div>
            <div class="auth-perm-groups">
                @forelse($permissions as $module => $items)
                    <div class="auth-perm-group" style="margin-bottom:12px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                            <div class="auth-perm-head" style="margin-bottom:0;">{{ strtoupper($module) }}</div>
                            <div style="display:flex; gap:6px;">
                                <button type="button" style="border:none; background:none; color:#2563eb; font-size:10px; font-weight:700; cursor:pointer; padding:0;" onclick="toggleModulePermissions('{{ $module }}', true)">All</button>
                                <span style="color:#d1d5db; font-size:10px;">|</span>
                                <button type="button" style="border:none; background:none; color:#2563eb; font-size:10px; font-weight:700; cursor:pointer; padding:0;" onclick="toggleModulePermissions('{{ $module }}', false)">Clear</button>
                            </div>
                        </div>
                        <div class="auth-perm-list">
                            @foreach($items as $permission)
                                <label class="auth-check">
                                    <input type="checkbox"
                                           name="permissions[]"
                                           value="{{ $permission->name }}"
                                           data-permission-checkbox="true"
                                           data-module-name="{{ $module }}"
                                           @checked(in_array($permission->name, old('permissions', [])))>
                                    <span>
                                        <strong>{{ $permission->display_name ?: ucfirst(str_replace(['.', '_'], ' ', $permission->name)) }}</strong>
                                        <small>{{ $permission->description ?: $permission->name }}</small>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="auth-muted">No permissions found. Create permissions first.</div>
                @endforelse
            </div>
            @error('permissions')<div class="auth-error">{{ $message }}</div>@enderror
            @error('permissions.*')<div class="auth-error">{{ $message }}</div>@enderror
        </div>
    @endif
</div>

@if(!$editing)
    @push('scripts')
    <script>
    function toggleAllPermissions(checked) {
        document.querySelectorAll('[data-permission-checkbox="true"]').forEach(function (checkbox) {
            checkbox.checked = checked;
        });
    }

    function toggleModulePermissions(moduleName, checked) {
        document.querySelectorAll('[data-module-name="' + moduleName + '"]').forEach(function (checkbox) {
            checkbox.checked = checked;
        });
    }
    </script>
    @endpush
@endif

<div class="auth-form-actions">
    <a href="{{ route('auth.roles.index') }}" class="auth-btn">Cancel</a>
    <button type="submit" class="auth-btn auth-btn-primary">{{ $editing ? 'Update Role' : 'Create Role' }}</button>
</div>
