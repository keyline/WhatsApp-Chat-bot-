@extends('layouts.app')

@section('title', 'Bots & Flows — WhatsApp Campaigner')
@section('page_title', 'Bots & Flows')
@section('page_subtitle', 'Create automated conversations and workflows.')

@section('content')
    <section class="content-card">
        <div class="content-card-header">
            <h2>Conversation Flows</h2>
            <button class="btn-primary">+ New Flow</button>
        </div>

        @if($bots->isEmpty())
            <p class="page-subtitle" style="margin-top: 10px;">
                You don’t have any bots yet. Click <strong>“+ New Flow”</strong> to create your first one.
            </p>
        @else
            <div class="cards-grid">
                @foreach ($bots as $bot)
                    <div class="content-card">
                        <div class="content-card-header">
                            <h2>{{ $bot->bot_name }}</h2>

                            @php
                                $statusClass = $bot->status === 'active'
                                    ? 'status-live'
                                    : 'status-paused';
                            @endphp

                            <span class="status-pill {{ $statusClass }}">
                                {{ ucfirst($bot->status) }}
                            </span>
                        </div>

                        <p class="page-subtitle">
                            Triggered by
                            @if($bot->trigger_type === 'keyword')
                                keyword: <strong>{{ $bot->trigger_keyword ?? '—' }}</strong>.
                            @elseif($bot->trigger_type === 'menu')
                                selection from <strong>main menu</strong>.
                            @else
                                a <strong>new incoming WhatsApp message</strong>.
                            @endif
                        </p>

                        <ul class="status-list">
                            <li>
                                <span class="status-dot status-dot-green"></span>
                                Entry:
                                @if($bot->trigger_type === 'keyword')
                                    Keyword “{{ $bot->trigger_keyword ?? '—' }}”
                                @elseif($bot->trigger_type === 'menu')
                                    From main menu
                                @else
                                    New WhatsApp message
                                @endif
                            </li>
                            <li>
                                <span class="status-dot status-dot-green"></span>
                                Status: {{ ucfirst($bot->status) }}
                            </li>
                            <li>
                                Last updated:
                                <strong>{{ $bot->updated_at?->diffForHumans() ?? 'N/A' }}</strong>
                            </li>
                        </ul>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
@endsection
