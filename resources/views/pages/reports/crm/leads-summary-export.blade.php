<table border="1">
    <thead>
        <tr>
            <th colspan="15">CRM Leads Summary Export - {{ \Illuminate\Support\Carbon::parse($selectedFromDate)->format('d M Y') }} to {{ \Illuminate\Support\Carbon::parse($selectedToDate)->format('d M Y') }}</th>
        </tr>
        <tr>
            @foreach(array_keys($rows->first() ?? [
                'Lead ID' => '',
                'Branch' => '',
                'Name' => '',
                'Email' => '',
                'Mobile Number' => '',
                'Lead Source' => '',
                'Lead Status' => '',
                'Product Name' => '',
                'Entry Date' => '',
                'Converted Date' => '',
                'Total Cost' => '',
                'Received Cost' => '',
                'Pending Cost' => '',
                'Allocated To' => '',
                'Lead Age' => '',
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
                <td colspan="15">No lead summary records found for the selected filters.</td>
            </tr>
        @endforelse
    </tbody>
</table>
