import { useState, type FormEvent } from "react";
import { GatewayError } from "../../ports/ProjectsGateway";
import { Button, ErrorBanner, Field, Spinner, inputClass } from "../components/ui";
import { useI18n } from "../i18n";
import { AuthCard, totpInputProps } from "./AuthCard";
import { useAuth } from "./AuthProvider";

/** Primer paso: la contraseña. */
export function LoginPage() {
  const { gateway, setState } = useAuth();
  const { t } = useI18n();
  const [password, setPassword] = useState("");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<Error | null>(null);

  async function submit(e: FormEvent) {
    e.preventDefault();
    setBusy(true);
    setError(null);
    try {
      await gateway.login(password);
      setState("two_factor");
    } catch (err) {
      setError(err as Error);
      setBusy(false);
    }
  }

  return (
    <AuthCard title={t.auth.login.title}>
      <form onSubmit={submit} className="space-y-4">
        {error && <ErrorBanner>{error.message}</ErrorBanner>}
        <Field label={t.auth.password} error={error instanceof GatewayError ? error.fields.password : undefined}>
          <input
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            autoComplete="current-password"
            autoFocus
            required
            className={inputClass}
          />
        </Field>
        <Button type="submit" variant="primary" className="w-full" disabled={busy}>
          {busy && <Spinner />} {t.auth.login.submit}
        </Button>
      </form>
    </AuthCard>
  );
}

/** Segundo paso: código de la app o, si no hay móvil, uno de recuperación. */
export function TwoFactorPage() {
  const { gateway, setState, logout } = useAuth();
  const { t } = useI18n();
  const [useRecovery, setUseRecovery] = useState(false);
  const [code, setCode] = useState("");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<Error | null>(null);
  const [codesLeft, setCodesLeft] = useState<number | null>(null);

  async function submit(e: FormEvent) {
    e.preventDefault();
    setBusy(true);
    setError(null);
    try {
      if (useRecovery) {
        const left = await gateway.verifyRecoveryCode(code);
        // Aviso antes de entrar: después ya no hay forma de saberlo.
        if (left <= 3) {
          setCodesLeft(left);
          return;
        }
      } else {
        await gateway.verifyTotp(code);
      }
      setState("authenticated");
    } catch (err) {
      setError(err as Error);
      setCode("");
    } finally {
      setBusy(false);
    }
  }

  if (codesLeft !== null) {
    return (
      <AuthCard title={t.auth.twoFactor.title}>
        <div className="space-y-4">
          <ErrorBanner>{t.auth.twoFactor.lowCodes(codesLeft)}</ErrorBanner>
          <Button variant="primary" className="w-full" onClick={() => setState("authenticated")}>
            {t.auth.continue}
          </Button>
        </div>
      </AuthCard>
    );
  }

  return (
    <AuthCard title={t.auth.twoFactor.title}>
      <form onSubmit={submit} className="space-y-4">
        <p className="text-sm text-zinc-600 dark:text-zinc-400">
          {useRecovery ? t.auth.twoFactor.recoveryIntro : t.auth.twoFactor.intro}
        </p>
        {error && <ErrorBanner>{error.message}</ErrorBanner>}
        {useRecovery ? (
          <Field label={t.auth.twoFactor.recoveryCode}>
            <input
              key="recovery"
              value={code}
              onChange={(e) => setCode(e.target.value)}
              autoComplete="off"
              autoFocus
              required
              spellCheck={false}
              placeholder="XXXXX-XXXXX"
              className={inputClass + " font-mono uppercase"}
            />
          </Field>
        ) : (
          <Field label={t.auth.code}>
            <input
              key="totp"
              value={code}
              onChange={(e) => setCode(e.target.value)}
              autoFocus
              placeholder="123456"
              className={inputClass + " font-mono tracking-widest"}
              {...totpInputProps}
            />
          </Field>
        )}
        <Button type="submit" variant="primary" className="w-full" disabled={busy}>
          {busy && <Spinner />} {t.auth.twoFactor.verify}
        </Button>
        <div className="flex flex-wrap justify-between gap-2 text-sm">
          <button
            type="button"
            className="text-indigo-600 hover:underline dark:text-indigo-400"
            onClick={() => {
              setUseRecovery(!useRecovery);
              setCode("");
              setError(null);
            }}
          >
            {useRecovery ? t.auth.twoFactor.useTotp : t.auth.twoFactor.useRecovery}
          </button>
          <button type="button" className="text-zinc-500 hover:underline" onClick={() => void logout()}>
            {t.auth.twoFactor.restart}
          </button>
        </div>
      </form>
    </AuthCard>
  );
}
