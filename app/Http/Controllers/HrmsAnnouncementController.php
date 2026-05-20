<?php

namespace App\Http\Controllers;

use App\Models\HrmsAnnouncement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HrmsAnnouncementController extends Controller
{
    public function index(): View
    {
        $this->authorizeAnnouncementManagement();

        $announcements = HrmsAnnouncement::query()
            ->visibleForCompany(auth()->user()?->company_id)
            ->latest('announcement_date')
            ->latest('id')
            ->paginate(12);

        return view('pages.hrms.announcements.index', compact('announcements'));
    }

    public function create(): View
    {
        $this->authorizeAnnouncementManagement();

        return view('pages.hrms.announcements.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAnnouncementManagement();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:2000'],
            'priority' => ['required', 'in:high,medium,low'],
            'announcement_date' => ['required', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        HrmsAnnouncement::create([
            'company_id' => auth()->user()?->company_id,
            'title' => $validated['title'],
            'message' => $validated['message'],
            'priority' => $validated['priority'],
            'announcement_date' => $validated['announcement_date'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->route('hrms-announcements.index')
            ->with('success', 'Announcement created successfully.');
    }

    private function authorizeAnnouncementManagement(): void
    {
        $user = auth()->user();

        abort_unless($user && ($user->belongsToHrDepartment() || $user->hasHrLikeRole()), 403);
    }
}
