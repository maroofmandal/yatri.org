@extends('layouts.app')
@section('title', 'Create Post — Yatri')

@section('content')
<div class="wrap" style="max-width:640px;padding-top:36px;padding-bottom:100px">
  <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px">
    <a href="{{ route('home') }}" class="icon-btn"><span class="material-symbols-outlined">arrow_back</span></a>
    <h2 style="margin:0">Create a post</h2>
  </div>

  <form method="POST" action="{{ route('posts.store') }}" enctype="multipart/form-data" class="settings-section" style="padding:28px">
    @csrf
    <div class="field">
      <label>Title *</label>
      <input type="text" name="title" value="{{ old('title') }}" placeholder="Give your post a catchy title..." required minlength="6" maxlength="255">
      @error('title')<div class="err">{{ $message }}</div>@enderror
    </div>
    <div class="field">
      <label>Content</label>
      <textarea name="body" rows="4" placeholder="Share your travel story, tips, or memories...">{{ old('body') }}</textarea>
      @error('body')<div class="err">{{ $message }}</div>@enderror
    </div>
    <div class="field">
      <label>Post Type</label>
      <div class="chips">
        <label class="chip-toggle"><input type="radio" name="type" value="text" {{ old('type') === 'text' ? 'checked' : '' }}><span><span class="material-symbols-outlined md-18" style="vertical-align:middle">article</span> Text</span></label>
        <label class="chip-toggle"><input type="radio" name="type" value="photo" {{ old('type', 'photo') === 'photo' ? 'checked' : '' }}><span><span class="material-symbols-outlined md-18" style="vertical-align:middle">photo_camera</span> Photo</span></label>
        <label class="chip-toggle"><input type="radio" name="type" value="video" {{ old('type') === 'video' ? 'checked' : '' }}><span><span class="material-symbols-outlined md-18" style="vertical-align:middle">videocam</span> Video</span></label>
      </div>
    </div>
    <div class="field">
      <label>Media (optional)</label>
      <div id="media-dropzone" style="border:2px dashed var(--md-outline-variant);border-radius:var(--md-shape-md);padding:32px;text-align:center;cursor:pointer;transition:all .15s" onmouseover="this.style.borderColor='var(--md-primary)';this.style.background='var(--md-primary-container)'" onmouseout="this.style.borderColor='var(--md-outline-variant)';this.style.background='transparent'">
        <span class="material-symbols-outlined md-32" style="color:var(--md-on-surface-variant);display:block;margin:0 auto 8px">cloud_upload</span>
        <p style="color:var(--md-on-surface-variant);margin:0">Drag & drop photos or videos here</p>
        <p style="color:var(--md-on-surface-variant);margin:4px 0 0;font-size:13px">or click to browse</p>
        <input type="file" name="media[]" id="media-input" multiple accept="image/*,video/*" style="display:none">
      </div>
      <div id="media-preview" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px"></div>
      @error('media')<div class="err">{{ $message }}</div>@enderror
    </div>
    @if($trips->count())
    <div class="field">
      <label>Link to a trip (optional)</label>
      <select name="trip_id">
        <option value="">No trip</option>
        @foreach($trips as $trip)
          <option value="{{ $trip->id }}" {{ old('trip_id') == $trip->id ? 'selected' : '' }}>{{ $trip->title }} ({{ $trip->days }} days)</option>
        @endforeach
      </select>
    </div>
    @endif
    <div class="field">
      <label>Location (optional)</label>
      <input type="text" name="location" value="{{ old('location') }}" placeholder="Where was this taken?" data-places>
      <input type="hidden" name="location_lat" value="{{ old('location_lat') }}">
      <input type="hidden" name="location_lng" value="{{ old('location_lng') }}">
    </div>
    <button type="submit" class="btn btn-filled btn-block">
      <span class="material-symbols-outlined md-18">send</span> Share Post
    </button>
  </form>
</div>

<style>
.media-item{position:relative;width:80px;height:80px;border-radius:var(--md-shape-sm);overflow:hidden}
.media-item img,.media-item video{width:100%;height:100%;object-fit:cover}
.media-item .remove{position:absolute;top:4px;right:4px;width:24px;height:24px;border-radius:50%;background:rgba(0,0,0,.6);color:#fff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center}
</style>

@push('scripts')
<script>
const dropzone = document.getElementById('media-dropzone');
const input = document.getElementById('media-input');
const preview = document.getElementById('media-preview');
const files = [];
dropzone.addEventListener('click', () => input.click());
dropzone.addEventListener('dragover', (e) => { e.preventDefault(); });
dropzone.addEventListener('drop', (e) => { e.preventDefault(); handleFiles(e.dataTransfer.files); });
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
    video.src = URL.createObjectURL(file); video.muted = true;
    div.appendChild(video);
  } else {
    const img = document.createElement('img');
    img.src = URL.createObjectURL(file);
    div.appendChild(img);
  }
  const btn = document.createElement('button');
  btn.className = 'remove';
  btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:16px">close</span>';
  btn.onclick = () => { files.splice(index, 1); div.remove(); };
  div.appendChild(btn);
  preview.appendChild(div);
}
</script>
@endpush
@endsection
