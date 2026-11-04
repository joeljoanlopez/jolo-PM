import { progress } from "../../domain/board";

export function ProgressBar({ total, done, review, className = "" }: { total: number; done: number; review: number; className?: string }) {
  const p = progress({ total, done, review });
  return (
    <div
      className={`flex h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-800 ${className}`}
      role="progressbar"
      aria-valuemin={0}
      aria-valuemax={total}
      aria-valuenow={done}
      aria-label={`${done} de ${total} hechas, ${review} en revisión`}
    >
      <div className="bg-emerald-500 transition-all" style={{ width: `${p.done}%` }} />
      <div className="bg-amber-400 transition-all" style={{ width: `${p.review}%` }} />
    </div>
  );
}
