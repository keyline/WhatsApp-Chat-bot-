@extends('layouts.app')

@section('title', 'Send Message')
@section('page_title', 'Send Message')
@section('page_subtitle', 'Send a template message to an individual number.')

@section('content')
<div class="card">
    <div class="card-header">
        <h2>Send WhatsApp Message</h2>
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route('messages.store') }}">
            @csrf

            <div class="auth-field">
                <label for="phone">WhatsApp Number</label>
                <input
                    type="text"
                    id="phone"
                    name="phone"
                    placeholder="+91 98765 43210"
                     class="form-control"
                    required
                >
                @error('phone')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="auth-field">
                <label for="template_name">Template</label>
                <select id="template_name" name="template_name" class="form-select" required>
                    <option value="">Select a template</option>
                    @foreach($meta_templates as $tpl)
                        <option value="{{ $tpl['name'] }}">
                            {{ $tpl['name'] }}
                        </option>
                    @endforeach
                </select>
                @error('template_id')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="modal-footer mt-3" style="justify-content: flex-end; padding: 0;">
                <a href="{{ route('messages.index') }}" class="btn btn-danger">Cancel</a>
                <button type="submit" class="btn-primary">Send Message</button>
            </div>
        </form>
    </div>
</div>
@endsection
