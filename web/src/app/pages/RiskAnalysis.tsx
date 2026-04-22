import { Card, CardContent, CardHeader, CardTitle } from "../components/ui/card";
import { Shield, Activity, TrendingDown, AlertTriangle } from "lucide-react";
import { Progress } from "../components/ui/progress";
import {
  AreaChart,
  Area,
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  Line,
  LineChart,
} from "recharts";

export function RiskAnalysis() {
  const volatilityData = [
    { day: "Lun", value: 12 },
    { day: "Mar", value: 18 },
    { day: "Mié", value: 15 },
    { day: "Jue", value: 22 },
    { day: "Vie", value: 19 },
    { day: "Sáb", value: 16 },
    { day: "Dom", value: 14 },
  ];

  const drawdownData = [
    { month: "Ene", value: -5 },
    { month: "Feb", value: -3 },
    { month: "Mar", value: -8 },
    { month: "Abr", value: -2 },
    { month: "May", value: -12 },
    { month: "Jun", value: -4 },
  ];

  const monteCarloData = Array.from({ length: 20 }, (_, i) => ({
    scenario: i + 1,
    return: Math.random() * 60 - 10,
  }));

  return (
    <div className="min-h-screen py-8">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-white mb-2 bg-gradient-to-r from-cyan-400 to-emerald-400 bg-clip-text text-transparent">
            Análisis de Riesgo
          </h1>
          <p className="text-slate-400">
            Métricas avanzadas de riesgo y análisis de portafolio
          </p>
        </div>

        {/* KPI Cards */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
          <Card className="bg-slate-900/50 border-slate-800">
            <CardContent className="p-6">
              <div className="flex items-start justify-between mb-4">
                <div className="bg-gradient-to-br from-cyan-500 to-blue-500 p-2 rounded-lg shadow-lg shadow-cyan-500/50">
                  <Shield className="w-5 h-5 text-white" />
                </div>
              </div>
              <div className="text-2xl font-bold text-white mb-1">1.45</div>
              <div className="text-sm text-slate-400">Sharpe Ratio</div>
              <Progress value={72.5} className="mt-2" />
            </CardContent>
          </Card>

          <Card className="bg-slate-900/50 border-slate-800">
            <CardContent className="p-6">
              <div className="flex items-start justify-between mb-4">
                <div className="bg-gradient-to-br from-emerald-500 to-green-500 p-2 rounded-lg shadow-lg shadow-emerald-500/50">
                  <Activity className="w-5 h-5 text-white" />
                </div>
              </div>
              <div className="text-2xl font-bold text-white mb-1">18.5%</div>
              <div className="text-sm text-slate-400">Volatilidad Anual</div>
              <Progress value={61.67} className="mt-2" />
            </CardContent>
          </Card>

          <Card className="bg-slate-900/50 border-slate-800">
            <CardContent className="p-6">
              <div className="flex items-start justify-between mb-4">
                <div className="bg-gradient-to-br from-orange-500 to-pink-500 p-2 rounded-lg shadow-lg shadow-orange-500/50">
                  <TrendingDown className="w-5 h-5 text-white" />
                </div>
              </div>
              <div className="text-2xl font-bold text-white mb-1">-12.3%</div>
              <div className="text-sm text-slate-400">Max Drawdown</div>
              <Progress value={41} className="mt-2" />
            </CardContent>
          </Card>

          <Card className="bg-slate-900/50 border-slate-800">
            <CardContent className="p-6">
              <div className="flex items-start justify-between mb-4">
                <div className="bg-gradient-to-br from-purple-500 to-fuchsia-500 p-2 rounded-lg shadow-lg shadow-purple-500/50">
                  <AlertTriangle className="w-5 h-5 text-white" />
                </div>
              </div>
              <div className="text-2xl font-bold text-white mb-1">$15,200</div>
              <div className="text-sm text-slate-400">VaR (95%)</div>
              <Progress value={30.4} className="mt-2" />
            </CardContent>
          </Card>
        </div>

        {/* Charts */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
          <Card className="bg-slate-900/50 border-slate-800">
            <CardHeader>
              <CardTitle className="text-white bg-gradient-to-r from-cyan-400 to-emerald-400 bg-clip-text text-transparent">
                Volatilidad Diaria
              </CardTitle>
            </CardHeader>
            <CardContent>
              <ResponsiveContainer width="100%" height={250}>
                <AreaChart data={volatilityData}>
                  <defs>
                    <linearGradient id="volatility" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="5%" stopColor="#F97316" stopOpacity={0.3} />
                      <stop offset="95%" stopColor="#F97316" stopOpacity={0} />
                    </linearGradient>
                  </defs>
                  <CartesianGrid strokeDasharray="3 3" stroke="#334155" />
                  <XAxis dataKey="day" stroke="#94A3B8" />
                  <YAxis stroke="#94A3B8" />
                  <Tooltip
                    contentStyle={{
                      backgroundColor: "#0F172A",
                      border: "1px solid #334155",
                      borderRadius: "8px",
                    }}
                  />
                  <Area
                    type="monotone"
                    dataKey="value"
                    stroke="#F97316"
                    strokeWidth={2}
                    fill="url(#volatility)"
                  />
                </AreaChart>
              </ResponsiveContainer>
            </CardContent>
          </Card>

          <Card className="bg-slate-900/50 border-slate-800">
            <CardHeader>
              <CardTitle className="text-white bg-gradient-to-r from-orange-400 to-pink-400 bg-clip-text text-transparent">
                Drawdown Mensual
              </CardTitle>
            </CardHeader>
            <CardContent>
              <ResponsiveContainer width="100%" height={250}>
                <BarChart data={drawdownData}>
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
                  <Bar dataKey="value" fill="#EF4444" radius={[8, 8, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            </CardContent>
          </Card>
        </div>

        {/* Monte Carlo Simulation */}
        <Card className="bg-slate-900/50 border-slate-800 mb-8">
          <CardHeader>
            <CardTitle className="text-white bg-gradient-to-r from-purple-400 to-fuchsia-400 bg-clip-text text-transparent">
              Simulación Monte Carlo
            </CardTitle>
            <p className="text-sm text-slate-400">
              1000 escenarios simulados a 1 año
            </p>
          </CardHeader>
          <CardContent>
            <ResponsiveContainer width="100%" height={300}>
              <LineChart data={monteCarloData}>
                <CartesianGrid strokeDasharray="3 3" stroke="#334155" />
                <XAxis dataKey="scenario" stroke="#94A3B8" />
                <YAxis stroke="#94A3B8" />
                <Tooltip
                  contentStyle={{
                    backgroundColor: "#0F172A",
                    border: "1px solid #334155",
                    borderRadius: "8px",
                  }}
                />
                <Line
                  type="monotone"
                  dataKey="return"
                  stroke="#8B5CF6"
                  strokeWidth={2}
                  dot={false}
                />
              </LineChart>
            </ResponsiveContainer>
          </CardContent>
        </Card>

        {/* Markowitz Optimization */}
        <Card className="bg-slate-900/50 border-slate-800">
          <CardHeader>
            <CardTitle className="text-white bg-gradient-to-r from-emerald-400 to-green-400 bg-clip-text text-transparent">
              Optimización de Markowitz
            </CardTitle>
            <p className="text-sm text-slate-400">Asignación óptima de activos</p>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {[
                { name: "Bitcoin", weight: 35, color: "from-orange-500 to-yellow-500" },
                { name: "Ethereum", weight: 30, color: "from-blue-500 to-cyan-500" },
                { name: "S&P 500", weight: 20, color: "from-emerald-500 to-green-500" },
                { name: "Bonos", weight: 10, color: "from-purple-500 to-fuchsia-500" },
                { name: "Oro", weight: 5, color: "from-yellow-500 to-amber-500" },
              ].map((asset, index) => (
                <div key={index}>
                  <div className="flex justify-between mb-2">
                    <span className="text-slate-300">{asset.name}</span>
                    <span className="text-white font-medium">{asset.weight}%</span>
                  </div>
                  <div className="h-2 bg-slate-800 rounded-full overflow-hidden">
                    <div
                      className={`h-full bg-gradient-to-r ${asset.color} rounded-full shadow-lg`}
                      style={{ width: `${asset.weight}%` }}
                    />
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
