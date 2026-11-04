import { AlertTriangle, FolderGit2, Plus } from "lucide-react";
import { useState } from "react";
import { COLUMNS, age } from "../../domain/board";
import type { ProjectListItem } from "../../domain/model";
import { ProgressBar } from "../components/ProgressBar";
import { Button, ErrorBanner, Modal, Spinner } from "../components/ui";
import { useGateway } from "../gateway";
import { navigate, useAsync } from "../hooks";
import { useI18n } from "../i18n";
import { ProjectForm } from "./ProjectForm";

export function ProjectsPage() {
  const gateway = useGateway();
  const { t } = useI18n();
  const projects = useAsync(() => gateway.listProjects(), [], 60_000);
  const [adding, setAdding] = useState(false);

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between gap-4">
        <h1 className="text-xl font-semibold tracking-tight">{t.projects.title}</h1>
        <Button variant="primary" onClick={() => setAdding(true)}>
          <Plus className="size-4" aria-hidden /> {t.projects.add}
        </Button>
      </div>

      {projects.error && <ErrorBanner>{projects.error.message}</ErrorBanner>}

      {projects.data === null && projects.loading && (
        <div className="flex justify-center py-16 text-zinc-500"><Spinner className="size-6" /></div>
      )}

      {projects.data?.length === 0 && (
        <div className="rounded-xl border border-dashed border-zinc-300 px-6 py-16 text-center dark:border-zinc-700">
          <FolderGit2 className="mx-auto size-8 text-zinc-400" aria-hidden />
          <p className="mt-3 font-medium">{t.projects.emptyTitle}</p>
          <p className="mt-1 text-sm text-zinc-500">{t.projects.emptyHint}</p>
          <Button variant="primary" className="mt-4" onClick={() => setAdding(true)}>
            <Plus className="size-4" aria-hidden /> {t.projects.addFirst}
          </Button>
        </div>
      )}

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {projects.data?.map((p) => <ProjectSummaryCard key={p.id} project={p} />)}
      </div>

      {adding && (
        <Modal title={t.projects.add} onClose={() => setAdding(false)}>
          <ProjectForm
            onCancel={() => setAdding(false)}
            onSaved={(board) => navigate(`#/projects/${board.project.id}`)}
          />
        </Modal>
      )}
    </div>
  );
}

function ProjectSummaryCard({ project }: { project: ProjectListItem }) {
  const { t } = useI18n();
  const s = project.summary;
  const percent = s && s.total > 0 ? Math.round((s.done / s.total) * 100) : 0;

  return (
    <a
      href={`#/projects/${project.id}`}
      className="group block rounded-xl border border-zinc-200 bg-white p-4 shadow-sm transition hover:border-indigo-300 hover:shadow dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-indigo-700"
    >
      <div className="flex items-start justify-between gap-3">
        <div className="min-w-0">
          <h2 className="truncate font-semibold group-hover:text-indigo-600 dark:group-hover:text-indigo-400">{project.label}</h2>
          <p className="truncate text-xs text-zinc-500">{project.repository}</p>
        </div>
        <span className="text-2xl font-semibold tabular-nums">{percent}%</span>
      </div>

      {s ? (
        <>
          <ProgressBar total={s.total} done={s.done} review={s.review} className="mt-3" />
          <p className="mt-1.5 text-xs text-zinc-500 tabular-nums">
            {t.projects.tasks(s.done, s.total)}{s.review > 0 && ` · ${t.common.inReview(s.review)}`}
          </p>
          <dl className="mt-3 grid grid-cols-4 gap-1 text-center">
            {COLUMNS.map((status) => (
              <div key={status} className="rounded-md bg-zinc-50 py-1.5 dark:bg-zinc-800/60">
                <dd className="text-sm font-semibold tabular-nums">{s.columns[status]}</dd>
                <dt className="truncate px-1 text-[11px] text-zinc-500">{t.columns[status]}</dt>
              </div>
            ))}
          </dl>
        </>
      ) : (
        <p className="mt-3 text-sm text-zinc-500">{t.projects.notSynced}</p>
      )}

      <div className="mt-3 flex items-center gap-1.5 text-xs text-zinc-500">
        {project.error ? (
          <span className="flex items-center gap-1 text-red-600 dark:text-red-400" title={project.error}>
            <AlertTriangle className="size-3.5" aria-hidden /> {t.projects.syncFailed}
          </span>
        ) : (
          <span>{t.common.syncedAgo(t.age(age(project.syncedAt)))}</span>
        )}
        {project.paths.length > 0 && (
          <span className="ml-auto flex items-center gap-1 truncate font-mono" title={project.paths.join("\n")}>
            {project.paths[0]}{project.paths.length > 1 && ` +${project.paths.length - 1}`}
          </span>
        )}
      </div>
    </a>
  );
}
