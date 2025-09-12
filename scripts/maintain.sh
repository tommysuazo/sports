#!/usr/bin/env bash
set -euo pipefail
export DEBIAN_FRONTEND=noninteractive

########################################
# Blindaje contra shims (mise/asdf)
########################################
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

########################################
# Arranque rápido de contenedores
########################################
./vendor/bin/sail up -d

########################################
# Refrescar Laravel dentro de Sail
########################################
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan queue:restart

echo "Entorno refrescado con éxito."
