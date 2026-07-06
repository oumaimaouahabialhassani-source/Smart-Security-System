@extends('layouts.app')

@section('title', 'Edit Device — ' . config('app.name'))

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-title">Edit Device</h1>
            <p class="page-subtitle">Update {{ $device->name }} ({{ $device->device_id }}).</p>
        </div>
        <a href="{{ route('devices.index') }}" class="btn btn-secondary">← Back to Devices</a>
    </div>

    @include('devices._form')

@endsection
