@extends('layouts.guest')

@section('title', 'Login — ' . config('app.name'))

@section('content')

    <h2>Login</h2>

    <p>Please sign in to continue.</p>

    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login.submit') }}" novalidate data-loading>
        @csrf

        <label for="email">Email</label>
        <input
            type="email"
            id="email"
            name="email"
            value="{{ old('email') }}"
            placeholder="Enter your email"
            autocomplete="email"
            autofocus
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
                placeholder="Enter your password"
                autocomplete="current-password"
                required
                aria-describedby="password-error"
                @class(['is-invalid' => $errors->has('password')])
                @if ($errors->has('password')) aria-invalid="true" @endif
            >
            <button type="button" class="toggle-password" data-target="password" aria-label="Show password">
                Show
            </button>
        </div>
        @error('password')
            <p class="field-error" id="password-error" role="alert">{{ $message }}</p>
        @enderror

        <div class="form-row">
            <label class="remember">
                <input type="checkbox" name="remember" @checked(old('remember'))>
                Remember Me
            </label>

            <a href="#" class="forgot-link">Forgot Password?</a>
        </div>

        <button type="submit" class="btn-login" data-loading-text="Signing in…">Login</button>

    </form>

    <p class="auth-switch">
        Don't have an account?
        <a href="{{ route('register') }}">Create one</a>
    </p>

@endsection
