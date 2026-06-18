<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\ProductionInitiation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ProductionApprovalApiController extends Controller
{
    public function index(Request $request): JsonResponse
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
            'pending'  => ['items' => [], 'count' => 0],
            'approval' => ['items' => [], 'count' => 0],
            'rejected' => ['items' => [], 'count' => 0],
        ];

        foreach ($initiations as $initiation) {
            $bucket = $this->resolveBucket((string) $initiation->production_approval_status);
            if (! $bucket) continue;

            $buckets[$bucket]['items'][] = $this->formatItem($initiation);
            $buckets[$bucket]['count']++;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'buckets' => $buckets,
                'counts'  => [
                    'pending'  => $buckets['pending']['count'],
                    'approval' => $buckets['approval']['count'],
                    'rejected' => $buckets['rejected']['count'],
                ],
            ],
        ]);
    }

    public function review(Request $request, ProductionInitiation $productionInitiation): JsonResponse
    {
        if (! $this->canReview($productionInitiation)) {
            return response()->json(['success' => false, 'message' => 'You are not allowed to review this item.'], 403);
        }

        $validated = $request->validate([
            'decision'                    => ['required', 'in:approval,rejected'],
            'production_approval_remarks' => ['required', 'string', 'max:5000'],
        ]);

        $productionInitiation->update([
            'production_approval_status'       => $validated['decision'],
            'production_approval_remarks'      => trim($validated['production_approval_remarks']),
            'production_approval_reviewed_at'  => Carbon::now(),
            'production_approval_reviewed_by'  => auth()->id(),
            'project_allocation_status'        => $validated['decision'] === 'approval' ? 'allocation_pending' : null,
            'project_allocated_at'             => null,
            'project_allocated_by'             => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => $validated['decision'] === 'approval'
                ? 'Production approval completed successfully.'
                : 'Production approval item moved to rejected.',
        ]);
    }

    private function formatItem(ProductionInitiation $i): array
    {
        $customFormData = [];
        if (is_array($i->custom_form_data)) {
            foreach ($i->custom_form_data as $field) {
                $label = $field['label'] ?? ($field['key'] ?? '');
                $value = $field['value'] ?? '';
                if ($label) {
                    $customFormData[] = [
                        'label'    => $label,
                        'value'    => is_array($value) ? implode(', ', $value) : (string) $value,
                        'is_file'  => ($field['type'] ?? '') === 'file',
                        'file_url' => ($field['type'] ?? '') === 'file' ? ($field['file_url'] ?? null) : null,
                    ];
                }
            }
        }

        return [
            'id'                              => $i->id,
            'product_name'                    => $i->product_name ?? '',
            'total_working_days'              => $i->total_working_days ?? 0,
            'department'                      => $i->department?->name ?? '',
            'company_name'                    => $i->lead?->company_name ?? $i->company_name ?? '',
            'client_name'                     => $i->lead?->contact_name ?? $i->client_name ?? '',
            'production_approval_status'      => $i->production_approval_status,
            'bucket'                          => $this->resolveBucket((string) $i->production_approval_status),
            'ovp_reviewed_by'                 => $i->reviewedBy?->name,
            'ovp_reviewed_at'                 => $i->reviewed_at?->toIso8601String(),
            'actioned_by'                     => $i->productionApprovalReviewedBy?->name,
            'actioned_at'                     => $i->production_approval_reviewed_at?->toIso8601String(),
            'approval_remarks'                => $i->production_approval_remarks,
            'custom_form_data'                => $customFormData,
            'can_review'                      => $this->canReview($i),
        ];
    }

    private function resolveBucket(string $status): ?string
    {
        return match (strtolower(trim($status))) {
            'pending'              => 'pending',
            'approval', 'approved' => 'approval',
            'rejected', 'reject'   => 'rejected',
            default                => null,
        };
    }

    private function canReview(ProductionInitiation $i): bool
    {
        return in_array(strtolower(trim((string) $i->status)), ['approval', 'approved'], true)
            && strtolower(trim((string) $i->production_approval_status)) === 'pending';
    }
}