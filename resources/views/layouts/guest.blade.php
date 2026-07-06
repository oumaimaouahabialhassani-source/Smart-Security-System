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

    @include('partials.ui-scripts')

</body>
</html>
