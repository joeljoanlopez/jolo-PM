// Peticiones a la API: idioma, sesión en cookie, CSRF y errores con forma fija.
// Lo comparten los adaptadores de proyectos y de login.
import { GatewayError } from "../ports/ProjectsGateway";
import { messages, type Language } from "../i18n/messages";

const SAFE_METHODS = new Set(["GET", "HEAD", "OPTIONS"]);

/** Valor de la cookie XSRF-TOKEN que pone Laravel; se devuelve en la cabecera X-XSRF-TOKEN. */
export function readXsrfToken(cookies: string): string | null {
  for (const part of cookies.split(";")) {
    const [name, ...value] = part.trim().split("=");
    if (name === "XSRF-TOKEN") return decodeURIComponent(value.join("="));
  }
  return null;
}

export class HttpClient {
  private readonly unauthorizedListeners = new Set<() => void>();

  /** `language` da el idioma de cada petición: la API responde los errores en él. */
  constructor(
    private readonly baseUrl = "/api",
    private readonly language: () => Language = () => "es",
  ) {}

  onUnauthorized(listener: () => void): () => void {
    this.unauthorizedListeners.add(listener);
    return () => this.unauthorizedListeners.delete(listener);
  }

  async request<T>(method: string, path: string, body?: unknown): Promise<T> {
    const language = this.language();
    const headers: Record<string, string> = { Accept: "application/json", "Accept-Language": language };
    if (body !== undefined) headers["Content-Type"] = "application/json";
    const xsrf = SAFE_METHODS.has(method) ? null : readXsrfToken(document.cookie);
    if (xsrf) headers["X-XSRF-TOKEN"] = xsrf;

    let response: Response;
    try {
      response = await fetch(this.baseUrl + path, {
        method,
        headers,
        credentials: "same-origin",
        body: body !== undefined ? JSON.stringify(body) : undefined,
      });
    } catch {
      throw new GatewayError(messages[language].errors.network, 0, "network");
    }

    if (response.status === 401) this.unauthorizedListeners.forEach((listener) => listener());
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
