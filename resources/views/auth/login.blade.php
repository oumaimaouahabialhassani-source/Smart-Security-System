<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} - Login</title>
    @vite(['resources/css/login.css'])
</head>
<body>

    <div class="login-container">

        <div class="login-brand">
            <span class="login-shield" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6l8-4z"/>
                    <path d="M9 12l2 2 4-4"/>
                </svg>
            </span>
            <h1>{{ config('app.name') }}</h1>
        </div>

        <h2>Login</h2>

        <p>Please sign in to continue.</p>

        @if ($errors->any())
            <div class="alert alert-error" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.submit') }}" novalidate>
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
                @class(['is-invalid' => $errors->has('email')])
            >

            <label for="password">Password</label>
            <div class="password-field">
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required
                    @class(['is-invalid' => $errors->has('password')])
                >
                <button type="button" class="toggle-password" id="toggle-password" aria-label="Show password">
                    Show
                </button>
            </div>

            <div class="form-row">
                <label class="remember">
                    <input type="checkbox" name="remember" @checked(old('remember'))>
                    Remember Me
                </label>

                <a href="#" class="forgot-link">Forgot Password?</a>
            </div>

            <button type="submit" class="btn-login">Login</button>

        </form>

        <hr>

        <p class="copyright">© {{ date('Y') }} {{ config('app.name') }}. All Rights Reserved.</p>

    </div>

    <script>
        const toggle = document.getElementById('toggle-password');
        const password = document.getElementById('password');

        toggle.addEventListener('click', () => {
            const hidden = password.type === 'password';
            password.type = hidden ? 'text' : 'password';
            toggle.textContent = hidden ? 'Hide' : 'Show';
            toggle.setAttribute('aria-label', hidden ? 'Hide password' : 'Show password');
        });
    </script>

</body>
</html>
