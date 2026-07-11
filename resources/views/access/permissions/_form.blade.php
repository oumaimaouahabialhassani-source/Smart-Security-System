{{-- Shared permission form: $permission is null on create. --}}
@php($editing = $permission !== null)

<form method="POST"
      action="{{ $editing ? route('access.permissions.update', $permission) : route('access.permissions.store') }}"
      class="panel form-panel"
      data-loading>
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <h3 class="form-section-title">Holder</h3>
    <div class="form-grid">
        <div class="form-field">
            <label for="user_id">Employee <span class="req" aria-hidden="true">*</span></label>
            <select id="user_id" name="user_id" required @class(['is-invalid' => $errors->has('user_id')])>
                <option value="" disabled @selected(old('user_id', $permission?->user_id) === null)>Select an employee…</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" @selected((int) old('user_id', $permission?->user_id) === $employee->id)>{{ $employee->name }} — {{ $employee->role->label() }}</option>
                @endforeach
            </select>
            @error('user_id') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="badge_id">Badge ID <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="badge_id" name="badge_id" value="{{ old('badge_id', $permission?->badge_id) }}"
                   placeholder="BDG-0001" required maxlength="30" @class(['is-invalid' => $errors->has('badge_id')])>
            @error('badge_id') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="department">Department <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="department" name="department" list="department-options"
                   value="{{ old('department', $permission?->department) }}" required maxlength="100"
                   @class(['is-invalid' => $errors->has('department')])>
            <datalist id="department-options">
                @foreach ($departments as $department)
                    <option value="{{ $department }}"></option>
                @endforeach
            </datalist>
            @error('department') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="position">Position</label>
            <input type="text" id="position" name="position" value="{{ old('position', $permission?->position) }}" maxlength="100" placeholder="e.g. Network Engineer">
        </div>
    </div>

    <h3 class="form-section-title">Access Scope</h3>
    <div class="form-grid">
        <div class="form-field">
            <label for="access_level">Access Level <span class="req" aria-hidden="true">*</span></label>
            <select id="access_level" name="access_level" required>
                @foreach ($levels as $level)
                    <option value="{{ $level->value }}" @selected(old('access_level', $permission?->access_level?->value ?? 'reception') === $level->value)>{{ $level->label() }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-field">
            <label for="building">Building</label>
            <input type="text" id="building" name="building" value="{{ old('building', $permission?->building) }}" maxlength="100" placeholder="HQ Building A">
        </div>

        <div class="form-field">
            <label for="floor">Floor</label>
            <input type="text" id="floor" name="floor" value="{{ old('floor', $permission?->floor) }}" maxlength="50" placeholder="Floor 2">
        </div>

        <div class="form-field form-field-full">
            <span class="label-like">Allowed Doors <span class="req" aria-hidden="true">*</span></span>
            @error('doors') <p class="field-error" role="alert">{{ $message }}</p> @enderror
            <div class="check-grid">
                @php($selectedDoors = collect(old('doors', $permission?->doors?->pluck('id')->all() ?? []))->map(fn ($id) => (int) $id))
                @foreach ($doors as $door)
                    <label class="check-option">
                        <input type="checkbox" name="doors[]" value="{{ $door->id }}" @checked($selectedDoors->contains($door->id))>
                        {{ $door->name }} <span class="muted">({{ $door->building }})</span>
                    </label>
                @endforeach
            </div>
        </div>
    </div>

    <h3 class="form-section-title">Schedule & Validity</h3>
    <div class="form-grid">
        <div class="form-field form-field-full">
            <span class="label-like">Working Days</span>
            <div class="check-grid">
                @php($selectedDays = collect(old('working_days', $permission?->working_days ?? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'])))
                @foreach ($days as $day)
                    <label class="check-option">
                        <input type="checkbox" name="working_days[]" value="{{ $day }}" @checked($selectedDays->contains($day))>
                        {{ $day }}
                    </label>
                @endforeach
            </div>
        </div>

        <div class="form-field">
            <label for="start_time">Start Time</label>
            <input type="time" id="start_time" name="start_time"
                   value="{{ old('start_time', $permission?->start_time ? substr($permission->start_time, 0, 5) : '08:00') }}">
        </div>

        <div class="form-field">
            <label for="end_time">End Time</label>
            <input type="time" id="end_time" name="end_time"
                   value="{{ old('end_time', $permission?->end_time ? substr($permission->end_time, 0, 5) : '18:00') }}"
                   @class(['is-invalid' => $errors->has('end_time')])>
            @error('end_time') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="valid_from">Valid From <span class="req" aria-hidden="true">*</span></label>
            <input type="date" id="valid_from" name="valid_from"
                   value="{{ old('valid_from', $permission?->valid_from?->format('Y-m-d') ?? today()->format('Y-m-d')) }}" required>
        </div>

        <div class="form-field">
            <label for="valid_until">Valid Until <span class="label-hint">(empty = no expiry)</span></label>
            <input type="date" id="valid_until" name="valid_until"
                   value="{{ old('valid_until', $permission?->valid_until?->format('Y-m-d')) }}"
                   @class(['is-invalid' => $errors->has('valid_until')])>
            @error('valid_until') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <span class="label-like">State</span>
            <label class="check-option">
                <input type="hidden" name="active" value="0">
                <input type="checkbox" name="active" value="1" @checked(old('active', $permission?->active ?? true))>
                Active (uncheck to disable the badge)
            </label>
        </div>

        <div class="form-field form-field-full">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" rows="3" maxlength="1000"
                      placeholder="Anything the security team should know…">{{ old('notes', $permission?->notes) }}</textarea>
        </div>
    </div>

    <div class="form-actions">
        <a href="{{ route('access.index') }}" class="btn btn-ghost">Cancel</a>
        <button type="submit" class="btn btn-primary" data-loading-text="Saving…">{{ $editing ? 'Save Changes' : 'Create Permission' }}</button>
    </div>
</form>
