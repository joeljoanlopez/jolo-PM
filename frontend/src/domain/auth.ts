// Estado del login (E10), tal como lo cuenta GET /api/auth/me.
export type AuthState = "setup" | "login" | "two_factor" | "authenticated";

/** Lo que devuelve el paso 1 del asistente: el secreto TOTP y su URI otpauth:// para el QR. */
export type TotpEnrollment = { secret: string; uri: string };

/** El secreto en grupos de 4 para copiarlo a mano sin perderse. */
export function groupSecret(secret: string): string {
  return secret.match(/.{1,4}/g)?.join(" ") ?? "";
}
