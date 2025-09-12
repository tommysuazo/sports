#!/usr/bin/env bash
set -euo pipefail
export DEBIAN_FRONTEND=noninteractive

# Blindaje contra shims (mise/asdf)
MISE_SHIMS="${HOME}/.local/share/mise/shims"
if [[ -d "$MISE_SHIMS" ]]; then
  CLEAN_PATH=""
  IFS=':' read -r -a _PATH_ARR <<< "$PATH"
  for p in "${_PATH_ARR[@]}"; do
    [[ "$p" == "$MISE_SHIMS" ]] && continue
    [[ -n "$CLEAN_PATH" ]] && CLEAN_PATH="${CLEAN_PATH}:$p" || CLEAN_PATH="$p"
  done
  export PATH="$CLEAN_PATH:$MISE_SHIMS"
fi

# Arranque inicial de contenedores
./vendor/bin/sail up -d

# Instalación de dependencias dentro de Sail
./vendor/bin/sail composer install --no-interaction --prefer-dist
./vendor/bin/sail npm install --silent

# Esperar a que MySQL y Redis estén listos (dentro de los contenedores)
echo "Esperando a que MySQL esté listo..."
./vendor/bin/sail exec mysql sh -lc 'until mysqladmin ping -uroot -p"$MYSQL_ROOT_PASSWORD" --silent; do sleep 2; done'

echo "Esperando a que Redis esté listo..."
./vendor/bin/sail exec redis sh -lc 'redis-cli ping' >/dev/null

# Configuración inicial de Laravel
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan key:generate || true

echo "Entorno configurado con éxito."
