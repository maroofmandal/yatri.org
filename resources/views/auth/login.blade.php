@extends('layouts.app')
@section('title', 'Log in — Yatri')

@section('content')
<div class="wrap-sm" style="padding:56px 24px">
  <div class="settings-section" style="padding:32px">
    <div style="text-align:center;margin-bottom:24px">
      <span class="material-symbols-outlined md-36" style="color:var(--md-primary);display:block;margin:0 auto 12px">login</span>
      <h2 style="margin:0">Welcome back</h2>
      <p class="lead" style="margin:6px 0 0">Log in to save and manage your trips.</p>
    </div>
    <form method="POST" action="{{ route('login') }}">
      @csrf
      <div class="field">
        <label>Email</label>
        <input type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="you@example.com">
        @error('email')<div class="err">{{ $message }}</div>@enderror
      </div>
      <div class="field">
        <label>Password</label>
        <input type="password" name="password" required placeholder="Your password">
      </div>
      <label style="font-weight:500;font-size:13px;display:flex;align-items:center;gap:6px;cursor:pointer">
        <input type="checkbox" name="remember" style="width:auto;accent-color:var(--md-primary)"> Remember me
      </label>
      <button class="btn btn-filled btn-block mt" style="margin-top:20px">
        <span class="material-symbols-outlined md-18">login</span> Log in
      </button>
    </form>
    <p class="hint center" style="margin-top:16px">No account yet? <a href="{{ route('register') }}">Create one</a></p>
  </div>
</div>
@endsection
