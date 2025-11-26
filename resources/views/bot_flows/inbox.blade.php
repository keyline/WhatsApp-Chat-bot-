@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row" style="height:80vh;">
        {{-- LEFT: conversation list --}}
        <div class="col-md-3 border-end overflow-auto">
            <h5 class="mt-3 mb-3">Conversations</h5>

            <ul class="list-group" id="conversation-list">
                @foreach($conversations as $conv)
                    <li class="list-group-item conversation-item"
                        data-id="{{ $conv->id }}">
                        <div class="fw-bold">{{ $conv->phone }}</div>
                        <small class="text-muted">
                            {{ $conv->name ?? 'Unknown' }}
                        </small>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- RIGHT: chat window --}}
        <div class="col-md-9 d-flex flex-column">
            <div class="border-bottom py-2 px-3">
                <h5 id="chat-title" class="mb-0">Select a conversation</h5>
            </div>

            <div id="chat-messages"
                 class="flex-grow-1 p-3 overflow-auto"
                 style="background:#f5f5f5;">
            </div>

            <form id="chat-form" class="border-top p-3 d-flex">
                @csrf
                <input type="text"
                       id="chat-input"
                       class="form-control me-2"
                       placeholder="Type a message..."
                       autocomplete="off">
                <button class="btn btn-primary" type="submit">Send</button>
            </form>
        </div>
    </div>
</div>

<script>
    let currentConversationId = null;
    const csrfToken = '{{ csrf_token() }}';

    function renderMessages(data) {
        currentConversationId = data.conversation.id;

        const title = data.conversation.name
            ? data.conversation.name + ' (' + data.conversation.phone + ')'
            : data.conversation.phone;
        document.getElementById('chat-title').innerText = title;

        const box = document.getElementById('chat-messages');
        box.innerHTML = '';

        data.messages.forEach(msg => {
            const wrapper = document.createElement('div');
            wrapper.classList.add('mb-2');
            wrapper.style.maxWidth = '70%';

            const bubble = document.createElement('div');
            bubble.classList.add('p-2', 'rounded');

            if (msg.direction === 'out') {
                wrapper.classList.add('ms-auto','text-end');
                bubble.classList.add('bg-success','text-white','d-inline-block');
            } else {
                wrapper.classList.add('me-auto');
                bubble.classList.add('bg-white','border','d-inline-block');
            }

            bubble.innerText = msg.text;
            wrapper.appendChild(bubble);
            box.appendChild(wrapper);
        });

        box.scrollTop = box.scrollHeight;
    }

    // click on a conversation
    document.querySelectorAll('.conversation-item').forEach(item => {
        item.addEventListener('click', () => {
            const id = item.dataset.id;

            fetch('{{ url('/bot/inbox') }}/' + id)
                .then(res => res.json())
                .then(renderMessages);
        });
    });

    // send new message
    document.getElementById('chat-form').addEventListener('submit', function (e) {
        e.preventDefault();
        if (!currentConversationId) return;

        const input = document.getElementById('chat-input');
        const text  = input.value.trim();
        if (!text) return;

        fetch('{{ url('/bot/inbox') }}/' + currentConversationId + '/send', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ text }),
        })
        .then(res => res.json())
        .then(() => {
            // Immediately append message in UI
            const box = document.getElementById('chat-messages');
            const wrapper = document.createElement('div');
            wrapper.classList.add('mb-2','ms-auto','text-end');
            wrapper.style.maxWidth = '70%';

            const bubble = document.createElement('div');
            bubble.classList.add('bg-success','text-white','p-2','rounded','d-inline-block');
            bubble.innerText = text;

            wrapper.appendChild(bubble);
            box.appendChild(wrapper);
            box.scrollTop = box.scrollHeight;
            input.value = '';
        });
    });
</script>
@endsection
