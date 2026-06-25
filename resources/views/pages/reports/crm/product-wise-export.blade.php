<table border="1">
    <thead>
        <tr>
            <th colspan="8">CRM Product Wise Export - {{ \Illuminate\Support\Carbon::parse($selectedFromDate)->format('d M Y') }} to {{ \Illuminate\Support\Carbon::parse($selectedToDate)->format('d M Y') }}</th>
        </tr>
        <tr>
            @foreach(array_keys($rows->first() ?? [
                'Product Code' => '',
                'Product Name' => '',
                'Category' => '',
                'Quantity Sold' => '',
                'Sales Amount' => '',
                'Discount Amount' => '',
                'Tax Amount' => '',
                'Net Revenue' => '',
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
                <td colspan="8">No product-wise records found for the selected filters.</td>
            </tr>
        @endforelse
    </tbody>
</table>
