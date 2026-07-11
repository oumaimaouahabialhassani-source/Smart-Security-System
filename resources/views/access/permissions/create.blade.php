@extends('layouts.app')

@section('title', 'Add Access Permission — ' . config('app.name'))

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-title">Add Access Permission</h1>
            <p class="page-subtitle">Assign a badge, doors and a working schedule to an employee.</p>
        </div>
        <a href="{{ route('access.index') }}" class="btn btn-secondary">← Back to Access Control</a>
    </div>

    @include('access.permissions._form', ['permission' => null])

@endsection
