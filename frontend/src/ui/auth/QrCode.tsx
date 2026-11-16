import { useMemo } from "react";
import { encode } from "uqr";

/** QR en SVG, siempre negro sobre blanco (en modo oscuro también) para que las cámaras lo lean. */
export function QrCode({ value, label, className = "size-48" }: { value: string; label: string; className?: string }) {
  const { size, path } = useMemo(() => {
    // border: zona de silencio de 4 módulos que pide la norma.
    const { size, data } = encode(value, { ecc: "M", border: 4 });
    let d = "";
    data.forEach((row, y) => row.forEach((dark, x) => dark && (d += `M${x},${y}h1v1h-1z`)));
    return { size, path: d };
  }, [value]);

  return (
    <svg
      role="img"
      aria-label={label}
      viewBox={`0 0 ${size} ${size}`}
      shapeRendering="crispEdges"
      className={`rounded-md bg-white ${className}`}
    >
      <path d={path} fill="#000" />
    </svg>
  );
}
