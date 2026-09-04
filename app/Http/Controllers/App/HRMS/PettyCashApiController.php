<?php

namespace App\Http\Controllers\App\HRMS;

use App\Http\Controllers\Controller;
use App\Models\PettyCashEntry;
use App\Models\RaniPettyCash;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Mobile API — Petty Cash.
 *
 * Mirrors web's App\Http\Controllers\HRMS\PettyCashController exactly:
 * same two independent ledgers (the DR/CR PettyCashEntry report and the
 * simpler RaniPettyCash side-account), same company scoping
 * (`where('company_id', $companyId)->orWhereNull('company_id')` — note
 * PettyCashEntry/RaniPettyCash do NOT auto-scope via BelongsToCompany the
 * way some other models do, so every query below applies it manually, same
 * as web does), same running-balance algorithm
 * (calculateReportData() below is byte-for-byte the same math as web's),
 * and the same absence of any approval workflow or delete action on the
 * main ledger (PettyCashController has no destroy() for PettyCashEntry —
 * entries are create + edit only; only Rani entries support delete). Web
 * files are untouched — this is a self-contained mirror, matching the
 * existing ExpenseRequestApiController / RecruitmentApiController pattern.
 *
 * Mobile-only additions (do not change web behaviour): (1) `notes` is
 * accepted and returned for Rani entries — the field already exists on the
 * model/validation on web but web's own modal never exposed an input for
 * it, so this fills a pre-existing UI gap without touching web; (2) the
 * report query is defensively capped in scope by requiring a start/end
 * date (mobile always sends one — see PettyCashProvider), keeping typical
 * payload size small without altering the underlying algorithm.
 */
class PettyCashApiController extends Controller
{
    // ── GET /mobile/hrms/petty-cash ──────────────────────────────────────────
    public function report(Request $request): JsonResponse
    {
        try {
            $companyId = Auth::user()?->company_id;

            $startDateInput = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
            $endDateInput = $request->input('end_date', Carbon::now()->toDateString());

            try {
                $startDate = Carbon::parse($startDateInput)->startOfDay();
                $endDate = Carbon::parse($endDateInput)->endOfDay();
            } catch (Throwable) {
                $startDate = Carbon::now()->startOfMonth()->startOfDay();
                $endDate = Carbon::now()->endOfDay();
            }

            if ($startDate->greaterThan($endDate)) {
                [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
            }

            $reportData = $this->calculateReportData($companyId, $startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => array_merge($reportData, [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                ]),
            ]);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Unable to load the petty cash report. Please try again.',
            ], 500);
        }
    }

    /**
     * Identical math to web's PettyCashController::calculateReportData() —
     * see that file for the source of truth this mirrors.
     */
    private function calculateReportData($companyId, Carbon $startDate, Carbon $endDate): array
    {
        $priorQuery = PettyCashEntry::query();
        if ($companyId) {
            $priorQuery->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->orWhereNull('company_id');
            });
        }

        $priorCredits = (float) (clone $priorQuery)->where('entry_date', '<', $startDate->toDateString())
            ->whereIn('type', ['credit', 'cash_in_hand'])
            ->sum('amount');

        $priorDebits = (float) (clone $priorQuery)->where('entry_date', '<', $startDate->toDateString())
            ->where('type', 'debit')
            ->sum('amount');

        $openingBalance = $priorCredits - $priorDebits;

        $txQuery = PettyCashEntry::with('creator');
        if ($companyId) {
            $txQuery->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->orWhereNull('company_id');
            });
        }

        $entries = $txQuery->whereDate('entry_date', '>=', $startDate->toDateString())
            ->whereDate('entry_date', '<=', $endDate->toDateString())
            ->orderBy('entry_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $runningBalance = $openingBalance;
        $totalDebit = 0.00;
        $totalCredit = 0.00;

        $transactions = [];
        foreach ($entries as $entry) {
            $isCredit = in_array($entry->type, ['credit', 'cash_in_hand']);
            $debit = $entry->type === 'debit' ? (float) $entry->amount : 0.00;
            $credit = $isCredit ? (float) $entry->amount : 0.00;

            $totalDebit += $debit;
            $totalCredit += $credit;
            $runningBalance += ($credit - $debit);

            $transactions[] = [
                'id' => $entry->id,
                'entry_date' => optional($entry->entry_date)->format('Y-m-d'),
                'entry_date_formatted' => optional($entry->entry_date)->format('d M Y'),
                'voucher_no' => $entry->voucher_no ?: '-',
                'name' => $entry->name ?: '-',
                'particulars' => $entry->particulars,
                'type' => $entry->type,
                'type_label' => $entry->type === 'cash_in_hand' ? 'Cash In Hand' : ($entry->type === 'credit' ? 'Credit' : 'Debit'),
                'amount' => (float) $entry->amount,
                'debit' => $debit,
                'credit' => $credit,
                'running_balance' => $runningBalance,
                'creator_name' => $entry->creator?->name ?: 'System',
            ];
        }

        $closingBalance = $openingBalance + $totalCredit - $totalDebit;

        return [
            'openingBalance' => $openingBalance,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'closingBalance' => $closingBalance,
            'transactions' => $transactions,
        ];
    }

    // ── POST /mobile/hrms/petty-cash ─────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'branch_id' => ['nullable', 'exists:branches,id'],
                'entry_date' => ['required', 'date'],
                'voucher_no' => ['nullable', 'string', 'max:100'],
                'name' => ['nullable', 'string', 'max:255'],
                'particulars' => ['required', 'string', 'max:255'],
                'type' => ['required', 'in:cash_in_hand,credit,debit'],
                'amount' => ['required', 'numeric', 'min:0.01'],
            ]);

            $entry = PettyCashEntry::create([
                'company_id' => Auth::user()?->company_id,
                'branch_id' => $validated['branch_id'] ?? null,
                'entry_date' => $validated['entry_date'],
                'voucher_no' => $validated['voucher_no'] ?? null,
                'name' => $validated['name'] ?? null,
                'particulars' => $validated['particulars'],
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'created_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Petty cash transaction recorded successfully.',
                'data' => ['id' => $entry->id],
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first() ?: 'Please check the form and try again.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Unable to record the transaction. Please try again.',
            ], 500);
        }
    }

    // ── PUT /mobile/hrms/petty-cash/{entry} ──────────────────────────────────
    public function update(Request $request, PettyCashEntry $entry): JsonResponse
    {
        try {
            $companyId = Auth::user()?->company_id;
            if ($companyId && $entry->company_id && (int) $entry->company_id !== (int) $companyId) {
                return response()->json(['success' => false, 'message' => 'Transaction not found.'], 404);
            }

            $validated = $request->validate([
                'entry_date' => ['required', 'date'],
                'voucher_no' => ['nullable', 'string', 'max:100'],
                'name' => ['nullable', 'string', 'max:255'],
                'particulars' => ['required', 'string', 'max:255'],
                'type' => ['required', 'in:cash_in_hand,credit,debit'],
                'amount' => ['required', 'numeric', 'min:0.01'],
            ]);

            $entry->update([
                'entry_date' => $validated['entry_date'],
                'voucher_no' => $validated['voucher_no'] ?? null,
                'name' => $validated['name'] ?? null,
                'particulars' => $validated['particulars'],
                'type' => $validated['type'],
                'amount' => $validated['amount'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Petty cash transaction updated successfully.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first() ?: 'Please check the form and try again.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Unable to update the transaction. Please try again.',
            ], 500);
        }
    }

    // ── GET /mobile/hrms/petty-cash/rani ─────────────────────────────────────
    public function raniIndex(Request $request): JsonResponse
    {
        try {
            $companyId = Auth::user()?->company_id;

            $entries = RaniPettyCash::query()
                ->with('user')
                ->when($companyId, fn ($q) => $q->where(fn ($q2) => $q2->where('company_id', $companyId)->orWhereNull('company_id')))
                ->orderBy('entry_date', 'desc')
                ->orderBy('id', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $entries->map(fn (RaniPettyCash $r) => $this->formatRani($r))->values(),
            ]);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Unable to load Rani entries. Please try again.',
            ], 500);
        }
    }

    // ── POST /mobile/hrms/petty-cash/rani ────────────────────────────────────
    public function raniStore(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'entry_date' => 'required|date',
                'amount'     => 'required|numeric|min:0.01',
                'notes'      => 'nullable|string|max:1000',
            ]);

            $entry = RaniPettyCash::create([
                'company_id' => Auth::user()?->company_id,
                'user_id'    => Auth::id(),
                'entry_date' => $validated['entry_date'],
                'amount'     => $validated['amount'],
                'notes'      => $validated['notes'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Rani entry added successfully.',
                'data' => $this->formatRani($entry),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first() ?: 'Please check the form and try again.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Unable to add the Rani entry. Please try again.',
            ], 500);
        }
    }

    // ── PUT /mobile/hrms/petty-cash/rani/{raniEntry} ─────────────────────────
    public function raniUpdate(Request $request, RaniPettyCash $raniEntry): JsonResponse
    {
        try {
            $validated = $request->validate([
                'entry_date' => 'required|date',
                'amount'     => 'required|numeric|min:0.01',
                'notes'      => 'nullable|string|max:1000',
            ]);

            $raniEntry->update([
                'entry_date' => $validated['entry_date'],
                'amount'     => $validated['amount'],
                'notes'      => $validated['notes'] ?? $raniEntry->notes,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Rani entry updated successfully.',
                'data' => $this->formatRani($raniEntry),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first() ?: 'Please check the form and try again.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Unable to update the Rani entry. Please try again.',
            ], 500);
        }
    }

    // ── DELETE /mobile/hrms/petty-cash/rani/{raniEntry} ──────────────────────
    public function raniDestroy(RaniPettyCash $raniEntry): JsonResponse
    {
        try {
            $raniEntry->delete();

            return response()->json([
                'success' => true,
                'message' => 'Rani entry deleted successfully.',
            ]);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Unable to delete the Rani entry. Please try again.',
            ], 500);
        }
    }

    private function formatRani(RaniPettyCash $r): array
    {
        return [
            'id' => $r->id,
            'entry_date' => optional($r->entry_date)->format('Y-m-d'),
            'entry_date_formatted' => optional($r->entry_date)->format('d M Y'),
            'amount' => (float) $r->amount,
            'notes' => $r->notes,
            'created_by_name' => $r->user?->name,
        ];
    }
}
