/**
 * Conversión y formato de montos.
 *
 * El tipo de cambio es un valor fijo: ajústalo aquí mientras el backend no
 * exponga cotizaciones reales (tabla historial_precios).
 */
export const USD_MXN = 17;

/** Convierte un monto a pesos. Devuelve null si la moneda no se puede convertir. */
export function aMxn(monto: number, moneda?: string): number | null {
  const codigo = (moneda ?? 'USD').toUpperCase();
  if (codigo === 'MXN') return monto;
  if (codigo === 'USD') return monto * USD_MXN;
  return null;
}

export const formatMoneda = (monto: number, moneda = 'USD') =>
  new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: (moneda || 'USD').toUpperCase(),
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(Number.isFinite(monto) ? monto : 0);

export const formatMxn = (monto: number) => formatMoneda(monto, 'MXN');

export const formatCantidad = (cantidad: number) =>
  new Intl.NumberFormat('es-MX', { maximumFractionDigits: 8 }).format(
    Number.isFinite(cantidad) ? cantidad : 0
  );
