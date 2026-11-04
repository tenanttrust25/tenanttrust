#!/usr/bin/env bash
# TenantTrust nightly backup: MySQL dump + .env copy + document mirror,
# with local retention and an off-box push to the tailnet desktop.
set -Eeuo pipefail

APP=/var/www/tenanttrust
DEST=/var/backups/tenanttrust
KEEP_DB_DAYS=14
TS=$(date +%Y%m%d-%H%M%S)
HOST=$(hostname -s)

mkdir -p "$DEST/db" "$DEST/config" "$DEST/docs"

# --- credentials, read into vars, never printed -----------------------------
DB=$(grep -m1 '^DB_DATABASE=' "$APP/.env" | cut -d= -f2- | tr -d '"')
DBU=$(grep -m1 '^DB_USERNAME=' "$APP/.env" | cut -d= -f2- | tr -d '"')
DBP=$(grep -m1 '^DB_PASSWORD=' "$APP/.env" | cut -d= -f2- | tr -d '"')
DBH=$(grep -m1 '^DB_HOST=' "$APP/.env" | cut -d= -f2- | tr -d '"')
DBH=${DBH:-127.0.0.1}
[ -n "${DB:-}" ] && [ -n "${DBU:-}" ] || { echo "cannot read DB credentials"; exit 1; }

# --- database ---------------------------------------------------------------
OUT="$DEST/db/${DB}-${TS}.sql.gz"
MYSQL_PWD="$DBP" mysqldump -h"$DBH" -u"$DBU" \
  --single-transaction --quick --routines --triggers --events \
  --no-tablespaces --default-character-set=utf8mb4 "$DB" | gzip -9 > "$OUT"

gunzip -t "$OUT" || { echo "FATAL: dump is corrupt: $OUT"; exit 1; }
SIZE=$(stat -c%s "$OUT")
TABLES=$(zcat "$OUT" | grep -c '^CREATE TABLE' || true)
INSERTS=$(zcat "$OUT" | grep -c '^INSERT INTO' || true)
echo "$(date -Iseconds) host=$HOST db=$DB bytes=$SIZE tables=$TABLES insert_lines=$INSERTS file=$(basename "$OUT")" >> "$DEST/backup.log"

# Sanity: absolute floor plus a comparison against the previous good dump
# (mysqldump writes one extended INSERT per table, so "28 INSERT lines" for
# 36 tables is normal — row volume is not measurable from line count).
PREV=$(find "$DEST/db" -name '*.sql.gz' ! -newer "$OUT" -printf '%s\n' 2>/dev/null | tail -1)
if [ "$SIZE" -lt 50000 ] || [ "$TABLES" -lt 30 ]; then
  echo "FATAL: dump looks wrong (bytes=$SIZE tables=$TABLES)" >&2
  exit 1
fi
if [ -n "$PREV" ] && [ "$PREV" -gt 0 ] && [ "$SIZE" -lt $((PREV / 2)) ]; then
  echo "FATAL: dump shrank from $PREV to $SIZE bytes" >&2
  exit 1
fi

# --- app config (secrets: root-only) ---------------------------------------
tar czf "$DEST/config/env-${TS}.tar.gz" -C "$APP" .env 2>/dev/null || true
chmod 600 "$DEST/config/"*.tar.gz 2>/dev/null || true
find "$DEST/config" -name 'env-*.tar.gz' -mtime +30 -delete

# --- documents mirror (incremental) ---------------------------------------
if [ -d "$APP/storage/app/documents" ]; then
  rsync -a --delete "$APP/storage/app/documents/" "$DEST/docs/"
fi

# --- retention -------------------------------------------------------------
find "$DEST/db" -name '*.sql.gz' -mtime +"$KEEP_DB_DAYS" -delete

# --- off-box ---------------------------------------------------------------
if [ -x /usr/local/bin/tenanttrust-backup-push.sh ]; then
  /usr/local/bin/tenanttrust-backup-push.sh || echo "WARN: off-box push failed (local backup is intact)"
fi

echo "$(date -Iseconds) backup complete: $OUT"
