@extends('layouts.app')

@section('title', 'Templates — WhatsApp Campaigner')
@section('page_title', 'Templates')
@section('page_subtitle', 'Manage approved WhatsApp message templates.')

@section('content')
    <section class="content-card">
        <div class="content-card-header">
            <h2>Message Templates</h2>
            <div>
                {{-- <button class="btn-ghost">Refresh from Meta</button> --}}
                <div class="templates-header-actions">
                    <button onclick="location.reload();" class="btn btn-primary">
                      🔄 Sync from Meta
                      </button>
                </div>

                <button class="btn-primary">+ Create Template</button>
            </div>
        </div>

        {{-- <table class="table">
            <thead>
                <tr>
                    <th>Template Name</th>
                    <th>Category</th>
                    <th>Language</th>
                    <th>Status</th>
                    <th>Last Used</th>
                </tr>
            </thead>
            <tbody>
                @forelse($templates as $template)
                    @php
                        $statusClass = match($template->status) {
                            'approved' => 'status-live',
                            'pending'  => 'status-paused',
                            'rejected' => 'status-completed',
                            default    => 'status-paused',
                        };
                    @endphp
                    <tr>
                        <td>{{ $template->template_name }}</td>
                        <td>{{ ucfirst($template->category) }}</td>
                        <td>{{ $template->language }}</td>
                        <td>
                            <span class="status-pill {{ $statusClass }}">
                                {{ ucfirst($template->status) }}
                            </span>
                        </td>
                        <td>
                            @if($template->last_used_at)
                                {{ $template->last_used_at->diffForHumans() }}
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align:center; padding:20px; color:#9ca3af;">
                            No templates found. Click <strong>+ Create Template</strong> to add one or sync from Meta.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table> --}}

        <table class="table-templates">
            <thead>
                <tr>
                    <th>Template Name</th>
                    <th>Category</th>
                    <th>Language</th>
                    <th>Status</th>
                </tr>
            </thead>
                <tbody>
                    @forelse($meta_templates as $tpl)
                        <tr>
                            <td>{{ $tpl['name'] ?? '-' }}</td>
                            <td>{{ $tpl['category'] ?? '-' }}</td>
                            <td>{{ $tpl['language'] ?? '-' }}</td>
                            <td>{{ $tpl['status'] ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                No templates found. Click <strong>Refresh from Meta</strong> to sync.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

        </table>


    </section>
@endsection
