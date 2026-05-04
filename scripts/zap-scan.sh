#!/bin/bash
# =============================================================================
# zap-scan.sh — Análisis de seguridad OWASP ZAP contra lanzataxi.es
#
# Usa OWASP ZAP en modo Docker (sin interfaz gráfica).
# Genera un informe HTML con las vulnerabilidades detectadas.
#
# Requisitos:
#   - Docker instalado en el servidor: sudo apt-get install docker.io
#   - Acceso a Internet (para descargar la imagen de ZAP si no está en local)
#
# Uso:
#   sudo bash scripts/zap-scan.sh [--target URL] [--output DIR]
#
# Ejemplos:
#   sudo bash scripts/zap-scan.sh
#   sudo bash scripts/zap-scan.sh --target https://lanzataxi.es --output /tmp/zap
#
# El informe HTML se guarda en:
#   storage/exports/zap-report-<fecha>.html
# =============================================================================

set -euo pipefail

# ── Valores por defecto ────────────────────────────────────────────────────────
TARGET="https://lanzataxi.es"
REPORT_DIR="/var/www/lanzataxi/current/storage/exports"
ZAP_IMAGE="ghcr.io/zaproxy/zaproxy:stable"
DATE=$(date +%Y%m%d_%H%M%S)
REPORT_FILE="zap-report-$DATE.html"
REPORT_JSON="zap-report-$DATE.json"

# ── Parsear argumentos ────────────────────────────────────────────────────────
while [[ $# -gt 0 ]]; do
  case $1 in
    --target)  TARGET="$2";     shift 2 ;;
    --output)  REPORT_DIR="$2"; shift 2 ;;
    *) echo "Argumento desconocido: $1"; exit 1 ;;
  esac
done

# ── Comprobaciones previas ─────────────────────────────────────────────────────
if ! command -v docker &> /dev/null; then
  echo "ERROR: Docker no está instalado."
  echo "Instalar con: sudo apt-get install -y docker.io"
  exit 1
fi

if ! docker info &> /dev/null; then
  echo "ERROR: El servicio Docker no está en ejecución."
  echo "Iniciar con: sudo systemctl start docker"
  exit 1
fi

mkdir -p "$REPORT_DIR"

echo "============================================="
echo " OWASP ZAP — Análisis de seguridad"
echo " Target:  $TARGET"
echo " Informe: $REPORT_DIR/$REPORT_FILE"
echo " Fecha:   $DATE"
echo "============================================="
echo ""

# ── Descargar imagen si no está disponible localmente ─────────────────────────
if ! docker image inspect "$ZAP_IMAGE" &> /dev/null; then
  echo "Descargando imagen ZAP ($ZAP_IMAGE)..."
  docker pull "$ZAP_IMAGE"
fi

# ── Ejecutar ZAP Baseline Scan ────────────────────────────────────────────────
# El "baseline scan" realiza:
#   - Spider pasivo (no envía datos de formularios)
#   - Análisis pasivo de respuestas (no es intrusivo)
#   - Ideal para uso en CI/CD y entornos de producción
#
# Para un análisis más profundo (activo), usar zap-full-scan.py
# ADVERTENCIA: El scan activo puede generar carga en el servidor y enviar datos.

echo "Iniciando escaneo baseline (pasivo)..."
echo ""

# ZAP escribe los ficheros en /zap/wrk/ dentro del contenedor.
# Los montamos sobre $REPORT_DIR del host.
docker run --rm \
  -v "$REPORT_DIR:/zap/wrk/:rw" \
  "$ZAP_IMAGE" \
  zap-baseline.py \
    -t "$TARGET" \
    -r "$REPORT_FILE" \
    -J "$REPORT_JSON" \
    -I \
    --auto \
  || true  # ZAP devuelve código ≠ 0 si encuentra alertas; no queremos abortar el script

echo ""
echo "============================================="
echo " Escaneo completado"
echo "============================================="
echo ""

# ── Mostrar resumen ───────────────────────────────────────────────────────────
if [ -f "$REPORT_DIR/$REPORT_JSON" ]; then
  echo "Resumen de alertas detectadas:"
  echo ""

  # Extraer resumen con python3 (disponible en Debian 12 por defecto)
  python3 - <<EOF
import json, sys

try:
    with open("$REPORT_DIR/$REPORT_JSON") as f:
        report = json.load(f)

    sites = report.get("site", [])
    totals = {"High": 0, "Medium": 0, "Low": 0, "Informational": 0}

    for site in sites:
        for alert in site.get("alerts", []):
            risk = alert.get("riskdesc", "Unknown").split(" ")[0]
            if risk in totals:
                totals[risk] += 1
            print(f"  [{alert.get('riskdesc','?'):25s}] {alert.get('name','?')}")

    print("")
    print("  Totales:")
    for level, count in totals.items():
        print(f"    {level:15s}: {count}")
except Exception as e:
    print(f"  (No se pudo parsear el JSON: {e})")
EOF
fi

echo ""
echo "Informe HTML completo:"
echo "  $REPORT_DIR/$REPORT_FILE"
echo ""
echo "Para ver el informe en el navegador (desde el VPS con port-forward):"
echo "  python3 -m http.server 8080 --directory $REPORT_DIR"
echo "  Luego accede a: http://localhost:8080/$REPORT_FILE"
echo ""

# ── Vulnerabilidades OWASP Top 10 más comunes a revisar ───────────────────────
cat << 'TIPS'
=============================================================
 Elementos más frecuentes en proyectos Laravel:
=============================================================

 A01 - Broken Access Control
   → Verificar que /api/admin/* requiere rol admin (✓ en routes/api.php)
   → Verificar que IDs no son predecibles (usar UUIDs o autorizar por política)

 A02 - Cryptographic Failures
   → HTTPS activo con TLS 1.2/1.3 (✓ config/nginx/lanzataxi.es.conf)
   → Verificar que SESSION_DRIVER=database o redis (no cookie sin cifrado)
   → Verificar que APP_KEY está generada (php artisan key:generate)

 A03 - Injection
   → Eloquent ORM usa consultas preparadas por defecto (✓)
   → Verificar que no hay ::whereRaw() con input de usuario sin sanitizar

 A05 - Security Misconfiguration
   → APP_DEBUG=false en .env de producción
   → APP_ENV=production en .env de producción
   → HTTPS forzado en config/session.php: 'secure' => true

 A07 - Identification and Authentication Failures
   → Rate limiting en /api/login (✓ routes/api.php throttle:5,1)
   → fail2ban configurado (✓ scripts/hardening.sh)

 A09 - Security Logging and Monitoring Failures
   → Logs de Nginx configurados (✓ config/nginx/lanzataxi.es.conf)
   → fail2ban monitorizando nginx (✓ scripts/hardening.sh)
   → Backup diario del storage/logs (✓ scripts/backup.sh)

=============================================================
TIPS
