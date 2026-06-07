@extends('admin.layout')
@section('title','Trips')

@section('admin')
<div class="adm-h">
  <h1>Trips</h1>
  <form method="GET" style="display:flex;gap:8px">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Search title / origin" style="width:220px">
    <select name="status" onchange="this.form.submit()">
      <option value="">All status</option>
      @foreach(['draft','generating','ready','failed'] as $s)
        <option value="{{ $s }}" {{ request('status')===$s?'selected':'' }}>{{ ucfirst($s) }}</option>
      @endforeach
    </select>
    <button class="btn btn-ghost btn-sm">Search</button>
  </form>
</div>

<table class="t">
  <tr><th>Title</th><th>Origin</th><th>Days</th><th>Budget</th><th>Status</th><th>Public</th><th>Views</th><th>Actions</th></tr>
  @forelse($trips as $t)
    <tr>
      <td>{{ \Illuminate\Support\Str::limit($t->title,30) }}</td>
      <td>{{ $t->origin }}</td>
      <td>{{ $t->days }}</td>
      <td>{{ strtoupper($t->currency) }} {{ number_format($t->budget_total) }}</td>
      <td><span class="badge {{ $t->status==='ready'?'ok':($t->status==='failed'?'warn':'mut') }}">{{ $t->status }}</span></td>
      <td>
        <form class="inline-form" method="POST" action="{{ route('admin.trips.toggle',$t->id) }}">@csrf @method('PATCH')
          <button class="badge {{ $t->is_public?'ok':'mut' }}" style="border:none;cursor:pointer">{{ $t->is_public?'Public':'Private' }}</button>
        </form>
      </td>
      <td>{{ $t->views }}</td>
      <td style="white-space:nowrap">
        <a href="{{ route('admin.trips.show',$t->id) }}">View</a> ·
        <form class="inline-form" method="POST" action="{{ route('admin.trips.destroy',$t->id) }}" onsubmit="return confirm('Delete this trip?')">@csrf @method('DELETE')
          <button style="border:none;background:none;color:var(--accent);cursor:pointer;font-size:13.5px">Delete</button>
        </form>
      </td>
    </tr>
  @empty
    <tr><td colspan="8" class="muted">No trips found.</td></tr>
  @endforelse
</table>

@if($trips->hasPages())
<div class="pager" style="display:flex;gap:8px;align-items:center">
  @if($trips->onFirstPage())<span class="btn btn-ghost btn-sm" style="opacity:.5">← Prev</span>@else<a class="btn btn-ghost btn-sm" href="{{ $trips->previousPageUrl() }}">← Prev</a>@endif
  <span class="muted" style="font-size:13px">Page {{ $trips->currentPage() }} / {{ $trips->lastPage() }}</span>
  @if($trips->hasMorePages())<a class="btn btn-ghost btn-sm" href="{{ $trips->nextPageUrl() }}">Next →</a>@else<span class="btn btn-ghost btn-sm" style="opacity:.5">Next →</span>@endif
</div>
@endif
@endsection
