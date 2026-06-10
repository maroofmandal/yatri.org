@extends('layouts.app')
@section('title', 'Building your plan…')

@section('content')
<div class="wrap-sm" style="padding:90px 24px;text-align:center">
  <div class="spinner"></div>
  <h2 style="margin-top:26px" id="title">Building your plan…</h2>
  <p class="lead" id="msg">Researching live prices, hotels and routes with Gemini + Google Maps…</p>
  <p class="hint" id="sub">This usually takes 10–30 seconds. Keep this tab open.</p>
  <a href="{{ route('home') }}" class="btn btn-ghost btn-sm mt2" id="retry" style="display:none">
    <x-icon name="arrow_back" :size="18" /> Start over
  </a>
</div>

@push('scripts')
<script>
const token = document.querySelector('meta[name=csrf-token]').content;
const url = "{{ route('trip.generate', $trip) }}";
let transient = 0;
const MAX_TRANSIENT = 6;
const MAX_WAIT_MS = 5 * 60 * 1000;
const startedAt = Date.now();

function fail(title, msg){
  document.querySelector('.spinner').style.display='none';
  document.getElementById('title').textContent = title;
  document.getElementById('msg').textContent = msg;
  document.getElementById('sub').textContent = '';
  document.getElementById('retry').style.display = 'inline-flex';
}

async function poll(){
  if (Date.now() - startedAt > MAX_WAIT_MS) {
    return fail('Still working…', 'This is taking longer than usual. Reload to keep waiting, or start over.');
  }
  try {
    const r = await fetch(url, { method:'POST', headers:{'X-CSRF-TOKEN':token,'Accept':'application/json'} });
    if (!r.ok) throw new Error('http '+r.status);
    const d = await r.json();
    transient = 0;
    if (d.status === 'ready') { window.location = d.redirect; return; }
    if (d.status === 'error') {
      return fail('Couldn\'t finish the plan', d.error || 'Something went wrong. Please try again.');
    }
    setTimeout(poll, 3000);
  } catch (e) {
    if (++transient >= MAX_TRANSIENT) {
      return fail('Network error', 'Please check your connection and retry.');
    }
    setTimeout(poll, 4000);
  }
}
poll();
</script>
@endpush
@endsection
