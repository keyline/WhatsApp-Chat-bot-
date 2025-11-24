@extends('layouts.app') {{-- or whatever your layout name is --}}

@section('title', 'Messages')
@section('page_title', 'Messages')
@section('page_subtitle', 'See your full message history and statuses.')

@section('content')
<div class="card">
    <div class="card-header">
        <h2>Message History</h2>
        <a href="{{ route('messages.create') }}" class="btn btn-primary">
            + Send Message
        </a>
    </div>

    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($messages->isEmpty())
            <p>No messages yet. Click “Send Message” to send your first one.</p>
        @else
            <table class="table messages-table">
                <thead>
                    <tr>
                        <th>To</th>
                        <th>Template</th>
                        <th>Direction</th>
                        <th>Status</th>
                        <th>Sent At</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($messages as $msg)
                        <tr>
                            <td>{{ $msg->phone }}</td>
                            <td>{{ $msg->template_name ?? '-' }}</td>
                            <td>{{ strtoupper($msg->direction) }}</td>
                            <td>
                                <span>
                                    {{ ucfirst($msg->status) }}
                                </span>
                            </td>
                            <td>{{ $msg->created_at->format('d M Y, H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{ $messages->links() }}
        @endif
    </div>
</div>
@endsection
