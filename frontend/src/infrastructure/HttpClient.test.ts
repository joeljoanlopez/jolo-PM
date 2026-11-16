import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { GatewayError } from "../ports/ProjectsGateway";
import { HttpClient, readXsrfToken } from "./HttpClient";

describe("readXsrfToken", () => {
  it("lee y decodifica la cookie XSRF-TOKEN entre las demás", () => {
    expect(readXsrfToken("a=1; XSRF-TOKEN=eyJ%3D%3D; jolo-pm-session=x")).toBe("eyJ==");
    expect(readXsrfToken("a=1")).toBeNull();
    expect(readXsrfToken("")).toBeNull();
  });
});

describe("HttpClient", () => {
  const fetchMock = vi.fn();

  beforeEach(() => {
    vi.stubGlobal("fetch", fetchMock);
    vi.stubGlobal("document", { cookie: "XSRF-TOKEN=token%3D" });
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    fetchMock.mockReset();
  });

  const respond = (status: number, body: unknown = null) =>
    fetchMock.mockResolvedValueOnce(new Response(body === null ? null : JSON.stringify(body), { status }));

  it("manda el token CSRF solo en las peticiones que cambian algo", async () => {
    const http = new HttpClient("/api", () => "en");
    respond(200, { ok: true });
    respond(204);

    await http.request("GET", "/auth/me");
    await http.request("POST", "/auth/logout");

    const [, get] = fetchMock.mock.calls[0]!;
    const [, post] = fetchMock.mock.calls[1]!;
    expect(get.headers["X-XSRF-TOKEN"]).toBeUndefined();
    expect(get.headers["Accept-Language"]).toBe("en");
    expect(post.headers["X-XSRF-TOKEN"]).toBe("token=");
    expect(post.credentials).toBe("same-origin");
  });

  it("avisa a los interesados ante un 401 y lanza el error de la API", async () => {
    const http = new HttpClient();
    const listener = vi.fn();
    const unsubscribe = http.onUnauthorized(listener);
    respond(401, { error: { code: "unauthenticated", message: "Inicia sesión para continuar." } });

    const error = await http.request("GET", "/projects").catch((e: unknown) => e);

    expect(listener).toHaveBeenCalledOnce();
    expect(error).toBeInstanceOf(GatewayError);
    expect(error).toMatchObject({ status: 401, code: "unauthenticated", message: "Inicia sesión para continuar." });

    unsubscribe();
    respond(401);
    await http.request("GET", "/projects").catch(() => undefined);
    expect(listener).toHaveBeenCalledOnce();
  });
});
