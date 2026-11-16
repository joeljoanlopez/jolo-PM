import { Check, Copy, Download } from "lucide-react";
import { useState } from "react";
import { Button } from "../components/ui";
import { useI18n } from "../i18n";

/** Los códigos de recuperación, para copiarlos o descargarlos. Solo se ven esta vez. */
export function RecoveryCodesList({ codes }: { codes: string[] }) {
  const { t } = useI18n();
  const [copied, setCopied] = useState(false);
  const text = codes.join("\n") + "\n";

  async function copy() {
    try {
      await navigator.clipboard.writeText(text);
      setCopied(true);
      window.setTimeout(() => setCopied(false), 2000);
    } catch {
      // Sin portapapeles (HTTP sin TLS…): quedan a la vista para copiarlos a mano.
    }
  }

  function download() {
    const url = URL.createObjectURL(new Blob([text], { type: "text/plain" }));
    const link = Object.assign(document.createElement("a"), { href: url, download: "jolo-pm-recovery-codes.txt" });
    link.click();
    URL.revokeObjectURL(url);
  }

  return (
    <div className="space-y-3">
      <ol className="grid grid-cols-2 gap-x-6 gap-y-1 rounded-md border border-zinc-200 bg-zinc-50 p-3 font-mono text-sm dark:border-zinc-800 dark:bg-zinc-950">
        {codes.map((code) => (
          <li key={code} className="select-all">{code}</li>
        ))}
      </ol>
      <div className="flex gap-2">
        <Button onClick={copy}>
          {copied ? <Check className="size-4" aria-hidden /> : <Copy className="size-4" aria-hidden />}
          {copied ? t.auth.copied : t.auth.copy}
        </Button>
        <Button onClick={download}>
          <Download className="size-4" aria-hidden /> {t.auth.download}
        </Button>
      </div>
    </div>
  );
}
