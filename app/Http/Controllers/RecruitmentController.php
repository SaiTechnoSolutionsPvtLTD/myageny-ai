<?php

namespace App\Http\Controllers;

use App\Models\EmployeeOnboarding;
use App\Models\RecruitmentCallUpdate;
use App\Models\RecruitmentCandidate;
use App\Models\RecruitmentInterview;
use App\Models\RecruitmentReminder;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RecruitmentController extends Controller
{
    private const RESUME_DIRECTORY = 'recruitment/resumes';

    public function index(Request $request): View
    {
        $user = auth()->user();
        $employee = $this->currentEmployee();

        $query = RecruitmentCandidate::query()
            ->with(['creator', 'latestInterview'])
            ->withCount(['callUpdates', 'interviews'])
            ->latest();

        $dateFrom = $request->filled('date_from') ? $request->input('date_from') : null;
        $dateTo = $request->filled('date_to') ? $request->input('date_to') : null;

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('candidate_no', 'like', '%' . $search . '%')
                    ->orWhere('name', 'like', '%' . $search . '%')
                    ->orWhere('mobile_number', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('job_title', 'like', '%' . $search . '%')
                    ->orWhere('location', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('bucket')) {
            match ($request->bucket) {
                'selected' => $query->where('status', RecruitmentCandidate::STATUS_SELECTED),
                'rejected' => $query->where('status', RecruitmentCandidate::STATUS_REJECTED),
                'active' => $query->whereNotIn('status', [
                    RecruitmentCandidate::STATUS_SELECTED,
                    RecruitmentCandidate::STATUS_REJECTED,
                ]),
                default => null,
            };
        }

        $applyAssignedFilter = function ($q) use ($user, $employee) {
            $q->whereHas('interviews', function ($iq) use ($user, $employee) {
                $iq->where(function ($sub) use ($user, $employee) {
                    if ($user) {
                        $sub->where('interviewer_id', $user->id)
                            ->orWhere('interviewer_name', $user->name);
                    }
                    if ($employee && $employee->name && (! $user || $employee->name !== $user->name)) {
                        $sub->orWhere('interviewer_name', $employee->name);
                    }
                });
            });
        };

        if ($request->get('assigned') === 'me' || $request->filled('my_interviews')) {
            $applyAssignedFilter($query);
        }

        $candidates = $query->paginate(12)->withQueryString();

        $baseCountQuery = RecruitmentCandidate::query();
        if ($dateFrom) {
            $baseCountQuery->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $baseCountQuery->whereDate('created_at', '<=', $dateTo);
        }

        $counts = [
            'all' => (clone $baseCountQuery)->count(),
            'active' => (clone $baseCountQuery)->whereNotIn('status', [
                RecruitmentCandidate::STATUS_SELECTED,
                RecruitmentCandidate::STATUS_REJECTED,
            ])->count(),
            'selected' => (clone $baseCountQuery)->where('status', RecruitmentCandidate::STATUS_SELECTED)->count(),
            'rejected' => (clone $baseCountQuery)->where('status', RecruitmentCandidate::STATUS_REJECTED)->count(),
        ];

        return view('pages.hrms.recruitment.index', [
            'candidates' => $candidates,
            'counts' => $counts,
            'statuses' => RecruitmentCandidate::STATUSES,
        ]);
    }

    public function callUpdates(Request $request): View
    {
        $hasDateFilter = $request->has('date_from') || $request->has('date_to');

        if ($hasDateFilter) {
            $dateFrom = $request->filled('date_from') ? $request->input('date_from') : null;
            $dateTo = $request->filled('date_to') ? $request->input('date_to') : null;
        } else {
            $dateFrom = now()->startOfMonth()->toDateString();
            $dateTo = now()->endOfMonth()->toDateString();
        }

        $query = RecruitmentCallUpdate::query()
            ->with([
                'candidate:id,candidate_no,name,mobile_number,email,job_title,location,candidate_type,status',
                'user:id,name',
            ])
            ->latest('called_at');

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                    ->orWhereHas('candidate', function ($cq) use ($search) {
                        $cq->where('id', $search)
                            ->orWhere('candidate_no', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%")
                            ->orWhere('mobile_number', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('job_title', 'like', "%{$search}%")
                            ->orWhere('location', 'like', "%{$search}%");
                    })
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('outcome')) {
            $query->where('outcome', $request->outcome);
        }

        if ($request->filled('call_type')) {
            $query->where('call_type', $request->call_type);
        }

        if ($request->filled('candidate_status')) {
            $query->whereHas('candidate', function ($cq) use ($request) {
                $cq->where('status', $request->candidate_status);
            });
        }

        if ($dateFrom) {
            $query->whereDate('called_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('called_at', '<=', $dateTo);
        }

        if ($request->filled('follow_up_date')) {
            $query->whereDate('next_follow_up_at', $request->follow_up_date);
        }

        // Summary Counts
        $baseCountQuery = RecruitmentCallUpdate::query();
        if ($dateFrom) {
            $baseCountQuery->whereDate('called_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $baseCountQuery->whereDate('called_at', '<=', $dateTo);
        }

        $counts = [
            'total' => (clone $baseCountQuery)->count(),
            'today' => RecruitmentCallUpdate::query()->whereDate('called_at', today())->count(),
            'interested' => (clone $baseCountQuery)->whereIn('outcome', ['interested', 'screening', 'interview_planned'])->count(),
            'follow_up' => (clone $baseCountQuery)->where('outcome', 'follow_up')->count(),
            'selected' => (clone $baseCountQuery)->where('outcome', 'selected')->count(),
        ];

        $callUpdates = $query->paginate(15)->withQueryString();

        // HR department users for the Caller / HR filter
        $onboardingUserIds = \App\Models\EmployeeOnboarding::whereHas('department', function ($q) {
            $q->where('name', 'LIKE', '%hr%')
              ->orWhere('name', 'LIKE', '%human%');
        })->pluck('portal_user_id')->filter()->toArray();

        $internUserIds = \App\Models\InternJoiningForm::whereHas('department', function ($q) {
            $q->where('name', 'LIKE', '%hr%')
              ->orWhere('name', 'LIKE', '%human%');
        })->pluck('portal_user_id')->filter()->toArray();

        $users = User::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->filter(function ($u) use ($onboardingUserIds, $internUserIds) {
                return $u->belongsToHrDepartment()
                    || $u->hasHrLikeRole()
                    || in_array($u->id, $onboardingUserIds)
                    || in_array($u->id, $internUserIds);
            })
            ->values();

        if ($users->isEmpty()) {
            $userIds = RecruitmentCallUpdate::query()->distinct()->pluck('user_id')->filter();
            $users = User::whereIn('id', $userIds)->orderBy('name')->get(['id', 'name']);
        }

        return view('pages.hrms.recruitment.call_updates.index', [
            'callUpdates' => $callUpdates,
            'users' => $users,
            'outcomes' => RecruitmentCallUpdate::OUTCOMES,
            'callTypes' => RecruitmentCallUpdate::CALL_TYPES,
            'candidateStatuses' => RecruitmentCandidate::STATUSES,
            'counts' => $counts,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    public function create(): View
    {
        return view('pages.hrms.recruitment.create', [
            'candidateNo' => RecruitmentCandidate::generateCandidateNo(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'mobile_number' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'location' => ['nullable', 'string', 'max:150'],
            'job_title' => ['required', 'string', 'max:150'],
            'candidate_type' => ['required', 'string', Rule::in(['fresher', 'experienced', 'intern'])],
            'institute_name' => ['nullable', 'string', 'max:255'],
            'course_name' => ['nullable', 'string', 'max:255'],
            'internship_months' => ['nullable', 'string', 'max:50'],
            'has_stipend' => ['nullable', 'string', Rule::in(['yes', 'no'])],
            'stipend_amount' => ['nullable', 'numeric', 'min:0'],
            'source' => ['nullable', 'string', 'max:100'],
            'source_details' => ['nullable', 'string', 'max:255'],
            'current_ctc' => ['nullable', 'numeric', 'min:0'],
            'expected_ctc' => ['nullable', 'numeric', 'min:0'],
            'notice_period' => ['nullable', 'string', 'max:100'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
            'previous_company' => ['nullable', 'string', 'max:150'],
            'previous_hr_name' => ['nullable', 'string', 'max:150'],
            'previous_hr_contact' => ['nullable', 'string', 'max:50'],
            'relieving_reason' => ['nullable', 'string', 'max:255'],
            'has_laptop' => ['nullable', 'string', Rule::in(['yes', 'no'])],
            'education_details' => ['nullable', 'array'],
            'education_details.*.degree' => ['nullable', 'string', 'max:150'],
            'education_details.*.institution' => ['nullable', 'string', 'max:150'],
            'education_details.*.specialization' => ['nullable', 'string', 'max:150'],
            'education_details.*.year_of_passing' => ['nullable', 'string', 'max:10'],
            'education_details.*.percentage' => ['nullable', 'string', 'max:20'],
            'remarks' => ['nullable', 'string', 'max:3000'],
            'resume' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ]);

        if (($validated['source'] ?? '') !== 'Others') {
            $validated['source_details'] = null;
        }

        if (($validated['candidate_type'] ?? '') === 'fresher') {
            $validated['experience_years'] = null;
            $validated['previous_company'] = null;
            $validated['previous_hr_name'] = null;
            $validated['previous_hr_contact'] = null;
            $validated['relieving_reason'] = null;
            $validated['has_laptop'] = null;
            $validated['notice_period'] = null;
            $validated['current_ctc'] = null;
            $validated['institute_name'] = null;
            $validated['course_name'] = null;
            $validated['internship_months'] = null;
            $validated['has_stipend'] = null;
            $validated['stipend_amount'] = null;
        } elseif (($validated['candidate_type'] ?? '') === 'experienced') {
            $validated['institute_name'] = null;
            $validated['course_name'] = null;
            $validated['internship_months'] = null;
            $validated['has_stipend'] = null;
            $validated['stipend_amount'] = null;
        } elseif (($validated['candidate_type'] ?? '') === 'intern') {
            $validated['experience_years'] = null;
            $validated['previous_company'] = null;
            $validated['previous_hr_name'] = null;
            $validated['previous_hr_contact'] = null;
            $validated['relieving_reason'] = null;
            $validated['has_laptop'] = null;
            $validated['notice_period'] = null;
            $validated['current_ctc'] = null;

            if (($validated['has_stipend'] ?? '') !== 'yes') {
                $validated['stipend_amount'] = null;
            }
        }

        if (!empty($validated['education_details'])) {
            $validated['education_details'] = array_values(array_filter($validated['education_details'], function ($item) {
                return !empty($item['degree']) || !empty($item['institution']) || !empty($item['specialization']);
            }));
        }

        if ($request->hasFile('resume')) {
            $validated['resume_path'] = $request->file('resume')->store(self::RESUME_DIRECTORY, 'public');
        }

        unset($validated['resume']);

        $candidate = RecruitmentCandidate::create(array_merge($validated, [
            'candidate_no' => RecruitmentCandidate::generateCandidateNo(),
            'status' => RecruitmentCandidate::STATUS_SHORTLIST,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
            'status_updated_at' => now(),
        ]));

        return redirect()
            ->route('recruitment.show', $candidate)
            ->with('success', "Candidate <strong>{$candidate->name}</strong> added to recruitment.");
    }

    public function show(RecruitmentCandidate $recruitment): View
    {
        $recruitment->load(['callUpdates.user', 'interviews.scheduler', 'creator', 'updater']);

        $activeUsers = User::query()
            ->with(['roles', 'branch'])
            ->where(function ($query) {
                $query->where('is_active', true)
                    ->orWhere('user_status', 'active');
            })
            ->orderBy('name')
            ->get()
            ->filter(fn ($u) => $u->hasTlLikeRole() || $u->isSuperAdmin() || $u->isCompanyAdmin() || $u->hasAdminLikeRole())
            ->values();

        return view('pages.hrms.recruitment.show', [
            'candidate' => $recruitment,
            'statuses' => RecruitmentCandidate::STATUSES,
            'callTypes' => RecruitmentCallUpdate::CALL_TYPES,
            'callOutcomes' => RecruitmentCallUpdate::OUTCOMES,
            'interviewModes' => RecruitmentInterview::MODES,
            'interviewStatuses' => RecruitmentInterview::STATUSES,
            'activeUsers' => $activeUsers,
        ]);
    }

    public function edit(RecruitmentCandidate $recruitment): View
    {
        return view('pages.hrms.recruitment.edit', [
            'candidate' => $recruitment,
        ]);
    }

    public function update(Request $request, RecruitmentCandidate $recruitment): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'mobile_number' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'location' => ['nullable', 'string', 'max:150'],
            'job_title' => ['required', 'string', 'max:150'],
            'candidate_type' => ['required', 'string', Rule::in(['fresher', 'experienced', 'intern'])],
            'institute_name' => ['nullable', 'string', 'max:255'],
            'course_name' => ['nullable', 'string', 'max:255'],
            'internship_months' => ['nullable', 'string', 'max:50'],
            'has_stipend' => ['nullable', 'string', Rule::in(['yes', 'no'])],
            'stipend_amount' => ['nullable', 'numeric', 'min:0'],
            'source' => ['nullable', 'string', 'max:100'],
            'source_details' => ['nullable', 'string', 'max:255'],
            'current_ctc' => ['nullable', 'numeric', 'min:0'],
            'expected_ctc' => ['nullable', 'numeric', 'min:0'],
            'notice_period' => ['nullable', 'string', 'max:100'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
            'previous_company' => ['nullable', 'string', 'max:150'],
            'previous_hr_name' => ['nullable', 'string', 'max:150'],
            'previous_hr_contact' => ['nullable', 'string', 'max:50'],
            'relieving_reason' => ['nullable', 'string', 'max:255'],
            'has_laptop' => ['nullable', 'string', Rule::in(['yes', 'no'])],
            'education_details' => ['nullable', 'array'],
            'education_details.*.degree' => ['nullable', 'string', 'max:150'],
            'education_details.*.institution' => ['nullable', 'string', 'max:150'],
            'education_details.*.specialization' => ['nullable', 'string', 'max:150'],
            'education_details.*.year_of_passing' => ['nullable', 'string', 'max:10'],
            'education_details.*.percentage' => ['nullable', 'string', 'max:20'],
            'remarks' => ['nullable', 'string', 'max:3000'],
            'resume' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ]);

        if (($validated['source'] ?? '') !== 'Others') {
            $validated['source_details'] = null;
        }

        if (($validated['candidate_type'] ?? '') === 'fresher') {
            $validated['experience_years'] = null;
            $validated['previous_company'] = null;
            $validated['previous_hr_name'] = null;
            $validated['previous_hr_contact'] = null;
            $validated['relieving_reason'] = null;
            $validated['has_laptop'] = null;
            $validated['notice_period'] = null;
            $validated['current_ctc'] = null;
            $validated['institute_name'] = null;
            $validated['course_name'] = null;
            $validated['internship_months'] = null;
            $validated['has_stipend'] = null;
            $validated['stipend_amount'] = null;
        } elseif (($validated['candidate_type'] ?? '') === 'experienced') {
            $validated['institute_name'] = null;
            $validated['course_name'] = null;
            $validated['internship_months'] = null;
            $validated['has_stipend'] = null;
            $validated['stipend_amount'] = null;
        } elseif (($validated['candidate_type'] ?? '') === 'intern') {
            $validated['experience_years'] = null;
            $validated['previous_company'] = null;
            $validated['previous_hr_name'] = null;
            $validated['previous_hr_contact'] = null;
            $validated['relieving_reason'] = null;
            $validated['has_laptop'] = null;
            $validated['notice_period'] = null;
            $validated['current_ctc'] = null;

            if (($validated['has_stipend'] ?? '') !== 'yes') {
                $validated['stipend_amount'] = null;
            }
        }

        if (!empty($validated['education_details'])) {
            $validated['education_details'] = array_values(array_filter($validated['education_details'], function ($item) {
                return !empty($item['degree']) || !empty($item['institution']) || !empty($item['specialization']);
            }));
        }

        if ($request->hasFile('resume')) {
            if ($recruitment->resume_path && Storage::disk('public')->exists($recruitment->resume_path)) {
                Storage::disk('public')->delete($recruitment->resume_path);
            }
            $validated['resume_path'] = $request->file('resume')->store(self::RESUME_DIRECTORY, 'public');
        }

        unset($validated['resume']);

        $recruitment->update(array_merge($validated, [
            'updated_by' => auth()->id(),
        ]));

        return redirect()
            ->route('recruitment.show', $recruitment)
            ->with('success', "Candidate <strong>{$recruitment->name}</strong> updated successfully.");
    }

    public function storeCallUpdate(Request $request, RecruitmentCandidate $recruitment): RedirectResponse
    {
        $validated = $request->validate([
            'called_at' => ['required', 'date'],
            'call_type' => ['required', Rule::in(array_keys(RecruitmentCallUpdate::CALL_TYPES))],
            'duration_minutes' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'outcome' => ['required', Rule::in(array_keys(RecruitmentCallUpdate::OUTCOMES))],
            'notes' => ['nullable', 'string', 'max:3000'],
            'next_follow_up_at' => ['nullable', 'date'],
        ]);

        $callUpdate = $recruitment->callUpdates()->create(array_merge($validated, [
            'company_id' => auth()->user()?->company_id,
            'user_id' => auth()->id(),
        ]));

        if (!empty($validated['next_follow_up_at'])) {
            RecruitmentReminder::create([
                'company_id' => auth()->user()?->company_id,
                'recruitment_candidate_id' => $recruitment->id,
                'recruitment_call_update_id' => $callUpdate->id,
                'user_id' => auth()->id(),
                'title' => 'Follow-up Call: ' . $recruitment->name . ($recruitment->job_title ? ' (' . $recruitment->job_title . ')' : ''),
                'description' => $validated['notes'] ?? null,
                'remind_at' => $validated['next_follow_up_at'],
                'type' => 'follow_up',
                'priority' => 'high',
                'is_completed' => false,
            ]);
        }

        $this->syncCandidateStatusFromCallOutcome($recruitment, $validated['outcome']);

        return back()->with('success', 'Call update added successfully.');
    }

    public function storeInterview(Request $request, RecruitmentCandidate $recruitment): RedirectResponse
    {
        $validated = $request->validate([
            'scheduled_at' => ['required', 'date'],
            'round' => ['nullable', 'string', 'max:80'],
            'mode' => ['nullable', Rule::in(array_keys(RecruitmentInterview::MODES))],
            'interviewer_id' => ['nullable', 'exists:users,id'],
            'interviewer_name' => ['nullable', 'string', 'max:150'],
            'interview_link' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(array_keys(RecruitmentInterview::STATUSES))],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        if (empty($validated['mode'])) {
            $validated['mode'] = 'phone';
        }

        $interviewerUser = null;
        if (!empty($validated['interviewer_id'])) {
            $interviewerUser = User::find($validated['interviewer_id']);
            if ($interviewerUser) {
                $validated['interviewer_name'] = $interviewerUser->name;
            }
        } elseif (!empty($validated['interviewer_name'])) {
            $interviewerUser = User::where('name', $validated['interviewer_name'])->first();
            if ($interviewerUser) {
                $validated['interviewer_id'] = $interviewerUser->id;
            }
        }

        $interview = $recruitment->interviews()->create(array_merge($validated, [
            'company_id' => auth()->user()?->company_id,
            'scheduled_by' => auth()->id(),
        ]));

        if (!empty($validated['scheduled_at'])) {
            $roundName = !empty($validated['round']) ? $validated['round'] : 'Interview Round';
            RecruitmentReminder::create([
                'company_id' => auth()->user()?->company_id,
                'recruitment_candidate_id' => $recruitment->id,
                'user_id' => !empty($validated['interviewer_id']) ? $validated['interviewer_id'] : auth()->id(),
                'title' => 'Interview (' . $roundName . '): ' . $recruitment->name . ($recruitment->job_title ? ' (' . $recruitment->job_title . ')' : ''),
                'description' => $validated['notes'] ?? null,
                'remind_at' => $validated['scheduled_at'],
                'type' => 'interview',
                'priority' => 'high',
                'is_completed' => false,
            ]);
        }

        if (! in_array($recruitment->status, [
            RecruitmentCandidate::STATUS_SELECTED,
            RecruitmentCandidate::STATUS_REJECTED,
        ], true)) {
            $this->updateCandidateStatus($recruitment, RecruitmentCandidate::STATUS_INTERVIEW_SCHEDULED);
        }

        // Send Notification Email to Interviewer with default CC
        if ($interviewerUser && !empty($interviewerUser->email) && filter_var($interviewerUser->email, FILTER_VALIDATE_EMAIL)) {
            try {
                Mail::send('emails.interview_details', [
                    'interview' => $interview,
                    'candidate' => $recruitment,
                    'interviewer' => $interviewerUser,
                ], function ($message) use ($interviewerUser, $recruitment) {
                    $message->to($interviewerUser->email, $interviewerUser->name)
                        ->cc('tamilarasan@saitechnosolutions.net')
                        ->subject('Interview Scheduled: ' . $recruitment->name . ' - ' . $recruitment->job_title);
                });
            } catch (\Throwable $e) {
                Log::error('Failed to send interview email to interviewer.', [
                    'interviewer_id' => $interviewerUser->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return back()->with('success', 'Interview scheduled successfully.');
    }

    public function updateInterview(Request $request, RecruitmentCandidate $recruitment, RecruitmentInterview $interview): RedirectResponse
    {
        $validated = $request->validate([
            'scheduled_at' => ['required', 'date'],
            'round' => ['nullable', 'string', 'max:80'],
            'mode' => ['nullable', Rule::in(array_keys(RecruitmentInterview::MODES))],
            'interviewer_id' => ['nullable', 'exists:users,id'],
            'interviewer_name' => ['nullable', 'string', 'max:150'],
            'interview_link' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(array_keys(RecruitmentInterview::STATUSES))],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        if (empty($validated['mode'])) {
            $validated['mode'] = 'phone';
        }

        $interviewerUser = null;
        if (!empty($validated['interviewer_id'])) {
            $interviewerUser = User::find($validated['interviewer_id']);
            if ($interviewerUser) {
                $validated['interviewer_name'] = $interviewerUser->name;
            }
        } elseif (!empty($validated['interviewer_name'])) {
            $interviewerUser = User::where('name', $validated['interviewer_name'])->first();
            if ($interviewerUser) {
                $validated['interviewer_id'] = $interviewerUser->id;
            }
        }

        $interview->update($validated);

        // Send Email to Interviewer when rescheduled/updated
        if ($interviewerUser && !empty($interviewerUser->email) && filter_var($interviewerUser->email, FILTER_VALIDATE_EMAIL)) {
            try {
                Mail::send('emails.interview_details', [
                    'interview' => $interview,
                    'candidate' => $recruitment,
                    'interviewer' => $interviewerUser,
                    'isRescheduled' => true,
                ], function ($message) use ($interviewerUser, $recruitment) {
                    $message->to($interviewerUser->email, $interviewerUser->name)
                        ->cc('tamilarasan@saitechnosolutions.net')
                        ->subject('Interview Rescheduled: ' . $recruitment->name . ' - ' . $recruitment->job_title);
                });
            } catch (\Throwable $e) {
                Log::error('Failed to send rescheduled interview email to interviewer.', [
                    'interviewer_id' => $interviewerUser->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return back()->with('success', 'Interview rescheduled successfully.');
    }

    public function updateStatus(Request $request, RecruitmentCandidate $recruitment): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(RecruitmentCandidate::STATUSES))],
        ]);

        $this->updateCandidateStatus($recruitment, $validated['status']);

        return back()->with('success', 'Candidate moved to ' . $recruitment->fresh()->status_label . '.');
    }

    public function destroy(RecruitmentCandidate $recruitment): RedirectResponse
    {
        $name = $recruitment->name;

        if ($recruitment->resume_path && Storage::disk('public')->exists($recruitment->resume_path)) {
            Storage::disk('public')->delete($recruitment->resume_path);
        }

        $recruitment->delete();

        return redirect()
            ->route('recruitment.index')
            ->with('success', "Candidate <strong>{$name}</strong> deleted successfully.");
    }

    private function syncCandidateStatusFromCallOutcome(RecruitmentCandidate $candidate, string $outcome): void
    {
        $status = match ($outcome) {
            'screening', 'interested' => RecruitmentCandidate::STATUS_SHORTLIST,
            'follow_up' => RecruitmentCandidate::STATUS_FOLLOW_UP,
            'no_answer' => RecruitmentCandidate::STATUS_RNR,
            'interview_planned' => RecruitmentCandidate::STATUS_INTERVIEW_SCHEDULED,
            'selected' => RecruitmentCandidate::STATUS_SELECTED,
            'rejected', 'not_interested' => RecruitmentCandidate::STATUS_REJECTED,
            default => null,
        };

        if ($status) {
            $this->updateCandidateStatus($candidate, $status);
        }
    }

    private function updateCandidateStatus(RecruitmentCandidate $candidate, string $status): void
    {
        $candidate->update([
            'status' => $status,
            'updated_by' => auth()->id(),
            'status_updated_at' => now(),
        ]);
    }

    private function currentEmployee(): ?EmployeeOnboarding
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        $query = EmployeeOnboarding::withoutGlobalScopes()->active();
        if ($user->company_id) {
            $query->where('company_id', $user->company_id);
        }

        return $query->where(function ($q) use ($user) {
            $q->where('portal_user_id', $user->id)
                ->orWhere('email', $user->email);
        })
        ->latest('id')
        ->first();
    }
}
