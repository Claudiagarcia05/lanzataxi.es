#!/bin/bash
# =============================================================================
# backup.sh — Copia de seguridad automatizada de LanzaTaxi
#
# Qué hace:
#   1. Volcado de la base de datos (mysqldump)
#   2. Compresión de storage y .env
#   3. Rotación local (elimina copias con más de 7 días)
#   4. Copia remota mediante rsync (SSH)
#
# Configurar en cron (como root):
#   sudo bash scripts/setup-backup-cron.sh
#
# Variables de entorno opcionales (se leen del .env de la app):
#   BACKUP_REMOTE_HOST  — IP/hostname del servidor remoto (p.ej. backup@192.168.1.10)
#   BACKUP_REMOTE_DIR   — Ruta en el servidor remoto (p.ej. /backups/lanzataxi)
#   BACKUP_REMOTE_KEY   — Clave SSH privada para el servidor remoto
# =============================================================================

set -euo pipefail

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/lanzataxi"
APP_DIR="/var/www/lanzataxi/current"
LOG_FILE="/var/log/lanzataxi-backup.log"

# ── Función de log ────────────────────────────────────────────────────────────
log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$LOG_FILE"; }

log "====== Inicio de backup ======"

# ── Cargar variables del .env de la app ──────────────────────────────────────
if [ -f "$APP_DIR/.env" ]; then
  set -a
  # shellcheck disable=SC1091
  . "$APP_DIR/.env"
  set +a
fi

DB_NAME="${DB_DATABASE:-lanzataxi_db}"
DB_USER="${DB_USERNAME:-lanzataxi_user}"
DB_PASS="${DB_PASSWORD:-}"

# Variables de backup remoto (pueden venir del .env o definirse aquí)
REMOTE_HOST="${BACKUP_REMOTE_HOST:-}"
REMOTE_DIR="${BACKUP_REMOTE_DIR:-/backups/lanzataxi}"
REMOTE_KEY="${BACKUP_REMOTE_KEY:-/root/.ssh/backup_key}"

mkdir -p "$BACKUP_DIR"

# ── 1. Volcado de base de datos ───────────────────────────────────────────────
log "Volcando base de datos '$DB_NAME'..."
DB_FILE="$BACKUP_DIR/db_$DATE.sql.gz"

if [ -n "$DB_PASS" ]; then
  mysqldump \
    --single-transaction \
    --routines \
    --triggers \
    -u "$DB_USER" -p"$DB_PASS" \
    "$DB_NAME" | gzip > "$DB_FILE"
else
  mysqldump \
    --single-transaction \
    --routines \
    --triggers \
    -u "$DB_USER" \
    "$DB_NAME" | gzip > "$DB_FILE"
fi
log "BD volcada: $DB_FILE ($(du -sh "$DB_FILE" | cut -f1))"

# ── 2. Comprimir storage y .env ──────────────────────────────────────────────
log "Comprimiendo ficheros de la app..."
FILES_FILE="$BACKUP_DIR/files_$DATE.tar.gz"
tar -czf "$FILES_FILE" \
  --warning=no-file-changed \
  "$APP_DIR/storage/app" \
  "$APP_DIR/.env" \
  2>/dev/null || true
log "Ficheros comprimidos: $FILES_FILE ($(du -sh "$FILES_FILE" | cut -f1))"

# ── 3. Verificar integridad del backup ────────────────────────────────────────
log "Verificando integridad..."
if gzip -t "$DB_FILE" 2>/dev/null; then
  log "BD OK"
else
  log "ERROR: El volcado de BD está corrupto"
  exit 1
fi

if tar -tzf "$FILES_FILE" > /dev/null 2>&1; then
  log "Ficheros OK"
else
  log "ERROR: El tar de ficheros está corrupto"
  exit 1
fi

# ── 4. Copia remota (rsync sobre SSH) ─────────────────────────────────────────
if [ -n "$REMOTE_HOST" ]; then
  log "Enviando backup al servidor remoto $REMOTE_HOST:$REMOTE_DIR ..."

  SSH_OPTS="-o StrictHostKeyChecking=no -o ConnectTimeout=30"
  if [ -f "$REMOTE_KEY" ]; then
    SSH_OPTS="$SSH_OPTS -i $REMOTE_KEY"
  fi

  # Crear directorio remoto si no existe
  ssh $SSH_OPTS "$REMOTE_HOST" "mkdir -p '$REMOTE_DIR'" 2>/dev/null || true

  # Sincronizar solo los ficheros nuevos
  rsync -az \
    --no-perms \
    -e "ssh $SSH_OPTS" \
    "$DB_FILE" "$FILES_FILE" \
    "$REMOTE_HOST:$REMOTE_DIR/" \
    && log "Backup remoto completado" \
    || log "ADVERTENCIA: No se pudo completar el backup remoto (el local sigue disponible)"
else
  log "BACKUP_REMOTE_HOST no configurado — solo se guarda copia local"
  log "Añade BACKUP_REMOTE_HOST=usuario@ip en el .env o en este script para activar el backup remoto."
fi

# ── 5. Rotación: eliminar copias locales de más de 7 días ────────────────────
log "Limpiando copias locales de más de 7 días..."
DELETED=$(find "$BACKUP_DIR" -type f \( -name "db_*.sql.gz" -o -name "files_*.tar.gz" \) -mtime +7 -print -delete | wc -l)
log "Eliminados: $DELETED fichero(s) antiguos"

# Tamaño total del directorio de backups
TOTAL_SIZE=$(du -sh "$BACKUP_DIR" | cut -f1)
log "Tamaño total del directorio de backups: $TOTAL_SIZE"

log "====== Backup completado ======"
