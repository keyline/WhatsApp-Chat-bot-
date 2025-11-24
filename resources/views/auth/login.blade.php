@extends('layouts.auth')

@section('title', 'Login — WhatsApp Campaigner')

@section('content')
    <h1 class="auth-title">Login</h1>
    <p class="auth-subtitle">Sign in to manage your WhatsApp campaigns and bots.</p>

    @if ($errors->any())
        <div class="auth-error" style="margin-bottom:10px;">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ url('/login') }}">
        @csrf

        <div class="auth-field">
            <label for="email">Email</label>
            <input id="email" type="email"
                   name="email"
                   value="{{ old('email') }}"
                   required autofocus>
        </div>

        <div class="auth-field">
            <label for="password">Password</label>
            <input id="password" type="password"
                   name="password"
                   required>
        </div>

        <div class="auth-field" style="display:flex;align-items:center;gap:6px;">
            <input type="checkbox" id="remember" name="remember" style="width:14px;height:14px;">
            <label for="remember" style="margin:0;font-size:12px;">Remember me</label>
        </div>

        <button type="submit" class="btn-primary w-100">
            Login
        </button>
    </form>

    <div class="auth-footer">
        Don’t have an account?
        <a href="{{ route('register') }}">Create one</a>
    </div>
@endsection
