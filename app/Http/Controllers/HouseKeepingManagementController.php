<?php

namespace App\Http\Controllers;

use App\Models\HouseKeepingCategory;
use App\Models\HouseKeepingCompletion;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HouseKeepingManagementController extends Controller
{
    public function index(Request $request): View
    {
        $selectedMonth = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', (string) $request->month)->startOfMonth()
            : now()->startOfMonth();

        $categories = HouseKeepingCategory::query()
            ->with(['works' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $completedKeys = HouseKeepingCompletion::query()
            ->whereBetween('completed_date', [
                $selectedMonth->copy()->startOfMonth()->toDateString(),
                $selectedMonth->copy()->endOfMonth()->toDateString(),
            ])
            ->get(['house_keeping_work_id', 'completed_date'])
            ->map(fn (HouseKeepingCompletion $completion) => $completion->house_keeping_work_id . '|' . $completion->completed_date->format('Y-m-d'))
            ->flip();

        $completionTotals = HouseKeepingCompletion::query()
            ->whereBetween('completed_date', [
                $selectedMonth->copy()->startOfMonth()->toDateString(),
                $selectedMonth->copy()->endOfMonth()->toDateString(),
            ])
            ->selectRaw('house_keeping_work_id, COUNT(*) as total')
            ->groupBy('house_keeping_work_id')
            ->pluck('total', 'house_keeping_work_id');

        return view('pages.hrms.house-keeping.index', [
            'categories' => $categories,
            'selectedMonth' => $selectedMonth,
            'completedKeys' => $completedKeys,
            'completionTotals' => $completionTotals,
            'days' => collect(range(1, $selectedMonth->daysInMonth))
                ->map(fn (int $day) => $selectedMonth->copy()->day($day)),
        ]);
    }

    public function updateCompletion(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'house_keeping_work_id' => ['required', 'exists:house_keeping_works,id'],
            'completed_date' => ['required', 'date'],
            'completed' => ['required', 'boolean'],
        ]);

        if ($validated['completed']) {
            HouseKeepingCompletion::updateOrCreate(
                [
                    'house_keeping_work_id' => $validated['house_keeping_work_id'],
                    'completed_date' => $validated['completed_date'],
                ],
                [
                    'completed_by' => auth()->id(),
                ]
            );
        } else {
            HouseKeepingCompletion::query()
                ->where('house_keeping_work_id', $validated['house_keeping_work_id'])
                ->whereDate('completed_date', $validated['completed_date'])
                ->delete();
        }

        return response()->json([
            'saved' => true,
        ]);
    }
}
