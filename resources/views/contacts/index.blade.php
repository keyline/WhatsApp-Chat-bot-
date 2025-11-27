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
                    <button
                        class="btn-ghost"
                        type="button"
                        data-bs-toggle="modal"
                        data-bs-target="#importCsvModal">
                        Import CSV
                    </button>                    
                    <button
                        class="btn-primary"
                        type="button"
                        id="openContactModal"
                        data-bs-toggle="modal"
                        data-bs-target="#contactModal"
                    >
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
    <!-- Bootstrap Contact Modal -->
    <div class="modal fade" id="contactModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" style="color:black;">Add Contact</h5>
                    <button type="button"
                            class="btn-close"
                            id="closeContactModal"
                            data-bs-dismiss="modal"
                            aria-label="Close"></button>
                </div>

                <form method="POST" action="{{ route('contacts.store') }}">
                    @csrf

                    <div class="modal-body">

                        <!-- Name -->
                        <div class="mb-3">
                            <label for="contact_name" class="form-label">Name</label>
                            <input
                                type="text"
                                class="form-control"
                                id="contact_name"
                                name="name"
                                value="{{ old('name') }}"
                                placeholder="John Doe"
                            >
                        </div>

                        <!-- Phone -->
                        <div class="mb-3">
                            <label for="contact_phone" class="form-label">Phone (WhatsApp)</label>
                            <input
                                type="text"
                                class="form-control"
                                id="contact_phone"
                                name="phone"
                                value="{{ old('phone') }}"
                                placeholder="+91 98765 43210"
                                required
                            >
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email (Optional)</label>
                            <input
                                type="email"
                                class="form-control"
                                id="email"
                                name="email"
                                placeholder="demo@gmail.com"
                            >
                        </div>

                        <!-- Tags -->
                        <div class="mb-3">
                            <label for="contact_tags" class="form-label">Tags (Optional)</label>
                            <input
                                type="text"
                                class="form-control"
                                id="contact_tags"
                                name="tags"
                                placeholder="buyer, hot lead, real-estate"
                            >
                        </div>

                        <!-- Opt-in Status -->
                        <div class="mb-3">
                            <label for="optin_status" class="form-label">Opt-In Status</label>
                            <select class="form-select" id="optin_status" name="optin_status">
                                <option value="opted_in"  {{ old('optin_status') === 'opted_in' ? 'selected' : '' }}>Opted In</option>
                                <option value="pending"   {{ old('optin_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="opted_out" {{ old('optin_status') === 'opted_out' ? 'selected' : '' }}>Opted Out</option>
                            </select>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button"
                                class="btn btn-secondary"
                                id="cancelContactModal"
                                data-bs-dismiss="modal">
                            Cancel
                        </button>

                        <button type="submit" class="btn btn-success">
                            Save Contact
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    {{-- Import CSV Modal --}}
    <div class="modal fade" id="importCsvModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" style="color:black;">Import Contacts from CSV</h5>
                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"></button>
                </div>

                <form method="POST"
                    action="{{ route('contacts.import.excel') }}"
                    enctype="multipart/form-data">
                    @csrf

                    <div class="modal-body">

                        <p class="mb-2" style="font-size: 14px;">
                            Upload a CSV file with columns:
                            <strong>name, phone, email, tags, optin_status</strong>.<br>
                            Example:
                            <code>John,+919876543210,john@example.com,buyer,opted_in</code>
                        </p>

                        <div class="mb-3">
                            <label for="csv_file" class="form-label">Upload CSV File</label>

                            <div class="d-flex align-items-center gap-2">
                            <input type="file" name="excel_file" accept=".xlsx,.xls,.csv" class="form-control">

                                <a href="{{ route('contacts.sample.xls') }}"
                                class="btn btn-outline-secondary btn-sm">
                                    Download Sample xls
                                </a>
                            </div>

                            <small class="text-muted">
                                Format: <code>name, phone, email, tags, optin_status</code>  
                                Example: <code>John,+919876543210,john@example.com,buyer,opted_in</code>
                            </small>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button"
                                class="btn btn-secondary"
                                data-bs-dismiss="modal">
                            Cancel
                        </button>

                        <button type="submit" class="btn btn-success">
                            Import Contacts
                        </button>
                    </div>
                </form>

            </div>
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
