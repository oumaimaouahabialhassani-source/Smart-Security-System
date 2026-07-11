<!DOCTYPE html>
@php($theme = \App\Models\Setting::get('appearance.theme', 'system'))
@php($accent = \App\Models\Setting::get('appearance.accent_color'))
@php($favicon = \App\Models\Setting::get('appearance.favicon'))
<html lang="en" @class(['theme-dark' => $theme === 'dark', 'theme-light' => $theme === 'light'])>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name'))</title>
    @if ($favicon)
        <link rel="icon" href="{{ $favicon }}">
    @endif
    @vite(['resources/css/dashboard.css'])
    @if ($accent)
        @php([$r, $gr, $b] = sscanf($accent, '#%02x%02x%02x'))
        <style>
            :root{
                --accent: {{ $accent }};
                --accent-dark: {{ $accent }};
                --accent-soft: rgba({{ $r }}, {{ $gr }}, {{ $b }}, 0.12);
                --accent-border: rgba({{ $r }}, {{ $gr }}, {{ $b }}, 0.35);
            }
        </style>
    @endif
</head>
<body>

<div @class(['layout', 'sidebar-compact' => \App\Models\Setting::get('appearance.sidebar_style') === 'compact', 'content-wide' => \App\Models\Setting::get('appearance.dashboard_layout') === 'wide'])>

    @include('partials.sidebar')

    <div class="main">

        @include('partials.topbar')

        <main class="content">

            @if (session('status'))
                <div class="flash flash-success" role="status">{{ session('status') }}</div>
            @endif

            @if (session('error'))
                <div class="flash flash-error" role="alert">{{ session('error') }}</div>
            @endif

            @yield('content')

        </main>

        @include('partials.footer')

    </div>
</div>

@include('partials.ui-scripts')

@stack('scripts')

</body>
</html>
