import { describe, expect, it } from "vitest";
import { detectLanguage, messages } from "./messages";

describe("i18n", () => {
  it("usa lo elegido, luego el idioma del navegador y si no castellano", () => {
    expect(detectLanguage("en", ["es-ES"])).toBe("en");
    expect(detectLanguage(null, ["fr-FR", "en-GB"])).toBe("en");
    expect(detectLanguage("klingon", ["de"])).toBe("es");
  });

  it("formatea tiempos relativos en cada idioma", () => {
    expect(messages.es.age(null)).toBe("nunca");
    expect(messages.es.age({ unit: "minutes", value: 3 })).toBe("hace 3 min");
    expect(messages.en.age({ unit: "hours", value: 3 })).toBe("3 h ago");
    expect(messages.en.age({ unit: "now" })).toBe("just now");
  });
});
