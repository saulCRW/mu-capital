import { Link } from "react-router";
import { Plus, TrendingUp, TrendingDown, DollarSign, PieChart } from "lucide-react";
import { Button } from "../components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "../components/ui/card";
import { Badge } from "../components/ui/badge";
import { usePortafolios } from "../../controllers/usePortafolios";

export function Portfolios() {
  const { portafolios, loading } = usePortafolios();

  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat("es-US", {
      style: "currency",
      currency: "USD",
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(value);
  };

  const totalValue = portafolios.reduce((sum, p) => sum + (p as any).totalValue || 0, 0);
  const totalInvested = portafolios.reduce((sum, p) => sum + (p as any).totalInvested || 0, 0);
  const totalPerformance = totalInvested > 0 ? ((totalValue - totalInvested) / totalInvested) * 100 : 0;

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
                {totalPerformance >= 0 ? "+" : ""}{totalPerformance.toFixed(2)}%
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
              <div className="text-2xl font-bold text-white mb-1">{portafolios.length}</div>
              <div className="text-sm text-slate-400">Portafolios Activos</div>
            </CardContent>
          </Card>
        </div>

        {/* Portfolios Grid */}
        {loading ? (
          <div className="text-center text-slate-400 py-12">Cargando portafolios...</div>
        ) : portafolios.length === 0 ? (
          <div className="text-center text-slate-400 py-12">No tienes portafolios aún. ¡Crea el primero!</div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {portafolios.map((portfolio) => {
              const performance = (portfolio as any).performance ?? 0;
              const trend = performance >= 0 ? "up" : "down";
              return (
                <Link key={portfolio.id} to={`/app/portfolios/${portfolio.id}`}>
                  <Card className="bg-slate-900/50 border-slate-800 hover:bg-slate-900 transition-all hover:scale-[1.02] cursor-pointer">
                    <CardHeader>
                      <div className="flex items-start justify-between">
                        <div>
                          <CardTitle className="text-white mb-2">{portfolio.nombre}</CardTitle>
                          <p className="text-sm text-slate-400">{portfolio.descripcion}</p>
                        </div>
                        <Badge
                          className={
                            trend === "up"
                              ? "bg-emerald-500/20 text-emerald-400 hover:bg-emerald-500/30 border-emerald-500/50"
                              : "bg-red-500/20 text-red-400 hover:bg-red-500/30 border-red-500/50"
                          }
                        >
                          {trend === "up" ? (
                            <TrendingUp className="w-3 h-3 mr-1" />
                          ) : (
                            <TrendingDown className="w-3 h-3 mr-1" />
                          )}
                          {performance >= 0 ? "+" : ""}
                          {performance.toFixed(2)}%
                        </Badge>
                      </div>
                    </CardHeader>
                    <CardContent>
                      <div className="space-y-4">
                        <div className="flex justify-between items-center">
                          <span className="text-slate-400 text-sm">Estrategia</span>
                          <span className="text-white font-semibold capitalize">
                            {portfolio.estrategia ?? "—"}
                          </span>
                        </div>
                        <div className="flex justify-between items-center">
                          <span className="text-slate-400 text-sm">Moneda</span>
                          <span className="text-slate-400">{portfolio.moneda}</span>
                        </div>
                        <div className="flex justify-between items-center">
                          <span className="text-slate-400 text-sm">Activos</span>
                          <span className="text-white">{portfolio.activos?.length ?? 0}</span>
                        </div>
                        <div className="pt-4 border-t border-slate-700">
                          <span className="text-xs text-slate-500">
                            Creado: {portfolio.created_at ? new Date(portfolio.created_at).toLocaleDateString("es-MX") : "—"}
                          </span>
                        </div>
                      </div>
                    </CardContent>
                  </Card>
                </Link>
              );
            })}
          </div>
        )}
      </div>
    </div>
  );
}
