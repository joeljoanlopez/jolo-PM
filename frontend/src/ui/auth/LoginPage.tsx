import { useState, type FormEvent } from "react";
import { GatewayError } from "../../ports/ProjectsGateway";
import { Button, ErrorBanner, Field, inputClass, Spinner } from "../components/ui";
import { useAuthGateway } from "../gateway";
import { useI18n } from "../i18n";
import { AuthLayout } from "./AuthLayout";

type Step = "password" | "code" | "recovery";

/** Login en dos pasos: contraseña y luego código TOTP o de recuperación. */
export function LoginPage({ pendingSecondFactor, onSignedIn }: { pendingSecondFactor: boolean; onSignedIn: () => void }) {
  const auth = useAuthGateway();
  const { t } = useI18n();
  const [step, setStep] = useState<Step>(pendingSecondFactor ? "code" : "password");
  const [value, setValue] = useState("");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<Error | null>(null);

  function go(next: Step, keepError = false) {
    setStep(next);
    setValue("");
    if (!keepError) setError(null);
  }

  async function submit(e: FormEvent) {
    e.preventDefault();
    setBusy(true);
    setError(null);
    try {
      if (step === "password") {
        await auth.login(value);
        go("code");
      } else {
        await auth.verifySecondFactor(step === "code" ? { code: value } : { recoveryCode: value });
        onSignedIn();
        return;
      }
    } catch (err) {
      setError(err as Error);
      // El paso de la contraseña caducó (5 min): se vuelve a empezar.
      if (err instanceof GatewayError && err.status === 401) go("password", true);
    }
    setBusy(false);
  }

  const field =
    step === "password" ? (
      <Field label={t.auth.password}>
        <input type="password" value={value} onChange={(e) => setValue(e.target.value)} className={inputClass} autoComplete="current-password" autoFocus required />
      </Field>
    ) : step === "code" ? (
      <Field label={t.auth.code} hint={t.auth.codeHint}>
        <input
          value={value}
          onChange={(e) => setValue(e.target.value.replace(/\D/g, "").slice(0, 6))}
          className={inputClass + " font-mono tracking-[0.3em]"}
          inputMode="numeric"
          autoComplete="one-time-code"
          pattern="\d{6}"
          autoFocus
          required
        />
      </Field>
    ) : (
      <Field label={t.auth.recoveryCode} hint={t.auth.recoveryCodeHint}>
        <input value={value} onChange={(e) => setValue(e.target.value)} className={inputClass + " font-mono"} autoComplete="off" spellCheck={false} autoFocus required />
      </Field>
    );

  return (
    <AuthLayout title={step === "password" ? t.auth.signInTitle : t.auth.codeTitle}>
      <form onSubmit={submit} className="space-y-4">
        {field}
        {error && <ErrorBanner>{error.message}</ErrorBanner>}
        <div className="flex flex-wrap items-center justify-between gap-2">
          {step === "password" ? (
            <span />
          ) : (
            <button
              type="button"
              className="text-sm text-indigo-600 hover:underline dark:text-indigo-400"
              onClick={() => go(step === "code" ? "recovery" : "code")}
            >
              {step === "code" ? t.auth.useRecoveryCode : t.auth.useAuthenticator}
            </button>
          )}
          <Button type="submit" variant="primary" disabled={busy || value.trim() === ""}>
            {busy && <Spinner />} {step === "password" ? t.auth.continue : t.auth.signIn}
          </Button>
        </div>
      </form>
    </AuthLayout>
  );
}
