import { useCallback, useEffect, useRef, useState } from "react";
import { useI18n } from "./i18n";

/**
 * Carga datos asíncronos con recarga manual y, opcionalmente, periódica.
 * Las recargas no vacían los datos: se sigue viendo lo anterior mientras llega lo nuevo.
 * Al cambiar de idioma también recarga, porque los errores de la API vienen traducidos.
 */
export function useAsync<T>(load: () => Promise<T>, deps: unknown[], refreshMs?: number) {
  const [data, setData] = useState<T | null>(null);
  const [error, setError] = useState<Error | null>(null);
  const [loading, setLoading] = useState(true);
  const loadRef = useRef(load);
  loadRef.current = load;

  const reload = useCallback(async () => {
    setLoading(true);
    try {
      setData(await loadRef.current());
      setError(null);
    } catch (e) {
      setError(e as Error);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    setData(null);
    void reload();
    if (!refreshMs) return;
    const timer = window.setInterval(() => {
      if (document.visibilityState === "visible") void reload();
    }, refreshMs);
    return () => window.clearInterval(timer);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, deps);

  const { language } = useI18n();
  const loadedLanguage = useRef(language);
  useEffect(() => {
    if (language === loadedLanguage.current) return;
    loadedLanguage.current = language;
    void reload();
  }, [language, reload]);

  return { data, setData, error, loading, reload };
}

export type Route = { name: "home" } | { name: "project"; id: number };

function parseHash(hash: string): Route {
  const match = /^#\/projects\/(\d+)/.exec(hash);
  return match ? { name: "project", id: Number(match[1]) } : { name: "home" };
}

/** Enrutado mínimo por hash: #/ y #/projects/{id}. */
export function useRoute(): Route {
  const [route, setRoute] = useState(() => parseHash(window.location.hash));
  useEffect(() => {
    const onChange = () => setRoute(parseHash(window.location.hash));
    window.addEventListener("hashchange", onChange);
    return () => window.removeEventListener("hashchange", onChange);
  }, []);
  return route;
}

export const navigate = (hash: string) => {
  window.location.hash = hash;
};
