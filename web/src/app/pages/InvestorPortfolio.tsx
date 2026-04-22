import { Card, CardContent, CardHeader, CardTitle } from "../components/ui/card";
import { Badge } from "../components/ui/badge";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "../components/ui/table";
import { DollarSign, TrendingUp, TrendingDown, PieChart as PieChartIcon, Lock, AlertCircle } from "lucide-react";
import { PieChart, Pie, Cell, ResponsiveContainer, Legend, Tooltip } from "recharts";

export function InvestorPortfolio() {
  const portfolioData = {
    currentValue: 245000,
    initialInvestment: 180000,
    profitLoss: 65000,
    returnPercent: 36.11,
  };

  const assets = [
    { asset: "BTC", quantity: 2.5, currentPrice: 70000, totalValue: 175000, change: 15.5 },
    { asset: "ETH", quantity: 15, currentPrice: 4173, totalValue: 62595, change: 8.2 },
    { asset: "AAPL", quantity: 0, currentPrice: 195, totalValue: 0, change: -2.1 },
    { asset: "SPY", quantity: 20, currentPrice: 425, totalValue: 8500, change: 4.3 },
    { asset: "TSLA", quantity: 0, currentPrice: 220, totalValue: 0, change: -5.8 },
  ];

  const distributionData = [
    { name: "BTC", value: 175000, color: "#f59e0b" },
    { name: "ETH", value: 62595, color: "#6366f1" },
    { name: "SPY", value: 8500, color: "#10b981" },
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
              Mi Portafolio
            </h1>
            <Lock className="w-5 h-5 text-slate-600" />
          </div>
          <p className="text-slate-400">Información gestionada por tu administrador</p>
        </div>

        {/* Info Banner */}
        <Card className="bg-cyan-500/10 border-cyan-500/30 mb-8">
          <CardContent className="p-4">
            <div className="flex gap-3">
              <div className="bg-cyan-500/20 p-2 rounded-lg h-fit">
                <AlertCircle className="w-5 h-5 text-cyan-400" />
              </div>
              <div>
                <h4 className="text-white font-medium mb-1">Portafolio Gestionado</h4>
                <p className="text-slate-300 text-sm leading-relaxed">
                  Tu portafolio es gestionado por tu administrador. Para realizar cambios,
                  contacta a tu asesor asignado.
                </p>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Summary Cards */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
          <Card className="bg-slate-900/50 border-slate-800">
            <CardContent className="p-6">
              <div className="flex items-start justify-between mb-4">
                <div className="bg-gradient-to-br from-cyan-500 to-blue-500 p-2 rounded-lg shadow-lg shadow-cyan-500/50">
                  <DollarSign className="w-5 h-5 text-white" />
                </div>
              </div>
              <div className="text-2xl font-bold text-white mb-1">
                {formatCurrency(portfolioData.currentValue)}
              </div>
              <div className="text-sm text-slate-400">Valor Actual</div>
            </CardContent>
          </Card>

          <Card className="bg-slate-900/50 border-slate-800">
            <CardContent className="p-6">
              <div className="flex items-start justify-between mb-4">
                <div className="bg-gradient-to-br from-purple-500 to-fuchsia-500 p-2 rounded-lg shadow-lg shadow-purple-500/50">
                  <TrendingDown className="w-5 h-5 text-white" />
                </div>
              </div>
              <div className="text-2xl font-bold text-white mb-1">
                {formatCurrency(portfolioData.initialInvestment)}
              </div>
              <div className="text-sm text-slate-400">Inversión Inicial</div>
            </CardContent>
          </Card>

          <Card className="bg-slate-900/50 border-slate-800">
            <CardContent className="p-6">
              <div className="flex items-start justify-between mb-4">
                <div className="bg-gradient-to-br from-emerald-500 to-green-500 p-2 rounded-lg shadow-lg shadow-emerald-500/50">
                  <TrendingUp className="w-5 h-5 text-white" />
                </div>
              </div>
              <div className="text-2xl font-bold text-emerald-400 mb-1">
                +{formatCurrency(portfolioData.profitLoss)}
              </div>
              <div className="text-sm text-slate-400">Ganancia/Pérdida</div>
            </CardContent>
          </Card>

          <Card className="bg-slate-900/50 border-slate-800">
            <CardContent className="p-6">
              <div className="flex items-start justify-between mb-4">
                <div className="bg-gradient-to-br from-orange-500 to-pink-500 p-2 rounded-lg shadow-lg shadow-orange-500/50">
                  <PieChartIcon className="w-5 h-5 text-white" />
                </div>
              </div>
              <div className="text-2xl font-bold text-emerald-400 mb-1">
                +{portfolioData.returnPercent}%
              </div>
              <div className="text-sm text-slate-400">Rendimiento %</div>
            </CardContent>
          </Card>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
          {/* Assets Table */}
          <Card className="bg-slate-900/50 border-slate-800 lg:col-span-2">
            <CardHeader>
              <div className="flex items-center justify-between">
                <CardTitle className="text-white">Mis Activos</CardTitle>
                <Lock className="w-4 h-4 text-slate-600" />
              </div>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow className="border-slate-700">
                    <TableHead className="text-slate-400">Activo</TableHead>
                    <TableHead className="text-slate-400">Cantidad</TableHead>
                    <TableHead className="text-slate-400">Precio Actual</TableHead>
                    <TableHead className="text-slate-400">Valor Total</TableHead>
                    <TableHead className="text-slate-400">Variación</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {assets.map((asset, index) => (
                    <TableRow key={index} className="border-slate-700">
                      <TableCell className="text-white font-medium">{asset.asset}</TableCell>
                      <TableCell className="text-slate-300">{asset.quantity}</TableCell>
                      <TableCell className="text-slate-300">
                        {formatCurrency(asset.currentPrice)}
                      </TableCell>
                      <TableCell className="text-white font-medium">
                        {formatCurrency(asset.totalValue)}
                      </TableCell>
                      <TableCell>
                        <Badge
                          className={
                            asset.change >= 0
                              ? "bg-emerald-500/20 text-emerald-400 border-emerald-500/50"
                              : "bg-red-500/20 text-red-400 border-red-500/50"
                          }
                        >
                          {asset.change >= 0 ? "+" : ""}
                          {asset.change}%
                        </Badge>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
              <p className="text-xs text-slate-500 mt-4 flex items-center gap-2">
                <Lock className="w-3 h-3" />
                Solo lectura – Información actualizada en tiempo real
              </p>
            </CardContent>
          </Card>

          {/* Distribution Chart */}
          <Card className="bg-slate-900/50 border-slate-800">
            <CardHeader>
              <div className="flex items-center justify-between">
                <CardTitle className="text-white">Distribución</CardTitle>
                <Lock className="w-4 h-4 text-slate-600" />
              </div>
            </CardHeader>
            <CardContent>
              <ResponsiveContainer width="100%" height={300}>
                <PieChart>
                  <Pie
                    data={distributionData}
                    cx="50%"
                    cy="50%"
                    labelLine={false}
                    label={(entry) => `${((entry.value / 246095) * 100).toFixed(0)}%`}
                    outerRadius={80}
                    fill="#8884d8"
                    dataKey="value"
                  >
                    {distributionData.map((entry, index) => (
                      <Cell key={`cell-${index}`} fill={entry.color} />
                    ))}
                  </Pie>
                  <Tooltip
                    contentStyle={{
                      backgroundColor: "#1e293b",
                      border: "1px solid #334155",
                      borderRadius: "8px",
                    }}
                    formatter={(value: number) => formatCurrency(value)}
                  />
                  <Legend />
                </PieChart>
              </ResponsiveContainer>
              <p className="text-xs text-slate-500 mt-4 flex items-center gap-2">
                <Lock className="w-3 h-3" />
                Solo visualización
              </p>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}
