@extends('layouts.app')

@section('content')
<style>
    /* MAIN LAYOUT */
    .chat-container {
        height: calc(100vh - 70px);
        display: flex;
        border-radius: 8px;
        overflow: hidden;
        background: #fff;
    }

    /* LEFT PANEL — conversations list */
    .chat-sidebar {
        width: 300px;
        border-right: 1px solid #ddd;
        background: #f8f9fa;
        overflow-y: auto;
    }

    .chat-sidebar .list-group-item {
        cursor: pointer;
        border-radius: 0 !important;
    }

    .chat-sidebar .list-group-item.active {
        background: #0d6efd !important;
        color: #fff;
        border-color: #0d6efd;
    }

    /* RIGHT PANEL — chat messages */
    .chat-window {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: #e5ddd5; /* WhatsApp-like */
        position: relative;
    }

    /* CHAT HEADER */
    .chat-header {
        padding: 10px 16px;
        background: #ffffff;
        border-bottom: 1px solid #ddd;
        color: #000; /* important: override dark theme */
    }

    /* CHAT MESSAGES SCROLL BOX */
    .chat-messages {
        flex: 1;
        padding: 16px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    /* BUBBLES */
    .bubble {
        max-width: 70%;
        padding: 8px 12px;
        border-radius: 15px;
        font-size: 14px;
        line-height: 1.4;
        white-space: pre-wrap;
        word-wrap: break-word;
    }

    /* incoming (user) – LEFT, white with dark text */
    .bubble-in {
        background: #ffffff;
        border: 1px solid #ddd;
        color: #000;                 /* <-- fix 1: text visible */
        align-self: flex-start;      /* left */
    }

    /* outgoing (bot/you) – RIGHT, green with white text */
    .bubble-out {
        background: #25D366;
        color: #ffffff;
        align-self: flex-end;        /* right */
    }

    /* CHAT INPUT BOX */
    .chat-input-bar {
        padding: 10px;
        background: #ffffff;
        border-top: 1px solid #ddd;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .chat-input-bar input {
        flex: 1;
    }
</style>

<div class="chat-container shadow">

    {{-- LEFT: CONTACT LIST --}}
    <div class="chat-sidebar">
        <div class="p-3 fw-bold">Conversations</div>
        <ul class="list-group list-group-flush" id="conversation-list">
            @foreach($conversations as $conv)
                <li class="list-group-item conversation-item"
                    data-id="{{ $conv->id }}"
                    data-history-url="{{ route('bot.inbox.history', $conv) }}"
                    data-send-url="{{ route('bot.inbox.send', $conv) }}">
                    <div class="fw-bold">{{ $conv->phone }}</div>
                    <small class="text-muted">{{ $conv->name ?? '-' }}</small>
                </li>
            @endforeach
        </ul>
    </div>

    {{-- RIGHT: CHAT WINDOW --}}
    <div class="chat-window">

        {{-- HEADER: phone + small name --}}
        <div class="chat-header">
            <div id="chat-header-phone" class="fw-semibold">Select a conversation</div>
            <small id="chat-header-name" class="text-muted"></small>
        </div>

        {{-- MESSAGES --}}
        <div id="chat-messages" class="chat-messages"></div>

        {{-- INPUT --}}
        <form id="chat-form" class="chat-input-bar">
            @csrf
            <input type="text" id="chat-input" class="form-control"
                   placeholder="Type a message..." autocomplete="off">
            <button class="btn btn-success px-4" type="submit">Send</button>
        </form>

    </div>
</div>

<script>
    let currentSendUrl = null;
    const csrfToken = '{{ csrf_token() }}';

    function renderMessages(data) {
        // 2. header: phone + name
        document.getElementById('chat-header-phone').textContent =
            data.conversation.phone || '';

        document.getElementById('chat-header-name').textContent =
            data.conversation.name || '';

        const box = document.getElementById('chat-messages');
        box.innerHTML = '';

        // 1 & 3. show both sides properly
        data.messages.forEach(msg => {
            const el = document.createElement('div');
            el.classList.add('bubble');

            if (msg.direction === 'out') {
                // our/bot message – RIGHT, green
                el.classList.add('bubble-out');
            } else {
                // user reply – LEFT, white
                el.classList.add('bubble-in');
            }

            el.textContent = msg.text;
            box.appendChild(el);
        });

        box.scrollTop = box.scrollHeight;
    }

    // click on a conversation
    document.querySelectorAll('.conversation-item').forEach(item => {
        item.addEventListener('click', () => {
            document.querySelectorAll('.conversation-item')
                .forEach(i => i.classList.remove('active'));
            item.classList.add('active');

            const historyUrl = item.dataset.historyUrl;
            currentSendUrl   = item.dataset.sendUrl;

            fetch(historyUrl)
                .then(res => res.json())
                .then(renderMessages)
                .catch(err => console.error('History fetch error', err));
        });
    });

    // send message
    document.getElementById('chat-form').addEventListener('submit', function (e) {
        e.preventDefault();
        if (!currentSendUrl) return;

        const input = document.getElementById('chat-input');
        const text  = input.value.trim();
        if (!text) return;

        fetch(currentSendUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ text }),
        })
        .then(res => res.json())
        .then(() => {
            // append our new message on RIGHT
            const box = document.getElementById('chat-messages');
            const el  = document.createElement('div');
            el.classList.add('bubble', 'bubble-out');
            el.textContent = text;
            box.appendChild(el);
            box.scrollTop = box.scrollHeight;
            input.value = '';
        })
        .catch(err => console.error('Send error', err));
    });
</script>
@endsection
