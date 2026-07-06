@extends('layouts.app')

@section('title', 'Edit Camera — ' . config('app.name'))

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-title">Edit Camera</h1>
            <p class="page-subtitle">Update {{ $camera->name }} ({{ $camera->camera_id }}).</p>
        </div>
        <a href="{{ route('cameras.index') }}" class="btn btn-secondary">← Back to Cameras</a>
    </div>

    @include('cameras._form')

@endsection
