@extends('layouts.app')

@section('title', 'Register Visitor — ' . config('app.name'))

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-title">Register Visitor</h1>
            <p class="page-subtitle">Record the visitor's identity, visit details and security checks.</p>
        </div>
        <a href="{{ route('visitors.index') }}" class="btn btn-secondary">← Back to Visitors</a>
    </div>

    @include('visitors._form', ['visit' => null])

@endsection
