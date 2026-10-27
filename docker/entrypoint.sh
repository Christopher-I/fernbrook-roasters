#!/bin/sh
set -e

APP_DIR="/var/www/html"
PERSIST_DIR="${PERSIST_DIR:-/mnt/persist}"
PORT="${PORT:-8080}"

log() { echo "[entrypoint] $*"; }

# ---------------------------------------------------------------------------
# 1. Render the nginx vhost. Railway assigns $PORT at runtime, so the listen
#    directive cannot be baked into the image.
# ---------------------------------------------------------------------------
export PORT
mkdir -p /etc/nginx/conf.d
envsubst '${PORT}' \
  < /etc/nginx/templates/default.conf.tmpl \
  > /etc/nginx/conf.d/default.conf
log "nginx will listen on ${PORT}"

mkdir -p /tmp/nginx-client-body /tmp/nginx-proxy /tmp/nginx-fastcgi \
         /tmp/nginx-uwsgi /tmp/nginx-scgi
chown -R www-data:www-data /tmp/nginx-* 2>/dev/null || true

# ---------------------------------------------------------------------------
# 2. Persistent storage.
#
#    Railway allows exactly one volume per service, so a single volume is
#    mounted at $PERSIST_DIR and the two directories Craft must keep between
#    deploys are symlinked into it:
#
#      storage/    compiled templates, logs, runtime caches, the license key
#      web/uploads uploaded assets
#
#    Volumes mount as root, so they are chowned to www-data here. This runs in
#    the start command, not a pre-deploy hook, because Railway does not mount
#    volumes during pre-deploy.
# ---------------------------------------------------------------------------
if [ -d "$PERSIST_DIR" ]; then
  log "persistent volume found at ${PERSIST_DIR}"

  mkdir -p "${PERSIST_DIR}/storage/runtime" \
           "${PERSIST_DIR}/storage/logs" \
           "${PERSIST_DIR}/uploads"

  # Seed the volume from the image on first boot so Craft's expected
  # subdirectories exist.
  if [ -d "${APP_DIR}/storage" ] && [ ! -L "${APP_DIR}/storage" ]; then
    cp -rn "${APP_DIR}/storage/." "${PERSIST_DIR}/storage/" 2>/dev/null || true
    rm -rf "${APP_DIR}/storage"
  fi
  ln -sfn "${PERSIST_DIR}/storage" "${APP_DIR}/storage"

  rm -rf "${APP_DIR}/web/uploads"
  ln -sfn "${PERSIST_DIR}/uploads" "${APP_DIR}/web/uploads"

  chown -R www-data:www-data "$PERSIST_DIR"
else
  log "no volume at ${PERSIST_DIR}; using ephemeral in-image storage"
  mkdir -p "${APP_DIR}/storage/runtime" "${APP_DIR}/storage/logs" \
           "${APP_DIR}/web/uploads"
  chown -R www-data:www-data "${APP_DIR}/storage" "${APP_DIR}/web/uploads"
fi

# ---------------------------------------------------------------------------
# 3. Wait for the database. Railway starts services in parallel.
# ---------------------------------------------------------------------------
if [ -n "${CRAFT_DB_SERVER:-}" ]; then
  log "waiting for database at ${CRAFT_DB_SERVER}:${CRAFT_DB_PORT:-3306}"
  i=0
  until php -r '
      $dsn = sprintf("mysql:host=%s;port=%s",
          getenv("CRAFT_DB_SERVER"), getenv("CRAFT_DB_PORT") ?: 3306);
      try { new PDO($dsn, getenv("CRAFT_DB_USER"), getenv("CRAFT_DB_PASSWORD")); }
      catch (Throwable $e) { exit(1); }
  ' 2>/dev/null; do
    i=$((i + 1))
    if [ "$i" -ge 60 ]; then
      log "database did not become reachable in 60s; continuing anyway"
      break
    fi
    sleep 1
  done
fi

# ---------------------------------------------------------------------------
# 4. Install Craft on the very first boot, then apply migrations and project
#    config on every boot. `craft up` is the one command that has to run after
#    each deploy; see MAINTENANCE.md.
# ---------------------------------------------------------------------------
run_as_app() { su -s /bin/sh www-data -c "$*"; }

if [ "${CRAFT_SKIP_BOOTSTRAP:-0}" != "1" ]; then
  if run_as_app "php ${APP_DIR}/craft install/check" >/dev/null 2>&1; then
    log "Craft is installed; running craft up"
    run_as_app "php ${APP_DIR}/craft up --interactive=0" || \
      log "craft up failed; check the deploy logs"
  else
    log "Craft is not installed; running craft install"
    run_as_app "php ${APP_DIR}/craft install \
      --interactive=0 \
      --username='${CRAFT_ADMIN_USERNAME:-admin}' \
      --email='${CRAFT_ADMIN_EMAIL:-admin@example.com}' \
      --password='${CRAFT_ADMIN_PASSWORD:-}' \
      --site-name='${CRAFT_SITE_NAME:-Site}' \
      --site-url='${PRIMARY_SITE_URL}' \
      --language='${CRAFT_SITE_LANGUAGE:-en-GB}'" || \
        log "craft install failed; check the deploy logs"

    run_as_app "php ${APP_DIR}/craft up --interactive=0" || true

    # First boot of an empty database. Load the demo content so the site is
    # not an empty shell; set CRAFT_SEED_ON_INSTALL=0 to skip it.
    if [ "${CRAFT_SEED_ON_INSTALL:-1}" = "1" ]; then
      log "seeding demo content"
      run_as_app "php ${APP_DIR}/craft fernbrook/seed/run" || \
        log "seeding failed; check the deploy logs"
    fi
  fi

  run_as_app "php ${APP_DIR}/craft clear-caches/compiled-templates" >/dev/null 2>&1 || true
fi

log "starting supervisord"
exec "$@"
