<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name'))</title>
    @vite(['resources/css/login.css'])
</head>
<body>

    <div class="login-container">

        <div class="login-brand">
            <span class="login-shield" aria-hidden="true">
                <x-shield-logo />
            </span>
            <h1>{{ config('app.name') }}</h1>
        </div>

        @yield('content')

        <hr>

        <p class="copyright">© {{ date('Y') }} {{ config('app.name') }}. All Rights Reserved.</p>

    </div>

    <script>
        // Show/Hide toggles: each button targets the input in data-target.
        document.querySelectorAll('.toggle-password').forEach((toggle) => {
            const input = document.getElementById(toggle.dataset.target);
            if (! input) return;

            toggle.addEventListener('click', () => {
                const hidden = input.type === 'password';
                input.type = hidden ? 'text' : 'password';
                toggle.textContent = hidden ? 'Hide' : 'Show';
                toggle.setAttribute('aria-label', hidden ? 'Hide password' : 'Show password');
            });
        });

        // Loading state: disable the submit button once the form is submitted.
        document.querySelectorAll('form[data-loading]').forEach((form) => {
            form.addEventListener('submit', () => {
                const button = form.querySelector('button[type="submit"]');
                if (! button) return;

                button.disabled = true;
                button.classList.add('is-loading');
                button.textContent = button.dataset.loadingText || 'Please wait…';
            });
        });
    </script>

</body>
</html>
