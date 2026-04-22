import { Card, CardContent, CardHeader, CardTitle } from "../components/ui/card";
import { Badge } from "../components/ui/badge";
import { Switch } from "../components/ui/switch";
import { TrendingDown, Target, TrendingUp, Lock, AlertCircle } from "lucide-react";

export function InvestorAlerts() {
  const alertsSummary = [
    { type: "Caída", count: 2, color: "from-red-500 to-orange-500", shadowColor: "shadow-red-500/50" },
    { type: "Objetivo", count: 2, color: "from-emerald-500 to-green-500", shadowColor: "shadow-emerald-500/50" },
    { type: "Volatilidad", count: 1, color: "from-orange-500 to-pink-500", shadowColor: "shadow-orange-500/50" },
  ];

  const alerts = [
    { id: 1, type: "Caída", asset: "BTC", condition: "Precio < $65,000", current: "$70,000", status: true },
    { id: 2, type: "Objetivo", asset: "AAPL", condition: "Precio > $200", current: "$195", status: true },
    { id: 3, type: "Volatilidad", asset: "ETH", condition: "Volatilidad > 25%", current: "18%", status: true },
    { id: 4, type: "Caída", asset: "SPY", condition: "Precio < $400", current: "$425", status: false },
    { id: 5, type: "Objetivo", asset: "BTC", condition: "Precio > $75,000", current: "$70,000", status: true },
  ];

  return (
    <div className="min-h-screen py-8">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Header */}
        <div className="mb-8">
          <div className="flex items-center justify-between mb-2">
            <h1 className="text-3xl font-bold text-white bg-gradient-to-r from-cyan-400 to-emerald-400 bg-clip-text text-transparent">
              Mis Alertas
            </h1>
            <Lock className="w-5 h-5 text-slate-600" />
          </div>
          <p className="text-slate-400">Notificaciones configuradas para tu portafolio</p>
        </div>

        {/* Info Banner */}
        <Card className="bg-cyan-500/10 border-cyan-500/30 mb-8">
          <CardContent className="p-4">
            <div className="flex gap-3">
              <div className="bg-cyan-500/20 p-2 rounded-lg h-fit">
                <AlertCircle className="w-5 h-5 text-cyan-400" />
              </div>
              <div>
                <h4 className="text-white font-medium mb-1">Alertas Configuradas</h4>
                <p className="text-slate-300 text-sm leading-relaxed">
                  Las alertas son configuradas por tu administrador para mantenerte
                  informado sobre cambios importantes en tu portafolio.
                </p>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Alert Types Summary */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <Card className="bg-slate-900/50 border-slate-800">
            <CardContent className="p-6">
              <div className="flex items-start justify-between mb-4">
                <div className="bg-gradient-to-br from-red-500 to-orange-500 p-2 rounded-lg shadow-lg shadow-red-500/50">
                  <TrendingDown className="w-5 h-5 text-white" />
                </div>
                <Lock className="w-4 h-4 text-slate-600" />
              </div>
              <div className="text-2xl font-bold text-white mb-1">2</div>
              <div className="text-sm text-slate-400">Alertas de Caída</div>
            </CardContent>
          </Card>

          <Card className="bg-slate-900/50 border-slate-800">
            <CardContent className="p-6">
              <div className="flex items-start justify-between mb-4">
                <div className="bg-gradient-to-br from-emerald-500 to-green-500 p-2 rounded-lg shadow-lg shadow-emerald-500/50">
                  <Target className="w-5 h-5 text-white" />
                </div>
                <Lock className="w-4 h-4 text-slate-600" />
              </div>
              <div className="text-2xl font-bold text-white mb-1">2</div>
              <div className="text-sm text-slate-400">Alertas de Objetivo</div>
            </CardContent>
          </Card>

          <Card className="bg-slate-900/50 border-slate-800">
            <CardContent className="p-6">
              <div className="flex items-start justify-between mb-4">
                <div className="bg-gradient-to-br from-orange-500 to-pink-500 p-2 rounded-lg shadow-lg shadow-orange-500/50">
                  <TrendingUp className="w-5 h-5 text-white" />
                </div>
                <Lock className="w-4 h-4 text-slate-600" />
              </div>
              <div className="text-2xl font-bold text-white mb-1">1</div>
              <div className="text-sm text-slate-400">Alertas de Volatilidad</div>
            </CardContent>
          </Card>
        </div>

        {/* Alerts List */}
        <Card className="bg-slate-900/50 border-slate-800">
          <CardHeader>
            <div className="flex items-center justify-between">
              <CardTitle className="text-white">Mis Alertas Configuradas</CardTitle>
              <Lock className="w-4 h-4 text-slate-600" />
            </div>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {alerts.map((alert) => (
                <div
                  key={alert.id}
                  className="flex flex-col md:flex-row items-start md:items-center justify-between p-4 bg-slate-800/50 rounded-lg border border-slate-700"
                >
                  <div className="flex items-start gap-4 flex-1">
                    <div
                      className={`p-2 rounded-lg ${
                        alert.type === "Caída"
                          ? "bg-red-500/20"
                          : alert.type === "Objetivo"
                          ? "bg-emerald-500/20"
                          : "bg-orange-500/20"
                      }`}
                    >
                      {alert.type === "Caída" ? (
                        <TrendingDown className="w-5 h-5 text-red-400" />
                      ) : alert.type === "Objetivo" ? (
                        <Target className="w-5 h-5 text-emerald-400" />
                      ) : (
                        <TrendingUp className="w-5 h-5 text-orange-400" />
                      )}
                    </div>
                    <div className="flex-1">
                      <div className="flex items-center gap-2 mb-2">
                        <span className="text-white font-semibold">{alert.asset}</span>
                        <Badge
                          variant="outline"
                          className={
                            alert.type === "Caída"
                              ? "border-red-500/50 text-red-400"
                              : alert.type === "Objetivo"
                              ? "border-emerald-500/50 text-emerald-400"
                              : "border-orange-500/50 text-orange-400"
                          }
                        >
                          {alert.type}
                        </Badge>
                      </div>
                      <p className="text-slate-400 text-sm mb-1">{alert.condition}</p>
                      <p className="text-slate-500 text-xs">Valor actual: {alert.current}</p>
                    </div>
                  </div>
                  <div className="flex items-center gap-4 mt-4 md:mt-0">
                    <div className="flex items-center gap-2 opacity-50 cursor-not-allowed">
                      <Switch checked={alert.status} disabled />
                      <Lock className="w-3 h-3 text-slate-600" />
                    </div>
                  </div>
                </div>
              ))}
            </div>
            <p className="text-xs text-slate-500 mt-4 flex items-center gap-2">
              <Lock className="w-3 h-3" />
              Solo lectura – Las alertas son gestionadas por tu administrador
            </p>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
