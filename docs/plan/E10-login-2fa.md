# E10 — Login con 2FA

## Objetivo
Que solo yo pueda ver los tableros y gestionar proyectos, aunque alguien llegue
a la web: hoy la única barrera es que el proxy limita el acceso a la red local
y la VPN, y cualquiera de dentro ve los repos a los que tiene acceso el token.

## Alcance

### Usuarios
- Pocos usuarios y **sin registro público**. Las cuentas se crean por consola:
  `php artisan users:create {usuario}` pide la contraseña sin mostrarla.
- Contraseña con hash de Laravel (argon2id o bcrypt) y un mínimo de 12
  caracteres.
- **El 2FA es obligatorio.** El primer login después de la contraseña obliga a
  darlo de alta; mientras no se haga, no se puede usar nada más.

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
- Si se pierde el móvil y los códigos de recuperación:
  `php artisan users:reset-2fa {usuario}` obliga a darlo de alta de nuevo en el
  siguiente login.

### Flujo de login (dos pasos)
1. `POST /api/auth/login` `{username, password}`. Si es correcto, la sesión
   queda **pendiente de 2FA** (caduca en 5 minutos), no autenticada.
2. `POST /api/auth/two-factor` `{code}` o `{recoveryCode}`. Si es correcto, la
   sesión pasa a autenticada y su id se regenera.
- `POST /api/auth/logout` y `GET /api/auth/me` (quién soy y en qué paso estoy).
- Los errores no revelan si falló el usuario o la contraseña.

### Protección
- Todas las rutas de `/api` exigen sesión autenticada, salvo `/api/health` y
  `/api/auth/*`. Sin sesión, la respuesta es 401 con la forma de error de
  siempre.
- **Límite de intentos** de login y de 2FA, por IP y por usuario (por ejemplo,
  5 por minuto). Al superarlo: 429 con el tiempo de espera.
- Cookie de sesión `HttpOnly`, `Secure` y `SameSite=Strict`, con caducidad por
  inactividad configurable (`SESSION_LIFETIME`, 7 días por defecto).
- Protección CSRF con el mecanismo de Laravel: cookie `XSRF-TOKEN` y cabecera
  `X-XSRF-TOKEN` en cada petición que modifica algo.
- Un registro de cada inicio de sesión, correcto o fallido, con usuario, IP y
  hora.
- `APP_KEY` pasa a ser obligatoria, porque cifra las cookies y el secreto TOTP:
  `${APP_KEY:?}` en el compose y su generación documentada en el README.
- El worker no cambia: corre por consola y no pasa por HTTP.

### Arquitectura
- **Dominio** (PHP puro): `User`, `RecoveryCodes`, `TotpVerifier` (RFC 6238 con
  `hash_hmac`, sin librerías) y los puertos `UserRepository`, `PasswordHasher`,
  `SecretCipher`, `Clock` y `LoginAttemptLimiter`.
- **Aplicación**: `Login`, `VerifySecondFactor`, `EnrollTotp`,
  `ConfirmTotpEnrollment`, `RegenerateRecoveryCodes`, `CreateUser` y
  `ResetTwoFactor`.
- **Infraestructura**: adaptadores sobre `Hash`, `Crypt`, `RateLimiter` y la
  sesión de Laravel; el middleware de autenticación; los controladores y los
  comandos.
- **Frontend**: un puerto `AuthGateway` con su adaptador HTTP, las pantallas de
  login, de código y de alta del 2FA, y un guardián que manda a login ante
  cualquier 401.

### Fuera de alcance
- Passkeys (WebAuthn), login con GitHub y cualquier forma de SSO. Si se quieren,
  van al backlog (E09).
- Autenticación en el proxy (Authelia, cabeceras de Tailscale). Es una
  alternativa válida que no toca la app, pero se descarta para que la app se
  proteja sola, esté detrás del proxy que esté.

## Criterios de aceptación
- Sin sesión, ninguna ruta de datos responde: `curl /api/projects` → 401.
- Con la contraseña correcta pero sin el segundo factor, tampoco hay acceso.
- Un código TOTP ya usado se rechaza aunque siga dentro de su ventana.
- Un código de recuperación funciona una sola vez.
- El 6.º intento fallido en un minuto devuelve 429.
- En la base de datos no aparece en claro ni el secreto TOTP ni los códigos de
  recuperación.
- El test de arquitectura sigue en verde: nada de Laravel en `Domain` ni en
  `Application`.

## Subtareas
- [ ] Modelo `User`, migración y comando `users:create`
- [ ] `TotpVerifier` en el dominio, probado con los vectores del RFC 6238
- [ ] Rechazo de códigos reutilizados (último periodo usado)
- [ ] Códigos de recuperación: generación, hash y consumo de un solo uso
- [ ] Secreto TOTP cifrado (puerto `SecretCipher` + adaptador con `Crypt`)
- [ ] Casos de uso de login en dos pasos, alta del 2FA y regeneración de códigos
- [ ] Rutas `/api/auth/*`, middleware de autenticación y 401 en el resto de la API
- [ ] Límite de intentos por IP y por usuario (429)
- [ ] Sesión en cookie segura, CSRF y `APP_KEY` obligatoria
- [ ] Registro de inicios de sesión
- [ ] Comando `users:reset-2fa`
- [ ] Frontend: `AuthGateway`, pantallas de login, código y alta con QR
- [ ] Frontend: redirección a login ante 401 y botón de cerrar sesión
- [ ] Tests de API del flujo completo, incluidos límite de intentos y CSRF
- [ ] README: crear el primer usuario, `APP_KEY` y qué hacer si se pierde el 2FA
