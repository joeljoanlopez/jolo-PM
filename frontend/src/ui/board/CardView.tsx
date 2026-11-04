import { GitPullRequest } from "lucide-react";
import { plainTaskText } from "../../domain/board";
import type { Card } from "../../domain/model";
import { ProgressBar } from "../components/ProgressBar";
import { useI18n } from "../i18n";

export function CardView({ card, onOpen }: { card: Card; onOpen: () => void }) {
  const { t } = useI18n();
  return (
    <button
      type="button"
      onClick={onOpen}
      className="block w-full rounded-lg border border-zinc-200 bg-white p-3 text-left shadow-xs transition hover:border-indigo-300 hover:shadow focus-visible:outline-2 focus-visible:outline-indigo-500 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-indigo-700"
    >
      <h3 className="text-sm leading-snug font-medium">{plainTaskText(card.title)}</h3>
      <p className="mt-0.5 truncate font-mono text-[11px] text-zinc-500" title={card.path}>{card.path}</p>
      <div className="mt-2.5 flex items-center gap-2">
        <ProgressBar total={card.total} done={card.done} review={card.review} className="flex-1" />
        <span className="text-xs text-zinc-500 tabular-nums">{card.done}/{card.total}</span>
      </div>
      {card.pullRequests.length > 0 && (
        <div className="mt-2 flex flex-wrap gap-1">
          {card.pullRequests.map((pr) => (
            <span key={pr.number} className="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-medium text-amber-800 dark:bg-amber-900/40 dark:text-amber-300" title={pr.title}>
              <GitPullRequest className="size-3" aria-hidden />#{pr.number}{pr.draft && ` · ${t.common.draft}`}
            </span>
          ))}
        </div>
      )}
    </button>
  );
}
