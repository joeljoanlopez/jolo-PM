// Adaptador del puerto ProjectsGateway sobre la API REST del backend.
import type { ProjectBoard, ProjectInput, ProjectListItem, Repository } from "../domain/model";
import { GatewayError, type ProjectsGateway } from "../ports/ProjectsGateway";
import { messages, type Language } from "../i18n/messages";

export class HttpProjectsGateway implements ProjectsGateway {
  /** `language` da el idioma de cada petición: la API responde los errores en él. */
  constructor(
    private readonly baseUrl = "/api",
    private readonly language: () => Language = () => "es",
  ) {}

  listProjects() {
    return this.request<ProjectListItem[]>("GET", "/projects");
  }

  getBoard(id: number) {
    return this.request<ProjectBoard>("GET", `/projects/${id}/board`);
  }

  createProject(input: ProjectInput) {
    return this.request<ProjectBoard>("POST", "/projects", input);
  }

  updateProject(id: number, input: ProjectInput) {
    const { repository: _ignored, ...editable } = input;
    return this.request<ProjectBoard>("PATCH", `/projects/${id}`, editable);
  }

  async deleteProject(id: number) {
    await this.request<null>("DELETE", `/projects/${id}`);
  }

  syncProject(id: number) {
    return this.request<ProjectBoard>("POST", `/projects/${id}/sync`);
  }

  listRepositories() {
    return this.request<Repository[]>("GET", "/repositories");
  }

  private async request<T>(method: string, path: string, body?: unknown): Promise<T> {
    const language = this.language();
    let response: Response;
    try {
      response = await fetch(this.baseUrl + path, {
        method,
        headers: { Accept: "application/json", "Accept-Language": language, ...(body ? { "Content-Type": "application/json" } : {}) },
        body: body ? JSON.stringify(body) : undefined,
      });
    } catch {
      throw new GatewayError(messages[language].errors.network, 0, "network");
    }

    if (response.status === 204) return null as T;

    const data: unknown = await response.json().catch(() => null);
    if (!response.ok) {
      const error = (data as { error?: { code?: string; message?: string; fields?: Record<string, string> } } | null)?.error;
      throw new GatewayError(
        error?.message ?? messages[language].errors.http(response.status),
        response.status,
        error?.code ?? "http_error",
        error?.fields ?? {},
      );
    }
    return data as T;
  }
}
