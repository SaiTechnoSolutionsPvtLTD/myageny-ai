<table border="1">
    <thead>
        <tr>
            <th colspan="15">CRM Payment Collection Export - {{ \Illuminate\Support\Carbon::parse($selectedFromDate)->format('d M Y') }} to {{ \Illuminate\Support\Carbon::parse($selectedToDate)->format('d M Y') }}</th>
        </tr>
        <tr>
            @foreach(array_keys($rows->first() ?? [
                'Payment ID' => '',
                'Payment Date' => '',
                'Receipt No' => '',
                'Customer ID' => '',
                'Branch' => '',
                'Company Name' => '',
                'Customer Name' => '',
                'Payment Type' => '',
                'Total Amount' => '',
                'Received Amount' => '',
                'TDS Amount (%)' => '',
                'Outstanding Amount' => '',
                'Payment Mode' => '',
                'Transaction Reference' => '',
                'Received By' => '',
            ]) as $heading)
                <th>{{ $heading }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
            <tr>
                @foreach($row as $value)
                    <td>{{ $value }}</td>
                @endforeach
            </tr>
        @empty
            <tr>
                <td colspan="15">No payment collection records found for the selected filters.</td>
            </tr>
        @endforelse
    </tbody>
    @if($rows->isNotEmpty())
        <tfoot>
            <tr style="font-weight: bold; background-color: #f2f2f2;">
                <td colspan="8" align="right">Total ({{ $rows->count() }} rows):</td>
                <td>{{ number_format($rows->sum(fn($r) => (float)($r['Total Amount'] ?? 0)), 2, '.', '') }}</td>
                <td>{{ number_format($rows->sum(fn($r) => (float)($r['Received Amount'] ?? 0)), 2, '.', '') }}</td>
                <td>{{ number_format($rows->sum(fn($r) => (float)explode(' ', (string)($r['TDS Amount (%)'] ?? 0))[0]), 2, '.', '') }}</td>
                <td>{{ number_format($rows->sum(fn($r) => (float)($r['Outstanding Amount'] ?? 0)), 2, '.', '') }}</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    @endif
</table>
