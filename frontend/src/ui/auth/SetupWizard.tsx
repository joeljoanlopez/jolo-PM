import { useState, type FormEvent, type ReactNode } from "react";
import type { TotpEnrollment } from "../../domain/model";
import { GatewayError } from "../../ports/ProjectsGateway";
import { Button, ErrorBanner, Field, inputClass, Spinner } from "../components/ui";
import { useAuthGateway } from "../gateway";
import { useI18n } from "../i18n";
import { AuthLayout } from "./AuthLayout";
import { QrCode } from "./QrCode";
import { RecoveryCodesList } from "./RecoveryCodesList";

const MIN_PASSWORD = 12;
const STEPS = 4;

/**
 * Asistente de configuración inicial: código de los logs → contraseña → alta
 * del 2FA → códigos de recuperación. La cuenta se crea al confirmar el 2FA.
 */
export function SetupWizard({ onDone }: { onDone: () => void }) {
  const auth = useAuthGateway();
  const { t } = useI18n();
  const [step, setStep] = useState(1);
  const [setupCode, setSetupCode] = useState("");
  const [password, setPassword] = useState("");
  const [confirmation, setConfirmation] = useState("");
  const [enrollment, setEnrollment] = useState<TotpEnrollment | null>(null);
  const [code, setCode] = useState("");
  const [recoveryCodes, setRecoveryCodes] = useState<string[]>([]);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<Error | null>(null);

  const fields = error instanceof GatewayError ? error.fields : {};

  async function run(action: () => Promise<void>) {
    setBusy(true);
    setError(null);
    try {
      await action();
    } catch (err) {
      setError(err as Error);
      // El paso anterior caducó (la sesión o el código cambiaron): desde el principio.
      if (err instanceof GatewayError && err.status === 401) setStep(1);
    }
    setBusy(false);
  }

  const submit = (action: () => Promise<void>) => (e: FormEvent) => {
    e.preventDefault();
    void run(action);
  };

  let content: ReactNode;
  if (step === 1) {
    content = (
      <form onSubmit={submit(async () => { await auth.checkSetupCode(setupCode); setStep(2); })} className="space-y-4">
        <p className="text-sm text-zinc-600 dark:text-zinc-400">{t.setup.codeIntro}</p>
        <pre className="rounded-md bg-zinc-100 px-3 py-2 font-mono text-xs dark:bg-zinc-950">docker compose logs backend</pre>
        <Field label={t.setup.setupCode}>
          <input value={setupCode} onChange={(e) => setSetupCode(e.target.value)} className={inputClass + " font-mono uppercase"} placeholder="XXXX-XXXX-XXXX-XXXX" autoComplete="off" spellCheck={false} autoFocus required />
        </Field>
        <Footer busy={busy} disabled={setupCode.trim() === ""} label={t.auth.continue} error={error} />
      </form>
    );
  } else if (step === 2) {
    content = (
      <form
        onSubmit={submit(async () => {
          setEnrollment(await auth.choosePassword(setupCode, password, confirmation));
          setStep(3);
        })}
        className="space-y-4"
      >
        <p className="text-sm text-zinc-600 dark:text-zinc-400">{t.setup.passwordIntro}</p>
        <Field label={t.auth.password} hint={t.setup.passwordHint(MIN_PASSWORD)} error={fields.password}>
          <input type="password" value={password} onChange={(e) => setPassword(e.target.value)} className={inputClass} minLength={MIN_PASSWORD} autoComplete="new-password" autoFocus required />
        </Field>
        <Field label={t.setup.passwordConfirmation} error={fields.passwordConfirmation}>
          <input type="password" value={confirmation} onChange={(e) => setConfirmation(e.target.value)} className={inputClass} minLength={MIN_PASSWORD} autoComplete="new-password" required />
        </Field>
        <Footer busy={busy} disabled={password.length < MIN_PASSWORD || confirmation === ""} label={t.auth.continue} error={Object.keys(fields).length ? null : error} />
      </form>
    );
  } else if (step === 3 && enrollment) {
    content = (
      <form
        onSubmit={submit(async () => {
          setRecoveryCodes(await auth.confirmSetup(setupCode, code));
          setStep(4);
        })}
        className="space-y-4"
      >
        <p className="text-sm text-zinc-600 dark:text-zinc-400">{t.setup.totpIntro}</p>
        <div className="flex justify-center">
          <QrCode value={enrollment.otpauthUri} label={t.setup.qrLabel} />
        </div>
        <div className="text-xs text-zinc-500">
          {t.setup.manualKey}
          <code className="mt-1 block rounded bg-zinc-100 px-2 py-1 font-mono text-sm break-all text-zinc-900 select-all dark:bg-zinc-950 dark:text-zinc-100">
            {enrollment.secret.replace(/(.{4})/g, "$1 ").trim()}
          </code>
        </div>
        <Field label={t.auth.code} hint={t.auth.codeHint}>
          <input
            value={code}
            onChange={(e) => setCode(e.target.value.replace(/\D/g, "").slice(0, 6))}
            className={inputClass + " font-mono tracking-[0.3em]"}
            inputMode="numeric"
            autoComplete="one-time-code"
            pattern="\d{6}"
            required
          />
        </Field>
        <Footer busy={busy} disabled={code.length !== 6} label={t.setup.activate} error={error} />
      </form>
    );
  } else {
    content = (
      <div className="space-y-4">
        <p className="text-sm text-zinc-600 dark:text-zinc-400">{t.setup.recoveryIntro}</p>
        <RecoveryCodesList codes={recoveryCodes} />
        <div className="flex justify-end pt-2">
          <Button variant="primary" onClick={onDone}>{t.setup.done}</Button>
        </div>
      </div>
    );
  }

  return (
    <AuthLayout title={t.setup.title} subtitle={t.setup.step(step, STEPS)}>
      {content}
    </AuthLayout>
  );
}

function Footer({ busy, disabled, label, error }: { busy: boolean; disabled: boolean; label: string; error: Error | null }) {
  return (
    <>
      {error && <ErrorBanner>{error.message}</ErrorBanner>}
      <div className="flex justify-end pt-2">
        <Button type="submit" variant="primary" disabled={busy || disabled}>
          {busy && <Spinner />} {label}
        </Button>
      </div>
    </>
  );
}
