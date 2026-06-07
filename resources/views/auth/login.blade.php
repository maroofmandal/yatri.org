@extends('layouts.app')
@section('title', 'Log in')

@section('content')
<div class="wrap-sm" style="padding:56px 24px">
  <div class="block">
    <h2>Welcome back</h2>
    <p class="lead">Log in to save and manage your trips.</p>
    <form method="POST" action="{{ route('login') }}">
      @csrf
      <div class="field">
        <label>Email</label>
        <input type="email" name="email" value="{{ old('email') }}" required autofocus>
        @error('email')<div class="err">{{ $message }}</div>@enderror
      </div>
      <div class="field">
        <label>Password</label>
        <input type="password" name="password" required>
      </div>
      <label style="font-weight:500;font-size:13.5px"><input type="checkbox" name="remember" style="width:auto;margin-right:6px">Remember me</label>
      <button class="btn btn-accent btn-block mt">Log in</button>
    </form>
    <p class="hint center mt">No account yet? <a href="{{ route('register') }}">Create one</a></p>
  </div>
</div>
@endsection
