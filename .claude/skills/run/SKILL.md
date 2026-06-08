# Run Yatri Locally

Start the Yatri trip planner on localhost with Laravel backend + Vite frontend.

## Quick Start

```bash
cd ~/Developer/yatri.org

# Terminal 1 — Laravel backend
php artisan serve

# Terminal 2 — Vite frontend (hot reload)
npm run dev
```

Open **http://127.0.0.1:8000** in your browser.

## One-liner (both servers in one terminal)

```bash
cd ~/Developer/yatri.org && bash run.sh
```

Press `Ctrl+C` to stop both servers.

## Details

| Server | URL | Purpose |
|--------|-----|---------|
| Laravel | http://127.0.0.1:8000 | Backend API + routes |
| Vite | http://localhost:5173 | Frontend hot reload (dev only) |

## Troubleshooting

- **Port in use**: `lsof -ti:8000 | xargs kill -9` then retry
- **Missing deps**: `composer install && npm install`
- **DB errors**: `php artisan migrate --force`
