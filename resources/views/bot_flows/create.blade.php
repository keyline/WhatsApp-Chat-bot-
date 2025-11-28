{{-- resources/views/bot_flow/create.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1>Create Chat Flow Question</h1>

    <a href="{{ route('bot.flow.index') }}" class="btn btn-link mb-3">&larr; Back to list</a>

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>There were some problems with your input:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('bot.flow.store') }}" method="POST">
        @csrf

        {{-- Question meta --}}
        <div class="card mb-4">
            <div class="card-header">
                Question Details
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Key (step name) *</label>
                    <input type="text" name="key" class="form-control" value="{{ old('key') }}" placeholder="e.g. ask_service" required>
                    <small class="text-muted">
                        This must match the step name you use in the bot (e.g. ask_service, web_type, dm_goal).
                    </small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Service (optional)</label>
                    <input type="text" name="service" class="form-control" value="{{ old('service') }}" placeholder="website, mobile_app, digital_marketing, branding or leave empty">
                    <small class="text-muted">
                        Leave empty if this question is common for all services.
                    </small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Message *</label>
                    <textarea name="message" rows="5" class="form-control" required>{{ old('message') }}</textarea>
                    <small class="text-muted">
                        This is the message that will be sent to the user at this step.
                    </small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Store Field (optional)</label>
                    <input type="text" name="store_field" class="form-control" value="{{ old('store_field') }}" placeholder="e.g. contact.name, website.type">
                    <small class="text-muted">
                        If set, the user's reply will be stored in this JSON path in conversation data.
                    </small>
                </div>
            </div>
        </div>

        {{-- Options --}}
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Options (user replies)</span>
                <button type="button" class="btn btn-sm btn-secondary" id="add-option-btn">+ Add Option</button>
            </div>

            <div class="card-body" id="options-wrapper">
                {{-- one example row --}}
                <div class="option-row border rounded p-3 mb-3">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Match Value *</label>
                            <input type="text" name="options[0][match_value]" class="form-control" placeholder="e.g. 1, yes, no" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Next Key</label>
                            <input type="text" name="options[0][next_key]" class="form-control" placeholder="e.g. web_type, app_platform">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Set Service</label>
                            <input type="text" name="options[0][set_service]" class="form-control" placeholder="website, mobile_app">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Default?</label><br>
                            <input type="checkbox" name="options[0][is_default]" value="1">
                            <small class="text-muted d-block">
                                Use exactly one default per question (for invalid input).
                            </small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Store Field</label>
                            <input type="text" name="options[0][store_field]" class="form-control" placeholder="e.g. website.type">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Store Value</label>
                            <input type="text" name="options[0][store_value]" class="form-control" placeholder="e.g. Business Website">
                        </div>
                    </div>

                    <button type="button" class="btn btn-sm btn-outline-danger remove-option-btn">
                        Remove this option
                    </button>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-success">
            Save Question & Options
        </button>
    </form>
</div>

{{-- simple JS repeater --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        let optionIndex = 1; // we already used 0

        const wrapper   = document.getElementById('options-wrapper');
        const addBtn    = document.getElementById('add-option-btn');

        addBtn.addEventListener('click', function () {
            const html = `
                <div class="option-row border rounded p-3 mb-3">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Match Value *</label>
                            <input type="text" name="options[${optionIndex}][match_value]" class="form-control" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Next Key</label>
                            <input type="text" name="options[${optionIndex}][next_key]" class="form-control">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Set Service</label>
                            <input type="text" name="options[${optionIndex}][set_service]" class="form-control">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Default?</label><br>
                            <input type="checkbox" name="options[${optionIndex}][is_default]" value="1">
                            <small class="text-muted d-block">
                                Use exactly one default per question.
                            </small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Store Field</label>
                            <input type="text" name="options[${optionIndex}][store_field]" class="form-control">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Store Value</label>
                            <input type="text" name="options[${optionIndex}][store_value]" class="form-control">
                        </div>
                    </div>

                    <button type="button" class="btn btn-sm btn-outline-danger remove-option-btn">
                        Remove this option
                    </button>
                </div>
            `;

            wrapper.insertAdjacentHTML('beforeend', html);
            optionIndex++;
        });

        wrapper.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-option-btn')) {
                e.target.closest('.option-row').remove();
            }
        });
    });
</script>

@endsection
