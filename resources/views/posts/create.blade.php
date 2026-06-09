@extends('layouts.app')
@section('title', 'Create Post — Yatri')

@section('content')
<header class="hero" style="padding:40px 0 30px"><div class="wrap">
  <p class="eyebrow">Share your journey</p>
  <h1><strong>Create a post</strong></h1>
</div></header>

<div class="wrap" style="max-width:640px;margin-top:32px">
  <form method="POST" action="{{ route('posts.store') }}" enctype="multipart/form-data" class="block">
    @csrf
    
    <div class="field">
      <label for="title">Title *</label>
      <input type="text" name="title" id="title" value="{{ old('title') }}" placeholder="Give your post a catchy title..." required minlength="6" maxlength="255">
      @error('title')<p class="err">{{ $message }}</p>@enderror
    </div>

    <div class="field">
      <label for="body">Content</label>
      <textarea name="body" id="body" rows="4" placeholder="Share your travel story, tips, or memories...">{{ old('body') }}</textarea>
      @error('body')<p class="err">{{ $message }}</p>@enderror
    </div>

    <div class="field">
      <label>Post Type</label>
      <div class="chips">
        <label class="chip-toggle">
          <input type="radio" name="type" value="text" {{ old('type') === 'text' ? 'checked' : '' }}>
          <span>📝 Text</span>
        </label>
        <label class="chip-toggle">
          <input type="radio" name="type" value="photo" {{ old('type', 'photo') === 'photo' ? 'checked' : '' }}>
          <span>📷 Photo</span>
        </label>
        <label class="chip-toggle">
          <input type="radio" name="type" value="video" {{ old('type') === 'video' ? 'checked' : '' }}>
          <span>🎥 Video</span>
        </label>
      </div>
    </div>

    <div class="field">
      <label for="media">Media (optional)</label>
      <div id="media-dropzone" class="media-dropzone" style="border:2px dashed var(--line);border-radius:var(--r);padding:32px;text-align:center;cursor:pointer;transition:all .2s">
        <p style="color:var(--muted);margin:0">Drag & drop photos or videos here</p>
        <p style="color:var(--muted);margin:8px 0 0;font-size:13px">or click to browse</p>
        <input type="file" name="media[]" id="media-input" multiple accept="image/*,video/*" style="display:none">
      </div>
      <div id="media-preview" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px"></div>
      @error('media')<p class="err">{{ $message }}</p>@enderror
    </div>

    @if($trips->count())
    <div class="field">
      <label for="trip_id">Link to a trip (optional)</label>
      <select name="trip_id" id="trip_id">
        <option value="">No trip</option>
        @foreach($trips as $trip)
          <option value="{{ $trip->id }}" {{ old('trip_id') == $trip->id ? 'selected' : '' }}>
            {{ $trip->title }} ({{ $trip->days }} days)
          </option>
        @endforeach
      </select>
    </div>
    @endif

    <div class="field">
      <label for="location">Location (optional)</label>
      <input type="text" name="location" id="location" value="{{ old('location') }}" placeholder="Where was this taken?">
      <input type="hidden" name="location_lat" id="location_lat" value="{{ old('location_lat') }}">
      <input type="hidden" name="location_lng" id="location_lng" value="{{ old('location_lng') }}">
    </div>

    <button type="submit" class="btn btn-accent btn-block">Share Post</button>
  </form>
</div>

<style>
.media-dropzone:hover { border-color: var(--accent); background: #fef2f2; }
.media-dropzone.dragover { border-color: var(--accent); background: #fef2f2; }
.media-item { position:relative;width:80px;height:80px;border-radius:var(--r-sm);overflow:hidden }
.media-item img,.media-item video { width:100%;height:100%;object-fit:cover }
.media-item .remove { position:absolute;top:4px;right:4px;width:20px;height:20px;border-radius:50%;background:rgba(0,0,0,.6);color:#fff;border:none;cursor:pointer;font-size:12px;display:flex;align-items:center;justify-content:center }
</style>

@push('scripts')
<script>
const dropzone = document.getElementById('media-dropzone');
const input = document.getElementById('media-input');
const preview = document.getElementById('media-preview');
const files = [];

dropzone.addEventListener('click', () => input.click());
dropzone.addEventListener('dragover', (e) => { e.preventDefault(); dropzone.classList.add('dragover'); });
dropzone.addEventListener('dragleave', () => dropzone.classList.remove('dragover'));
dropzone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropzone.classList.remove('dragover');
    handleFiles(e.dataTransfer.files);
});
input.addEventListener('change', (e) => handleFiles(e.target.files));

function handleFiles(fileList) {
    Array.from(fileList).forEach(file => {
        if (files.length >= 10) return;
        files.push(file);
        renderPreview(file, files.length - 1);
    });
}

function renderPreview(file, index) {
    const div = document.createElement('div');
    div.className = 'media-item';
    
    if (file.type.startsWith('video/')) {
        const video = document.createElement('video');
        video.src = URL.createObjectURL(file);
        video.muted = true;
        div.appendChild(video);
    } else {
        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        div.appendChild(img);
    }
    
    const btn = document.createElement('button');
    btn.className = 'remove';
    btn.innerHTML = '×';
    btn.onclick = () => {
        files.splice(index, 1);
        div.remove();
    };
    div.appendChild(btn);
    
    preview.appendChild(div);
}
</script>
@endpush
@endsection