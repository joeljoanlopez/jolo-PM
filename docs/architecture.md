# Arquitectura

jolo-PM sigue **arquitectura hexagonal** (puertos y adaptadores) en las dos
apps. La regla es una sola: **las dependencias apuntan hacia dentro**. El
dominio no conoce a nadie; la aplicación solo conoce al dominio; el framework,
GitHub, SQLite y React viven fuera y se enchufan a través de interfaces.

```
            entrada (driving)                          salida (driven)
   HTTP ─┐                                                  ┌─ GitHub (API REST)
 Consola ─┼─► Infrastructure ─► Application ─► Domain ◄─ Infrastructure ─┼─ SQLite (Eloquent)
         ┘   (controladores,     (casos de     (modelos,   (adaptadores   └─ caché de ficheros
              comandos)           uso)          reglas,     de los puertos)
                                                puertos)
```

## Backend (`backend/app`)

| Capa | Carpeta | Qué hay | Puede usar |
| --- | --- | --- | --- |
| Dominio | `Domain/Model` | `Project`, `Task`, `Board`, `Card`… y sus reglas de validación | nada |
| | `Domain/Service` | `MarkdownTaskParser`, `GlobMatcher`, `BoardBuilder` (reglas de columna) | nada |
| | `Domain/Model/Auth`, `Domain/Service/Auth` | Login (E10): `Account`, `SetupCode`, `RecoveryCodes`, `Password`; `TotpVerifier` (RFC 6238 con `hash_hmac`) y `Base32` | nada |
| | `Domain/Port` | Interfaces que el dominio necesita: `SourceHost`, `ProjectRepository`, `BoardStore`, `ContentCache`; para el login, `AccountRepository`, `PasswordHasher`, `SecretCipher`, `Clock`, `LoginAttemptLimiter`, `LoginLog` | nada |
| Aplicación | `Application` | Un caso de uso por clase: `AddProject`, `SyncProject`, `SyncAllProjects`… y en `Application/Auth` los del login: `CompleteSetup`, `Login`, `VerifySecondFactor`, `RegenerateRecoveryCodes`, `ResetAccount` | Dominio |
| Infraestructura | `Infrastructure/Http` | Controladores, `FormRequest`, traducción de excepciones a HTTP; `AuthSession` (estado del login en la sesión) y el middleware `Authenticate` (401 sin sesión) | todo |
| | `Infrastructure/Auth` | Adaptadores del login sobre `Hash`, `Crypt`, `RateLimiter` y la hora de Laravel | todo |
| | `Infrastructure/Console` | Comandos `artisan` (`projects:sync`, `blobs:prune`) | todo |
| | `Infrastructure/GitHub` | `GitHubSourceHost`: implementa `SourceHost` con el cliente `Http` de Laravel | todo |
| | `Infrastructure/Persistence` | Modelos Eloquent y adaptadores de `ProjectRepository`, `BoardStore`, `ContentCache` | todo |
| | `Infrastructure/Cache` | `CachingSourceHost`: decorador que cachea cualquier `SourceHost` | Dominio |
| Composición | `Providers/AppServiceProvider` | Qué adaptador implementa cada puerto | todo |

`Domain` y `Application` son **PHP puro**: ni `Illuminate\…` ni helpers como
`config()` o `now()`. `tests/Unit/ArchitectureTest.php` falla si alguien lo
rompe.

### Cómo cambiar una pieza

- **Otro proveedor de repos (GitLab, Gitea…)**: nueva clase en
  `Infrastructure/` que implemente `Domain\Port\SourceHost` y cambiar su línea
  en `AppServiceProvider`. La caché sigue funcionando porque decora el puerto.
- **Otra base de datos**: si Laravel la soporta (MySQL, Postgres), solo cambian
  las variables `DB_*`. Si no, nuevos adaptadores de `ProjectRepository`,
  `BoardStore` y `ContentCache`.
- **Otra regla de columnas**: `Domain/Model/CardStatus::fromCounts` y
  `Domain/Service/BoardBuilder`. Tienen tests unitarios que no arrancan Laravel.
- **Quitar Laravel**: los adaptadores y el `ServiceProvider` se reescriben;
  `Domain`, `Application` y sus tests se quedan igual.

### Flujo de una sincronización

1. `POST /api/projects/{id}/sync` (o `php artisan projects:sync`) → `SyncProject`.
2. `SourceHost` da los ficheros markdown de la rama; `GlobMatcher` filtra.
3. `MarkdownTaskParser` lee las casillas de cada fichero (contenido cacheado por SHA).
4. Por cada PR abierta contra la rama, se leen sus versiones de esos ficheros.
5. `BoardBuilder` decide el estado de cada tarea y la columna de cada tarjeta.
6. `BoardStore` guarda el tablero; si algo falló, guarda el error y conserva el anterior.

## Frontend (`frontend/src`)

| Capa | Carpeta | Qué hay |
| --- | --- | --- |
| Dominio | `domain/` | Tipos (`model.ts`) y funciones puras de presentación (`board.ts`: columnas, progreso, agrupado) |
| Puerto | `ports/ProjectsGateway.ts`, `ports/AuthGateway.ts` | Interfaces con todo lo que la UI pide al backend |
| Adaptador | `infrastructure/Http*Gateway.ts` | Implementación con `fetch` contra `/api`; `HttpClient` añade la cookie de sesión, el token CSRF y avisa de los 401 |
| UI | `ui/` | Componentes React. Obtienen el gateway por contexto (`useGateway`), nunca importan el adaptador |
| Composición | `main.tsx` | Elige el adaptador |

Para trabajar sin backend basta otro adaptador del puerto con datos en memoria
y cambiarlo en `main.tsx`.

## Login (E10)

Toda la API salvo `/api/health` y `/api/auth/*` exige sesión: el middleware
`Authenticate` responde 401 si la sesión no ha pasado contraseña **y** segundo
factor. La sesión guarda el id aleatorio de la cuenta; `auth:reset` borra la
cuenta y con ella invalida todas las sesiones. En el frontend, `AuthProvider`
escucha los 401 de cualquier petición y vuelve al login o al asistente.

