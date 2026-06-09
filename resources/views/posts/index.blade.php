@extends('layouts.app')
@section('title', 'Posts — Yatri')

@section('content')
<div class="wrap" style="padding-top:36px;padding-bottom:100px">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <h2 style="margin:0">Latest Posts</h2>
    @auth
      <a class="btn btn-filled btn-sm" href="{{ route('posts.create') }}">
        <span class="material-symbols-outlined md-18">add</span> Create Post
      </a>
    @endauth
  </div>

  @if($posts->count())
    <div class="posts-feed">
      @foreach($posts as $post)
        @include('partials.post-card')
      @endforeach
    </div>
    @if($posts->hasPages())
      <div class="pager mt2" style="display:flex;gap:8px;align-items:center;justify-content:center">
        @if($posts->onFirstPage())<span class="btn btn-ghost btn-sm" style="opacity:.5"><span class="material-symbols-outlined md-18">chevron_left</span> Prev</span>@else<a class="btn btn-ghost btn-sm" href="{{ $posts->previousPageUrl() }}"><span class="material-symbols-outlined md-18">chevron_left</span> Prev</a>@endif
        <span class="muted" style="font-size:13px">Page {{ $posts->currentPage() }} / {{ $posts->lastPage() }}</span>
        @if($posts->hasMorePages())<a class="btn btn-ghost btn-sm" href="{{ $posts->nextPageUrl() }}">Next <span class="material-symbols-outlined md-18">chevron_right</span></a>@else<span class="btn btn-ghost btn-sm" style="opacity:.5">Next <span class="material-symbols-outlined md-18">chevron_right</span></span>@endif
      </div>
    @endif
  @else
    <div class="block center">
      <span class="material-symbols-outlined md-36" style="color:var(--md-on-surface-variant);display:block;margin:0 auto 12px">article</span>
      <p class="lead">No posts yet. Be the first to share your travel story!</p>
      @auth
        <a class="btn btn-filled" href="{{ route('posts.create') }}"><span class="material-symbols-outlined md-18">add</span> Create Post</a>
      @else
        <a class="btn btn-filled" href="{{ route('register') }}"><span class="material-symbols-outlined md-18">person_add</span> Sign up to post</a>
      @endauth
    </div>
  @endif
</div>
@endsection
