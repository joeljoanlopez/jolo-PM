import { LANGUAGES } from "../../i18n/messages";
import { useI18n } from "../i18n";

/** Botones ES | EN en la cabecera. */
export function LanguageSwitcher() {
  const { language, setLanguage, t } = useI18n();

  return (
    <div role="group" aria-label={t.app.language} className="flex rounded-md border border-zinc-300 p-0.5 text-xs font-medium dark:border-zinc-700">
      {LANGUAGES.map((code) => (
        <button
          key={code}
          type="button"
          lang={code}
          aria-pressed={code === language}
          onClick={() => setLanguage(code)}
          className={`rounded px-2 py-0.5 uppercase transition-colors focus-visible:outline-2 focus-visible:outline-indigo-500 ${
            code === language
              ? "bg-indigo-600 text-white"
              : "text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
          }`}
        >
          {code}
        </button>
      ))}
    </div>
  );
}
