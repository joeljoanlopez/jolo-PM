// Adaptador del puerto ProjectsGateway sobre la API REST del backend.
import type { ProjectBoard, ProjectInput, ProjectListItem, Repository } from "../domain/model";
import type { ProjectsGateway } from "../ports/ProjectsGateway";
import type { HttpClient } from "./HttpClient";

export class HttpProjectsGateway implements ProjectsGateway {
  constructor(private readonly http: HttpClient) {}

  listProjects() {
    return this.http.request<ProjectListItem[]>("GET", "/projects");
  }

  getBoard(id: number) {
    return this.http.request<ProjectBoard>("GET", `/projects/${id}/board`);
  }

  createProject(input: ProjectInput) {
    return this.http.request<ProjectBoard>("POST", "/projects", input);
  }

  updateProject(id: number, input: ProjectInput) {
    const { repository: _ignored, ...editable } = input;
    return this.http.request<ProjectBoard>("PATCH", `/projects/${id}`, editable);
  }

  async deleteProject(id: number) {
    await this.http.request<null>("DELETE", `/projects/${id}`);
  }

  syncProject(id: number) {
    return this.http.request<ProjectBoard>("POST", `/projects/${id}/sync`);
  }

  listRepositories() {
    return this.http.request<Repository[]>("GET", "/repositories");
  }
}
