@extends(auth()->check() ? 'layouts.app' : 'layouts.guest')

@section('title', '403 — Access Denied — ' . config('app.name'))

@section('content')

    <section class="panel" style="max-width:560px; margin:60px auto; text-align:center">
        <p class="stat-value" style="font-size:56px" aria-hidden="true">403</p>
        <h1 class="page-title">Access Denied</h1>
        <p class="page-subtitle">
            Your account ({{ auth()->user()?->role->label() ?? 'Guest' }}) does not have permission to open this page.
            If you believe this is a mistake, contact your administrator.
        </p>
        <p style="margin-top:18px">
            <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="btn btn-primary">
                {{ auth()->check() ? 'Back to Dashboard' : 'Go to Login' }}
            </a>
        </p>
    </section>

@endsection
