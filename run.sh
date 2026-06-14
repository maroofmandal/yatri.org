#!/usr/bin/env bash
set -e

cd "$(dirname "$0")"

echo "==> Yatri — starting servers on localhost"
echo "    Laravel: http://127.0.0.1:8008"
echo "    Vite:    http://localhost:5173"
echo ""

cleanup() {
  echo ""
  echo "==> Stopping servers..."
  kill $LARAVEL_PID $VITE_PID 2>/dev/null
  wait $LARAVEL_PID $VITE_PID 2>/dev/null
  echo "==> Done."
  exit 0
}
trap cleanup SIGINT SIGTERM

php artisan serve --host=127.0.0.1 --port=8008 &
LARAVEL_PID=$!

npm run dev &
VITE_PID=$!

wait $LARAVEL_PID $VITE_PID
