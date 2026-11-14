import { RefreshCw } from "lucide-react";
import { useState } from "react";
import { Button, ErrorBanner, Modal, Spinner } from "../components/ui";
import { useAuthGateway } from "../gateway";
import { useI18n } from "../i18n";
import { useSession } from "./AuthGate";
import { RecoveryCodesList } from "./RecoveryCodesList";

/** Códigos de recuperación que quedan y botón para generar otros. */
export function SecurityDialog({ onClose }: { onClose: () => void }) {
  const auth = useAuthGateway();
  const session = useSession();
  const { t } = useI18n();
  const [codes, setCodes] = useState<string[] | null>(null);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<Error | null>(null);

  async function regenerate() {
    if (!window.confirm(t.recovery.confirmRegenerate)) return;
    setBusy(true);
    setError(null);
    try {
      setCodes(await auth.regenerateRecoveryCodes());
      await session.refresh();
    } catch (e) {
      setError(e as Error);
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
            <Button onClick={regenerate} disabled={busy}>
              {busy ? <Spinner /> : <RefreshCw className="size-4" aria-hidden />} {t.recovery.regenerate}
            </Button>
          </>
        )}
        {error && <ErrorBanner>{error.message}</ErrorBanner>}
        <p className="text-xs text-zinc-500">{t.recovery.lost}</p>
      </div>
    </Modal>
  );
}
