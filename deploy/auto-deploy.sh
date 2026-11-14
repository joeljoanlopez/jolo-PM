#!/usr/bin/env bash
# Despliegue automático de jolo-PM (E11).
#
# Pensado para lanzarse cada pocos minutos desde el timer de systemd de usuario
# (ver deploy/install-auto-deploy.sh). Si la rama principal de GitHub tiene un
# commit nuevo con el CI en verde, lo trae con fast-forward y reconstruye los
# contenedores. No hace nada salvo que `.env` tenga `AUTO_DEPLOY=true`.
#
# Códigos de salida: 0 si no había nada que hacer, se ha saltado a propósito o
# ha desplegado; 1 si falla el fetch, el merge, la consulta del CI o el compose.

set -euo pipefail

readonly BRANCH=main

log() { printf '%s\n' "$*"; }
err() { printf '%s\n' "$*" >&2; }

# Lee una variable del .env sin hacer `source`: hay valores sin comillas con
# espacios y asteriscos (SYNC_CRON) que la shell interpretaría. Gana la última
# asignación, como en Docker Compose; se quitan comillas y espacios de los extremos.
env_value() {
    local name=$1 file=$2 line
    [[ -f $file ]] || return 0
    line=$(grep -E "^[[:space:]]*(export[[:space:]]+)?${name}[[:space:]]*=" "$file" | tail -n 1) || return 0
    line=${line#*=}
    line=$(printf '%s' "$line" | sed -E 's/^[[:space:]]+//; s/[[:space:]]+$//')
    line=${line#\"}; line=${line%\"}
    line=${line#\'}; line=${line%\'}
    printf '%s' "$line"
}

# owner/name del remoto origin, tanto en SSH como en HTTPS.
github_repo() {
    local url
    url=$(git config --get remote.origin.url)
    url=${url%.git}
    case $url in
        git@github.com:*) printf '%s' "${url#git@github.com:}" ;;
        ssh://git@github.com/*) printf '%s' "${url#ssh://git@github.com/}" ;;
        https://github.com/*) printf '%s' "${url#https://github.com/}" ;;
        *) return 1 ;;
    esac
}

# GET a la API de GitHub. Usa `gh` si está instalado y autenticado; si no,
# `curl` sin autenticar (el repo es público) y `jq` para filtrar.
github_api() {
    local path=$1 filter=$2
    if command -v gh >/dev/null 2>&1 && gh auth status >/dev/null 2>&1; then
        gh api "$path" --jq "$filter"
    else
        command -v jq >/dev/null 2>&1 || { err "Sin gh autenticado hace falta jq para leer la API de GitHub."; return 1; }
        curl -fsSL -H 'Accept: application/vnd.github+json' \
            -H 'X-GitHub-Api-Version: 2022-11-28' \
            "https://api.github.com/$path" | jq -r "$filter"
    fi
}

# Estado del CI de un commit: imprime `success`, `pending` o `failure`.
# Mira los check runs (GitHub Actions) y los statuses clásicos. Sin ningún
# check todavía cuenta como pendiente: el CI aún no ha arrancado.
ci_state() {
    local repo=$1 sha=$2 checks statuses
    checks=$(github_api "repos/$repo/commits/$sha/check-runs?per_page=100" \
        '[.check_runs[] | if .status != "completed" then "pending"
            elif (.conclusion | IN("success", "neutral", "skipped")) then "success"
            else "failure" end] | join(" ")') || return 1
    # El estado combinado sale `pending` cuando no hay ningún status: se ignora.
    statuses=$(github_api "repos/$repo/commits/$sha/status" \
        'if .total_count == 0 then "" else .state end') || return 1

    local all="$checks $statuses"
    if [[ $all == *failure* || $all == *error* ]]; then
        printf 'failure'
    elif [[ -z ${checks// /} || $all == *pending* ]]; then
        printf 'pending'
    else
        printf 'success'
    fi
}

main() {
    local repo_dir
    repo_dir=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
    cd "$repo_dir"

    local enabled
    enabled=$(env_value AUTO_DEPLOY .env)
    if [[ $enabled != true ]]; then
        return 0
    fi

    # Candado y marca de despliegue pendiente viven en .git: nunca se versionan.
    local git_dir pending_file
    git_dir=$(git rev-parse --absolute-git-dir)
    pending_file="$git_dir/auto-deploy.pending"

    # Una sola ejecución a la vez.
    exec 9>"$git_dir/auto-deploy.lock"
    if ! flock -n 9; then
        log "Otra ejecución sigue en marcha; salgo."
        return 0
    fi

    local current
    current=$(git symbolic-ref --quiet --short HEAD || echo '(HEAD separado)')
    if [[ $current != "$BRANCH" ]]; then
        log "La copia local está en '$current', no en '$BRANCH': no despliego."
        return 0
    fi
    if [[ -n $(git status --porcelain --untracked-files=no) ]]; then
        log "Hay cambios locales sin commitear en ficheros seguidos por git: no despliego."
        git status --short --untracked-files=no
        return 0
    fi

    if ! git fetch --quiet origin "$BRANCH"; then
        err "Falló git fetch de origin/$BRANCH."
        return 1
    fi

    local head remote
    head=$(git rev-parse HEAD)
    remote=$(git rev-parse "origin/$BRANCH")
    if [[ $head == "$remote" ]]; then
        # Si el último compose falló tras el merge, HEAD ya está al día pero los
        # contenedores no: se reintenta (el CI de ese commit ya estaba en verde).
        if [[ -f $pending_file && $(<"$pending_file") == "$head" ]]; then
            log "Reintentando el compose de ${head:0:7}, que falló en la vuelta anterior."
            compose_up "$head" "$pending_file"
            return
        fi
        return 0
    fi
    if ! git merge-base --is-ancestor HEAD "origin/$BRANCH"; then
        log "La copia local tiene commits que no están en origin/$BRANCH: no despliego."
        return 0
    fi

    local gh_repo state
    if ! gh_repo=$(github_repo); then
        err "El remoto origin no apunta a GitHub: $(git config --get remote.origin.url)"
        return 1
    fi
    if ! state=$(ci_state "$gh_repo" "$remote"); then
        err "No se pudo consultar el CI de ${remote:0:7}."
        return 1
    fi
    case $state in
        pending)
            log "CI de ${remote:0:7} pendiente; se reintenta en la siguiente vuelta."
            return 0 ;;
        failure)
            log "CI de ${remote:0:7} en rojo: no despliego."
            return 0 ;;
    esac

    log "Desplegando ${head:0:7} → ${remote:0:7}:"
    git log --oneline "$head..$remote"

    if ! git merge --ff-only --quiet "origin/$BRANCH"; then
        err "Falló git merge --ff-only origin/$BRANCH."
        return 1
    fi
    compose_up "$remote" "$pending_file"
}

# Reconstruye los contenedores. Mientras no salga bien, deja el commit en la
# marca de pendiente para que la siguiente vuelta lo reintente.
compose_up() {
    local sha=$1 pending_file=$2
    printf '%s\n' "$sha" >"$pending_file"
    if ! docker compose up -d --build --remove-orphans; then
        err "Falló docker compose up con ${sha:0:7}; se reintenta en la siguiente vuelta."
        return 1
    fi
    rm -f "$pending_file"
    log "Desplegado ${sha:0:7}."
}

# Todo va dentro de main: bash lee el script entero antes de ejecutarlo, así que
# el merge puede sustituir este mismo fichero sin romper la ejecución en curso.
main "$@"
exit $?
