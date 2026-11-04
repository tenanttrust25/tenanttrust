#!/usr/bin/env bash
# Push the local backup set to the tailnet desktop (off the server).
# Failure here is non-fatal: the local copy is already written.
set -Eeuo pipefail

TARGET=root@100.89.143.15
REMOTE_DIR=/root/backups/srv1740221

ssh -i /root/.ssh/backup_ed25519 -o BatchMode=yes -o StrictHostKeyChecking=accept-new \
  "${TARGET}" "mkdir -p ${REMOTE_DIR}"

rsync -a --delete --timeout=600 \
  -e "ssh -i /root/.ssh/backup_ed25519 -o BatchMode=yes -o StrictHostKeyChecking=accept-new" \
  /var/backups/tenanttrust/ "${TARGET}:${REMOTE_DIR}/"
