import { Lock, Search } from "lucide-react";
import { useMemo, useState, type FormEvent } from "react";
import type { Project, ProjectBoard } from "../../domain/model";
import { GatewayError } from "../../ports/ProjectsGateway";
import { Button, ErrorBanner, Field, inputClass, Spinner } from "../components/ui";
import { useGateway } from "../gateway";
import { useAsync } from "../hooks";
import { useI18n } from "../i18n";

const DEFAULT_PATHS = "docs/**/*.md";

/** Alta (sin `project`) o edición (con `project`) de un proyecto. */
export function ProjectForm({
  project,
  onSaved,
  onCancel,
}: {
  project?: Project;
  onSaved: (board: ProjectBoard) => void;
  onCancel: () => void;
}) {
  const gateway = useGateway();
  const { t } = useI18n();
  const editing = project !== undefined;

  const [repository, setRepository] = useState(project?.repository ?? "");
  const [branch, setBranch] = useState(project?.branch ?? "");
  const [paths, setPaths] = useState(project?.paths.join("\n") ?? DEFAULT_PATHS);
  const [name, setName] = useState(project?.name ?? "");
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<GatewayError | Error | null>(null);

  const fields = error instanceof GatewayError ? error.fields : {};

  async function submit(e: FormEvent) {
    e.preventDefault();
    setSaving(true);
    setError(null);
    const input = {
      repository,
      branch: branch.trim() || null,
      paths: paths.split("\n").map((p) => p.trim()).filter(Boolean),
      name: name.trim() || null,
    };
    try {
      onSaved(editing ? await gateway.updateProject(project.id, input) : await gateway.createProject(input));
    } catch (err) {
      setError(err as Error);
      setSaving(false);
    }
  }

  return (
    <form onSubmit={submit} className="space-y-4">
      {editing ? (
        <Field label={t.form.repository}>
          <p className="font-mono text-sm">{project.repository}</p>
        </Field>
      ) : (
        <RepositoryPicker value={repository} onChange={setRepository} error={fields.repository} />
      )}

      <Field label={t.form.paths} hint={t.form.pathsHint} error={fields.paths ?? fields["paths.0"]}>
        <textarea
          value={paths}
          onChange={(e) => setPaths(e.target.value)}
          rows={3}
          required
          spellCheck={false}
          className={inputClass + " font-mono"}
          placeholder={"docs/plan/*.md\nTODO.md"}
        />
      </Field>

      <div className="grid gap-4 sm:grid-cols-2">
        <Field label={t.form.branch} hint={t.form.branchHint} error={fields.branch}>
          <input value={branch} onChange={(e) => setBranch(e.target.value)} className={inputClass} placeholder="main" spellCheck={false} />
        </Field>
        <Field label={t.form.name} hint={t.form.nameHint} error={fields.name}>
          <input value={name} onChange={(e) => setName(e.target.value)} className={inputClass} maxLength={100} />
        </Field>
      </div>

      {error && Object.keys(fields).length === 0 && <ErrorBanner>{error.message}</ErrorBanner>}

      <div className="flex justify-end gap-2 pt-2">
        <Button onClick={onCancel} disabled={saving}>{t.common.cancel}</Button>
        <Button type="submit" variant="primary" disabled={saving || (!editing && repository.trim() === "")}>
          {saving && <Spinner />} {editing ? t.form.save : t.form.create}
        </Button>
      </div>
    </form>
  );
}

function RepositoryPicker({ value, onChange, error }: { value: string; onChange: (v: string) => void; error?: string }) {
  const gateway = useGateway();
  const { t } = useI18n();
  const repos = useAsync(() => gateway.listRepositories(), []);
  const [open, setOpen] = useState(false);

  const matches = useMemo(() => {
    const q = value.trim().toLowerCase();
    return (repos.data ?? []).filter((r) => r.fullName.toLowerCase().includes(q)).slice(0, 50);
  }, [repos.data, value]);

  return (
    <Field label={t.form.repository} hint={t.form.repositoryHint} error={error ?? repos.error?.message}>
      <div className="relative">
        <Search className="pointer-events-none absolute top-2.5 left-2.5 size-4 text-zinc-400" aria-hidden />
        <input
          value={value}
          onChange={(e) => {
            onChange(e.target.value);
            setOpen(true);
          }}
          onFocus={() => setOpen(true)}
          onBlur={() => window.setTimeout(() => setOpen(false), 150)}
          className={inputClass + " pl-8"}
          placeholder={t.form.repositoryPlaceholder}
          spellCheck={false}
          autoComplete="off"
          required
          autoFocus
          role="combobox"
          aria-expanded={open}
        />
        {repos.loading && <Spinner className="absolute top-2.5 right-2.5 size-4 text-zinc-400" />}
        {open && matches.length > 0 && (
          <ul role="listbox" className="absolute z-10 mt-1 max-h-64 w-full overflow-y-auto rounded-md border border-zinc-200 bg-white py-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
            {matches.map((r) => (
              <li key={r.fullName}>
                <button
                  type="button"
                  role="option"
                  aria-selected={r.fullName === value}
                  className="flex w-full items-start gap-2 px-3 py-1.5 text-left text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800"
                  onMouseDown={(e) => e.preventDefault()}
                  onClick={() => {
                    onChange(r.fullName);
                    setOpen(false);
                  }}
                >
                  <span className="min-w-0 flex-1">
                    <span className="block truncate font-mono">{r.fullName}</span>
                    {r.description && <span className="block truncate text-xs text-zinc-500">{r.description}</span>}
                  </span>
                  {r.private && <Lock className="mt-0.5 size-3.5 shrink-0 text-zinc-400" aria-label={t.form.private} />}
                </button>
              </li>
            ))}
          </ul>
        )}
      </div>
    </Field>
  );
}
