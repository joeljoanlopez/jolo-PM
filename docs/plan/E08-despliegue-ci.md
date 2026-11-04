# E08 — Despliegue y CI

## Objetivo
Tener jolo-PM corriendo en el servidor detrás de nginx, y que cada PR pase los
tests antes del merge.

## Alcance
- Desplegado en `joloserver` con `docker compose`, servido como
  `jolo-pm.lan.joeljoanlopez.com` mediante las labels de `nginx-autoconf`
  (solo LAN y Tailscale).
- GitHub Actions: PHPUnit del backend y `tsc` + build del frontend en cada push
  y PR.
- README con puesta en marcha, cómo crear el token y cómo conectar proyectos.

## Criterios de aceptación
- La app responde en el dominio interno y el workflow de CI pasa en verde.

## Subtareas
- [ ] Workflow de CI con backend y frontend
- [ ] README de puesta en marcha y token de GitHub
- [ ] Despliegue en el servidor con `nginx-autoconf`
- [ ] Alta de PKManagement y de jolo-PM en la instancia desplegada
