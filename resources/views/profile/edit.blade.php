@extends('layouts.app')

@section('title', 'My Profile — ' . config('app.name'))

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-title">My Profile</h1>
            <p class="page-subtitle">Update your personal information, photo and password.</p>
        </div>
    </div>

    <div class="panels-grid">

        {{-- Personal information --}}
        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data"
              class="panel form-panel" data-loading>
            @csrf
            @method('PUT')

            <h2 class="panel-title">Personal Information</h2>

            <div class="profile-avatar-row">
                <x-user-avatar :user="$user" size="lg" />
                <div class="form-field">
                    <label for="avatar">Profile photo</label>
                    <input type="file" id="avatar" name="avatar" accept="image/*"
                           @class(['is-invalid' => $errors->has('avatar')])>
                    @error('avatar') <p class="field-error" role="alert">{{ $message }}</p> @enderror
                    <p class="label-hint">JPG, PNG or WebP — 2 MB max.</p>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-field">
                    <label for="first_name">First Name <span class="req" aria-hidden="true">*</span></label>
                    <input type="text" id="first_name" name="first_name"
                           value="{{ old('first_name', $user->first_name) }}" required maxlength="100"
                           @class(['is-invalid' => $errors->has('first_name')])>
                    @error('first_name') <p class="field-error" role="alert">{{ $message }}</p> @enderror
                </div>

                <div class="form-field">
                    <label for="last_name">Last Name <span class="req" aria-hidden="true">*</span></label>
                    <input type="text" id="last_name" name="last_name"
                           value="{{ old('last_name', $user->last_name) }}" required maxlength="100"
                           @class(['is-invalid' => $errors->has('last_name')])>
                    @error('last_name') <p class="field-error" role="alert">{{ $message }}</p> @enderror
                </div>

                <div class="form-field">
                    <label for="email">Email <span class="req" aria-hidden="true">*</span></label>
                    <input type="email" id="email" name="email"
                           value="{{ old('email', $user->email) }}" required
                           @class(['is-invalid' => $errors->has('email')])>
                    @error('email') <p class="field-error" role="alert">{{ $message }}</p> @enderror
                </div>

                <div class="form-field">
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone"
                           value="{{ old('phone', $user->phone) }}" maxlength="30"
                           placeholder="+212 6 00 00 00 00"
                           @class(['is-invalid' => $errors->has('phone')])>
                    @error('phone') <p class="field-error" role="alert">{{ $message }}</p> @enderror
                </div>

                <div class="form-field">
                    <label>Role</label>
                    <input type="text" value="{{ $user->role->label() }}" disabled>
                    <p class="label-hint">Roles are managed by an administrator from the Users module.</p>
                </div>

                <div class="form-field">
                    <label>Account Status</label>
                    <input type="text" value="{{ $user->status->label() }}" disabled>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>

        {{-- Change password --}}
        <form method="POST" action="{{ route('profile.password') }}" class="panel form-panel" data-loading>
            @csrf
            @method('PUT')

            <h2 class="panel-title">Change Password</h2>

            <div class="form-field">
                <label for="current_password">Current Password <span class="req" aria-hidden="true">*</span></label>
                <div class="password-field">
                    <input type="password" id="current_password" name="current_password" required
                           autocomplete="current-password"
                           @class(['is-invalid' => $errors->has('current_password')])>
                    <x-password-toggle target="current_password" />
                </div>
                @error('current_password') <p class="field-error" role="alert">{{ $message }}</p> @enderror
            </div>

            <div class="form-field">
                <label for="password">New Password <span class="req" aria-hidden="true">*</span></label>
                <div class="password-field">
                    <input type="password" id="password" name="password" required
                           autocomplete="new-password"
                           @class(['is-invalid' => $errors->has('password')])>
                    <x-password-toggle target="password" />
                </div>
                @error('password') <p class="field-error" role="alert">{{ $message }}</p> @enderror
            </div>

            <div class="form-field">
                <label for="password_confirmation">Confirm New Password <span class="req" aria-hidden="true">*</span></label>
                <div class="password-field">
                    <input type="password" id="password_confirmation" name="password_confirmation" required
                           autocomplete="new-password">
                    <x-password-toggle target="password_confirmation" />
                </div>
            </div>

            <p class="label-hint">The password must satisfy the security policy defined in Settings.</p>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Change Password</button>
            </div>
        </form>

    </div>

@endsection
