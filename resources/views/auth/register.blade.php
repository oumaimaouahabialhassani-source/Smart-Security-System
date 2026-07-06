@extends('layouts.guest')

@section('title', 'Create Account — ' . config('app.name'))

@section('content')

    <h2>Create Account</h2>

    <p>Register to access the security dashboard.</p>

    <form method="POST" action="{{ route('register.submit') }}" novalidate data-loading>
        @csrf

        <div class="name-grid">
            <div>
                <label for="first_name">First Name</label>
                <input
                    type="text"
                    id="first_name"
                    name="first_name"
                    value="{{ old('first_name') }}"
                    placeholder="First name"
                    autocomplete="given-name"
                    autofocus
                    required
                    aria-describedby="first_name-error"
                    @class(['is-invalid' => $errors->has('first_name')])
                    @if ($errors->has('first_name')) aria-invalid="true" @endif
                >
                @error('first_name')
                    <p class="field-error" id="first_name-error" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="last_name">Last Name</label>
                <input
                    type="text"
                    id="last_name"
                    name="last_name"
                    value="{{ old('last_name') }}"
                    placeholder="Last name"
                    autocomplete="family-name"
                    required
                    aria-describedby="last_name-error"
                    @class(['is-invalid' => $errors->has('last_name')])
                    @if ($errors->has('last_name')) aria-invalid="true" @endif
                >
                @error('last_name')
                    <p class="field-error" id="last_name-error" role="alert">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <label for="email">Email</label>
        <input
            type="email"
            id="email"
            name="email"
            value="{{ old('email') }}"
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

        <label for="password">Password</label>
        <div class="password-field">
            <input
                type="password"
                id="password"
                name="password"
                placeholder="At least 8 characters"
                autocomplete="new-password"
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

        <label for="password_confirmation">Confirm Password</label>
        <div class="password-field">
            <input
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                placeholder="Repeat your password"
                autocomplete="new-password"
                required
            >
            <x-password-toggle target="password_confirmation" />
        </div>

        <button type="submit" class="btn-login" data-loading-text="Creating account…">Create Account</button>

    </form>

    <p class="auth-switch">
        Already have an account?
        <a href="{{ route('login') }}">Sign in</a>
    </p>

@endsection
