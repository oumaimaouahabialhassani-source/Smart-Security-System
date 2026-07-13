@extends('layouts.app')

@section('title', 'Help & Support — ' . config('app.name'))

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-title">Help &amp; Support</h1>
            <p class="page-subtitle">Quick answers about the Smart Security System, and how to reach the team.</p>
        </div>
    </div>

    <section class="panels-grid">
        <div class="panel">
            <h2 class="panel-title">Getting Started</h2>
            <dl class="profile-grid">
                <div class="profile-item profile-item-wide"><dt>Dashboard</dt><dd>Live overview of cameras, devices, visitors and alerts. Your role decides which modules appear in the sidebar.</dd></div>
                <div class="profile-item profile-item-wide"><dt>Alerts &amp; AI Security Bot</dt><dd>The Alerts page collects every system alert; the AI Security Bot analyzes events continuously, scores their risk and recommends actions.</dd></div>
                <div class="profile-item profile-item-wide"><dt>Notifications</dt><dd>The bell in the top bar and the Notifications page show alerts addressed to you. Configure preferences from the Alerts page.</dd></div>
                <div class="profile-item profile-item-wide"><dt>Your Profile</dt><dd>Update your name, phone, avatar and password from the Profile page.</dd></div>
            </dl>
        </div>

        <div class="panel">
            <h2 class="panel-title">Roles &amp; Access</h2>
            <dl class="profile-grid">
                <div class="profile-item profile-item-wide"><dt>Super Admin</dt><dd>Unrestricted access — manages users, hardware, settings and every module. Only one exists.</dd></div>
                <div class="profile-item profile-item-wide"><dt>Viewer</dt><dd>Read-only access to every operational module: dashboards, cameras, live monitoring, visitors, devices, biometrics, access logs, alerts, AI analytics and reports. Cannot change anything.</dd></div>
            </dl>

            <h3 class="form-section-title">Contact Support</h3>
            <dl class="profile-grid">
                <div class="profile-item"><dt>Email</dt><dd>support@smartsecurity.test</dd></div>
                <div class="profile-item"><dt>Phone</dt><dd>+212 600 000 000</dd></div>
                <div class="profile-item profile-item-wide"><dt>Documentation</dt><dd>See <span class="mono">docs/AI_SECURITY_BOT.md</span> in the project repository.</dd></div>
            </dl>
        </div>
    </section>

@endsection
