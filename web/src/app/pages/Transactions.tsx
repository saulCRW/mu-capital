import { useState } from "react";
import { Link } from "react-router";
import { Card, CardContent, CardHeader, CardTitle } from "../components/ui/card";
import { Button } from "../components/ui/button";
import { Input } from "../components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "../components/ui/table";
import { Badge } from "../components/ui/badge";
import { Download, Filter, Plus, Search } from "lucide-react";

export function Transactions() {
  const [searchTerm, setSearchTerm] = useState("");

  const transactions = [
    { id: 1, date: "2026-02-25", type: "Compra", asset: "BTC", quantity: 0.5, price: 70000, total: 35000, status: "Completada" },
    { id: 2, date: "2026-02-24", type: "Venta", asset: "AAPL", quantity: 100, price: 195, total: 19500, status: "Completada" },
    { id: 3, date: "2026-02-23", type: "Compra", asset: "ETH", quantity: 10, price: 4173, total: 41730, status: "Completada" },
    { id: 4, date: "2026-02-22", type: "Compra", asset: "SPY", quantity: 50, price: 425, total: 21250, status: "Completada" },
    { id: 5, date: "2026-02-21", type: "Venta", asset: "TSLA", quantity: 25, price: 220, total: 5500, status: "Pendiente" },
  ];

  const formatCurrency = (value: number) =>
    new Intl.NumberFormat("es-US", { style: "currency", currency: "USD" }).format(value);

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
              <Select>
                <SelectTrigger className="bg-slate-800/50 border-slate-700 text-white">
                  <SelectValue placeholder="Tipo" />
                </SelectTrigger>
                <SelectContent className="bg-slate-800 border-slate-700">
                  <SelectItem value="all">Todos</SelectItem>
                  <SelectItem value="buy">Compra</SelectItem>
                  <SelectItem value="sell">Venta</SelectItem>
                </SelectContent>
              </Select>
              <Select>
                <SelectTrigger className="bg-slate-800/50 border-slate-700 text-white">
                  <SelectValue placeholder="Estado" />
                </SelectTrigger>
                <SelectContent className="bg-slate-800 border-slate-700">
                  <SelectItem value="all">Todos</SelectItem>
                  <SelectItem value="completed">Completada</SelectItem>
                  <SelectItem value="pending">Pendiente</SelectItem>
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
                {transactions.map((tx) => (
                  <TableRow key={tx.id} className="border-slate-700">
                    <TableCell className="text-slate-300">{tx.date}</TableCell>
                    <TableCell>
                      <Badge className={tx.type === "Compra" ? "bg-emerald-500/20 text-emerald-400 border-emerald-500/50" : "bg-orange-500/20 text-orange-400 border-orange-500/50"}>
                        {tx.type}
                      </Badge>
                    </TableCell>
                    <TableCell className="text-white font-medium">{tx.asset}</TableCell>
                    <TableCell className="text-slate-300">{tx.quantity}</TableCell>
                    <TableCell className="text-slate-300">{formatCurrency(tx.price)}</TableCell>
                    <TableCell className="text-white font-medium">{formatCurrency(tx.total)}</TableCell>
                    <TableCell>
                      <Badge className={tx.status === "Completada" ? "bg-cyan-500/20 text-cyan-400 border-cyan-500/50" : "bg-yellow-500/20 text-yellow-400 border-yellow-500/50"}>
                        {tx.status}
                      </Badge>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}