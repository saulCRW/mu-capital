import { useMemo } from "react";
import { PieChart, Pie, Cell, Tooltip, ResponsiveContainer } from "recharts";
import { Card, CardContent } from "../ui/card";
import { colorPorIndice, formatCurrency } from "./format";

export interface ItemDesglose {
  id: string | number;
  label: string;
  sublabel?: string;
  valor: number;
}

interface Props {
  titulo: string;
  items: ItemDesglose[];
  vacio: string;
  onSelect?: (id: string | number) => void;
}

/** Cuántas porciones se dibujan antes de agrupar el resto en "Otros". */
const MAX_PORCIONES = 8;
const COLOR_OTROS = "#475569";

/**
 * Desglose de un indicador: gráfica de pastel con la composición y la lista
 * de los elementos que la forman. Se abre desde la tarjeta del indicador.
 */
export function PanelDesglose({ titulo, items, vacio, onSelect }: Props) {
  const ordenados = useMemo(() => [...items].sort((a, b) => b.valor - a.valor), [items]);
  const total = useMemo(() => ordenados.reduce((suma, i) => suma + i.valor, 0), [ordenados]);

  const datosPastel = useMemo(() => {
    const principales = ordenados.slice(0, MAX_PORCIONES).map((item, i) => ({
      name: item.label,
      value: Math.round(item.valor * 100) / 100,
      color: colorPorIndice(i),
    }));

    const resto = ordenados.slice(MAX_PORCIONES).reduce((suma, i) => suma + i.valor, 0);
    if (resto > 0) {
      principales.push({ name: "Otros", value: Math.round(resto * 100) / 100, color: COLOR_OTROS });
    }

    return principales.filter((d) => d.value > 0);
  }, [ordenados]);

  const colorDe = (index: number) => (index < MAX_PORCIONES ? colorPorIndice(index) : COLOR_OTROS);
  const porcentaje = (valor: number) => (total > 0 ? `${((valor / total) * 100).toFixed(1)}%` : "—");

  return (
    <Card className="bg-slate-900/50 border-cyan-500/30 mb-8">
      <CardContent className="p-6">
        {ordenados.length === 0 || total <= 0 ? (
          <div className="py-10 text-center text-slate-400">{vacio}</div>
        ) : (
          <div className="grid grid-cols-1 lg:grid-cols-5 gap-6">
            <div className="lg:col-span-2">
              <p className="text-sm text-slate-400 mb-2">{titulo}</p>
              <ResponsiveContainer width="100%" height={240}>
                <PieChart>
                  <Pie
                    data={datosPastel}
                    cx="50%"
                    cy="50%"
                    innerRadius={55}
                    outerRadius={95}
                    paddingAngle={2}
                    dataKey="value"
                  >
                    {datosPastel.map((entry) => (
                      <Cell key={entry.name} fill={entry.color} />
                    ))}
                  </Pie>
                  <Tooltip
                    formatter={(value: number) => formatCurrency(value)}
                    contentStyle={{ backgroundColor: "#0F172A", border: "1px solid #334155", borderRadius: "8px" }}
                  />
                </PieChart>
              </ResponsiveContainer>
            </div>

            <div className="lg:col-span-3">
              <div className="flex items-center justify-between text-sm text-slate-400 mb-2">
                <span>{ordenados.length} elementos</span>
                <span>Total {formatCurrency(total)}</span>
              </div>
              <div className="max-h-[260px] overflow-y-auto pr-1 space-y-1">
                {ordenados.map((item, index) => {
                  const Fila = onSelect ? "button" : "div";
                  return (
                    <Fila
                      key={item.id}
                      {...(onSelect ? { onClick: () => onSelect(item.id), type: "button" as const } : {})}
                      className={`w-full text-left flex items-center gap-3 px-3 py-2 rounded-lg transition-colors ${
                        onSelect ? "hover:bg-slate-800/70 cursor-pointer" : ""
                      }`}
                    >
                      <span
                        className="w-2.5 h-2.5 rounded-full shrink-0"
                        style={{ backgroundColor: colorDe(index) }}
                      />
                      <span className="min-w-0 flex-1">
                        <span className="block text-white text-sm truncate">{item.label}</span>
                        {item.sublabel && (
                          <span className="block text-xs text-slate-500 truncate">{item.sublabel}</span>
                        )}
                      </span>
                      <span className="text-white text-sm font-medium whitespace-nowrap">
                        {formatCurrency(item.valor)}
                      </span>
                      <span className="text-xs text-slate-400 w-14 text-right">{porcentaje(item.valor)}</span>
                    </Fila>
                  );
                })}
              </div>
            </div>
          </div>
        )}
      </CardContent>
    </Card>
  );
}
