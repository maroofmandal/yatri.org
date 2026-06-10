@extends('layouts.app')
@section('title', 'Contact — Yatri')
@section('meta_description', 'Get in touch with the Yatri team — questions, feedback, or support.')

@section('content')
<div class="wrap" style="padding-top:48px;padding-bottom:100px;max-width:560px">
  <p class="eyebrow">Contact</p>
  <h1 style="margin:0 0 8px"><strong>Get in touch</strong></h1>
  <p style="color:var(--md-on-surface-variant);margin:0 0 32px">Questions, feedback, or just want to say hi — we'd love to hear from you.</p>

  <form method="POST" action="{{ route('contact.store') }}" style="display:flex;flex-direction:column;gap:16px">
    @csrf

    <div class="field">
      <label for="name">Name</label>
      <input id="name" name="name" type="text" class="input" value="{{ old('name', auth()?->user()?->name) }}" required>
      @error('name')<span class="field-msg">{{ $message }}</span>@enderror
    </div>

    <div class="field">
      <label for="email">Email</label>
      <input id="email" name="email" type="email" class="input" value="{{ old('email', auth()?->user()?->email) }}" required>
      @error('email')<span class="field-msg">{{ $message }}</span>@enderror
    </div>

    <div class="field">
      <label for="subject">Subject</label>
      <input id="subject" name="subject" type="text" class="input" value="{{ old('subject') }}" required>
      @error('subject')<span class="field-msg">{{ $message }}</span>@enderror
    </div>

    <div class="field">
      <label for="message">Message</label>
      <textarea id="message" name="message" class="input" rows="5" required>{{ old('message') }}</textarea>
      @error('message')<span class="field-msg">{{ $message }}</span>@enderror
    </div>

    <button type="submit" class="btn btn-filled" style="align-self:flex-start">
      <x-icon name="send" :size="18" /> Send message
    </button>
  </form>
</div>
@endsection
