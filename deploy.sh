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
sed -i '' "s/'version' => env('APP_VERSION', '[^']*')/'version' => env('APP_VERSION', '$NEW_VERSION')/" config/app.php
echo "    Version bumped to: $NEW_VERSION"

echo "==> Committing changes..."
git add -A
git commit -m "$COMMIT_MSG" || echo "    Nothing to commit"

echo "==> Pushing to GitHub..."
git push origin main

echo "==> Deploying to server..."
ssh -i "$SSH_KEY" "$REMOTE" "
  cd $REMOTE_DIR &&

  echo '---- Pulling latest code...' &&
  git pull origin main &&

  echo '---- Installing composer dependencies...' &&
  composer install --no-dev --optimize-autoloader --no-interaction 2>&1 &&

  echo '---- Clearing ALL Laravel caches...' &&
  php artisan view:clear &&
  php artisan route:clear &&
  php artisan config:clear &&
  php artisan cache:clear &&
  php artisan event:clear &&

  echo '---- Rebuilding caches...' &&
  php artisan route:cache &&
  php artisan config:cache &&
  php artisan view:cache &&

  echo '---- Restarting PHP-FPM...' &&
  sudo systemctl reload php8.2-fpm || sudo service php8.2-fpm restart || echo '    php-fpm restart skipped' &&

  echo '---- Flushing nginx cache (if any)...' &&
  sudo nginx -s reload 2>/dev/null || echo '    nginx reload skipped' &&

  echo '---- Done! Site should be live with fresh assets.'
"

echo ""
echo "==> Deploy complete! Asset version: $NEW_VERSION"
echo "    Users will now see fresh CSS/JS on next page load."
