/** Utilidades compartidas por las vistas de administración. */

export const formatCurrency = (value: number, moneda = 'USD') =>
  new Intl.NumberFormat('es-US', {
    style: 'currency',
    currency: moneda,
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(Number.isFinite(value) ? value : 0);

export const formatPercent = (value: number) =>
  `${value >= 0 ? '+' : ''}${(Number.isFinite(value) ? value : 0).toFixed(1)}%`;

export const formatCantidad = (value: number) =>
  new Intl.NumberFormat('es-US', { maximumFractionDigits: 4 }).format(value);

export const formatFecha = (value?: string) =>
  value
    ? new Date(value).toLocaleDateString('es-US', { day: '2-digit', month: 'short', year: 'numeric' })
    : '—';

export const iniciales = (nombre: string, apellido: string) =>
  `${nombre?.[0] ?? ''}${apellido?.[0] ?? ''}`.toUpperCase() || '?';

/** Paleta de las gráficas: se recorre en orden y se repite si hace falta. */
export const PALETA = [
  '#06B6D4', '#10B981', '#F97316', '#A855F7',
  '#EC4899', '#3B82F6', '#F59E0B', '#14B8A6',
];

export const colorPorIndice = (index: number) => PALETA[index % PALETA.length];

export const TIPO_ACTIVO_LABEL: Record<string, string> = {
  crypto: 'Criptomonedas',
  accion: 'Acciones',
  etf: 'ETFs',
  bono: 'Renta Fija',
  commodity: 'Commodities',
  fiat: 'Efectivo',
};

export const ESTRATEGIA_LABEL: Record<string, string> = {
  growth: 'Crecimiento',
  income: 'Ingresos',
  balanced: 'Balanceado',
  especulativo: 'Especulativo',
};

export const ALERTA_TIPO_LABEL: Record<string, string> = {
  precio_minimo: 'Caída',
  precio_maximo: 'Objetivo',
  variacion_porcentual: 'Volatilidad',
};

/** Signo con el que cada tipo de transacción mueve el capital del inversionista. */
export const SIGNO_TRANSACCION: Record<string, number> = {
  compra: 1,
  deposito: 1,
  dividendo: 1,
  venta: -1,
  retiro: -1,
  fee: -1,
};
