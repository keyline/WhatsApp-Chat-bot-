@extends('layouts.app')

@section('title', 'Dashboard — WhatsApp Campaigner')
@section('page_title', 'Dashboard')
@section('page_subtitle', 'Overview of your campaigns, contacts and performance.')

@section('content')

    {{-- 🔥 STAT CARDS --}}
    <section class="cards-grid">

        {{-- Total Campaigns --}}
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Total Campaigns</span>
                <span class="stat-badge stat-badge-green">Live</span>
            </div>
            <div class="stat-value">{{ $total_campaigns }}</div>
            <div class="stat-footer">
                <span class="stat-trend up">▲ Active</span>
            </div>
        </div>

        {{-- Messages Sent (last 24h) --}}
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Messages Sent</span>
                <span class="stat-badge stat-badge-blue">Last 24h</span>
            </div>
            <div class="stat-value">{{ $messages_last_24h }}</div>
            <div class="stat-footer">
                <span class="stat-trend up">Last 24 hours</span>
            </div>
        </div>

        {{-- Delivery Rate --}}
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Delivery Rate</span>
                <span class="stat-badge stat-badge-purple">Quality</span>
            </div>
            <div class="stat-value">{{ $delivery_rate }}%</div>
            <div class="stat-footer">
                <span class="stat-trend neutral">● Based on delivery reports</span>
            </div>
        </div>

        {{-- Active Bots --}}
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Active Bots</span>
                <span class="stat-badge stat-badge-orange">Automation</span>
            </div>
            <div class="stat-value">{{ $active_bots }}</div>
            <div class="stat-footer">
                <span class="stat-trend up">Running</span>
            </div>
        </div>

    </section>

    {{-- 🔥 RECENT CAMPAIGNS --}}
    <section>
        <div class="content-card">
            <div class="content-card-header">
                <h2>Recent Campaigns</h2>
                <a href="{{ route('campaigns') }}" class="link-small">View all</a>
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th>Campaign</th>
                        <th>Status</th>
                        <th>Sent</th>
                        <th>Read</th>
                        <th>Replies</th>
                    </tr>
                </thead>
                <tbody>

                @forelse ($recent_campaigns as $campaign)
                    <tr>
                        <td>{{ $campaign->name }}</td>

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

                        <td>{{ $campaign->total_sent }}</td>
                        <td>{{ $campaign->read_count }}</td>
                        <td>{{ $campaign->reply_count }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align:center; padding:20px; color:#aaa;">
                            No campaigns yet. Start your first one.
                        </td>
                    </tr>
                @endforelse

                </tbody>
            </table>
        </div>

        {{-- Quick Actions (When need to open this part, also must add this class to the section tag 
        <section class="content-grid">) --}}
        {{-- <div class="content-card">
            <div class="content-card-header">
                <h2>Quick Actions</h2>
            </div>

            <div class="quick-actions">
                <button class="btn-primary w-100">Create New Campaign</button>
                <button class="btn-ghost w-100">Upload Contacts CSV</button>
                <button class="btn-ghost w-100">Configure Bot Flow</button>
                <button class="btn-ghost w-100">View Delivery Reports</button>
            </div>

            <div class="divider"></div>

            <div class="content-card-header">
                <h2>Account Status</h2>
            </div>

            <ul class="status-list">
                <li><span class="status-dot status-dot-green"></span> WhatsApp API Connected</li>
                <li><span class="status-dot status-dot-green"></span> Webhook Verified</li>
                <li><span class="status-dot status-dot-amber"></span> Daily quota: 8,000 / 10,000</li>
            </ul>
        </div> --}}
    </section>

@endsection
