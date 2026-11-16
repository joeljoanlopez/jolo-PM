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
    groupBy: "Una tarjeta por",
    groupByHint: "Por sección: cada encabezado ## con casillas es una tarjeta.",
    groupByOptions: { file: "Fichero", section: "Sección (##)" },
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
    checking: "Comprobando la sesión…",
    logout: "Cerrar sesión",
    recoveryCodes: "Códigos de recuperación",
    continue: "Continuar",
    back: "Atrás",
    password: "Contraseña",
    passwordRepeat: "Repite la contraseña",
    passwordHint: (min: number) => `Mínimo ${min} caracteres. Para cambiarla más adelante: php artisan auth:reset.`,
    code: "Código de 6 dígitos",
    setup: {
      title: "Configuración inicial",
      intro: "jolo-PM aún no tiene cuenta. Créala en cuatro pasos: solo hay un usuario y el segundo factor es obligatorio.",
      steps: ["Código", "Contraseña", "2FA", "Recuperación"],
      codeLabel: "Código de configuración",
      codeHint: (
        <>
          Está en los logs del servidor: <code className="font-mono">docker compose logs backend</code>. Vale una sola vez.
        </>
      ) as ReactNode,
      totpIntro: "Escanea el QR con tu app de autenticación (Aegis, 1Password, Google Authenticator…) y escribe el código que te da.",
      manualSecret: "¿No puedes escanearlo? Introduce esta clave a mano:",
      openInApp: "Abrir en la app de este dispositivo",
      create: "Crear la cuenta",
      codesIntro: "Guarda estos códigos en un sitio seguro. Cada uno sirve una vez para entrar si pierdes el móvil, y no se vuelven a enseñar.",
      codesSaved: "Los he guardado",
    },
    login: {
      title: "Iniciar sesión",
      submit: "Entrar",
    },
    twoFactor: {
      title: "Verificación en dos pasos",
      intro: "Escribe el código de tu app de autenticación.",
      recoveryIntro: "Escribe uno de tus códigos de recuperación. Cada uno vale una sola vez.",
      recoveryCode: "Código de recuperación",
      useRecovery: "Usar un código de recuperación",
      useTotp: "Usar el código de la app",
      verify: "Verificar",
      restart: "Volver a la contraseña",
      lowCodes: (left: number) =>
        left === 0
          ? "Has usado tu último código de recuperación. Genera otros desde «Códigos de recuperación»."
          : `Te quedan ${left} códigos de recuperación.`,
    },
    regenerate: {
      intro: "Genera 10 códigos nuevos; los anteriores dejan de valer. Confírmalo con un código de tu app de autenticación.",
      submit: "Generar códigos nuevos",
    },
    copy: "Copiar",
    copied: "Copiado",
    download: "Descargar .txt",
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
    groupBy: "One card per",
    groupByHint: "By section: each ## heading with checkboxes is a card.",
    groupByOptions: { file: "File", section: "Section (##)" },
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
    checking: "Checking your session…",
    logout: "Sign out",
    recoveryCodes: "Recovery codes",
    continue: "Continue",
    back: "Back",
    password: "Password",
    passwordRepeat: "Repeat the password",
    passwordHint: (min) => `At least ${min} characters. To change it later: php artisan auth:reset.`,
    code: "6-digit code",
    setup: {
      title: "Initial setup",
      intro: "jolo-PM has no account yet. Create it in four steps: there is a single user and the second factor is mandatory.",
      steps: ["Code", "Password", "2FA", "Recovery"],
      codeLabel: "Setup code",
      codeHint: (
        <>
          It is in the server logs: <code className="font-mono">docker compose logs backend</code>. It only works once.
        </>
      ),
      totpIntro: "Scan the QR code with your authenticator app (Aegis, 1Password, Google Authenticator…) and type the code it shows.",
      manualSecret: "Can't scan it? Enter this key by hand:",
      openInApp: "Open in this device's app",
      create: "Create the account",
      codesIntro: "Keep these codes somewhere safe. Each one signs you in once if you lose your phone, and they will not be shown again.",
      codesSaved: "I have saved them",
    },
    login: {
      title: "Sign in",
      submit: "Sign in",
    },
    twoFactor: {
      title: "Two-step verification",
      intro: "Type the code from your authenticator app.",
      recoveryIntro: "Type one of your recovery codes. Each one only works once.",
      recoveryCode: "Recovery code",
      useRecovery: "Use a recovery code",
      useTotp: "Use the app code",
      verify: "Verify",
      restart: "Back to the password",
      lowCodes: (left) =>
        left === 0
          ? "You have used your last recovery code. Generate new ones from “Recovery codes”."
          : `You have ${left} recovery codes left.`,
    },
    regenerate: {
      intro: "Generate 10 new codes; the previous ones stop working. Confirm it with a code from your authenticator app.",
      submit: "Generate new codes",
    },
    copy: "Copy",
    copied: "Copied",
    download: "Download .txt",
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
