# E09 — Mejoras futuras

## Objetivo
Backlog de ideas que no hacen falta para usar la app pero la harían mejor.
Ninguna está comprometida; se mueven a su propia épica cuando se vayan a hacer.

## Ideas
- [ ] **Webhook de GitHub** (`push` y `pull_request`) para sincronizar al
  instante en vez de esperar al intervalo. Requiere exponer una ruta pública
  con firma HMAC.
- [ ] **Tarjeta por sección** además de por fichero, configurable por proyecto,
  para planes que lo meten todo en un único markdown.
- [ ] **Historial de progreso**: guardar un punto por día y dibujar la evolución
  (burn-up) de cada proyecto.
- [ ] **Tareas nuevas en PRs**: mostrar las casillas que una PR añade y aún no
  existen en la rama principal.
- [ ] **Ramas sin PR**: detectar casillas marcadas en ramas con commits recientes
  aunque todavía no haya PR abierta.
- [ ] **Widget para Glance** con el progreso de cada proyecto.
