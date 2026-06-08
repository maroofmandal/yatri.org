# Interactive Trip Planner UI

## Task 1: Enhance CSS Design System (`public/css/yatri.css`)

Add new CSS components for the interactive features while keeping the minimalist aesthetic:

- **Photo carousel** styles (`.photo-carousel`, `.carousel-item`) — horizontally scrollable thumbnails with hover scale
- **Lightbox** styles (`.lightbox`, `.lightbox-img`, `.lightbox-nav`) — full-screen image viewer with prev/next/close
- **Draggable day cards** (`.day[draggable]`, `.day.dragging`, `.day.drag-over`) — visual feedback for drag reorder
- **City destination cards** (`.city-card`, `.city-head`, `.city-body`, `.spot-card`) — expandable cards with gradient headers like the demo
- **Interactive controls** (`.budget-slider`, `.day-stepper`, `.selection-toggle`) — budget slider, +/- day counters, checkbox-style selection
- **Sticky estimation bar** (`.est-bar`) — fixed bottom bar showing running total of selected items
- **Selectable hotel/flight cards** (`.card.selectable`, `.card.selected`) — cards with selection state
- **Scroll reveal animations** (`.reveal`, `.visible`) — fade-up on scroll like the demo
- Responsive adjustments for mobile (760px breakpoint)

**Files:** `public/css/yatri.css`

---

## Task 2: Expose Full Plan Data to JavaScript (`resources/views/planner/show.blade.php`)

Currently only `route`, `route_options`, and `cur` are exposed to JS via `#page-data`. Expand this:

- Add `data-plan` attribute containing the full `$plan` JSON (days, hotels, flights, budget, places, transport)
- Add `data-photos-api-key` for building photo URLs client-side
- Add `data-hotel-link` and `data-flight-link` patterns for building booking URLs
- This allows all interactive features to work purely client-side without additional API calls

**Files:** `resources/views/planner/show.blade.php` (the `#page-data` div around line 453)

---

## Task 3: Rewrite Day-by-Day Section with Photo Carousels & Lightbox

Replace the static day-by-day rendering (lines 204-255) with an enhanced version inspired by the demo:

- Each day card gets a **photo carousel** (horizontally scrollable thumbnails from Google Places photos)
- Clicking a photo opens a **lightbox** overlay with prev/next navigation and keyboard support (Esc, arrow keys)
- Day cards show: day number/date sidebar (gradient colored), title, location, image carousel, bullet-point activities, review snippet, "From the reviews" callout, Google Maps link button
- Add **tag badges** (Culture, City Vibes, Anime Day, etc.) with emoji like the demo
- Add **drag handle** on each day card for reordering (uses HTML5 drag-and-drop API)
- Days are wrapped in a container with `id="daysContainer"` for JS manipulation

**Files:** `resources/views/planner/show.blade.php` (lines 204-255)

---

## Task 4: Rewrite Destinations Section as City Cards

Replace the static hotels block (lines 257-317) with city-grouped destination cards:

- Each city gets a **collapsible card** with gradient header (city name, nights, "Show on map" button)
- **Cost chips** row: hotel/night, food/day, activities, transport — with currency-convertible `.money` spans
- **Hotel options** grid (3-column): photo strip, name, Google star rating, review count, price/night, "Map & reviews" link
- **Spots to visit** list: photo gallery (3 images per spot), spot name, Google rating, entry cost, review quote, Maps link
- Each hotel and spot has a **checkbox/toggle** to include/exclude from the running total
- Hotel selection is tracked in JS state and contributes to the live budget estimation

**Files:** `resources/views/planner/show.blade.php` (lines 257-317)

---

## Task 5: Add Interactive Controls — Budget Slider, Day Stepper, Route Selection

Add an **interactive control panel** between the hero and the budget section:

- **Budget slider** (`<input type="range">`): allows user to adjust their target budget; as they slide, the budget bars and fit status update live; if budget drops below current plan total, show a warning
- **Days stepper** (+/− buttons): lets user increase/decrease the trip duration; shows a message like "Add/remove days to see adjusted itinerary" (since we can't regenerate the AI plan client-side, this triggers a regenerate prompt if days change)
- **Route selection tabs**: enhanced version of the existing route cards — tabbed interface with "Trip A" / "Trip B" labels; switching updates the map polyline, day-by-day plan (reordered days), and the hotel/flight sections
- **Currency toggle** remains in the nav but also add a quick-toggle (₹/$) pill in the control panel

**Files:** `resources/views/planner/show.blade.php`, `public/css/yatri.css`

---

## Task 6: Add Flights Selection with Options

Enhance the flights section (lines 171-188):

- Each flight card becomes **selectable** with a checkbox or radio button
- Show flight type badge (non-stop vs 1-stop) with distinct colors
- Add a "flights total" summary card that sums selected flights
- Flight costs feed into the live estimation bar

**Files:** `resources/views/planner/show.blade.php` (lines 171-188)

---

## Task 7: Add Sticky Estimation Bar & Live Total Calculation

Add a **fixed bottom estimation bar** that persists as the user scrolls:

- Shows running totals: Accommodation (selected hotels × nights) + Food (per day × days) + Activities (selected spots) + Transport + Flights (selected)
- Grand total with currency conversion
- "Within budget" / "Over budget by X" indicator
- Updates instantly when user toggles hotels, spots, flights, or adjusts budget slider
- Smooth slide-up animation on scroll (appears after scrolling past the hero)
- Collapsible on mobile

**Files:** `resources/views/planner/show.blade.php` (new section), `public/css/yatri.css`

---

## Task 8: JavaScript Interactive Engine

Add a comprehensive `<script>` block replacing the current one (lines 459-601):

### Core state management:
```js
const state = {
  selectedRoute: 0,        // active route option index
  budget: tripBudgetTotal, // user's current budget target
  days: tripDays,          // current day count
  selectedHotels: {},      // cityIndex -> hotelIndex mapping
  selectedFlights: [],     // indices of selected flight cards
  selectedSpots: {},       // dayIndex -> [spotIndices]
  currency: tripCurrency,  // display currency
  fxRate: 1               // current FX rate
};
```

### Key functions:
- `recalcTotal()` — sums all selected items, updates the estimation bar
- `renderDays(routeIdx)` — re-renders day-by-day based on selected route (reorders days from plan data)
- `initDragDrop()` — HTML5 drag-and-drop on day cards; on drop, reorders the day array and re-renders
- `initLightbox()` — photo lightbox with prev/next/keyboard navigation
- `initBudgetSlider()` — range input that updates budget bars and fit status live
- `initRouteTabs()` — switches route, updates map polylines, re-renders days and destination cards
- `initSelection()` — checkbox/toggle handlers for hotels, flights, spots
- `initScrollReveal()` — IntersectionObserver for fade-in animations
- `applyCurrency()` — converts all `.money[data-amt]` elements

### Persistence:
- Save `state` to `localStorage` keyed by trip share_token so selections persist on page reload
- Restore state on page load

**Files:** `resources/views/planner/show.blade.php` (replace script block)

---

## Task 9: Enhance Photo Display with Real Google Business Images

The `PlacesEnricher` already fetches Google Places photos, but the current UI only shows:
- 1 thumbnail (60px) per day item
- 3 photos (120px) per hotel

Enhance to match the demo:
- **Day items**: Show a 4-photo horizontal carousel (200px each) per day, sourced from the place's photos in `plan.places`
- **Spots in destination cards**: Show 3-photo gallery per spot with click-to-enlarge
- **Hotels**: Keep existing 3-photo strip but increase to 160px height and add lightbox
- **Fallback**: When no Google Places photos exist, show a gradient placeholder (like the demo's `linear-gradient(135deg,#33384a,#7a1f37)`)

The `$photoUrl` closure and `placesData` are already available in the Blade template. Just need to render more photos and wire up the lightbox.

**Files:** `resources/views/planner/show.blade.php`

---

## Task 10: UI Polish — Minimalist & Modern Styling

Apply the demo's design language to the existing components:

- **Softer shadows** and more whitespace (increase block padding to 28px, card padding to 20px)
- **Gradient day sidebars** — cycle through 5 gradient colors like the demo (`#33384a,#7a1f37`, `#1f3a5f,#2b6cb0`, etc.)
- **Pill badges** for tags (anime, culture, city, fuji, move, village) with emoji
- **Review callout boxes** — left-bordered with accent2 color, "From the reviews —" prefix
- **Google Maps buttons** — rounded pill style with pin icon
- **Cost badges** — green "Free" or "Entry $X" aligned right on spot names
- **City card headers** — gradient backgrounds with white text, matching the demo's per-city colors
- **Chip components** for cost breakdowns (icon + label + bold price)
- **Smooth hover transitions** on all interactive elements (translateY(-2px) + shadow increase)

**Files:** `public/css/yatri.css`, `resources/views/planner/show.blade.php`

---

## Dependencies

- Task 1 (CSS) should be done first — all other tasks depend on the new styles
- Task 2 (data exposure) must be done before Tasks 3-8 (JS features)
- Tasks 3, 4, 6 (section rewrites) can be done in parallel
- Task 5 (controls) and Task 7 (estimation bar) depend on Task 8 (JS engine)
- Task 8 (JS) depends on Tasks 3, 4, 6 (needs the new HTML structure)
- Task 9 (photos) is part of Tasks 3 and 4
- Task 10 (polish) is iterative and applied throughout

## Risks & Mitigations

| Risk | Mitigation |
|------|-----------|
| Large JS bundle in inline `<script>` | Keep code well-organized with clear sections; consider extracting to `resources/js/trip-planner.js` if >500 lines |
| Drag-and-drop conflicts with Leaflet map scroll | Only enable drag on day cards, not on the map; use `draggable` attribute only on `.day` elements |
| Plan JSON too large for `data-` attribute | If plan exceeds ~50KB, use a `<script type="application/json">` tag instead of a data attribute |
| Photo loading performance | Use `loading="lazy"` on all images; limit carousel to 4 photos per day, 3 per spot |
| localStorage quota | State object is small (<1KB per trip); no risk |
| Currency conversion accuracy | Reuse existing FX rate API; rates are cached 24h |

## Rejected Alternatives

1. **Using Alpine.js/Vue** — rejected because the project uses 100% vanilla JS everywhere; introducing a framework would be inconsistent and add bundle weight for a single page
2. **Server-side re-rendering via AJAX** — rejected because all data is already in the plan JSON; client-side manipulation is faster and avoids server round-trips
3. **Separate JS file** — considered but rejected initially; the existing pattern is inline `<script>` blocks in Blade templates. May extract later if code grows beyond 600 lines
4. **React/SPA approach** — rejected as massive overkill for this Laravel app with server-rendered pages
