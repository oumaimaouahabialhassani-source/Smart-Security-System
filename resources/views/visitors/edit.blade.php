@extends('layouts.app')

@section('title', 'Edit Visit — ' . config('app.name'))

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-title">Edit Visit {{ $visit->visit_code }}</h1>
            <p class="page-subtitle">Update visit information for {{ $visit->full_name }}.</p>
        </div>
        <a href="{{ route('visitors.index') }}" class="btn btn-secondary">← Back to Visitors</a>
    </div>

    @include('visitors._form')

@endsection
