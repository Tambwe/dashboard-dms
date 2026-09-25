#!/usr/bin/env bash
set -euo pipefail

DOMAIN="${DOMAIN:-dashboard-dms.48-222-144-8.sslip.io}"
PUBLIC_IP="${PUBLIC_IP:-48.222.144.8}"
REPO_URL="${REPO_URL:-https://github.com/Tambwe/dashboard-dms.git}"
APP_DIR="${APP_DIR:-/home/ubuntu/dashboard-dms}"

if [ "$(id -u)" -ne 0 ]; then
  echo "Run this script with sudo or as root." >&2
  exit 1
fi

export DEBIAN_FRONTEND=noninteractive

wait_for_apt() {
  while fuser /var/lib/dpkg/lock-frontend >/dev/null 2>&1 || \
        fuser /var/lib/dpkg/lock >/dev/null 2>&1 || \
        fuser /var/lib/apt/lists/lock >/dev/null 2>&1; do
    echo "Waiting for apt lock..."
    sleep 5
  done
}

disable_stale_apt_proxy() {
  unset http_proxy https_proxy HTTP_PROXY HTTPS_PROXY

  if apt-config dump | grep -q '10\.7\.37\.44:3128'; then
    echo "Disabling stale apt proxy 10.7.37.44:3128..."
    cat > /etc/apt/apt.conf.d/99dashboard-dms-no-proxy <<'EOF'
Acquire::http::Proxy "false";
Acquire::https::Proxy "false";
EOF
  fi
}

set_env() {
  local key="$1"
  local value="$2"
  local file=".env.production"
  local tmp
  tmp="$(mktemp)"
  if [ -f "$file" ]; then
    grep -v "^${key}=" "$file" > "$tmp" || true
  fi
  printf '%s=%s\n' "$key" "$value" >> "$tmp"
  mv "$tmp" "$file"
}

ensure_secret() {
  local key="$1"
  local value="$2"
  local current=""
  if [ -f .env.production ]; then
    current="$(grep "^${key}=" .env.production 2>/dev/null | tail -n 1 | cut -d= -f2- || true)"
  fi
  if [ -z "$current" ] || [ "$current" = "null" ]; then
    set_env "$key" "$value"
  fi
}

echo "== Installing prerequisites =="
disable_stale_apt_proxy
wait_for_apt
apt-get update
wait_for_apt
apt-get install -y ca-certificates curl gnupg lsb-release git openssl

echo "== Installing Docker =="
install -m 0755 -d /etc/apt/keyrings
if [ ! -f /etc/apt/keyrings/docker.asc ]; then
  curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
  chmod a+r /etc/apt/keyrings/docker.asc
fi

. /etc/os-release
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu ${VERSION_CODENAME} stable" > /etc/apt/sources.list.d/docker.list

wait_for_apt
apt-get update
wait_for_apt
if ! apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin; then
  echo "Official Docker packages are unavailable. Falling back to Ubuntu docker.io packages..."
  wait_for_apt
  apt-get install -y docker.io docker-compose-v2
fi
systemctl enable --now docker

docker --version
docker compose version

echo "== Fetching application =="
if [ ! -d "$APP_DIR/.git" ]; then
  rm -rf "$APP_DIR"
  git clone "$REPO_URL" "$APP_DIR"
fi

cd "$APP_DIR"
git pull --ff-only origin main

echo "== Configuring production environment =="
if [ ! -f .env.production ]; then
  cp .env.example .env.production
fi

set_env APP_NAME "Dashboard DMS"
set_env APP_ENV production
set_env APP_DEBUG false
set_env APP_URL "https://${DOMAIN}"
set_env APP_DOMAIN "${DOMAIN}"
set_env LOG_CHANNEL stderr
set_env LOG_LEVEL error
set_env DB_CONNECTION mysql
set_env DB_HOST db
set_env DB_PORT 3306
set_env DB_DATABASE dashboard_dms
set_env DB_USERNAME dashboard_dms
set_env CACHE_DRIVER file
set_env SESSION_DRIVER file
set_env QUEUE_CONNECTION sync
set_env FILESYSTEM_DISK local

ensure_secret DB_PASSWORD "$(openssl rand -hex 16)"
ensure_secret DB_ROOT_PASSWORD "$(openssl rand -hex 24)"
if ! grep -q '^APP_KEY=base64:' .env.production; then
  set_env APP_KEY "base64:$(openssl rand -base64 32)"
fi

echo "== Opening local firewall if ufw is enabled =="
if command -v ufw >/dev/null 2>&1; then
  ufw allow 80/tcp || true
  ufw allow 443/tcp || true
  ufw status || true
fi

echo "== Building and starting containers =="
docker compose -f docker-compose.production.yml --env-file .env.production down --remove-orphans || true
docker compose -f docker-compose.production.yml --env-file .env.production up -d --build

echo "== Waiting for services =="
sleep 45

echo "== Initializing Laravel =="
docker compose -f docker-compose.production.yml --env-file .env.production exec -T app php artisan config:clear
docker compose -f docker-compose.production.yml --env-file .env.production exec -T app php artisan migrate --force
docker compose -f docker-compose.production.yml --env-file .env.production exec -T app php artisan config:cache
docker compose -f docker-compose.production.yml --env-file .env.production exec -T app php artisan route:cache
docker compose -f docker-compose.production.yml --env-file .env.production exec -T app php artisan view:cache

echo "== Container status =="
docker compose -f docker-compose.production.yml --env-file .env.production ps

echo "== HTTP checks =="
curl -I "http://127.0.0.1/" || true
curl -I "http://${PUBLIC_IP}/" || true
curl -I "http://${DOMAIN}/" || true
curl -I "https://${DOMAIN}/" || true

echo "== Recent Caddy logs =="
docker compose -f docker-compose.production.yml --env-file .env.production logs --tail=120 caddy

echo
echo "Deployment completed."
echo "Open: http://${PUBLIC_IP}/"
echo "Open: https://${DOMAIN}/"
