import { useState, type FormEvent } from "react";
import { Button, ErrorBanner, Field, Modal, Spinner, inputClass } from "../components/ui";
import { useI18n } from "../i18n";
import { totpInputProps } from "./AuthCard";
import { useAuth } from "./AuthProvider";
import { RecoveryCodesList } from "./RecoveryCodesList";

/** Regenerar los códigos de recuperación: con la sesión iniciada y un código TOTP. */
export function RecoveryCodesDialog({ onClose }: { onClose: () => void }) {
  const { gateway } = useAuth();
  const { t } = useI18n();
  const [code, setCode] = useState("");
  const [codes, setCodes] = useState<string[] | null>(null);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<Error | null>(null);

  async function submit(e: FormEvent) {
    e.preventDefault();
    setBusy(true);
    setError(null);
    try {
      setCodes(await gateway.regenerateRecoveryCodes(code));
    } catch (err) {
      setError(err as Error);
      setCode("");
    } finally {
      setBusy(false);
    }
  }

  return (
    <Modal title={t.auth.recoveryCodes} onClose={onClose}>
      {codes ? (
        <div className="space-y-4">
          <p className="text-sm text-zinc-600 dark:text-zinc-400">{t.auth.setup.codesIntro}</p>
          <RecoveryCodesList codes={codes} />
          <Button variant="primary" className="w-full" onClick={onClose}>
            {t.auth.setup.codesSaved}
          </Button>
        </div>
      ) : (
        <form onSubmit={submit} className="space-y-4">
          <p className="text-sm text-zinc-600 dark:text-zinc-400">{t.auth.regenerate.intro}</p>
          {error && <ErrorBanner>{error.message}</ErrorBanner>}
          <Field label={t.auth.code}>
            <input
              value={code}
              onChange={(e) => setCode(e.target.value)}
              autoFocus
              placeholder="123456"
              className={inputClass + " font-mono tracking-widest"}
              {...totpInputProps}
            />
          </Field>
          <Button type="submit" variant="primary" className="w-full" disabled={busy}>
            {busy && <Spinner />} {t.auth.regenerate.submit}
          </Button>
        </form>
      )}
    </Modal>
  );
}
