import { useState } from "react";
import { Link } from "react-router";
import { Card, CardContent, CardHeader, CardTitle } from "../components/ui/card";
import { Button } from "../components/ui/button";
import { Input } from "../components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "../components/ui/table";
import { Badge } from "../components/ui/badge";
import { Download, Filter, Plus, Search } from "lucide-react";
import { useTransacciones } from "../../controllers/useTransacciones";

export function Transactions() {
  const [searchTerm, setSearchTerm] = useState("");
  const [tipoFiltro, setTipoFiltro] = useState("all");
  const [estadoFiltro, setEstadoFiltro] = useState("all");
  const { transacciones, loading } = useTransacciones();

  const formatCurrency = (value: number) =>
    new Intl.NumberFormat("es-US", { style: "currency", currency: "USD" }).format(value);

  const transaccionesFiltradas = transacciones.filter((t) => {
    const matchSearch = !searchTerm ||
      t.activo?.simbolo?.toLowerCase().includes(searchTerm.toLowerCase()) ||
      t.tipo.toLowerCase().includes(searchTerm.toLowerCase());
    const matchTipo = tipoFiltro === "all" || t.tipo === tipoFiltro;
    const matchEstado = estadoFiltro === "all" || t.estado === estadoFiltro;
    return matchSearch && matchTipo && matchEstado;
  });

  const tipoLabel = (tipo: string) => {
    const labels: Record<string, string> = {
      compra: "Compra", venta: "Venta", deposito: "Depósito",
      retiro: "Retiro", dividendo: "Dividendo", fee: "Comisión",
    };
    return labels[tipo] ?? tipo;
  };

  const estadoLabel = (estado?: string) => {
    const labels: Record<string, string> = {
      completada: "Completada", pendiente: "Pendiente", cancelada: "Cancelada",
    };
    return labels[estado ?? ""] ?? "Completada";
  };

  return (
    <div className="min-h-screen py-8">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
          <div>
            <h1 className="text-3xl font-bold text-white mb-2 bg-gradient-to-r from-cyan-400 to-emerald-400 bg-clip-text text-transparent">
              Transacciones
            </h1>
            <p className="text-slate-400">Historial completo de operaciones</p>
          </div>
          <div className="flex gap-2">
            <Button variant="outline" className="border-slate-700 text-slate-300 hover:bg-slate-800">
              <Download className="w-4 h-4 mr-2" />
              Exportar CSV
            </Button>
            <Link to="/app/transactions/create">
              <Button className="bg-gradient-to-r from-cyan-500 to-emerald-500 hover:from-cyan-600 hover:to-emerald-600 text-white shadow-lg shadow-cyan-500/30 transition-all hover:scale-105">
                <Plus className="w-4 h-4 mr-2" />
                Nueva Transacción
              </Button>
            </Link>
          </div>
        </div>

        {/* Filters */}
        <Card className="bg-slate-900/50 border-slate-800 mb-6">
          <CardContent className="p-6">
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div className="relative">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                <Input
                  placeholder="Buscar transacción..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="bg-slate-800/50 border-slate-700 text-white pl-10"
                />
              </div>
              <Select value={tipoFiltro} onValueChange={setTipoFiltro}>
                <SelectTrigger className="bg-slate-800/50 border-slate-700 text-white">
                  <SelectValue placeholder="Tipo" />
                </SelectTrigger>
                <SelectContent className="bg-slate-800 border-slate-700">
                  <SelectItem value="all">Todos</SelectItem>
                  <SelectItem value="compra">Compra</SelectItem>
                  <SelectItem value="venta">Venta</SelectItem>
                </SelectContent>
              </Select>
              <Select value={estadoFiltro} onValueChange={setEstadoFiltro}>
                <SelectTrigger className="bg-slate-800/50 border-slate-700 text-white">
                  <SelectValue placeholder="Estado" />
                </SelectTrigger>
                <SelectContent className="bg-slate-800 border-slate-700">
                  <SelectItem value="all">Todos</SelectItem>
                  <SelectItem value="completada">Completada</SelectItem>
                  <SelectItem value="pendiente">Pendiente</SelectItem>
                </SelectContent>
              </Select>
              <Button variant="outline" className="border-slate-700 text-slate-300 hover:bg-slate-800">
                <Filter className="w-4 h-4 mr-2" />
                Aplicar Filtros
              </Button>
            </div>
          </CardContent>
        </Card>

        {/* Transactions Table */}
        <Card className="bg-slate-900/50 border-slate-800">
          <CardHeader>
            <CardTitle className="text-white">Historial de Transacciones</CardTitle>
          </CardHeader>
          <CardContent>
            {loading ? (
              <div className="text-center text-slate-400 py-8">Cargando transacciones...</div>
            ) : (
              <Table>
                <TableHeader>
                  <TableRow className="border-slate-700">
                    <TableHead className="text-slate-400">Fecha</TableHead>
                    <TableHead className="text-slate-400">Tipo</TableHead>
                    <TableHead className="text-slate-400">Activo</TableHead>
                    <TableHead className="text-slate-400">Cantidad</TableHead>
                    <TableHead className="text-slate-400">Precio</TableHead>
                    <TableHead className="text-slate-400">Total</TableHead>
                    <TableHead className="text-slate-400">Estado</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {transaccionesFiltradas.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={7} className="text-center text-slate-400 py-8">
                        No hay transacciones registradas.
                      </TableCell>
                    </TableRow>
                  ) : (
                    transaccionesFiltradas.map((tx) => (
                      <TableRow key={tx.id} className="border-slate-700">
                        <TableCell className="text-slate-300">
                          {tx.created_at?.split("T")[0] ?? "—"}
                        </TableCell>
                        <TableCell>
                          <Badge className={
                            tx.tipo === "compra"
                              ? "bg-emerald-500/20 text-emerald-400 border-emerald-500/50"
                              : "bg-orange-500/20 text-orange-400 border-orange-500/50"
                          }>
                            {tipoLabel(tx.tipo)}
                          </Badge>
                        </TableCell>
                        <TableCell className="text-white font-medium">
                          {tx.activo?.simbolo ?? tx.activo_id}
                        </TableCell>
                        <TableCell className="text-slate-300">{tx.cantidad}</TableCell>
                        <TableCell className="text-slate-300">{formatCurrency(tx.precio_unitario)}</TableCell>
                        <TableCell className="text-white font-medium">{formatCurrency(tx.monto_total)}</TableCell>
                        <TableCell>
                          <Badge className={
                            tx.estado === "pendiente"
                              ? "bg-yellow-500/20 text-yellow-400 border-yellow-500/50"
                              : "bg-cyan-500/20 text-cyan-400 border-cyan-500/50"
                          }>
                            {estadoLabel(tx.estado)}
                          </Badge>
                        </TableCell>
                      </TableRow>
                    ))
                  )}
                </TableBody>
              </Table>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
