import { useState, type FormEvent, type ReactNode } from "react";
import { groupSecret, type TotpEnrollment } from "../../domain/auth";
import { GatewayError } from "../../ports/ProjectsGateway";
import { Button, ErrorBanner, Field, Spinner, inputClass } from "../components/ui";
import { useI18n } from "../i18n";
import { AuthCard, totpInputProps } from "./AuthCard";
import { useAuth } from "./AuthProvider";
import { QrCode } from "./QrCode";
import { RecoveryCodesList } from "./RecoveryCodesList";

const MIN_PASSWORD = 12;

/**
 * Configuración inicial en cuatro pasos: código de los logs, contraseña, alta
 * del TOTP y códigos de recuperación. La cuenta se crea al confirmar el TOTP;
 * la contraseña se queda en memoria hasta entonces.
 */
export function SetupWizard() {
  const { gateway, setState } = useAuth();
  const { t } = useI18n();
  const [step, setStep] = useState(0);
  const [setupCode, setSetupCode] = useState("");
  const [enrollment, setEnrollment] = useState<TotpEnrollment | null>(null);
  const [password, setPassword] = useState("");
  const [confirmation, setConfirmation] = useState("");
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
    } finally {
      setBusy(false);
    }
  }

  const submitCode = (e: FormEvent) => {
    e.preventDefault();
    void run(async () => {
      setEnrollment(await gateway.setupCode(setupCode));
      setStep(1);
    });
  };

  const submitPassword = (e: FormEvent) => {
    e.preventDefault();
    void run(async () => {
      await gateway.setupPassword(password, confirmation);
      setStep(2);
    });
  };

  const submitTotp = (e: FormEvent) => {
    e.preventDefault();
    void run(async () => {
      try {
        setRecoveryCodes(await gateway.completeSetup(password, confirmation, code));
        setPassword("");
        setConfirmation("");
        setStep(3);
      } catch (err) {
        setCode("");
        throw err;
      }
    });
  };

  const submitButton = (label: string) => (
    <Button type="submit" variant="primary" className="w-full" disabled={busy}>
      {busy && <Spinner />} {label}
    </Button>
  );

  let body: ReactNode;
  if (step === 0) {
    body = (
      <form onSubmit={submitCode} className="space-y-4">
        <p className="text-sm text-zinc-600 dark:text-zinc-400">{t.auth.setup.intro}</p>
        <Field label={t.auth.setup.codeLabel} hint={t.auth.setup.codeHint} error={fields.setupCode}>
          <input
            value={setupCode}
            onChange={(e) => setSetupCode(e.target.value)}
            autoComplete="off"
            autoFocus
            required
            spellCheck={false}
            placeholder="XXXX-XXXX-XXXX-XXXX"
            className={inputClass + " font-mono uppercase"}
          />
        </Field>
        {submitButton(t.auth.continue)}
      </form>
    );
  } else if (step === 1) {
    body = (
      <form onSubmit={submitPassword} className="space-y-4">
        <Field label={t.auth.password} hint={t.auth.passwordHint(MIN_PASSWORD)} error={fields.password}>
          <input
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            autoComplete="new-password"
            minLength={MIN_PASSWORD}
            autoFocus
            required
            className={inputClass}
          />
        </Field>
        <Field label={t.auth.passwordRepeat} error={fields.passwordConfirmation}>
          <input
            type="password"
            value={confirmation}
            onChange={(e) => setConfirmation(e.target.value)}
            autoComplete="new-password"
            required
            className={inputClass}
          />
        </Field>
        {submitButton(t.auth.continue)}
      </form>
    );
  } else if (step === 2 && enrollment) {
    body = (
      <form onSubmit={submitTotp} className="space-y-4">
        <p className="text-sm text-zinc-600 dark:text-zinc-400">{t.auth.setup.totpIntro}</p>
        <div className="flex justify-center">
          <QrCode value={enrollment.uri} label={t.auth.setup.totpIntro} />
        </div>
        <div className="space-y-1 text-center text-xs text-zinc-500">
          <p>{t.auth.setup.manualSecret}</p>
          <p className="select-all font-mono text-sm text-zinc-900 dark:text-zinc-100">{groupSecret(enrollment.secret)}</p>
          <a href={enrollment.uri} className="text-indigo-600 hover:underline dark:text-indigo-400">
            {t.auth.setup.openInApp}
          </a>
        </div>
        <Field label={t.auth.code} error={fields.code}>
          <input
            value={code}
            onChange={(e) => setCode(e.target.value)}
            autoFocus
            placeholder="123456"
            className={inputClass + " font-mono tracking-widest"}
            {...totpInputProps}
          />
        </Field>
        {submitButton(t.auth.setup.create)}
        <Button className="w-full" variant="ghost" onClick={() => setStep(1)} disabled={busy}>
          {t.auth.back}
        </Button>
      </form>
    );
  } else {
    body = (
      <div className="space-y-4">
        <p className="text-sm text-zinc-600 dark:text-zinc-400">{t.auth.setup.codesIntro}</p>
        <RecoveryCodesList codes={recoveryCodes} />
        <Button variant="primary" className="w-full" onClick={() => setState("authenticated")}>
          {t.auth.setup.codesSaved}
        </Button>
      </div>
    );
  }

  return (
    <AuthCard title={t.auth.setup.title}>
      <ol className="mb-5 grid grid-cols-4 gap-1 text-center text-xs" aria-label={t.auth.setup.title}>
        {t.auth.setup.steps.map((label, i) => (
          <li
            key={label}
            aria-current={i === step ? "step" : undefined}
            className={`border-t-2 pt-1 ${i <= step ? "border-indigo-600 text-zinc-900 dark:border-indigo-400 dark:text-zinc-100" : "border-zinc-200 text-zinc-400 dark:border-zinc-800"}`}
          >
            {label}
          </li>
        ))}
      </ol>
      {error && step !== 3 && <div className="mb-4"><ErrorBanner>{error.message}</ErrorBanner></div>}
      {body}
    </AuthCard>
  );
}
