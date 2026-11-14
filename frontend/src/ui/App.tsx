import { KanbanSquare, LogOut, ShieldCheck } from "lucide-react";
import { useState } from "react";
import { useRoute } from "./hooks";
import { useI18n } from "./i18n";
import { LanguageSwitcher } from "./components/LanguageSwitcher";
import { Button } from "./components/ui";
import { useSession } from "./auth/AuthGate";
import { SecurityDialog } from "./auth/SecurityDialog";
import { ProjectsPage } from "./projects/ProjectsPage";
import { BoardPage } from "./board/BoardPage";

export function App() {
  const route = useRoute();
  const { t } = useI18n();
  const session = useSession();
  const [security, setSecurity] = useState(false);

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
            <Button variant="ghost" className="!px-2" onClick={() => setSecurity(true)} aria-label={t.auth.security} title={t.auth.security}>
              <ShieldCheck className="size-4" aria-hidden />
            </Button>
            <Button variant="ghost" className="!px-2" onClick={() => void session.logout()} aria-label={t.auth.logout} title={t.auth.logout}>
              <LogOut className="size-4" aria-hidden />
            </Button>
          </div>
        </div>
      </header>
      <main className="mx-auto max-w-7xl px-4 py-6">
        {route.name === "project" ? <BoardPage key={route.id} id={route.id} /> : <ProjectsPage />}
      </main>
      {security && <SecurityDialog onClose={() => setSecurity(false)} />}
    </div>
  );
}
