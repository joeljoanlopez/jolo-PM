import { useSyncExternalStore } from "react";
import { getLanguage, setLanguage, subscribeLanguage } from "../i18n/language";
import { messages } from "../i18n/messages";

/** Idioma actual y sus textos; el componente se repinta al cambiarlo. */
export function useI18n() {
  const language = useSyncExternalStore(subscribeLanguage, getLanguage);
  return { language, t: messages[language], setLanguage };
}
