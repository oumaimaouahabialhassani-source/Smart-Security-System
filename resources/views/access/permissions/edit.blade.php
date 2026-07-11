@extends('layouts.app')

@section('title', 'Edit Access Permission — ' . config('app.name'))

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-title">Edit Permission — {{ $permission->badge_id }}</h1>
            <p class="page-subtitle">Update access rights for {{ $permission->holderName() }}.</p>
        </div>
        <a href="{{ route('access.index') }}" class="btn btn-secondary">← Back to Access Control</a>
    </div>

    @include('access.permissions._form')

@endsection
