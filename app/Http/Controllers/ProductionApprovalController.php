<?php

namespace App\Http\Controllers;

use App\Models\ProductionInitiation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ProductionApprovalController extends Controller
{
    public function index(Request $request): View
    {
        $initiations = ProductionInitiation::query()
            ->with([
                'lead:id,company_name,contact_name',
                'department:id,name',
                'reviewedBy:id,name',
                'productionApprovalReviewedBy:id,name',
            ])
            ->whereIn('status', ['approval', 'approved'])
            ->whereIn('production_approval_status', ['pending', 'approval', 'approved', 'rejected', 'reject'])
            ->latest()
            ->get();

        $buckets = [
            'pending' => [
                'title' => 'Pending',
                'status_label' => 'Pending',
                'count' => 0,
                'items' => collect(),
            ],
            'approval' => [
                'title' => 'Approval',
                'status_label' => 'Approval',
                'count' => 0,
                'items' => collect(),
            ],
            'rejected' => [
                'title' => 'Rejects',
                'status_label' => 'Rejected',
                'count' => 0,
                'items' => collect(),
            ],
        ];

        foreach ($initiations as $initiation) {
            $bucket = $this->resolveBucket((string) $initiation->production_approval_status);

            if (! $bucket) {
                continue;
            }

            /** @var Collection $items */
            $items = $buckets[$bucket]['items'];
            $items->push($initiation);

            $buckets[$bucket]['items'] = $items;
            $buckets[$bucket]['count']++;
        }

        $selectedBucket = (string) $request->query('bucket', 'pending');

        if (! array_key_exists($selectedBucket, $buckets)) {
            $selectedBucket = 'pending';
        }

        return view('pages.production_approvals.index', [
            'cards' => $buckets,
            'selectedBucket' => $selectedBucket,
            'selectedCard' => $buckets[$selectedBucket],
        ]);
    }

    public function review(Request $request, ProductionInitiation $productionInitiation): RedirectResponse
    {
        abort_unless($this->canReview($productionInitiation), 403);

        $validated = $request->validate([
            'decision' => ['required', 'in:approval,rejected'],
            'production_approval_remarks' => ['required', 'string', 'max:5000'],
        ]);

        $productionInitiation->update([
            'production_approval_status' => $validated['decision'],
            'production_approval_remarks' => trim($validated['production_approval_remarks']),
            'production_approval_reviewed_at' => Carbon::now(),
            'production_approval_reviewed_by' => auth()->id(),
            'project_allocation_status' => $validated['decision'] === 'approval' ? 'allocation_pending' : null,
            'project_allocated_at' => null,
            'project_allocated_by' => null,
        ]);

        return redirect()
            ->route('production-approvals.index', ['bucket' => $validated['decision'] === 'approval' ? 'approval' : 'rejected'])
            ->with('success', $validated['decision'] === 'approval'
                ? 'Production approval completed successfully.'
                : 'Production approval item moved to rejected.');
    }

    private function resolveBucket(string $status): ?string
    {
        return match (strtolower(trim($status))) {
            'pending' => 'pending',
            'approval', 'approved' => 'approval',
            'rejected', 'reject' => 'rejected',
            default => null,
        };
    }

    private function canReview(ProductionInitiation $productionInitiation): bool
    {
        $ovpStatus = strtolower(trim((string) $productionInitiation->status));
        $productionApprovalStatus = strtolower(trim((string) $productionInitiation->production_approval_status));

        return in_array($ovpStatus, ['approval', 'approved'], true)
            && $productionApprovalStatus === 'pending';
    }
}
