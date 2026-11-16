// Puerto: lo que la interfaz necesita del backend para el login con 2FA.
import type { AuthState, TotpEnrollment } from "../domain/auth";

export interface AuthGateway {
  state(): Promise<AuthState>;

  // Asistente de configuración inicial.
  setupCode(setupCode: string): Promise<TotpEnrollment>;
  setupPassword(password: string, passwordConfirmation: string): Promise<void>;
  completeSetup(password: string, passwordConfirmation: string, code: string): Promise<string[]>;

  // Login en dos pasos.
  login(password: string): Promise<void>;
  verifyTotp(code: string): Promise<void>;
  /** Devuelve cuántos códigos de recuperación quedan. */
  verifyRecoveryCode(recoveryCode: string): Promise<number>;
  logout(): Promise<void>;

  regenerateRecoveryCodes(code: string): Promise<string[]>;

  /** Avisa cuando cualquier petición (también las de proyectos) responde 401. */
  onUnauthorized(listener: () => void): () => void;
}
