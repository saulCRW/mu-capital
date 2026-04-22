import { Card, CardContent, CardHeader, CardTitle } from "../components/ui/card";
import { Badge } from "../components/ui/badge";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "../components/ui/table";
import { Lock, AlertCircle } from "lucide-react";

export function InvestorTransactions() {
  const transactions = [
    { id: 1, date: "2026-03-24", type: "Compra", asset: "BTC", quantity: 0.5, price: 70000, total: 35000, status: "Completada" },
    { id: 2, date: "2026-03-22", type: "Compra", asset: "ETH", quantity: 10, price: 4173, total: 41730, status: "Completada" },
    { id: 3, date: "2026-03-20", type: "Venta", asset: "AAPL", quantity: 50, price: 195, total: 9750, status: "Completada" },
    { id: 4, date: "2026-03-18", type: "Compra", asset: "SPY", quantity: 20, price: 425, total: 8500, status: "Completada" },
    { id: 5, date: "2026-03-15", type: "Compra", asset: "TSLA", quantity: 15, price: 220, total: 3300, status: "Pendiente" },
    { id: 6, date: "2026-03-12", type: "Venta", asset: "BTC", quantity: 0.2, price: 68000, total: 13600, status: "Completada" },
    { id: 7, date: "2026-03-10", type: "Compra", asset: "ETH", quantity: 5, price: 4100, total: 20500, status: "Completada" },
    { id: 8, date: "2026-03-08", type: "Compra", asset: "AAPL", quantity: 100, price: 192, total: 19200, status: "Completada" },
  ];

  const formatCurrency = (value: number) =>
    new Intl.NumberFormat("es-US", { style: "currency", currency: "USD", minimumFractionDigits: 0 }).format(value);

  return (
    <div className="min-h-screen py-8">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Header */}
        <div className="mb-8">
          <div className="flex items-center justify-between mb-2">
            <h1 className="text-3xl font-bold text-white bg-gradient-to-r from-cyan-400 to-emerald-400 bg-clip-text text-transparent">
              Mis Movimientos
            </h1>
            <Lock className="w-5 h-5 text-slate-600" />
          </div>
          <p className="text-slate-400">Historial de operaciones realizadas en tu portafolio</p>
        </div>

        {/* Info Banner */}
        <Card className="bg-cyan-500/10 border-cyan-500/30 mb-8">
          <CardContent className="p-4">
            <div className="flex gap-3">
              <div className="bg-cyan-500/20 p-2 rounded-lg h-fit">
                <AlertCircle className="w-5 h-5 text-cyan-400" />
              </div>
              <div>
                <h4 className="text-white font-medium mb-1">Operaciones Gestionadas</h4>
                <p className="text-slate-300 text-sm leading-relaxed">
                  Todas las operaciones son realizadas por tu administrador asignado. Este
                  historial es solo de consulta.
                </p>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Transactions Table */}
        <Card className="bg-slate-900/50 border-slate-800">
          <CardHeader>
            <div className="flex items-center justify-between">
              <CardTitle className="text-white">Historial de Transacciones</CardTitle>
              <Lock className="w-4 h-4 text-slate-600" />
            </div>
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
                      <Badge
                        className={
                          tx.type === "Compra"
                            ? "bg-emerald-500/20 text-emerald-400 border-emerald-500/50"
                            : "bg-orange-500/20 text-orange-400 border-orange-500/50"
                        }
                      >
                        {tx.type}
                      </Badge>
                    </TableCell>
                    <TableCell className="text-white font-medium">{tx.asset}</TableCell>
                    <TableCell className="text-slate-300">{tx.quantity}</TableCell>
                    <TableCell className="text-slate-300">{formatCurrency(tx.price)}</TableCell>
                    <TableCell className="text-white font-medium">{formatCurrency(tx.total)}</TableCell>
                    <TableCell>
                      <Badge
                        className={
                          tx.status === "Completada"
                            ? "bg-cyan-500/20 text-cyan-400 border-cyan-500/50"
                            : "bg-yellow-500/20 text-yellow-400 border-yellow-500/50"
                        }
                      >
                        {tx.status}
                      </Badge>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
            <p className="text-xs text-slate-500 mt-4 flex items-center gap-2">
              <Lock className="w-3 h-3" />
              Solo lectura – Contacta a tu administrador para consultas
            </p>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
