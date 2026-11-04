import { describe, expect, it } from "vitest";
import { age, groupBySection, groupByStatus, plainTaskText, progress } from "./board";
import type { BoardTask, Card } from "./model";

const task = (section: string | null, text = "t"): BoardTask => ({ text, depth: 0, line: 1, section, state: "pending", prs: [] });

describe("board", () => {
  it("agrupa tarjetas por columna", () => {
    const card = (status: Card["status"]) => ({ status }) as Card;
    const groups = groupByStatus([card("done"), card("todo"), card("done")]);
    expect(groups.done).toHaveLength(2);
    expect(groups.review).toHaveLength(0);
  });

  it("calcula porcentajes sin dividir por cero", () => {
    expect(progress({ total: 0, done: 0, review: 0 })).toEqual({ done: 0, review: 0 });
    expect(progress({ total: 4, done: 1, review: 2 })).toEqual({ done: 25, review: 50 });
  });

  it("agrupa tareas por secciones consecutivas", () => {
    const groups = groupBySection([task("A"), task("A"), task("B"), task("A")]);
    expect(groups.map((g) => [g.section, g.tasks.length])).toEqual([["A", 2], ["B", 1], ["A", 1]]);
  });

  it("calcula la antigüedad redondeada", () => {
    const now = new Date("2026-11-04T12:00:00Z");
    expect(age(null, now)).toBeNull();
    expect(age("2026-11-04T11:59:50Z", now)).toEqual({ unit: "now" });
    expect(age("2026-11-04T11:57:00Z", now)).toEqual({ unit: "minutes", value: 3 });
    expect(age("2026-11-04T09:00:00Z", now)).toEqual({ unit: "hours", value: 3 });
  });

  it("limpia enlaces y negritas del texto", () => {
    expect(plainTaskText("[**Cap 01**](01.md) — HTTP")).toBe("Cap 01 — HTTP");
  });
});
