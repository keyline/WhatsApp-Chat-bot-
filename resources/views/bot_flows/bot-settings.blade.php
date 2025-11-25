{{-- resources/views/bot-settings.blade.php --}}
@extends('layouts.app') {{-- or whatever layout you use --}}

@section('content')
<div class="container py-4">

    <h1 class="mb-4">WhatsApp Bot Settings & Conversations</h1>

    {{-- Webhook info --}}
    <div class="card mb-4">
        {{-- <div class="card-header">
            Webhook Configuration
        </div>
        <div class="card-body">
            <p><strong>Webhook URL:</strong></p>
            <code>{{ $webhookUrl }}</code>

            <p class="mt-3"><strong>Verify Token:</strong></p>
            <code>{{ $settings->verify_token }}</code>
        </div>
    </div> --}}

    {{-- Conversations table --}}
    <div class="card">
        <div class="card-header">
            Conversations
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Sl. No.</th>
                            <th>Phone</th>
                            <th>Step</th>
                            <th>Service</th>
                            <th>Option 1</th>
                            <th>Option 2</th>
                            <th>Name</th>
                            <th>Business Name</th>
                            <th>Contact Number</th>
                            <th>Email</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $i = 1; @endphp
                        @forelse($conversations as $conv)
                            <tr>
                                <td><?php echo $i; $i++; ?></td>
                                <td>{{ $conv->phone }}</td>
                                <td>{{ $conv->step }}</td>
                                <td>{{ $conv->service }}</td>
                                <td>{{ $conv->option1 }}</td>
                                <td>{{ $conv->option2 }}</td>
                                <td>{{ $conv->name }}</td>
                                <td>{{ $conv->business_name }}</td>
                                <td>{{ $conv->contact_number }}</td>
                                <td>{{ $conv->email }}</td>
                                <td>{{ $conv->created_at }}</td>
                                <td>
                                    <a href="#" class="btn btn-primary">Message</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center p-3">
                                    No conversations yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($conversations->hasPages())
            <div class="card-footer">
                {{ $conversations->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
