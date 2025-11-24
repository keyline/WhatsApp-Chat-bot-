{{-- resources/views/bot-settings.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="page-header">
    <h1>Bots & Flows – Webhook</h1>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('bot.settings.update') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label">Webhook URL</label>
                <input type="text"
                       class="form-control"
                       value="{{ $webhookUrl }}"
                       readonly
                       onclick="this.select();">
                <small class="text-muted">
                    Use this URL as the webhook in your WhatsApp settings.
                </small>
            </div>

            <div class="mb-3">
                <label class="form-label">Bot Token</label>
                <input type="text"
                       name="bot_token"
                       class="form-control @error('bot_token') is-invalid @enderror"
                       value="{{ old('bot_token', $settings->bot_token) }}">
                @error('bot_token')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="text-muted">
                    You can customize the token; it becomes part of the webhook URL.
                </small>
            </div>

            <button type="submit" class="btn btn-primary">
                Save
            </button>
        </form>
    </div>
</div>
@endsection
