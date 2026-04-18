#!/bin/bash
DUMP_FILE="$1"

if [ -z "$DUMP_FILE" ]; then
  echo "Usage: $0 <path/to/database.dump>"
  exit 1
fi

if [ ! -f "$DUMP_FILE" ]; then
  echo "ERROR: File not found: $DUMP_FILE"
  exit 1
fi

export PGPASSWORD="owlopsco432"
export PGSSLMODE=require

pg_restore \
  -h aws-1-ap-south-1.pooler.supabase.com \
  -p 5432 \
  -U postgres.iakongqdopzyvxhqfzvp \
  -d postgres \
  --no-owner \
  --no-acl \
  -c \
  "$DUMP_FILE"

if [ $? -ne 0 ]; then
  echo "ERROR: pg_restore failed"
  exit 1
fi

echo "Restore complete"
