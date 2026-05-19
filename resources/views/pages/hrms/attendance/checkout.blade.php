@extends('layouts.app')

@section('title', 'Checkout Attendance')

@push('styles')
<style>
.att-page{display:flex;flex-direction:column;min-height:100%;background:#f4f5f7;font-family:var(--font-family, 'Inter', sans-serif)}
.att-topbar{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:0 28px;min-height:60px;background:#fff;border-bottom:1px solid #e1dee3}
.att-title{font-size:18px;font-weight:800;color:#121212}
.att-breadcrumb{margin-top:2px;color:#9e9e9e;font-size:12px}
.att-body{padding:22px 28px 34px}
.att-card{max-width:860px;margin:0 auto;background:#fff;border:1px solid #e1dee3;border-radius:16px;overflow:hidden}
.att-card-head{padding:18px 22px;border-bottom:1px solid #f0eef2}
.att-card-title{font-size:16px;font-weight:800;color:#121212}
.att-card-sub{margin-top:4px;color:#9e9e9e;font-size:12px}
.att-card-body{padding:22px}
.att-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.att-field{display:flex;flex-direction:column;gap:7px}
.att-field.full{grid-column:1 / -1}
.att-label{font-size:13px;font-weight:700;color:#444}
.att-req{color:#fe5f04}
.att-input,.att-select,.att-textarea{width:100%;border:1px solid #e1dee3;border-radius:10px;padding:11px 12px;background:#fff;color:#20222a;font-size:14px;font-family:inherit;outline:none}
.att-input[readonly]{background:#fafafa;color:#5b5f69}
.att-textarea{min-height:110px;resize:vertical}
.att-input:focus,.att-select:focus,.att-textarea:focus{border-color:#fe5f04;box-shadow:0 0 0 3px rgba(254,95,4,.1)}
.att-error{font-size:12px;color:#dc2626}
.att-help{font-size:12px;color:#8a8a97}
.att-help.is-warning{color:#c2410c}
.att-foot{padding:18px 22px;border-top:1px solid #f0eef2;display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap}
.att-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:9px 16px;border-radius:10px;border:1px solid transparent;background:#fff;color:#121212;text-decoration:none;font-size:13px;font-weight:700;cursor:pointer}
.att-btn-primary{background:linear-gradient(135deg,#fe5f04,#ff7c30);border-color:#fe5f04;color:#fff}
.att-btn-ghost{background:#fff;color:#121212;border-color:#e1dee3}
.att-alert{max-width:860px;margin:0 auto 16px;padding:12px 14px;border-radius:12px;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;font-size:13px}
@media (max-width: 760px){
    .att-topbar{padding:16px 20px;align-items:flex-start;flex-direction:column}
    .att-body{padding:16px 20px 24px}
    .att-grid{grid-template-columns:1fr}
    .att-field.full{grid-column:auto}
}
</style>
@endpush

@section('content')
<div class="att-page">
    <div class="att-topbar">
        <div>
            <div class="att-title">Checkout Attendance</div>
            <div class="att-breadcrumb">HRMS > Attendance > Checkout</div>
        </div>
        <div>
            <a href="{{ route('attendance.index') }}" class="att-btn att-btn-ghost">Back</a>
        </div>
    </div>

    <div class="att-body">
        @if($errors->any())
            <div class="att-alert">Please fix the highlighted fields and submit again.</div>
        @endif

        <form method="POST" action="{{ route('attendance.checkout.store') }}" class="att-card">
            @csrf
            <div class="att-card-head">
                <div class="att-card-title">Manual HR Checkout Entry</div>
                <div class="att-card-sub">Select employee or intern, choose date, and enter only the out time. Existing login time will load automatically.</div>
            </div>
            <div class="att-card-body">
                <div class="att-grid">
                    <div class="att-field full">
                        <label class="att-label">Employee / Intern <span class="att-req">*</span></label>
                        <select name="attendee_key" class="att-select" id="attendee_key" required>
                            <option value="">Select attendee</option>
                            @foreach($attendees as $attendee)
                                <option value="{{ $attendee['select_key'] }}" @selected(old('attendee_key') == $attendee['select_key'])>
                                    {{ strtoupper($attendee['attendee_type']) }} - {{ $attendee['name'] }}{{ $attendee['display_id'] ? ' - ' . $attendee['display_id'] : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('attendee_key')<div class="att-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="att-field">
                        <label class="att-label">Date <span class="att-req">*</span></label>
                        <input type="date" name="attendance_date" id="attendance_date" class="att-input" value="{{ old('attendance_date', now()->toDateString()) }}" required>
                        @error('attendance_date')<div class="att-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="att-field">
                        <label class="att-label">In Time</label>
                        <input type="time" id="login_time" class="att-input" value="{{ old('login_time') }}" readonly>
                        <div class="att-help" id="login_time_help">Choose attendee and date to load the existing check-in time.</div>
                    </div>

                    <div class="att-field">
                        <label class="att-label">Out Time <span class="att-req">*</span></label>
                        <input type="time" name="logout_time" id="logout_time" class="att-input" value="{{ old('logout_time') }}" required>
                        @error('logout_time')<div class="att-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="att-field full">
                        <label class="att-label">HR Input / Remarks</label>
                        <textarea name="remarks" class="att-textarea" placeholder="Optional HR notes">{{ old('remarks') }}</textarea>
                        @error('remarks')<div class="att-error">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
            <div class="att-foot">
                <a href="{{ route('attendance.index') }}" class="att-btn att-btn-ghost">Cancel</a>
                <button type="submit" class="att-btn att-btn-primary">Save Checkout</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const attendeeField = document.getElementById('attendee_key');
    const dateField = document.getElementById('attendance_date');
    const loginField = document.getElementById('login_time');
    const logoutField = document.getElementById('logout_time');
    const helpField = document.getElementById('login_time_help');
    let activeLookup = 0;

    const resetAttendanceState = function (message, warning) {
        loginField.value = '';
        logoutField.value = '';
        helpField.textContent = message;
        helpField.classList.toggle('is-warning', Boolean(warning));
    };

    const lookupAttendance = function () {
        const attendeeKey = attendeeField.value;
        const attendanceDate = dateField.value;

        if (!attendeeKey || !attendanceDate) {
            resetAttendanceState('Choose attendee and date to load the existing check-in time.', false);
            return;
        }

        const lookupId = ++activeLookup;
        const url = new URL(@json(route('attendance.lookup')));
        url.searchParams.set('attendee_key', attendeeKey);
        url.searchParams.set('attendance_date', attendanceDate);

        fetch(url.toString(), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Lookup failed');
                }

                return response.json();
            })
            .then(function (data) {
                if (lookupId !== activeLookup) {
                    return;
                }

                if (!data.found || !data.login_time) {
                    resetAttendanceState('No check-in record found for the selected attendee and date.', true);
                    return;
                }

                loginField.value = data.login_time;
                logoutField.value = data.logout_time || '';
                helpField.textContent = data.logout_time
                    ? 'Existing check-in and checkout times loaded for this attendee and date.'
                    : 'Existing check-in time loaded. Add the out time and save checkout.';
                helpField.classList.remove('is-warning');
            })
            .catch(function () {
                if (lookupId !== activeLookup) {
                    return;
                }

                resetAttendanceState('Unable to load attendance timing right now.', true);
            });
    };

    attendeeField.addEventListener('change', lookupAttendance);
    dateField.addEventListener('change', lookupAttendance);

    if (attendeeField.value && dateField.value) {
        lookupAttendance();
    }
});
</script>
@endpush
