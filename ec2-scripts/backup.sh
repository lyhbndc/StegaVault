#!/bin/bash
TIMESTAMP=$(date +%Y-%m-%d-%H-%M)
BACKUP_DIR="/opt/backups/${TIMESTAMP}"

mkdir -p "$BACKUP_DIR"
echo "Starting backup: ${TIMESTAMP}"

export PGPASSWORD="owlopsco432"
export PGSSLMODE=require

pg_dump \
  -h aws-1-ap-south-1.pooler.supabase.com \
  -p 5432 \
  -U postgres.iakongqdopzyvxhqfzvp \
  -d postgres \
  --no-owner \
  --no-acl \
  -Fc \
  -f "${BACKUP_DIR}/database.dump"

if [ $? -ne 0 ]; then
  echo "ERROR: pg_dump failed"
  rm -rf "$BACKUP_DIR"
  exit 1
fi

# --- DOCKER VOLUME BACKUP ---
if docker info > /dev/null 2>&1 && docker volume inspect php_db_data > /dev/null 2>&1; then
  docker run --rm \
    -v php_db_data:/source \
    -v "$BACKUP_DIR:/backup" \
    alpine \
    tar czf /backup/docker-volumes.tar.gz /source
  echo "Docker volume backup: ${BACKUP_DIR}/docker-volumes.tar.gz"
else
  echo "Skipping Docker volume backup (Docker unavailable or volume not found)"
fi

echo "Backup complete: ${BACKUP_DIR}"
