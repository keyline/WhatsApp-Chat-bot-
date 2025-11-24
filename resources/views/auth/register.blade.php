@extends('layouts.auth')

@section('title', 'Sign Up — WhatsApp Campaigner')

@section('content')
    <h1 class="auth-title">Create account</h1>
    <p class="auth-subtitle">Sign up once and start sending WhatsApp campaigns.</p>

    @if ($errors->any())
        <div class="auth-error" style="margin-bottom:10px;">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ url('/register') }}">
        @csrf

        <div class="auth-field">
            <label for="name">Full Name</label>
            <input id="name" type="text"
                   name="name"
                   value="{{ old('name') }}"
                   required autofocus>
        </div>

        <div class="auth-field">
            <label for="email">Email</label>
            <input id="email" type="email"
                   name="email"
                   value="{{ old('email') }}"
                   required>
        </div>

        <div class="auth-field">
            <label for="password">Password</label>
            <input id="password" type="password"
                   name="password"
                   required>
        </div>

        <div class="auth-field">
            <label for="password_confirmation">Confirm Password</label>
            <input id="password_confirmation" type="password"
                   name="password_confirmation"
                   required>
        </div>

        <button type="submit" class="btn-primary w-100">
            Sign Up
        </button>
    </form>

    <div class="auth-footer">
        Already have an account?
        <a href="{{ route('login') }}">Login</a>
    </div>
@endsection
