import { RefreshCw } from "lucide-react";
import { useState, type FormEvent } from "react";
import { Button, ErrorBanner, Field, Modal, Spinner, inputClass } from "../components/ui";
import { useAuthGateway } from "../gateway";
import { useI18n } from "../i18n";
import { useSession } from "./AuthGate";
import { RecoveryCodesList } from "./RecoveryCodesList";

/**
 * Códigos de recuperación que quedan y formulario para generar otros, que pide
 * un código TOTP: con una sesión abierta sola no basta.
 */
export function SecurityDialog({ onClose }: { onClose: () => void }) {
  const auth = useAuthGateway();
  const session = useSession();
  const { t } = useI18n();
  const [codes, setCodes] = useState<string[] | null>(null);
  const [totp, setTotp] = useState("");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<Error | null>(null);

  async function regenerate(e: FormEvent) {
    e.preventDefault();
    if (!window.confirm(t.recovery.confirmRegenerate)) return;
    setBusy(true);
    setError(null);
    try {
      setCodes(await auth.regenerateRecoveryCodes(totp));
      await session.refresh();
    } catch (err) {
      setError(err as Error);
      setTotp("");
    }
    setBusy(false);
  }

  return (
    <Modal title={t.recovery.title} onClose={onClose}>
      <div className="space-y-4 text-sm">
        {codes ? (
          <>
            <p>{t.recovery.fresh}</p>
            <RecoveryCodesList codes={codes} />
          </>
        ) : (
          <>
            <p>{t.recovery.left(session.status.recoveryCodesLeft ?? 0)}</p>
            <form onSubmit={regenerate} className="space-y-3">
              <Field label={t.auth.code} hint={t.recovery.regenerateHint}>
                <input
                  value={totp}
                  onChange={(e) => setTotp(e.target.value.replace(/\D/g, "").slice(0, 6))}
                  className={inputClass + " font-mono tracking-[0.3em]"}
                  inputMode="numeric"
                  autoComplete="one-time-code"
                  pattern="\d{6}"
                  required
                />
              </Field>
              <Button type="submit" disabled={busy}>
                {busy ? <Spinner /> : <RefreshCw className="size-4" aria-hidden />} {t.recovery.regenerate}
              </Button>
            </form>
          </>
        )}
        {error && <ErrorBanner>{error.message}</ErrorBanner>}
        <p className="text-xs text-zinc-500">{t.recovery.lost}</p>
      </div>
    </Modal>
  );
}
