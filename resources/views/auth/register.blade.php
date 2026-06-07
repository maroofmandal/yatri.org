@extends('layouts.app')
@section('title', 'Sign up')

@section('content')
<div class="wrap-sm" style="padding:56px 24px">
  <div class="block">
    <h2>Create your account</h2>
    <p class="lead">Save trips, sync across devices, and build a travel profile.</p>
    <form method="POST" action="{{ route('register') }}">
      @csrf
      <div class="field">
        <label>Name</label>
        <input type="text" name="name" value="{{ old('name') }}" required autofocus>
        @error('name')<div class="err">{{ $message }}</div>@enderror
      </div>
      <div class="field">
        <label>Email</label>
        <input type="email" name="email" value="{{ old('email') }}" required>
        @error('email')<div class="err">{{ $message }}</div>@enderror
      </div>
      <div class="row row-2">
        <div class="field">
          <label>Password</label>
          <input type="password" name="password" required>
          @error('password')<div class="err">{{ $message }}</div>@enderror
        </div>
        <div class="field">
          <label>Confirm password</label>
          <input type="password" name="password_confirmation" required>
        </div>
      </div>
      <button class="btn btn-accent btn-block mt">Create account</button>
    </form>
    <p class="hint center mt">Already have an account? <a href="{{ route('login') }}">Log in</a></p>
  </div>
</div>
@endsection
