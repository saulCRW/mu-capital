import { useMemo, useState } from "react";
import {
  AreaChart,
  Area,
  PieChart,
  Pie,
  Cell,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
} from "recharts";
import { Card, CardContent, CardHeader, CardTitle } from "../ui/card";
import { Badge } from "../ui/badge";
import { Button } from "../ui/button";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "../ui/table";
import {
  X, Wallet, TrendingUp, Layers, Briefcase, Bell, ArrowUpRight, ArrowDownRight, Mail, MapPin, ChevronDown,
} from "lucide-react";
import { useUsuarioDetalle } from "../../../controllers/useUsuarioDetalle";
import {
  ALERTA_TIPO_LABEL,
  ESTRATEGIA_LABEL,
  SIGNO_TRANSACCION,
  TIPO_ACTIVO_LABEL,
  colorPorIndice,
  formatCantidad,
  formatCurrency,
  formatFecha,
  formatPercent,
  iniciales,
} from "./format";

interface Props {
  usuarioId: number;
  onClose: () => void;
}

type ModoGrafica = "activo" | "tipo";

const etiquetaMes = (clave: string) => {
  const [anio, mes] = clave.split("-");
  const fecha = new Date(Number(anio), Number(mes) - 1, 1);
  return fecha.toLocaleDateString("es-US", { month: "short", year: "2-digit" });
};

export function InversionistaDetalle({ usuarioId, onClose }: Props) {
  const { usuario, loading, error } = useUsuarioDetalle(usuarioId);
  const [portafolioSel, setPortafolioSel] = useState<number | null>(null);
  const [modoGrafica, setModoGrafica] = useState<ModoGrafica>("activo");
  const [portafoliosAbierto, setPortafoliosAbierto] = useState(false);

  const portafolios = usuario?.portafolios ?? [];

  // El chip "Todos" (portafolioSel === null) agrega la cartera completa.
  const portafoliosVisibles = useMemo(
    () => (portafolioSel === null ? portafolios : portafolios.filter((p) => p.id === portafolioSel)),
    [portafolios, portafolioSel]
  );

  const activosAgregados = useMemo(() => {
    const mapa = new Map<string, { simbolo: string; nombre: string; tipo: string; cantidad: number; valor: number }>();

    for (const portafolio of portafoliosVisibles) {
      for (const activo of portafolio.activos) {
        const previo = mapa.get(activo.simbolo);
        if (previo) {
          previo.cantidad += Number(activo.cantidad);
          previo.valor += Number(activo.valor_invertido);
        } else {
          mapa.set(activo.simbolo, {
            simbolo: activo.simbolo,
            nombre: activo.nombre,
            tipo: activo.tipo,
            cantidad: Number(activo.cantidad),
            valor: Number(activo.valor_invertido),
          });
        }
      }
    }

    return [...mapa.values()].sort((a, b) => b.valor - a.valor);
  }, [portafoliosVisibles]);

  const totalVisible = useMemo(
    () => activosAgregados.reduce((suma, a) => suma + a.valor, 0),
    [activosAgregados]
  );

  const datosPastel = useMemo(() => {
    if (modoGrafica === "activo") {
      return activosAgregados.map((a, i) => ({
        name: a.simbolo,
        value: Math.round(a.valor * 100) / 100,
        color: colorPorIndice(i),
      }));
    }

    const porTipo = new Map<string, number>();
    for (const a of activosAgregados) {
      porTipo.set(a.tipo, (porTipo.get(a.tipo) ?? 0) + a.valor);
    }

    return [...porTipo.entries()]
      .sort((a, b) => b[1] - a[1])
      .map(([tipo, valor], i) => ({
        name: TIPO_ACTIVO_LABEL[tipo] ?? tipo,
        value: Math.round(valor * 100) / 100,
        color: colorPorIndice(i),
      }));
  }, [activosAgregados, modoGrafica]);

  // Curva de capital acumulado a partir de las transacciones del inversionista.
  const evolucion = useMemo(() => {
    const transacciones = [...(usuario?.transacciones ?? [])].sort(
      (a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime()
    );

    let acumulado = 0;
    const porMes = new Map<string, number>();

    for (const t of transacciones) {
      acumulado += (SIGNO_TRANSACCION[t.tipo] ?? 0) * Number(t.monto_total ?? 0);
      const fecha = new Date(t.created_at);
      const clave = `${fecha.getFullYear()}-${String(fecha.getMonth() + 1).padStart(2, "0")}`;
      porMes.set(clave, acumulado);
    }

    return [...porMes.entries()].map(([clave, valor]) => ({
      mes: etiquetaMes(clave),
      capital: Math.round(valor),
    }));
  }, [usuario]);

  // Composición de la cartera por portafolio (no por activo).
  const datosPorPortafolio = useMemo(
    () =>
      portafolios
        .map((p, i) => ({
          name: p.nombre,
          value: Math.round(p.valor_invertido * 100) / 100,
          color: p.color || colorPorIndice(i),
        }))
        .filter((d) => d.value > 0),
    [portafolios]
  );

  const totalPortafolios = useMemo(
    () => datosPorPortafolio.reduce((suma, d) => suma + d.value, 0),
    [datosPorPortafolio]
  );

  const transaccionesRecientes = (usuario?.transacciones ?? []).slice(0, 6);

  if (loading) {
    return (
      <Card className="bg-slate-900/50 border-slate-800">
        <CardContent className="py-16 text-center text-slate-400">Cargando cartera del inversionista...</CardContent>
      </Card>
    );
  }

  if (error || !usuario) {
    return (
      <Card className="bg-slate-900/50 border-slate-800">
        <CardContent className="py-16 text-center">
          <p className="text-slate-300 mb-4">{error ?? "Inversionista no encontrado"}</p>
          <Button variant="outline" className="border-slate-700 text-slate-300 hover:bg-slate-800" onClick={onClose}>
            Cerrar
          </Button>
        </CardContent>
      </Card>
    );
  }

  const rendimientoPositivo = usuario.rendimiento >= 0;

  const kpis = [
    {
      label: "Capital Invertido",
      value: formatCurrency(usuario.valor_invertido),
      icon: Wallet,
      gradient: "from-cyan-500 to-blue-500",
    },
    {
      label: "Valor Actual",
      value: formatCurrency(usuario.valor_actual),
      icon: TrendingUp,
      gradient: "from-emerald-500 to-green-500",
    },
    {
      label: "Rendimiento",
      value: formatPercent(usuario.rendimiento),
      icon: rendimientoPositivo ? ArrowUpRight : ArrowDownRight,
      gradient: rendimientoPositivo ? "from-emerald-500 to-teal-500" : "from-red-500 to-orange-500",
    },
    {
      label: "Portafolios",
      value: String(usuario.portafolios_count),
      icon: Briefcase,
      gradient: "from-purple-500 to-fuchsia-500",
    },
  ];

  return (
    <div className="space-y-6">
      {/* Cabecera del inversionista */}
      <Card className="bg-slate-900/50 border-cyan-500/30">
        <CardContent className="p-6">
          <div className="flex flex-col md:flex-row md:items-center gap-4 justify-between">
            <div className="flex items-center gap-4">
              <div className="w-14 h-14 rounded-full bg-gradient-to-br from-cyan-500 to-emerald-500 flex items-center justify-center text-white text-lg font-bold shadow-lg shadow-cyan-500/30">
                {iniciales(usuario.nombre, usuario.apellido)}
              </div>
              <div>
                <h2 className="text-2xl font-bold text-white">
                  {usuario.nombre} {usuario.apellido}
                </h2>
                <div className="flex flex-wrap items-center gap-3 text-sm text-slate-400 mt-1">
                  <span className="flex items-center gap-1">
                    <Mail className="w-3.5 h-3.5" />
                    {usuario.email}
                  </span>
                  {usuario.pais && (
                    <span className="flex items-center gap-1">
                      <MapPin className="w-3.5 h-3.5" />
                      {usuario.pais}
                    </span>
                  )}
                  <Badge className="bg-cyan-500/20 text-cyan-400 border-cyan-500/50">{usuario.rol?.nombre ?? "—"}</Badge>
                  <Badge
                    className={
                      usuario.activo
                        ? "bg-emerald-500/20 text-emerald-400 border-emerald-500/50"
                        : "bg-slate-500/20 text-slate-400 border-slate-500/50"
                    }
                  >
                    {usuario.activo ? "Activo" : "Inactivo"}
                  </Badge>
                </div>
              </div>
            </div>
            <Button
              variant="ghost"
              onClick={onClose}
              className="text-slate-400 hover:text-white self-start"
              aria-label="Cerrar detalle"
            >
              <X className="w-4 h-4 mr-2" />
              Cerrar
            </Button>
          </div>

          <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
            {kpis.map((kpi) => (
              <div key={kpi.label} className="bg-slate-800/50 rounded-lg p-4 border border-slate-700">
                <div className="flex items-center gap-2 mb-2">
                  <div className={`bg-gradient-to-br ${kpi.gradient} p-1.5 rounded-md`}>
                    <kpi.icon className="w-4 h-4 text-white" />
                  </div>
                  <span className="text-xs text-slate-400">{kpi.label}</span>
                </div>
                <div className="text-xl font-bold text-white">{kpi.value}</div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>

      {portafolios.length === 0 ? (
        <Card className="bg-slate-900/50 border-slate-800">
          <CardContent className="py-12 text-center text-slate-400">
            Este inversionista todavía no tiene portafolios registrados.
          </CardContent>
        </Card>
      ) : (
        <>
          {/* Selector de portafolio */}
          <div className="flex flex-wrap gap-2">
            <button
              onClick={() => setPortafolioSel(null)}
              className={`px-4 py-2 rounded-lg text-sm border transition-colors ${
                portafolioSel === null
                  ? "bg-cyan-500/20 border-cyan-500/50 text-cyan-300"
                  : "bg-slate-900/50 border-slate-700 text-slate-400 hover:text-white hover:border-slate-600"
              }`}
            >
              Todos los portafolios
            </button>
            {portafolios.map((p) => (
              <button
                key={p.id}
                onClick={() => setPortafolioSel(p.id)}
                className={`px-4 py-2 rounded-lg text-sm border transition-colors ${
                  portafolioSel === p.id
                    ? "bg-cyan-500/20 border-cyan-500/50 text-cyan-300"
                    : "bg-slate-900/50 border-slate-700 text-slate-400 hover:text-white hover:border-slate-600"
                }`}
              >
                {p.nombre}
              </button>
            ))}
          </div>

          {/* Gráficas */}
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <Card className="bg-slate-900/50 border-slate-800">
              <CardHeader>
                <div className="flex items-center justify-between gap-2">
                  <CardTitle className="text-white bg-gradient-to-r from-orange-400 to-pink-400 bg-clip-text text-transparent">
                    Distribución
                  </CardTitle>
                  <div className="flex rounded-lg border border-slate-700 overflow-hidden text-xs">
                    <button
                      onClick={() => setModoGrafica("activo")}
                      className={`px-3 py-1.5 ${modoGrafica === "activo" ? "bg-cyan-500/20 text-cyan-300" : "text-slate-400 hover:text-white"}`}
                    >
                      Por activo
                    </button>
                    <button
                      onClick={() => setModoGrafica("tipo")}
                      className={`px-3 py-1.5 ${modoGrafica === "tipo" ? "bg-cyan-500/20 text-cyan-300" : "text-slate-400 hover:text-white"}`}
                    >
                      Por tipo
                    </button>
                  </div>
                </div>
              </CardHeader>
              <CardContent>
                {datosPastel.length === 0 ? (
                  <div className="h-[260px] flex items-center justify-center text-slate-400 text-sm text-center px-4">
                    Sin activos cargados en esta selección.
                  </div>
                ) : (
                  <>
                    <ResponsiveContainer width="100%" height={260}>
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
                    <div className="space-y-2 mt-4">
                      {datosPastel.map((item) => (
                        <div key={item.name} className="flex items-center justify-between text-sm">
                          <div className="flex items-center gap-2">
                            <div className="w-3 h-3 rounded-full" style={{ backgroundColor: item.color }} />
                            <span className="text-slate-300">{item.name}</span>
                          </div>
                          <span className="text-white font-medium">
                            {totalVisible > 0 ? `${((item.value / totalVisible) * 100).toFixed(1)}%` : "—"}
                          </span>
                        </div>
                      ))}
                    </div>
                  </>
                )}
              </CardContent>
            </Card>

            <Card className="lg:col-span-2 bg-slate-900/50 border-slate-800">
              <CardHeader>
                <CardTitle className="text-white bg-gradient-to-r from-cyan-400 to-emerald-400 bg-clip-text text-transparent">
                  Evolución del Capital
                </CardTitle>
                <p className="text-sm text-slate-400">Acumulado según las transacciones registradas</p>
              </CardHeader>
              <CardContent>
                {evolucion.length < 2 ? (
                  <div className="h-[300px] flex items-center justify-center text-slate-400 text-sm text-center px-4">
                    Se necesitan al menos dos meses con transacciones para dibujar la curva.
                  </div>
                ) : (
                  <ResponsiveContainer width="100%" height={300}>
                    <AreaChart data={evolucion}>
                      <defs>
                        <linearGradient id="capitalInversionista" x1="0" y1="0" x2="0" y2="1">
                          <stop offset="5%" stopColor="#06B6D4" stopOpacity={0.4} />
                          <stop offset="95%" stopColor="#06B6D4" stopOpacity={0} />
                        </linearGradient>
                      </defs>
                      <CartesianGrid strokeDasharray="3 3" stroke="#334155" />
                      <XAxis dataKey="mes" stroke="#94A3B8" />
                      <YAxis stroke="#94A3B8" />
                      <Tooltip
                        formatter={(value: number) => formatCurrency(value)}
                        contentStyle={{ backgroundColor: "#0F172A", border: "1px solid #334155", borderRadius: "8px" }}
                      />
                      <Area
                        type="monotone"
                        dataKey="capital"
                        name="Capital"
                        stroke="#06B6D4"
                        strokeWidth={3}
                        fill="url(#capitalInversionista)"
                      />
                    </AreaChart>
                  </ResponsiveContainer>
                )}
              </CardContent>
            </Card>
          </div>

          {/* Portafolios del inversionista: desplegable con su composición */}
          <Card className={`bg-slate-900/50 ${portafoliosAbierto ? "border-cyan-500/30" : "border-slate-800"}`}>
            <button
              type="button"
              onClick={() => setPortafoliosAbierto((prev) => !prev)}
              className="w-full flex items-center justify-between gap-4 p-6 text-left"
            >
              <div className="flex items-center gap-3">
                <div className="bg-gradient-to-br from-emerald-500 to-green-500 p-2 rounded-lg">
                  <Briefcase className="w-5 h-5 text-white" />
                </div>
                <div>
                  <div className="text-white font-semibold">Portafolios de {usuario.nombre}</div>
                  <div className="text-xs text-slate-500">
                    {portafolios.length} en total · {portafoliosAbierto ? "ocultar" : "ver composición"}
                  </div>
                </div>
              </div>
              <div className="flex items-center gap-3">
                <span className="text-white font-medium hidden sm:block">{formatCurrency(totalPortafolios)}</span>
                <ChevronDown
                  className={`w-4 h-4 transition-transform ${portafoliosAbierto ? "rotate-180 text-cyan-400" : "text-slate-500"}`}
                />
              </div>
            </button>
            {portafoliosAbierto && (
            <CardContent className="pt-0">
              <div className="grid grid-cols-1 lg:grid-cols-5 gap-6">
                <div className="lg:col-span-2">
                  {datosPorPortafolio.length === 0 ? (
                    <div className="h-[220px] flex items-center justify-center text-slate-400 text-sm text-center px-4">
                      Los portafolios todavía no tienen capital asignado.
                    </div>
                  ) : (
                    <>
                      <ResponsiveContainer width="100%" height={220}>
                        <PieChart>
                          <Pie
                            data={datosPorPortafolio}
                            cx="50%"
                            cy="50%"
                            innerRadius={50}
                            outerRadius={88}
                            paddingAngle={2}
                            dataKey="value"
                          >
                            {datosPorPortafolio.map((entry) => (
                              <Cell key={entry.name} fill={entry.color} />
                            ))}
                          </Pie>
                          <Tooltip
                            formatter={(value: number) => formatCurrency(value)}
                            contentStyle={{ backgroundColor: "#0F172A", border: "1px solid #334155", borderRadius: "8px" }}
                          />
                        </PieChart>
                      </ResponsiveContainer>
                      <div className="space-y-2 mt-3">
                        {datosPorPortafolio.map((item) => (
                          <div key={item.name} className="flex items-center justify-between text-sm">
                            <div className="flex items-center gap-2 min-w-0">
                              <div className="w-3 h-3 rounded-full shrink-0" style={{ backgroundColor: item.color }} />
                              <span className="text-slate-300 truncate">{item.name}</span>
                            </div>
                            <span className="text-white font-medium">
                              {totalPortafolios > 0 ? `${((item.value / totalPortafolios) * 100).toFixed(1)}%` : "—"}
                            </span>
                          </div>
                        ))}
                      </div>
                    </>
                  )}
                </div>

                <div className="lg:col-span-3 grid grid-cols-1 md:grid-cols-2 gap-4 content-start">
                {portafolios.map((p, i) => {
                  const seleccionado = portafolioSel === p.id;
                  return (
                    <button
                      key={p.id}
                      onClick={() => setPortafolioSel(seleccionado ? null : p.id)}
                      className={`text-left p-4 rounded-lg border transition-all hover:scale-[1.02] ${
                        seleccionado
                          ? "bg-cyan-500/10 border-cyan-500/50"
                          : "bg-slate-800/50 border-slate-700 hover:border-slate-600"
                      }`}
                    >
                      <div className="flex items-start justify-between mb-3">
                        <div className="flex items-center gap-2">
                          <div
                            className="w-3 h-3 rounded-full"
                            style={{ backgroundColor: p.color || colorPorIndice(i) }}
                          />
                          <span className="text-white font-semibold">{p.nombre}</span>
                        </div>
                        <Badge
                          className={
                            p.rendimiento >= 0
                              ? "bg-emerald-500/20 text-emerald-400 border-emerald-500/50"
                              : "bg-red-500/20 text-red-400 border-red-500/50"
                          }
                        >
                          {formatPercent(p.rendimiento)}
                        </Badge>
                      </div>
                      <div className="text-2xl font-bold text-white mb-1">
                        {formatCurrency(p.valor_actual, p.moneda)}
                      </div>
                      <div className="flex items-center gap-3 text-xs text-slate-400">
                        <span className="flex items-center gap-1">
                          <Layers className="w-3 h-3" />
                          {p.activos.length} activos
                        </span>
                        <span>{ESTRATEGIA_LABEL[p.estrategia ?? ""] ?? "Sin estrategia"}</span>
                      </div>
                    </button>
                  );
                })}
                </div>
              </div>
            </CardContent>
            )}
          </Card>

          {/* Activos y actividad */}
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <Card className="bg-slate-900/50 border-slate-800">
              <CardHeader>
                <CardTitle className="text-white bg-gradient-to-r from-emerald-400 to-green-400 bg-clip-text text-transparent">
                  Activos {portafolioSel === null ? "de toda la cartera" : "del portafolio"}
                </CardTitle>
              </CardHeader>
              <CardContent>
                {activosAgregados.length === 0 ? (
                  <div className="text-center text-slate-400 py-8">Sin activos en esta selección.</div>
                ) : (
                  <Table>
                    <TableHeader>
                      <TableRow className="border-slate-700 hover:bg-transparent">
                        <TableHead className="text-slate-400">Activo</TableHead>
                        <TableHead className="text-slate-400 text-right">Cantidad</TableHead>
                        <TableHead className="text-slate-400 text-right">Invertido</TableHead>
                        <TableHead className="text-slate-400 text-right">Peso</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {activosAgregados.map((a, i) => (
                        <TableRow key={a.simbolo} className="border-slate-800">
                          <TableCell>
                            <div className="flex items-center gap-3">
                              <div
                                className="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold"
                                style={{ backgroundColor: colorPorIndice(i) }}
                              >
                                {a.simbolo.slice(0, 2)}
                              </div>
                              <div>
                                <div className="text-white text-sm font-medium">{a.simbolo}</div>
                                <div className="text-xs text-slate-400">{a.nombre}</div>
                              </div>
                            </div>
                          </TableCell>
                          <TableCell className="text-right text-slate-300">{formatCantidad(a.cantidad)}</TableCell>
                          <TableCell className="text-right text-white font-medium">{formatCurrency(a.valor)}</TableCell>
                          <TableCell className="text-right text-slate-300">
                            {totalVisible > 0 ? `${((a.valor / totalVisible) * 100).toFixed(1)}%` : "—"}
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                )}
              </CardContent>
            </Card>

            <div className="space-y-6">
              <Card className="bg-slate-900/50 border-slate-800">
                <CardHeader>
                  <CardTitle className="text-white">Últimas Transacciones</CardTitle>
                </CardHeader>
                <CardContent>
                  {transaccionesRecientes.length === 0 ? (
                    <div className="text-center text-slate-400 py-6">Sin transacciones registradas.</div>
                  ) : (
                    <div className="space-y-3">
                      {transaccionesRecientes.map((t) => {
                        const entrada = (SIGNO_TRANSACCION[t.tipo] ?? 0) >= 0;
                        return (
                          <div key={t.id} className="flex items-center justify-between p-3 bg-slate-800/50 rounded-lg">
                            <div className="flex items-center gap-3">
                              <div className={`p-2 rounded-lg ${entrada ? "bg-emerald-500/20" : "bg-orange-500/20"}`}>
                                {entrada ? (
                                  <ArrowUpRight className="w-4 h-4 text-emerald-400" />
                                ) : (
                                  <ArrowDownRight className="w-4 h-4 text-orange-400" />
                                )}
                              </div>
                              <div>
                                <div className="text-white text-sm capitalize">
                                  {t.tipo} {t.activo?.simbolo ? `· ${t.activo.simbolo}` : ""}
                                </div>
                                <div className="text-xs text-slate-500">{formatFecha(t.created_at)}</div>
                              </div>
                            </div>
                            <div className="text-white font-medium">{formatCurrency(Number(t.monto_total))}</div>
                          </div>
                        );
                      })}
                    </div>
                  )}
                </CardContent>
              </Card>

              <Card className="bg-slate-900/50 border-slate-800">
                <CardHeader>
                  <CardTitle className="text-white bg-gradient-to-r from-purple-400 to-fuchsia-400 bg-clip-text text-transparent">
                    Alertas del Inversionista
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  {(usuario.alertas ?? []).length === 0 ? (
                    <div className="text-center text-slate-400 py-6">Sin alertas configuradas.</div>
                  ) : (
                    <div className="space-y-3">
                      {usuario.alertas.slice(0, 5).map((alerta) => (
                        <div key={alerta.id} className="flex items-start gap-3 p-3 bg-slate-800/50 rounded-lg">
                          <div className={`p-2 rounded-lg ${alerta.activa ? "bg-emerald-500/20" : "bg-slate-600/20"}`}>
                            <Bell className={`w-4 h-4 ${alerta.activa ? "text-emerald-400" : "text-slate-400"}`} />
                          </div>
                          <div className="flex-1">
                            <p className="text-white text-sm">{alerta.nombre}</p>
                            <p className="text-slate-500 text-xs">
                              {ALERTA_TIPO_LABEL[alerta.tipo] ?? alerta.tipo} — {alerta.activo?.simbolo ?? "—"}
                            </p>
                          </div>
                        </div>
                      ))}
                    </div>
                  )}
                </CardContent>
              </Card>
            </div>
          </div>
        </>
      )}
    </div>
  );
}
