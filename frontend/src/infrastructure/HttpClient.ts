// Cliente HTTP común de los adaptadores: idioma, cookies de sesión, CSRF y
// aviso cuando la API responde 401 (sesión cerrada o caducada).
import { GatewayError } from "../ports/ProjectsGateway";
import { messages, type Language } from "../i18n/messages";

const SAFE_METHODS = ["GET", "HEAD", "OPTIONS"];

/** Valor de una cookie de `document.cookie`, o null si no está. */
export function readCookie(cookies: string, name: string): string | null {
  for (const part of cookies.split(";")) {
    const [key, ...value] = part.trim().split("=");
    if (key === name) return decodeURIComponent(value.join("="));
  }
  return null;
}

export class HttpClient {
  private readonly unauthorizedListeners = new Set<() => void>();

  constructor(
    private readonly baseUrl = "/api",
    /** Idioma de cada petición: la API responde los errores en él. */
    private readonly language: () => Language = () => "es",
    private readonly cookies: () => string = () => (typeof document === "undefined" ? "" : document.cookie),
  ) {}

  /** Avisa cada vez que una petición responde 401. */
  onUnauthorized(listener: () => void): () => void {
    this.unauthorizedListeners.add(listener);
    return () => this.unauthorizedListeners.delete(listener);
  }

  async request<T>(method: string, path: string, body?: unknown, retryCsrf = true): Promise<T> {
    const language = this.language();
    const headers: Record<string, string> = { Accept: "application/json", "Accept-Language": language };
    if (body !== undefined) headers["Content-Type"] = "application/json";
    // Protección CSRF de Laravel: la cookie XSRF-TOKEN vuelve en esta cabecera.
    const xsrf = readCookie(this.cookies(), "XSRF-TOKEN");
    if (xsrf && !SAFE_METHODS.includes(method)) headers["X-XSRF-TOKEN"] = xsrf;

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

    // Token CSRF caducado (p. ej. la pestaña llevaba días abierta): una
    // petición de lectura renueva la cookie y se reintenta una vez.
    if (response.status === 419 && retryCsrf) {
      await fetch(this.baseUrl + "/auth/me", { headers: { Accept: "application/json" }, credentials: "same-origin" }).catch(() => null);
      return this.request<T>(method, path, body, false);
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
