import { KanbanSquare, KeyRound, LogOut } from "lucide-react";
import { useState } from "react";
import { useRoute } from "./hooks";
import { useI18n } from "./i18n";
import { LanguageSwitcher } from "./components/LanguageSwitcher";
import { Button, ErrorBanner, Spinner } from "./components/ui";
import { ProjectsPage } from "./projects/ProjectsPage";
import { BoardPage } from "./board/BoardPage";
import { useAuth } from "./auth/AuthProvider";
import { SetupWizard } from "./auth/SetupWizard";
import { LoginPage, TwoFactorPage } from "./auth/LoginPage";
import { RecoveryCodesDialog } from "./auth/RecoveryCodesDialog";

export function App() {
  const { t } = useI18n();
  const auth = useAuth();
  const [recoveryOpen, setRecoveryOpen] = useState(false);
  const authenticated = auth.state === "authenticated";

  return (
    <div className="min-h-screen">
      <header className="border-b border-zinc-200 bg-white/80 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/80">
        <div className="mx-auto flex max-w-7xl items-center gap-2 px-4 py-3">
          <a href="#/" className="flex items-center gap-2 font-semibold tracking-tight">
            <KanbanSquare className="size-5 text-indigo-600 dark:text-indigo-400" aria-hidden />
            jolo-PM
          </a>
          <span className="hidden text-sm text-zinc-500 sm:inline">· {t.app.tagline}</span>
          <div className="ml-auto flex items-center gap-1">
            <LanguageSwitcher />
            {authenticated && (
              <>
                <Button variant="ghost" className="!px-2" onClick={() => setRecoveryOpen(true)} title={t.auth.recoveryCodes} aria-label={t.auth.recoveryCodes}>
                  <KeyRound className="size-4" aria-hidden />
                </Button>
                <Button variant="ghost" className="!px-2" onClick={() => void auth.logout()} title={t.auth.logout} aria-label={t.auth.logout}>
                  <LogOut className="size-4" aria-hidden />
                </Button>
              </>
            )}
          </div>
        </div>
      </header>
      <main className="mx-auto max-w-7xl px-4 py-6">
        <Screen />
      </main>
      {recoveryOpen && authenticated && <RecoveryCodesDialog onClose={() => setRecoveryOpen(false)} />}
    </div>
  );
}

/** Lo que toca enseñar según el paso del login; los tableros, solo con sesión. */
function Screen() {
  const { t } = useI18n();
  const { state, error } = useAuth();

  switch (state) {
    case "setup":
      return <SetupWizard />;
    case "login":
      return <LoginPage />;
    case "two_factor":
      return <TwoFactorPage />;
    case "authenticated":
      return <Routes />;
    default:
      return error ? (
        <ErrorBanner>{error.message}</ErrorBanner>
      ) : (
        <div className="flex justify-center py-16 text-zinc-500" aria-label={t.auth.checking}>
          <Spinner className="size-6" />
        </div>
      );
  }
}

function Routes() {
  const route = useRoute();
  return route.name === "project" ? <BoardPage key={route.id} id={route.id} /> : <ProjectsPage />;
}
