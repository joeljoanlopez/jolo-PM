import { ArrowLeft, ExternalLink, GitBranch, RefreshCw, Settings, Trash2 } from "lucide-react";
import { useState } from "react";
import { COLUMNS, groupByStatus, relativeTime } from "../../domain/board";
import type { Card } from "../../domain/model";
import { ProgressBar } from "../components/ProgressBar";
import { Button, ErrorBanner, Modal, Spinner } from "../components/ui";
import { useGateway } from "../gateway";
import { navigate, useAsync } from "../hooks";
import { ProjectForm } from "../projects/ProjectForm";
import { CardDetail } from "./CardDetail";
import { CardView } from "./CardView";

const columnAccent: Record<Card["status"], string> = {
  todo: "bg-zinc-400",
  in_progress: "bg-sky-500",
  review: "bg-amber-400",
  done: "bg-emerald-500",
};

export function BoardPage({ id }: { id: number }) {
  const gateway = useGateway();
  // Refresco cada minuto: el worker sincroniza con GitHub y aquí solo se relee.
  const state = useAsync(() => gateway.getBoard(id), [id], 60_000);
  const [syncing, setSyncing] = useState(false);
  const [syncError, setSyncError] = useState<string | null>(null);
  const [selected, setSelected] = useState<Card | null>(null);
  const [editing, setEditing] = useState(false);

  async function sync() {
    setSyncing(true);
    setSyncError(null);
    try {
      state.setData(await gateway.syncProject(id));
    } catch (e) {
      setSyncError((e as Error).message);
      void state.reload();
    } finally {
      setSyncing(false);
    }
  }

  async function remove() {
    if (!state.data || !window.confirm(`¿Dejar de seguir ${state.data.project.repository}? No se toca nada en GitHub.`)) return;
    await gateway.deleteProject(id);
    navigate("#/");
  }

  if (state.error && !state.data) {
    return (
      <div className="space-y-4">
        <BackLink />
        <ErrorBanner>{state.error.message}</ErrorBanner>
      </div>
    );
  }
  if (!state.data) {
    return <div className="flex justify-center py-16 text-zinc-500"><Spinner className="size-6" /></div>;
  }

  const { project, board, error } = state.data;
  const groups = groupByStatus(board?.cards ?? []);

  return (
    <div className="space-y-5">
      <div className="space-y-3">
        <BackLink />
        <div className="flex flex-wrap items-start justify-between gap-3">
          <div className="min-w-0">
            <h1 className="truncate text-xl font-semibold tracking-tight">{project.label}</h1>
            <div className="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-zinc-500">
              {board ? (
                <a href={board.repositoryUrl} target="_blank" rel="noreferrer" className="inline-flex items-center gap-1 hover:text-indigo-600 dark:hover:text-indigo-400">
                  {project.repository} <ExternalLink className="size-3.5" aria-hidden />
                </a>
              ) : (
                <span>{project.repository}</span>
              )}
              {board && (
                <span className="inline-flex items-center gap-1"><GitBranch className="size-3.5" aria-hidden />{board.branch}</span>
              )}
              <span>Sincronizado {relativeTime(board?.generatedAt ?? null)}</span>
            </div>
          </div>
          <div className="flex gap-2">
            <Button onClick={() => setEditing(true)} aria-label="Ajustes del proyecto">
              <Settings className="size-4" aria-hidden /> <span className="hidden sm:inline">Ajustes</span>
            </Button>
            <Button variant="primary" onClick={sync} disabled={syncing}>
              {syncing ? <Spinner /> : <RefreshCw className="size-4" aria-hidden />} Sincronizar
            </Button>
          </div>
        </div>

        {board && (
          <div className="flex items-center gap-3">
            <ProgressBar total={board.summary.total} done={board.summary.done} review={board.summary.review} className="flex-1" />
            <span className="text-sm text-zinc-500 tabular-nums">
              {board.summary.done}/{board.summary.total}
              {board.summary.review > 0 && <span className="text-amber-600 dark:text-amber-400"> · {board.summary.review} en revisión</span>}
            </span>
          </div>
        )}

        {(syncError ?? error) && (
          <ErrorBanner>
            {syncError ?? error}
            {board && <span className="block text-xs opacity-80">Se muestra el último tablero bueno.</span>}
          </ErrorBanner>
        )}
      </div>

      {board && board.cards.length === 0 && (
        <p className="rounded-xl border border-dashed border-zinc-300 px-6 py-12 text-center text-sm text-zinc-500 dark:border-zinc-700">
          Ningún fichero que case con <span className="font-mono">{project.paths.join(", ")}</span> tiene casillas en la rama <span className="font-mono">{board.branch}</span>.
        </p>
      )}

      {board && board.cards.length > 0 && (
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          {COLUMNS.map((column) => (
            <section key={column.status} aria-label={column.label} className="flex flex-col rounded-xl bg-zinc-100/80 p-2 dark:bg-zinc-900/60">
              <h2 className="flex items-center gap-2 px-2 py-1.5 text-sm font-semibold">
                <span className={`size-2 rounded-full ${columnAccent[column.status]}`} aria-hidden />
                {column.label}
                <span className="ml-auto rounded-full bg-zinc-200 px-2 text-xs font-medium tabular-nums dark:bg-zinc-800">{groups[column.status].length}</span>
              </h2>
              <div className="mt-1 space-y-2">
                {groups[column.status].map((card) => (
                  <CardView key={card.path} card={card} onOpen={() => setSelected(card)} />
                ))}
                {groups[column.status].length === 0 && <p className="px-2 py-4 text-center text-xs text-zinc-400">Vacío</p>}
              </div>
            </section>
          ))}
        </div>
      )}

      {selected && <CardDetail card={selected} onClose={() => setSelected(null)} />}

      {editing && (
        <Modal title="Ajustes del proyecto" onClose={() => setEditing(false)}>
          <ProjectForm
            project={project}
            onCancel={() => setEditing(false)}
            onSaved={(updated) => {
              state.setData(updated);
              setEditing(false);
            }}
          />
          <div className="mt-6 border-t border-zinc-200 pt-4 dark:border-zinc-800">
            <Button variant="danger" onClick={remove}>
              <Trash2 className="size-4" aria-hidden /> Dejar de seguir este repo
            </Button>
          </div>
        </Modal>
      )}
    </div>
  );
}

function BackLink() {
  return (
    <a href="#/" className="inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100">
      <ArrowLeft className="size-4" aria-hidden /> Proyectos
    </a>
  );
}
