#!/bin/bash
# =============================================================================
# hardening.sh — Hardenización del servidor (Debian 12 / lanzataxi.es)
#
# Ejecutar como root en el VPS:
#   sudo bash scripts/hardening.sh
#
# Qué hace:
#   1. Actualiza el sistema
#   2. Configura UFW (firewall): permite solo 22, 80, 443
#   3. Endurece SSH: deshabilita root login y autenticación por contraseña
#   4. Instala y configura fail2ban (bloqueo de IPs tras intentos fallidos)
#   5. Deshabilita servicios innecesarios
#   6. Aplica parámetros de red seguros (sysctl)
# =============================================================================

set -euo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

ok()   { echo -e "${GREEN}[OK]${NC} $*"; }
warn() { echo -e "${YELLOW}[WARN]${NC} $*"; }
err()  { echo -e "${RED}[ERR]${NC} $*"; exit 1; }

[[ $EUID -ne 0 ]] && err "Este script debe ejecutarse como root (sudo bash hardening.sh)"

echo "============================================="
echo " LanzaTaxi — Hardenización del servidor"
echo " Debian 12 | $(date '+%Y-%m-%d %H:%M:%S')"
echo "============================================="

# -----------------------------------------------------------------------------
# 1. Actualización del sistema
# -----------------------------------------------------------------------------
echo ""
echo ">>> 1/6 Actualizando el sistema..."
apt-get update -qq
apt-get upgrade -y -qq
apt-get install -y -qq ufw fail2ban unattended-upgrades apt-listchanges
ok "Sistema actualizado"

# -----------------------------------------------------------------------------
# 2. Firewall — UFW
# -----------------------------------------------------------------------------
echo ""
echo ">>> 2/6 Configurando UFW..."

# Política por defecto: denegar todo el tráfico entrante, permitir saliente.
ufw --force reset
ufw default deny incoming
ufw default allow outgoing

# Puertos permitidos
ufw allow 22/tcp   comment 'SSH'
ufw allow 80/tcp   comment 'HTTP (redirige a HTTPS)'
ufw allow 443/tcp  comment 'HTTPS'

# Protección contra escaneos de puertos (rate limiting SSH)
ufw limit 22/tcp comment 'SSH rate-limit'

ufw --force enable
ufw status verbose
ok "UFW configurado y activo"

# -----------------------------------------------------------------------------
# 3. Endurecimiento SSH
# -----------------------------------------------------------------------------
echo ""
echo ">>> 3/6 Endureciendo SSH..."

SSHD_CFG="/etc/ssh/sshd_config"

# Copia de seguridad antes de modificar
cp "$SSHD_CFG" "${SSHD_CFG}.bak.$(date +%Y%m%d%H%M%S)"

# Función para aplicar/reemplazar una directiva en sshd_config
set_ssh_option() {
    local key="$1"
    local value="$2"
    if grep -qE "^#?${key}\s" "$SSHD_CFG"; then
        sed -i "s|^#\?${key}\s.*|${key} ${value}|" "$SSHD_CFG"
    else
        echo "${key} ${value}" >> "$SSHD_CFG"
    fi
}

# Deshabilitar login como root
set_ssh_option "PermitRootLogin"            "no"
# Deshabilitar autenticación por contraseña (solo clave SSH)
set_ssh_option "PasswordAuthentication"     "no"
# Deshabilitar autenticación GSSAPI (reduce superficie)
set_ssh_option "GSSAPIAuthentication"       "no"
# Deshabilitar reenvío X11
set_ssh_option "X11Forwarding"              "no"
# Tiempo máximo para autenticarse tras conectar
set_ssh_option "LoginGraceTime"             "30"
# Máximo de intentos por sesión
set_ssh_option "MaxAuthTries"               "3"
# Máximo de sesiones concurrentes por conexión
set_ssh_option "MaxSessions"                "5"
# Desconectar sesiones inactivas tras 5 minutos
set_ssh_option "ClientAliveInterval"        "300"
set_ssh_option "ClientAliveCountMax"        "2"
# Solo protocolo SSH2
set_ssh_option "Protocol"                   "2"
# Deshabilitar túneles TCP (no es necesario para este proyecto)
set_ssh_option "AllowTcpForwarding"         "no"
# Deshabilitar forwarding del agente
set_ssh_option "AllowAgentForwarding"       "no"

# Verificar config antes de recargar
sshd -t && systemctl reload sshd
ok "SSH endurecido"
warn "IMPORTANTE: Solo podrás entrar con tu clave SSH. Asegúrate de tenerla configurada antes de cerrar esta sesión."

# -----------------------------------------------------------------------------
# 4. fail2ban
# -----------------------------------------------------------------------------
echo ""
echo ">>> 4/6 Configurando fail2ban..."

# Configuración local (no tocar default.conf para no perder cambios en updates)
cat > /etc/fail2ban/jail.local << 'EOF'
[DEFAULT]
# Bloqueo de 1 hora tras 5 intentos fallidos en 10 minutos
bantime   = 3600
findtime  = 600
maxretry  = 5
# Backend automático (auto detecta inotify/polling)
backend   = auto
# Notificación por email (cambiar si se tiene postfix configurado)
destemail = root@localhost
action    = %(action_mwl)s

[sshd]
enabled  = true
port     = ssh
filter   = sshd
logpath  = /var/log/auth.log
maxretry = 3
bantime  = 7200

[nginx-http-auth]
enabled  = true
port     = http,https
filter   = nginx-http-auth
logpath  = /var/log/nginx/error.log
maxretry = 5

[nginx-limit-req]
enabled  = true
port     = http,https
filter   = nginx-limit-req
logpath  = /var/log/nginx/error.log
maxretry = 10
findtime = 60
bantime  = 600

[nginx-botsearch]
enabled  = true
port     = http,https
filter   = nginx-botsearch
logpath  = /var/log/nginx/access.log
maxretry = 2
EOF

systemctl enable fail2ban
systemctl restart fail2ban
ok "fail2ban configurado y activo"

# -----------------------------------------------------------------------------
# 5. Deshabilitar servicios innecesarios
# -----------------------------------------------------------------------------
echo ""
echo ">>> 5/6 Deshabilitando servicios innecesarios..."

SERVICIOS_A_DESHABILITAR=(
    "bluetooth"
    "avahi-daemon"
    "cups"
    "rpcbind"
    "nfs-server"
    "telnet"
    "vsftpd"
    "snmpd"
)

for servicio in "${SERVICIOS_A_DESHABILITAR[@]}"; do
    if systemctl list-units --all | grep -q "$servicio"; then
        systemctl disable --now "$servicio" 2>/dev/null || true
        ok "Deshabilitado: $servicio"
    fi
done

# Deshabilitar IPv6 si no se usa (reduce superficie de ataque)
# Comentar las siguientes líneas si el VPS necesita IPv6
# echo "net.ipv6.conf.all.disable_ipv6 = 1" >> /etc/sysctl.conf
# echo "net.ipv6.conf.default.disable_ipv6 = 1" >> /etc/sysctl.conf

ok "Servicios innecesarios deshabilitados"

# -----------------------------------------------------------------------------
# 6. Parámetros de red seguros (sysctl)
# -----------------------------------------------------------------------------
echo ""
echo ">>> 6/6 Aplicando parámetros sysctl seguros..."

SYSCTL_FILE="/etc/sysctl.d/99-lanzataxi-hardening.conf"

cat > "$SYSCTL_FILE" << 'EOF'
# ---- LanzaTaxi: parámetros de red seguros ----

# Protección contra SYN flood (DoS)
net.ipv4.tcp_syncookies = 1

# Ignorar pings de broadcast (prevención Smurf)
net.ipv4.icmp_echo_ignore_broadcasts = 1

# Ignorar respuestas ICMP falsas
net.ipv4.icmp_ignore_bogus_error_responses = 1

# No enrutar paquetes (el servidor no es un router)
net.ipv4.ip_forward = 0
net.ipv4.conf.all.forwarding = 0

# Evitar IP spoofing (validación de origen)
net.ipv4.conf.default.rp_filter = 1
net.ipv4.conf.all.rp_filter = 1

# Deshabilitar redirecciones ICMP (prevención de ataques de ruta)
net.ipv4.conf.all.accept_redirects = 0
net.ipv4.conf.default.accept_redirects = 0
net.ipv4.conf.all.secure_redirects = 0
net.ipv4.conf.default.secure_redirects = 0
net.ipv4.conf.all.send_redirects = 0
net.ipv4.conf.default.send_redirects = 0

# Registrar paquetes con origen sospechoso (martians)
net.ipv4.conf.all.log_martians = 1
net.ipv4.conf.default.log_martians = 1

# Protección TIME-WAIT (prevención ataques de reconexión)
net.ipv4.tcp_rfc1337 = 1

# Reducir tamaño de cola de conexiones para liberar recursos bajo carga
net.ipv4.tcp_max_syn_backlog = 2048
net.ipv4.tcp_synack_retries = 2
net.ipv4.tcp_syn_retries = 5
EOF

sysctl --system > /dev/null 2>&1
ok "Parámetros sysctl aplicados"

# -----------------------------------------------------------------------------
# Actualizaciones automáticas de seguridad
# -----------------------------------------------------------------------------
cat > /etc/apt/apt.conf.d/50unattended-upgrades-lanzataxi << 'EOF'
Unattended-Upgrade::Allowed-Origins {
    "${distro_id}:${distro_codename}-security";
};
Unattended-Upgrade::AutoFixInterruptedDpkg "true";
Unattended-Upgrade::MinimalSteps "true";
Unattended-Upgrade::Remove-Unused-Dependencies "true";
Unattended-Upgrade::Automatic-Reboot "false";
EOF

cat > /etc/apt/apt.conf.d/20auto-upgrades << 'EOF'
APT::Periodic::Update-Package-Lists "1";
APT::Periodic::Unattended-Upgrade "1";
APT::Periodic::AutocleanInterval "7";
EOF

systemctl enable unattended-upgrades
ok "Actualizaciones de seguridad automáticas configuradas"

# -----------------------------------------------------------------------------
# Resumen final
# -----------------------------------------------------------------------------
echo ""
echo "============================================="
echo " Hardenización completada"
echo "============================================="
echo ""
echo " UFW:        $(ufw status | head -1)"
echo " fail2ban:   $(systemctl is-active fail2ban)"
echo " SSH root:   $(grep '^PermitRootLogin' /etc/ssh/sshd_config)"
echo " SSH passwd: $(grep '^PasswordAuthentication' /etc/ssh/sshd_config)"
echo ""
warn "Recuerda verificar que puedes acceder por SSH con tu clave antes de cerrar esta sesión."
echo ""
