<table border="1">
    <thead>
        <tr>
            <th colspan="14" style="font-size: 14px; font-weight: bold; background-color: #fef3c7; color: #b45309; text-align: center; height: 35px;">
                CRM Lead-Wise Outstanding Report - {{ !empty($selectedFromDate) ? \Illuminate\Support\Carbon::parse($selectedFromDate)->format('d M Y') : 'All' }} to {{ !empty($selectedToDate) ? \Illuminate\Support\Carbon::parse($selectedToDate)->format('d M Y') : 'All' }}
            </th>
        </tr>
        <tr style="background-color: #f1f5f9; font-weight: bold;">
            @foreach(array_keys($rows->first() ?? [
                'Lead ID'             => '',
                'Company Name'        => '',
                'Customer / Contact'  => '',
                'Mobile Number'       => '',
                'Email'               => '',
                'Branch'              => '',
                'Assigned Employee'   => '',
                'Products'            => '',
                'Total Products'      => '',
                'Total Deal Value'    => '',
                'Amount Received'     => '',
                'Outstanding Balance' => '',
                'Payment Status'      => '',
                'Lead Date'           => '',
                'Last Payment Date'   => '',
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
                <td colspan="14" style="text-align: center;">No outstanding lead records found for the selected filters.</td>
            </tr>
        @endforelse
    </tbody>
</table>
