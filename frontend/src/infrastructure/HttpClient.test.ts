import { afterEach, describe, expect, it, vi } from "vitest";
import { GatewayError } from "../ports/ProjectsGateway";
import { HttpClient, readCookie } from "./HttpClient";

const json = (status: number, body: unknown) =>
  new Response(body === null ? null : JSON.stringify(body), { status, headers: { "Content-Type": "application/json" } });

function stubFetch(...responses: Response[]) {
  const fetch = vi.fn<typeof globalThis.fetch>();
  responses.forEach((r) => fetch.mockResolvedValueOnce(r));
  vi.stubGlobal("fetch", fetch);
  return fetch;
}

const headersOf = (fetch: ReturnType<typeof stubFetch>, call: number) => fetch.mock.calls[call]![1]?.headers as Record<string, string>;

afterEach(() => vi.unstubAllGlobals());

describe("HttpClient", () => {
  it("lee cookies decodificadas", () => {
    expect(readCookie("a=1; XSRF-TOKEN=eyJ%3D%3D; b=2", "XSRF-TOKEN")).toBe("eyJ==");
    expect(readCookie("a=1", "XSRF-TOKEN")).toBeNull();
  });

  it("manda la cookie XSRF-TOKEN en la cabecera solo en las peticiones que modifican", async () => {
    const fetch = stubFetch(json(200, []), json(200, {}));
    const http = new HttpClient("/api", () => "en", () => "XSRF-TOKEN=tok%3D");

    await http.request("GET", "/projects");
    await http.request("POST", "/projects", { a: 1 });

    expect(headersOf(fetch, 0)["X-XSRF-TOKEN"]).toBeUndefined();
    expect(headersOf(fetch, 1)["X-XSRF-TOKEN"]).toBe("tok=");
    expect(headersOf(fetch, 1)["Accept-Language"]).toBe("en");
    expect(fetch.mock.calls[1]![1]?.credentials).toBe("same-origin");
  });

  it("avisa ante un 401 y lanza el error de la API", async () => {
    stubFetch(json(401, { error: { code: "unauthenticated", message: "Inicia sesión" } }));
    const http = new HttpClient("/api", () => "es", () => "");
    const lost = vi.fn();
    http.onUnauthorized(lost);

    const error = await http.request("GET", "/projects").catch((e: unknown) => e);

    expect(lost).toHaveBeenCalledOnce();
    expect(error).toBeInstanceOf(GatewayError);
    expect((error as GatewayError).code).toBe("unauthenticated");
  });

  it("con un 419 renueva el token y reintenta una vez", async () => {
    const fetch = stubFetch(json(419, { error: { code: "csrf_mismatch", message: "x" } }), json(200, { state: "login" }), json(204, null));
    const http = new HttpClient("/api", () => "es", () => "XSRF-TOKEN=t");

    await http.request("POST", "/auth/logout");

    expect(fetch.mock.calls.map((c) => c[0])).toEqual(["/api/auth/logout", "/api/auth/me", "/api/auth/logout"]);
  });
});
