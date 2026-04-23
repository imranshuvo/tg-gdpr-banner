#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
MYSQL_BASEDIR="$(brew --prefix mysql)"
LOCAL_RUNTIME_DIR="${ROOT_DIR}/storage/mysql-local"
MYSQL_DATA_DIR="${LOCAL_RUNTIME_DIR}/data"
MYSQL_SOCKET="${LOCAL_RUNTIME_DIR}/mysql.sock"
MYSQL_PID_FILE="${LOCAL_RUNTIME_DIR}/mysql.pid"
MYSQL_LOG_FILE="${LOCAL_RUNTIME_DIR}/mysql.err"
API_PID_FILE="${LOCAL_RUNTIME_DIR}/api-server.pid"
API_LOG_FILE="${LOCAL_RUNTIME_DIR}/api-server.log"
API_PORT_FILE="${LOCAL_RUNTIME_DIR}/api-server.port"
VITE_MANIFEST_FILE="${ROOT_DIR}/public/build/manifest.json"

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3307}"
DB_DATABASE="${DB_DATABASE:-tg_gdpr_licensing_api_local}"
DB_USERNAME="${DB_USERNAME:-root}"
DB_PASSWORD="${DB_PASSWORD:-}"
APP_HOST="${APP_HOST:-127.0.0.1}"
APP_PORT="${APP_PORT:-}"
RUN_SMOKE_TEST="${RUN_SMOKE_TEST:-1}"
BUILD_ASSETS="${BUILD_ASSETS:-1}"

mysql_client=(mysql --protocol=tcp --host="$DB_HOST" --port="$DB_PORT" -u"$DB_USERNAME")
mysqladmin_client=(mysqladmin --protocol=tcp --host="$DB_HOST" --port="$DB_PORT" -u"$DB_USERNAME")

if [[ -n "$DB_PASSWORD" ]]; then
    mysql_client+=("-p${DB_PASSWORD}")
    mysqladmin_client+=("-p${DB_PASSWORD}")
fi

log() {
    printf '%s\n' "$*"
}

port_in_use() {
    lsof -nP -iTCP:"$1" -sTCP:LISTEN >/dev/null 2>&1
}

ensure_env() {
    if [[ -f "${ROOT_DIR}/.env" ]]; then
        return
    fi

    cp "${ROOT_DIR}/.env.example" "${ROOT_DIR}/.env"
    php "${ROOT_DIR}/artisan" key:generate --force >/dev/null
}

ensure_assets() {
    if [[ "$BUILD_ASSETS" != "1" ]]; then
        return
    fi

    if [[ -f "$VITE_MANIFEST_FILE" ]]; then
        return
    fi

    if ! command -v npm >/dev/null 2>&1; then
        printf 'npm is required to build Vite assets because %s is missing.\n' "$VITE_MANIFEST_FILE" >&2
        exit 1
    fi

    if [[ ! -d "${ROOT_DIR}/node_modules" ]]; then
        log "Installing frontend dependencies"
        (cd "$ROOT_DIR" && npm ci)
    fi

    log "Building Vite assets"
    (cd "$ROOT_DIR" && npm run build)
}

mysql_is_ready() {
    "${mysqladmin_client[@]}" ping >/dev/null 2>&1
}

initialize_mysql_data() {
    mkdir -p "$LOCAL_RUNTIME_DIR"

    if [[ -d "${MYSQL_DATA_DIR}/mysql" ]]; then
        return
    fi

    mysqld --initialize-insecure --datadir="$MYSQL_DATA_DIR" --basedir="$MYSQL_BASEDIR" >/dev/null 2>&1
}

start_mysql() {
    if mysql_is_ready; then
        log "Temporary MySQL already running on ${DB_HOST}:${DB_PORT}"
        return
    fi

    if port_in_use "$DB_PORT"; then
        printf 'Port %s is already in use and is not responding as the temporary local MySQL instance.\n' "$DB_PORT" >&2
        exit 1
    fi

    initialize_mysql_data

    mysqld \
        --datadir="$MYSQL_DATA_DIR" \
        --basedir="$MYSQL_BASEDIR" \
        --port="$DB_PORT" \
        --socket="$MYSQL_SOCKET" \
        --pid-file="$MYSQL_PID_FILE" \
        --bind-address="$DB_HOST" \
        --skip-networking=0 \
        --mysqlx=0 \
        --log-error="$MYSQL_LOG_FILE" \
        --daemonize

    "${mysqladmin_client[@]}" --wait=30 ping >/dev/null
    log "Temporary MySQL started on ${DB_HOST}:${DB_PORT}"
}

stop_mysql() {
    if mysql_is_ready; then
        "${mysqladmin_client[@]}" shutdown >/dev/null
        log "Temporary MySQL stopped"
    fi
}

pick_app_port() {
    local port

    if [[ -n "$APP_PORT" ]]; then
        printf '%s\n' "$APP_PORT"
        return
    fi

    port=8000
    while port_in_use "$port"; do
        port=$((port + 1))
    done

    printf '%s\n' "$port"
}

run_artisan() {
    local app_url="http://${APP_HOST}:${APP_PORT}"

    env \
        APP_URL="$app_url" \
        DB_CONNECTION=mysql \
        DB_HOST="$DB_HOST" \
        DB_PORT="$DB_PORT" \
        DB_DATABASE="$DB_DATABASE" \
        DB_USERNAME="$DB_USERNAME" \
        DB_PASSWORD="$DB_PASSWORD" \
        php "${ROOT_DIR}/artisan" "$@"
}

prepare_database() {
    "${mysql_client[@]}" -e "CREATE DATABASE IF NOT EXISTS \`${DB_DATABASE}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    run_artisan migrate:fresh --force
    run_artisan db:seed --class=LicenseSeeder --force
}

stop_api() {
    if [[ ! -f "$API_PID_FILE" ]]; then
        return
    fi

    local pid
    pid="$(cat "$API_PID_FILE")"

    if kill -0 "$pid" >/dev/null 2>&1; then
        kill "$pid" >/dev/null 2>&1 || true
    fi

    rm -f "$API_PID_FILE" "$API_PORT_FILE"
}

start_api() {
    APP_PORT="$(pick_app_port)"
    mkdir -p "$LOCAL_RUNTIME_DIR"
    stop_api

    if port_in_use "$APP_PORT"; then
        printf 'Port %s is already in use. Set APP_PORT to another value and retry.\n' "$APP_PORT" >&2
        exit 1
    fi

    env \
        APP_URL="http://${APP_HOST}:${APP_PORT}" \
        DB_CONNECTION=mysql \
        DB_HOST="$DB_HOST" \
        DB_PORT="$DB_PORT" \
        DB_DATABASE="$DB_DATABASE" \
        DB_USERNAME="$DB_USERNAME" \
        DB_PASSWORD="$DB_PASSWORD" \
        php "${ROOT_DIR}/artisan" serve --no-reload --host="$APP_HOST" --port="$APP_PORT" >"$API_LOG_FILE" 2>&1 &

    echo $! > "$API_PID_FILE"
    printf '%s\n' "$APP_PORT" > "$API_PORT_FILE"

    if ! curl --silent --output /dev/null --retry 20 --retry-all-errors --retry-connrefused --fail "http://${APP_HOST}:${APP_PORT}/up"; then
        tail -n 50 "$API_LOG_FILE" >&2 || true
        printf 'Failed to start licensing API on http://%s:%s\n' "$APP_HOST" "$APP_PORT" >&2
        exit 1
    fi

    log "Licensing API started on http://${APP_HOST}:${APP_PORT}"
}

load_api_port() {
    if [[ -n "$APP_PORT" ]]; then
        return
    fi

    if [[ -f "$API_PORT_FILE" ]]; then
        APP_PORT="$(cat "$API_PORT_FILE")"
        return
    fi

    APP_PORT=8000
}

run_smoke_test() {
    load_api_port

    env \
        APP_URL="http://${APP_HOST}:${APP_PORT}" \
        DB_CONNECTION=mysql \
        DB_HOST="$DB_HOST" \
        DB_PORT="$DB_PORT" \
        DB_DATABASE="$DB_DATABASE" \
        DB_USERNAME="$DB_USERNAME" \
        DB_PASSWORD="$DB_PASSWORD" \
        BASE_URL="http://${APP_HOST}:${APP_PORT}/api/v1" \
        ARTISAN="${ROOT_DIR}/artisan" \
        bash "${ROOT_DIR}/test-api.sh"
}

print_summary() {
    log "Local licensing API bootstrap is ready."
    log "API URL: http://${APP_HOST}:${APP_PORT}"
    log "Temporary MySQL: ${DB_HOST}:${DB_PORT}/${DB_DATABASE}"
    log "Runtime artifacts: ${LOCAL_RUNTIME_DIR}"
    log "Rerun smoke test: bash ${ROOT_DIR}/scripts/local-bootstrap.sh smoke-test"
    log "Stop local services: bash ${ROOT_DIR}/scripts/local-bootstrap.sh down"
}

up() {
    ensure_env
    ensure_assets
    start_mysql
    APP_PORT="$(pick_app_port)"
    prepare_database
    start_api

    if [[ "$RUN_SMOKE_TEST" == "1" ]]; then
        run_smoke_test
    fi

    print_summary
}

down() {
    stop_api
    stop_mysql
}

case "${1:-up}" in
    up)
        up
        ;;
    smoke-test)
        run_smoke_test
        ;;
    down)
        down
        ;;
    *)
        printf 'Usage: %s [up|smoke-test|down]\n' "${BASH_SOURCE[0]}" >&2
        exit 1
        ;;
esac