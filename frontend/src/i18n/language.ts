// Idioma elegido, fuera de React para que también lo use el adaptador HTTP
// (cabecera Accept-Language). Se recuerda en el navegador; la primera vez sale
// del idioma del navegador, y si no es ninguno de los que hay, castellano.
import { detectLanguage, type Language } from "./messages";

const STORAGE_KEY = "jolo-pm.language";
const listeners = new Set<() => void>();

function readSaved(): string | null {
  try {
    return window.localStorage.getItem(STORAGE_KEY);
  } catch {
    return null;
  }
}

let current: Language = detectLanguage(readSaved(), navigator.languages ?? [navigator.language]);
document.documentElement.lang = current;

export function getLanguage(): Language {
  return current;
}

export function setLanguage(language: Language) {
  if (language === current) return;
  current = language;
  document.documentElement.lang = language;
  try {
    window.localStorage.setItem(STORAGE_KEY, language);
  } catch {
    // Sin almacenamiento (modo privado…): vale para esta visita.
  }
  listeners.forEach((listener) => listener());
}

export function subscribeLanguage(listener: () => void): () => void {
  listeners.add(listener);
  return () => listeners.delete(listener);
}
