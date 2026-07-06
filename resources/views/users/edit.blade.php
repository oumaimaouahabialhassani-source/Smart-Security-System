@extends('layouts.app')

@section('title', 'Edit User — ' . config('app.name'))

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-title">Edit User</h1>
            <p class="page-subtitle">Update {{ $user->name }}'s account information.</p>
        </div>
        <a href="{{ route('users.index') }}" class="btn btn-secondary">← Back to Users</a>
    </div>

    @include('users._form')

@endsection
