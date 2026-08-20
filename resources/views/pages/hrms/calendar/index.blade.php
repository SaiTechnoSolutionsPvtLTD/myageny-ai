@extends('layouts.app')

@section('title', 'HRMS Task Calendar')

@push('styles')
<style>
.hrms-calendar-page {
    min-height: 100%;
    padding: 28px;
    background:
        radial-gradient(circle at top left, rgba(254,95,4,.10), transparent 24%),
        linear-gradient(180deg, #fff7f1 0%, #f8f5f1 42%, #f4f5f7 100%);
}
.hrms-calendar-shell {
    max-width: 1440px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 22px;
}
.hrms-calendar-card {
    background: #ffffff;
    border-radius: 20px;
    border: 1px solid #efe7e0;
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.04);
    padding: 24px;
}

/* Dedicated Standalone Modal Overlay Styling */
#addHrmsTaskModal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    z-index: 99999;
    align-items: center;
    justify-content: center;
    padding: 20px;
    box-sizing: border-box;
}

#addHrmsTaskModal.show,
#addHrmsTaskModal.is-open {
    display: flex !important;
}

#addHrmsTaskModal .modal-dialog {
    width: 100%;
    max-width: 520px;
    margin: auto;
    border-radius: 20px;
    background: #ffffff;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    overflow: hidden;
    animation: hrmsModalPop 0.22s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes hrmsModalPop {
    from { opacity: 0; transform: scale(0.94) translateY(12px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}

#addHrmsTaskModal .modal-header {
    background: linear-gradient(135deg, #fe5f04, #ff8f42);
    color: #ffffff;
    padding: 18px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

#addHrmsTaskModal .modal-title {
    font-weight: 800;
    font-size: 18px;
    color: #ffffff;
    margin: 0;
}

#addHrmsTaskModal .modal-body {
    padding: 24px;
    background: #ffffff;
}

#addHrmsTaskModal .modal-footer {
    padding: 16px 24px;
    background: #f9fafb;
    border-top: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
}

#addHrmsTaskModal .form-group-item {
    margin-bottom: 18px;
}

#addHrmsTaskModal .form-label-custom {
    display: block;
    font-weight: 700;
    font-size: 13px;
    color: #374151;
    margin-bottom: 6px;
}

#addHrmsTaskModal .form-input-custom {
    width: 100%;
    padding: 10px 14px;
    border-radius: 10px;
    border: 1px solid #d1d5db;
    font-size: 14px;
    font-family: inherit;
    color: #1f2937;
    background-color: #ffffff;
    box-sizing: border-box;
    outline: none;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

#addHrmsTaskModal .form-input-custom:focus {
    border-color: #fe5f04;
    box-shadow: 0 0 0 3px rgba(254, 95, 4, 0.15);
}
</style>
@endpush

@section('content')
<div class="hrms-calendar-page">
    <div class="hrms-calendar-shell">
        
        <!-- Header & Breadcrumbs -->
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
            <div>
                <h1 style="font-size: 26px; font-weight: 900; color: #0f1923; margin: 0;">📅 HRMS Task Calendar</h1>
                <p style="font-size: 13px; color: #6b7280; margin: 4px 0 0;">Manage schedule, assign tasks date & time, set remarks and trigger email notifications.</p>
            </div>
            <div>
                <button type="button" onclick="openAddTaskModalWithDate()" style="border: none; background: linear-gradient(135deg, #fe5f04, #ff8f42); color: #ffffff; border-radius: 10px; font-weight: 800; padding: 10px 20px; box-shadow: 0 4px 12px rgba(254, 95, 4, 0.25); cursor: pointer;">
                    <i class="fas fa-plus me-1"></i> Add Task
                </button>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 12px; font-weight: 700; margin-bottom: 0;">
                {{ session('success') }}
                <button type="button" class="btn-close" onclick="this.parentElement.remove()" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 12px; font-weight: 700; margin-bottom: 0;">
                {{ session('error') }}
                <button type="button" class="btn-close" onclick="this.parentElement.remove()" aria-label="Close"></button>
            </div>
        @endif

        <!-- Calendar Panel -->
        <div class="hrms-calendar-card">
            <?php
                $calMonth = $stats['calendar_month'] ?? \Carbon\Carbon::today();
                if (is_string($calMonth)) {
                    try {
                        $calMonth = \Carbon\Carbon::parse($calMonth);
                    } catch (\Throwable $e) {
                        $calMonth = \Carbon\Carbon::today();
                    }
                }
                $prevMonth = $calMonth->copy()->subMonth()->format('Y-m');
                $nextMonth = $calMonth->copy()->addMonth()->format('Y-m');
                $hrmsTasksCollection = $stats['hrms_tasks'] ?? collect();
                $calendarHolidaysCollection = $stats['calendar_holidays'] ?? collect();
            ?>

            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; margin-bottom: 20px;">
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <a href="?calendar_month={{ $prevMonth }}&user_id={{ !empty($stats['show_all_users']) ? 'all' : ($stats['selected_user_id'] ?? auth()->id()) }}" class="btn btn-sm btn-outline-secondary" style="border-radius: 8px; font-weight: 700; padding: 6px 14px; text-decoration: none;">&laquo; Prev</a>
                    <span style="font-weight: 900; font-size: 18px; color: #fe5f04;">{{ $calMonth->format('F Y') }}</span>
                    <a href="?calendar_month={{ $nextMonth }}&user_id={{ !empty($stats['show_all_users']) ? 'all' : ($stats['selected_user_id'] ?? auth()->id()) }}" class="btn btn-sm btn-outline-secondary" style="border-radius: 8px; font-weight: 700; padding: 6px 14px; text-decoration: none;">Next &raquo;</a>
                </div>

                <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                    <!-- User Filter -->
                    <form method="GET" action="{{ route('hrms.calendar.index') }}" style="display: flex; align-items: center; gap: 6px;">
                        <input type="hidden" name="calendar_month" value="{{ $calMonth->format('Y-m') }}">
                        <label style="font-size: 13px; font-weight: 700; color: #4b5563;">User Filter:</label>
                        <select name="user_id" onchange="this.form.submit()" style="padding: 6px 12px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 13px; font-weight: 700; background: #ffffff; color: #374151; cursor: pointer; outline: none;">
                            <option value="{{ auth()->id() }}" {{ ($stats['selected_user_id'] ?? null) == auth()->id() && !($stats['show_all_users'] ?? false) ? 'selected' : '' }}>👤 My Tasks Only</option>
                            @if(auth()->user()?->isSuperAdmin() || auth()->user()?->isCompanyAdmin() || auth()->user()?->isBranchAdmin() || auth()->user()?->belongsToHrDepartment())
                                <option value="all" {{ ($stats['show_all_users'] ?? false) ? 'selected' : '' }}>👥 All Users Tasks</option>
                            @endif
                            @foreach($stats['assignable_users'] ?? [] as $u)
                                @if($u->id !== auth()->id())
                                    <option value="{{ $u->id }}" {{ ($stats['selected_user_id'] ?? null) == $u->id && !($stats['show_all_users'] ?? false) ? 'selected' : '' }}>{{ $u->name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </form>

                    <div style="font-size: 13px; font-weight: 700; color: #4b5563;">
                        Total Tasks: <span class="badge bg-primary" style="font-size: 12px; border-radius: 6px; padding: 4px 8px;">{{ $hrmsTasksCollection->count() }} Scheduled</span>
                    </div>
                </div>
            </div>

            <!-- Calendar Grid -->
            <div style="border: 1px solid #e9e1da; border-radius: 16px; overflow: hidden; background: #ffffff;">
                <!-- Header Days -->
                <div style="display: grid; grid-template-columns: repeat(7, 1fr); background: #fdfaf7; border-bottom: 1px solid #e9e1da; text-align: center; font-weight: 800; font-size: 12px; color: #7c7c7c; padding: 12px 0;">
                    <div>Sun</div>
                    <div>Mon</div>
                    <div>Tue</div>
                    <div>Wed</div>
                    <div>Thu</div>
                    <div>Fri</div>
                    <div>Sat</div>
                </div>

                <!-- Days Cells -->
                <?php
                    $startDayOfWeek = $calMonth->copy()->startOfMonth()->dayOfWeek;
                    $daysInMonth = $calMonth->daysInMonth;
                    $todayStr = \Carbon\Carbon::today()->toDateString();
                    
                    $tasksByDate = $hrmsTasksCollection->groupBy(function ($t) {
                        return $t->task_date ? \Carbon\Carbon::parse($t->task_date)->toDateString() : '';
                    });
                    $holidaysByDate = $calendarHolidaysCollection->groupBy(function ($h) {
                        return \Carbon\Carbon::parse($h->holiday_date)->toDateString();
                    });
                ?>

                <div style="display: grid; grid-template-columns: repeat(7, 1fr); background: #fdfaf7; gap: 1px;">
                    @for($i = 0; $i < $startDayOfWeek; $i++)
                        <div style="background: #faf8fb; min-height: 110px; padding: 8px;"></div>
                    @endfor

                    @for($day = 1; $day <= $daysInMonth; $day++)
                        <?php
                            $cellDate = $calMonth->copy()->day($day)->toDateString();
                            $isTodayCell = ($cellDate === $todayStr);
                            $dayTasks = $tasksByDate->get($cellDate, collect());
                            $dayHolidays = $holidaysByDate->get($cellDate, collect());
                        ?>
                        <div style="background: #ffffff; min-height: 115px; padding: 8px; border: 1px solid #f2ece6; position: relative; {{ $isTodayCell ? 'background: #fff8f3; border: 2px solid #fe5f04;' : '' }}">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                                <span style="font-weight: 800; font-size: 13px; {{ $isTodayCell ? 'color: #fe5f04; font-size: 15px;' : 'color: #333;' }}">
                                    {{ $day }}
                                </span>
                                @if($isTodayCell)
                                    <span class="badge bg-warning text-dark" style="font-size: 9px; padding: 2px 6px;">Today</span>
                                @endif
                                <button type="button" onclick="openAddTaskModalWithDate('{{ $cellDate }}')" style="border: none; background: transparent; color: #fe5f04; font-size: 14px; font-weight: 800; cursor: pointer;" title="Add Task on {{ $cellDate }}">
                                    +
                                </button>
                            </div>

                            @foreach($dayHolidays as $holiday)
                                <div style="font-size: 10px; font-weight: 700; color: #b45309; background: #fffbeb; border: 1px solid #fde68a; padding: 3px 6px; border-radius: 4px; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $holiday->name }}">
                                    🌴 {{ $holiday->name }}
                                </div>
                            @endforeach

                            @foreach($dayTasks as $t)
                                <?php $isDone = ($t->status === 'completed'); ?>
                                <div style="font-size: 11px; padding: 5px 7px; border-radius: 6px; margin-bottom: 4px; {{ $isDone ? 'background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;' : 'background: #fff7ed; color: #c2410c; border: 1px solid #ffedd5;' }}" title="{{ $t->remarks }} ({{ $t->user?->name }})">
                                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 4px;">
                                        <span style="font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-decoration: {{ $isDone ? 'line-through' : 'none' }};">
                                            ⏰ {{ $t->task_time ? date('h:i A', strtotime($t->task_time)) : '' }} {{ Str::limit($t->remarks, 18) }}
                                        </span>
                                    </div>
                                    <div style="font-size: 9px; color: #6b7280; display: flex; align-items: center; justify-content: space-between; margin-top: 3px;">
                                        <span>👤 {{ $t->user?->name ?? 'User' }}</span>
                                        <div style="display: flex; gap: 4px;">
                                            @if(!$isDone)
                                                <form method="POST" action="{{ route('hrms.tasks.complete', $t) }}" style="display: inline;" onsubmit="return confirm('Mark task as completed?');">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" style="border: none; background: none; color: #16a34a; padding: 0; font-size: 11px; cursor: pointer;" title="Complete Task">✓</button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('hrms.tasks.destroy', $t) }}" style="display: inline;" onsubmit="return confirm('Delete task?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" style="border: none; background: none; color: #dc2626; padding: 0; font-size: 11px; cursor: pointer;" title="Delete Task">✕</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endfor
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal Popup for Adding HRMS Task -->
<div id="addHrmsTaskModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">📅 Add Task to Calendar</h5>
                <button type="button" onclick="closeAddTaskModal()" aria-label="Close" style="background: transparent; border: none; color: #ffffff; font-size: 24px; font-weight: bold; cursor: pointer; line-height: 1;">&times;</button>
            </div>
            <form method="POST" action="{{ route('hrms.tasks.store') }}" onsubmit="return handleHrmsTaskSubmit(this)">
                @csrf
                <div class="modal-body">
                    
                    <div class="form-group-item">
                        <label class="form-label-custom">Task Date <span style="color: #ef4444;">*</span></label>
                        <input type="date" name="task_date" id="modalTaskDate" class="form-input-custom" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="form-group-item">
                        <label class="form-label-custom">Task Time</label>
                        <input type="time" name="task_time" class="form-input-custom" value="10:00">
                    </div>

                    <div class="form-group-item">
                        <label class="form-label-custom">Assign User / Self</label>
                        <select name="user_id" class="form-input-custom">
                            @foreach($stats['assignable_users'] ?? [] as $user)
                                <option value="{{ $user->id }}" {{ $user->id == auth()->id() ? 'selected' : '' }}>
                                    {{ $user->name }} {{ $user->id == auth()->id() ? '(Self)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group-item">
                        <label class="form-label-custom">Remarks / Description <span style="color: #ef4444;">*</span></label>
                        <textarea name="remarks" class="form-input-custom" rows="3" placeholder="Enter task details or remarks..." required style="resize: vertical;"></textarea>
                        <small style="display: block; margin-top: 6px; font-size: 12px; color: #6b7280;">Task notifications are automatically sent via email at 09:30 AM on the scheduled date.</small>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeAddTaskModal()" style="padding: 10px 18px; border-radius: 10px; border: 1px solid #d1d5db; background: #ffffff; color: #374151; font-weight: 700; cursor: pointer;">Cancel</button>
                    <button type="submit" style="padding: 10px 22px; border-radius: 10px; border: none; background: linear-gradient(135deg, #fe5f04, #ff8f42); color: #ffffff; font-weight: 800; cursor: pointer; box-shadow: 0 4px 12px rgba(254, 95, 4, 0.25);">Save Task</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openAddTaskModalWithDate(dateStr) {
    resetSaveTaskButton();
    if (dateStr) {
        var dateInput = document.getElementById('modalTaskDate');
        if (dateInput) {
            dateInput.value = dateStr;
        }
    }
    var modalEl = document.getElementById('addHrmsTaskModal');
    if (modalEl) {
        modalEl.classList.add('is-open');
    }
}

function closeAddTaskModal() {
    var modalEl = document.getElementById('addHrmsTaskModal');
    if (modalEl) {
        modalEl.classList.remove('is-open');
    }
}

function resetSaveTaskButton() {
    var btn = document.querySelector('#addHrmsTaskModal button[type="submit"]');
    if (btn) {
        btn.disabled = false;
        btn.innerHTML = 'Save Task';
        btn.style.opacity = '1';
        btn.style.cursor = 'pointer';
    }
}

function handleHrmsTaskSubmit(form) {
    var btn = form.querySelector('button[type="submit"]');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Processing...';
        btn.style.opacity = '0.75';
        btn.style.cursor = 'not-allowed';
    }
    return true;
}

document.addEventListener('DOMContentLoaded', function () {
    var modalEl = document.getElementById('addHrmsTaskModal');
    if (modalEl) {
        modalEl.addEventListener('click', function (e) {
            if (e.target === modalEl) {
                closeAddTaskModal();
            }
        });
    }
});
</script>
@endpush
