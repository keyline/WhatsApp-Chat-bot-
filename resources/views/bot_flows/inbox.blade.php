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
        background: #e5ddd5; /* WhatsApp-like grey */
        position: relative;
    }

    /* CHAT HEADER */
    .chat-header {
        padding: 12px 18px;
        background: #fff;
        border-bottom: 1px solid #ddd;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* CHAT MESSAGES SCROLL BOX */
    .chat-messages {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
    }

    /* BUBBLES */
    .bubble {
        max-width: 70%;
        padding: 10px 14px;
        border-radius: 15px;
        margin-bottom: 10px;
        font-size: 14px;
        line-height: 1.4;
        white-space: pre-wrap;
    }

    .bubble-in {
        background: #fff;
        border: 1px solid #ddd;
        align-self: flex-start;
    }

    .bubble-out {
        background: #25D366;
        color: white;
        align-self: flex-end;
    }

    /* CHAT INPUT BOX */
    .chat-input-bar {
        padding: 10px;
        background: #fff;
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

    <!-- LEFT: CONTACT LIST -->
    <div class="chat-sidebar">
        <div class="p-3 fw-bold">Conversations</div>
        <ul class="list-group list-group-flush" id="conversation-list">
            @foreach($conversations as $conv)
            <li class="list-group-item conversation-item"
                data-id="{{ $conv->id }}">
                <div class="fw-bold">{{ $conv->phone }}</div>
                <small class="text-muted">{{ $conv->name ?? '-' }}</small>
            </li>
            @endforeach
        </ul>
    </div>

    <!-- RIGHT: CHAT WINDOW -->
    <div class="chat-window">
        
        <!-- Chat Header -->
        <div class="chat-header" id="chat-title">
            Select a conversation
        </div>

        <!-- Messages -->
        <div class="chat-messages" id="chat-messages"></div>

        <!-- Chat Input -->
        <form id="chat-form" class="chat-input-bar">
            @csrf
            <input type="text" id="chat-input" class="form-control"
                   placeholder="Type a message..." autocomplete="off">
            <button class="btn btn-primary px-4">Send</button>
        </form>

    </div>
</div>

<script>
    let currentConversationId = null;
    const csrfToken = '{{ csrf_token() }}';

    function renderMessages(data) {
        currentConversationId = data.conversation.id;
        const name = data.conversation.name ?? "";
        document.getElementById("chat-title").innerText =
            `${name} (${data.conversation.phone})`;

        const box = document.getElementById("chat-messages");
        box.innerHTML = "";

        data.messages.forEach(msg => {
            const el = document.createElement("div");
            el.classList.add("bubble");

            if (msg.direction === "out") {
                el.classList.add("bubble-out");
            } else {
                el.classList.add("bubble-in");
            }

            el.textContent = msg.text;
            box.appendChild(el);
        });

        box.scrollTop = box.scrollHeight;
    }

    // Click: load conversation
    document.querySelectorAll(".conversation-item").forEach(item => {
        item.addEventListener("click", () => {
            document.querySelectorAll(".conversation-item")
                .forEach(i => i.classList.remove("active"));
            item.classList.add("active");

            fetch(`/bot/inbox/${item.dataset.id}`)
                .then(res => res.json())
                .then(renderMessages);
        });
    });

    // Send message
    document.getElementById("chat-form").addEventListener("submit", function(e) {
        e.preventDefault();
        if (!currentConversationId) return;

        const input = document.getElementById("chat-input");
        const text = input.value.trim();
        if (!text) return;

        fetch(`/bot/inbox/${currentConversationId}/send`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken
            },
            body: JSON.stringify({ text })
        }).then(() => {

            // Append bubble instantly
            const box = document.getElementById("chat-messages");
            const el = document.createElement("div");
            el.classList.add("bubble", "bubble-out");
            el.textContent = text;
            box.appendChild(el);
            box.scrollTop = box.scrollHeight;

            input.value = "";
        });
    });
</script>

@endsection
