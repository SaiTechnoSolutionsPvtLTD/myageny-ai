@php
    $updateFormAction = $updateFormAction ?? '';
    $updateEditorId = $updateEditorId ?? 'projectUpdateEditor';
    $showProjectSelector = $showProjectSelector ?? false;
    $projectOptions = $projectOptions ?? collect();
    $selectedProjectId = old('production_initiation_id', $selectedProjectId ?? '');
@endphp

<form method="POST" action="{{ $updateFormAction }}">
    @csrf

    @if(! empty($updateReturnTarget))
        <input type="hidden" name="return_to" value="{{ $updateReturnTarget }}">
    @endif

    <div class="ps-update-form">
        <div class="ps-form-grid{{ $showProjectSelector ? '' : ' ps-form-grid-single' }}">
            @if($showProjectSelector)
                <div>
                    <div class="ps-label" style="margin-bottom:8px;">Project</div>
                    <select name="production_initiation_id" class="ps-input" required>
                        <option value="">Select project</option>
                        @foreach($projectOptions as $projectOption)
                            <option value="{{ $projectOption->id }}" @selected((string) $selectedProjectId === (string) $projectOption->id)>
                                {{ $projectOption->product_name }}
                                @if($projectOption->client_name || $projectOption->company_name || $projectOption->lead?->company_name)
                                    | {{ $projectOption->client_name ?: ($projectOption->company_name ?: ($projectOption->lead?->company_name ?: '')) }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('production_initiation_id')
                        <div class="ps-allocate-note" style="color:#b91c1c;">{{ $message }}</div>
                    @enderror
                </div>
            @endif

            <div>
                <div class="ps-label" style="margin-bottom:8px;">Type</div>
                <select name="type" class="ps-input" required>
                    <option value="">Select update type</option>
                    <option value="production_update" @selected(old('type') === 'production_update')>Production Update</option>
                    <option value="meeting_update" @selected(old('type') === 'meeting_update')>Meeting Update</option>
                    <option value="weekly_update" @selected(old('type') === 'weekly_update')>Weekly Update</option>
                </select>
                @error('type')
                    <div class="ps-allocate-note" style="color:#b91c1c;">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="ps-form-editor">
            <div class="ps-label" style="margin-bottom:8px;">Update Details</div>
            <textarea id="{{ $updateEditorId }}" name="content" class="ps-textarea" required>{{ old('content') }}</textarea>
            @error('content')
                <div class="ps-allocate-note" style="color:#b91c1c;">{{ $message }}</div>
            @enderror
        </div>
    </div>



    <div class="ps-actions" style="margin-top:16px;">
        <button type="button" class="ps-btn" data-close-update-modal>Cancel</button>
        <button type="submit" class="ps-btn ps-btn-primary">Save Update</button>
    </div>
</form>
