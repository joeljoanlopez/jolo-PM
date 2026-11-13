// Puerto: el login. Como ProjectsGateway, la UI solo depende de esta interfaz.
import type { AuthStatus, TotpEnrollment } from "../domain/model";

export type SecondFactor = { code: string } | { recoveryCode: string };

export interface AuthGateway {
  /** En qué paso está la sesión. */
  status(): Promise<AuthStatus>;

  // Configuración inicial: cada paso lleva el código de los logs del backend.
  checkSetupCode(setupCode: string): Promise<void>;
  choosePassword(setupCode: string, password: string, passwordConfirmation: string): Promise<TotpEnrollment>;
  /** Crea la cuenta e inicia sesión. Devuelve los códigos de recuperación. */
  confirmSetup(setupCode: string, code: string): Promise<string[]>;

  login(password: string): Promise<void>;
  verifySecondFactor(factor: SecondFactor): Promise<AuthStatus>;
  logout(): Promise<void>;
  regenerateRecoveryCodes(): Promise<string[]>;

  /** Avisa cuando el backend deja de reconocer la sesión (cualquier 401). */
  onSessionLost(listener: () => void): () => void;
}
