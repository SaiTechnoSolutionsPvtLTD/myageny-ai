{{-- SMM Report Export View (Excel-friendly HTML table) --}}
<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;font-family:Arial,sans-serif;font-size:12px;">
    <thead>
        <tr style="background:#0891b2;color:#fff;">
            <th colspan="19" align="center" style="font-size:15px;font-weight:bold;padding:10px;">
                SMM Report — {{ \Carbon\Carbon::parse($filters['date_from'])->format('d M Y') }} to {{ \Carbon\Carbon::parse($filters['date_to'])->format('d M Y') }}
            </th>
        </tr>
        <tr style="background:#cffafe;font-weight:bold;color:#0e7490;">
            <th rowspan="2">Month</th>
            <th rowspan="2">Account Name</th>
            <th rowspan="2">Product</th>
            <th rowspan="2">Start Date</th>
            <th rowspan="2">End Date</th>
            <th rowspan="2">Tenure (Days)</th>
            <th rowspan="2">Committed Posters</th>
            <th rowspan="2">Committed Videos</th>
            <th colspan="5" style="background:#f0fdf4;color:#047857;border:1px solid #bbf7d0;">Design Team</th>
            <th colspan="5" style="background:#f5f3ff;color:#6d28d9;border:1px solid #c4b5fd;">DM Team</th>
            <th rowspan="2">Status</th>
        </tr>
        <tr style="font-weight:bold;">
            <th style="background:#f0fdf4;color:#047857;">Done Posters</th>
            <th style="background:#f0fdf4;color:#d97706;">Pending Posters</th>
            <th style="background:#f0fdf4;color:#047857;">Done Videos</th>
            <th style="background:#f0fdf4;color:#d97706;">Pending Videos</th>
            <th style="background:#f0fdf4;color:#047857;">Allocated Person(s)</th>
            <th style="background:#f5f3ff;color:#6d28d9;">Done Posters</th>
            <th style="background:#f5f3ff;color:#d97706;">Pending Posters</th>
            <th style="background:#f5f3ff;color:#6d28d9;">Done Videos</th>
            <th style="background:#f5f3ff;color:#d97706;">Pending Videos</th>
            <th style="background:#f5f3ff;color:#6d28d9;">Allocated Person(s)</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
            <tr>
                <td>{{ $row['month'] }}</td>
                <td><strong>{{ $row['account_name'] }}</strong></td>
                <td>{{ $row['product_name'] }}</td>
                <td>{{ $row['start_date'] ? \Carbon\Carbon::parse($row['start_date'])->format('d/m/Y') : '' }}</td>
                <td>{{ $row['end_date'] ? \Carbon\Carbon::parse($row['end_date'])->format('d/m/Y') : '' }}</td>
                <td align="center">{{ $row['tenure'] !== null ? $row['tenure'] : '' }}</td>
                <td align="center">{{ $row['committed_posters'] ?: 0 }}</td>
                <td align="center">{{ $row['committed_videos'] ?: 0 }}</td>
                
                <td align="center" style="background:#f0fdf4;">{{ $row['design_completed_posters'] ?: 0 }}</td>
                <td align="center" style="background:#fefce8;color:#854d0e;">{{ $row['design_pending_posters'] ?: 0 }}</td>
                <td align="center" style="background:#f0fdf4;">{{ $row['design_completed_videos'] ?: 0 }}</td>
                <td align="center" style="background:#fefce8;color:#854d0e;">{{ $row['design_pending_videos'] ?: 0 }}</td>
                <td style="background:#f0fdf4;">{{ $row['design_persons'] }}</td>
                <td align="center" style="background:#f5f3ff;">{{ $row['dm_completed_posters'] ?: 0 }}</td>
                <td align="center" style="background:#fefce8;color:#854d0e;">{{ $row['dm_pending_posters'] ?: 0 }}</td>
                <td align="center" style="background:#f5f3ff;">{{ $row['dm_completed_videos'] ?: 0 }}</td>
                <td align="center" style="background:#fefce8;color:#854d0e;">{{ $row['dm_pending_videos'] ?: 0 }}</td>
                <td style="background:#f5f3ff;">{{ $row['dm_persons'] }}</td>
                <td align="center" style="
                    @if($row['status'] === 'completed') background:#f0fdf4;color:#16a34a;
                    @elseif($row['status'] === 'overdue') background:#fef2f2;color:#dc2626;
                    @else background:#fff7ed;color:#c2410c;
                    @endif
                    font-weight:bold;">{{ ucfirst($row['status']) }}</td>
            </tr>
        @empty
            <tr><td colspan="19" align="center" style="color:#6b7280;padding:20px;">No records found.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr style="background:#f8fafc;font-weight:bold;">
            <td colspan="6">Total</td>
            <td align="center">{{ $rows->sum('committed_posters') }}</td>
            <td align="center">{{ $rows->sum('committed_videos') }}</td>
            
            <td align="center" style="background:#f0fdf4;">{{ $rows->sum('design_completed_posters') }}</td>
            <td align="center" style="background:#fefce8;">{{ $rows->sum('design_pending_posters') }}</td>
            <td align="center" style="background:#f0fdf4;">{{ $rows->sum('design_completed_videos') }}</td>
            <td align="center" style="background:#fefce8;">{{ $rows->sum('design_pending_videos') }}</td>
            <td style="background:#f0fdf4;"></td>
            <td align="center" style="background:#f5f3ff;">{{ $rows->sum('dm_completed_posters') }}</td>
            <td align="center" style="background:#fefce8;">{{ $rows->sum('dm_pending_posters') }}</td>
            <td align="center" style="background:#f5f3ff;">{{ $rows->sum('dm_completed_videos') }}</td>
            <td align="center" style="background:#fefce8;">{{ $rows->sum('dm_pending_videos') }}</td>
            <td style="background:#f5f3ff;"></td>
            <td>
                ✅ {{ $rows->where('status','completed')->count() }} Completed |
                ⏳ {{ $rows->where('status','pending')->count() }} Pending |
                🔴 {{ $rows->where('status','overdue')->count() }} Overdue
            </td>
        </tr>
    </tfoot>
</table>
