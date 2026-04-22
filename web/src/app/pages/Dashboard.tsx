import {
  LineChart,
  Line,
  AreaChart,
  Area,
  BarChart,
  Bar,
  PieChart,
  Pie,
  Cell,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer,
} from "recharts";
import { Card, CardContent, CardHeader, CardTitle } from "../components/ui/card";
import { ArrowUpRight, ArrowDownRight, TrendingUp, Wallet, DollarSign, Activity, Shield, Bell } from "lucide-react";
import { Badge } from "../components/ui/badge";

export function Dashboard() {
  const performanceData = [
    { month: "Ago", portfolio: 100000, benchmark: 100000 },
    { month: "Sep", portfolio: 112000, benchmark: 105000 },
    { month: "Oct", portfolio: 125000, benchmark: 108000 },
    { month: "Nov", portfolio: 138000, benchmark: 112000 },
    { month: "Dic", portfolio: 165000, benchmark: 115000 },
    { month: "Ene", portfolio: 185000, benchmark: 118000 },
    { month: "Feb", portfolio: 210000, benchmark: 122000 },
  ];

  const allocationData = [
    { name: "Acciones", value: 40, color: "#06B6D4" },
    { name: "Criptomonedas", value: 30, color: "#10B981" },
    { name: "ETFs", value: 20, color: "#F97316" },
    { name: "Renta Fija", value: 10, color: "#A855F7" },
  ];

  const dailyData = Array.from({ length: 24 }, (_, i) => ({
    hour: `${i}:00`,
    value: 200000 + Math.random() * 20000,
  }));

  const topAssets = [
    { symbol: "BTC", name: "Bitcoin", value: 105000, change: 8.5, trend: "up" },
    { symbol: "AAPL", name: "Apple", value: 97500, change: 5.2, trend: "up" },
    { symbol: "ETH", name: "Ethereum", value: 52500, change: 12.3, trend: "up" },
    { symbol: "SPY", name: "S&P 500", value: 42500, change: 3.1, trend: "up" },
  ];

  const recentAlerts = [
    { type: "success", message: "BTC alcanzó objetivo de $70,000", time: "Hace 2 horas" },
    { type: "warning", message: "Alta volatilidad detectada en ETH", time: "Hace 5 horas" },
    { type: "info", message: "Rebalanceo automático completado", time: "Hace 1 día" },
  ];

  const stats = [
    {
      title: "Valor Total del Portafolio",
      value: "$245,000",
      change: "+10.5%",
      isPositive: true,
      icon: Wallet,
      gradient: "from-cyan-500 to-blue-500",
      shadow: "shadow-cyan-500/50",
    },
    {
      title: "ROI Total",
      value: "+36.1%",
      change: "vs inversión inicial",
      isPositive: true,
      icon: TrendingUp,
      gradient: "from-emerald-500 to-green-500",
      shadow: "shadow-emerald-500/50",
    },
    {
      title: "Rendimiento Mensual",
      value: "+13.5%",
      change: "+5% vs mes anterior",
      isPositive: true,
      icon: Activity,
      gradient: "from-orange-500 to-pink-500",
      shadow: "shadow-orange-500/50",
    },
    {
      title: "Sharpe Ratio",
      value: "1.45",
      change: "Riesgo ajustado",
      isPositive: true,
      icon: Shield,
      gradient: "from-purple-500 to-fuchsia-500",
      shadow: "shadow-purple-500/50",
    },
  ];

  const formatCurrency = (value: number) =>
    new Intl.NumberFormat("es-US", {
      style: "currency",
      currency: "USD",
      minimumFractionDigits: 0,
    }).format(value);

  return (
    <div className="min-h-screen py-8">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-white mb-2 bg-gradient-to-r from-cyan-400 to-emerald-400 bg-clip-text text-transparent">
            Dashboard Ejecutivo
          </h1>
          <p className="text-slate-400">
            Visión general de tus inversiones en tiempo real
          </p>
        </div>

        {/* Stats Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          {stats.map((stat, index) => (
            <Card key={index} className="bg-slate-900/50 border-slate-800 hover:scale-105 transition-transform">
              <CardContent className="p-6">
                <div className="flex items-start justify-between mb-4">
                  <div className={`bg-gradient-to-br ${stat.gradient} p-2 rounded-lg shadow-lg ${stat.shadow}`}>
                    <stat.icon className="w-5 h-5 text-white" />
                  </div>
                  <div className={`flex items-center gap-1 text-sm font-medium ${stat.isPositive ? "text-emerald-500" : "text-red-500"}`}>
                    {stat.isPositive ? (
                      <ArrowUpRight className="w-4 h-4" />
                    ) : (
                      <ArrowDownRight className="w-4 h-4" />
                    )}
                    {stat.change}
                  </div>
                </div>
                <div className="text-2xl font-bold text-white mb-1">{stat.value}</div>
                <div className="text-sm text-slate-400">{stat.title}</div>
              </CardContent>
            </Card>
          ))}
        </div>

        {/* Main Charts */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
          <Card className="lg:col-span-2 bg-slate-900/50 border-slate-800">
            <CardHeader>
              <CardTitle className="text-white bg-gradient-to-r from-cyan-400 to-emerald-400 bg-clip-text text-transparent">
                Evolución del Portafolio vs Benchmark
              </CardTitle>
              <p className="text-sm text-slate-400">Últimos 7 meses</p>
            </CardHeader>
            <CardContent>
              <ResponsiveContainer width="100%" height={300}>
                <AreaChart data={performanceData}>
                  <defs>
                    <linearGradient id="portfolio" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="5%" stopColor="#06B6D4" stopOpacity={0.4} />
                      <stop offset="95%" stopColor="#06B6D4" stopOpacity={0} />
                    </linearGradient>
                    <linearGradient id="benchmark" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="5%" stopColor="#F97316" stopOpacity={0.3} />
                      <stop offset="95%" stopColor="#F97316" stopOpacity={0} />
                    </linearGradient>
                  </defs>
                  <CartesianGrid strokeDasharray="3 3" stroke="#334155" />
                  <XAxis dataKey="month" stroke="#94A3B8" />
                  <YAxis stroke="#94A3B8" />
                  <Tooltip
                    contentStyle={{
                      backgroundColor: "#0F172A",
                      border: "1px solid #334155",
                      borderRadius: "8px",
                    }}
                  />
                  <Legend />
                  <Area
                    type="monotone"
                    dataKey="portfolio"
                    stroke="#06B6D4"
                    strokeWidth={3}
                    fill="url(#portfolio)"
                    name="Mi Portafolio"
                  />
                  <Area
                    type="monotone"
                    dataKey="benchmark"
                    stroke="#F97316"
                    strokeWidth={2}
                    fill="url(#benchmark)"
                    name="S&P 500"
                  />
                </AreaChart>
              </ResponsiveContainer>
            </CardContent>
          </Card>

          <Card className="bg-slate-900/50 border-slate-800">
            <CardHeader>
              <CardTitle className="text-white bg-gradient-to-r from-orange-400 to-pink-400 bg-clip-text text-transparent">
                Distribución por Tipo
              </CardTitle>
            </CardHeader>
            <CardContent>
              <ResponsiveContainer width="100%" height={300}>
                <PieChart>
                  <Pie
                    data={allocationData}
                    cx="50%"
                    cy="50%"
                    innerRadius={60}
                    outerRadius={100}
                    paddingAngle={2}
                    dataKey="value"
                  >
                    {allocationData.map((entry, index) => (
                      <Cell key={`cell-${index}`} fill={entry.color} />
                    ))}
                  </Pie>
                  <Tooltip
                    contentStyle={{
                      backgroundColor: "#0F172A",
                      border: "1px solid #334155",
                      borderRadius: "8px",
                    }}
                  />
                </PieChart>
              </ResponsiveContainer>
              <div className="space-y-2 mt-4">
                {allocationData.map((item, index) => (
                  <div key={index} className="flex items-center justify-between text-sm">
                    <div className="flex items-center gap-2">
                      <div
                        className="w-3 h-3 rounded-full shadow-lg"
                        style={{ backgroundColor: item.color }}
                      />
                      <span className="text-slate-300">{item.name}</span>
                    </div>
                    <span className="text-white font-medium">{item.value}%</span>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Bottom Section */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          {/* Top Assets */}
          <Card className="bg-slate-900/50 border-slate-800">
            <CardHeader>
              <CardTitle className="text-white bg-gradient-to-r from-emerald-400 to-green-400 bg-clip-text text-transparent">
                Principales Activos
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                {topAssets.map((asset, index) => (
                  <div key={index} className="flex items-center justify-between p-3 bg-slate-800/50 rounded-lg">
                    <div className="flex items-center gap-3">
                      <div className="bg-gradient-to-br from-cyan-500 to-emerald-500 w-10 h-10 rounded-full flex items-center justify-center font-bold text-white text-sm">
                        {asset.symbol[0]}
                      </div>
                      <div>
                        <div className="text-white font-medium">{asset.symbol}</div>
                        <div className="text-sm text-slate-400">{asset.name}</div>
                      </div>
                    </div>
                    <div className="text-right">
                      <div className="text-white font-medium">
                        {formatCurrency(asset.value)}
                      </div>
                      <Badge className="bg-emerald-500/20 text-emerald-400 border-emerald-500/50">
                        <ArrowUpRight className="w-3 h-3 mr-1" />
                        +{asset.change}%
                      </Badge>
                    </div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>

          {/* Recent Alerts */}
          <Card className="bg-slate-900/50 border-slate-800">
            <CardHeader>
              <CardTitle className="text-white bg-gradient-to-r from-purple-400 to-fuchsia-400 bg-clip-text text-transparent">
                Alertas Recientes
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                {recentAlerts.map((alert, index) => (
                  <div key={index} className="flex items-start gap-3 p-3 bg-slate-800/50 rounded-lg">
                    <div className={`p-2 rounded-lg ${
                      alert.type === "success" ? "bg-emerald-500/20" :
                      alert.type === "warning" ? "bg-orange-500/20" :
                      "bg-cyan-500/20"
                    }`}>
                      <Bell className={`w-4 h-4 ${
                        alert.type === "success" ? "text-emerald-400" :
                        alert.type === "warning" ? "text-orange-400" :
                        "text-cyan-400"
                      }`} />
                    </div>
                    <div className="flex-1">
                      <p className="text-white text-sm mb-1">{alert.message}</p>
                      <p className="text-slate-500 text-xs">{alert.time}</p>
                    </div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}
