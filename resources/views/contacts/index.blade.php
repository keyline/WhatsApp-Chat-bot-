@extends('layouts.app')

@section('title', 'Contacts — WhatsApp Campaigner')
@section('page_title', 'Contacts')
@section('page_subtitle', 'Organise your audience and manage opt-ins.')

@section('content')
    <section class="content-grid">
        <div class="content-card">
            <div class="content-card-header">
                <h2>Contacts List</h2>
                <div>
                    <button class="btn-ghost">Import CSV</button>                    
                    <button class="btn-primary" type="button" id="openContactModal">
                        + Add Contact
                    </button>
                </div>
            </div>

            <div class="search-box" style="margin-bottom: 10px;">
                <input type="text" placeholder="Search by name, phone or tag..." />
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Tags</th>
                        <th>Opt-In Status</th>
                        <th>Last Seen</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($contacts as $contact)
                        @php
                            $tags = $contact->tags ?? [];
                            $tagsLabel = is_array($tags) ? implode(', ', $tags) : $tags;

                            $statusClass = match($contact->optin_status) {
                                'opted_in' => 'status-live',
                                'pending'  => 'status-paused',
                                'opted_out'=> 'status-completed',
                                default    => 'status-paused',
                            };

                            $statusText = match($contact->optin_status) {
                                'opted_in' => 'Opted In',
                                'pending'  => 'Pending',
                                'opted_out'=> 'Opted Out',
                                default    => ucfirst($contact->optin_status),
                            };
                        @endphp
                        <tr>
                            <td>{{ $contact->name ?? '—' }}</td>
                            <td>{{ $contact->phone }}</td>
                            <td>{{ $tagsLabel ?: '—' }}</td>
                            <td><span >{{ $statusText }}</span></td>
                            <td>
                                @if($contact->last_seen_at)
                                    {{ $contact->last_seen_at->diffForHumans() }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align:center; padding:20px; color:#9ca3af;">
                                No contacts yet. Import CSV or click <strong>+ Add Contact</strong>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="content-card">
            <div class="content-card-header">
                <h2>Segments</h2>
                <button class="btn-ghost">+ New Segment</button>
            </div>
            <ul class="status-list">
                <li>
                    <span class="status-dot status-dot-green"></span>
                    All Contacts ({{ $stats['total'] }})
                </li>
                <li>
                    <span class="status-dot status-dot-green"></span>
                    Opted-In ({{ $stats['opted_in'] }})
                </li>
                <li>
                    <span class="status-dot status-dot-amber"></span>
                    Pending Opt-In ({{ $stats['pending'] }})
                </li>
                <li>
                    <span class="status-dot status-dot-green"></span>
                    Opted Out ({{ $stats['opted_out'] }})
                </li>
                <li>
                    <span class="status-dot status-dot-green"></span>
                    High Value ({{ $stats['high_value'] }})
                </li>
                <li>
                    <span class="status-dot status-dot-green"></span>
                    Real Estate Leads ({{ $stats['real_estate'] }})
                </li>
            </ul>
        </div>
    </section>

    {{-- Add Contact Modal --}}
    <div id="contactModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add Contact</h2>
                <button type="button" class="modal-close" id="closeContactModal">
                    &times;
                </button>
            </div>

            <form method="POST" action="{{ route('contacts.store')}}">
                @csrf

                <div class="modal-body">
                    <div class="auth-field">
                        <label for="contact_name">Name</label>
                        <input
                            type="text"
                            id="contact_name"
                            name="name"
                            value="{{ old('name') }}"
                            placeholder="John Doe">
                    </div>

                    <div class="auth-field">
                        <label for="contact_phone">Phone (WhatsApp)</label>
                        <input
                            type="text"
                            id="contact_phone"
                            name="phone"
                            value="{{ old('phone') }}"
                            placeholder="+91 98765 43210"
                            required>
                    </div>
                    <div class="auth-field">
                        <label for="contact_phone">Email (Optional)</label>
                        <input
                            type="text"
                            id="email"
                            name="email"
                            placeholder="demo@gmail.com">
                    </div>

                    <div class="auth-field">
                        <label for="contact_tags">Tags (Optional)</label>
                        <input
                            type="text"
                            id="contact_tags"
                            name="tags"
                            placeholder="buyer, hot lead, real-estate">
                    </div>

                    <div class="auth-field">
                        <label for="optin_status">Opt-In Status</label>
                        <select id="optin_status" name="optin_status">
                            <option value="opted_in"  {{ old('optin_status') === 'opted_in' ? 'selected' : '' }}>Opted In</option>
                            <option value="pending"   {{ old('optin_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="opted_out" {{ old('optin_status') === 'opted_out' ? 'selected' : '' }}>Opted Out</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-ghost" id="cancelContactModal">
                        Cancel
                    </button>
                    <button type="submit" class="btn-primary">
                        Save Contact
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal script --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const openBtn   = document.getElementById('openContactModal');
            const modal     = document.getElementById('contactModal');
            const closeBtn  = document.getElementById('closeContactModal');
            const cancelBtn = document.getElementById('cancelContactModal');

            if (!openBtn || !modal) return;

            const openModal = () => modal.classList.add('is-open');
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
        });
    </script>
@endsection
