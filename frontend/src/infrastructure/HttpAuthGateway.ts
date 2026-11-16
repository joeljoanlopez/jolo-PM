// Adaptador del puerto AuthGateway sobre /api/auth.
import type { AuthState, TotpEnrollment } from "../domain/auth";
import type { AuthGateway } from "../ports/AuthGateway";
import type { HttpClient } from "./HttpClient";

export class HttpAuthGateway implements AuthGateway {
  constructor(private readonly http: HttpClient) {}

  async state() {
    return (await this.http.request<{ state: AuthState }>("GET", "/auth/me")).state;
  }

  setupCode(setupCode: string) {
    return this.http.request<TotpEnrollment>("POST", "/auth/setup/code", { setupCode });
  }

  async setupPassword(password: string, passwordConfirmation: string) {
    await this.http.request<null>("POST", "/auth/setup/password", { password, passwordConfirmation });
  }

  async completeSetup(password: string, passwordConfirmation: string, code: string) {
    const body = { password, passwordConfirmation, code };
    return (await this.http.request<{ recoveryCodes: string[] }>("POST", "/auth/setup/complete", body)).recoveryCodes;
  }

  async login(password: string) {
    await this.http.request("POST", "/auth/login", { password });
  }

  async verifyTotp(code: string) {
    await this.http.request("POST", "/auth/two-factor", { code });
  }

  async verifyRecoveryCode(recoveryCode: string) {
    const result = await this.http.request<{ recoveryCodesLeft: number }>("POST", "/auth/two-factor", { recoveryCode });
    return result.recoveryCodesLeft;
  }

  async logout() {
    await this.http.request<null>("POST", "/auth/logout");
  }

  async regenerateRecoveryCodes(code: string) {
    return (await this.http.request<{ recoveryCodes: string[] }>("POST", "/auth/recovery-codes", { code })).recoveryCodes;
  }

  onUnauthorized(listener: () => void) {
    return this.http.onUnauthorized(listener);
  }
}
