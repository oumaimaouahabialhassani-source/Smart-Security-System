{{-- Shared visit form: $visit is null on create, a Visit model on edit. --}}
@php($editing = $visit !== null)

<form method="POST"
      action="{{ $editing ? route('visitors.update', $visit) : route('visitors.store') }}"
      enctype="multipart/form-data"
      class="panel form-panel"
      data-loading>
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    {{-- Section 1 — Personal Information --}}
    <h3 class="form-section-title">Personal Information</h3>
    <div class="form-grid">

        <div class="form-field">
            <label for="full_name">Full Name <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="full_name" name="full_name"
                   value="{{ old('full_name', $visit?->full_name) }}"
                   placeholder="Visitor full name" required maxlength="150"
                   @class(['is-invalid' => $errors->has('full_name')])>
            @error('full_name') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="national_id">National ID / Passport <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="national_id" name="national_id"
                   value="{{ old('national_id', $visit?->national_id) }}"
                   placeholder="AB123456" required maxlength="50"
                   @class(['is-invalid' => $errors->has('national_id')])>
            @error('national_id') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="phone">Phone <span class="req" aria-hidden="true">*</span></label>
            <input type="tel" id="phone" name="phone"
                   value="{{ old('phone', $visit?->phone) }}"
                   placeholder="+212 600 000 000" required maxlength="30"
                   @class(['is-invalid' => $errors->has('phone')])>
            @error('phone') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="email">Email <span class="label-hint">(optional)</span></label>
            <input type="email" id="email" name="email"
                   value="{{ old('email', $visit?->email) }}"
                   placeholder="visitor@company.com"
                   @class(['is-invalid' => $errors->has('email')])>
            @error('email') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="gender">Gender</label>
            <select id="gender" name="gender" @class(['is-invalid' => $errors->has('gender')])>
                <option value="">Prefer not to say</option>
                <option value="male" @selected(old('gender', $visit?->gender) === 'male')>Male</option>
                <option value="female" @selected(old('gender', $visit?->gender) === 'female')>Female</option>
            </select>
            @error('gender') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="date_of_birth">Date of Birth</label>
            <input type="date" id="date_of_birth" name="date_of_birth"
                   value="{{ old('date_of_birth', $visit?->date_of_birth?->format('Y-m-d')) }}"
                   max="{{ today()->format('Y-m-d') }}"
                   @class(['is-invalid' => $errors->has('date_of_birth')])>
            @error('date_of_birth') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="nationality">Nationality</label>
            <input type="text" id="nationality" name="nationality"
                   value="{{ old('nationality', $visit?->nationality) }}"
                   placeholder="Moroccan" maxlength="100"
                   @class(['is-invalid' => $errors->has('nationality')])>
            @error('nationality') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="company">Company / Organization</label>
            <input type="text" id="company" name="company"
                   value="{{ old('company', $visit?->company) }}"
                   placeholder="Company name" maxlength="150"
                   @class(['is-invalid' => $errors->has('company')])>
            @error('company') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field form-field-full">
            <label for="photo">Profile Photo <span class="label-hint">(optional, max 2 MB)</span></label>
            <div class="avatar-upload">
                @if ($editing && $visit->photo_url)
                    <x-visitor-avatar :visit="$visit" size="lg" />
                @endif
                <input type="file" id="photo" name="photo" accept="image/*"
                       @class(['is-invalid' => $errors->has('photo')])>
            </div>
            @error('photo') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

    </div>

    {{-- Section 2 — Visit Information --}}
    <h3 class="form-section-title">Visit Information</h3>
    <div class="form-grid">

        <div class="form-field">
            <label for="host_user_id">Person to Visit <span class="req" aria-hidden="true">*</span></label>
            <select id="host_user_id" name="host_user_id" required @class(['is-invalid' => $errors->has('host_user_id')])>
                <option value="" disabled @selected(old('host_user_id', $visit?->host_user_id) === null)>Select an employee…</option>
                @foreach ($hosts as $host)
                    <option value="{{ $host->id }}" @selected((int) old('host_user_id', $visit?->host_user_id) === $host->id)>{{ $host->name }} — {{ $host->role->label() }}</option>
                @endforeach
            </select>
            @error('host_user_id') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="department">Department <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="department" name="department" list="department-options"
                   value="{{ old('department', $visit?->department) }}"
                   placeholder="Choose or type a department" required maxlength="100"
                   @class(['is-invalid' => $errors->has('department')])>
            <datalist id="department-options">
                @foreach ($departments as $department)
                    <option value="{{ $department }}"></option>
                @endforeach
            </datalist>
            @error('department') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="purpose">Visit Purpose <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="purpose" name="purpose"
                   value="{{ old('purpose', $visit?->purpose) }}"
                   placeholder="Business meeting, delivery, interview…" required maxlength="200"
                   @class(['is-invalid' => $errors->has('purpose')])>
            @error('purpose') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="visit_date">Visit Date <span class="req" aria-hidden="true">*</span></label>
            <input type="date" id="visit_date" name="visit_date"
                   value="{{ old('visit_date', $visit?->visit_date?->format('Y-m-d') ?? today()->format('Y-m-d')) }}"
                   required
                   @class(['is-invalid' => $errors->has('visit_date')])>
            @error('visit_date') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="expected_check_in">Expected Check-In Time</label>
            <input type="time" id="expected_check_in" name="expected_check_in"
                   value="{{ old('expected_check_in', $visit?->expected_check_in ? substr($visit->expected_check_in, 0, 5) : null) }}"
                   @class(['is-invalid' => $errors->has('expected_check_in')])>
            @error('expected_check_in') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="expected_duration_minutes">Expected Duration (minutes) <span class="req" aria-hidden="true">*</span></label>
            <input type="number" id="expected_duration_minutes" name="expected_duration_minutes"
                   value="{{ old('expected_duration_minutes', $visit?->expected_duration_minutes ?? 60) }}"
                   min="5" max="1440" step="5" required
                   @class(['is-invalid' => $errors->has('expected_duration_minutes')])>
            @error('expected_duration_minutes') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="companions">Number of Companions</label>
            <input type="number" id="companions" name="companions"
                   value="{{ old('companions', $visit?->companions ?? 0) }}"
                   min="0" max="50"
                   @class(['is-invalid' => $errors->has('companions')])>
            @error('companions') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="vehicle_plate">Vehicle Plate Number</label>
            <input type="text" id="vehicle_plate" name="vehicle_plate"
                   value="{{ old('vehicle_plate', $visit?->vehicle_plate) }}"
                   placeholder="12345-A-6" maxlength="30"
                   @class(['is-invalid' => $errors->has('vehicle_plate')])>
            @error('vehicle_plate') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

    </div>

    {{-- Section 3 — Security Information --}}
    <h3 class="form-section-title">Security Information</h3>
    <div class="form-grid">

        <div class="form-field">
            <label for="document_type">Identity Document Type <span class="req" aria-hidden="true">*</span></label>
            <select id="document_type" name="document_type" required @class(['is-invalid' => $errors->has('document_type')])>
                @foreach ($documentTypes as $type)
                    <option value="{{ $type->value }}" @selected(old('document_type', $visit?->document_type?->value ?? 'national_id') === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </select>
            @error('document_type') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="badge_number">Visitor Badge Number <span class="label-hint">(auto-generated at check-in if empty)</span></label>
            <input type="text" id="badge_number" name="badge_number"
                   value="{{ old('badge_number', $visit?->badge_number) }}"
                   placeholder="BDG-XXXXXX" maxlength="30"
                   @class(['is-invalid' => $errors->has('badge_number')])>
            @error('badge_number') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="access_level">Access Level <span class="req" aria-hidden="true">*</span></label>
            <select id="access_level" name="access_level" required @class(['is-invalid' => $errors->has('access_level')])>
                @foreach ($accessLevels as $level)
                    <option value="{{ $level->value }}" @selected(old('access_level', $visit?->access_level?->value ?? 'reception') === $level->value)>{{ $level->label() }}</option>
                @endforeach
            </select>
            @error('access_level') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <span class="label-like">Checks</span>
            <label class="check-option">
                <input type="hidden" name="bag_inspected" value="0">
                <input type="checkbox" name="bag_inspected" value="1" @checked(old('bag_inspected', $visit?->bag_inspected))>
                Bag inspected
            </label>
            <label class="check-option">
                <input type="hidden" name="special_permission" value="0">
                <input type="checkbox" name="special_permission" value="1" @checked(old('special_permission', $visit?->special_permission))>
                Requires special permission
            </label>
            <label class="check-option check-option-danger">
                <input type="hidden" name="blacklisted" value="0">
                <input type="checkbox" name="blacklisted" value="1" @checked(old('blacklisted', $visit?->blacklisted))>
                Blacklisted (check-in will be refused)
            </label>
        </div>

        <div class="form-field form-field-full">
            <label for="security_notes">Security Notes</label>
            <textarea id="security_notes" name="security_notes" rows="3" maxlength="1000"
                      placeholder="Anything the security team should know…"
                      @class(['is-invalid' => $errors->has('security_notes')])>{{ old('security_notes', $visit?->security_notes) }}</textarea>
            @error('security_notes') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

    </div>

    <div class="form-actions">
        <a href="{{ route('visitors.index') }}" class="btn btn-ghost">Cancel</a>
        <button type="submit" class="btn btn-primary" data-loading-text="Saving…">{{ $editing ? 'Save Changes' : 'Register Visitor' }}</button>
    </div>
</form>
