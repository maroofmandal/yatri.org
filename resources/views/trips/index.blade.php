@extends('layouts.app')
@section('title', 'Explore trips — Yatri')
@section('meta_description', 'Browse AI-planned trips from travelers worldwide. See budgets, itineraries, and real costs for destinations across the globe.')

@section('right_sidebar')
  @if($destinations->count())
  <div class="sidebar-widget">
    <h3>Popular destinations</h3>
    <div class="chips">
      @foreach($destinations as $d)
        <a class="chip-link {{ request('destination') === $d->name ? 'active' : '' }}"
           href="{{ route('trips.explore', ['destination' => $d->name]) }}">
          <x-icon name="location_on" :size="16" /> {{ $d->name }}
        </a>
      @endforeach
    </div>
    @if(request('destination'))
      <div style="margin-top:12px">
        <a href="{{ route('trips.explore') }}" style="font-size:12px;color:var(--md-primary);font-weight:600;display:inline-flex;align-items:center;gap:4px">
          <x-icon name="close" :size="14" /> Clear filter
        </a>
      </div>
    @endif
  </div>
  @endif
@endsection

@section('content')
<div class="wrap" style="padding-top:36px;padding-bottom:100px">

  {{-- Page header --}}
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px">
    <div>
      <h2 style="margin:0">
        @if(request('destination'))
          Trips to {{ request('destination') }}
        @else
          Explore trips
        @endif
      </h2>
      @if(request('search'))
        <p class="muted" style="font-size:13px;margin:4px 0 0">
          Results for "{{ request('search') }}"
        </p>
      @endif
    </div>
    <a class="btn btn-filled btn-sm" href="{{ route('planner') }}">
      <x-icon name="add" :size="18" /> Plan a trip
    </a>
  </div>

  {{-- Toolbar: selector + icon toggles --}}
  <form method="GET" action="{{ route('trips.explore') }}" id="tripsForm">
    @if(request('destination'))
      <input type="hidden" name="destination" value="{{ request('destination') }}">
    @endif
    @if(request('search'))
      <input type="hidden" name="search" id="searchHidden" value="{{ request('search') }}">
    @endif
    @if(request('sort'))
      <input type="hidden" name="sort" id="sortHidden" value="{{ request('sort') }}">
    @endif

    {{-- Row 1: For you / Following + icon buttons --}}
    <div class="trips-toprow">
      <div class="seg">
        <label><input type="radio" name="filter" value="" {{ !request('filter')?'checked':'' }} @guest disabled @endguest onchange="this.form.submit()"><span>For you</span></label>
        <label><input type="radio" name="filter" value="following" {{ request('filter')==='following'?'checked':'' }} @guest disabled @endguest onchange="this.form.submit()"><span>Following</span></label>
      </div>

      <div class="trips-icons">
        <button type="button" class="trips-toggle-btn {{ request('search') ? 'active' : '' }}" id="btnToggleSearch" title="Search">
          <x-icon name="search" :size="20" />
        </button>
        <button type="button" class="trips-toggle-btn {{ request('sort') && request('sort') !== 'latest' ? 'active' : '' }}" id="btnToggleFilter" title="Sort & filter">
          <x-icon name="filter_list" :size="20" />
        </button>
      </div>
    </div>

    {{-- Row 2: Search panel (hidden by default) --}}
    <div class="trips-panel {{ request('search') ? 'open' : '' }}" id="panelSearch">
      <div class="search-input">
        <x-icon name="search" :size="20" />
        <input type="text" id="searchInput" placeholder="Search trips, destinations…"
               value="{{ request('search') }}"
               onkeydown="if(event.key==='Enter'){document.getElementById('searchHidden').value=this.value;this.form.submit()}">
        @if(request('search'))
          <button type="button" class="clear-btn" onclick="document.getElementById('searchHidden').value='';document.getElementById('searchInput').value='';document.getElementById('tripsForm').submit()">
            <x-icon name="close" :size="16" />
          </button>
        @endif
      </div>
    </div>

    {{-- Row 3: Sort/filter panel (hidden by default) --}}
    <div class="trips-panel {{ request('sort') && request('sort') !== 'latest' ? 'open' : '' }}" id="panelFilter">
      <div class="filter-row">
        <label class="filter-label">Sort by</label>
        <select name="sort" onchange="this.form.submit()">
          <option value="latest" {{ request('sort','latest')==='latest'?'selected':'' }}>Newest first</option>
          <option value="oldest" {{ request('sort')==='oldest'?'selected':'' }}>Oldest first</option>
          <option value="budget_low" {{ request('sort')==='budget_low'?'selected':'' }}>Budget: low to high</option>
          <option value="budget_high" {{ request('sort')==='budget_high'?'selected':'' }}>Budget: high to low</option>
          <option value="most_liked" {{ request('sort')==='most_liked'?'selected':'' }}>Most liked</option>
        </select>
      </div>
    </div>
  </form>

  <script>
    (function(){
      const btnSearch = document.getElementById('btnToggleSearch');
      const btnFilter = document.getElementById('btnToggleFilter');
      const panelSearch = document.getElementById('panelSearch');
      const panelFilter = document.getElementById('panelFilter');

      function toggle(panel, btn){
        const isOpen = panel.classList.contains('open');
        // close both
        panelSearch.classList.remove('open');
        panelFilter.classList.remove('open');
        btnSearch.classList.remove('active');
        btnFilter.classList.remove('active');
        if(!isOpen){
          panel.classList.add('open');
          btn.classList.add('active');
          // focus search input if opening search panel
          if(panel === panelSearch){
            setTimeout(()=>document.getElementById('searchInput')?.focus(), 100);
          }
        }
      }

      btnSearch.addEventListener('click', ()=>toggle(panelSearch, btnSearch));
      btnFilter.addEventListener('click', ()=>toggle(panelFilter, btnFilter));
    })();
  </script>

  {{-- Trips grid --}}
  @if($trips->count())
    <div class="grid grid-3">
      @foreach($trips as $trip)
        @include('partials.trip-card')
      @endforeach
    </div>

    @if($trips->hasPages())
      <div class="pager mt2" style="display:flex;gap:8px;align-items:center;justify-content:center">
        @if($trips->onFirstPage())
          <span class="btn btn-ghost btn-sm" style="opacity:.5"><x-icon name="chevron_left" :size="18" /> Prev</span>
        @else
          <a class="btn btn-ghost btn-sm" href="{{ $trips->previousPageUrl() }}"><x-icon name="chevron_left" :size="18" /> Prev</a>
        @endif
        <span class="muted" style="font-size:13px">Page {{ $trips->currentPage() }} / {{ $trips->lastPage() }}</span>
        @if($trips->hasMorePages())
          <a class="btn btn-ghost btn-sm" href="{{ $trips->nextPageUrl() }}">Next <x-icon name="chevron_right" :size="18" /></a>
        @else
          <span class="btn btn-ghost btn-sm" style="opacity:.5">Next <x-icon name="chevron_right" :size="18" /></span>
        @endif
      </div>
    @endif
  @else
    <div class="block center">
      <x-icon name="explore" :size="36" style="color:var(--md-on-surface-variant);display:block;margin:0 auto 12px" />
      @if(request('search') || request('destination'))
        <p class="lead">No trips match your search. Try different keywords or clear the filter.</p>
        <a class="btn btn-ghost btn-sm" href="{{ route('trips.explore') }}">Clear filters</a>
      @else
        <p class="lead">No public trips yet — be the first to share one.</p>
        <a class="btn btn-filled" href="{{ route('planner') }}">Plan a trip</a>
      @endif
    </div>
  @endif
</div>
@endsection
