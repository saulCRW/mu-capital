import { Card, CardContent, CardHeader, CardTitle } from "../components/ui/card";
import { Badge } from "../components/ui/badge";
import { DollarSign, TrendingUp, Briefcase, Bell, Lock } from "lucide-react";
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from "recharts";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "../components/ui/table";

export function InvestorDashboard() {
  const userName = "Carlos Mendoza";

  const chartData = [
    { date: "Día 1", value: 180000 },
    { date: "Día 5", value: 185000 },
    { date: "Día 10", value: 195000 },
    { date: "Día 15", value: 210000 },
    { date: "Día 20", value: 230000 },
    { date: "Día 25", value: 235000 },
    { date: "Día 30", value: 245000 },
  ];

  const recentMovements = [
    { date: "2026-03-24", type: "Compra", asset: "BTC", quantity: 0.5, total: 35000, status: "Completada" },
    { date: "2026-03-22", type: "Compra", asset: "ETH", quantity: 10, total: 41730, status: "Completada" },
    { date: "2026-03-20", type: "Venta", asset: "AAPL", quantity: 50, total: 9750, status: "Completada" },
    { date: "2026-03-18", type: "Compra", asset: "SPY", quantity: 20, total: 8500, status: "Completada" },
    { date: "2026-03-15", type: "Compra", asset: "TSLA", quantity: 15, total: 3300, status: "Pendiente" },
  ];

  const activeAlerts = [
    { asset: "BTC", type: "Objetivo", condition: "Precio > $75,000" },
    { asset: "ETH", type: "Caída", condition: "Precio < $4,000" },
    { asset: "AAPL", type: "Volatilidad", condition: "Volatilidad > 20%" },
  ];

  const formatCurrency = (value: number) =>
    new Intl.NumberFormat("es-US", { style: "currency", currency: "USD", minimumFractionDigits: 0 }).format(value);

  return (
    <div className="min-h-screen py-8">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Welcome */}
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-white mb-2">
            Bienvenido, <span className="bg-gradient-to-r from-cyan-400 to-emerald-400 bg-clip-text text-transparent">{userName}</span>
          </h1>
          <p className="text-slate-400">Aquí está el resumen de tu portafolio</p>
        </div>

        {/* Summary Cards */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <Card className="bg-slate-900/50 border-slate-800">
            <CardContent className="p-6">
              <div className="flex items-start justify-between mb-4">
                <div className="bg-gradient-to-br from-cyan-500 to-blue-500 p-2 rounded-lg shadow-lg shadow-cyan-500/50">
                  <DollarSign className="w-5 h-5 text-white" />
                </div>
                <Lock className="w-4 h-4 text-slate-600" />
              </div>
              <div className="text-2xl font-bold text-white mb-1">$245,000</div>
              <div className="text-sm text-slate-400">Valor Total de mi Portafolio</div>
            </CardContent>
          </Card>

          <Card className="bg-slate-900/50 border-slate-800">
            <CardContent className="p-6">
              <div className="flex items-start justify-between mb-4">
                <div className="bg-gradient-to-br from-emerald-500 to-green-500 p-2 rounded-lg shadow-lg shadow-emerald-500/50">
                  <TrendingUp className="w-5 h-5 text-white" />
                </div>
                <Lock className="w-4 h-4 text-slate-600" />
              </div>
              <div className="text-2xl font-bold text-emerald-400 mb-1">+36.11%</div>
              <div className="text-sm text-slate-400">Rendimiento Total</div>
            </CardContent>
          </Card>

          <Card className="bg-slate-900/50 border-slate-800">
            <CardContent className="p-6">
              <div className="flex items-start justify-between mb-4">
                <div className="bg-gradient-to-br from-orange-500 to-pink-500 p-2 rounded-lg shadow-lg shadow-orange-500/50">
                  <Briefcase className="w-5 h-5 text-white" />
                </div>
                <Lock className="w-4 h-4 text-slate-600" />
              </div>
              <div className="text-2xl font-bold text-white mb-1">12</div>
              <div className="text-sm text-slate-400">Activos en mi Portafolio</div>
            </CardContent>
          </Card>
        </div>

        {/* Performance Chart */}
        <Card className="bg-slate-900/50 border-slate-800 mb-8">
          <CardHeader>
            <div className="flex items-center justify-between">
              <CardTitle className="text-white">Rendimiento Histórico (Últimos 30 días)</CardTitle>
              <Lock className="w-4 h-4 text-slate-600" />
            </div>
          </CardHeader>
          <CardContent>
            <ResponsiveContainer width="100%" height={300}>
              <LineChart data={chartData}>
                <CartesianGrid strokeDasharray="3 3" stroke="#334155" />
                <XAxis dataKey="date" stroke="#94a3b8" />
                <YAxis stroke="#94a3b8" tickFormatter={(value) => `$${value / 1000}k`} />
                <Tooltip
                  contentStyle={{
                    backgroundColor: "#1e293b",
                    border: "1px solid #334155",
                    borderRadius: "8px",
                  }}
                  labelStyle={{ color: "#f1f5f9" }}
                  formatter={(value: number) => [formatCurrency(value), "Valor"]}
                />
                <Line type="monotone" dataKey="value" stroke="#06b6d4" strokeWidth={2} dot={{ fill: "#06b6d4" }} />
              </LineChart>
            </ResponsiveContainer>
            <p className="text-xs text-slate-500 mt-4 flex items-center gap-2">
              <Lock className="w-3 h-3" />
              Solo lectura – Información actualizada automáticamente
            </p>
          </CardContent>
        </Card>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
          {/* Recent Movements */}
          <Card className="bg-slate-900/50 border-slate-800">
            <CardHeader>
              <div className="flex items-center justify-between">
                <CardTitle className="text-white">Últimos Movimientos</CardTitle>
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
                    <TableHead className="text-slate-400">Total</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {recentMovements.map((movement, index) => (
                    <TableRow key={index} className="border-slate-700">
                      <TableCell className="text-slate-300 text-sm">{movement.date}</TableCell>
                      <TableCell>
                        <Badge
                          className={
                            movement.type === "Compra"
                              ? "bg-emerald-500/20 text-emerald-400 border-emerald-500/50"
                              : "bg-orange-500/20 text-orange-400 border-orange-500/50"
                          }
                        >
                          {movement.type}
                        </Badge>
                      </TableCell>
                      <TableCell className="text-white font-medium">{movement.asset}</TableCell>
                      <TableCell className="text-slate-300">{formatCurrency(movement.total)}</TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
              <p className="text-xs text-slate-500 mt-4 flex items-center gap-2">
                <Lock className="w-3 h-3" />
                Solo lectura – Contacta a tu administrador para cambios
              </p>
            </CardContent>
          </Card>

          {/* Active Alerts */}
          <Card className="bg-slate-900/50 border-slate-800">
            <CardHeader>
              <div className="flex items-center justify-between">
                <CardTitle className="text-white">Mis Alertas Activas</CardTitle>
                <Lock className="w-4 h-4 text-slate-600" />
              </div>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                {activeAlerts.map((alert, index) => (
                  <div
                    key={index}
                    className="flex items-center justify-between p-4 bg-slate-800/50 rounded-lg border border-slate-700"
                  >
                    <div className="flex items-center gap-3">
                      <div
                        className={`p-2 rounded-lg ${
                          alert.type === "Objetivo"
                            ? "bg-emerald-500/20"
                            : alert.type === "Caída"
                            ? "bg-red-500/20"
                            : "bg-orange-500/20"
                        }`}
                      >
                        <Bell
                          className={`w-4 h-4 ${
                            alert.type === "Objetivo"
                              ? "text-emerald-400"
                              : alert.type === "Caída"
                              ? "text-red-400"
                              : "text-orange-400"
                          }`}
                        />
                      </div>
                      <div>
                        <div className="text-white font-medium">{alert.asset}</div>
                        <div className="text-slate-400 text-sm">{alert.condition}</div>
                      </div>
                    </div>
                    <Badge
                      className={
                        alert.type === "Objetivo"
                          ? "bg-emerald-500/20 text-emerald-400 border-emerald-500/50"
                          : alert.type === "Caída"
                          ? "bg-red-500/20 text-red-400 border-red-500/50"
                          : "bg-orange-500/20 text-orange-400 border-orange-500/50"
                      }
                    >
                      {alert.type}
                    </Badge>
                  </div>
                ))}
              </div>
              <p className="text-xs text-slate-500 mt-4 flex items-center gap-2">
                <Lock className="w-3 h-3" />
                Alertas configuradas por tu administrador
              </p>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}
