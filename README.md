# Yatri — AI budget trip planner

Type a start city, add as many stops as you want, set a budget — Yatri builds a
**costed, day-by-day itinerary that fits the budget**, grounded with live Google
Search + Google Maps data via the Gemini API. Every plan gets a shareable link.

Built on **Laravel 13 + MySQL**. No build step required to run (CSS is a static
asset; Vite is optional).

---

## How it works

**Budget-fit engine** (`app/Services/Planner/TripPlanner.php`)

1. **Research pass** — Gemini call *grounded* with Google Search + Google Maps
   gathers live 2026 prices, hotels, transport and attractions for the route.
2. **Structure pass** — a second Gemini call (JSON `responseSchema`) turns that
   research into a strict itinerary. Grounding tools and JSON schema can't be used
   in the same call, so these are two passes.
3. **Fit loop** — the backend sums the line items; if the total exceeds the cap it
   re-prompts Gemini to downgrade (cheaper hotels, buses/trains over flights, fewer
   paid activities) up to 3× until it fits. The result is saved with a `fit_status`.

**No API key?** The planner falls back to a deterministic sample plan scaled to the
budget, so the whole flow is usable immediately. Add a key for live plans.

**Gemini** (`app/Services/Gemini/GeminiClient.php`) defaults to the free-tier
`gemini-2.5-flash` with `google_search` + `google_maps` grounding, and degrades
gracefully (full grounding → search-only → none) if a tool is unsupported.

---

## Local development

```bash
composer install
cp .env.example .env && php artisan key:generate   # if no .env yet
php artisan migrate:fresh --seed
php artisan serve
```

Local dev uses **SQLite** by default (see `.env`). Production uses **MySQL**.
Migrations are portable across both.

Add a Gemini key (free): https://aistudio.google.com/apikey → paste it in
**Admin → Settings → AI**, or set `GEMINI_API_KEY` in `.env`.

### Seeded admin

| Email | Password | Role |
|---|---|---|
| `admin@yatri.org` | `yatri-admin-2026` | admin |
| `traveler@yatri.org` | `password` | user |

> Change the admin password after first login.

---

## Production deploy (yatri.org)

Pushing to `main` triggers `.github/workflows/deploy.yml`, which SSHes to the server
and runs `git pull` + `composer install` + `php artisan migrate --force` + cache
warmup.

**One-time server setup** (not automated — do it once):

1. Create `.env` on the server from `.env.example` with the real MySQL creds and a
   generated `APP_KEY` (`php artisan key:generate`). Set `GEMINI_API_KEY`.
2. Point the vhost docroot at **`~/htdocs/yatri.org/public`** (Laravel's entry point).
3. Ensure `storage/` and `bootstrap/cache/` are writable by the web user.
4. Run the first `php artisan migrate --force --seed`.

Database: `yatri` / user `yatri` (MySQL). Created already on the server.

---

## Admin panel

`/admin` (admin role required):

- **Dashboard** — users, trips, Gemini calls/tokens, failure counts
- **Trips** — search, view full plan JSON, toggle public/private, delete
- **Users** — promote/demote admin, delete
- **Destinations** — manage the popular-cities catalog (autocomplete + suggestions)
- **Gemini usage** — every call logged: kind, model, tokens, latency, grounded, status
- **Settings** — Gemini key/model/grounding toggles, brand, FX rate, affiliate URLs

---

## Roadmap (wedge-first)

This repo is **Phase 1**: the AI budget planner. Deliberately *not* the 200-feature
social network in the original brief — that's death by scope. Build order:

1. **Now** — AI budget planner: inputs → grounded itinerary → budget-fit → share link + admin ✅
2. Accounts + saved trips + AI chat edits + booking affiliate deep-links
3. Light social: public trip gallery, clone-a-trip, follow (grows from shared plans)
4. Scoring/gamification — *only after* liquidity; start trivial (trips/countries/distance)
5. Premium (unlimited AI, offline, price alerts) + DMO sponsorship

See the planning notes in the PR/handoff for the full critique of the original spec.

---

## Stack

Laravel 13 · PHP 8.3 · MySQL (prod) / SQLite (dev) · Gemini API · Leaflet/OSM maps ·
vanilla JS + a hand-rolled design system (`public/css/yatri.css`). Legacy static
mockup preserved in `legacy/` as the design reference.
