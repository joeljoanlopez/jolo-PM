// Adaptador del puerto AuthGateway sobre /api/auth.
import type { AuthStatus, TotpEnrollment } from "../domain/model";
import type { AuthGateway, SecondFactor } from "../ports/AuthGateway";
import type { HttpClient } from "./HttpClient";

export class HttpAuthGateway implements AuthGateway {
  constructor(private readonly http: HttpClient) {}

  status() {
    return this.http.request<AuthStatus>("GET", "/auth/me");
  }

  async checkSetupCode(setupCode: string) {
    await this.http.request<null>("POST", "/auth/setup/code", { setupCode });
  }

  choosePassword(setupCode: string, password: string, passwordConfirmation: string) {
    return this.http.request<TotpEnrollment>("POST", "/auth/setup/password", { setupCode, password, passwordConfirmation });
  }

  async confirmSetup(setupCode: string, code: string) {
    return (await this.http.request<{ recoveryCodes: string[] }>("POST", "/auth/setup/two-factor", { setupCode, code })).recoveryCodes;
  }

  async login(password: string) {
    await this.http.request<unknown>("POST", "/auth/login", { password });
  }

  verifySecondFactor(factor: SecondFactor) {
    return this.http.request<AuthStatus>("POST", "/auth/two-factor", factor);
  }

  async logout() {
    await this.http.request<null>("POST", "/auth/logout");
  }

  async regenerateRecoveryCodes() {
    return (await this.http.request<{ recoveryCodes: string[] }>("POST", "/auth/recovery-codes")).recoveryCodes;
  }

  onSessionLost(listener: () => void) {
    return this.http.onUnauthorized(listener);
  }
}
