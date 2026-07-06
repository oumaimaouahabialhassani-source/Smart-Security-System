@extends('layouts.app')

@section('title', 'Add User — ' . config('app.name'))

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-title">Add User</h1>
            <p class="page-subtitle">Create a new system user account.</p>
        </div>
        <a href="{{ route('users.index') }}" class="btn btn-secondary">← Back to Users</a>
    </div>

    @include('users._form', ['user' => null])

@endsection
