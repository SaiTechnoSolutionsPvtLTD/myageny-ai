@extends('layouts.app')

@section('title', 'Recruitment - ' . $candidate->name)

@push('styles')
    @include('pages.leads.show_page_style')
    @include('pages.hrms.recruitment.styles')
    <style>
        .rec-decision-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:12px; }
        .rec-wide-grid { display:grid; grid-template-columns:1.2fr .8fr; gap:18px; }
        .rec-status-dot { width:7px; height:7px; border-radius:50%; display:inline-block; }
        .rec-mini-actions { display:flex; gap:8px; flex-wrap:wrap; }
        .rec-danger-box { border:1px solid #fecaca; background:#fffafa; border-radius:14px; padding:14px; }
        .rec-empty-tight { padding:28px 20px; }
        .lsp-inp[type="datetime-local"] {
            background:linear-gradient(180deg,#fff7f1 0%, #fff2e8 100%);
            border-color:#f7c9ac;
            color:#c2410c;
        }
        @media (max-width: 980px) {
            .rec-wide-grid, .rec-decision-grid { grid-template-columns:1fr; }
        }
    </style>
@endpush

@section('content')
@php
    $colors = ['#fe5f04', '#7c3aed', '#2563eb', '#16a34a', '#be123c', '#0284c7', '#b45309', '#0f766e'];
    $heroColor = $colors[$candidate->id % count($colors)];
    $statusColors = [
        'shortlist' => ['bg' => '#eef2ff', 'text' => '#4338ca', 'border' => '#c7d2fe'],
        'follow_up' => ['bg' => '#fffbeb', 'text' => '#b45309', 'border' => '#fde68a'],
        'rnr' => ['bg' => '#fff1f2', 'text' => '#e11d48', 'border' => '#fecdd3'],
        'applied' => ['bg' => '#eff6ff', 'text' => '#2563eb', 'border' => '#bfdbfe'],
        'screening' => ['bg' => '#fff7ed', 'text' => '#c2410c', 'border' => '#fed7aa'],
        'interview_scheduled' => ['bg' => '#faf5ff', 'text' => '#7c3aed', 'border' => '#e9d5ff'],
        'selected' => ['bg' => '#f0fdf4', 'text' => '#15803d', 'border' => '#bbf7d0'],
        'rejected' => ['bg' => '#fef2f2', 'text' => '#b91c1c', 'border' => '#fecaca'],
    ];
    $sc = $statusColors[$candidate->status] ?? $statusColors['applied'];
    $callCount = $candidate->callUpdates->count();
    $interviewCount = $candidate->interviews->count();
    $lastCall = $candidate->callUpdates->first();
    $nextInterview = $candidate->interviews->where('scheduled_at', '>=', now())->sortBy('scheduled_at')->first();
@endphp

<div class="lsp">
    <div class="lsp-topbar">
        <div>
            <div class="lsp-title">{{ $candidate->name }}</div>
            <div class="lsp-crumb">
                <a href="{{ route('recruitment.index') }}">Recruitment</a> >
                {{ $candidate->candidate_no }}
            </div>
        </div>
        <div class="lsp-topbar-right">
            <a href="{{ route('recruitment.edit', $candidate) }}" class="lsp-btn lsp-btn-outline"><i class="bi bi-pencil"></i> Edit Candidate</a>
            @if($candidate->resume_path)
                <a href="{{ asset('storage/' . $candidate->resume_path) }}" target="_blank" class="lsp-btn lsp-btn-primary">View Resume</a>
            @endif
            <a href="{{ route('recruitment.index') }}" class="lsp-btn lsp-btn-outline">Back</a>
        </div>
    </div>

    <div class="lsp-hero">
        <div class="lsp-hero-logo" style="background:{{ $heroColor }}">{{ $candidate->initials }}</div>
        <div>
            <div class="lsp-hero-name">{{ $candidate->name }}</div>
            <div class="lsp-hero-sub">
                <span>{{ $candidate->job_title }}</span>
                <span style="color:#ddd">.</span>
                <span>{{ $candidate->mobile_number }}</span>
                @if($candidate->email)
                    <span style="color:#ddd">.</span>
                    <span>{{ $candidate->email }}</span>
                @endif
                @if($candidate->location)
                    <span style="color:#ddd">.</span>
                    <span>{{ $candidate->location }}</span>
                @endif
            </div>
        </div>
        <div class="lsp-hero-right">
            <span class="lsp-badge" style="background:{{ $sc['bg'] }};color:{{ $sc['text'] }};border-color:{{ $sc['border'] }}">
                <span class="rec-status-dot" style="background:{{ $sc['text'] }}"></span>
                {{ $candidate->status_label }}
            </span>
            <span class="lsp-badge" style="background:#fff7ed;color:#c2410c;border-color:#fed7aa">{{ $candidate->candidate_no }}</span>
            <span style="font-size:12px;font-weight:800;color:var(--text);">{{ $candidate->created_at->format('d M Y') }}</span>
        </div>
    </div>

    <div class="lsp-tabs-wrap">
        <div class="lsp-tabs">
            <button class="lsp-tab active" type="button" onclick="switchRecruitmentTab('info', this)">
                Candidate Info
            </button>
            <button class="lsp-tab" type="button" onclick="switchRecruitmentTab('calls', this)">
                Call Updates <span class="lsp-tab-count">{{ $callCount }}</span>
            </button>
            <button class="lsp-tab" type="button" onclick="switchRecruitmentTab('interviews', this)">
                Interviews <span class="lsp-tab-count">{{ $interviewCount }}</span>
            </button>
            <button class="lsp-tab" type="button" onclick="switchRecruitmentTab('decision', this)">
                Decision
            </button>
        </div>
    </div>

    <div class="lsp-body">
        @if(session('success') || $errors->any())
            <div style="padding:14px 28px 0;">
                @if(session('success'))
                    <div class="lsp-alert lsp-alert-success">{!! session('success') !!}</div>
                @endif
                @if($errors->any())
                    <div class="lsp-alert lsp-alert-error">Please review the form fields and try again.</div>
                @endif
            </div>
        @endif

        <div class="lsp-panel active" id="panel-info">
            <div class="lsp-grid-2" style="gap:18px;">
                <div class="lsp-stack">
                    <div class="lsp-card">
                        <div class="lsp-card-head">
                            <div class="lsp-card-title">Candidate Profile & Information</div>
                        </div>
                        <div class="lsp-card-body">
                            <div class="lsp-info-grid">
                                <div class="lsp-info-item"><div class="lsp-il">Candidate Name</div><div class="lsp-iv">{{ $candidate->name }}</div></div>
                                <div class="lsp-info-item"><div class="lsp-il">Mobile Number</div><div class="lsp-iv"><a href="tel:{{ $candidate->mobile_number }}">{{ $candidate->mobile_number }}</a></div></div>
                                <div class="lsp-info-item"><div class="lsp-il">Email</div><div class="lsp-iv">{!! $candidate->email ? '<a href="mailto:'.$candidate->email.'">'.$candidate->email.'</a>' : 'N/A' !!}</div></div>
                                <div class="lsp-info-item"><div class="lsp-il">Location</div><div class="lsp-iv">{{ $candidate->location ?: 'N/A' }}</div></div>
                                <div class="lsp-info-item"><div class="lsp-il">Applied For</div><div class="lsp-iv">{{ $candidate->job_title }}</div></div>
                                <div class="lsp-info-item">
                                    <div class="lsp-il">Candidate Type</div>
                                    <div class="lsp-iv">
                                        <span class="rec-chip {{ $candidate->candidate_type === 'experienced' ? 'rec-chip-interview_scheduled' : 'rec-chip-applied' }}">
                                            {{ ucfirst($candidate->candidate_type ?: 'fresher') }}
                                        </span>
                                    </div>
                                </div>
                                <div class="lsp-info-item">
                                    <div class="lsp-il">Source</div>
                                    <div class="lsp-iv">
                                        {{ $candidate->source ?: 'N/A' }}
                                        @if($candidate->source === 'Others' && $candidate->source_details)
                                            <span style="font-weight:600; color:#666;">({{ $candidate->source_details }})</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="lsp-info-item"><div class="lsp-il">Expected CTC</div><div class="lsp-iv">{{ $candidate->expected_ctc !== null ? '₹ ' . number_format((float) $candidate->expected_ctc, 2) : 'N/A' }}</div></div>
                            </div>
                        </div>
                    </div>

                    @if($candidate->candidate_type === 'intern' || $candidate->institute_name || $candidate->course_name)
                        <div class="lsp-card">
                            <div class="lsp-card-head">
                                <div class="lsp-card-title">Internship Details</div>
                            </div>
                            <div class="lsp-card-body">
                                <div class="lsp-info-grid">
                                    <div class="lsp-info-item"><div class="lsp-il">Institute / College</div><div class="lsp-iv">{{ $candidate->institute_name ?: 'N/A' }}</div></div>
                                    <div class="lsp-info-item"><div class="lsp-il">Course / Degree</div><div class="lsp-iv">{{ $candidate->course_name ?: 'N/A' }}</div></div>
                                    <div class="lsp-info-item"><div class="lsp-il">Internship Duration</div><div class="lsp-iv">{{ $candidate->internship_months ?: 'N/A' }}</div></div>
                                    <div class="lsp-info-item"><div class="lsp-il">Stipend Provided</div><div class="lsp-iv">{{ $candidate->has_stipend ? ucfirst($candidate->has_stipend) : 'N/A' }}</div></div>
                                    @if($candidate->has_stipend === 'yes')
                                        <div class="lsp-info-item"><div class="lsp-il">Stipend Amount</div><div class="lsp-iv">{{ $candidate->stipend_amount !== null ? '₹ ' . number_format((float) $candidate->stipend_amount, 2) : 'N/A' }}</div></div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($candidate->candidate_type === 'experienced' || $candidate->experience_years !== null || $candidate->previous_company)
                        <div class="lsp-card">
                            <div class="lsp-card-head">
                                <div class="lsp-card-title">Experience & Previous HR Details</div>
                            </div>
                            <div class="lsp-card-body">
                                <div class="lsp-info-grid">
                                    <div class="lsp-info-item"><div class="lsp-il">Experience Years</div><div class="lsp-iv">{{ $candidate->experience_years !== null ? $candidate->experience_years . ' Year(s)' : 'N/A' }}</div></div>
                                    <div class="lsp-info-item"><div class="lsp-il">Notice Period</div><div class="lsp-iv">{{ $candidate->notice_period ?: 'N/A' }}</div></div>
                                    <div class="lsp-info-item"><div class="lsp-il">Current CTC</div><div class="lsp-iv">{{ $candidate->current_ctc !== null ? '₹ ' . number_format((float) $candidate->current_ctc, 2) : 'N/A' }}</div></div>
                                    <div class="lsp-info-item"><div class="lsp-il">Previous Company</div><div class="lsp-iv">{{ $candidate->previous_company ?: 'N/A' }}</div></div>
                                    <div class="lsp-info-item"><div class="lsp-il">Previous HR Name</div><div class="lsp-iv">{{ $candidate->previous_hr_name ?: 'N/A' }}</div></div>
                                    <div class="lsp-info-item"><div class="lsp-il">Previous HR Contact</div><div class="lsp-iv">{!! $candidate->previous_hr_contact ? '<a href="tel:'.$candidate->previous_hr_contact.'">'.$candidate->previous_hr_contact.'</a>' : 'N/A' !!}</div></div>
                                    <div class="lsp-info-item"><div class="lsp-il">Relieving Reason</div><div class="lsp-iv">{{ $candidate->relieving_reason ?: 'N/A' }}</div></div>
                                    <div class="lsp-info-item"><div class="lsp-il">Own Laptop</div><div class="lsp-iv">{{ $candidate->has_laptop ? ucfirst($candidate->has_laptop) : 'N/A' }}</div></div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if(!empty($candidate->education_details))
                        <div class="lsp-card">
                            <div class="lsp-card-head">
                                <div class="lsp-card-title">Education Details</div>
                            </div>
                            <div class="lsp-card-body">
                                <div style="overflow-x:auto;">
                                    <table class="lsp-qt-items-tbl">
                                        <thead>
                                            <tr>
                                                <th>Degree / Qualification</th>
                                                <th>Institution / College</th>
                                                <th>Specialization</th>
                                                <th>Passing Year</th>
                                                <th>Percentage / CGPA</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($candidate->education_details as $edu)
                                                <tr>
                                                    <td><strong>{{ $edu['degree'] ?? 'N/A' }}</strong></td>
                                                    <td>{{ $edu['institution'] ?? 'N/A' }}</td>
                                                    <td>{{ $edu['specialization'] ?? 'N/A' }}</td>
                                                    <td>{{ $edu['year_of_passing'] ?? 'N/A' }}</td>
                                                    <td>{{ $edu['percentage'] ?? 'N/A' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="lsp-card">
                        <div class="lsp-card-head">
                            <div class="lsp-card-title">Remarks</div>
                        </div>
                        <div class="lsp-card-body">
                            <div class="lsp-remarks-box">{{ $candidate->remarks ?: 'No remarks added.' }}</div>
                        </div>
                    </div>
                </div>

                <div class="lsp-stack">
                    <div class="lsp-card">
                        <div class="lsp-card-head">
                            <div class="lsp-card-title">Pipeline Summary</div>
                        </div>
                        <div class="lsp-card-body">
                            <div class="lsp-stat-grid">
                                <div class="lsp-stat-box">
                                    <div class="lsp-il">Call Updates</div>
                                    <div class="lsp-deal-big">{{ $callCount }}</div>
                                </div>
                                <div class="lsp-stat-box blue">
                                    <div class="lsp-il">Interviews</div>
                                    <div class="lsp-deal-big">{{ $interviewCount }}</div>
                                </div>
                            </div>
                            <div class="lsp-info-grid">
                                <div class="lsp-info-item"><div class="lsp-il">Current CTC</div><div class="lsp-iv">{{ $candidate->current_ctc !== null ? number_format((float) $candidate->current_ctc, 2) : 'N/A' }}</div></div>
                                <div class="lsp-info-item"><div class="lsp-il">Expected CTC</div><div class="lsp-iv">{{ $candidate->expected_ctc !== null ? number_format((float) $candidate->expected_ctc, 2) : 'N/A' }}</div></div>
                                <div class="lsp-info-item"><div class="lsp-il">Last Call</div><div class="lsp-iv">{{ $lastCall?->called_at?->format('d M Y, h:i A') ?: 'N/A' }}</div></div>
                                <div class="lsp-info-item"><div class="lsp-il">Next Interview</div><div class="lsp-iv">{{ $nextInterview?->scheduled_at?->format('d M Y, h:i A') ?: 'N/A' }}</div></div>
                            </div>
                            <div class="lsp-meta-line" style="margin-top:14px;">
                                <div>
                                    <div class="lsp-il">Current Status</div>
                                    <div class="lsp-meta-value">{{ $candidate->status_label }}</div>
                                </div>
                                <span class="lsp-age-pill">{{ $candidate->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="lsp-card">
                        <div class="lsp-card-head">
                            <div class="lsp-card-title">Quick Decision</div>
                        </div>
                        <div class="lsp-card-body">
                            <div class="rec-decision-grid">
                                <form method="POST" action="{{ route('recruitment.status.update', $candidate) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="selected">
                                    <button type="submit" class="lsp-btn lsp-btn-primary" style="width:100%;justify-content:center;">Select</button>
                                </form>
                                <form method="POST" action="{{ route('recruitment.status.update', $candidate) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="rejected">
                                    <button type="submit" class="lsp-btn lsp-btn-danger" style="width:100%;justify-content:center;">Reject</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="lsp-panel" id="panel-calls">
            <div class="rec-wide-grid">
                <div class="lsp-card">
                    <div class="lsp-card-head">
                        <div class="lsp-card-title">Call History</div>
                    </div>
                    <div class="lsp-card-body">
                        <div class="lsp-call-list">
                            @forelse($candidate->callUpdates as $call)
                                <div class="lsp-call-card type-{{ $call->call_type }}">
                                    <div class="lsp-call-top">
                                        <div class="lsp-call-meta">
                                            <span class="lsp-call-type ct-{{ $call->call_type }}">{{ $call->call_type_label }}</span>
                                            <span class="lsp-call-when">{{ $call->called_at?->format('d M Y, h:i A') }}</span>
                                            @if($call->duration_minutes)
                                                <span class="lsp-call-dur">{{ $call->duration_minutes }} min</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="lsp-call-notes">{{ $call->notes ?: 'No notes added.' }}</div>
                                    <div class="lsp-call-footer">
                                        <span class="lsp-call-outcome" style="background:#fff7ed;color:#c2410c;">{{ $call->outcome_label }}</span>
                                        <span class="lsp-call-by">{{ $call->user?->name ?? 'HR' }}</span>
                                        @if($call->next_follow_up_at)
                                            <span class="lsp-call-followup">Follow-up {{ $call->next_follow_up_at->format('d M Y, h:i A') }}</span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="lsp-empty rec-empty-tight">
                                    <div class="lsp-empty-title">No call updates yet.</div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="lsp-card">
                    <div class="lsp-card-head">
                        <div class="lsp-card-title">Add Call Update</div>
                    </div>
                    <form method="POST" action="{{ route('recruitment.call-updates.store', $candidate) }}">
                        @csrf
                        <div class="lsp-card-body">
                            <div class="lsp-stack">
                                <div class="lsp-group">
                                    <label class="lsp-label">Called At <span class="lsp-req">*</span></label>
                                    <input type="datetime-local" name="called_at" class="lsp-inp no-ico" value="{{ old('called_at', now()->format('Y-m-d\TH:i')) }}" required>
                                </div>
                                <div class="lsp-form-row lsp-form-row-2">
                                    <div class="lsp-group">
                                        <label class="lsp-label">Call Type <span class="lsp-req">*</span></label>
                                        <select name="call_type" class="lsp-sel no-ico" required>
                                            @foreach($callTypes as $value => $label)
                                                <option value="{{ $value }}" @selected(old('call_type', 'outgoing') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="lsp-group">
                                        <label class="lsp-label">Duration</label>
                                        <input type="number" name="duration_minutes" class="lsp-inp no-ico" min="0" value="{{ old('duration_minutes') }}">
                                    </div>
                                </div>
                                <div class="lsp-group">
                                    <label class="lsp-label">Outcome <span class="lsp-req">*</span></label>
                                    <select name="outcome" class="lsp-sel no-ico" required>
                                        @foreach($callOutcomes as $value => $label)
                                            <option value="{{ $value }}" @selected(old('outcome') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="lsp-group">
                                    <label class="lsp-label">Next Follow-up</label>
                                    <input type="datetime-local" name="next_follow_up_at" class="lsp-inp no-ico" value="{{ old('next_follow_up_at') }}">
                                </div>
                                <div class="lsp-group">
                                    <label class="lsp-label">Notes</label>
                                    <textarea name="notes" class="lsp-ta" rows="5" placeholder="Candidate expectation, HR discussion, callback promise...">{{ old('notes') }}</textarea>
                                </div>
                                <button type="submit" class="lsp-btn lsp-btn-primary" style="justify-content:center;">Add Call Update</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="lsp-panel" id="panel-interviews">
            <div class="rec-wide-grid">
                <div class="lsp-card">
                    <div class="lsp-card-head">
                        <div class="lsp-card-title">Interview History</div>
                    </div>
                    <div class="lsp-card-body">
                        <div style="overflow-x:auto;">
                            <table class="lsp-qt-items-tbl">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Interviewer</th>
                                        <th>Status</th>
                                        <th>Notes</th>
                                        <th style="text-align:right;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($candidate->interviews as $interview)
                                        <tr>
                                            <td>{{ $interview->scheduled_at?->format('d M Y, h:i A') }}</td>
                                            <td>{{ $interview->interviewer_name ?: 'N/A' }}</td>
                                            <td>{{ $interview->status_label }}</td>
                                            <td>{{ $interview->notes ?: 'N/A' }}</td>
                                            <td style="text-align:right;">
                                                 <button type="button" class="lsp-btn lsp-btn-outline" style="padding:4px 10px;font-size:12px;display:inline-flex;align-items:center;gap:4px;"
                                                     onclick="openRescheduleModal(
                                                         {{ $interview->id }},
                                                         '{{ optional($interview->scheduled_at)->format('Y-m-d\TH:i') }}',
                                                         '{{ addslashes($interview->interviewer_name ?? '') }}',
                                                         '{{ $interview->status }}',
                                                         '{{ addslashes($interview->notes ?? '') }}'
                                                     )">
                                                     <i class="bi bi-arrow-repeat"></i> Reschedule
                                                 </button>
                                             </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" style="text-align:center;color:#9e9e9e;padding:28px;">No interviews scheduled.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="lsp-card">
                    <div class="lsp-card-head">
                        <div class="lsp-card-title">Schedule Interview</div>
                    </div>
                    <form method="POST" action="{{ route('recruitment.interviews.store', $candidate) }}">
                        @csrf
                        <div class="lsp-card-body">
                            <div class="lsp-stack">
                                <div class="lsp-group">
                                    <label class="lsp-label">Scheduled At <span class="lsp-req">*</span></label>
                                    <input type="datetime-local" name="scheduled_at" class="lsp-inp no-ico" value="{{ old('scheduled_at') }}" required>
                                </div>
                                <div class="lsp-group">
                                    <label class="lsp-label">Interviewer (Team Leads)</label>
                                    <select name="interviewer_id" id="interviewer_select" class="lsp-sel no-ico select2" style="width:100%;">
                                        <option value="">Select Interviewer</option>
                                        @foreach($activeUsers as $user)
                                            @php
                                                $roleName = $user->role_display_name;
                                                $label = $user->name . ($roleName && $roleName !== 'No Role' ? " ({$roleName})" : '');
                                            @endphp
                                            <option value="{{ $user->id }}" data-name="{{ $user->name }}" @selected(old('interviewer_id') == $user->id || old('interviewer_name') === $user->name)>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="lsp-group">
                                    <label class="lsp-label">Interview Status</label>
                                    <select name="status" class="lsp-sel no-ico">
                                        @foreach($interviewStatuses as $value => $label)
                                            <option value="{{ $value }}" @selected(old('status', 'scheduled') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="lsp-group">
                                    <label class="lsp-label">Notes</label>
                                    <textarea name="notes" class="lsp-ta" rows="4" placeholder="Agenda, panel details, instructions...">{{ old('notes') }}</textarea>
                                </div>
                                <button type="submit" class="lsp-btn lsp-btn-primary" style="justify-content:center;">Schedule Interview</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="lsp-panel" id="panel-decision">
            <div class="lsp-grid-2" style="gap:18px;">
                <div class="lsp-card">
                    <div class="lsp-card-head">
                        <div class="lsp-card-title">Move Candidate</div>
                    </div>
                    <div class="lsp-card-body">
                        <form method="POST" action="{{ route('recruitment.status.update', $candidate) }}">
                            @csrf
                            @method('PATCH')
                            <div class="lsp-stack">
                                <div class="lsp-group">
                                    <label class="lsp-label">Status</label>
                                    <select name="status" class="lsp-sel no-ico">
                                        @foreach($statuses as $value => $label)
                                            <option value="{{ $value }}" @selected($candidate->status === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit" class="lsp-btn lsp-btn-primary" style="justify-content:center;">Update Status</button>
                            </div>
                        </form>

                        <div class="rec-mini-actions" style="margin-top:14px;">
                            <form method="POST" action="{{ route('recruitment.status.update', $candidate) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="selected">
                                <button type="submit" class="lsp-btn lsp-btn-green">Move to Selected</button>
                            </form>
                            <form method="POST" action="{{ route('recruitment.status.update', $candidate) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="rejected">
                                <button type="submit" class="lsp-btn lsp-btn-danger">Move to Rejected</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="lsp-card">
                    <div class="lsp-card-head">
                        <div class="lsp-card-title">Record</div>
                    </div>
                    <div class="lsp-card-body">
                        <div class="lsp-info-grid">
                            <div class="lsp-info-item"><div class="lsp-il">Created By</div><div class="lsp-iv">{{ $candidate->creator?->name ?? 'System' }}</div></div>
                            <div class="lsp-info-item"><div class="lsp-il">Updated By</div><div class="lsp-iv">{{ $candidate->updater?->name ?? 'System' }}</div></div>
                            <div class="lsp-info-item"><div class="lsp-il">Created At</div><div class="lsp-iv">{{ $candidate->created_at->format('d M Y, h:i A') }}</div></div>
                            <div class="lsp-info-item"><div class="lsp-il">Status Updated</div><div class="lsp-iv">{{ $candidate->status_updated_at?->format('d M Y, h:i A') ?: 'N/A' }}</div></div>
                        </div>
                        <div class="rec-danger-box" style="margin-top:14px;">
                            <form method="POST" action="{{ route('recruitment.destroy', $candidate) }}" onsubmit="return confirm('Delete this candidate?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="lsp-btn lsp-btn-danger" style="width:100%;justify-content:center;">Delete Candidate</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- RESCHEDULE INTERVIEW MODAL --}}
<div id="modalRescheduleInterview" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;padding:16px;">
    <div class="lsp-card" style="max-width:540px;width:100%;background:#fff;border-radius:18px;box-shadow:0 20px 45px rgba(0,0,0,0.2);overflow:hidden;margin:0 auto;">
        <div class="lsp-card-head" style="display:flex;justify-content:space-between;align-items:center;background:linear-gradient(135deg,#fe5f04 0%,#ff8745 100%);padding:16px 20px;">
            <div class="lsp-card-title" style="color:#fff;font-size:16px;font-weight:700;">🔄 Reschedule Interview</div>
            <button type="button" style="border:none;background:none;font-size:22px;font-weight:700;cursor:pointer;color:#fff;line-height:1;" onclick="closeRescheduleModal()">&times;</button>
        </div>
        <form id="formRescheduleInterview" method="POST" action="">
            @csrf
            @method('PUT')
            <div class="lsp-card-body" style="padding:20px;">
                <div class="lsp-stack">
                    <div class="lsp-group">
                        <label class="lsp-label">Scheduled At <span class="lsp-req">*</span></label>
                        <input type="datetime-local" id="reschedule_scheduled_at" name="scheduled_at" class="lsp-inp no-ico" required>
                    </div>
                    <div class="lsp-group">
                        <label class="lsp-label">Interviewer (Team Leads)</label>
                        <select id="reschedule_interviewer_select" name="interviewer_id" class="lsp-sel no-ico select2" style="width:100%;">
                            <option value="">Select Interviewer</option>
                            @foreach($activeUsers as $user)
                                @php
                                    $roleName = $user->role_display_name;
                                    $label = $user->name . ($roleName && $roleName !== 'No Role' ? " ({$roleName})" : '');
                                @endphp
                                <option value="{{ $user->id }}" data-name="{{ $user->name }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lsp-group">
                        <label class="lsp-label">Interview Status</label>
                        <select id="reschedule_status" name="status" class="lsp-sel no-ico">
                            @foreach($interviewStatuses as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lsp-group">
                        <label class="lsp-label">Notes</label>
                        <textarea id="reschedule_notes" name="notes" class="lsp-ta" rows="3"></textarea>
                    </div>
                    <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:14px;padding-top:10px;border-top:1px solid #eee;">
                        <button type="button" class="lsp-btn lsp-btn-outline" onclick="closeRescheduleModal()">Cancel</button>
                        <button type="submit" class="lsp-btn lsp-btn-primary">Save Changes & Send Email</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function switchRecruitmentTab(name, btn) {
    document.querySelectorAll('.lsp-tab').forEach(function (tab) {
        tab.classList.remove('active');
    });

    document.querySelectorAll('.lsp-panel').forEach(function (panel) {
        panel.classList.remove('active');
    });

    btn.classList.add('active');
    document.getElementById('panel-' + name)?.classList.add('active');
}

function openRescheduleModal(id, scheduledAt, interviewerName, status, notes) {
    const modal = document.getElementById('modalRescheduleInterview');
    const form = document.getElementById('formRescheduleInterview');
    form.action = `{{ url('/') }}/recruitment/{{ $candidate->id }}/interviews/${id}`;

    document.getElementById('reschedule_scheduled_at').value = scheduledAt;
    document.getElementById('reschedule_status').value = status;
    document.getElementById('reschedule_notes').value = notes;

    const select = document.getElementById('reschedule_interviewer_select');
    if (select) {
        let matchedVal = '';
        for (let i = 0; i < select.options.length; i++) {
            if (select.options[i].getAttribute('data-name') === interviewerName || select.options[i].text.trim() === interviewerName) {
                matchedVal = select.options[i].value;
                break;
            }
        }
        select.value = matchedVal;
        if (window.jQuery && window.jQuery(select).data('select2')) {
            window.jQuery(select).val(matchedVal).trigger('change');
        }
    }

    modal.style.display = 'flex';
}

function closeRescheduleModal() {
    document.getElementById('modalRescheduleInterview').style.display = 'none';
}

document.addEventListener('DOMContentLoaded', function() {
    if (window.jQuery && window.jQuery.fn.select2) {
        window.jQuery('#interviewer_select, #reschedule_interviewer_select').select2({
            placeholder: 'Select Interviewer',
            allowClear: true,
            width: '100%'
        });
    }
});
</script>
@endpush
