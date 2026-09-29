import { useEffect, useMemo, useRef, useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "../components/ui/card";
import { Input } from "../components/ui/input";
import { Badge } from "../components/ui/badge";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "../components/ui/table";
import {
  Users, Wallet, TrendingUp, Layers, Search, ChevronDown, ChevronUp, ChevronRight, Bell, RefreshCw,
} from "lucide-react";
import { useUsuarios } from "../../controllers/useUsuarios";
import { useAlertas } from "../../controllers/useAlertas";
import { InversionistaDetalle } from "../components/admin/InversionistaDetalle";
import {
  ALERTA_TIPO_LABEL,
  colorPorIndice,
  formatCurrency,
  formatPercent,
  iniciales,
} from "../components/admin/format";
import type { UsuarioResumen } from "../../models/types";

type CampoOrden = "nombre" | "portafolios_count" | "activos_count" | "valor_invertido" | "valor_actual" | "rendimiento";

const ROLES_ADMIN = ["admin", "administrador"];

const esInversionista = (usuario: UsuarioResumen) =>
  !ROLES_ADMIN.includes((usuario.rol?.nombre ?? "").toLowerCase());

export function Dashboard() {
  const { usuarios, loading, error, refresh } = useUsuarios();
  const { alertas } = useAlertas();

  const [busqueda, setBusqueda] = useState("");
  const [soloInversionistas, setSoloInversionistas] = useState(true);
  const [orden, setOrden] = useState<{ campo: CampoOrden; dir: "asc" | "desc" }>({
    campo: "valor_invertido",
    dir: "desc",
  });
  const [seleccionado, setSeleccionado] = useState<number | null>(null);
  const detalleRef = useRef<HTMLDivElement>(null);

  // Al elegir una fila, llevamos la vista al panel de detalle.
  useEffect(() => {
    if (seleccionado !== null) {
      detalleRef.current?.scrollIntoView({ behavior: "smooth", block: "start" });
    }
  }, [seleccionado]);

  const inversionistas = useMemo(
    () => (soloInversionistas ? usuarios.filter(esInversionista) : usuarios),
    [usuarios, soloInversionistas]
  );

  const filtrados = useMemo(() => {
    const termino = busqueda.trim().toLowerCase();
    const base = termino
      ? inversionistas.filter((u) =>
          `${u.nombre} ${u.apellido} ${u.email}`.toLowerCase().includes(termino)
        )
      : inversionistas;

    const factor = orden.dir === "asc" ? 1 : -1;

    return [...base].sort((a, b) => {
      if (orden.campo === "nombre") {
        return factor * `${a.nombre} ${a.apellido}`.localeCompare(`${b.nombre} ${b.apellido}`);
      }
      return factor * (Number(a[orden.campo]) - Number(b[orden.campo]));
    });
  }, [inversionistas, busqueda, orden]);

  const totales = useMemo(() => {
    const invertido = inversionistas.reduce((s, u) => s + Number(u.valor_invertido), 0);
    const actual = inversionistas.reduce((s, u) => s + Number(u.valor_actual), 0);

    return {
      inversionistas: inversionistas.length,
      portafolios: inversionistas.reduce((s, u) => s + Number(u.portafolios_count), 0),
      invertido,
      actual,
      rendimiento: invertido > 0 ? ((actual - invertido) / invertido) * 100 : 0,
    };
  }, [inversionistas]);

  const topInversionistas = useMemo(
    () => [...inversionistas].sort((a, b) => b.valor_invertido - a.valor_invertido).slice(0, 5),
    [inversionistas]
  );

  const cambiarOrden = (campo: CampoOrden) =>
    setOrden((prev) =>
      prev.campo === campo
        ? { campo, dir: prev.dir === "asc" ? "desc" : "asc" }
        : { campo, dir: campo === "nombre" ? "asc" : "desc" }
    );

  const stats = [
    {
      title: "Inversionistas",
      value: String(totales.inversionistas),
      hint: soloInversionistas ? "sin contar administradores" : "todos los usuarios",
      icon: Users,
      gradient: "from-cyan-500 to-blue-500",
      shadow: "shadow-cyan-500/50",
    },
    {
      title: "Portafolios",
      value: String(totales.portafolios),
      hint: "registrados en la plataforma",
      icon: Layers,
      gradient: "from-emerald-500 to-green-500",
      shadow: "shadow-emerald-500/50",
    },
    {
      title: "Capital Invertido",
      value: formatCurrency(totales.invertido),
      hint: "suma de todas las carteras",
      icon: Wallet,
      gradient: "from-orange-500 to-pink-500",
      shadow: "shadow-orange-500/50",
    },
    {
      title: "Rendimiento Global",
      value: formatPercent(totales.rendimiento),
      hint: `valor actual ${formatCurrency(totales.actual)}`,
      icon: TrendingUp,
      gradient: "from-purple-500 to-fuchsia-500",
      shadow: "shadow-purple-500/50",
    },
  ];

  const columnas: { campo: CampoOrden; label: string; alineacion: string }[] = [
    { campo: "nombre", label: "Inversionista", alineacion: "text-left" },
    { campo: "portafolios_count", label: "Portafolios", alineacion: "text-right" },
    { campo: "activos_count", label: "Activos", alineacion: "text-right" },
    { campo: "valor_invertido", label: "Invertido", alineacion: "text-right" },
    { campo: "valor_actual", label: "Valor actual", alineacion: "text-right" },
    { campo: "rendimiento", label: "Rendimiento", alineacion: "text-right" },
  ];

  return (
    <div className="min-h-screen py-8">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-white mb-2 bg-gradient-to-r from-cyan-400 to-emerald-400 bg-clip-text text-transparent">
            Dashboard Ejecutivo
          </h1>
          <p className="text-slate-400">
            Selecciona un inversionista en la tabla para ver sus portafolios, su distribución y su actividad.
          </p>
        </div>

        {/* Indicadores globales */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          {stats.map((stat) => (
            <Card key={stat.title} className="bg-slate-900/50 border-slate-800">
              <CardContent className="p-6">
                <div className="flex items-start justify-between mb-4">
                  <div className={`bg-gradient-to-br ${stat.gradient} p-2 rounded-lg shadow-lg ${stat.shadow}`}>
                    <stat.icon className="w-5 h-5 text-white" />
                  </div>
                </div>
                <div className="text-2xl font-bold text-white mb-1">{loading ? "—" : stat.value}</div>
                <div className="text-sm text-slate-400">{stat.title}</div>
                <div className="text-xs text-slate-500 mt-1">{stat.hint}</div>
              </CardContent>
            </Card>
          ))}
        </div>

        {/* Tabla de inversionistas */}
        <Card className="bg-slate-900/50 border-slate-800 mb-8">
          <CardHeader>
            <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
              <div>
                <CardTitle className="text-white">Inversionistas</CardTitle>
                <p className="text-sm text-slate-400 mt-1">
                  {loading ? "Cargando..." : `${filtrados.length} de ${usuarios.length} usuarios`}
                </p>
              </div>
              <div className="flex flex-wrap items-center gap-2">
                <div className="flex rounded-lg border border-slate-700 overflow-hidden text-sm">
                  <button
                    onClick={() => setSoloInversionistas(true)}
                    className={`px-3 py-2 ${soloInversionistas ? "bg-cyan-500/20 text-cyan-300" : "text-slate-400 hover:text-white"}`}
                  >
                    Inversionistas
                  </button>
                  <button
                    onClick={() => setSoloInversionistas(false)}
                    className={`px-3 py-2 ${!soloInversionistas ? "bg-cyan-500/20 text-cyan-300" : "text-slate-400 hover:text-white"}`}
                  >
                    Todos
                  </button>
                </div>
                <div className="relative">
                  <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                  <Input
                    value={busqueda}
                    onChange={(e) => setBusqueda(e.target.value)}
                    placeholder="Buscar por nombre o email..."
                    className="bg-slate-800/50 border-slate-700 text-white pl-10 w-full sm:w-64"
                  />
                </div>
                <button
                  onClick={refresh}
                  className="p-2 rounded-lg border border-slate-700 text-slate-400 hover:text-white hover:border-slate-600 transition-colors"
                  aria-label="Recargar"
                >
                  <RefreshCw className="w-4 h-4" />
                </button>
              </div>
            </div>
          </CardHeader>
          <CardContent>
            {loading ? (
              <div className="text-center text-slate-400 py-10">Cargando inversionistas...</div>
            ) : error ? (
              <div className="text-center text-slate-400 py-10">{error}</div>
            ) : filtrados.length === 0 ? (
              <div className="text-center text-slate-400 py-10">
                {busqueda ? "Ningún inversionista coincide con la búsqueda." : "Todavía no hay inversionistas registrados."}
              </div>
            ) : (
              <div className="overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow className="border-slate-700 hover:bg-transparent">
                      {columnas.map((col) => (
                        <TableHead key={col.campo} className={`text-slate-400 ${col.alineacion}`}>
                          <button
                            onClick={() => cambiarOrden(col.campo)}
                            className={`inline-flex items-center gap-1 hover:text-white transition-colors ${
                              orden.campo === col.campo ? "text-cyan-400" : ""
                            }`}
                          >
                            {col.label}
                            {orden.campo === col.campo &&
                              (orden.dir === "asc" ? (
                                <ChevronUp className="w-3 h-3" />
                              ) : (
                                <ChevronDown className="w-3 h-3" />
                              ))}
                          </button>
                        </TableHead>
                      ))}
                      <TableHead className="w-10" />
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {filtrados.map((usuario, index) => {
                      const activo = seleccionado === usuario.id;
                      return (
                        <TableRow
                          key={usuario.id}
                          onClick={() => setSeleccionado(activo ? null : usuario.id)}
                          className={`border-slate-800 cursor-pointer transition-colors ${
                            activo ? "bg-cyan-500/10 hover:bg-cyan-500/15" : "hover:bg-slate-800/50"
                          }`}
                        >
                          <TableCell>
                            <div className="flex items-center gap-3">
                              <div
                                className="w-9 h-9 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0"
                                style={{ backgroundColor: colorPorIndice(index) }}
                              >
                                {iniciales(usuario.nombre, usuario.apellido)}
                              </div>
                              <div className="min-w-0">
                                <div className="text-white font-medium truncate">
                                  {usuario.nombre} {usuario.apellido}
                                </div>
                                <div className="text-xs text-slate-400 truncate">{usuario.email}</div>
                              </div>
                              {!usuario.activo && (
                                <Badge className="bg-slate-500/20 text-slate-400 border-slate-500/50">Inactivo</Badge>
                              )}
                            </div>
                          </TableCell>
                          <TableCell className="text-right text-slate-300">{usuario.portafolios_count}</TableCell>
                          <TableCell className="text-right text-slate-300">{usuario.activos_count}</TableCell>
                          <TableCell className="text-right text-white font-medium">
                            {formatCurrency(usuario.valor_invertido)}
                          </TableCell>
                          <TableCell className="text-right text-slate-300">
                            {formatCurrency(usuario.valor_actual)}
                          </TableCell>
                          <TableCell className="text-right">
                            <Badge
                              className={
                                usuario.rendimiento >= 0
                                  ? "bg-emerald-500/20 text-emerald-400 border-emerald-500/50"
                                  : "bg-red-500/20 text-red-400 border-red-500/50"
                              }
                            >
                              {formatPercent(usuario.rendimiento)}
                            </Badge>
                          </TableCell>
                          <TableCell>
                            <ChevronRight
                              className={`w-4 h-4 transition-transform ${
                                activo ? "rotate-90 text-cyan-400" : "text-slate-500"
                              }`}
                            />
                          </TableCell>
                        </TableRow>
                      );
                    })}
                  </TableBody>
                </Table>
              </div>
            )}
          </CardContent>
        </Card>

        {/* Detalle del inversionista seleccionado */}
        <div ref={detalleRef} className="scroll-mt-24">
          {seleccionado !== null ? (
            <InversionistaDetalle usuarioId={seleccionado} onClose={() => setSeleccionado(null)} />
          ) : (
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <Card className="bg-slate-900/50 border-slate-800">
                <CardHeader>
                  <CardTitle className="text-white bg-gradient-to-r from-emerald-400 to-green-400 bg-clip-text text-transparent">
                    Mayor Capital Invertido
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  {topInversionistas.length === 0 ? (
                    <div className="text-center text-slate-400 py-6">Sin datos todavía.</div>
                  ) : (
                    <div className="space-y-4">
                      {topInversionistas.map((usuario, index) => {
                        const peso =
                          totales.invertido > 0 ? (usuario.valor_invertido / totales.invertido) * 100 : 0;
                        return (
                          <button
                            key={usuario.id}
                            onClick={() => setSeleccionado(usuario.id)}
                            className="w-full text-left p-3 bg-slate-800/50 rounded-lg hover:bg-slate-800 transition-colors"
                          >
                            <div className="flex items-center justify-between mb-2">
                              <div className="flex items-center gap-3">
                                <div
                                  className="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold"
                                  style={{ backgroundColor: colorPorIndice(index) }}
                                >
                                  {iniciales(usuario.nombre, usuario.apellido)}
                                </div>
                                <div className="text-white text-sm font-medium">
                                  {usuario.nombre} {usuario.apellido}
                                </div>
                              </div>
                              <div className="text-white font-medium">{formatCurrency(usuario.valor_invertido)}</div>
                            </div>
                            <div className="h-2 bg-slate-700 rounded-full overflow-hidden">
                              <div
                                className="h-full rounded-full bg-gradient-to-r from-cyan-500 to-emerald-500"
                                style={{ width: `${Math.min(peso, 100)}%` }}
                              />
                            </div>
                          </button>
                        );
                      })}
                    </div>
                  )}
                </CardContent>
              </Card>

              <Card className="bg-slate-900/50 border-slate-800">
                <CardHeader>
                  <CardTitle className="text-white bg-gradient-to-r from-purple-400 to-fuchsia-400 bg-clip-text text-transparent">
                    Alertas Recientes
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  {alertas.length === 0 ? (
                    <div className="text-center text-slate-400 py-6">No hay alertas configuradas.</div>
                  ) : (
                    <div className="space-y-4">
                      {alertas.slice(0, 5).map((alerta) => (
                        <div key={alerta.id} className="flex items-start gap-3 p-3 bg-slate-800/50 rounded-lg">
                          <div
                            className={`p-2 rounded-lg ${
                              alerta.activada_en
                                ? "bg-orange-500/20"
                                : alerta.activa
                                ? "bg-emerald-500/20"
                                : "bg-cyan-500/20"
                            }`}
                          >
                            <Bell
                              className={`w-4 h-4 ${
                                alerta.activada_en
                                  ? "text-orange-400"
                                  : alerta.activa
                                  ? "text-emerald-400"
                                  : "text-cyan-400"
                              }`}
                            />
                          </div>
                          <div className="flex-1">
                            <p className="text-white text-sm mb-1">{alerta.nombre}</p>
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
          )}
        </div>
      </div>
    </div>
  );
}
