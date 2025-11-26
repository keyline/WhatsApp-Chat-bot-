@extends('layouts.app')

@section('title', 'Campaigns — WhatsApp Campaigner')
@section('page_title', 'Campaigns')
@section('page_subtitle', 'Manage, schedule and track all your WhatsApp campaigns.')

@section('content')
    <section class="content-card">
        <div class="content-card-header">
            <h2>All Campaigns</h2>
            <button
                class="btn-primary"
                type="button"
                data-bs-toggle="modal"
                data-bs-target="#campaignModal"
            >
                + New Campaign
            </button>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Campaign Name</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Created On</th>
                    <th>Schedule Time</th>
                    <th>Messages Sent</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>

                @forelse ($campaigns as $campaign)
                    <tr>
                        <td>{{ $campaign->name }}</td>
                        <td>{{ ucfirst($campaign->type) }}</td>

                        <td>
                            @php
                                $statusClass = match($campaign->status) {
                                    'running' => 'status-live',
                                    'completed' => 'status-completed',
                                    'paused' => 'status-paused',
                                    default => 'status-live',
                                };
                            @endphp
                            <span >
                                {{ ucfirst($campaign->status) }}
                            </span>
                        </td>

                        <td>{{ $campaign->created_at->format('d M Y, H:i') }}</td>
                        <td>{{ $campaign->scheduled_at ? $campaign->scheduled_at->format('d M Y, H:i') : '-' }}</td>

                        {{-- Messages Sent --}}
                        <td>{{ $campaign->total_sent }}</td>

                        <td>
                            <a href="#" class="link-small">View</a> ·
                            <a href="#" class="link-small">Edit</a> ·
                            <a href="#" class="link-small">Report</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center; padding:20px; color:#9ca3af;">
                            No campaigns created yet. Click + New Campaign to start.
                        </td>
                    </tr>
                @endforelse

            </tbody>
        </table>
    </section>

    {{-- New Campaign Modal --}}
    <div class="modal fade" id="campaignModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h2 class="modal-title" style="color: black">New Campaign</h2>
                    <button type="button" class="btn-close" id="closeCampaignModal" data-bs-dismiss="modal"></button>
                </div>

                <form method="POST" action="{{ route('campaigns.store') }}">
                    @csrf

                    <div class="modal-body">

                        {{-- Campaign Name --}}
                        <div class="mb-3">
                            <label for="campaign_name" class="form-label">Campaign Name</label>
                            <input
                                type="text"
                                class="form-control"
                                id="campaign_name"
                                name="name"
                                value="{{ old('name') }}"
                                placeholder="Diwali Offer Broadcast"
                                required
                            >
                        </div>

                        {{-- Template --}}
                        <div class="mb-3">
                            <label for="campaign_template_name" class="form-label">Template Name</label>
                            <select class="form-select" id="campaign_template_name" name="template_name" required>
                                <option value="">Select a template</option>
                                @foreach($meta_templates as $tpl)
                                    <option value="{{ $tpl['name'] }}">
                                        {{ $tpl['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Audience --}}
                        <div class="mb-3">
                            <label class="form-label">Recipients</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input
                                        class="form-check-input"
                                        type="radio"
                                        name="audience_type"
                                        value="all"
                                        {{ old('audience_type', 'all') === 'all' ? 'checked' : '' }}>
                                    <label class="form-check-label" style="color:black;">All Contacts</label>
                                </div>

                                <div class="form-check">
                                    <input
                                        class="form-check-input"
                                        type="radio"
                                        name="audience_type"
                                        value="selected"
                                        {{ old('audience_type') === 'selected' ? 'checked' : '' }}>
                                    <label class="form-check-label" style="color:black;">Selected Only</label>
                                </div>
                            </div>
                        </div>

                        {{-- Selected Contacts --}}
                        <div class="mb-3" id="selectedContactsWrapper"
                            style="{{ old('audience_type') === 'selected' ? '' : 'display:none;' }}">
                            <label class="form-label">Select Contacts</label>

                            <div class="border rounded p-2" style="max-height:240px; overflow-y:auto;">
                                @forelse($contacts as $contact)
                                    @php
                                        $checked = in_array($contact->phone, old('selected_numbers', []));
                                    @endphp
                                    <div class="form-check mb-2">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            name="selected_numbers[]"
                                            value="{{ $contact->phone }}"
                                            {{ $checked ? 'checked' : '' }}>
                                        <label class="form-check-label">
                                            {{ $contact->name ?? '—' }}
                                            <span class="text-muted">({{ $contact->phone }})</span>
                                        </label>
                                    </div>
                                @empty
                                    <p class="text-muted small">No contacts found. Add some contacts first.</p>
                                @endforelse
                            </div>
                        </div>

                        {{-- Type --}}
                        <div class="mb-3">
                            <label for="campaign_type" class="form-label">Type</label>
                            <select class="form-select" id="campaign_type" name="type" required>
                                <option value="broadcast"  {{ old('type') === 'broadcast' ? 'selected' : '' }}>Broadcast</option>
                                <option value="automation" {{ old('type') === 'automation' ? 'selected' : '' }}>Automation</option>
                                <option value="bot"        {{ old('type') === 'bot' ? 'selected' : '' }}>Bot</option>
                            </select>
                        </div>
                        {{-- When to send --}}
                        <div class="mb-3">
                            <label class="form-label">When to send?</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input"
                                        type="radio"
                                        name="schedule_type"
                                        value="now"
                                        {{ old('schedule_type', 'now') === 'now' ? 'checked' : '' }}>
                                    <label class="form-check-label" style="color:black;">Send now</label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input"
                                        type="radio"
                                        name="schedule_type"
                                        value="once"
                                        {{ old('schedule_type') === 'once' ? 'checked' : '' }}>
                                    <label class="form-check-label" style="color:black;">Schedule once</label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input"
                                        type="radio"
                                        name="schedule_type"
                                        value="daily"
                                        {{ old('schedule_type') === 'daily' ? 'checked' : '' }}>
                                    <label class="form-check-label" style="color:black;">Repeat daily</label>
                                </div>
                            </div>
                        </div>

                        {{-- First run date/time --}}
                        @php
                            $showFirstRun = in_array(old('schedule_type', 'now'), ['once', 'daily']);
                        @endphp

                        <div class="mb-3" id="firstRunWrapper" style="{{ $showFirstRun ? '' : 'display:none;' }}">
                            <label for="campaign_scheduled_at" class="form-label">
                                First run at (for schedule / daily)
                            </label>
                            <input
                                type="datetime-local"
                                class="form-control"
                                id="campaign_scheduled_at"
                                name="scheduled_at"
                                value="{{ old('scheduled_at') }}"
                            >
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button"
                                id="cancelCampaignModal"
                                class="btn btn-secondary"
                                data-bs-dismiss="modal">
                            Cancel
                        </button>

                        <button type="submit" class="btn btn-success">
                            Create Campaign
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>


    {{-- Modal JS --}}
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // ===== Recipients (existing code) =====
        const audienceRadios   = document.querySelectorAll('input[name="audience_type"]');
        const selectedWrapper  = document.getElementById('selectedContactsWrapper');

        if (audienceRadios.length && selectedWrapper) {
            const updateAudienceVisibility = () => {
                const value = document.querySelector('input[name="audience_type"]:checked')?.value;
                selectedWrapper.style.display = (value === 'selected') ? '' : 'none';
            };

            audienceRadios.forEach(r => r.addEventListener('change', updateAudienceVisibility));
            updateAudienceVisibility();
        }

        // ===== Schedule type → show/hide datetime =====
        const scheduleRadios = document.querySelectorAll('input[name="schedule_type"]');
        const firstRunWrapper = document.getElementById('firstRunWrapper');

        if (scheduleRadios.length && firstRunWrapper) {
            const updateScheduleVisibility = () => {
                const value = document.querySelector('input[name="schedule_type"]:checked')?.value;
                if (value === 'now') {
                    firstRunWrapper.style.display = 'none';
                } else {
                    firstRunWrapper.style.display = '';
                }
            };

            scheduleRadios.forEach(r => r.addEventListener('change', updateScheduleVisibility));
            updateScheduleVisibility(); // set on page load
        }
    });
    </script>



@endsection
