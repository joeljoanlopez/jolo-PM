import { KanbanSquare } from "lucide-react";
import type { ReactNode } from "react";
import { LanguageSwitcher } from "../components/LanguageSwitcher";

/** Pantalla centrada de las páginas de acceso (login, código, configuración inicial). */
export function AuthLayout({ title, subtitle, children }: { title: string; subtitle?: string; children: ReactNode }) {
  return (
    <div className="flex min-h-screen flex-col">
      <header className="mx-auto flex w-full max-w-7xl items-center gap-2 px-4 py-3">
        <span className="flex items-center gap-2 font-semibold tracking-tight">
          <KanbanSquare className="size-5 text-indigo-600 dark:text-indigo-400" aria-hidden />
          jolo-PM
        </span>
        <div className="ml-auto">
          <LanguageSwitcher />
        </div>
      </header>
      <main className="flex flex-1 items-start justify-center px-4 pt-[8vh] pb-12">
        <div className="w-full max-w-md rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
          <h1 className="text-lg font-semibold tracking-tight">{title}</h1>
          {subtitle && <p className="mt-0.5 text-xs font-medium tracking-wide text-zinc-500 uppercase">{subtitle}</p>}
          <div className="mt-5">{children}</div>
        </div>
      </main>
    </div>
  );
}
