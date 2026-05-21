<?php

namespace App\Http\Controllers;

use App\Http\Requests\HolidayCalendarRequest;
use App\Models\HolidayCalendar;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use SimpleXMLElement;
use SplFileObject;
use ZipArchive;

class HolidayCalendarController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = auth()->user()?->company_id;
        $selectedMonth = $request->input('month', now()->format('Y-m'));
        $calendarMonth = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();

        $holidays = HolidayCalendar::query()
            ->ownedByCompany($companyId)
            ->when($request->search, function ($query) use ($request) {
                $search = trim((string) $request->search);

                $query->where('reason', 'like', '%' . $search . '%');
            })
            ->when($request->filled('month'), function ($query) use ($calendarMonth) {
                $query->whereBetween('holiday_date', [$calendarMonth->copy()->startOfMonth(), $calendarMonth->copy()->endOfMonth()]);
            })
            ->orderBy('holiday_date')
            ->paginate(12)
            ->withQueryString();

        $calendarHolidays = HolidayCalendar::query()
            ->ownedByCompany($companyId)
            ->whereBetween('holiday_date', [$calendarMonth->copy()->startOfMonth(), $calendarMonth->copy()->endOfMonth()])
            ->orderBy('holiday_date')
            ->get()
            ->groupBy(fn (HolidayCalendar $holiday) => $holiday->holiday_date->format('Y-m-d'));

        $calendarStart = $calendarMonth->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY);
        $calendarEnd = $calendarMonth->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);
        $calendarDays = collect();

        for ($date = $calendarStart->copy(); $date->lte($calendarEnd); $date->addDay()) {
            $calendarDays->push($date->copy());
        }

        return view('pages.settings.holiday_calendar.index', [
            'holidays' => $holidays,
            'calendarMonth' => $calendarMonth,
            'calendarDays' => $calendarDays,
            'calendarHolidays' => $calendarHolidays,
        ]);
    }

    public function create(): View
    {
        return view('pages.settings.holiday_calendar.create');
    }

    public function store(HolidayCalendarRequest $request): RedirectResponse
    {
        $holiday = HolidayCalendar::create($request->validated());

        return redirect()
            ->route('settings.holiday-calendars.index')
            ->with('success', "Holiday for {$holiday->holiday_date->format('d M Y')} created successfully.");
    }

    public function edit(HolidayCalendar $holiday_calendar): View
    {
        $this->authorizeCompanyOwnership($holiday_calendar);

        return view('pages.settings.holiday_calendar.edit', [
            'holiday' => $holiday_calendar,
        ]);
    }

    public function update(HolidayCalendarRequest $request, HolidayCalendar $holiday_calendar): RedirectResponse
    {
        $this->authorizeCompanyOwnership($holiday_calendar);
        $holiday_calendar->update($request->validated());

        return redirect()
            ->route('settings.holiday-calendars.index')
            ->with('success', "Holiday for {$holiday_calendar->holiday_date->format('d M Y')} updated successfully.");
    }

    public function destroy(HolidayCalendar $holiday_calendar): RedirectResponse
    {
        $this->authorizeCompanyOwnership($holiday_calendar);
        $date = $holiday_calendar->holiday_date->format('d M Y');
        $holiday_calendar->delete();

        return redirect()
            ->route('settings.holiday-calendars.index')
            ->with('success', "Holiday for {$date} deleted successfully.");
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'import_file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:5120'],
        ]);

        $rows = $this->parseImportFile($request->file('import_file')->getRealPath(), $request->file('import_file')->getClientOriginalExtension());

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages([
                'import_file' => 'The uploaded file does not contain any holiday rows.',
            ]);
        }

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($rows, &$created, &$updated) {
            $companyId = auth()->user()?->company_id;

            foreach ($rows as $row) {
                $holiday = HolidayCalendar::withTrashed()->ownedByCompany($companyId)->firstOrNew([
                    'holiday_date' => $row['holiday_date'],
                    'company_id' => $companyId,
                ]);

                $wasExisting = $holiday->exists;

                if ($holiday->trashed()) {
                    $holiday->restore();
                }

                $holiday->reason = $row['reason'];
                $holiday->save();

                if ($wasExisting) {
                    $updated++;
                } else {
                    $created++;
                }
            }
        });

        return redirect()
            ->route('settings.holiday-calendars.index', ['month' => now()->format('Y-m')])
            ->with('success', "{$created} holiday(s) imported and {$updated} holiday(s) updated successfully.");
    }

    private function authorizeCompanyOwnership(HolidayCalendar $holiday): void
    {
        $companyId = auth()->user()?->company_id;

        if ($companyId === null) {
            return;
        }

        abort_unless((int) $holiday->company_id === (int) $companyId, 403);
    }

    private function parseImportFile(string $path, string $extension): Collection
    {
        $extension = strtolower($extension);

        return match ($extension) {
            'csv', 'txt' => $this->parseCsv($path),
            'xlsx' => $this->parseXlsx($path),
            default => throw ValidationException::withMessages([
                'import_file' => 'Only CSV and XLSX files are supported.',
            ]),
        };
    }

    private function parseCsv(string $path): Collection
    {
        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);

        $headers = null;
        $rows = collect();

        foreach ($file as $row) {
            if (! is_array($row) || $row === [null] || $row === false) {
                continue;
            }

            $row = array_map(fn ($value) => is_string($value) ? trim($value) : $value, $row);

            if ($headers === null) {
                $headers = $this->normalizeHeaders($row);
                continue;
            }

            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $mapped = $this->mapImportRow($headers, $row);
            if ($mapped !== null) {
                $rows->push($mapped);
            }
        }

        return $rows;
    }

    private function parseXlsx(string $path): Collection
    {
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            throw ValidationException::withMessages([
                'import_file' => 'The uploaded XLSX file could not be opened.',
            ]);
        }

        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            throw ValidationException::withMessages([
                'import_file' => 'The uploaded XLSX file does not contain the first worksheet.',
            ]);
        }

        $sharedStrings = $this->parseSharedStrings($sharedStringsXml ?: '');
        $worksheet = new SimpleXMLElement($sheetXml);
        $worksheet->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $headers = null;
        $rows = collect();

        foreach ($worksheet->xpath('//main:sheetData/main:row') ?: [] as $rowNode) {
            $cells = [];

            foreach ($rowNode->c as $cell) {
                $ref = (string) $cell['r'];
                $column = preg_replace('/\d+/', '', $ref);
                $value = $this->extractSpreadsheetCellValue($cell, $sharedStrings);
                $cells[$column] = $value;
            }

            if ($cells === []) {
                continue;
            }

            ksort($cells);
            $row = array_values($cells);

            if ($headers === null) {
                $headers = $this->normalizeHeaders($row);
                continue;
            }

            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $mapped = $this->mapImportRow($headers, $row);
            if ($mapped !== null) {
                $rows->push($mapped);
            }
        }

        return $rows;
    }

    private function parseSharedStrings(string $xml): array
    {
        if ($xml === '') {
            return [];
        }

        $sharedStringsXml = new SimpleXMLElement($xml);
        $sharedStringsXml->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $values = [];

        foreach ($sharedStringsXml->xpath('//main:si') ?: [] as $stringItem) {
            $textParts = [];

            foreach ($stringItem->xpath('.//main:t') ?: [] as $textNode) {
                $textParts[] = (string) $textNode;
            }

            $values[] = implode('', $textParts);
        }

        return $values;
    }

    private function extractSpreadsheetCellValue(SimpleXMLElement $cell, array $sharedStrings): string
    {
        $type = (string) $cell['t'];
        $value = isset($cell->v) ? (string) $cell->v : '';

        if ($type === 's') {
            return (string) ($sharedStrings[(int) $value] ?? '');
        }

        if (is_numeric($value) && str_contains($value, '.') === false) {
            $numericValue = (int) $value;

            if ($numericValue > 30000 && $numericValue < 60000) {
                return Carbon::create(1899, 12, 30)->addDays($numericValue)->format('Y-m-d');
            }
        }

        return trim($value);
    }

    private function normalizeHeaders(array $headers): array
    {
        return array_map(function ($header) {
            $header = Str::of((string) $header)->lower()->replace(['.', '-', '/'], ' ')->squish()->value();

            return match ($header) {
                'holiday date', 'date', 'holidaydate' => 'holiday_date',
                'reason', 'reason for holiday', 'holiday reason' => 'reason',
                default => $header,
            };
        }, $headers);
    }

    private function mapImportRow(array $headers, array $row): ?array
    {
        $values = array_pad($row, count($headers), null);
        $mapped = array_combine($headers, $values);

        if (! is_array($mapped)) {
            return null;
        }

        $holidayDate = $this->normalizeImportDate($mapped['holiday_date'] ?? $mapped['date'] ?? null);
        $reason = trim((string) ($mapped['reason'] ?? ''));

        if (! $holidayDate || $reason === '') {
            return null;
        }

        return [
            'holiday_date' => $holidayDate,
            'reason' => Str::limit($reason, 255, ''),
        ];
    }

    private function normalizeImportDate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
