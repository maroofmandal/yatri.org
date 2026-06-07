@extends('layouts.app')
@section('title', 'Building your plan…')

@section('content')
<div class="wrap-sm" style="padding:90px 24px;text-align:center">
  <div class="spinner"></div>
  <h2 style="margin-top:26px" id="title">Building your plan…</h2>
  <p class="lead" id="msg">Researching live prices, hotels and routes with Gemini + Google Maps…</p>
  <p class="hint" id="sub">This usually takes 10–30 seconds. Keep this tab open.</p>
  <a href="{{ route('home') }}" class="btn btn-ghost btn-sm mt2" id="retry" style="display:none">← Start over</a>
</div>

@push('scripts')
<script>
const token = document.querySelector('meta[name=csrf-token]').content;
fetch("{{ route('trip.generate', $trip) }}", {
  method:'POST',
  headers:{'X-CSRF-TOKEN':token,'Accept':'application/json'}
})
.then(r=>r.json())
.then(d=>{
  if(d.status==='ready'){ window.location = d.redirect; return; }
  document.querySelector('.spinner').style.display='none';
  document.getElementById('title').textContent='Couldn’t finish the plan';
  document.getElementById('msg').textContent = d.error || 'Something went wrong. Please try again.';
  document.getElementById('sub').textContent='';
  document.getElementById('retry').style.display='inline-flex';
})
.catch(()=>{
  document.querySelector('.spinner').style.display='none';
  document.getElementById('title').textContent='Network error';
  document.getElementById('msg').textContent='Please check your connection and retry.';
  document.getElementById('retry').style.display='inline-flex';
});
</script>
@endpush
@endsection
