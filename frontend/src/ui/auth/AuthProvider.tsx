import { createContext, useCallback, useContext, useEffect, useState, type ReactNode } from "react";
import type { AuthState } from "../../domain/auth";
import type { AuthGateway } from "../../ports/AuthGateway";

type AuthContextValue = {
  gateway: AuthGateway;
  /** null mientras se pregunta al backend. */
  state: AuthState | null;
  error: Error | null;
  setState: (state: AuthState) => void;
  /** Vuelve a preguntar al backend en qué paso estamos. */
  refresh: () => Promise<void>;
  logout: () => Promise<void>;
};

const AuthContext = createContext<AuthContextValue | null>(null);

/**
 * Estado del login para toda la app. Es también el guardián: si cualquier
 * petición responde 401 (sesión caducada, cuenta reiniciada…), vuelve a
 * preguntar el estado y la app enseña el login o el asistente.
 */
export function AuthProvider({ gateway, children }: { gateway: AuthGateway; children: ReactNode }) {
  const [state, setState] = useState<AuthState | null>(null);
  const [error, setError] = useState<Error | null>(null);

  const refresh = useCallback(async () => {
    try {
      setState(await gateway.state());
      setError(null);
    } catch (e) {
      setError(e as Error);
    }
  }, [gateway]);

  useEffect(() => {
    void refresh();
    return gateway.onUnauthorized(() => void refresh());
  }, [gateway, refresh]);

  const logout = useCallback(async () => {
    try {
      await gateway.logout();
    } finally {
      await refresh();
    }
  }, [gateway, refresh]);

  return (
    <AuthContext.Provider value={{ gateway, state, error, setState, refresh, logout }}>{children}</AuthContext.Provider>
  );
}

export function useAuth(): AuthContextValue {
  const auth = useContext(AuthContext);
  if (!auth) throw new Error("Falta <AuthProvider>");
  return auth;
}
