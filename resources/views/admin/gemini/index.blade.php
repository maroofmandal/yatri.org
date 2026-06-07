@extends('admin.layout')
@section('title','Gemini usage')

@section('admin')
<div class="adm-h">
  <h1>Gemini usage</h1>
  <form method="GET">
    <select name="kind" onchange="this.form.submit()">
      <option value="">All kinds</option>
      @foreach(['research','plan','chat'] as $k)
        <option value="{{ $k }}" {{ request('kind')===$k?'selected':'' }}>{{ ucfirst($k) }}</option>
      @endforeach
    </select>
  </form>
</div>

<div class="stat-grid">
  <div class="stat"><div class="n">{{ number_format($totals['calls']) }}</div><div class="l">Total calls</div></div>
  <div class="stat"><div class="n">{{ number_format($totals['tokens']) }}</div><div class="l">Total tokens</div></div>
  <div class="stat"><div class="n">{{ number_format($totals['errors']) }}</div><div class="l">Errors</div></div>
</div>

<table class="t">
  <tr><th>When</th><th>Kind</th><th>Model</th><th>Trip</th><th>Prompt</th><th>Output</th><th>ms</th><th>Grounded</th><th>Status</th></tr>
  @forelse($logs as $l)
    <tr>
      <td style="white-space:nowrap">{{ $l->created_at->format('d M H:i') }}</td>
      <td>{{ $l->kind }}</td>
      <td>{{ \Illuminate\Support\Str::limit($l->model,18) }}</td>
      <td>@if($l->trip)<a href="{{ route('admin.trips.show',$l->trip_id) }}">#{{ $l->trip_id }}</a>@else<span class="muted">—</span>@endif</td>
      <td>{{ number_format($l->prompt_tokens) }}</td>
      <td>{{ number_format($l->output_tokens) }}</td>
      <td>{{ $l->latency_ms }}</td>
      <td>{{ $l->grounded?'✓':'' }}</td>
      <td><span class="badge {{ $l->status==='ok'?'ok':'warn' }}">{{ $l->status }}</span></td>
    </tr>
  @empty
    <tr><td colspan="9" class="muted">No Gemini calls logged yet.</td></tr>
  @endforelse
</table>

@if($logs->hasPages())
<div class="pager" style="display:flex;gap:8px;align-items:center">
  @if($logs->onFirstPage())<span class="btn btn-ghost btn-sm" style="opacity:.5">← Prev</span>@else<a class="btn btn-ghost btn-sm" href="{{ $logs->previousPageUrl() }}">← Prev</a>@endif
  <span class="muted" style="font-size:13px">Page {{ $logs->currentPage() }} / {{ $logs->lastPage() }}</span>
  @if($logs->hasMorePages())<a class="btn btn-ghost btn-sm" href="{{ $logs->nextPageUrl() }}">Next →</a>@else<span class="btn btn-ghost btn-sm" style="opacity:.5">Next →</span>@endif
</div>
@endif
@endsection
