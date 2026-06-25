<table border="1">
    <thead>
        <tr>
            <th colspan="8">CRM Revenue Comparison Export - {{ strtoupper($selectedFilters['period_type']) }}</th>
        </tr>
        <tr>
            @foreach(array_keys($rows->first() ?? [
                'Period' => '',
                'Current Period Value' => '',
                'Current Period Count' => '',
                'Previous Period Value' => '',
                'Previous Period Count' => '',
                'Difference Amount' => '',
                'Difference %' => '',
                'Growth %' => '',
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
                <td colspan="8">No revenue comparison data found for the selected filters.</td>
            </tr>
        @endforelse
    </tbody>
</table>
