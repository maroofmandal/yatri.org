@extends('layouts.app')
@section('title', 'Posts — Yatri')
@section('meta_description', 'Read travel posts from the Yatri community — real trip reports, destination guides, and budget breakdowns from travelers worldwide.')

@section('content')
<div class="wrap" style="padding-top:36px;padding-bottom:100px">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <h2 style="margin:0">Latest Posts</h2>
    @auth
      <a class="btn btn-filled btn-sm" href="{{ route('posts.create') }}">
        <x-icon name="add" :size="18" /> Create Post
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
        @if($posts->onFirstPage())<span class="btn btn-ghost btn-sm" style="opacity:.5"><x-icon name="chevron_left" :size="18" /> Prev</span>@else<a class="btn btn-ghost btn-sm" href="{{ $posts->previousPageUrl() }}"><x-icon name="chevron_left" :size="18" /> Prev</a>@endif
        <span class="muted" style="font-size:13px">Page {{ $posts->currentPage() }} / {{ $posts->lastPage() }}</span>
        @if($posts->hasMorePages())<a class="btn btn-ghost btn-sm" href="{{ $posts->nextPageUrl() }}">Next <x-icon name="chevron_right" :size="18" /></a>@else<span class="btn btn-ghost btn-sm" style="opacity:.5">Next <x-icon name="chevron_right" :size="18" /></span>@endif
      </div>
    @endif
  @else
    <div class="block center">
      <x-icon name="article" :size="36" style="color:var(--md-on-surface-variant);display:block;margin:0 auto 12px" />
      <p class="lead">No posts yet. Be the first to share your travel story!</p>
      @auth
        <a class="btn btn-filled" href="{{ route('posts.create') }}"><x-icon name="add" :size="18" /> Create Post</a>
      @else
        <a class="btn btn-filled" href="{{ route('register') }}"><x-icon name="person_add" :size="18" /> Sign up to post</a>
      @endauth
    </div>
  @endif
</div>
@endsection
