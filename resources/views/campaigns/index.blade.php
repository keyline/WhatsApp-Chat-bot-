@extends('layouts.app')

@section('title', 'Campaigns — WhatsApp Campaigner')
@section('page_title', 'Campaigns')
@section('page_subtitle', 'Manage, schedule and track all your WhatsApp campaigns.')

@section('content')
    <section class="content-card">
        <div class="content-card-header">
            <h2>All Campaigns</h2>
            <button class="btn-primary" id="openCampaignModal" type="button">
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
                        <td>{{ $campaign->scheduled_at->format('d M Y, H:i') }}</td>

                        <td>{{ $campaign->targets_count }}</td>

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
    <div id="campaignModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2>New Campaign</h2>
                <button type="button" class="modal-close" id="closeCampaignModal">
                    &times;
                </button>
            </div>

            <form method="POST" action="{{ route('campaigns.store') }}">
                @csrf

                <div class="modal-body">
                    <div class="auth-field">
                        <label for="campaign_name">Campaign Name</label>
                        <input
                            type="text"
                            id="campaign_name"
                            name="name"
                            value="{{ old('name') }}"
                            placeholder="Diwali Offer Broadcast"
                            required
                        >
                    </div>

                    <div class="auth-field">
                        <label for="campaign_template_name">Template Name</label>
                        <select id="campaign_template_name" name="template_name" required>
                            <option value="">Select a template</option>
                            @foreach($meta_templates as $tpl)
                                <option value="{{ $tpl['name'] }}">
                                    {{ $tpl['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Audience type --}}
                    <div class="auth-field">
                        <label>Recipients</label>
                        <div class="recipient-options" style="display:flex; gap:16px;">
                            <label>
                                <input
                                    type="radio"
                                    name="audience_type"
                                    value="all"
                                    {{ old('audience_type', 'all') === 'all' ? 'checked' : '' }}>
                                All Contacts
                            </label>
                            <label>
                                <input
                                    type="radio"
                                    name="audience_type"
                                    value="selected"
                                    {{ old('audience_type') === 'selected' ? 'checked' : '' }}>
                                Selected Only
                            </label>
                        </div>
                    </div>

                    {{-- Contacts list (only for "Selected Only") --}}
                    <div class="auth-field" id="selectedContactsWrapper"
                        style="{{ old('audience_type') === 'selected' ? '' : 'display:none;' }}">
                        <label>Select Contacts</label>

                        <div class="contacts-list-scroll" style="max-height:240px; overflow-y:auto; border:1px solid #1f2933; border-radius:8px; padding:8px;">
                            @forelse($contacts as $contact)
                                @php
                                    $checked = in_array($contact->phone, old('selected_numbers', []));
                                @endphp
                                <label class="contact-choice" style="display:flex; align-items:center; gap:8px; padding:4px 2px;">
                                    <input
                                        type="checkbox"
                                        name="selected_numbers[]"
                                        value="{{ $contact->phone }}"
                                        {{ $checked ? 'checked' : '' }}>
                                    <span>
                                        {{ $contact->name ?? '—' }}
                                        <span style="color:#9ca3af;">({{ $contact->phone }})</span>
                                    </span>
                                </label>
                            @empty
                                <p style="color:#9ca3af; font-size:13px; padding:4px 2px;">
                                    No contacts found. Add some contacts first.
                                </p>
                            @endforelse
                        </div>
                    </div>


                    <div class="auth-field">
                        <label for="campaign_type">Type</label>
                        <select id="campaign_type" name="type" required>
                            <option value="broadcast"  {{ old('type') === 'broadcast' ? 'selected' : '' }}>Broadcast</option>
                            <option value="automation" {{ old('type') === 'automation' ? 'selected' : '' }}>Automation</option>
                            <option value="bot"        {{ old('type') === 'bot' ? 'selected' : '' }}>Bot</option>
                        </select>
                    </div>

                    <div class="auth-field">
                        <label for="campaign_scheduled_at">Scheduled At (optional)</label>
                        <input
                            type="datetime-local"
                            id="campaign_scheduled_at"
                            name="scheduled_at"
                            value="{{ old('scheduled_at') }}"
                        >
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-ghost" id="cancelCampaignModal">
                        Cancel
                    </button>
                    <button type="submit" class="btn-primary">
                        Create Campaign
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal JS --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const openBtn   = document.getElementById('openCampaignModal');
            const modal     = document.getElementById('campaignModal');
            const closeBtn  = document.getElementById('closeCampaignModal');
            const cancelBtn = document.getElementById('cancelCampaignModal');

            if (!openBtn || !modal) return;

            const openModal  = () => modal.classList.add('is-open');
            const closeModal = () => modal.classList.remove('is-open');

            openBtn.addEventListener('click', openModal);
            closeBtn.addEventListener('click', closeModal);
            cancelBtn.addEventListener('click', closeModal);

            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') closeModal();
            });

            const audienceRadios = document.querySelectorAll('input[name="audience_type"]');
            const selectedWrapper = document.getElementById('selectedContactsWrapper');

            if (audienceRadios.length && selectedWrapper) {
                const updateVisibility = () => {
                    const value = document.querySelector('input[name="audience_type"]:checked')?.value;
                    if (value === 'selected') {
                        selectedWrapper.style.display = '';
                    } else {
                        selectedWrapper.style.display = 'none';
                    }
                };

                audienceRadios.forEach(r => r.addEventListener('change', updateVisibility));
                updateVisibility();
            }

        });
    </script>

@endsection
