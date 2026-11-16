import { describe, expect, it } from "vitest";
import { groupSecret } from "./auth";

describe("groupSecret", () => {
  it("agrupa el secreto de 4 en 4 para teclearlo a mano", () => {
    expect(groupSecret("JBSWY3DPEHPK3PXP")).toBe("JBSW Y3DP EHPK 3PXP");
    expect(groupSecret("ABCDEF")).toBe("ABCD EF");
    expect(groupSecret("")).toBe("");
  });
});
