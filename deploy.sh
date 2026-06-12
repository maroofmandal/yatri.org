#!/bin/bash
# deploy.sh — Deploy yatri.org with full cache flush
# Usage: bash deploy.sh [commit_message]

set -e

COMMIT_MSG="${1:-Deploy: $(date +%Y-%m-%d\ %H:%M)}"
REMOTE="ubuntu@155.248.246.43"
SSH_KEY="$HOME/.ssh/sattaz-key"
REMOTE_DIR="/home/yatri/htdocs/yatri.org"

echo "==> Bumping asset version..."
# Auto-bump the APP_VERSION in config/app.php
NEW_VERSION=$(date +%Y.%m.%d.%H%M)
if [[ "$OSTYPE" == "darwin"* ]]; then
  sed -i '' "s/'version' => env('APP_VERSION', '[^']*')/'version' => env('APP_VERSION', '$NEW_VERSION')/" config/app.php
else
  sed -i "s/'version' => env('APP_VERSION', '[^']*')/'version' => env('APP_VERSION', '$NEW_VERSION')/" config/app.php
fi
echo "    Version bumped to: $NEW_VERSION"

echo "==> Committing changes..."
git add -A
git commit -m "$COMMIT_MSG" || echo "    Nothing to commit"

echo "==> Pushing to GitHub..."
git push origin main

echo "==> Deploying to server..."
ssh -i "$SSH_KEY" "$REMOTE" "
  sudo su - yatri -c '
  cd ~/htdocs/yatri.org &&

  echo \"---- Pulling latest code...\" &&
  git pull origin main &&

  echo \"---- Installing composer dependencies...\" &&
  composer install --no-dev --optimize-autoloader --no-interaction 2>&1 | tail -5 &&

  echo \"---- Running database migrations...\" &&
  php artisan migrate --force &&

  echo \"---- Clearing ALL Laravel caches...\" &&
  php artisan view:clear &&
  php artisan route:clear &&
  php artisan config:clear &&
  php artisan cache:clear &&
  php artisan event:clear &&

  echo \"---- Fixing permissions...\" &&
  chmod 644 public/images/*.png public/images/*.ico public/site.webmanifest &&
  cp public/site.webmanifest site.webmanifest 2>/dev/null; chmod 644 site.webmanifest 2>/dev/null; chmod 644 favicon.ico 2>/dev/null; mkdir -p storage/app/public/images && cp public/images/*.png public/images/*.ico storage/app/public/images/ 2>/dev/null; chmod 644 storage/app/public/images/* 2>/dev/null; true &&

  echo \"---- Rebuilding caches...\" &&
  php artisan route:cache &&
  php artisan config:cache &&
  php artisan view:cache &&

  echo \"---- Deploy complete!\"
  '
"

echo ""
echo "==> Deploy complete! Asset version: $NEW_VERSION"
echo "    Users will now see fresh CSS/JS on next page load."
