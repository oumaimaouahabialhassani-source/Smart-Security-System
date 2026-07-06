@extends('layouts.guest')

@section('title', 'Forgot Password — ' . config('app.name'))

@section('content')

    <h2>Forgot Password</h2>

    <p>Enter your email and we'll send you a link to reset your password.</p>

    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" novalidate data-loading>
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

        <button type="submit" class="btn-login" data-loading-text="Sending…">Send Reset Link</button>

    </form>

    <p class="auth-switch">
        Remembered your password?
        <a href="{{ route('login') }}">Back to login</a>
    </p>

@endsection
