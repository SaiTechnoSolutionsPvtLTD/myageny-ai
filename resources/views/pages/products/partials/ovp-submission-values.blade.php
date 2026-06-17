@php
    $entries = collect($entries ?? [])->filter(fn ($entry) => is_array($entry) && !empty($entry['label']));
@endphp

@if($entries->isEmpty())
    <span class="pm-muted">No custom values stored.</span>
@else
    <div style="display:grid; gap:8px;">
        @foreach($entries as $entry)
            <div style="padding:10px 12px; border:1px solid #f0eef2; border-radius:10px; background:#fcfcfc;">
                <div style="font-size:12px; font-weight:700; color:#3a3a3a;">{{ $entry['label'] }}</div>
                <div style="font-size:13px; color:#666; margin-top:4px;">
                    @if(($entry['type'] ?? null) === 'file' && is_array($entry['value'] ?? null))
                        <a href="{{ $entry['value']['url'] ?? '#' }}" target="_blank" rel="noopener">{{ $entry['value']['name'] ?? 'View file' }}</a>
                    @elseif(is_array($entry['value'] ?? null))
                        {{ implode(', ', $entry['value']) }}
                    @else
                        {{ $entry['value'] }}
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif
