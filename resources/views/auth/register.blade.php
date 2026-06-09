@extends('layouts.app')
@section('title', 'Sign up — Yatri')

@section('content')
<div class="wrap-sm" style="padding:56px 24px">
  <div class="settings-section" style="padding:32px">
    <div style="text-align:center;margin-bottom:24px">
      <span class="material-symbols-outlined md-36" style="color:var(--md-primary);display:block;margin:0 auto 12px">person_add</span>
      <h2 style="margin:0">Create your account</h2>
      <p class="lead" style="margin:6px 0 0">Save trips, sync across devices, and build a travel profile.</p>
    </div>
    <form method="POST" action="{{ route('register') }}">
      @csrf
      <div class="field">
        <label>Name</label>
        <input type="text" name="name" value="{{ old('name') }}" required autofocus placeholder="Your name">
        @error('name')<div class="err">{{ $message }}</div>@enderror
      </div>
      <div class="field">
        <label>Email</label>
        <input type="email" name="email" value="{{ old('email') }}" required placeholder="you@example.com">
        @error('email')<div class="err">{{ $message }}</div>@enderror
      </div>
      <div class="settings-row">
        <div class="field">
          <label>Password</label>
          <input type="password" name="password" required placeholder="Min 8 characters">
          @error('password')<div class="err">{{ $message }}</div>@enderror
        </div>
        <div class="field">
          <label>Confirm password</label>
          <input type="password" name="password_confirmation" required placeholder="Repeat password">
        </div>
      </div>
      <button class="btn btn-filled btn-block" style="margin-top:8px">
        <span class="material-symbols-outlined md-18">person_add</span> Create account
      </button>
    </form>
    <p class="hint center" style="margin-top:16px">Already have an account? <a href="{{ route('login') }}">Log in</a></p>
  </div>
</div>
@endsection
