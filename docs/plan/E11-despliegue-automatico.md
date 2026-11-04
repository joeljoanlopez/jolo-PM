# E11 — Despliegue automático

## Objetivo
Que el servidor se actualice solo al hacer merge de una PR a la rama principal,
sin abrir puertos ni instalar un runner de GitHub. Es **opcional** y viene
**desactivado** por defecto.

## Alcance
- Variable `AUTO_DEPLOY` en `.env`, `false` por defecto y documentada en
  `.env.example`. Con cualquier valor distinto de `true` no se despliega nada.
- Script `deploy/auto-deploy.sh`, pensado para lanzarse cada pocos minutos:
  1. Sale sin hacer nada si `AUTO_DEPLOY` no es `true`. Lee la variable del
     `.env` sin hacer `source`, porque hay valores sin comillas como `SYNC_CRON`.
  2. Usa `flock` para que no se solapen dos ejecuciones.
  3. Solo actúa si la copia local está en la rama principal y sin cambios en
     ficheros seguidos por git. Si no se cumple, lo registra y sale.
  4. Hace `git fetch` y compara `HEAD` con `origin/main`. Si son iguales, sale.
  5. Consulta los checks del commit remoto (`gh api`, o `curl` si no hay `gh`):
     si están pendientes, lo reintenta en la siguiente vuelta; si alguno ha
     fallado, no despliega.
  6. Ejecuta `git merge --ff-only` y `docker compose up -d --build`.
  7. Lo deja todo registrado en el journal y termina con código de error si
     falla el pull o el compose.
- Timer de systemd **de usuario** (sin `sudo`): `jolo-pm-deploy.service` y
  `.timer`. Un script de instalación los genera con la ruta absoluta del repo,
  así no queda ninguna ruta fija en git, y también sabe desinstalarlos.
- Sin runner de Actions ni webhook: el repo es público y el servidor no se
  expone. El webhook de E09 sigue siendo solo para sincronizar.

## Criterios de aceptación
- Con `AUTO_DEPLOY=false` (o sin definir), el timer se ejecuta y no toca nada.
- Con `AUTO_DEPLOY=true`, en menos de un intervalo tras un merge con CI en
  verde, los contenedores se reconstruyen con el commit nuevo.
- Un commit con el CI en rojo o pendiente no se despliega.
- Con cambios locales sin commitear, el script no los pisa: avisa y sale.

## Subtareas
- [ ] `AUTO_DEPLOY=false` en `.env.example`, documentada
- [ ] `deploy/auto-deploy.sh` con las comprobaciones de rama, cambios locales y CI
- [ ] Units de systemd de usuario y script de instalación y desinstalación
- [ ] Sección en el README: activar, instalar el timer, `loginctl enable-linger` y ver logs
- [ ] Probado en el servidor: no-op desactivado, deploy tras un merge, CI en rojo bloquea
