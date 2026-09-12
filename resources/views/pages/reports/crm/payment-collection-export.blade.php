<table border="1">
    <thead>
        <tr>
            <th colspan="13">CRM Payment Collection Export - {{ \Illuminate\Support\Carbon::parse($selectedFromDate)->format('d M Y') }} to {{ \Illuminate\Support\Carbon::parse($selectedToDate)->format('d M Y') }}</th>
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
                'Total Amount' => '',
                'Received Amount' => '',
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
                <td colspan="13">No payment collection records found for the selected filters.</td>
            </tr>
        @endforelse
    </tbody>
</table>
