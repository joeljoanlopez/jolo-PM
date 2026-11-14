import { createContext, useCallback, useContext, useEffect, useState, type ReactNode } from "react";
import type { AuthStatus } from "../../domain/model";
import { Button, ErrorBanner, Spinner } from "../components/ui";
import { useAuthGateway } from "../gateway";
import { useI18n } from "../i18n";
import { AuthLayout } from "./AuthLayout";
import { LoginPage } from "./LoginPage";
import { SetupWizard } from "./SetupWizard";

interface Session {
  status: AuthStatus;
  refresh: () => Promise<void>;
  logout: () => Promise<void>;
}

const SessionContext = createContext<Session | null>(null);

export function useSession(): Session {
  const session = useContext(SessionContext);
  if (!session) throw new Error("Falta <AuthGate>");
  return session;
}

/**
 * Guardián de la app: sin sesión iniciada enseña la configuración inicial o el
 * login en vez de `children`. Cualquier 401 de la API vuelve a preguntar en qué
 * paso está la sesión, y por tanto manda al login si se ha cerrado.
 */
export function AuthGate({ children }: { children: ReactNode }) {
  const auth = useAuthGateway();
  const { t } = useI18n();
  const [status, setStatus] = useState<AuthStatus | null>(null);
  const [error, setError] = useState<Error | null>(null);

  const refresh = useCallback(async () => {
    try {
      setStatus(await auth.status());
      setError(null);
    } catch (e) {
      setError(e as Error);
    }
  }, [auth]);

  const logout = useCallback(async () => {
    await auth.logout().catch(() => undefined);
    await refresh();
  }, [auth, refresh]);

  useEffect(() => {
    void refresh();
    return auth.onSessionLost(() => void refresh());
  }, [auth, refresh]);

  if (status === null) {
    return error ? (
      <AuthLayout title="jolo-PM">
        <div className="space-y-4">
          <ErrorBanner>{error.message}</ErrorBanner>
          <Button onClick={() => void refresh()}>{t.auth.retry}</Button>
        </div>
      </AuthLayout>
    ) : (
      <div className="flex min-h-screen items-center justify-center text-zinc-500">
        <Spinner className="size-6" />
      </div>
    );
  }

  switch (status.state) {
    case "setup":
      return <SetupWizard onDone={() => void refresh()} />;
    case "login":
    case "two_factor":
      return <LoginPage key={status.state} pendingSecondFactor={status.state === "two_factor"} onSignedIn={() => void refresh()} />;
    default:
      return <SessionContext.Provider value={{ status, refresh, logout }}>{children}</SessionContext.Provider>;
  }
}
