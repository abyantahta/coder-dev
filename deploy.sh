#!/usr/bin/env bash
#
# deploy.sh — CODER production deploy
#
# Usage (run on the production server, in the app directory):
#   ./deploy.sh
#
# IMPORTANT — this app has real, live QAD-linked data (Purchase
# Requisitions/Orders tied to real records in QAD). This script must NEVER
# run `migrate:fresh`, `migrate:refresh`, `db:wipe`, or any other
# destructive command. Only additive `migrate --force`. Do not add
# destructive commands to this file.
#
# Assumes: git-based deploy on a Unix server, PHP 8.3+, Composer, Node/npm,
# MySQL, and that queue workers (used by app/Jobs/SyncQadItemsJob.php) run
# under a process manager (supervisor/systemd/etc.) that auto-restarts a
# worker after `queue:restart` tells it to stop. Adjust the marked sections
# below for your actual server setup before relying on this.

set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$APP_DIR"

BRANCH="${DEPLOY_BRANCH:-main}"
TIMESTAMP="$(date '+%Y-%m-%d %H:%M:%S')"

log() { echo -e "\n==> $1"; }

echo "=========================================="
echo " Deploying CODER — ${TIMESTAMP}"
echo " Branch: ${BRANCH}"
echo "=========================================="

# ── 0. Guard rails ───────────────────────────────────────────────────────
if [ "$(id -u)" -eq 0 ] && [ "${ALLOW_ROOT_DEPLOY:-0}" != "1" ]; then
    echo "Refusing to run as root (set ALLOW_ROOT_DEPLOY=1 to override)." >&2
    exit 1
fi

if [ ! -f artisan ]; then
    echo "artisan not found in ${APP_DIR} — is APP_DIR correct?" >&2
    exit 1
fi

if git status --porcelain | grep -q .; then
    echo "Working tree has uncommitted/local changes on the server:" >&2
    git status --short >&2
    echo "Refusing to deploy over them — commit, stash, or discard first." >&2
    exit 1
fi

# ── 1. Maintenance mode ──────────────────────────────────────────────────
# Comment this out (and the `php artisan up` at the end) if you deploy
# zero-downtime some other way (e.g. symlinked releases).
log "Enabling maintenance mode..."
php artisan down --retry=60 || true

# Always try to bring the app back up, even if a later step fails.
trap 'php artisan up || true' EXIT

# ── 2. Pull latest code ──────────────────────────────────────────────────
log "Pulling latest code (origin/${BRANCH})..."
git fetch origin "${BRANCH}"
git reset --hard "origin/${BRANCH}"

# ── 3. Backup the database before migrating ──────────────────────────────
# Best-effort — skips quietly if mysqldump/DB credentials aren't available
# rather than blocking the deploy, but a real backup here is strongly
# recommended given this app's live QAD-linked data.
BACKUP_DIR="${APP_DIR}/storage/backups"
mkdir -p "${BACKUP_DIR}"
if command -v mysqldump >/dev/null 2>&1 && [ -f .env ]; then
    DB_HOST=$(grep -E '^DB_HOST=' .env | cut -d '=' -f2- || true)
    DB_PORT=$(grep -E '^DB_PORT=' .env | cut -d '=' -f2- || true)
    DB_DATABASE=$(grep -E '^DB_DATABASE=' .env | cut -d '=' -f2- || true)
    DB_USERNAME=$(grep -E '^DB_USERNAME=' .env | cut -d '=' -f2- || true)
    DB_PASSWORD=$(grep -E '^DB_PASSWORD=' .env | cut -d '=' -f2- || true)
    if [ -n "${DB_DATABASE:-}" ]; then
        log "Backing up database (${DB_DATABASE})..."
        BACKUP_FILE="${BACKUP_DIR}/pre-deploy-$(date +%Y%m%d-%H%M%S).sql.gz"
        MYSQL_PWD="${DB_PASSWORD}" mysqldump \
            -h "${DB_HOST:-127.0.0.1}" -P "${DB_PORT:-3306}" -u "${DB_USERNAME:-root}" \
            "${DB_DATABASE}" | gzip > "${BACKUP_FILE}"
        echo "  saved to ${BACKUP_FILE}"
        # Keep the last 14 backups only.
        ls -1t "${BACKUP_DIR}"/pre-deploy-*.sql.gz 2>/dev/null | tail -n +15 | xargs -r rm --
    fi
else
    echo "  (mysqldump not available or no .env — skipping backup, proceed with care)"
fi

# ── 4. PHP dependencies ──────────────────────────────────────────────────
log "Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# ── 5. Frontend assets ───────────────────────────────────────────────────
log "Building frontend assets..."
npm ci
npm run build
npm run vendor:chart

# ── 6. Database migrations — ADDITIVE ONLY, see warning at top of file ──
log "Running migrations (additive only, --force)..."
php artisan migrate --force

# ── 7. Storage symlink (idempotent, safe to always run) ─────────────────
php artisan storage:link || true

# ── 8. Rebuild caches ─────────────────────────────────────────────────────
log "Rebuilding caches..."
php artisan config:clear
php artisan config:cache
php artisan route:clear
php artisan route:cache
php artisan view:clear
php artisan view:cache
php artisan event:cache

# ── 9. Restart queue workers so they pick up the new code ───────────────
# Signals running `queue:work` processes to finish their current job and
# exit — your process manager (supervisor/systemd/etc.) is expected to
# restart them. Adjust/replace with your own supervisor command if needed,
# e.g.: sudo supervisorctl restart coder-worker:*
log "Restarting queue workers..."
php artisan queue:restart

# ── 10. (Optional) reload PHP-FPM / web server ───────────────────────────
# Uncomment and adjust for your server if you rely on opcache and want a
# clean reload rather than waiting on opcache.revalidate_freq.
# sudo systemctl reload php8.3-fpm
# sudo systemctl reload nginx

echo -e "\n=========================================="
echo " Deploy finished — $(date '+%Y-%m-%d %H:%M:%S')"
echo "=========================================="
# `trap` above brings the app back out of maintenance mode here.
