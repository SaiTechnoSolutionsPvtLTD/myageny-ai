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
        $user = auth()->user();
        $canManage = $this->canManageAnnouncements();

        $query = HrmsAnnouncement::query()
            ->visibleForCompany($user?->company_id);

        if (! $canManage) {
            $query->active();
        }

        $announcements = $query
            ->latest('announcement_date')
            ->latest('id')
            ->paginate(12);

        return view('pages.hrms.announcements.index', compact('announcements', 'canManage'));
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

    private function canManageAnnouncements(): bool
    {
        $user = auth()->user();

        return (bool) ($user && ($user->isSystemAdmin() || $user->isCompanyAdmin() || $user->belongsToHrDepartment() || $user->hasHrLikeRole()));
    }

    private function authorizeAnnouncementManagement(): void
    {
        abort_unless($this->canManageAnnouncements(), 403);
    }
}
