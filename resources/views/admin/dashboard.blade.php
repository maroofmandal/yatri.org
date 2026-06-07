@extends('admin.layout')
@section('title','Dashboard')

@section('admin')
<div class="adm-h"><h1>Dashboard</h1></div>

@if(!\App\Models\Setting::get('gemini_api_key') && !config('gemini.key'))
  <div class="flash flash-err">No Gemini API key set — planner runs in <strong>sample mode</strong>. Add one in <a href="{{ route('admin.settings.edit') }}">Settings → AI</a> to enable live, grounded plans.</div>
@endif

<div class="stat-grid">
  <div class="stat"><div class="n">{{ $stats['users'] }}</div><div class="l">Users</div></div>
  <div class="stat"><div class="n">{{ $stats['trips'] }}</div><div class="l">Trips</div></div>
  <div class="stat"><div class="n">{{ $stats['trips_ready'] }}</div><div class="l">Ready</div></div>
  <div class="stat"><div class="n">{{ $stats['trips_failed'] }}</div><div class="l">Failed</div></div>
  <div class="stat"><div class="n">{{ $stats['gemini_calls'] }}</div><div class="l">Gemini calls</div></div>
  <div class="stat"><div class="n">{{ number_format($stats['tokens']) }}</div><div class="l">Tokens used</div></div>
  <div class="stat"><div class="n">{{ $stats['destinations'] }}</div><div class="l">Destinations</div></div>
</div>

<div class="grid grid-2">
  <div>
    <h3 style="margin-bottom:10px">Recent trips</h3>
    <table class="t">
      <tr><th>Title</th><th>Origin</th><th>Status</th><th></th></tr>
      @forelse($recentTrips as $t)
        <tr>
          <td>{{ \Illuminate\Support\Str::limit($t->title,28) }}</td>
          <td>{{ $t->origin }}</td>
          <td><span class="badge {{ $t->status==='ready'?'ok':($t->status==='failed'?'warn':'mut') }}">{{ $t->status }}</span></td>
          <td><a href="{{ route('admin.trips.show',$t->id) }}">View</a></td>
        </tr>
      @empty
        <tr><td colspan="4" class="muted">No trips yet.</td></tr>
      @endforelse
    </table>
  </div>
  <div>
    <h3 style="margin-bottom:10px">Recent Gemini calls</h3>
    <table class="t">
      <tr><th>Kind</th><th>Model</th><th>Tokens</th><th>ms</th></tr>
      @forelse($recentLogs as $l)
        <tr>
          <td><span class="badge {{ $l->grounded?'blue':'mut' }}">{{ $l->kind }}</span></td>
          <td>{{ \Illuminate\Support\Str::limit($l->model,18) }}</td>
          <td>{{ number_format($l->prompt_tokens + $l->output_tokens) }}</td>
          <td>{{ $l->latency_ms }}</td>
        </tr>
      @empty
        <tr><td colspan="4" class="muted">No calls yet.</td></tr>
      @endforelse
    </table>
  </div>
</div>
@endsection
