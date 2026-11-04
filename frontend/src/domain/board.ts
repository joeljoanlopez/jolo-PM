// Reglas de presentación puras (sin React): fáciles de testear y de cambiar.
import type { BoardTask, Card, CardStatus, Summary } from "./model";

/** Columnas del tablero en orden. Sus nombres están en i18n/messages. */
export const COLUMNS: CardStatus[] = ["todo", "in_progress", "review", "done"];

export function groupByStatus(cards: Card[]): Record<CardStatus, Card[]> {
  const groups: Record<CardStatus, Card[]> = { todo: [], in_progress: [], review: [], done: [] };
  for (const card of cards) groups[card.status].push(card);
  return groups;
}

/** Porcentajes para la barra: hecho y en revisión, sobre el total. */
export function progress(s: Pick<Summary, "total" | "done" | "review">): { done: number; review: number } {
  if (s.total === 0) return { done: 0, review: 0 };
  return { done: (s.done / s.total) * 100, review: (s.review / s.total) * 100 };
}

/** Agrupa las tareas por sección manteniendo el orden del fichero. */
export function groupBySection(tasks: BoardTask[]): { section: string | null; tasks: BoardTask[] }[] {
  const groups: { section: string | null; tasks: BoardTask[] }[] = [];
  for (const task of tasks) {
    const last = groups[groups.length - 1];
    if (last && last.section === task.section) last.tasks.push(task);
    else groups.push({ section: task.section, tasks: [task] });
  }
  return groups;
}

/** Antigüedad redondeada, sin texto: el idioma la formatea en i18n/messages. */
export type Age = { unit: "now" } | { unit: "minutes" | "hours" | "days"; value: number };

export function age(iso: string | null, now: Date = new Date()): Age | null {
  if (!iso) return null;
  const seconds = Math.round((now.getTime() - new Date(iso).getTime()) / 1000);
  if (seconds < 45) return { unit: "now" };
  const minutes = Math.round(seconds / 60);
  if (minutes < 60) return { unit: "minutes", value: minutes };
  const hours = Math.round(minutes / 60);
  if (hours < 24) return { unit: "hours", value: hours };
  return { unit: "days", value: Math.round(hours / 24) };
}

/** Quita el formato markdown de enlace/énfasis para mostrar el texto de una tarea. */
export function plainTaskText(text: string): string {
  return text
    .replace(/!?\[([^\]]*)\]\([^)]*\)/g, "$1")
    .replace(/\*\*|__|~~/g, "")
    .trim();
}
