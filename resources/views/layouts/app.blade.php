<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name'))</title>
    @vite(['resources/css/dashboard.css'])
</head>
<body>

<div class="layout">

    @include('partials.sidebar')

    <div class="main">

        @include('partials.topbar')

        <main class="content">

            @if (session('status'))
                <div class="flash flash-success" role="status">{{ session('status') }}</div>
            @endif

            @yield('content')

        </main>

        @include('partials.footer')

    </div>
</div>

</body>
</html>
