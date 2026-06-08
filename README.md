# Yatri — AI Trip Planner

Run the project locally on localhost.

## Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+
- npm

## Setup

```bash
# Install PHP dependencies
composer install

# Install JS dependencies
npm install

# Create SQLite database (if missing)
touch database/database.sqlite

# Run migrations
php artisan migrate --force
```

## Run Locally

```bash
cd ~/Developer/yatri.org

# Terminal 1 — Laravel backend
php artisan serve

# Terminal 2 — Vite frontend (hot reload)
npm run dev
```

Open **http://127.0.0.1:8000** in your browser.

### One-liner (both servers)

```bash
bash run.sh
```

Press `Ctrl+C` to stop both servers.

## Servers

| Server | URL | Purpose |
|--------|-----|---------|
| Laravel | http://127.0.0.1:8000 | Backend API + routes |
| Vite | http://localhost:5173 | Frontend hot reload (dev only) |

## Troubleshooting

- **Port 8000 in use**: `lsof -ti:8000 | xargs kill -9`
- **Port 5173 in use**: `lsof -ti:5173 | xargs kill -9`
- **DB errors**: `php artisan migrate --force`
- **Cache issues**: `php artisan cache:clear && php artisan config:clear`
