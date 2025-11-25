<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'WhatsApp Campaign Dashboard')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- Boostrap style --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    {{-- Link your CSS --}}
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
</head>
<body>

    @php
    $user = auth()->user();
    $initial = $user && $user->name ? strtoupper(mb_substr($user->name, 0, 1)) : 'U';
    @endphp
     
    {{-- Global Flash Messages --}}
    @if (session('success'))
        <div class="alert alert-success mb-3">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger mb-3">
            {{ session('error') }}
        </div>
    @endif

    <div class="app-container">
        {{-- Sidebar --}}
        <aside class="sidebar">
            <div class="sidebar-logo">
                <div class="logo-circle">W</div>
                <div class="logo-text">
                    <span class="logo-title">WhatsApp</span>
                    <span class="logo-subtitle">Campaigner</span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <span class="nav-icon">📊</span>
                    <span class="nav-text">Dashboard</span>
                </a>
                <a href="{{ route('campaigns') }}" class="nav-item {{ request()->routeIs('campaigns') ? 'active' : '' }}">
                    <span class="nav-icon">📤</span>
                    <span class="nav-text">Campaigns</span>
                </a>
                <a href="{{ route('contacts') }}" class="nav-item {{ request()->routeIs('contacts') ? 'active' : '' }}">
                    <span class="nav-icon">👥</span>
                    <span class="nav-text">Contacts</span>
                </a>
                <a href="{{ route('messages.index') }}" class="nav-item {{ request()->routeIs('messages.*') ? 'active' : '' }}">
                    <span class="nav-icon">💬</span>
                    <span class="nav-text">Messages</span>
                </a>
                <a href="{{ route('bot.settings.dashboard') }}" class="nav-item {{ request()->routeIs('bots_flows') ? 'active' : '' }}">
                    <span class="nav-icon">🤖</span>
                    <span class="nav-text">Bots & Flows</span>
                </a>
                <a href="{{ route('templates') }}" class="nav-item {{ request()->routeIs('templates') ? 'active' : '' }}">
                    <span class="nav-icon">📄</span>
                    <span class="nav-text">Templates</span>
                </a>
                <a href="{{ route('settings') }}" class="nav-item {{ request()->routeIs('settings') ? 'active' : '' }}">
                    <span class="nav-icon">⚙️</span>
                    <span class="nav-text">Settings</span>
                </a>

            </nav>


            <div class="sidebar-footer">
                <div class="sidebar-plan">
                    <span class="plan-label">Plan</span>
                    <span class="plan-name">Starter</span>
                    <button class="btn-upgrade">Upgrade</button>
                </div>
            </div>
        </aside>

        {{-- Main content --}}
        <div class="main-content">
            {{-- Header (reusable) --}}
            <header class="topbar">
                <div class="topbar-left">
                    <h1 class="page-title">@yield('page_title', 'Dashboard')</h1>
                    <p class="page-subtitle">@yield('page_subtitle', 'Monitor your WhatsApp campaigns at a glance.')</p>
                </div>
                <div class="topbar-right">
                    <div class="search-box">
                        <input type="text" placeholder="Search campaigns, contacts..." />
                    </div>
                    <a class="btn-topbar btn-topbar-link" href="{{ route('settings') }}">+ Add New Setting</a>
                    <div class="topbar-user">
                        <div class="user-avatar">{{ $initial }}</div>
                        <div class="user-info">
                            <span class="user-name">{{ $user?->name ?? 'User' }}</span>
                            <span class="user-role">
                                {{ $user?->role ?? 'Account Owner' }}
                            </span>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Page content --}}
            <main class="content-wrapper">
                @yield('content')
            </main>

            {{-- Footer (reusable) --}}
            <footer class="footer">
                <span>© {{ date('Y') }} WhatsApp Campaigner</span>
                <span class="footer-separator">•</span>
                <a href="#">Help</a>
                <span class="footer-separator">•</span>
                <a href="#">Terms</a>
                <span class="footer-separator">•</span>
                <a href="#">Privacy</a>
            </footer>
        </div>
    </div>

            {{-- Settings Modal --}}
        <div id="settingsModal" class="modal-overlay">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>WhatsApp API Configuration</h2>
                    <button type="button" class="modal-close"
                        onclick="document.getElementById('settingsModal').classList.remove('is-open')">
                        &times;
                    </button>
                </div>

                <form method="POST" action="#">
                    @csrf

                    <div class="modal-body">
                        <div class="auth-field">
                            <label for="business_account_id">WhatsApp Business Account ID</label>
                            <input type="text" id="business_account_id" name="business_account_id"
                                value="{{ old('business_account_id') }}" required>
                        </div>

                        <div class="auth-field">
                            <label for="phone_number_id">Phone Number ID</label>
                            <input type="text" id="phone_number_id" name="phone_number_id"
                                value="{{ old('phone_number_id') }}" required>
                        </div>

                        <div class="auth-field">
                            <label for="whatsapp_number">WhatsApp Number (display)</label>
                            <input type="text" id="whatsapp_number" name="whatsapp_number"
                                placeholder="+91 98765 43210"
                                value="{{ old('whatsapp_number') }}">
                        </div>

                        <div class="auth-field">
                            <label for="access_token">Access Token</label>
                            <input type="text" id="access_token" name="access_token"
                                value="{{ old('access_token') }}" required>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn-ghost"
                                onclick="document.getElementById('settingsModal').classList.remove('is-open')">
                            Cancel
                        </button>
                        <button type="submit" class="btn-primary">Save Settings</button>
                    </div>
                </form>
            </div>
        </div>


{{-- Settings Modal --}}
    {{-- <script>
        document.addEventListener('DOMContentLoaded', function () {
        const openBtn  = document.getElementById('openSettingsModal');
        const modal    = document.getElementById('settingsModal');
        const closeBtn = document.getElementById('closeSettingsModal');
        const cancelBtn = document.getElementById('cancelSettingsModal');

        if (!openBtn || !modal) return;

        const closeModal = () => modal.classList.remove('is-open');
        const openModal  = () => modal.classList.add('is-open');

        openBtn.addEventListener('click', openModal);
        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);

        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal(); // click on backdrop
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeModal();
        });
      });
    </script> --}}


</body>
</html>
