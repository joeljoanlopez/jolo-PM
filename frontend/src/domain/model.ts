// Modelo del frontend: la forma de los datos que maneja la interfaz.
// No sabe de dónde vienen (eso es el puerto ProjectsGateway).

export type CardStatus = "todo" | "in_progress" | "review" | "done";
export type TaskState = "pending" | "review" | "done";

export interface PullRequestRef {
  number: number;
  title: string;
  url: string;
  draft: boolean;
}

export interface BoardTask {
  text: string;
  depth: number;
  line: number;
  section: string | null;
  state: TaskState;
  /** Números de las PRs abiertas que marcan esta tarea. */
  prs: number[];
}

export interface Card {
  path: string;
  title: string;
  url: string;
  status: CardStatus;
  total: number;
  done: number;
  review: number;
  pullRequests: PullRequestRef[];
  tasks: BoardTask[];
}

export interface Summary {
  total: number;
  done: number;
  review: number;
  columns: Record<CardStatus, number>;
}

export interface Board {
  branch: string;
  repositoryUrl: string;
  generatedAt: string;
  summary: Summary;
  cards: Card[];
}

export interface Project {
  id: number;
  repository: string;
  branch: string | null;
  paths: string[];
  name: string | null;
  label: string;
}

export interface ProjectListItem extends Project {
  summary: Summary | null;
  syncedAt: string | null;
  error: string | null;
}

export interface ProjectBoard {
  project: Project;
  board: Board | null;
  error: string | null;
  attemptedAt: string | null;
}

export interface Repository {
  fullName: string;
  defaultBranch: string;
  private: boolean;
  description: string | null;
  url: string;
}

export interface ProjectInput {
  repository?: string;
  branch: string | null;
  paths: string[];
  name: string | null;
}

/** Paso de la sesión: configuración inicial, contraseña, segundo factor o dentro. */
export type AuthState = "setup" | "login" | "two_factor" | "authenticated";

export interface AuthStatus {
  state: AuthState;
  /** Solo con la sesión iniciada. */
  recoveryCodesLeft?: number;
}

/** Secreto TOTP propuesto en la configuración inicial. */
export interface TotpEnrollment {
  secret: string;
  otpauthUri: string;
}
