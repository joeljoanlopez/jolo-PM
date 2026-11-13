import { Check, Copy, Download } from "lucide-react";
import { useState } from "react";
import { Button } from "../components/ui";
import { useI18n } from "../i18n";

/** Los códigos de recuperación recién generados, para copiarlos o descargarlos. */
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
      // Sin portapapeles (HTTP sin localhost…): quedan los códigos a la vista.
    }
  }

  function download() {
    const url = URL.createObjectURL(new Blob([text], { type: "text/plain" }));
    const link = document.createElement("a");
    link.href = url;
    link.download = "jolo-pm-recovery-codes.txt";
    link.click();
    URL.revokeObjectURL(url);
  }

  return (
    <div className="space-y-3">
      <ul className="grid grid-cols-2 gap-x-6 gap-y-1 rounded-md border border-zinc-200 bg-zinc-50 px-4 py-3 font-mono text-sm dark:border-zinc-800 dark:bg-zinc-950">
        {codes.map((code) => (
          <li key={code}>{code}</li>
        ))}
      </ul>
      <div className="flex gap-2">
        <Button onClick={copy}>
          {copied ? <Check className="size-4" aria-hidden /> : <Copy className="size-4" aria-hidden />}
          {copied ? t.recovery.copied : t.recovery.copy}
        </Button>
        <Button onClick={download}>
          <Download className="size-4" aria-hidden /> {t.recovery.download}
        </Button>
      </div>
    </div>
  );
}
