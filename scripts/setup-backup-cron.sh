#!/bin/bash
# =============================================================================
# setup-backup-cron.sh — Instala el cron de backup automático de LanzaTaxi
#
# Ejecutar como root:
#   sudo bash scripts/setup-backup-cron.sh
#
# Resultado: cron que ejecuta backup.sh todos los días a las 03:00
# =============================================================================

set -euo pipefail

[[ $EUID -ne 0 ]] && { echo "Ejecutar como root (sudo)"; exit 1; }

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKUP_SCRIPT="$SCRIPT_DIR/backup.sh"

# Asegurar que el script de backup tiene permisos de ejecución
chmod +x "$BACKUP_SCRIPT"

# Fichero de cron dedicado (más limpio que editar crontab directamente)
CRON_FILE="/etc/cron.d/lanzataxi-backup"

cat > "$CRON_FILE" << EOF
# LanzaTaxi — Backup automático diario a las 03:00
# Logs en /var/log/lanzataxi-backup.log
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin

# Backup completo cada día a las 03:00
0 3 * * * root $BACKUP_SCRIPT >> /var/log/lanzataxi-backup.log 2>&1

# Backup adicional cada domingo a las 02:00 (semanal de seguridad)
0 2 * * 0 root $BACKUP_SCRIPT >> /var/log/lanzataxi-backup.log 2>&1
EOF

chmod 644 "$CRON_FILE"

# Crear fichero de log con permisos correctos
touch /var/log/lanzataxi-backup.log
chmod 640 /var/log/lanzataxi-backup.log

# Logrotate para el log de backup
cat > /etc/logrotate.d/lanzataxi-backup << 'EOF'
/var/log/lanzataxi-backup.log {
    weekly
    rotate 12
    compress
    delaycompress
    missingok
    notifempty
    create 640 root root
}
EOF

echo ""
echo "Cron de backup instalado: $CRON_FILE"
echo "Horario: diario a las 03:00 y dominical a las 02:00"
echo "Log: /var/log/lanzataxi-backup.log"
echo ""
echo "Para probar el backup manualmente:"
echo "  sudo bash $BACKUP_SCRIPT"
echo ""
echo "Para ver el log:"
echo "  tail -f /var/log/lanzataxi-backup.log"
