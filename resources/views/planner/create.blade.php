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
  <form method="POST" action="{{ isset($editTrip) ? route('trip.update', $editTrip) : route('plan.store') }}" class="planner-card" id="plannerForm">
    @csrf
    <input type="hidden" name="destinations" id="destinationsField">

    <div class="row row-2">
      <div class="field">
        <label>Starting from</label>
        <input type="text" name="origin" id="originInput" list="cityList" data-places placeholder="e.g. Mumbai" value="{{ isset($editTrip) ? $editTrip->origin : old('origin') }}" autocomplete="off" required>
        @error('origin')<div class="err">{{ $message }}</div>@enderror
      </div>
      <div class="field">
        <label>Travelers</label>
        <input type="number" name="travelers" min="1" max="30" value="{{ isset($editTrip) ? $editTrip->travelers : old('travelers', 2) }}" required>
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
        <input type="date" name="start_date" value="{{ isset($editTrip) && $editTrip->start_date ? $editTrip->start_date->format('Y-m-d') : old('start_date') }}">
      </div>
      <div class="field">
        <label>End date <span class="muted" style="font-weight:500">(optional)</span></label>
        <input type="date" name="end_date" value="{{ isset($editTrip) && $editTrip->end_date ? $editTrip->end_date->format('Y-m-d') : old('end_date') }}">
      </div>
      <div class="field">
        <label>Travel style</label>
        <div class="seg">
          <label><input type="radio" name="style" value="budget" {{ (isset($editTrip) ? $editTrip->style : old('style'))==='budget'?'checked':'' }}><span>Budget</span></label>
          <label><input type="radio" name="style" value="mid" {{ (isset($editTrip) ? ($editTrip->style??'mid') : old('style','mid'))==='mid'?'checked':'' }}><span>Mid</span></label>
          <label><input type="radio" name="style" value="luxury" {{ (isset($editTrip) ? $editTrip->style : old('style'))==='luxury'?'checked':'' }}><span>Luxury</span></label>
        </div>
      </div>
    </div>

    <div class="field">
      <div class="budget-head">
        <label style="margin:0">Total budget <span class="muted" style="font-weight:500">— whole trip, whole party</span></label>
        <div class="budget-val"><span id="curSym">$</span><span id="budgetLabel">{{ isset($editTrip) ? number_format($editTrip->budget_total) : '3,000' }}</span></div>
      </div>
      <input type="range" id="budgetRange" min="200" max="30000" step="100" value="{{ isset($editTrip) ? $editTrip->budget_total : old('budget_total', 3000) }}">
      <div class="row row-2 mt">
        <input type="number" name="budget_total" id="budgetInput" min="0" value="{{ isset($editTrip) ? $editTrip->budget_total : old('budget_total', 3000) }}" required>
        <select name="currency" id="currency">
          @foreach(['USD'=>'$ USD','INR'=>'₹ INR','EUR'=>'€ EUR','GBP'=>'£ GBP','AED'=>'AED','SGD'=>'S$ SGD','JPY'=>'¥ JPY'] as $code=>$lbl)
            <option value="{{ $code }}" {{ (isset($editTrip) ? strtoupper($editTrip->currency) : old('currency','USD'))===$code?'selected':'' }}>{{ $lbl }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <div class="field">
      <label>Interests <span class="muted" style="font-weight:500">(optional)</span></label>
      <div class="chips">
        @php $chosenInterests = old('interests', isset($editTrip) ? ($editTrip->interests ?? []) : []); @endphp
        @foreach(['Food','Culture','Nature','Nightlife','Beaches','Adventure','Shopping','History','Relaxation','Art'] as $i)
          <label class="chip-toggle"><input type="checkbox" name="interests[]" value="{{ $i }}" {{ in_array($i, $chosenInterests)?'checked':'' }}><span>{{ $i }}</span></label>
        @endforeach
      </div>
    </div>

    <input type="hidden" name="compressed_chat_context" id="compressedChatContextField">

    {{-- Pre-Plan AI Chat Interface --}}
    <div id="prePlanChatBox" class="pre-plan-chat-box" style="display:none;margin-top:24px;border:1px solid var(--md-outline-variant);border-radius:12px;padding:20px;background:var(--md-surface-container-low);box-shadow:var(--md-elevation-1);">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;border-bottom:1px solid var(--md-outline-variant);padding-bottom:10px">
        <h4 style="margin:0;display:flex;align-items:center;gap:8px;font-family:'Poppins';font-weight:600;color:var(--md-primary)">
          <x-icon name="smart_toy" style="color:var(--md-primary)" /> Chat with Travel AI
        </h4>
        <button type="button" id="closePrePlanChat" style="border:none;background:none;font-size:24px;cursor:pointer;color:var(--md-on-surface-variant);line-height:1">&times;</button>
      </div>

      <div class="chat-log" id="prePlanChatLog" style="max-height:400px;overflow-y:auto;margin-bottom:16px;padding:8px;display:flex;flex-direction:column;gap:12px;">
        <div class="chat-msg bot">Hi! Let's personalize your trip. Gathering some clarification questions...</div>
      </div>

      <form id="prePlanChatForm" style="display:flex;gap:8px;margin-top:12px;border-top:1px solid var(--md-outline-variant);padding-top:12px">
        <input type="text" id="prePlanChatInput" placeholder="Type a custom preference or detail..." style="flex:1;padding:10px 14px;border-radius:20px;border:1px solid var(--md-outline-variant);background:var(--md-surface);color:var(--md-on-surface);font-size:14px;" autocomplete="off" required>
        <button type="submit" class="btn btn-filled btn-sm" style="border-radius:20px;padding:8px 16px;">Send</button>
      </form>
    </div>

    <div class="row row-2 mt" style="gap:12px;margin-top:20px;display:flex">
      <button type="button" class="btn btn-outlined" id="chatWithAIBtn" style="font-size:16px;padding:15px;display:flex;align-items:center;justify-content:center;gap:8px;flex:1">
        <x-icon name="chat" :size="20" /> Chat with AI
      </button>
      <button type="submit" class="btn btn-filled" id="generatePlanBtn" style="font-size:16px;padding:15px;display:flex;align-items:center;justify-content:center;gap:8px;flex:1">
        <x-icon name="auto_awesome" :size="20" /> {{ isset($editTrip) ? 'Save changes & regenerate' : 'Generate my plan' }}
      </button>
    </div>
    @if(!isset($editTrip))<p class="hint center mt">Free to try — no account needed. You'll get a shareable link.</p>@endif
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
<script type="application/json" id="editDestData">{!! isset($editTrip) ? json_encode($editTrip->destinations) : 'null' !!}</script>
<script type="application/json" id="fxRatesData">{!! json_encode($fxRates) !!}</script>
<script>
const SUGGEST = JSON.parse(document.getElementById('suggestData').textContent);
const EDIT_DESTS = JSON.parse(document.getElementById('editDestData').textContent);
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

// seed rows from edit data, or two empty rows
if(EDIT_DESTS && EDIT_DESTS.length){
  EDIT_DESTS.forEach(function(d){ destRow(d.name, d.days||3, d.nights||2, d.lat||'', d.lng||''); });
} else { addRow(); addRow(); }

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

<style>
.pre-plan-chat-box {
  background: var(--md-surface-container-low);
  border: 1px solid var(--md-outline-variant);
  border-radius: var(--md-shape-lg);
  padding: 24px;
  margin-top: 24px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: var(--md-elevation-1);
}
.pre-plan-chat-box h4 {
  font-family: 'Poppins', sans-serif;
  font-weight: 600;
  font-size: 18px;
  color: var(--md-primary);
}
.chat-log {
  max-height: 400px;
  overflow-y: auto;
  padding: 8px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.chat-msg {
  display: flex;
  flex-direction: column;
  gap: 8px;
  max-width: 85%;
  padding: 14px 18px;
  border-radius: var(--md-shape-lg);
  font-size: 14.5px;
  line-height: 1.5;
  animation: fadeIn 0.25s ease-out;
}
.chat-msg.bot {
  background: var(--md-surface-container-high);
  color: var(--md-on-surface);
  align-self: flex-start;
  border-bottom-left-radius: var(--md-shape-xs);
}
.chat-msg.user {
  background: var(--md-primary-container);
  color: var(--md-on-primary-container);
  align-self: flex-end;
  border-bottom-right-radius: var(--md-shape-xs);
}
.chat-question-card {
  border: 1px solid var(--md-outline-variant);
  border-radius: var(--md-shape-md);
  background: var(--md-surface-container);
  margin-bottom: 12px;
  overflow: hidden;
  transition: all 0.2s ease;
  text-align: left;
}
.chat-question-card.active {
  border-color: var(--md-primary);
  box-shadow: 0 0 0 1px var(--md-primary);
}
.chat-question-header {
  padding: 14px 18px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  justify-content: space-between;
  align-items: center;
  user-select: none;
}
.chat-question-header:hover {
  background: var(--md-state-hover);
}
.chat-question-options {
  padding: 0 18px 18px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.chat-option-btn {
  background: var(--md-surface-container-highest);
  border: 1px solid var(--md-outline-variant);
  border-radius: var(--md-shape-full);
  padding: 10px 18px;
  font-size: 13.5px;
  font-weight: 500;
  text-align: left;
  cursor: pointer;
  transition: all 0.15s ease;
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: var(--md-on-surface);
}
.chat-option-btn:hover {
  background: var(--md-primary-container);
  color: var(--md-on-primary-container);
  border-color: var(--md-primary);
}
.recommended-badge {
  background: var(--md-tertiary-container);
  color: var(--md-on-tertiary-container);
  font-size: 11px;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: var(--md-shape-full);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.preplan-pulse {
  animation: preplanPulse 2s infinite;
}
@keyframes preplanPulse {
  0% { box-shadow: 0 0 0 0 rgba(0, 92, 187, 0.4); }
  70% { box-shadow: 0 0 0 10px rgba(0, 92, 187, 0); }
  100% { box-shadow: 0 0 0 0 rgba(0, 92, 187, 0); }
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>

<script>
// Pre-Plan AI Chat Logic
(function() {
  const token = document.querySelector('meta[name=csrf-token]').content;
  const prePlanChatBox = document.getElementById('prePlanChatBox');
  const prePlanChatLog = document.getElementById('prePlanChatLog');
  const chatWithAIBtn = document.getElementById('chatWithAIBtn');
  const generatePlanBtn = document.getElementById('generatePlanBtn');
  const closePrePlanChat = document.getElementById('closePrePlanChat');
  const compressedChatContextField = document.getElementById('compressedChatContextField');
  const prePlanChatForm = document.getElementById('prePlanChatForm');
  const prePlanChatInput = document.getElementById('prePlanChatInput');

  let chatAnswers = [];
  let chatQuestions = [];
  let currentQuestionIndex = 0;

  chatWithAIBtn.addEventListener('click', async () => {
    if (prePlanChatBox.style.display === 'block') {
      prePlanChatBox.style.display = 'none';
      return;
    }

    const formVals = getFormValues();
    if (formVals.destinations.length === 0) {
      alert('Please add at least one destination first.');
      return;
    }

    prePlanChatBox.style.display = 'block';
    prePlanChatLog.innerHTML = `
      <div id="prePlanQuestionsContainer">
        <div class="chat-msg bot">Hi! Let's personalize your trip. Gathering some clarification questions...</div>
      </div>
      <div id="prePlanConversationFlow" style="display:flex;flex-direction:column;gap:12px;"></div>
    `;
    
    try {
      const res = await fetch('{{ route("plan.pre-chat-init") }}', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': token,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ form_values: formVals })
      });
      
      if (!res.ok) throw new Error('Failed to load questions');
      const data = await res.json();
      chatQuestions = data.questions || [];
      chatAnswers = [];
      currentQuestionIndex = 0;
      
      renderQuestionsList();
    } catch (err) {
      const container = document.getElementById('prePlanQuestionsContainer');
      if (container) {
        container.innerHTML = '<div class="chat-msg bot" style="color:var(--md-error)">Sorry, we couldn\'t load personalization questions. You can still generate your plan directly!</div>';
      }
    }
  });

  closePrePlanChat.addEventListener('click', () => {
    prePlanChatBox.style.display = 'none';
  });

  prePlanChatForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const text = prePlanChatInput.value.trim();
    if (!text) return;

    prePlanChatInput.value = '';

    const flowContainer = document.getElementById('prePlanConversationFlow');
    if (!flowContainer) return;

    // Append user input to log
    const userMsg = document.createElement('div');
    userMsg.className = 'chat-msg user';
    userMsg.textContent = text;
    flowContainer.appendChild(userMsg);
    prePlanChatLog.scrollTop = prePlanChatLog.scrollHeight;

    // Add to answers list
    chatAnswers.push({
      id: 'user_input_' + Date.now(),
      question: 'User message',
      answer: text
    });

    await checkNextAdaptiveQuestion();
  });

  function getSerializedDests() {
    return [...list.querySelectorAll('.dest-item')].map(r=>({
      name: r.querySelector('.dname').value.trim(),
      days: parseInt(r.querySelector('.ddays').value)||3,
      nights: parseInt(r.querySelector('.dnights').value)||2,
      lat: parseFloat(r.querySelector('.dlat').value)||null,
      lng: parseFloat(r.querySelector('.dlng').value)||null,
    })).filter(d=>d.name);
  }

  function getFormValues() {
    const dests = getSerializedDests();
    const interests = [];
    document.querySelectorAll('input[name="interests[]"]:checked').forEach(el => {
      interests.push(el.value);
    });
    return {
      origin: document.getElementById('originInput').value.trim(),
      destinations: dests,
      travelers: parseInt(document.querySelector('input[name="travelers"]').value) || 2,
      start_date: document.querySelector('input[name="start_date"]').value || null,
      end_date: document.querySelector('input[name="end_date"]').value || null,
      style: document.querySelector('input[name="style"]:checked')?.value || 'mid',
      budget_total: parseFloat(document.getElementById('budgetInput').value) || 3000,
      currency: document.getElementById('currency').value,
      interests: interests
    };
  }

  function renderQuestionsList() {
    const container = document.getElementById('prePlanQuestionsContainer');
    if (!container) return;

    container.innerHTML = '<div class="chat-msg bot">Please answer these 3 quick questions to help personalize your itinerary:</div>';
    
    chatQuestions.forEach((q, idx) => {
      const card = document.createElement('div');
      card.className = 'chat-question-card' + (idx === currentQuestionIndex ? ' active' : '');
      card.id = 'chat-q-card-' + idx;
      
      let answerHtml = '';
      const answered = chatAnswers.find(a => a.id === q.id);
      if (answered) {
        answerHtml = `<div style="padding:10px 18px;background:var(--md-primary-container);color:var(--md-on-primary-container);font-size:13.5px;font-weight:500;">✓ Selected: ${answered.answer}</div>`;
      }
      
      card.innerHTML = `
        <div class="chat-question-header" onclick="toggleQuestionCard(${idx})">
          <span>${idx + 1}. ${q.question}</span>
          <span class="arrow">${idx === currentQuestionIndex ? '▼' : '►'}</span>
        </div>
        <div class="chat-question-options" id="chat-q-options-${idx}" style="display:${idx === currentQuestionIndex && !answered ? 'flex' : 'none'}">
          ${q.options.map(opt => {
            const isRec = opt === q.recommended;
            return `
              <button type="button" class="chat-option-btn" onclick="selectAnswer(${idx}, '${opt.replace(/'/g, "\\'")}')">
                <span>${opt}</span>
                ${isRec ? '<span class="recommended-badge">Recommended</span>' : ''}
              </button>
            `;
          }).join('')}
        </div>
        ${answerHtml}
      `;
      
      container.appendChild(card);
    });
    
    prePlanChatLog.scrollTop = prePlanChatLog.scrollHeight;
  }

  window.toggleQuestionCard = function(idx) {
    const card = document.getElementById('chat-q-card-' + idx);
    const options = document.getElementById('chat-q-options-' + idx);
    const arrow = card.querySelector('.arrow');
    
    if (options.style.display === 'none') {
      for (let i = 0; i < chatQuestions.length; i++) {
        const opt = document.getElementById('chat-q-options-' + i);
        if (opt) opt.style.display = 'none';
        const arr = document.querySelector('#chat-q-card-' + i + ' .arrow');
        if (arr) arr.textContent = '►';
        const c = document.getElementById('chat-q-card-' + i);
        if (c) c.classList.remove('active');
      }
      
      options.style.display = 'flex';
      arrow.textContent = '▼';
      card.classList.add('active');
    } else {
      options.style.display = 'none';
      arrow.textContent = '►';
      card.classList.remove('active');
    }
  };

  window.selectAnswer = async function(qIdx, answer) {
    const q = chatQuestions[qIdx];
    
    const existingIdx = chatAnswers.findIndex(a => a.id === q.id);
    if (existingIdx > -1) {
      chatAnswers[existingIdx].answer = answer;
    } else {
      chatAnswers.push({ id: q.id, question: q.question, answer: answer });
    }
    
    if (currentQuestionIndex === qIdx) {
      currentQuestionIndex = chatQuestions.findIndex((q, idx) => !chatAnswers.some(a => a.id === q.id));
    }
    
    renderQuestionsList();
    
    if (chatAnswers.length >= chatQuestions.length) {
      await checkNextAdaptiveQuestion();
    }
  };

  function appendFollowUpQuestion(q) {
    const flowContainer = document.getElementById('prePlanConversationFlow');
    if (!flowContainer) return;

    const card = document.createElement('div');
    card.className = 'chat-question-card active';
    card.id = 'chat-q-card-followup-' + q.id;
    
    card.innerHTML = `
      <div class="chat-question-header">
        <span>${q.question}</span>
      </div>
      <div class="chat-question-options" id="chat-q-options-followup-${q.id}" style="display:flex;flex-direction:column;gap:10px;padding: 14px 18px 18px;">
        ${q.options.map(opt => {
          const isRec = opt === q.recommended;
          return `
            <button type="button" class="chat-option-btn" onclick="selectFollowUpAnswer('${q.id}', '${q.question.replace(/'/g, "\\'")}', '${opt.replace(/'/g, "\\'")}')">
              <span>${opt}</span>
              ${isRec ? '<span class="recommended-badge">Recommended</span>' : ''}
            </button>
          `;
        }).join('')}
      </div>
    `;
    flowContainer.appendChild(card);
    prePlanChatLog.scrollTop = prePlanChatLog.scrollHeight;
  }

  window.selectFollowUpAnswer = async function(qId, questionText, answer) {
    const card = document.getElementById('chat-q-card-followup-' + qId);
    if (!card) return;

    card.innerHTML = `
      <div class="chat-question-header">
        <span>${qId.includes('user_input') ? 'Custom preference' : questionText}</span>
      </div>
      <div style="padding:10px 18px;background:var(--md-primary-container);color:var(--md-on-primary-container);font-size:13.5px;font-weight:500;">✓ Selected: ${answer}</div>
    `;

    // Remove any previous matching ID to avoid duplicates
    const idx = chatAnswers.findIndex(a => a.id === qId);
    if (idx > -1) {
      chatAnswers[idx].answer = answer;
    } else {
      chatAnswers.push({ id: qId, question: questionText, answer: answer });
    }

    await checkNextAdaptiveQuestion();
  };

  async function checkNextAdaptiveQuestion() {
    const formVals = getFormValues();
    const flowContainer = document.getElementById('prePlanConversationFlow');
    if (!flowContainer) return;

    const loadingMsg = document.createElement('div');
    loadingMsg.className = 'chat-msg bot';
    loadingMsg.id = 'chat-loading-followup';
    loadingMsg.innerHTML = 'Analyzing preferences for follow-up...';
    flowContainer.appendChild(loadingMsg);
    prePlanChatLog.scrollTop = prePlanChatLog.scrollHeight;
    
    try {
      const res = await fetch('{{ route("plan.pre-chat-next") }}', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': token,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ form_values: formVals, answers: chatAnswers })
      });
      
      if (loadingMsg) loadingMsg.remove();
      
      if (!res.ok) throw new Error('Failed to get follow-up');
      const data = await res.json();
      
      if (data.has_more && data.question) {
        const newQ = data.question;
        if (!chatQuestions.some(q => q.id === newQ.id) && !chatAnswers.some(a => a.id === newQ.id)) {
          appendFollowUpQuestion(newQ);
        } else {
          finishPrePlanChat(data.compressed_context || "Context compiled.");
        }
      } else {
        finishPrePlanChat(data.compressed_context || "Context compiled.");
      }
    } catch (err) {
      if (loadingMsg) loadingMsg.remove();
      finishPrePlanChat("Fallback context compiled.");
    }
  }

  function finishPrePlanChat(compressedContext) {
    compressedChatContextField.value = compressedContext;
    const flowContainer = document.getElementById('prePlanConversationFlow');
    if (!flowContainer) return;
    
    const endCard = document.createElement('div');
    endCard.className = 'chat-msg bot';
    endCard.style.borderColor = 'var(--md-primary)';
    endCard.style.borderWidth = '1px';
    endCard.style.borderStyle = 'solid';
    endCard.innerHTML = `<strong>✨ Preferences saved!</strong><br>We gathered everything needed to tailor the itinerary to your style. Click <strong>Generate my plan</strong> below to build your custom trip plan.`;
    flowContainer.appendChild(endCard);
    prePlanChatLog.scrollTop = prePlanChatLog.scrollHeight;
    
    generatePlanBtn.classList.add('preplan-pulse');
  }
})();
</script>
@endpush
@endsection
