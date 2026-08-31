<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Timesheet LOP Report</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        th, td {
            border: 1px solid #cccccc;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .header-title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            padding: 10px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .lop-danger { color: #dc2626; font-weight: bold; }
    </style>
</head>
<body>
    <table>
        <tr>
            <th colspan="7" class="header-title">
                Timesheet LOP Report ({{ $selectedFromDate->format('d M Y') }} to {{ $selectedToDate->format('d M Y') }})
            </th>
        </tr>
        <tr>
            <th>Employee ID</th>
            <th>Employee Name</th>
            <th>Department</th>
            <th>Designation</th>
            <th class="text-center">Present Days</th>
            <th class="text-center">Timesheet Submitted Days</th>
            <th class="text-center">Missing Timesheet Days</th>
            <th class="text-center">Timesheet LOP Days</th>
        </tr>
        @foreach($rows as $row)
            @php $emp = $row['employee']; @endphp
            <tr>
                <td>{{ $emp->employee_id ?: 'N/A' }}</td>
                <td>{{ $emp->name }}</td>
                <td>{{ $emp->department?->name ?: 'N/A' }}</td>
                <td>{{ $emp->role?->display_name ?: ($emp->role?->name ?: 'N/A') }}</td>
                <td class="text-center">{{ $row['present_days'] }}</td>
                <td class="text-center">{{ $row['timesheet_submitted_days'] }}</td>
                <td class="text-center">{{ $row['missing_days'] }}</td>
                <td class="text-center {{ $row['timesheet_lop_days'] > 0 ? 'lop-danger' : '' }}">
                    {{ $row['timesheet_lop_days'] }}
                </td>
            </tr>
        @endforeach
        <tr>
            <th colspan="4" class="text-right">Total Summary:</th>
            <th class="text-center">{{ $stats['total_present_days'] }}</th>
            <th class="text-center">{{ $stats['total_submitted_timesheets'] }}</th>
            <th class="text-center">{{ $stats['total_missing_days'] }}</th>
            <th class="text-center lop-danger">{{ $stats['total_lop_days'] }} Days</th>
        </tr>
    </table>
</body>
</html>
