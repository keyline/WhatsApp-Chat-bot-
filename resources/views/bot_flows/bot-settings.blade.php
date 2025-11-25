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
                                    <button
                                        type="button"
                                        class="btn btn-primary btn-sm btn-message"
                                        data-bs-toggle="modal"
                                        data-bs-target="#sendMessageModal"
                                        data-phone="{{ $conv->phone }}"
                                    >
                                        Message
                                    </button>
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


{{-- Send Message Modal --}}
<div class="modal fade" id="sendMessageModal" tabindex="-1" aria-labelledby="sendMessageModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="sendMessageModalLabel">Send WhatsApp Message</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form method="POST" action="{{ route('bot.sendMessage') }}">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">To (WhatsApp Number)</label>
            <input
                type="text"
                name="phone"
                id="messagePhone"
                class="form-control"
                readonly
            >
            <small class="text-muted">
                This is the user's WhatsApp number (with + added automatically).
            </small>
          </div>

          <div class="mb-3">
            <label class="form-label">Message</label>
            <textarea
                name="message"
                class="form-control"
                rows="4"
                required
            ></textarea>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-success">Send</button>
        </div>
      </form>

    </div>
  </div>
</div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('sendMessageModal');

        if (!modal) return;

        modal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const phone  = button.getAttribute('data-phone') || '';

            const input = modal.querySelector('#messagePhone');

            let formatted = phone.trim();
            if (formatted && !formatted.startsWith('+')) {
                formatted = '+' + formatted;
            }

            input.value = formatted;
        });
    });
    </script>
    @endpush

@endsection
