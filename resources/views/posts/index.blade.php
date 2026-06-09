@extends('layouts.app')
@section('title', 'Posts — Yatri')

@section('content')
<header class="hero" style="padding:40px 0 30px"><div class="wrap">
  <p class="eyebrow">Travel stories</p>
  <h1><strong>Latest Posts</strong></h1>
</div></header>

<div class="wrap">
  @auth
    <div style="margin:24px 0;text-align:center">
      <a class="btn btn-accent" href="{{ route('posts.create') }}">+ Create Post</a>
    </div>
  @endauth

  @if($posts->count())
    <div class="posts-feed">
      @foreach($posts as $post)
        @include('partials.post-card')
      @endforeach
    </div>

    @if($posts->hasPages())
      <div class="pager mt2" style="display:flex;gap:8px;align-items:center;justify-content:center">
        @if($posts->onFirstPage())<span class="btn btn-ghost btn-sm" style="opacity:.5">← Prev</span>@else<a class="btn btn-ghost btn-sm" href="{{ $posts->previousPageUrl() }}">← Prev</a>@endif
        <span class="muted" style="font-size:13px">Page {{ $posts->currentPage() }} / {{ $posts->lastPage() }}</span>
        @if($posts->hasMorePages())<a class="btn btn-ghost btn-sm" href="{{ $posts->nextPageUrl() }}">Next →</a>@else<span class="btn btn-ghost btn-sm" style="opacity:.5">Next →</span>@endif
      </div>
    @endif
  @else
    <div class="block center">
      <p class="lead">No posts yet. Be the first to share your travel story!</p>
      @auth
        <a class="btn btn-accent" href="{{ route('posts.create') }}">Create Post</a>
      @else
        <a class="btn btn-accent" href="{{ route('register') }}">Sign up to post</a>
      @endauth
    </div>
  @endif
</div>
@endsection