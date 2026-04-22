import { Link } from "react-router";
import { Plus, TrendingUp, TrendingDown, DollarSign, PieChart } from "lucide-react";
import { Button } from "../components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "../components/ui/card";
import { Badge } from "../components/ui/badge";

export function Portfolios() {
  const portfolios = [
    {
      id: 1,
      name: "Portafolio Principal",
      description: "Inversiones diversificadas de largo plazo",
      totalValue: 245000,
      totalInvested: 180000,
      performance: 36.11,
      assets: 12,
      lastUpdate: "Hace 2 horas",
      trend: "up",
    },
    {
      id: 2,
      name: "Tecnología",
      description: "Acciones de empresas tecnológicas",
      totalValue: 89500,
      totalInvested: 75000,
      performance: 19.33,
      assets: 8,
      lastUpdate: "Hace 1 hora",
      trend: "up",
    },
    {
      id: 3,
      name: "Criptomonedas",
      description: "Portafolio enfocado en activos digitales",
      totalValue: 156000,
      totalInvested: 120000,
      performance: 30.0,
      assets: 15,
      lastUpdate: "Hace 30 min",
      trend: "up",
    },
    {
      id: 4,
      name: "Renta Fija",
      description: "Bonos y activos de bajo riesgo",
      totalValue: 52000,
      totalInvested: 50000,
      performance: 4.0,
      assets: 6,
      lastUpdate: "Hace 3 horas",
      trend: "up",
    },
  ];

  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat("es-US", {
      style: "currency",
      currency: "USD",
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(value);
  };

  const totalValue = portfolios.reduce((sum, p) => sum + p.totalValue, 0);
  const totalInvested = portfolios.reduce((sum, p) => sum + p.totalInvested, 0);
  const totalPerformance = ((totalValue - totalInvested) / totalInvested) * 100;

  return (
    <div className="min-h-screen py-8">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Header */}
        <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
          <div>
            <h1 className="text-3xl font-bold text-white mb-2 bg-gradient-to-r from-cyan-400 to-emerald-400 bg-clip-text text-transparent">
              Mis Portafolios
            </h1>
            <p className="text-slate-400">Gestiona todos tus portafolios de inversión</p>
          </div>
          <Link to="/app/portfolios/create">
            <Button className="bg-gradient-to-r from-cyan-500 to-emerald-500 hover:from-cyan-600 hover:to-emerald-600 text-white shadow-lg shadow-cyan-500/30 transition-all hover:scale-105">
              <Plus className="w-4 h-4 mr-2" />
              Crear Nuevo Portafolio
            </Button>
          </Link>
        </div>

        {/* Summary Cards */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <Card className="bg-slate-900/50 border-slate-800">
            <CardContent className="p-6">
              <div className="flex items-start justify-between mb-4">
                <div className="bg-gradient-to-br from-cyan-500 to-blue-500 p-2 rounded-lg shadow-lg shadow-cyan-500/50">
                  <DollarSign className="w-5 h-5 text-white" />
                </div>
              </div>
              <div className="text-2xl font-bold text-white mb-1">
                {formatCurrency(totalValue)}
              </div>
              <div className="text-sm text-slate-400">Valor Total</div>
            </CardContent>
          </Card>

          <Card className="bg-slate-900/50 border-slate-800">
            <CardContent className="p-6">
              <div className="flex items-start justify-between mb-4">
                <div className="bg-gradient-to-br from-emerald-500 to-green-500 p-2 rounded-lg shadow-lg shadow-emerald-500/50">
                  <TrendingUp className="w-5 h-5 text-white" />
                </div>
              </div>
              <div className="text-2xl font-bold text-white mb-1">
                +{totalPerformance.toFixed(2)}%
              </div>
              <div className="text-sm text-slate-400">Rendimiento Total</div>
            </CardContent>
          </Card>

          <Card className="bg-slate-900/50 border-slate-800">
            <CardContent className="p-6">
              <div className="flex items-start justify-between mb-4">
                <div className="bg-gradient-to-br from-orange-500 to-pink-500 p-2 rounded-lg shadow-lg shadow-orange-500/50">
                  <PieChart className="w-5 h-5 text-white" />
                </div>
              </div>
              <div className="text-2xl font-bold text-white mb-1">{portfolios.length}</div>
              <div className="text-sm text-slate-400">Portafolios Activos</div>
            </CardContent>
          </Card>
        </div>

        {/* Portfolios Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          {portfolios.map((portfolio) => (
            <Link key={portfolio.id} to={`/app/portfolios/${portfolio.id}`}>
              <Card className="bg-slate-900/50 border-slate-800 hover:bg-slate-900 transition-all hover:scale-[1.02] cursor-pointer">
                <CardHeader>
                  <div className="flex items-start justify-between">
                    <div>
                      <CardTitle className="text-white mb-2">{portfolio.name}</CardTitle>
                      <p className="text-sm text-slate-400">{portfolio.description}</p>
                    </div>
                    <Badge
                      className={
                        portfolio.trend === "up"
                          ? "bg-emerald-500/20 text-emerald-400 hover:bg-emerald-500/30 border-emerald-500/50"
                          : "bg-red-500/20 text-red-400 hover:bg-red-500/30 border-red-500/50"
                      }
                    >
                      {portfolio.trend === "up" ? (
                        <TrendingUp className="w-3 h-3 mr-1" />
                      ) : (
                        <TrendingDown className="w-3 h-3 mr-1" />
                      )}
                      {portfolio.performance > 0 ? "+" : ""}
                      {portfolio.performance.toFixed(2)}%
                    </Badge>
                  </div>
                </CardHeader>
                <CardContent>
                  <div className="space-y-4">
                    <div className="flex justify-between items-center">
                      <span className="text-slate-400 text-sm">Valor Actual</span>
                      <span className="text-white font-semibold">
                        {formatCurrency(portfolio.totalValue)}
                      </span>
                    </div>
                    <div className="flex justify-between items-center">
                      <span className="text-slate-400 text-sm">Inversión Inicial</span>
                      <span className="text-slate-400">
                        {formatCurrency(portfolio.totalInvested)}
                      </span>
                    </div>
                    <div className="flex justify-between items-center">
                      <span className="text-slate-400 text-sm">Activos</span>
                      <span className="text-white">{portfolio.assets}</span>
                    </div>
                    <div className="pt-4 border-t border-slate-700">
                      <span className="text-xs text-slate-500">
                        Última actualización: {portfolio.lastUpdate}
                      </span>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </Link>
          ))}
        </div>
      </div>
    </div>
  );
}