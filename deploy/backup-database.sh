#!/usr/bin/env bash

set -euo pipefail

project_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
backup_dir="${project_dir}/backups"
timestamp="$(date -u +%Y%m%dT%H%M%SZ)"
temporary_file="${backup_dir}/dashboard-dms-${timestamp}.sql.gz.tmp"
backup_file="${temporary_file%.tmp}"

install -d -m 700 "${backup_dir}"
cd "${project_dir}"

docker compose -f docker-compose.production.yml exec -T db \
    sh -c 'mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction --quick --routines --triggers --events dashboard_dms' \
    | gzip > "${temporary_file}"

mv "${temporary_file}" "${backup_file}"
find "${backup_dir}" -type f -name 'dashboard-dms-*.sql.gz' -mtime +14 -delete
