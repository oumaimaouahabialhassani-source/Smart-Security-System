{{-- Shared user form: $user is null on create, a User model on edit. --}}
@php($editing = $user !== null)

<form method="POST"
      action="{{ $editing ? route('users.update', $user) : route('users.store') }}"
      enctype="multipart/form-data"
      class="panel form-panel"
      data-loading>
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <div class="form-grid">

        <div class="form-field">
            <label for="first_name">First Name <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="first_name" name="first_name"
                   value="{{ old('first_name', $user?->first_name) }}"
                   placeholder="First name" required maxlength="100"
                   @class(['is-invalid' => $errors->has('first_name')])>
            @error('first_name') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="last_name">Last Name <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="last_name" name="last_name"
                   value="{{ old('last_name', $user?->last_name) }}"
                   placeholder="Last name" required maxlength="100"
                   @class(['is-invalid' => $errors->has('last_name')])>
            @error('last_name') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="email">Email <span class="req" aria-hidden="true">*</span></label>
            <input type="email" id="email" name="email"
                   value="{{ old('email', $user?->email) }}"
                   placeholder="user@company.com" required
                   @class(['is-invalid' => $errors->has('email')])>
            @error('email') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="phone">Phone <span class="req" aria-hidden="true">*</span></label>
            <input type="tel" id="phone" name="phone"
                   value="{{ old('phone', $user?->phone) }}"
                   placeholder="+212 600 000 000" required maxlength="30"
                   @class(['is-invalid' => $errors->has('phone')])>
            @error('phone') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        @if ($editing)
            <div class="form-field">
                <label for="password">
                    Password
                    <span class="label-hint">(leave blank to keep current)</span>
                </label>
                <div class="password-field">
                    <input type="password" id="password" name="password"
                           placeholder="At least 8 characters"
                           autocomplete="new-password" minlength="8"
                           @class(['is-invalid' => $errors->has('password')])>
                    <x-password-toggle target="password" />
                </div>
                @error('password') <p class="field-error" role="alert">{{ $message }}</p> @enderror
            </div>

            <div class="form-field">
                <label for="password_confirmation">Confirm Password</label>
                <div class="password-field">
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           placeholder="Repeat the password"
                           autocomplete="new-password" minlength="8">
                    <x-password-toggle target="password_confirmation" />
                </div>
            </div>
        @else
            <div class="form-field form-field-full">
                <div class="info-note" role="note">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    No password needed — the user will receive a welcome email with a link to choose their own password.
                </div>
            </div>
        @endif

        {{-- Roles are never chosen here: new accounts are always
             Viewer, and promotion happens from the Users table
             (Super Admin only). --}}
        <div class="form-field">
            <label for="role-display">Role</label>
            <input type="text" id="role-display" value="{{ $editing ? $user->role->label() : 'Viewer (default)' }}" disabled>
            <p class="muted" style="font-size:12.5px; margin:6px 0 0">{{ $editing ? 'Change it with Promote/Demote on the Users page.' : 'Every new account starts as a read-only Viewer.' }}</p>
        </div>

        <div class="form-field">
            <label for="status">Status <span class="req" aria-hidden="true">*</span></label>
            <select id="status" name="status" required @class(['is-invalid' => $errors->has('status')])>
                <option value="" disabled @selected(old('status', $user?->status?->value) === null)>Select a status…</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(old('status', $user?->status?->value) === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            @error('status') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field form-field-full">
            <label for="avatar">Profile Image <span class="label-hint">(optional, max 2 MB)</span></label>
            <div class="avatar-upload">
                @if ($editing && $user->avatar_url)
                    <x-user-avatar :user="$user" size="lg" />
                @endif
                <input type="file" id="avatar" name="avatar" accept="image/*"
                       @class(['is-invalid' => $errors->has('avatar')])>
            </div>
            @error('avatar') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

    </div>

    <div class="form-actions">
        <a href="{{ route('users.index') }}" class="btn btn-ghost">Cancel</a>
        <button type="submit" class="btn btn-primary" data-loading-text="Saving…">Save</button>
    </div>
</form>
