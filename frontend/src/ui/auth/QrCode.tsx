import { useMemo } from "react";
import { encode } from "uqr";

/** QR en SVG, generado en el navegador: el secreto no sale a ningún servicio. */
export function QrCode({ value, label }: { value: string; label: string }) {
  const { size, path } = useMemo(() => {
    const qr = encode(value, { ecc: "M", border: 2 });
    let d = "";
    qr.data.forEach((row, y) => row.forEach((dark, x) => dark && (d += `M${x},${y}h1v1h-1z`)));
    return { size: qr.size, path: d };
  }, [value]);

  return (
    <svg
      role="img"
      aria-label={label}
      viewBox={`0 0 ${size} ${size}`}
      shapeRendering="crispEdges"
      className="size-48 rounded-md bg-white"
    >
      <path d={path} fill="#000" />
    </svg>
  );
}
