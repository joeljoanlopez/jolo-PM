# E08 — Despliegue y CI

## Objetivo
Tener jolo-PM corriendo en el servidor detrás de nginx, y que cada PR pase los
tests antes del merge.

## Alcance
- Desplegado en el servidor de casa con `docker compose`, detrás del proxy
  inverso (labels de `nginx-autoconf`) y accesible solo desde la red local y la
  VPN.
- GitHub Actions: PHPUnit del backend y `tsc` + build del frontend en cada push
  y PR.
- README con puesta en marcha, cómo crear el token y cómo conectar proyectos.

## Criterios de aceptación
- La app responde en el dominio interno y el workflow de CI pasa en verde.

## Subtareas
- [x] Workflow de CI con backend y frontend
- [x] README de puesta en marcha y token de GitHub
- [x] Despliegue en el servidor con `nginx-autoconf` y token propio en `.env`
- [x] Alta de PKManagement y de jolo-PM en la instancia desplegada
