@extends('layouts.app')

@section('title', 'Add Camera — ' . config('app.name'))

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-title">Add Camera</h1>
            <p class="page-subtitle">Register a new security camera in the system.</p>
        </div>
        <a href="{{ route('cameras.index') }}" class="btn btn-secondary">← Back to Cameras</a>
    </div>

    @include('cameras._form', ['camera' => null])

@endsection
