import { CheckSquare2, Clock, ExternalLink, GitPullRequest, Square } from "lucide-react";
import { groupBySection, plainTaskText } from "../../domain/board";
import type { BoardTask, Card } from "../../domain/model";
import { ProgressBar } from "../components/ProgressBar";
import { Modal } from "../components/ui";
import { useI18n } from "../i18n";

export function CardDetail({ card, onClose }: { card: Card; onClose: () => void }) {
  const { t } = useI18n();
  const prsByNumber = new Map(card.pullRequests.map((pr) => [pr.number, pr]));

  return (
    <Modal
      wide
      onClose={onClose}
      title={
        <div>
          <div>{plainTaskText(card.title)}</div>
          <a href={card.url} target="_blank" rel="noreferrer" className="mt-0.5 inline-flex items-center gap-1 font-mono text-xs font-normal text-zinc-500 hover:text-indigo-600 dark:hover:text-indigo-400">
            {card.path} <ExternalLink className="size-3" aria-hidden />
          </a>
        </div>
      }
    >
      <div className="flex items-center gap-3">
        <ProgressBar total={card.total} done={card.done} review={card.review} className="flex-1" />
        <span className="text-sm text-zinc-500 tabular-nums">{card.done}/{card.total}</span>
      </div>

      {card.pullRequests.length > 0 && (
        <ul className="mt-4 space-y-1">
          {card.pullRequests.map((pr) => (
            <li key={pr.number}>
              <a href={pr.url} target="_blank" rel="noreferrer" className="inline-flex items-center gap-1.5 text-sm text-amber-700 hover:underline dark:text-amber-400">
                <GitPullRequest className="size-4" aria-hidden /> #{pr.number} {pr.title}{pr.draft && ` (${t.common.draft})`}
              </a>
            </li>
          ))}
        </ul>
      )}

      <div className="mt-4 space-y-4">
        {groupBySection(card.tasks).map((group, i) => (
          <section key={i}>
            {group.section && <h3 className="mb-1.5 text-xs font-semibold tracking-wide text-zinc-500 uppercase">{group.section}</h3>}
            <ul className="space-y-1">
              {group.tasks.map((task) => (
                <TaskRow key={task.line} task={task} url={`${card.url}#L${task.line}`} prs={task.prs.map((n) => prsByNumber.get(n)).filter((p) => p !== undefined)} />
              ))}
            </ul>
          </section>
        ))}
      </div>
    </Modal>
  );
}

function TaskRow({ task, url, prs }: { task: BoardTask; url: string; prs: Card["pullRequests"] }) {
  const { t } = useI18n();
  const Icon = task.state === "done" ? CheckSquare2 : task.state === "review" ? Clock : Square;
  const iconColor = task.state === "done" ? "text-emerald-600 dark:text-emerald-400" : task.state === "review" ? "text-amber-500" : "text-zinc-400";
  const label = t.task[task.state];

  return (
    <li className="flex items-start gap-2 text-sm" style={{ paddingLeft: `${task.depth * 1.25}rem` }}>
      <Icon className={`mt-0.5 size-4 shrink-0 ${iconColor}`} aria-label={label} />
      <a href={url} target="_blank" rel="noreferrer" className={`min-w-0 flex-1 hover:underline ${task.state === "done" ? "text-zinc-500 line-through decoration-zinc-400/60" : ""}`}>
        {plainTaskText(task.text)}
      </a>
      {prs.map((pr) => (
        <a key={pr.number} href={pr.url} target="_blank" rel="noreferrer" className="shrink-0 text-xs text-amber-700 hover:underline dark:text-amber-400" title={pr.title}>
          #{pr.number}
        </a>
      ))}
    </li>
  );
}
