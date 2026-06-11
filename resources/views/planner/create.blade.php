@extends('layouts.app')
@section('title', \App\Models\Setting::get('site_name','Yatri').' — plan a budget-perfect trip with AI')
@section('meta_description', 'Use Yatri AI to plan your trip — set your budget, destinations, and travel style for a complete day-by-day itinerary with costs.')

@section('content')
<header class="hero"><div class="wrap">
  <p class="eyebrow">AI trip planner · live Google Maps + Search data</p>
  <h1><strong>Tell us where</strong> &amp; your budget.
    <span class="sub">Add a start city and as many stops as you like, set a budget, and Yatri builds a costed, day-by-day plan — hotels, transport and activities that actually fit your number.</span>
  </h1>
</div></header>

<div class="wrap">
  <form method="POST" action="{{ route('plan.store') }}" class="planner-card" id="plannerForm">
    @csrf
    <input type="hidden" name="destinations" id="destinationsField">

    <div class="row row-2">
      <div class="field">
        <label>Starting from</label>
        <input type="text" name="origin" id="originInput" list="cityList" data-places placeholder="e.g. Mumbai" value="{{ old('origin') }}" autocomplete="off" required>
        @error('origin')<div class="err">{{ $message }}</div>@enderror
      </div>
      <div class="field">
        <label>Travelers</label>
        <input type="number" name="travelers" min="1" max="30" value="{{ old('travelers', 2) }}" required>
      </div>
    </div>

    <div class="field">
      <label>Destinations <span class="muted" style="font-weight:500">— add as many stops as you want, set nights each</span></label>
      <div class="dest-list" id="destList"></div>
      <button type="button" class="btn btn-ghost btn-sm mt" id="addDest">+ Add destination</button>
      <div class="suggest" id="suggest">
        @foreach($destinations as $d)
          <button type="button" data-name="{{ $d->name }}" data-lat="{{ $d->lat }}" data-lng="{{ $d->lng }}">+ {{ $d->name }}</button>
        @endforeach
      </div>
      @error('destinations')<div class="err">{{ $message }}</div>@enderror
    </div>

    <div class="row row-3">
      <div class="field">
        <label>Start date <span class="muted" style="font-weight:500">(optional)</span></label>
        <input type="date" name="start_date" value="{{ old('start_date') }}">
      </div>
      <div class="field">
        <label>End date <span class="muted" style="font-weight:500">(optional)</span></label>
        <input type="date" name="end_date" value="{{ old('end_date') }}">
      </div>
      <div class="field">
        <label>Travel style</label>
        <div class="seg">
          <label><input type="radio" name="style" value="budget" {{ old('style')==='budget'?'checked':'' }}><span>Budget</span></label>
          <label><input type="radio" name="style" value="mid" {{ old('style','mid')==='mid'?'checked':'' }}><span>Mid</span></label>
          <label><input type="radio" name="style" value="luxury" {{ old('style')==='luxury'?'checked':'' }}><span>Luxury</span></label>
        </div>
      </div>
    </div>

    <div class="field">
      <div class="budget-head">
        <label style="margin:0">Total budget <span class="muted" style="font-weight:500">— whole trip, whole party</span></label>
        <div class="budget-val"><span id="curSym">$</span><span id="budgetLabel">3,000</span></div>
      </div>
      <input type="range" id="budgetRange" min="200" max="30000" step="100" value="{{ old('budget_total', 3000) }}">
      <div class="row row-2 mt">
        <input type="number" name="budget_total" id="budgetInput" min="0" value="{{ old('budget_total', 3000) }}" required>
        <select name="currency" id="currency">
          @foreach(['USD'=>'$ USD','INR'=>'₹ INR','EUR'=>'€ EUR','GBP'=>'£ GBP','AED'=>'AED','SGD'=>'S$ SGD','JPY'=>'¥ JPY'] as $code=>$lbl)
            <option value="{{ $code }}" {{ old('currency','USD')===$code?'selected':'' }}>{{ $lbl }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <div class="field">
      <label>Interests <span class="muted" style="font-weight:500">(optional)</span></label>
      <div class="chips">
        @foreach(['Food','Culture','Nature','Nightlife','Beaches','Adventure','Shopping','History','Relaxation','Art'] as $i)
          <label class="chip-toggle"><input type="checkbox" name="interests[]" value="{{ $i }}"><span>{{ $i }}</span></label>
        @endforeach
      </div>
    </div>

    <button type="submit" class="btn btn-filled btn-block" style="font-size:16px;padding:15px"><x-icon name="auto_awesome" :size="20" /> Generate my plan</button>
    <p class="hint center mt">Free to try — no account needed. You'll get a shareable link.</p>
  </form>

  @if($recent->count())
  <div class="block">
    <h2>Recently planned</h2>
    <p class="lead">Real trips travelers built with Yatri.</p>
    <div class="grid grid-3">
      @foreach($recent as $t)
        <a class="card" href="{{ route('trip.show', $t) }}" style="color:inherit">
          <h3>{{ $t->title }}</h3>
          <p class="muted" style="font-size:13.5px;margin:6px 0">{{ $t->origin }} · {{ $t->days }} days · {{ $t->travelers }} pax</p>
          <span class="tag">{{ strtoupper($t->currency) }} {{ number_format($t->budget_total) }}</span>
        </a>
      @endforeach
    </div>
  </div>
  @endif
</div>

<datalist id="cityList">
  @foreach($destinations as $d)<option value="{{ $d->name }}">@endforeach
</datalist>

@push('scripts')
<script type="application/json" id="suggestData">{!! json_encode($destinations->map(fn($d)=>['name'=>$d->name,'lat'=>$d->lat,'lng'=>$d->lng])) !!}</script>
<script type="application/json" id="fxRatesData">{!! json_encode($fxRates) !!}</script>
<script>
const SUGGEST = JSON.parse(document.getElementById('suggestData').textContent);
const list = document.getElementById('destList');

function destRow(name='', days=3, nights=2, lat='', lng=''){
  const row = document.createElement('div');
  row.className = 'dest-item';
  row.innerHTML = `<span class="grip">⠿</span>
    <input class="dname" list="cityList" data-places placeholder="Add a city" value="${name}" autocomplete="off">
    <input type="hidden" class="dlat" value="${lat??''}"><input type="hidden" class="dlng" value="${lng??''}">
    <input type="hidden" class="ddays" value="${days}"><input type="hidden" class="dnights" value="${nights}">
    <span class="dest-counter">
      <label>Days</label>
      <div class="day-stepper">
        <button type="button" class="dd-minus">−</button>
        <span class="step-val">${days}</span>
        <button type="button" class="dd-plus">+</button>
      </div>
    </span>
    <span class="dest-counter">
      <label>Nights</label>
      <div class="day-stepper">
        <button type="button" class="dn-minus">−</button>
        <span class="step-val">${nights}</span>
        <button type="button" class="dn-plus">+</button>
      </div>
    </span>
    <button type="button" class="rm" title="Remove">×</button>`;
  row.querySelector('.rm').onclick = ()=>{ row.remove(); if(!list.children.length) addRow(); };
  // Days stepper
  const ddaysInput = row.querySelector('.ddays');
  const dnightsInput = row.querySelector('.dnights');
  const ddVal = row.querySelector('.dd-minus + .step-val');
  const dnVal = row.querySelector('.dn-minus + .step-val');
  row.querySelector('.dd-minus').onclick = ()=>{
    let d = parseInt(ddaysInput.value)||3;
    if(d > 0){
      d--;
      ddaysInput.value = d;
      ddVal.textContent = d;
      let n = parseInt(dnightsInput.value)||2;
      n = Math.min(n, d + 1);
      n = Math.max(n, Math.max(0, d - 1));
      dnightsInput.value = n;
      dnVal.textContent = n;
    }
  };
  row.querySelector('.dd-plus').onclick = ()=>{
    let d = parseInt(ddaysInput.value)||3;
    d++;
    ddaysInput.value = d;
    ddVal.textContent = d;
    let n = parseInt(dnightsInput.value)||2;
    n = Math.min(n, d + 1);
    n = Math.max(n, Math.max(0, d - 1));
    dnightsInput.value = n;
    dnVal.textContent = n;
  };
  // Nights stepper
  row.querySelector('.dn-minus').onclick = ()=>{
    let d = parseInt(ddaysInput.value)||3;
    let n = parseInt(dnightsInput.value)||2;
    if(n > Math.max(0, d - 1)){
      n--;
      dnightsInput.value = n;
      dnVal.textContent = n;
    }
  };
  row.querySelector('.dn-plus').onclick = ()=>{
    let d = parseInt(ddaysInput.value)||3;
    let n = parseInt(dnightsInput.value)||2;
    if(n < d + 1){
      n++;
      dnightsInput.value = n;
      dnVal.textContent = n;
    }
  };
  list.appendChild(row);
  // attach Places autocomplete to this row's city input
  const dname = row.querySelector('.dname');
  if(window.google && window.google.maps && window.google.maps.places){
    attachPlaces(dname, { onChange: function(p){
      if(p && p.geometry){
        row.querySelector('.dlat').value = p.geometry.location.lat();
        row.querySelector('.dlng').value = p.geometry.location.lng();
      }
    }});
  } else {
    // if Maps loads later, re-attach on first focus
    dname.addEventListener('focus', function onFocus(){
      if(window.google && window.google.maps && window.google.maps.places){
        attachPlaces(dname, { onChange: function(p){
          if(p && p.geometry){
            row.querySelector('.dlat').value = p.geometry.location.lat();
            row.querySelector('.dlng').value = p.geometry.location.lng();
          }
        }});
        dname.removeEventListener('focus', onFocus);
      }
    }, {once:false});
  }
  return row;
}
function addRow(){ return destRow(); }

document.getElementById('addDest').onclick = ()=> addRow().querySelector('.dname').focus();
document.querySelectorAll('#suggest button').forEach(b=>{
  b.onclick = ()=>{
    const empty = [...list.querySelectorAll('.dest-item')].find(r=>!r.querySelector('.dname').value.trim());
    if(empty){ empty.querySelector('.dname').value=b.dataset.name; empty.querySelector('.dlat').value=b.dataset.lat||''; empty.querySelector('.dlng').value=b.dataset.lng||''; }
    else destRow(b.dataset.name,3,2,b.dataset.lat,b.dataset.lng);
  };
});

// seed two rows
addRow(); addRow();

// budget slider <-> input <-> currency symbol + live FX conversion
const SYM = {USD:'$',INR:'₹',EUR:'€',GBP:'£',AED:'AED ',SGD:'S$',JPY:'¥'};
const FALLBACK_RATES = JSON.parse(document.getElementById('fxRatesData').textContent);
const range=document.getElementById('budgetRange'), input=document.getElementById('budgetInput'),
      label=document.getElementById('budgetLabel'), sym=document.getElementById('curSym'), cur=document.getElementById('currency');

// baseUSD is the "real" value the user is budgeting in USD terms.
let baseUSD = Number(input.value) || 3000;
let currentRate = 1; // 1 USD → currentRate units of selected currency

function round100(n){ return Math.round(n / 100) * 100; }

function syncLabel(v){ label.textContent = Number(v).toLocaleString(); }

// Apply a known exchange rate: update slider/input to converted + rounded value.
function applyRate(rate){
  currentRate = rate;
  const converted = round100(baseUSD * rate);
  // Scale slider range proportionally (keep 10 steps within the converted range).
  const sliderMax = Math.max(converted * 10, round100(baseUSD * rate * 10));
  range.max = sliderMax;
  range.step = 100;
  range.value = converted;
  input.value = converted;
  input.max = sliderMax;
  syncLabel(converted);
}

// Fetch live rate from backend, fall back to admin rates, then hardcoded defaults.
async function fetchRate(currency){
  if(currency === 'USD') return 1;
  try {
    const res = await fetch('/api/fx/' + currency);
    if(res.ok){
      const data = await res.json();
      if(data.rate) return data.rate;
    }
  } catch(_){}
  // Admin fallback
  const lc = currency.toLowerCase();
  if(FALLBACK_RATES && FALLBACK_RATES[lc]) return FALLBACK_RATES[lc];
  // Hardcoded last resort
  const hardcoded = {inr:85,eur:0.92,gbp:0.79,aed:3.67,sgd:1.34,jpy:157};
  return hardcoded[lc] || 1;
}

// Slider drag → update input (within current currency space)
range.oninput = ()=>{
  input.value = range.value;
  syncLabel(range.value);
  // Keep baseUSD in sync so switching currencies stays consistent.
  baseUSD = Math.round(Number(range.value) / currentRate);
};

// Manual input → update slider
input.oninput = ()=>{
  if(+input.value <= +range.max) range.value = input.value;
  syncLabel(input.value);
  baseUSD = Math.round(Number(input.value) / currentRate);
};

// Currency change → fetch rate and convert
cur.onchange = async ()=>{
  sym.textContent = SYM[cur.value] || cur.value + ' ';
  const rate = await fetchRate(cur.value);
  applyRate(rate);
};

// Initialise on page load
(async ()=>{
  sym.textContent = SYM[cur.value] || cur.value + ' ';
  if(cur.value !== 'USD'){
    const rate = await fetchRate(cur.value);
    applyRate(rate);
  } else {
    syncLabel(input.value);
  }
})();

// serialize destinations on submit
document.getElementById('plannerForm').addEventListener('submit', e=>{
  const dests = [...list.querySelectorAll('.dest-item')].map(r=>({
    name: r.querySelector('.dname').value.trim(),
    days: parseInt(r.querySelector('.ddays').value)||3,
    nights: parseInt(r.querySelector('.dnights').value)||2,
    lat: parseFloat(r.querySelector('.dlat').value)||null,
    lng: parseFloat(r.querySelector('.dlng').value)||null,
  })).filter(d=>d.name);
  if(!dests.length){ e.preventDefault(); alert('Add at least one destination.'); return; }
  document.getElementById('destinationsField').value = JSON.stringify(dests);
});
</script>
@endpush
@endsection
