<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Lead;
use App\Models\LeadProduct;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\Product;
use App\Models\User;
use App\Services\DataVisibilityService;
use Carbon\Carbon;
use DOMDocument;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use SimpleXMLElement;
use SplFileObject;
use ZipArchive;

class LeadImportController extends Controller
{
    public function __construct(protected DataVisibilityService $visibility)
    {
    }

    public function index()
    {
        $companyId = auth()->user()?->company_id;

        $companies = Company::orderBy('company_name')->get(['id', 'company_name']);

        $products = Product::query()
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->where('status', 'active')
            ->orderBy('package_name')
            ->get(['id', 'package_name', 'product_name', 'final_price', 'base_price']);

        if ($products->isEmpty()) {
            $products = Product::query()
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->orderBy('id', 'desc')
                ->get(['id', 'package_name', 'product_name', 'final_price', 'base_price']);
        }

        $salesUsers = $this->visibility->visibleAssignableUsers()
            ->reject(fn ($u) => $u->hasPreSalesLikeRole())
            ->values();

        if ($salesUsers->isEmpty()) {
            $salesUsers = User::query()
                ->where('user_status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'email']);
        }

        $preSaleUsers = User::query()
            ->where('user_status', 'active')
            ->where(function ($q) {
                $q->whereHas('roles', fn ($rq) => $rq->where('name', 'like', '%pre_sale%')->orWhere('display_name', 'like', '%pre%sale%'))
                  ->orWhereIn('id', Lead::query()->whereNotNull('pre_sale_executive_id')->distinct()->pluck('pre_sale_executive_id'));
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        if ($preSaleUsers->isEmpty()) {
            $preSaleUsers = User::query()
                ->where('user_status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'email']);
        }

        $leadSources = LeadSource::query()->orderBy('name')->get();
        $leadStatuses = LeadStatus::query()->orderBy('name')->get();

        return view('pages.settings.lead-import.index', compact(
            'companies',
            'products',
            'salesUsers',
            'preSaleUsers',
            'leadSources',
            'leadStatuses'
        ));
    }

    public function parseFile(Request $request)
    {
        $request->validate([
            'import_file'           => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:10240'],
            'company_id'            => ['nullable', 'exists:companies,id'],
            'product_id'            => ['nullable', 'exists:products,id'],
            'assigned_to'           => ['nullable', 'exists:users,id'],
            'pre_sale_executive_id' => ['nullable', 'exists:users,id'],
            'lead_source_id'        => ['nullable', 'exists:lead_sources,id'],
            'lead_status_id'        => ['nullable', 'exists:lead_statuses,id'],
        ]);

        $file = $request->file('import_file');
        $ext = strtolower($file->getClientOriginalExtension());
        $tempFilename = 'lead_import_' . auth()->id() . '_' . time() . '.' . $ext;
        $tempPath = $file->storeAs('temp_imports', $tempFilename);
        $fullPath = storage_path('app/private/' . $tempPath);

        if (!file_exists($fullPath)) {
            $fullPath = storage_path('app/' . $tempPath);
        }

        try {
            $parsedData = $this->extractFileContent($fullPath, $ext);
        } catch (Exception $e) {
            return back()->with('error', 'Error reading uploaded file: ' . $e->getMessage());
        }

        if (empty($parsedData['headers']) || empty(array_filter($parsedData['headers'], fn($h) => trim((string)$h) !== ''))) {
            return back()->with('error', 'The uploaded file contains no valid column headers.');
        }

        $headers = $parsedData['headers'];
        $previewRows = array_slice($parsedData['rows'], 0, 5);
        $totalRows = count($parsedData['rows']);

        $autoMappings = $this->autoMatchColumns($headers);

        $targetCompany = $request->company_id ? Company::find($request->company_id) : null;
        $product = $request->product_id ? Product::find($request->product_id) : null;
        $salesUser = $request->assigned_to ? User::find($request->assigned_to) : null;
        $preSaleUser = $request->pre_sale_executive_id ? User::find($request->pre_sale_executive_id) : null;
        $leadSource = $request->lead_source_id ? LeadSource::find($request->lead_source_id) : null;
        $leadStatus = $request->lead_status_id ? LeadStatus::find($request->lead_status_id) : null;

        return view('pages.settings.lead-import.index', [
            'step'                  => 2,
            'tempPath'              => $tempPath,
            'headers'               => $headers,
            'previewRows'           => $previewRows,
            'totalRows'             => $totalRows,
            'autoMappings'          => $autoMappings,
            'targetCompany'         => $targetCompany,
            'product'               => $product,
            'salesUser'             => $salesUser,
            'preSaleUser'           => $preSaleUser,
            'leadSource'            => $leadSource,
            'leadStatus'            => $leadStatus,
            'company_id'            => $request->company_id,
            'product_id'            => $request->product_id,
            'assigned_to'           => $request->assigned_to,
            'pre_sale_executive_id' => $request->pre_sale_executive_id,
            'lead_source_id'        => $request->lead_source_id,
            'lead_status_id'        => $request->lead_status_id,
            'companies'             => Company::orderBy('company_name')->get(['id', 'company_name']),
            'products'              => Product::orderBy('package_name')->get(['id', 'package_name', 'product_name']),
            'salesUsers'            => User::where('user_status', 'active')->orderBy('name')->get(['id', 'name']),
            'preSaleUsers'          => User::where('user_status', 'active')->orderBy('name')->get(['id', 'name']),
            'leadSources'           => LeadSource::orderBy('name')->get(),
            'leadStatuses'          => LeadStatus::orderBy('name')->get(),
        ]);
    }

    public function processImport(Request $request)
    {
        $request->validate([
            'temp_path'             => ['required', 'string'],
            'mappings'              => ['required', 'array'],
            'company_id'            => ['nullable', 'exists:companies,id'],
            'product_id'            => ['nullable', 'exists:products,id'],
            'assigned_to'           => ['nullable', 'exists:users,id'],
            'pre_sale_executive_id' => ['nullable', 'exists:users,id'],
            'lead_source_id'        => ['nullable', 'exists:lead_sources,id'],
            'lead_status_id'        => ['nullable', 'exists:lead_statuses,id'],
        ]);

        $tempPath = $request->temp_path;
        $fullPath = storage_path('app/private/' . $tempPath);
        if (!file_exists($fullPath)) {
            $fullPath = storage_path('app/' . $tempPath);
        }

        if (!file_exists($fullPath)) {
            return redirect()->route('settings.lead-import.index')->with('error', 'Import session expired or file not found. Please upload again.');
        }

        $ext = pathinfo($fullPath, PATHINFO_EXTENSION);
        $parsedData = $this->extractFileContent($fullPath, $ext);
        $rows = $parsedData['rows'];
        $mappings = $request->mappings; // [crm_field => col_index]

        if (empty($rows)) {
            return redirect()->route('settings.lead-import.index')->with('error', 'No data rows found in uploaded file.');
        }

        $companyId = $request->company_id ?: auth()->user()?->company_id;
        $productId = $request->product_id;
        $product = $productId ? Product::find($productId) : null;
        $assignedTo = $request->assigned_to;
        $preSaleExecId = $request->pre_sale_executive_id;
        $defaultSourceId = $request->lead_source_id;
        $defaultStatusId = $request->lead_status_id ?: LeadStatus::where('name', 'New')->first()?->id ?: 1;

        $createdCount = 0;
        $skippedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                $contactName = $this->getMappedValue($row, $mappings, 'contact_name');
                $companyName = $this->getMappedValue($row, $mappings, 'company_name');
                $mobileNumber = $this->getMappedValue($row, $mappings, 'mobile_number');
                $email = $this->getMappedValue($row, $mappings, 'email');
                $leadDateStr = $this->getMappedValue($row, $mappings, 'lead_date');
                $dealValue = $this->getMappedValue($row, $mappings, 'deal_value');
                $priority = strtolower($this->getMappedValue($row, $mappings, 'priority') ?: 'medium');
                $remarks = $this->getMappedValue($row, $mappings, 'remarks');
                $sourceName = $this->getMappedValue($row, $mappings, 'lead_source');
                $statusName = $this->getMappedValue($row, $mappings, 'lead_status');

                if (empty($contactName) && empty($mobileNumber) && empty($companyName) && empty($email)) {
                    $skippedCount++;
                    continue;
                }

                if (empty($contactName)) {
                    $contactName = $companyName ?: ($mobileNumber ?: 'Imported Lead');
                }

                $leadSourceId = $defaultSourceId;
                if (!empty($sourceName)) {
                    $srcRecord = LeadSource::where('name', 'like', trim($sourceName))->first();
                    if ($srcRecord) {
                        $leadSourceId = $srcRecord->id;
                    }
                }

                $leadStatusId = $defaultStatusId;
                if (!empty($statusName)) {
                    $stsRecord = LeadStatus::where('name', 'like', trim($statusName))->first();
                    if ($stsRecord) {
                        $leadStatusId = $stsRecord->id;
                    }
                }

                $leadDate = null;
                if (!empty($leadDateStr)) {
                    try {
                        $leadDate = Carbon::parse($leadDateStr)->format('Y-m-d');
                    } catch (Exception $e) {
                        $leadDate = now()->format('Y-m-d');
                    }
                } else {
                    $leadDate = now()->format('Y-m-d');
                }

                $cleanDealValue = 0.00;
                if (!empty($dealValue)) {
                    $cleanDealValue = (float) preg_replace('/[^0-9.]/', '', $dealValue);
                } elseif ($product) {
                    $cleanDealValue = (float) ($product->final_price ?: $product->base_price ?: 0);
                }

                if (!in_array($priority, ['low', 'medium', 'high'])) {
                    $priority = 'medium';
                }

                $lead = Lead::create([
                    'company_id'             => $companyId,
                    'contact_name'           => Str::limit($contactName, 255, ''),
                    'company_name'           => Str::limit($companyName, 255, ''),
                    'mobile_number'          => Str::limit($mobileNumber, 50, ''),
                    'email'                  => Str::limit($email, 255, ''),
                    'lead_date'              => $leadDate,
                    'deal_value'             => $cleanDealValue,
                    'priority'               => $priority,
                    'remarks'                => $remarks,
                    'lead_source_id'         => $leadSourceId,
                    'lead_status_id'         => $leadStatusId,
                    'product_id'             => $product?->id,
                    'product_name'           => $product ? ($product->package_name ?: $product->product_name) : null,
                    'assigned_to'            => $assignedTo,
                    'pre_sale_executive_id'  => $preSaleExecId,
                    'created_by'             => auth()->id(),
                ]);

                if ($product) {
                    LeadProduct::create([
                        'lead_id'          => $lead->id,
                        'product_id'       => $product->id,
                        'product_name'     => $product->package_name ?: $product->product_name,
                        'deal_name'        => "Import - " . $lead->contact_name,
                        'unit_price'       => $product->final_price ?: $product->base_price ?: 0,
                        'quantity'         => 1,
                        'total_price'      => $cleanDealValue,
                        'product_status'   => 'new',
                        'lead_status_id'   => $leadStatusId,
                        'lead_source_id'   => $leadSourceId,
                        'company_id'       => $companyId,
                        'created_by'       => auth()->id(),
                    ]);
                }

                $createdCount++;
            }

            DB::commit();

            @unlink($fullPath);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Lead Import Error: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->route('settings.lead-import.index')->with('error', 'Import failed: ' . $e->getMessage());
        }

        $message = "{$createdCount} lead(s) successfully imported";
        if ($assignedTo) {
            $salesName = User::find($assignedTo)?->name;
            $message .= " and allocated to Sales: {$salesName}";
        }
        if ($preSaleExecId) {
            $preSaleName = User::find($preSaleExecId)?->name;
            $message .= " (Presales: {$preSaleName})";
        }
        if ($skippedCount > 0) {
            $message .= ". {$skippedCount} empty row(s) skipped.";
        }

        return redirect()->route('settings.lead-import.index')->with('success', $message);
    }

    public function downloadSample()
    {
        $csvContent = "\xEF\xBB\xBFContact Name,Company Name,Mobile Number,Email,Lead Date,Deal Value,Priority,Remarks,Lead Source,Lead Status\n";
        $csvContent .= "John Doe,Acme Solutions,9876543210,john@acme.com,2026-08-14,50000,high,Interested in enterprise package,Online,New\n";
        $csvContent .= "Sarah Smith,Tech Corp,9876543211,sarah@techcorp.com,2026-08-14,25000,medium,Follow up next week,Referral,Qualified\n";

        return response($csvContent, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="lead_import_sample.csv"',
        ]);
    }

    private function getMappedValue(array $row, array $mappings, string $field): ?string
    {
        if (!isset($mappings[$field]) || $mappings[$field] === '' || $mappings[$field] === null) {
            return null;
        }

        $colIdx = (int) $mappings[$field];
        if (isset($row[$colIdx])) {
            $val = trim((string) $row[$colIdx]);
            return $val !== '' ? $val : null;
        }

        return null;
    }

    private function autoMatchColumns(array $headers): array
    {
        $auto = [];

        foreach ($headers as $idx => $headerName) {
            $clean = Str::lower(trim((string)$headerName));

            if (!isset($auto['contact_name']) && (str_contains($clean, 'name') || str_contains($clean, 'contact') || str_contains($clean, 'customer') || str_contains($clean, 'client'))) {
                $auto['contact_name'] = (string) $idx;
            } elseif (!isset($auto['company_name']) && (str_contains($clean, 'company') || str_contains($clean, 'business') || str_contains($clean, 'organization'))) {
                $auto['company_name'] = (string) $idx;
            } elseif (!isset($auto['mobile_number']) && (str_contains($clean, 'phone') || str_contains($clean, 'mobile') || str_contains($clean, 'number') || str_contains($clean, 'cell'))) {
                $auto['mobile_number'] = (string) $idx;
            } elseif (!isset($auto['email']) && (str_contains($clean, 'email') || str_contains($clean, 'mail'))) {
                $auto['email'] = (string) $idx;
            } elseif (!isset($auto['lead_date']) && (str_contains($clean, 'date'))) {
                $auto['lead_date'] = (string) $idx;
            } elseif (!isset($auto['deal_value']) && (str_contains($clean, 'value') || str_contains($clean, 'amount') || str_contains($clean, 'budget') || str_contains($clean, 'price'))) {
                $auto['deal_value'] = (string) $idx;
            } elseif (!isset($auto['priority']) && (str_contains($clean, 'priority') || str_contains($clean, 'level'))) {
                $auto['priority'] = (string) $idx;
            } elseif (!isset($auto['remarks']) && (str_contains($clean, 'remark') || str_contains($clean, 'note') || str_contains($clean, 'comment') || str_contains($clean, 'desc'))) {
                $auto['remarks'] = (string) $idx;
            } elseif (!isset($auto['lead_source']) && (str_contains($clean, 'source'))) {
                $auto['lead_source'] = (string) $idx;
            } elseif (!isset($auto['lead_status']) && (str_contains($clean, 'status'))) {
                $auto['lead_status'] = (string) $idx;
            }
        }

        return $auto;
    }

    private function cleanString(?string $str): string
    {
        if ($str === null) {
            return '';
        }
        $str = preg_replace('/^\xEF\xBB\xBF/', '', $str);
        return trim($str);
    }

    private function extractFileContent(string $path, string $extension): array
    {
        $rawContent = @file_get_contents($path);
        if ($rawContent === false) {
            throw new Exception('Could not read uploaded file.');
        }

        // 1. Check if file is an HTML table (CRM exports like Zoho/Salesforce saved as .xls/.xlsx/.csv)
        $trimmedRaw = trim(preg_replace('/^\xEF\xBB\xBF/', '', $rawContent));
        if (str_starts_with($trimmedRaw, '<') || stripos(substr($trimmedRaw, 0, 1000), '<table') !== false) {
            $htmlRes = $this->parseHtmlTable($rawContent);
            if (!empty($htmlRes['headers'])) {
                return $htmlRes;
            }
        }

        // 2. Check if file is a Zip archive (real XLSX)
        if (str_starts_with($rawContent, "PK\x03\x04") || $extension === 'xlsx') {
            try {
                $xlsxRes = $this->parseXlsx($path);
                if (!empty($xlsxRes['headers'])) {
                    return $xlsxRes;
                }
            } catch (Exception $e) {
                // If zip reading fails, fallback to CSV parsing
            }
        }

        // 3. Delimited text / CSV (supports UTF-8, UTF-16, BOM, comma, semicolon, tab, pipe)
        return $this->parseCsvContent($rawContent);
    }

    private function parseHtmlTable(string $content): array
    {
        $allRawRows = [];

        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        @$doc->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        $trNodes = $doc->getElementsByTagName('tr');
        foreach ($trNodes as $tr) {
            $cells = [];
            foreach ($tr->childNodes as $node) {
                if (in_array(strtolower($node->nodeName), ['th', 'td'])) {
                    $cells[] = $this->cleanString($node->textContent);
                }
            }

            if (!empty(array_filter($cells, fn($c) => $c !== ''))) {
                $allRawRows[] = $cells;
            }
        }

        return $this->extractHeadersAndRows($allRawRows);
    }

    private function parseCsvContent(string $content): array
    {
        // Convert UTF-16LE / UTF-16BE to UTF-8 if present
        if (str_starts_with($content, "\xFF\xFE")) {
            $content = mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16LE');
        } elseif (str_starts_with($content, "\xFE\xFF")) {
            $content = mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16BE');
        } elseif (substr_count(substr($content, 0, 200), "\x00") > 5) {
            $content = mb_convert_encoding($content, 'UTF-8', 'UTF-16LE');
        }

        // Auto detect delimiter
        $delimiters = [',', ';', "\t", '|'];
        $firstLine = '';
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $content));
        foreach ($lines as $line) {
            $trimmed = $this->cleanString($line);
            if ($trimmed !== '') {
                $firstLine = $line;
                break;
            }
        }

        $delimiter = ',';
        if ($firstLine !== '') {
            $counts = [];
            foreach ($delimiters as $delim) {
                $counts[$delim] = substr_count($firstLine, $delim);
            }
            arsort($counts);
            $bestDelim = key($counts);
            if ($counts[$bestDelim] > 0) {
                $delimiter = $bestDelim;
            }
        }

        $allRawRows = [];
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        $isFirstRow = true;
        while (($row = fgetcsv($stream, 0, $delimiter)) !== false) {
            if (!is_array($row) || $row === [null]) {
                continue;
            }

            if ($isFirstRow && isset($row[0])) {
                $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', $row[0]);
                $isFirstRow = false;
            }

            $row = array_map(fn ($val) => $this->cleanString(is_string($val) ? $val : (string) $val), $row);

            if (!empty(array_filter($row, fn ($v) => $v !== ''))) {
                $allRawRows[] = $row;
            }
        }

        fclose($stream);

        return $this->extractHeadersAndRows($allRawRows);
    }

    private function extractHeadersAndRows(array $allRawRows): array
    {
        $headers = [];
        $rows = [];
        $headerFound = false;

        foreach ($allRawRows as $rowValues) {
            $nonEmpty = array_filter($rowValues, fn($v) => $v !== '');
            if (empty($nonEmpty)) {
                continue;
            }

            if (!$headerFound) {
                // If row has >= 2 non-empty cells or is the only row available
                if (count($nonEmpty) >= 2 || count($allRawRows) === 1) {
                    while (!empty($rowValues) && end($rowValues) === '') {
                        array_pop($rowValues);
                    }
                    $headers = $rowValues;
                    $headerFound = true;
                }
                // Skip title / banner single-cell rows
                continue;
            }

            $rows[] = array_pad(array_slice($rowValues, 0, count($headers)), count($headers), '');
        }

        // Fallback if no multi-cell header row was found
        if (!$headerFound && !empty($allRawRows)) {
            foreach ($allRawRows as $rowValues) {
                $nonEmpty = array_filter($rowValues, fn($v) => $v !== '');
                if (!empty($nonEmpty)) {
                    while (!empty($rowValues) && end($rowValues) === '') {
                        array_pop($rowValues);
                    }
                    $headers = $rowValues;
                    break;
                }
            }
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    private function columnIndexToNum(string $col): int
    {
        $col = strtoupper(preg_replace('/[^A-Z]/i', '', $col));
        if ($col === '') {
            return 0;
        }
        $len = strlen($col);
        $num = 0;
        for ($i = 0; $i < $len; $i++) {
            $num = $num * 26 + (ord($col[$i]) - ord('A') + 1);
        }
        return max(0, $num - 1);
    }

    private function parseXlsx(string $path): array
    {
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            throw new Exception('The uploaded XLSX file could not be opened.');
        }

        $sharedStringsXml = false;
        $sheetXmls = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (strcasecmp($name, 'xl/sharedStrings.xml') === 0) {
                $sharedStringsXml = $zip->getFromName($name);
            } elseif (stripos($name, 'xl/worksheets/sheet') !== false && str_ends_with(strtolower($name), '.xml')) {
                $sheetXmls[$name] = $zip->getFromName($name);
            }
        }

        $zip->close();

        if (empty($sheetXmls)) {
            throw new Exception('No valid worksheet found in the uploaded XLSX file.');
        }

        $sharedStrings = $this->parseSharedStrings($sharedStringsXml ?: '');
        
        $bestResult = ['headers' => [], 'rows' => []];
        $maxRowCount = -1;

        ksort($sheetXmls);

        foreach ($sheetXmls as $sheetXml) {
            try {
                $worksheet = new SimpleXMLElement($sheetXml);
                $rowNodes = $worksheet->xpath('//*[local-name()="row"]');
                $allRawRows = [];

                foreach ($rowNodes ?: [] as $rowNode) {
                    $cellNodes = $rowNode->xpath('*[local-name()="c"]');
                    if (empty($cellNodes)) {
                        continue;
                    }

                    $indexedCells = [];
                    $maxColIdx = -1;
                    $autoColIdx = 0;

                    foreach ($cellNodes as $cell) {
                        $ref = (string) ($cell['r'] ?? '');
                        if ($ref !== '') {
                            $colLetters = preg_replace('/\d+/', '', $ref);
                            $colIdx = $this->columnIndexToNum($colLetters);
                        } else {
                            $colIdx = $autoColIdx;
                        }
                        $autoColIdx = $colIdx + 1;

                        $val = $this->extractCellValue($cell, $sharedStrings);
                        $indexedCells[$colIdx] = $val;
                        if ($colIdx > $maxColIdx) {
                            $maxColIdx = $colIdx;
                        }
                    }

                    if ($maxColIdx < 0) {
                        continue;
                    }

                    $rowValues = [];
                    for ($c = 0; $c <= $maxColIdx; $c++) {
                        $rowValues[$c] = $indexedCells[$c] ?? '';
                    }

                    if (!empty(array_filter($rowValues, fn($v) => $v !== ''))) {
                        $allRawRows[] = $rowValues;
                    }
                }

                $sheetRes = $this->extractHeadersAndRows($allRawRows);
                $rowCount = count($sheetRes['rows']);

                if (!empty($sheetRes['headers']) && $rowCount > $maxRowCount) {
                    $bestResult = $sheetRes;
                    $maxRowCount = $rowCount;
                }
            } catch (Exception $e) {
                continue;
            }
        }

        return $bestResult;
    }

    private function parseSharedStrings(string $xml): array
    {
        if ($xml === '') {
            return [];
        }

        try {
            $sharedStringsXml = new SimpleXMLElement($xml);
            $values = [];
            $siNodes = $sharedStringsXml->xpath('//*[local-name()="si"]');

            foreach ($siNodes ?: [] as $stringItem) {
                $textParts = [];
                $tNodes = $stringItem->xpath('.//*[local-name()="t"]');

                foreach ($tNodes ?: [] as $textNode) {
                    $textParts[] = (string) $textNode;
                }

                $values[] = $this->cleanString(implode('', $textParts));
            }

            return $values;
        } catch (Exception $e) {
            return [];
        }
    }

    private function extractCellValue(SimpleXMLElement $cell, array $sharedStrings): string
    {
        $type = (string) ($cell['t'] ?? '');

        if ($type === 's') {
            $vNodes = $cell->xpath('*[local-name()="v"]');
            $valIdx = !empty($vNodes) ? (int) (string) $vNodes[0] : (isset($cell->v) ? (int) $cell->v : null);
            return $valIdx !== null ? $this->cleanString($sharedStrings[$valIdx] ?? '') : '';
        }

        if ($type === 'inlineStr') {
            $tNodes = $cell->xpath('.//*[local-name()="t"]');
            if (!empty($tNodes)) {
                $textParts = [];
                foreach ($tNodes as $tNode) {
                    $textParts[] = (string) $tNode;
                }
                return $this->cleanString(implode('', $textParts));
            }
            $isNodes = $cell->xpath('*[local-name()="is"]');
            if (!empty($isNodes)) {
                return $this->cleanString((string) $isNodes[0]);
            }
        }

        $vNodes = $cell->xpath('*[local-name()="v"]');
        $value = !empty($vNodes) ? (string) $vNodes[0] : (isset($cell->v) ? (string) $cell->v : '');

        if ($value === '') {
            $tNodes = $cell->xpath('*[local-name()="t"]');
            if (!empty($tNodes)) {
                $value = (string) $tNodes[0];
            }
        }

        if ($value === '') {
            $tNodes = $cell->xpath('.//*[local-name()="t"]');
            if (!empty($tNodes)) {
                $textParts = [];
                foreach ($tNodes as $tNode) {
                    $textParts[] = (string) $tNode;
                }
                $value = implode('', $textParts);
            }
        }

        if ($value !== '' && is_numeric($value) && !str_contains($value, '.') && $type !== 'str') {
            $numericValue = (int) $value;
            if ($numericValue > 30000 && $numericValue < 60000) {
                return Carbon::create(1899, 12, 30)->addDays($numericValue)->format('Y-m-d');
            }
        }

        return $this->cleanString($value);
    }
}
