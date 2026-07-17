<table border="1">
    <thead>
        <tr>
            <th colspan="4" style="font-weight: bold; background-color: #f2f2f2; text-align: center;">Sales Comparison Export ({{ strtoupper($periodType) }} - {{ $periodValue }})</th>
        </tr>
        <tr>
            <th style="font-weight: bold; background-color: #f2f2f2;">Name / Branch</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: right;">Target Revenue</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: right;">Actual Collection</th>
            <th style="font-weight: bold; background-color: #f2f2f2; text-align: right;">Variance (Actual - Target)</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
            <tr>
                <td>{{ $row['Label'] }}</td>
                <td style="text-align: right;">{{ number_format($row['Target'], 2, '.', '') }}</td>
                <td style="text-align: right;">{{ number_format($row['Actual'], 2, '.', '') }}</td>
                <td style="text-align: right; color: {{ $row['Difference'] >= 0 ? '#16a34a' : '#dc2626' }};">
                    {{ $row['Difference'] > 0 ? '+' : '' }}{{ number_format($row['Difference'], 2, '.', '') }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" style="text-align: center;">No comparison data found for the selected filters.</td>
            </tr>
        @endforelse
    </tbody>
</table>
