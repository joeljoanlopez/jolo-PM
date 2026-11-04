# E12 — Idiomas: castellano e inglés

## Objetivo
Poder usar jolo-PM en castellano o en inglés, eligiendo el idioma desde la
propia web.

## Alcance
- **Selector ES | EN** en la cabecera. La elección se guarda en el navegador
  (`localStorage`); la primera vez se toma del idioma del navegador y, si no es
  ninguno de los dos, castellano. Actualiza `<html lang>`.
- **Frontend** sin dependencias nuevas: diccionarios en
  `frontend/src/i18n/messages.tsx`. `es` es la referencia y `en` se tipa contra
  él, así que una clave sin traducir no compila. Los textos con números o
  fragmentos (tiempos relativos, «3 en revisión»…) son funciones.
- El dominio del frontend deja de tener texto: `COLUMNS` es solo la lista de
  estados y `age()` devuelve la antigüedad sin formatear.
- **Backend**: responde los errores en el idioma de `Accept-Language` (`es` o
  `en`; cualquier otro, castellano). El adaptador HTTP del frontend manda esa
  cabecera con el idioma elegido y, al cambiarlo, las pantallas recargan.
- El dominio del backend sigue sin conocer Laravel: las excepciones llevan un
  `Message` (clave + parámetros) y la traducción la hacen la capa HTTP y la
  consola con `lang/{es,en}/errors.php`.
- El error de la última sincronización se guarda como `Message` en JSON y se
  traduce al leerlo. Los guardados antes como texto se enseñan tal cual hasta la
  siguiente sincronización.
- Fuera de alcance: traducir el contenido de los markdowns y los mensajes que
  devuelve GitHub (van tal cual dentro del error).

## Criterios de aceptación
- Al pulsar EN o ES cambia toda la interfaz sin recargar la página, y la
  elección se mantiene al volver.
- Los errores de la API (validación, repo sin acceso, GitHub caído…) llegan en
  el idioma elegido.
- Las pantallas nuevas (por ejemplo el login de E10) usan los mismos
  diccionarios.

## Subtareas
- [x] Diccionarios `es`/`en` tipados, selector en la cabecera e idioma recordado
- [x] Todas las pantallas del frontend traducidas
- [x] Backend: `Message` en el dominio, `Accept-Language` y `lang/{es,en}`
- [x] Error de sincronización guardado sin traducir y traducido al leerlo
