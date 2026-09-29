import { Wallet, Coins, ArrowRight, AlertTriangle, TrendingUp, TrendingDown } from "lucide-react";
import type { Activo, Portafolio } from "../../models/types";
import { USD_MXN, aMxn, formatCantidad, formatMoneda, formatMxn } from "../../models/config";

interface Props {
  portafolio?: Portafolio;
  activo?: Activo;
  cantidad: number;
  precioUnitario: number;
  tipo: "compra" | "venta";
}

/** Línea "etiqueta … valor", con el equivalente en pesos debajo si aplica. */
function Renglon({
  etiqueta,
  valor,
  enPesos,
  destacado = false,
}: {
  etiqueta: string;
  valor: string;
  enPesos?: number | null;
  destacado?: boolean;
}) {
  return (
    <div className="flex items-start justify-between gap-4">
      <span className="text-slate-400 text-sm">{etiqueta}</span>
      <div className="text-right">
        <div className={destacado ? "text-2xl font-bold text-white" : "text-white font-medium"}>{valor}</div>
        {enPesos !== null && enPesos !== undefined && (
          <div className="text-xs text-slate-500">≈ {formatMxn(enPesos)}</div>
        )}
      </div>
    </div>
  );
}

/**
 * Referencia en vivo antes de ejecutar una transacción: capital del
 * portafolio, posición actual en el activo elegido y cómo quedarían ambos
 * si se registra la operación.
 */
export function ResumenOperacion({ portafolio, activo, cantidad, precioUnitario, tipo }: Props) {
  const moneda = portafolio?.moneda ?? "USD";
  const esCompra = tipo === "compra";

  const capitalActual = (portafolio?.activos ?? []).reduce(
    (suma, a) => suma + Number(a.pivot?.cantidad ?? 0) * Number(a.pivot?.precio_promedio ?? 0),
    0
  );

  const tenencia = portafolio?.activos?.find((a) => a.id === activo?.id);
  const cantidadActual = Number(tenencia?.pivot?.cantidad ?? 0);
  const precioPromedio = Number(tenencia?.pivot?.precio_promedio ?? 0);
  const valorPosicion = cantidadActual * precioPromedio;

  // Con solo la cantidad ya se puede proyectar; si falta el precio unitario
  // usamos el precio promedio de la posición como referencia.
  const usaPromedio = precioUnitario <= 0 && precioPromedio > 0;
  const precioReferencia = precioUnitario > 0 ? precioUnitario : precioPromedio;
  const hayProyeccion = cantidad > 0 && Boolean(portafolio) && Boolean(activo);

  const montoOperacion = cantidad * precioReferencia;
  const cantidadFinal = esCompra ? cantidadActual + cantidad : cantidadActual - cantidad;
  const excedeTenencia = !esCompra && cantidad > cantidadActual;

  // En la venta se libera el costo promedio de las unidades que salen.
  const costoLiberado = Math.min(cantidad, cantidadActual) * precioPromedio;
  const capitalFinal = esCompra ? capitalActual + montoOperacion : capitalActual - costoLiberado;

  const promedioFinal = esCompra
    ? cantidadFinal > 0
      ? (valorPosicion + montoOperacion) / cantidadFinal
      : 0
    : precioPromedio;
  const valorPosicionFinal = Math.max(cantidadFinal, 0) * promedioFinal;

  return (
    <div className="rounded-lg border border-slate-700 bg-slate-800/30 divide-y divide-slate-700">
      {/* 1. Capital del portafolio */}
      <div className="p-4">
        <div className="flex items-center gap-2 mb-3">
          <div className="bg-cyan-500/20 p-1.5 rounded-md">
            <Wallet className="w-4 h-4 text-cyan-400" />
          </div>
          <span className="text-slate-300 text-sm font-medium">Capital total del portafolio</span>
        </div>
        {portafolio ? (
          <Renglon
            etiqueta={portafolio.nombre}
            valor={formatMoneda(capitalActual, moneda)}
            enPesos={aMxn(capitalActual, moneda)}
            destacado
          />
        ) : (
          <p className="text-slate-500 text-sm">Selecciona un portafolio para ver su capital.</p>
        )}
      </div>

      {/* 2. Posición actual en el activo elegido */}
      <div className="p-4">
        <div className="flex items-center gap-2 mb-3">
          <div className="bg-emerald-500/20 p-1.5 rounded-md">
            <Coins className="w-4 h-4 text-emerald-400" />
          </div>
          <span className="text-slate-300 text-sm font-medium">
            {activo ? `Lo que tienes de ${activo.simbolo}` : "Lo que tienes del activo"}
          </span>
        </div>
        {!portafolio || !activo ? (
          <p className="text-slate-500 text-sm">Selecciona un portafolio y un activo para ver tu posición.</p>
        ) : cantidadActual <= 0 ? (
          <p className="text-slate-500 text-sm">
            Todavía no tienes {activo.simbolo} en {portafolio.nombre}.
          </p>
        ) : (
          <div className="space-y-2">
            <Renglon etiqueta="Cantidad" valor={`${formatCantidad(cantidadActual)} ${activo.simbolo}`} />
            <Renglon etiqueta="Precio promedio" valor={formatMoneda(precioPromedio, moneda)} />
            <Renglon
              etiqueta="Valor de la posición"
              valor={formatMoneda(valorPosicion, moneda)}
              enPesos={aMxn(valorPosicion, moneda)}
            />
          </div>
        )}
      </div>

      {/* 3. Proyección de la operación */}
      <div className="p-4">
        <div className="flex items-center gap-2 mb-3">
          <div className={`p-1.5 rounded-md ${esCompra ? "bg-emerald-500/20" : "bg-orange-500/20"}`}>
            {esCompra ? (
              <TrendingUp className="w-4 h-4 text-emerald-400" />
            ) : (
              <TrendingDown className="w-4 h-4 text-orange-400" />
            )}
          </div>
          <span className="text-slate-300 text-sm font-medium">
            Cómo quedarías si {esCompra ? "compras" : "vendes"}
          </span>
        </div>

        {!hayProyeccion ? (
          <p className="text-slate-500 text-sm">
            Escribe la cantidad para ver cómo queda tu posición antes de ejecutar.
          </p>
        ) : (
          <div className="space-y-3">
            <div className="flex items-center justify-between gap-4">
              <span className="text-slate-400 text-sm">Cantidad de {activo!.simbolo}</span>
              <div className="flex items-center gap-2 text-right">
                <span className="text-slate-500">{formatCantidad(cantidadActual)}</span>
                <ArrowRight className="w-3.5 h-3.5 text-slate-600" />
                <span className={`font-semibold ${excedeTenencia ? "text-red-400" : "text-white"}`}>
                  {formatCantidad(cantidadFinal)}
                </span>
              </div>
            </div>

            <div className="flex items-center justify-between gap-4">
              <span className="text-slate-400 text-sm">Valor de la posición</span>
              <div className="flex items-center gap-2 text-right">
                <span className="text-slate-500">{formatMoneda(valorPosicion, moneda)}</span>
                <ArrowRight className="w-3.5 h-3.5 text-slate-600" />
                <span className="text-white font-semibold">{formatMoneda(valorPosicionFinal, moneda)}</span>
              </div>
            </div>

            <div className="flex items-center justify-between gap-4">
              <span className="text-slate-400 text-sm">Capital del portafolio</span>
              <div className="flex items-center gap-2 text-right">
                <span className="text-slate-500">{formatMoneda(capitalActual, moneda)}</span>
                <ArrowRight className="w-3.5 h-3.5 text-slate-600" />
                <span className="text-white font-semibold">{formatMoneda(capitalFinal, moneda)}</span>
              </div>
            </div>

            <div className="pt-2 border-t border-slate-700">
              <Renglon
                etiqueta={esCompra ? "Lo que pagas" : "Lo que recibes"}
                valor={formatMoneda(montoOperacion, moneda)}
                enPesos={aMxn(montoOperacion, moneda)}
              />
              {usaPromedio && (
                <p className="text-xs text-slate-500 mt-2">
                  Calculado con tu precio promedio; escribe el precio unitario para afinarlo.
                </p>
              )}
            </div>

            {excedeTenencia && (
              <div className="flex gap-2 items-start bg-red-500/10 border border-red-500/30 rounded-lg p-3">
                <AlertTriangle className="w-4 h-4 text-red-400 mt-0.5 shrink-0" />
                <p className="text-red-300 text-sm">
                  Estás vendiendo más {activo!.simbolo} del que tienes ({formatCantidad(cantidadActual)} disponibles).
                </p>
              </div>
            )}
          </div>
        )}
      </div>

      <div className="px-4 py-2">
        <p className="text-[11px] text-slate-600">Equivalencias en pesos con 1 USD = {USD_MXN} MXN.</p>
      </div>
    </div>
  );
}
