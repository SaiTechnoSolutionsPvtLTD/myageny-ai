<?php

namespace App\Http\Controllers;

use App\Models\Branch;
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
            ->with(['creator', 'updater'])
            ->visibleForCompany($user?->company_id);

        if (! $canManage) {
            $query->visibleForUser($user)->active();
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

        $companyId = auth()->user()?->company_id;
        $branches = Branch::query()
            ->where('company_id', $companyId)
            ->active()
            ->orderBy('name')
            ->get();

        return view('pages.hrms.announcements.create', compact('branches'));
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
            'target_type' => ['nullable', 'in:all,specific'],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['integer', 'exists:branches,id'],
        ]);

        $branchIds = null;
        if (($validated['target_type'] ?? 'all') === 'specific' && !empty($validated['branch_ids'])) {
            $branchIds = array_values(array_unique(array_map('intval', $validated['branch_ids'])));
        }

        HrmsAnnouncement::create([
            'company_id' => auth()->user()?->company_id,
            'branch_ids' => $branchIds,
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

    public function edit(HrmsAnnouncement $announcement): View
    {
        $this->authorizeAnnouncementManagement();
        $this->authorizeCompanyOwnership($announcement);

        $companyId = auth()->user()?->company_id;
        $branches = Branch::query()
            ->where('company_id', $companyId)
            ->active()
            ->orderBy('name')
            ->get();

        return view('pages.hrms.announcements.edit', compact('announcement', 'branches'));
    }

    public function update(Request $request, HrmsAnnouncement $announcement): RedirectResponse
    {
        $this->authorizeAnnouncementManagement();
        $this->authorizeCompanyOwnership($announcement);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:2000'],
            'priority' => ['required', 'in:high,medium,low'],
            'announcement_date' => ['required', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'target_type' => ['nullable', 'in:all,specific'],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['integer', 'exists:branches,id'],
        ]);

        $branchIds = null;
        if (($validated['target_type'] ?? 'all') === 'specific' && !empty($validated['branch_ids'])) {
            $branchIds = array_values(array_unique(array_map('intval', $validated['branch_ids'])));
        }

        $announcement->update([
            'branch_ids' => $branchIds,
            'title' => $validated['title'],
            'message' => $validated['message'],
            'priority' => $validated['priority'],
            'announcement_date' => $validated['announcement_date'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->route('hrms-announcements.index')
            ->with('success', 'Announcement updated successfully.');
    }

    public function destroy(HrmsAnnouncement $announcement): RedirectResponse
    {
        $this->authorizeAnnouncementManagement();
        $this->authorizeCompanyOwnership($announcement);

        $title = $announcement->title;
        $announcement->delete();

        return redirect()
            ->route('hrms-announcements.index')
            ->with('success', "Announcement \"{$title}\" deleted successfully.");
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

    private function authorizeCompanyOwnership(HrmsAnnouncement $announcement): void
    {
        $user = auth()->user();
        if ($user && ! $user->isSystemAdmin() && $announcement->company_id && (int) $announcement->company_id !== (int) $user->company_id) {
            abort(403, 'Unauthorized access to this announcement.');
        }
    }
}
