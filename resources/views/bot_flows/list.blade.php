{{-- resources/views/bot_flow/index.blade.php --}}
@extends('layouts.app') {{-- or whatever layout you use --}}

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Chat Flow – Questions</h1>
        <a href="{{ route('bot.flow.create') }}" class="btn btn-primary">
            + Add Question
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if($questions->isEmpty())
        <div class="alert alert-info">
            No questions created yet. Click "Add Question" to start.
        </div>
    @else
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Key</th>
                    <th>Service</th>
                    <th>Store Field</th>
                    <th>Options</th>
                </tr>
            </thead>
            <tbody>
                @foreach($questions as $q)
                    <tr>
                        <td>{{ $q->id }}</td>
                        <td>{{ $q->key }}</td>
                        <td>{{ $q->service ?? '-' }}</td>
                        <td>{{ $q->store_field ?? '-' }}</td>
                        <td>{{ $q->options_count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
