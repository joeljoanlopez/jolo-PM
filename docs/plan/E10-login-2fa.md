# E10 — Login con 2FA

## Objetivo
Que solo yo pueda ver los tableros y gestionar proyectos, aunque alguien llegue
a la web: hoy la única barrera es que el proxy limita el acceso a la red local
y la VPN, y cualquiera de dentro ve los repos a los que tiene acceso el token.

## Alcance

### Usuario único y configuración inicial
- La app tiene **un solo usuario**. No hay nombre de usuario ni registro: se
  entra con contraseña y código.
- Mientras no exista, la web muestra un **asistente de configuración inicial**
  en vez del login:
  1. Pide el **código de configuración**: un código aleatorio de un solo uso que
     el backend genera al arrancar si no hay usuario y escribe en sus logs
     (`docker compose logs backend`). Así, alguien de la red que abra la web
     antes que yo no puede quedarse la cuenta: hace falta acceso al servidor.
  2. Contraseña (dos veces, mínimo 12 caracteres), guardada con el hash de
     Laravel (argon2id o bcrypt).
  3. Alta del 2FA con QR y confirmación con un código válido.
  4. Muestra los códigos de recuperación.
  La cuenta solo se crea cuando se completan los cuatro pasos. Después, el
  asistente y su código dejan de existir.
- **El 2FA es obligatorio**: no hay cuenta sin él.

### Segundo factor: TOTP
- Códigos de 6 dígitos que cambian cada 30 s (RFC 6238), compatibles con
  cualquier app de autenticación (Aegis, 1Password, Google Authenticator…).
- Alta: el backend genera un secreto y lo devuelve como URI `otpauth://`. La
  web lo muestra como QR (y como texto, para copiarlo a mano), y el alta solo
  se confirma al introducir un código válido.
- Se acepta el código de la ventana anterior y de la siguiente (±30 s), para
  tolerar relojes desajustados, pero **cada código vale una sola vez**: se
  guarda el último periodo usado.
- El secreto se guarda **cifrado**, nunca en claro.
- **10 códigos de recuperación** de un solo uso, guardados con hash. Se
  muestran una única vez al activar el 2FA y se pueden regenerar con la sesión
  iniciada.
- Si se pierde la contraseña, el móvil o los códigos de recuperación:
  `php artisan auth:reset` borra la cuenta (no los proyectos) y la web vuelve
  al asistente de configuración inicial, con un código nuevo en los logs. Es la
  única operación por consola, y solo para recuperar el acceso.

### Flujo de login (dos pasos)
- `GET /api/auth/setup` dice si hace falta la configuración inicial;
  `POST /api/auth/setup/*` recorre sus pasos con el código de configuración.
1. `POST /api/auth/login` `{password}`. Si es correcto, la sesión
   queda **pendiente de 2FA** (caduca en 5 minutos), no autenticada.
2. `POST /api/auth/two-factor` `{code}` o `{recoveryCode}`. Si es correcto, la
   sesión pasa a autenticada y su id se regenera.
- `POST /api/auth/logout` y `GET /api/auth/me` (quién soy y en qué paso estoy).

### Protección
- Todas las rutas de `/api` exigen sesión autenticada, salvo `/api/health` y
  `/api/auth/*`. Sin sesión, la respuesta es 401 con la forma de error de
  siempre.
- **Límite de intentos** de login, de 2FA y del código de configuración, por IP
  (por ejemplo, 5 por minuto). Al superarlo: 429 con el tiempo de espera.
- Cookie de sesión `HttpOnly`, `Secure` y `SameSite=Strict`, con caducidad por
  inactividad configurable (`SESSION_LIFETIME`, 7 días por defecto).
- Protección CSRF con el mecanismo de Laravel: cookie `XSRF-TOKEN` y cabecera
  `X-XSRF-TOKEN` en cada petición que modifica algo.
- Un registro de cada inicio de sesión, correcto o fallido, con IP y hora.
- `APP_KEY` pasa a ser obligatoria, porque cifra las cookies y el secreto TOTP:
  `${APP_KEY:?}` en el compose y su generación documentada en el README.
- El worker no cambia: corre por consola y no pasa por HTTP.

### Arquitectura
- **Dominio** (PHP puro): `Account`, `SetupCode`, `RecoveryCodes`, `TotpVerifier` (RFC 6238 con
  `hash_hmac`, sin librerías) y los puertos `AccountRepository`, `PasswordHasher`,
  `SecretCipher`, `Clock` y `LoginAttemptLimiter`.
- **Aplicación**: `IssueSetupCode`, `CompleteSetup`, `Login`,
  `VerifySecondFactor`, `RegenerateRecoveryCodes` y `ResetAccount`.
- **Infraestructura**: adaptadores sobre `Hash`, `Crypt`, `RateLimiter` y la
  sesión de Laravel; el middleware de autenticación; los controladores y los
  comandos.
- **Frontend**: un puerto `AuthGateway` con su adaptador HTTP, el asistente de
  configuración inicial, las pantallas de login y de código, y un guardián que manda a login ante
  cualquier 401.

### Fuera de alcance
- Varios usuarios.
- Passkeys (WebAuthn): están en el backlog (E09). Login con GitHub o SSO: no.
- Autenticación en el proxy (Authelia, cabeceras de Tailscale). Es una
  alternativa válida que no toca la app, pero se descarta para que la app se
  proteja sola, esté detrás del proxy que esté.

## Criterios de aceptación
- Sin sesión, ninguna ruta de datos responde: `curl /api/projects` → 401.
- Con la contraseña correcta pero sin el segundo factor, tampoco hay acceso.
- Un código TOTP ya usado se rechaza aunque siga dentro de su ventana.
- Un código de recuperación funciona una sola vez.
- Sin el código de configuración de los logs no se puede crear la cuenta, y una
  vez creada el asistente responde 404.
- El 6.º intento fallido en un minuto devuelve 429.
- En la base de datos no aparece en claro ni el secreto TOTP ni los códigos de
  recuperación.
- El test de arquitectura sigue en verde: nada de Laravel en `Domain` ni en
  `Application`.

## Subtareas
- [ ] Modelo `Account` (usuario único) y migración
- [ ] Código de configuración: generado al arrancar sin cuenta, escrito en los logs y de un solo uso
- [ ] Asistente de configuración inicial en la API (contraseña + alta del 2FA + códigos de recuperación)
- [ ] `TotpVerifier` en el dominio, probado con los vectores del RFC 6238
- [ ] Rechazo de códigos reutilizados (último periodo usado)
- [ ] Códigos de recuperación: generación, hash y consumo de un solo uso
- [ ] Secreto TOTP cifrado (puerto `SecretCipher` + adaptador con `Crypt`)
- [ ] Casos de uso de login en dos pasos y regeneración de códigos de recuperación
- [ ] Rutas `/api/auth/*`, middleware de autenticación y 401 en el resto de la API
- [ ] Límite de intentos por IP en login, 2FA y configuración (429)
- [ ] Sesión en cookie segura, CSRF y `APP_KEY` obligatoria
- [ ] Registro de inicios de sesión
- [ ] Comando `auth:reset` para recuperar el acceso
- [ ] Frontend: `AuthGateway`, asistente de configuración con QR, pantallas de login y código
- [ ] Frontend: redirección a login ante 401 y botón de cerrar sesión
- [ ] Tests de API del flujo completo, incluidos límite de intentos y CSRF
- [ ] README: configuración inicial, `APP_KEY` y qué hacer si se pierde el acceso
