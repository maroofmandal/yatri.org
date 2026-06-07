@extends('admin.layout')
@section('title','Destinations')

@section('admin')
<div class="adm-h">
  <h1>Destinations</h1>
  <form method="GET"><input name="q" value="{{ request('q') }}" placeholder="Search" style="width:200px"></form>
</div>

<div class="card mb">
  <h3>Add destination</h3>
  <form method="POST" action="{{ route('admin.destinations.store') }}">
    @csrf
    <div class="row row-3">
      <div class="field"><label>Name</label><input name="name" required></div>
      <div class="field"><label>Country</label><input name="country"></div>
      <div class="field"><label>Avg daily cost (USD)</label><input type="number" name="avg_daily_cost"></div>
    </div>
    <div class="row row-3">
      <div class="field"><label>Lat</label><input type="number" step="any" name="lat"></div>
      <div class="field"><label>Lng</label><input type="number" step="any" name="lng"></div>
      <div class="field"><label>Popularity</label><input type="number" name="popularity" value="50"></div>
    </div>
    <button class="btn btn-accent btn-sm">Add destination</button>
  </form>
</div>

@foreach($destinations as $d)
  <div class="card" style="margin-bottom:10px">
    <form method="POST" action="{{ route('admin.destinations.update',$d) }}" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
      @csrf @method('PUT')
      <div><label>Name</label><input name="name" value="{{ $d->name }}"></div>
      <div><label>Country</label><input name="country" value="{{ $d->country }}"></div>
      <div><label>Lat</label><input name="lat" value="{{ $d->lat }}" style="width:96px"></div>
      <div><label>Lng</label><input name="lng" value="{{ $d->lng }}" style="width:96px"></div>
      <div><label>$/day</label><input type="number" name="avg_daily_cost" value="{{ $d->avg_daily_cost }}" style="width:80px"></div>
      <div><label>Pop.</label><input type="number" name="popularity" value="{{ $d->popularity }}" style="width:72px"></div>
      <label style="font-weight:500"><input type="checkbox" name="is_active" value="1" style="width:auto;margin-right:5px" {{ $d->is_active?'checked':'' }}>Active</label>
      <button class="btn btn-ghost btn-sm">Save</button>
    </form>
    <form method="POST" action="{{ route('admin.destinations.destroy',$d) }}" onsubmit="return confirm('Delete {{ $d->name }}?')" style="margin-top:8px">
      @csrf @method('DELETE')<button style="border:none;background:none;color:var(--accent);cursor:pointer;font-size:12.5px">Delete</button>
    </form>
  </div>
@endforeach

@if($destinations->hasPages())
<div class="pager" style="display:flex;gap:8px;align-items:center">
  @if($destinations->onFirstPage())<span class="btn btn-ghost btn-sm" style="opacity:.5">← Prev</span>@else<a class="btn btn-ghost btn-sm" href="{{ $destinations->previousPageUrl() }}">← Prev</a>@endif
  <span class="muted" style="font-size:13px">Page {{ $destinations->currentPage() }} / {{ $destinations->lastPage() }}</span>
  @if($destinations->hasMorePages())<a class="btn btn-ghost btn-sm" href="{{ $destinations->nextPageUrl() }}">Next →</a>@else<span class="btn btn-ghost btn-sm" style="opacity:.5">Next →</span>@endif
</div>
@endif
@endsection
