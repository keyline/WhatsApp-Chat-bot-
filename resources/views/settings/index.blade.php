@extends('layouts.app')

@section('title', 'Settings — WhatsApp Campaigner')
@section('page_title', 'Settings')
@section('page_subtitle', 'Configure WhatsApp API, webhooks, team access and billing.')

@section('content')
    <section>
        {{-- WhatsApp API SETTINGS --}}
        <div class="content-card">
            <div class="content-card-header">
                <h2>WhatsApp API Configuration</h2>
            </div>
            <ul class="status-list">
                <li>
                    <strong>WhatsApp Business Account ID</strong><br>
                    <span class="page-subtitle">
                        {{ $settings->business_account_id ?? 'Not configured' }}
                    </span>
                </li>
                <li>
                    <strong>Phone Number ID</strong><br>
                    <span class="page-subtitle">
                        {{ $settings->phone_number_id ?? 'Not configured' }}
                    </span>
                </li>
                <li>
                    <strong>WhatsApp Number</strong><br>
                    <span class="page-subtitle">
                        {{ $settings->whatsapp_number ?? 'Not linked' }}
                    </span>
                </li>
                <li>
                    <strong>Access Token</strong><br>
                    <span class="page-subtitle">
                        @if($settings->access_token)
                            {{ str_repeat('•', 20) }}
                        @else
                            Not set
                        @endif
                    </span>
                </li>
            </ul>

            {{-- <button class="btn-ghost" style="margin-top: 10px;">
                Edit API Credentials
            </button>
            <button class="btn-ghost" style="margin-top: 10px;">
               + Add API Credentials
            </button> --}}
            <button
                type="button"
                class="btn-ghost"
                style="margin-top: 10px;"
                data-open-api-modal
                data-mode="edit"
                data-business-account-id="{{ $settings->business_account_id ?? '' }}"
                data-phone-number-id="{{ $settings->phone_number_id ?? '' }}"
                data-whatsapp-number="{{ $settings->whatsapp_number ?? '' }}"
                data-access-token="{{ $settings->access_token ?? '' }}"
                @if(!$settings) disabled @endif
            >
                Edit API Credentials
            </button>
             
            <?php if(empty($settings)){ ?>
            <button
                type="button"
                class="btn-ghost"
                style="margin-top: 10px;"
                data-open-api-modal
                data-mode="add"
            >
            + Add API Credentials
            </button>
             <?php } ?>
            <div class="divider"></div>

            {{-- WEBHOOK INFO --}}
            <div class="content-card-header">
                <h2>Webhook</h2>
            </div>
            <p class="page-subtitle">
                Use this URL in Meta Developers to receive incoming messages and status updates.
            </p>
            <code style="font-size: 12px;">
                {{ $settings->webhook_url ?? url('/webhooks/whatsapp') }}
            </code>

            <ul class="status-list" style="margin-top: 8px;">
                <li>
                    <span class="status-dot status-dot-green"></span>
                    Webhook {{ $settings->verify_token ? 'Configured' : 'Not verified yet' }}
                </li>
                <li>
                    <span class="status-dot status-dot-green"></span>
                    Subscribed events: messages, statuses
                </li>
            </ul>
        </div>

        {{-- TEAM & BILLING (When need to open this part, also must add this class to the section tag 
        <section class="content-grid">) --}}
        {{-- <div class="content-card">
            <div class="content-card-header">
                <h2>Team & Billing</h2>
            </div>

            <h3 style="font-size: 13px; margin-top: 0;">Team Members</h3>
            <ul class="status-list">
                <li>{{ $owner->name }} (Owner)</li>
                @forelse($teamMembers as $member)
                    <li>{{ $member->email }} (Member)</li>
                @empty
                    <li>No additional team members yet.</li>
                @endforelse
            </ul>
            <button class="btn-ghost" style="margin-top: 6px;">Invite Member</button>

            <div class="divider"></div>

            <h3 style="font-size: 13px;">Plan & Usage</h3>
            <ul class="status-list">
                <li>Current Plan: <strong>{{ $planName }}</strong></li>
                <li>Monthly Limit: {{ number_format($monthlyLimit) }} conversations</li>
                <li>Used this month: {{ number_format($usedThisMonth) }}</li>
            </ul>
            <button class="btn-primary" style="margin-top: 6px;">Upgrade Plan</button>
        </div> --}}
    </section>

     {{-- API CREDENTIALS MODAL --}}
    <div id="api-modal" class="modal-overlay w-50 h-75 m-auto p-5">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="api-modal-title">Add API Credentials</h2>
                <button type="button" class="btn-ghost" data-close-api-modal>&times;</button>
            </div>

            <form id="api-form" method="POST" action="{{ route('settings.api.save') }}">
                @csrf
                {{-- Single save route will decide create/update based on $settings --}}
                
                <div class="form-group">
                    <label for="business_account_id">WhatsApp Business Account ID</label>
                    <input
                        type="text"
                        id="business_account_id"
                        name="business_account_id"
                        class="input"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="phone_number_id">Phone Number ID</label>
                    <input
                        type="text"
                        id="phone_number_id"
                        name="phone_number_id"
                        class="input"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="whatsapp_number">WhatsApp Number</label>
                    <input
                        type="text"
                        id="whatsapp_number"
                        name="whatsapp_number"
                        class="input"
                        placeholder="+91XXXXXXXXXX"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="access_token">Access Token</label>
                    <textarea
                        id="access_token"
                        name="access_token"
                        class="input"
                        rows="3"
                        required
                    ></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 12px;">
                    <button
                        type="button"
                        class="btn-ghost"
                        data-close-api-modal
                    >
                        Cancel
                    </button>
                    <button type="submit" class="btn-primary">
                        Save Credentials
                    </button>
                </div>
            </form>
        </div>
    </div>

@endsection


    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const overlay = document.getElementById('api-modal');
            const form = document.getElementById('api-form');
            const titleEl = document.getElementById('api-modal-title');

            const businessAccountInput = document.getElementById('business_account_id');
            const phoneNumberInput = document.getElementById('phone_number_id');
            const whatsappNumberInput = document.getElementById('whatsapp_number');
            const accessTokenInput = document.getElementById('access_token');

            const openButtons = document.querySelectorAll('[data-open-api-modal]');
            const closeButtons = document.querySelectorAll('[data-close-api-modal]');

            function openModal(mode, data = {}) {
                if (!overlay) return;

                overlay.classList.add('is-open');

                if (mode === 'add') {
                    titleEl.textContent = 'Add API Credentials';
                    form.reset();
                    businessAccountInput.value = '';
                    phoneNumberInput.value = '';
                    whatsappNumberInput.value = '';
                    accessTokenInput.value = '';
                } else {
                    titleEl.textContent = 'Edit API Credentials';
                    businessAccountInput.value = data.businessAccountId || '';
                    phoneNumberInput.value = data.phoneNumberId || '';
                    whatsappNumberInput.value = data.whatsappNumber || '';
                    accessTokenInput.value = data.accessToken || '';
                }
            }

            function closeModal() {
                if (!overlay) return;
                overlay.classList.remove('is-open');
            }

            openButtons.forEach(btn => {
                btn.addEventListener('click', function () {
                    const mode = this.dataset.mode;
                    if (mode === 'edit') {
                        openModal('edit', {
                            businessAccountId: this.dataset.businessAccountId,
                            phoneNumberId: this.dataset.phoneNumberId,
                            whatsappNumber: this.dataset.whatsappNumber,
                            accessToken: this.dataset.accessToken,
                        });
                    } else {
                        openModal('add');
                    }
                });
            });

            closeButtons.forEach(btn => {
                btn.addEventListener('click', closeModal);
            });

            // close when clicking outside modal
            if (overlay) {
                overlay.addEventListener('click', function (e) {
                    if (e.target === overlay) {
                        closeModal();
                    }
                });
            }

            // ESC key to close
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    closeModal();
                }
            });
        });
    </script>
