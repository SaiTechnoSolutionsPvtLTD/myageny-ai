@php
    $showDesignCols = in_array($teamView ?? 'all', ['all', 'design']);
    $showDmCols     = in_array($teamView ?? 'all', ['all', 'dm']);
    $totalCols = 5 + ($showDesignCols ? 5 : 0) + ($showDmCols ? 5 : 0) + 1;
@endphp
{{-- SMM Sheet Export View (Excel-friendly HTML table) --}}
<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;font-family:Arial,sans-serif;font-size:12px;">
    <thead>
        <tr style="background:#ea580c;color:#fff;">
            <th colspan="{{ $totalCols }}" align="center" style="font-size:15px;font-weight:bold;padding:10px;">
                SMM Sheet — {{ \Carbon\Carbon::parse($filters['date_from'])->format('d M Y') }} to {{ \Carbon\Carbon::parse($filters['date_to'])->format('d M Y') }}
            </th>
        </tr>
        <tr style="background:#ffedd5;font-weight:bold;color:#9a3412;">
            <th rowspan="2">Account &amp; Product</th>
            <th rowspan="2">Start Date</th>
            <th rowspan="2">End Date</th>
            <th rowspan="2">Committed Posters</th>
            <th rowspan="2">Committed Videos</th>

            @if($showDesignCols)
                <th colspan="5" style="background:#f0fdf4;color:#047857;border:1px solid #bbf7d0;">Design Team</th>
            @endif

            @if($showDmCols)
                <th colspan="5" style="background:#f5f3ff;color:#6d28d9;border:1px solid #c4b5fd;">DM Team</th>
            @endif

            <th rowspan="2">Status</th>
        </tr>
        <tr style="font-weight:bold;">
            @if($showDesignCols)
                <th style="background:#f0fdf4;color:#047857;">Done Posters</th>
                <th style="background:#f0fdf4;color:#d97706;">Pending Posters</th>
                <th style="background:#f0fdf4;color:#047857;">Done Videos</th>
                <th style="background:#f0fdf4;color:#d97706;">Pending Videos</th>
                <th style="background:#f0fdf4;color:#047857;">Allocated Person(s)</th>
            @endif

            @if($showDmCols)
                <th style="background:#f5f3ff;color:#6d28d9;">Done Posters</th>
                <th style="background:#f5f3ff;color:#d97706;">Pending Posters</th>
                <th style="background:#f5f3ff;color:#6d28d9;">Done Videos</th>
                <th style="background:#f5f3ff;color:#d97706;">Pending Videos</th>
                <th style="background:#f5f3ff;color:#6d28d9;">Allocated Person(s)</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
            <tr>
                <td>
                    <strong>{{ $row['account_name'] }}</strong><br>
                    <span style="color:#64748b;font-size:11px;">{{ $row['product_name'] }}</span>
                </td>
                <td>{{ $row['start_date'] ? \Carbon\Carbon::parse($row['start_date'])->format('d/m/Y') : '' }}</td>
                <td>{{ $row['end_date'] ? \Carbon\Carbon::parse($row['end_date'])->format('d/m/Y') : '' }}</td>
                <td align="center">{{ $row['committed_posters'] ?: 0 }}</td>
                <td align="center">{{ $row['committed_videos'] ?: 0 }}</td>

                @if($showDesignCols)
                    <td align="center" style="background:#f0fdf4;">{{ $row['design_completed_posters'] ?: 0 }}</td>
                    <td align="center" style="background:#fefce8;color:#854d0e;">{{ $row['design_pending_posters'] ?: 0 }}</td>
                    <td align="center" style="background:#f0fdf4;">{{ $row['design_completed_videos'] ?: 0 }}</td>
                    <td align="center" style="background:#fefce8;color:#854d0e;">{{ $row['design_pending_videos'] ?: 0 }}</td>
                    <td style="background:#f0fdf4;">{{ $row['design_persons'] }}</td>
                @endif

                @if($showDmCols)
                    <td align="center" style="background:#f5f3ff;">{{ $row['dm_completed_posters'] ?: 0 }}</td>
                    <td align="center" style="background:#fefce8;color:#854d0e;">{{ $row['dm_pending_posters'] ?: 0 }}</td>
                    <td align="center" style="background:#f5f3ff;">{{ $row['dm_completed_videos'] ?: 0 }}</td>
                    <td align="center" style="background:#fefce8;color:#854d0e;">{{ $row['dm_pending_videos'] ?: 0 }}</td>
                    <td style="background:#f5f3ff;">{{ $row['dm_persons'] }}</td>
                @endif

                <td align="center" style="
                    @if($row['status'] === 'completed') background:#f0fdf4;color:#16a34a;
                    @elseif($row['status'] === 'overdue') background:#fef2f2;color:#dc2626;
                    @else background:#fff7ed;color:#c2410c;
                    @endif
                    font-weight:bold;">{{ ucfirst($row['status']) }}</td>
            </tr>
        @empty
            <tr><td colspan="{{ $totalCols }}" align="center" style="color:#6b7280;padding:20px;">No records found.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr style="background:#f8fafc;font-weight:bold;">
            <td colspan="3">Total</td>
            <td align="center">{{ $rows->sum('committed_posters') }}</td>
            <td align="center">{{ $rows->sum('committed_videos') }}</td>

            @if($showDesignCols)
                <td align="center" style="background:#f0fdf4;">{{ $rows->sum('design_completed_posters') }}</td>
                <td align="center" style="background:#fefce8;">{{ $rows->sum('design_pending_posters') }}</td>
                <td align="center" style="background:#f0fdf4;">{{ $rows->sum('design_completed_videos') }}</td>
                <td align="center" style="background:#fefce8;">{{ $rows->sum('design_pending_videos') }}</td>
                <td style="background:#f0fdf4;"></td>
            @endif

            @if($showDmCols)
                <td align="center" style="background:#f5f3ff;">{{ $rows->sum('dm_completed_posters') }}</td>
                <td align="center" style="background:#fefce8;">{{ $rows->sum('dm_pending_posters') }}</td>
                <td align="center" style="background:#f5f3ff;">{{ $rows->sum('dm_completed_videos') }}</td>
                <td align="center" style="background:#fefce8;">{{ $rows->sum('dm_pending_videos') }}</td>
                <td style="background:#f5f3ff;"></td>
            @endif

            <td>
                ✅ {{ $rows->where('status','completed')->count() }} Completed |
                ⏳ {{ $rows->where('status','pending')->count() }} Pending |
                🔴 {{ $rows->where('status','overdue')->count() }} Overdue
            </td>
        </tr>
    </tfoot>
</table>
