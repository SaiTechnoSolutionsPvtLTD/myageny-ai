<!-- FILE: resources/views/pages/reports/crm/branch-comparison-export.blade.php -->
<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; font-family: sans-serif;">
    <thead>
        <tr style="background-color: #4f46e5; color: #ffffff;">
            <th colspan="7" align="center" style="font-size: 16px; font-weight: bold; padding: 10px;">
                Branch Wise Comparison Report ({{ $periodLabel }})
            </th>
        </tr>
        <tr style="background-color: #f1f5f9; font-weight: bold;">
            <th>Branch Name</th>
            <th>Total Leads</th>
            <th>Converted Leads</th>
            <th>Conversion Rate</th>
            <th>Sales Contract Value (Rs)</th>
            <th>Received Amount (Rs)</th>
            <th>Outstanding Balance (Rs)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $row)
            @php
                $rate = $row['total_leads'] > 0 ? round(($row['converted_leads'] / $row['total_leads']) * 100, 2) : 0;
                $outstanding = max(0, $row['revenue'] - $row['received']);
            @endphp
            <tr>
                <td><strong>{{ $row['branch_name'] }}</strong></td>
                <td align="right">{{ $row['total_leads'] }}</td>
                <td align="right">{{ $row['converted_leads'] }}</td>
                <td align="right"><strong>{{ $rate }}%</strong></td>
                <td align="right">{{ number_format($row['revenue'], 2, '.', '') }}</td>
                <td align="right" style="color: #16a34a;">{{ number_format($row['received'], 2, '.', '') }}</td>
                <td align="right" style="color: #dc2626;">{{ number_format($outstanding, 2, '.', '') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<br><br>

<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; font-family: sans-serif;">
    <thead>
        <tr style="background-color: #6366f1; color: #ffffff;">
            <th colspan="{{ count($allSources) + 2 }}" align="center" style="font-size: 14px; font-weight: bold; padding: 8px;">
                Lead Source Wise Matrix
            </th>
        </tr>
        <tr style="background-color: #f1f5f9; font-weight: bold;">
            <th>Branch Name</th>
            @foreach($allSources as $source)
                <th>{{ $source }}</th>
            @endforeach
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $row)
            @php $branchTotal = 0; @endphp
            <tr>
                <td><strong>{{ $row['branch_name'] }}</strong></td>
                @foreach($allSources as $source)
                    @php
                        $count = $row['sources'][$source] ?? 0;
                        $branchTotal += $count;
                    @endphp
                    <td align="right">{{ $count }}</td>
                @endforeach
                <td align="right" style="font-weight: bold; background-color: #f1f5f9;">{{ $branchTotal }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<br><br>

<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; font-family: sans-serif;">
    <thead>
        <tr style="background-color: #06b6d4; color: #ffffff;">
            <th colspan="{{ count($allStatuses) + 2 }}" align="center" style="font-size: 14px; font-weight: bold; padding: 8px;">
                Lead Status Wise Matrix
            </th>
        </tr>
        <tr style="background-color: #f1f5f9; font-weight: bold;">
            <th>Branch Name</th>
            @foreach($allStatuses as $status)
                <th>{{ $status }}</th>
            @endforeach
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $row)
            @php $branchTotal = 0; @endphp
            <tr>
                <td><strong>{{ $row['branch_name'] }}</strong></td>
                @foreach($allStatuses as $status)
                    @php
                        $count = $row['statuses'][$status] ?? 0;
                        $branchTotal += $count;
                    @endphp
                    <td align="right">{{ $count }}</td>
                @endforeach
                <td align="right" style="font-weight: bold; background-color: #f1f5f9;">{{ $branchTotal }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
