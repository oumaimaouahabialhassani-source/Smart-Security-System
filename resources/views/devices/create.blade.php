@extends('layouts.app')

@section('title', 'Add Device — ' . config('app.name'))

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-title">Add Device</h1>
            <p class="page-subtitle">Register a new IoT device in the system.</p>
        </div>
        <a href="{{ route('devices.index') }}" class="btn btn-secondary">← Back to Devices</a>
    </div>

    @include('devices._form', ['device' => null])

@endsection
