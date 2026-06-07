@extends('admin.layout')
@section('title','Trip · '.$trip->title)

@section('admin')
<div class="adm-h">
  <h1>{{ $trip->title }}</h1>
  <div style="display:flex;gap:8px">
    <a class="btn btn-ghost btn-sm" href="{{ route('trip.show',$trip) }}" target="_blank">↗ Public page</a>
    <a class="btn btn-ghost btn-sm" href="{{ route('admin.trips.index') }}">← Back</a>
  </div>
</div>

<div class="grid grid-2">
  <div class="card">
    <h3>Inputs</h3>
    <table class="t" style="border:none">
      <tr><td class="muted">Origin</td><td>{{ $trip->origin }}</td></tr>
      <tr><td class="muted">Destinations</td><td>{{ collect($trip->destinations)->pluck('name')->implode(', ') }}</td></tr>
      <tr><td class="muted">Days / Travelers</td><td>{{ $trip->days }} days · {{ $trip->travelers }} pax</td></tr>
      <tr><td class="muted">Budget</td><td>{{ strtoupper($trip->currency) }} {{ number_format($trip->budget_total) }} · {{ ucfirst($trip->style) }}</td></tr>
      <tr><td class="muted">Status / Fit</td><td><span class="badge {{ $trip->status==='ready'?'ok':'mut' }}">{{ $trip->status }}</span> · {{ $trip->fit_status }}</td></tr>
      <tr><td class="muted">Model</td><td>{{ $trip->model_used ?? '—' }}</td></tr>
      <tr><td class="muted">Created</td><td>{{ $trip->created_at->diffForHumans() }}</td></tr>
    </table>
    @if($trip->error)<div class="flash flash-err" style="margin-top:12px">{{ $trip->error }}</div>@endif
  </div>
  <div class="card">
    <h3>Budget breakdown</h3>
    @php $b=$trip->budget_breakdown ?? []; @endphp
    <table class="t" style="border:none">
      @forelse($b as $k=>$v)
        @if($k!=='currency')<tr><td class="muted">{{ ucfirst(str_replace('_',' ',$k)) }}</td><td>{{ is_numeric($v)?number_format($v):$v }}</td></tr>@endif
      @empty
        <tr><td class="muted">No breakdown.</td></tr>
      @endforelse
    </table>
  </div>
</div>

<div class="card mt">
  <h3>Plan JSON</h3>
  <pre style="background:#1c1917;color:#d6d3d1;padding:16px;border-radius:12px;overflow:auto;max-height:420px;font-size:12px;line-height:1.5">{{ json_encode($trip->plan, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) }}</pre>
</div>
@endsection
