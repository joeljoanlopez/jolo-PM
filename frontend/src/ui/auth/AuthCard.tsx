import { ShieldCheck } from "lucide-react";
import type { ReactNode } from "react";

/** Marco común de las pantallas de login y del asistente. */
export function AuthCard({ title, children }: { title: string; children: ReactNode }) {
  return (
    <div className="mx-auto mt-6 max-w-md rounded-xl border border-zinc-200 bg-white p-6 shadow-sm sm:mt-12 dark:border-zinc-800 dark:bg-zinc-900">
      <h1 className="mb-4 flex items-center gap-2 text-lg font-semibold tracking-tight">
        <ShieldCheck className="size-5 text-indigo-600 dark:text-indigo-400" aria-hidden />
        {title}
      </h1>
      {children}
    </div>
  );
}

/** El campo de un código TOTP: solo cifras, y el móvil propone el último código recibido. */
export const totpInputProps = {
  inputMode: "numeric",
  autoComplete: "one-time-code",
  pattern: "[0-9 ]*",
  maxLength: 7,
  required: true,
  spellCheck: false,
} as const;
