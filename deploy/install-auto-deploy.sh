#!/usr/bin/env bash
# Instala o desinstala el timer de systemd de usuario del despliegue automático.
#
#   deploy/install-auto-deploy.sh            # instala y arranca el timer
#   deploy/install-auto-deploy.sh uninstall  # lo para y borra las units
#
# Las units se generan con la ruta absoluta de esta copia del repo, así no hay
# rutas fijas en git. No hace falta sudo: van a ~/.config/systemd/user.
# El intervalo se cambia con AUTO_DEPLOY_INTERVAL (formato de systemd, 5min por
# defecto) al instalar.

set -euo pipefail

readonly NAME=jolo-pm-deploy
readonly UNIT_DIR="${XDG_CONFIG_HOME:-$HOME/.config}/systemd/user"

repo_dir=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
interval=${AUTO_DEPLOY_INTERVAL:-5min}

install_units() {
    mkdir -p "$UNIT_DIR"

    cat >"$UNIT_DIR/$NAME.service" <<EOF
[Unit]
Description=jolo-PM: despliegue automático desde origin/main
Documentation=file://$repo_dir/README.md

[Service]
Type=oneshot
ExecStart="$repo_dir/deploy/auto-deploy.sh"
EOF

    cat >"$UNIT_DIR/$NAME.timer" <<EOF
[Unit]
Description=jolo-PM: comprobar si hay que desplegar cada $interval

[Timer]
OnBootSec=2min
OnUnitInactiveSec=$interval
RandomizedDelaySec=30s

[Install]
WantedBy=timers.target
EOF

    systemctl --user daemon-reload
    systemctl --user enable --now "$NAME.timer"
    echo "Instalado $NAME.timer (cada $interval) para $repo_dir."

    if [[ $(loginctl show-user "$USER" --property=Linger --value 2>/dev/null) != yes ]]; then
        echo "Aviso: sin lingering el timer solo corre mientras tengas sesión abierta."
        echo "       Actívalo con: loginctl enable-linger $USER"
    fi
    if ! grep -qE '^[[:space:]]*AUTO_DEPLOY[[:space:]]*=[[:space:]]*"?true"?[[:space:]]*$' "$repo_dir/.env" 2>/dev/null; then
        echo "Aviso: AUTO_DEPLOY no es true en .env; el timer correrá sin desplegar nada."
    fi
}

uninstall_units() {
    systemctl --user disable --now "$NAME.timer" 2>/dev/null || true
    systemctl --user stop "$NAME.service" 2>/dev/null || true
    rm -f "$UNIT_DIR/$NAME.service" "$UNIT_DIR/$NAME.timer"
    systemctl --user daemon-reload
    echo "Desinstalado $NAME.timer."
}

case ${1:-install} in
    install) install_units ;;
    uninstall) uninstall_units ;;
    *) echo "Uso: $0 [install|uninstall]" >&2; exit 2 ;;
esac
