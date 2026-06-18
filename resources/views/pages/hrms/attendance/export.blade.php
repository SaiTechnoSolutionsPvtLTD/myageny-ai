<table border="1">
    <thead>
        <tr>
            <th colspan="14">Attendance Export - {{ $selectedFromDate->format('d M Y') }} to {{ $selectedToDate->format('d M Y') }}</th>
        </tr>
        <tr>
            @foreach(array_keys($rows->first() ?? [
                'Employee ID' => '',
                'Employee Name' => '',
                'Attendance Date' => '',
                'Status' => '',
                'Login Time' => '',
                'Logout Time' => '',
                'Working Hours' => '',
                'Login Timing' => '',
                'Login Location' => '',
                'Logout Location' => '',
                'Attendance Photo URL' => '',
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
                <td colspan="14">No attendance records found for the selected filters.</td>
            </tr>
        @endforelse
    </tbody>
</table>
