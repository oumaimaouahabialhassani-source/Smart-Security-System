@extends('layouts.guest')

@section('title', ($welcome ? 'Set Your Password' : 'Reset Password') . ' — ' . config('app.name'))

@section('content')

    <h2>{{ $welcome ? 'Welcome!' : 'Reset Password' }}</h2>

    <p>{{ $welcome ? 'Your account is ready — choose a password to activate it.' : 'Choose a new password for your account.' }}</p>

    <form method="POST" action="{{ route('password.update') }}" novalidate data-loading>
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <label for="email">Email</label>
        <input
            type="email"
            id="email"
            name="email"
            value="{{ old('email', $email) }}"
            placeholder="Enter your email"
            autocomplete="email"
            required
            aria-describedby="email-error"
            @class(['is-invalid' => $errors->has('email')])
            @if ($errors->has('email')) aria-invalid="true" @endif
        >
        @error('email')
            <p class="field-error" id="email-error" role="alert">{{ $message }}</p>
        @enderror

        <label for="password">New Password</label>
        <div class="password-field">
            <input
                type="password"
                id="password"
                name="password"
                placeholder="At least 8 characters"
                autocomplete="new-password"
                autofocus
                required
                aria-describedby="password-error"
                @class(['is-invalid' => $errors->has('password')])
                @if ($errors->has('password')) aria-invalid="true" @endif
            >
            <x-password-toggle target="password" />
        </div>
        @error('password')
            <p class="field-error" id="password-error" role="alert">{{ $message }}</p>
        @enderror

        <label for="password_confirmation">Confirm New Password</label>
        <div class="password-field">
            <input
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                placeholder="Repeat your new password"
                autocomplete="new-password"
                required
            >
            <x-password-toggle target="password_confirmation" />
        </div>

        <button type="submit" class="btn-login" data-loading-text="Saving…">{{ $welcome ? 'Set Password & Activate' : 'Reset Password' }}</button>

    </form>

    <p class="auth-switch">
        <a href="{{ route('login') }}">Back to login</a>
    </p>

@endsection
