@extends('layouts.app')

@section('title', 'Edit Biometric Profile — ' . config('app.name'))

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-title">Edit Profile {{ $profile->employee_code }}</h1>
            <p class="page-subtitle">Update biometric profile settings for {{ $profile->user->name }}.</p>
        </div>
        <a href="{{ route('biometrics.index') }}" class="btn btn-secondary">← Back to Biometrics</a>
    </div>

    <form method="POST" action="{{ route('biometrics.update', $profile) }}" class="panel form-panel" data-loading>
        @csrf
        @method('PUT')

        <div class="form-grid">
            <div class="form-field">
                <label>Employee</label>
                <input type="text" value="{{ $profile->user->name }} — {{ $profile->user->email }}" disabled>
            </div>

            <div class="form-field">
                <label for="department">Department <span class="req" aria-hidden="true">*</span></label>
                <input type="text" id="department" name="department" list="department-options"
                       value="{{ old('department', $profile->department) }}" required maxlength="100"
                       @class(['is-invalid' => $errors->has('department')])>
                <datalist id="department-options">
                    @foreach ($departments as $department)
                        <option value="{{ $department }}"></option>
                    @endforeach
                </datalist>
                @error('department') <p class="field-error" role="alert">{{ $message }}</p> @enderror
            </div>

            <div class="form-field">
                <label for="position">Position <span class="req" aria-hidden="true">*</span></label>
                <input type="text" id="position" name="position"
                       value="{{ old('position', $profile->position) }}" required maxlength="100"
                       @class(['is-invalid' => $errors->has('position')])>
                @error('position') <p class="field-error" role="alert">{{ $message }}</p> @enderror
            </div>

            <div class="form-field">
                <label for="assigned_device_id">Assigned Device</label>
                <select id="assigned_device_id" name="assigned_device_id" @class(['is-invalid' => $errors->has('assigned_device_id')])>
                    <option value="">No device assigned</option>
                    @foreach ($devices as $device)
                        <option value="{{ $device->id }}" @selected((int) old('assigned_device_id', $profile->assigned_device_id) === $device->id)>{{ $device->name }} — {{ $device->type->label() }}</option>
                    @endforeach
                </select>
                @error('assigned_device_id') <p class="field-error" role="alert">{{ $message }}</p> @enderror
            </div>

            <div class="form-field">
                <label for="status">Status <span class="req" aria-hidden="true">*</span></label>
                <select id="status" name="status" required @class(['is-invalid' => $errors->has('status')])>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(old('status', $profile->status->value) === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
                @error('status') <p class="field-error" role="alert">{{ $message }}</p> @enderror
            </div>

            <div class="form-field form-field-full">
                <label for="security_notes">Security Notes</label>
                <textarea id="security_notes" name="security_notes" rows="3" maxlength="1000"
                          placeholder="Anything the security team should know about this profile…"
                          @class(['is-invalid' => $errors->has('security_notes')])>{{ old('security_notes', $profile->security_notes) }}</textarea>
                @error('security_notes') <p class="field-error" role="alert">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="form-actions">
            <a href="{{ route('biometrics.index') }}" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-primary" data-loading-text="Saving…">Save Changes</button>
        </div>
    </form>

@endsection
