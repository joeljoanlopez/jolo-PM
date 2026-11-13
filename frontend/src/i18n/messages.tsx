// Textos de la interfaz. `es` es la referencia: `en` tiene que tener las mismas
// claves (lo comprueba el tipo), así que una traducción olvidada no compila.
import type { ReactNode } from "react";
import type { CardStatus } from "../domain/model";
import type { Age } from "../domain/board";

const es = {
  app: {
    tagline: "progreso leído de tus markdowns",
    language: "Idioma",
  },
  columns: {
    todo: "Por hacer",
    in_progress: "En curso",
    review: "En revisión",
    done: "Hecho",
  } satisfies Record<CardStatus, string>,
  age: (age: Age | null): string => {
    if (!age) return "nunca";
    if (age.unit === "now") return "hace un momento";
    return `hace ${age.value} ${{ minutes: "min", hours: "h", days: "d" }[age.unit]}`;
  },
  common: {
    close: "Cerrar",
    cancel: "Cancelar",
    draft: "borrador",
    syncedAgo: (ago: string) => `Sincronizado ${ago}`,
    inReview: (n: number) => `${n} en revisión`,
    progressLabel: (done: number, total: number, review: number) => `${done} de ${total} hechas, ${review} en revisión`,
  },
  projects: {
    title: "Proyectos",
    add: "Añadir proyecto",
    addFirst: "Añadir el primero",
    emptyTitle: "Aún no sigues ningún repositorio",
    emptyHint: "Elige un repo y qué markdowns leer; el tablero se calcula solo a partir de sus casillas.",
    tasks: (done: number, total: number) => `${done}/${total} tareas`,
    notSynced: "Sin sincronizar todavía.",
    syncFailed: "Error al sincronizar",
  },
  form: {
    repository: "Repositorio",
    repositoryHint: "Busca entre los repos a los que tiene acceso el token, o escribe propietario/nombre.",
    repositoryPlaceholder: "propietario/repositorio",
    private: "Privado",
    paths: "Ficheros a leer",
    pathsHint: "Un glob por línea. * no cruza carpetas, ** sí.",
    branch: "Rama",
    branchHint: "Vacío = la principal del repo",
    name: "Nombre",
    nameHint: "Vacío = el del repo",
    save: "Guardar y sincronizar",
    create: "Añadir y sincronizar",
  },
  board: {
    back: "Proyectos",
    settings: "Ajustes",
    settingsTitle: "Ajustes del proyecto",
    sync: "Sincronizar",
    lastGoodBoard: "Se muestra el último tablero bueno.",
    noCards: (paths: ReactNode, branch: ReactNode): ReactNode => (
      <>Ningún fichero que case con {paths} tiene casillas en la rama {branch}.</>
    ),
    empty: "Vacío",
    untrack: "Dejar de seguir este repo",
    confirmUntrack: (repository: string) => `¿Dejar de seguir ${repository}? No se toca nada en GitHub.`,
  },
  task: {
    done: "Hecha",
    review: "En revisión",
    pending: "Pendiente",
  },
  auth: {
    signInTitle: "Iniciar sesión",
    password: "Contraseña",
    continue: "Continuar",
    codeTitle: "Verificación en dos pasos",
    code: "Código",
    codeHint: "Los 6 dígitos que muestra tu app de autenticación.",
    signIn: "Entrar",
    useRecoveryCode: "Usar un código de recuperación",
    useAuthenticator: "Usar la app de autenticación",
    recoveryCode: "Código de recuperación",
    recoveryCodeHint: "Uno de los que guardaste al activar el 2FA. Cada uno vale una sola vez.",
    back: "Volver",
    logout: "Cerrar sesión",
    security: "Seguridad",
    retry: "Reintentar",
  },
  setup: {
    title: "Configuración inicial",
    step: (n: number, total: number) => `Paso ${n} de ${total}`,
    codeIntro: "jolo-PM aún no tiene cuenta. Para crearla, usa el código de configuración que el backend escribe en sus logs al arrancar:",
    setupCode: "Código de configuración",
    passwordIntro: "Elige la contraseña. No hay usuario: se entra con ella y con un código de tu móvil.",
    passwordHint: (min: number) => `Al menos ${min} caracteres.`,
    passwordConfirmation: "Repite la contraseña",
    totpIntro: "Escanea el QR con tu app de autenticación (Aegis, 1Password, Google Authenticator…) e introduce el código que te muestra.",
    qrLabel: "Código QR para la app de autenticación",
    manualKey: "¿No puedes escanearlo? Introduce esta clave a mano:",
    activate: "Activar",
    recoveryIntro: "Guarda estos códigos de recuperación en un sitio seguro: cada uno sirve una vez para entrar si pierdes el móvil. No se volverán a mostrar.",
    done: "Ya los he guardado",
  },
  recovery: {
    title: "Códigos de recuperación",
    left: (n: number) => `Te quedan ${n} de 10 códigos de recuperación.`,
    regenerate: "Generar códigos nuevos",
    confirmRegenerate: "Los códigos actuales dejarán de valer. ¿Generar 10 nuevos?",
    fresh: "Estos son tus códigos nuevos; los anteriores ya no valen. No se volverán a mostrar.",
    copy: "Copiar",
    copied: "Copiados",
    download: "Descargar",
    lost: "¿Has perdido la contraseña, el móvil y los códigos? Ejecuta php artisan auth:reset en el servidor: borra la cuenta (no los proyectos) y vuelve a la configuración inicial.",
  },
  errors: {
    network: "No se puede conectar con el servidor.",
    http: (status: number) => `Error ${status}`,
  },
};

export type Messages = typeof es;

const en: Messages = {
  app: {
    tagline: "progress read from your markdown files",
    language: "Language",
  },
  columns: {
    todo: "To do",
    in_progress: "In progress",
    review: "In review",
    done: "Done",
  },
  age: (age) => {
    if (!age) return "never";
    if (age.unit === "now") return "just now";
    return `${age.value} ${{ minutes: "min", hours: "h", days: "d" }[age.unit]} ago`;
  },
  common: {
    close: "Close",
    cancel: "Cancel",
    draft: "draft",
    syncedAgo: (ago) => `Synced ${ago}`,
    inReview: (n) => `${n} in review`,
    progressLabel: (done, total, review) => `${done} of ${total} done, ${review} in review`,
  },
  projects: {
    title: "Projects",
    add: "Add project",
    addFirst: "Add your first one",
    emptyTitle: "You are not tracking any repository yet",
    emptyHint: "Pick a repo and which markdown files to read; the board is computed from their checkboxes.",
    tasks: (done, total) => `${done}/${total} tasks`,
    notSynced: "Not synced yet.",
    syncFailed: "Sync failed",
  },
  form: {
    repository: "Repository",
    repositoryHint: "Search the repos the token can access, or type owner/name.",
    repositoryPlaceholder: "owner/repository",
    private: "Private",
    paths: "Files to read",
    pathsHint: "One glob per line. * does not cross folders, ** does.",
    branch: "Branch",
    branchHint: "Empty = the repo's default branch",
    name: "Name",
    nameHint: "Empty = the repo's name",
    save: "Save and sync",
    create: "Add and sync",
  },
  board: {
    back: "Projects",
    settings: "Settings",
    settingsTitle: "Project settings",
    sync: "Sync",
    lastGoodBoard: "Showing the last good board.",
    noCards: (paths, branch) => (
      <>No file matching {paths} has checkboxes on branch {branch}.</>
    ),
    empty: "Empty",
    untrack: "Stop tracking this repo",
    confirmUntrack: (repository) => `Stop tracking ${repository}? Nothing is changed on GitHub.`,
  },
  task: {
    done: "Done",
    review: "In review",
    pending: "Pending",
  },
  auth: {
    signInTitle: "Sign in",
    password: "Password",
    continue: "Continue",
    codeTitle: "Two-step verification",
    code: "Code",
    codeHint: "The 6 digits shown by your authenticator app.",
    signIn: "Sign in",
    useRecoveryCode: "Use a recovery code",
    useAuthenticator: "Use the authenticator app",
    recoveryCode: "Recovery code",
    recoveryCodeHint: "One of the codes you saved when enabling 2FA. Each one works only once.",
    back: "Back",
    logout: "Sign out",
    security: "Security",
    retry: "Retry",
  },
  setup: {
    title: "Initial setup",
    step: (n, total) => `Step ${n} of ${total}`,
    codeIntro: "jolo-PM has no account yet. To create it, use the setup code the backend writes to its logs on startup:",
    setupCode: "Setup code",
    passwordIntro: "Choose the password. There is no username: you sign in with it and a code from your phone.",
    passwordHint: (min) => `At least ${min} characters.`,
    passwordConfirmation: "Repeat the password",
    totpIntro: "Scan the QR code with your authenticator app (Aegis, 1Password, Google Authenticator…) and enter the code it shows.",
    qrLabel: "QR code for the authenticator app",
    manualKey: "Can't scan it? Enter this key manually:",
    activate: "Activate",
    recoveryIntro: "Store these recovery codes somewhere safe: each one lets you sign in once if you lose your phone. They won't be shown again.",
    done: "I've saved them",
  },
  recovery: {
    title: "Recovery codes",
    left: (n) => `You have ${n} of 10 recovery codes left.`,
    regenerate: "Generate new codes",
    confirmRegenerate: "The current codes will stop working. Generate 10 new ones?",
    fresh: "These are your new codes; the previous ones no longer work. They won't be shown again.",
    copy: "Copy",
    copied: "Copied",
    download: "Download",
    lost: "Lost your password, phone and codes? Run php artisan auth:reset on the server: it deletes the account (not the projects) and goes back to the initial setup.",
  },
  errors: {
    network: "Cannot connect to the server.",
    http: (status) => `Error ${status}`,
  },
};

export const LANGUAGES = ["es", "en"] as const;
export type Language = (typeof LANGUAGES)[number];

/** Lo elegido antes si vale; si no, el primer idioma del navegador que haya; si no, castellano. */
export function detectLanguage(saved: string | null, browser: readonly string[]): Language {
  const isLanguage = (value: unknown): value is Language => LANGUAGES.includes(value as Language);
  if (isLanguage(saved)) return saved;
  for (const tag of browser) {
    const base = tag.toLowerCase().split("-")[0];
    if (isLanguage(base)) return base;
  }
  return "es";
}

export const messages: Record<Language, Messages> = { es, en };
