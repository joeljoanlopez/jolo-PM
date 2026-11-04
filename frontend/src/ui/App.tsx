import { KanbanSquare } from "lucide-react";
import { useRoute } from "./hooks";
import { ProjectsPage } from "./projects/ProjectsPage";
import { BoardPage } from "./board/BoardPage";

export function App() {
  const route = useRoute();

  return (
    <div className="min-h-screen">
      <header className="border-b border-zinc-200 bg-white/80 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/80">
        <div className="mx-auto flex max-w-7xl items-center gap-2 px-4 py-3">
          <a href="#/" className="flex items-center gap-2 font-semibold tracking-tight">
            <KanbanSquare className="size-5 text-indigo-600 dark:text-indigo-400" aria-hidden />
            jolo-PM
          </a>
          <span className="hidden text-sm text-zinc-500 sm:inline">· progreso leído de tus markdowns</span>
        </div>
      </header>
      <main className="mx-auto max-w-7xl px-4 py-6">
        {route.name === "project" ? <BoardPage key={route.id} id={route.id} /> : <ProjectsPage />}
      </main>
    </div>
  );
}
