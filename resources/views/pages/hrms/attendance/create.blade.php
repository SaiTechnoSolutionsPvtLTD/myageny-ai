@extends('layouts.app')

@section('title', 'Check In Attendance')

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
.att-textarea{min-height:110px;resize:vertical}
.att-input:focus,.att-select:focus,.att-textarea:focus{border-color:#fe5f04;box-shadow:0 0 0 3px rgba(254,95,4,.1)}
.att-error{font-size:12px;color:#dc2626}
.att-help{font-size:12px;color:#8a8a97}
.att-help.is-warning{color:#c2410c}
.att-hidden{display:none}
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
.select2-container--default .select2-selection--single.att-select2-selection {
    height: 45px;
    border: 1px solid #e1dee3;
    border-radius: 10px;
    background: #fff;
    display: flex;
    align-items: center;
}
.select2-container--default .select2-selection--single.att-select2-selection .select2-selection__rendered {
    line-height: 43px;
    padding-left: 12px;
    padding-right: 36px;
    font-size: 14px;
    color: #20222a;
}
.select2-container--default .select2-selection--single.att-select2-selection .select2-selection__arrow {
    height: 43px;
    right: 12px;
    display: flex;
    align-items: center;
}
.select2-container--default.select2-container--focus .select2-selection--single.att-select2-selection,
.select2-container--default.select2-container--open .select2-selection--single.att-select2-selection {
    border-color: #fe5f04;
    box-shadow: 0 0 0 3px rgba(254,95,4,.1);
}
.select2-dropdown {
    border: 1px solid #e1dee3;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 12px 28px rgba(18,18,18,.08);
}
.select2-search--dropdown {
    padding: 10px;
}
.select2-search--dropdown .select2-search__field {
    border: 1px solid #e1dee3;
    border-radius: 8px;
    padding: 8px 10px;
    font-size: 13px;
    outline: none;
}
.select2-search--dropdown .select2-search__field:focus {
    border-color: #fe5f04;
}
.select2-results__option {
    font-size: 13px;
    padding: 9px 11px;
}
.select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
    background: #fe5f04;
    color: #fff;
}
</style>
@endpush

@section('content')
<div class="att-page">
    <div class="att-topbar">
        <div>
            <div class="att-title">Check In Attendance</div>
            <div class="att-breadcrumb">HRMS > Attendance > Check In</div>
        </div>
        <div>
            <a href="{{ route('attendance.index') }}" class="att-btn att-btn-ghost">Back</a>
        </div>
    </div>

    <div class="att-body">
        @if($errors->any())
            <div class="att-alert">Please fix the highlighted fields and submit again.</div>
        @endif

        <form method="POST" action="{{ route('attendance.store') }}" class="att-card">
            @csrf
            <div class="att-card-head">
                <div class="att-card-title">Manual HR Check-In Entry</div>
                <div class="att-card-sub">Select employee or intern, choose date, and record either attendance or leave. Checkout can be added separately for present entries.</div>
            </div>
            <div class="att-card-body">
                <div class="att-grid">
                    <div class="att-field full">
                        <label class="att-label">Employee / Intern <span class="att-req">*</span></label>
                        <select name="attendee_key" class="att-select select2" id="attendee_key" required>
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
                        <label class="att-label">Status <span class="att-req">*</span></label>
                        <select name="attendance_status" class="att-select" id="attendance_status" required>
                            <option value="present" @selected(old('attendance_status', request('attendance_status', 'present')) === 'present')>Present</option>
                            <option value="leave" @selected(old('attendance_status', request('attendance_status')) === 'leave')>Leave</option>
                        </select>
                        @error('attendance_status')<div class="att-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="att-field" id="leave_category_wrap">
                        <label class="att-label">Leave Type <span class="att-req">*</span></label>
                        <select name="leave_category" class="att-select" id="leave_category">
                            <option value="">Select leave type</option>
                            <option value="paid" @selected(old('leave_category') === 'paid')>Paid Leave</option>
                            <option value="lop" @selected(old('leave_category') === 'lop')>Loss of Pay</option>
                            <option value="half_day" @selected(old('leave_category') === 'half_day')>Half Day Leave</option>
                        </select>
                        @error('leave_category')<div class="att-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="att-field" id="leave_session_wrap">
                        <label class="att-label">Half Day Session <span class="att-req">*</span></label>
                        <select name="leave_session" class="att-select" id="leave_session">
                            <option value="">Select session</option>
                            <option value="first_half" @selected(old('leave_session') === 'first_half')>First Half</option>
                            <option value="second_half" @selected(old('leave_session') === 'second_half')>Second Half</option>
                        </select>
                        @error('leave_session')<div class="att-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="att-field">
                        <label class="att-label">In Time <span class="att-req" id="login_time_req">*</span></label>
                        <input type="time" name="login_time" id="login_time" class="att-input" value="{{ old('login_time') }}">
                        @error('login_time')<div class="att-error">{{ $message }}</div>@enderror
                        <div class="att-help" id="login_time_help">If a check-in already exists for the selected date, that time will appear here. Otherwise this stays blank.</div>
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
                <button type="submit" class="att-btn att-btn-primary">Save Check-In</button>
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
    const statusField = document.getElementById('attendance_status');
    const leaveCategoryWrap = document.getElementById('leave_category_wrap');
    const leaveCategoryField = document.getElementById('leave_category');
    const leaveSessionWrap = document.getElementById('leave_session_wrap');
    const leaveSessionField = document.getElementById('leave_session');
    const loginField = document.getElementById('login_time');
    const loginReq = document.getElementById('login_time_req');
    const helpField = document.getElementById('login_time_help');
    let activeLookup = 0;

    const syncStatusState = function () {
        const isLeave = statusField.value === 'leave';
        const isHalfDay = isLeave && leaveCategoryField.value === 'half_day';

        leaveCategoryWrap.classList.toggle('att-hidden', !isLeave);
        leaveCategoryField.required = isLeave;
        leaveSessionWrap.classList.toggle('att-hidden', !isHalfDay);
        leaveSessionField.required = isHalfDay;
        loginField.required = !isLeave;
        loginReq.classList.toggle('att-hidden', isLeave);

        if (!isHalfDay) {
            leaveSessionField.value = '';
        }

        if (isLeave) {
            loginField.value = '';
            helpField.textContent = isHalfDay
                ? 'Half day leave selected. Choose whether it is first half or second half.'
                : 'Leave entries do not need a check-in time.';
            helpField.classList.remove('is-warning');
            return;
        }

        helpField.textContent = 'If a check-in already exists for the selected date, that time will appear here. Otherwise this stays blank.';
    };

    const resetLoginState = function (message, warning) {
        loginField.value = '';
        helpField.textContent = message;
        helpField.classList.toggle('is-warning', Boolean(warning));
    };

    const lookupAttendance = function () {
        const attendeeKey = attendeeField.value;
        const attendanceDate = dateField.value;

        if (statusField.value === 'leave') {
            resetLoginState('Leave entries do not need a check-in time.', false);
            return;
        }

        if (!attendeeKey || !attendanceDate) {
            resetLoginState('Choose attendee and date to load any existing check-in time.', false);
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

                if (data.found && data.login_time) {
                    loginField.value = data.login_time;
                    helpField.textContent = 'Existing check-in time loaded for this attendee and date.';
                    helpField.classList.remove('is-warning');
                    return;
                }

                resetLoginState('No existing check-in time found for this attendee and date.', false);
            })
            .catch(function () {
                if (lookupId !== activeLookup) {
                    return;
                }

                resetLoginState('Unable to load existing check-in time right now.', true);
            });
    };

    if (window.jQuery && window.jQuery.fn.select2) {
        const $attendee = $('#attendee_key');
        if ($attendee.hasClass('select2-hidden-accessible')) {
            $attendee.select2('destroy');
        }
        $attendee.select2({
            placeholder: "Select attendee",
            allowClear: true,
            width: '100%'
        });
        $attendee.next('.select2-container').find('.select2-selection--single').addClass('att-select2-selection');
        $attendee.on('change.select2 change', function () {
            lookupAttendance();
        });
    }

    attendeeField.addEventListener('change', lookupAttendance);
    dateField.addEventListener('change', lookupAttendance);
    statusField.addEventListener('change', function () {
        syncStatusState();
        lookupAttendance();
    });
    leaveCategoryField.addEventListener('change', syncStatusState);

    syncStatusState();

    if (attendeeField.value && dateField.value) {
        lookupAttendance();
    }
});
</script>
@endpush
