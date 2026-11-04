// Puerto: lo que la interfaz necesita del backend. La UI solo depende de esta
// interfaz; el adaptador concreto (HTTP, datos de prueba…) se elige en main.tsx.
import type { ProjectBoard, ProjectInput, ProjectListItem, Repository } from "../domain/model";

export interface ProjectsGateway {
  listProjects(): Promise<ProjectListItem[]>;
  getBoard(id: number): Promise<ProjectBoard>;
  createProject(input: ProjectInput): Promise<ProjectBoard>;
  updateProject(id: number, input: ProjectInput): Promise<ProjectBoard>;
  deleteProject(id: number): Promise<void>;
  syncProject(id: number): Promise<ProjectBoard>;
  listRepositories(): Promise<Repository[]>;
}

/** Error de la API con la forma {error: {code, message, fields}}. */
export class GatewayError extends Error {
  constructor(
    message: string,
    readonly status: number,
    readonly code: string,
    readonly fields: Record<string, string> = {},
  ) {
    super(message);
  }
}
