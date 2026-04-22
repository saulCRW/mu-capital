import { Card, CardContent, CardHeader, CardTitle } from "../components/ui/card";
import { Button } from "../components/ui/button";
import { Badge } from "../components/ui/badge";
import { Bell, TrendingDown, TrendingUp, Target, Plus, Settings } from "lucide-react";
import { Switch } from "../components/ui/switch";
import { Link } from "react-router";

export function Alerts() {
  const alerts = [
    { id: 1, type: "Caída", asset: "BTC", condition: "Precio < $65,000", current: "$70,000", status: "Activa", triggered: false },
    { id: 2, type: "Objetivo", asset: "AAPL", condition: "Precio > $200", current: "$195", status: "Activa", triggered: false },
    { id: 3, type: "Volatilidad", asset: "ETH", condition: "Volatilidad > 25%", current: "18%", status: "Activa", triggered: false },
    { id: 4, type: "Caída", asset: "SPY", condition: "Precio < $400", current: "$425", status: "Inactiva", triggered: false },
    { id: 5, type: "Objetivo", asset: "BTC", condition: "Precio > $75,000", current: "$70,000", status: "Activa", triggered: true },
  ];

  return (
    <div className="min-h-screen py-8">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
          <div>
            <h1 className="text-3xl font-bold text-white mb-2 bg-gradient-to-r from-cyan-400 to-emerald-400 bg-clip-text text-transparent">
              Alertas Inteligentes
            </h1>
            <p className="text-slate-400">Configura alertas personalizadas para tus inversiones</p>
          </div>
          <div className="flex gap-2">
            <Button variant="outline" className="border-slate-700 text-slate-300 hover:bg-slate-800">
              <Settings className="w-4 h-4 mr-2" />
              Configuración
            </Button>
            <Link to="/app/alerts/create">
              <Button className="bg-gradient-to-r from-cyan-500 to-emerald-500 hover:from-cyan-600 hover:to-emerald-600 text-white shadow-lg shadow-cyan-500/30 transition-all hover:scale-105">
                <Plus className="w-4 h-4 mr-2" />
                Nueva Alerta
              </Button>
            </Link>
          </div>
        </div>

        {/* Alert Types Summary */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <Card className="bg-slate-900/50 border-slate-800">
            <CardContent className="p-6">
              <div className="flex items-start justify-between mb-4">
                <div className="bg-gradient-to-br from-red-500 to-orange-500 p-2 rounded-lg shadow-lg shadow-red-500/50">
                  <TrendingDown className="w-5 h-5 text-white" />
                </div>
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
              </div>
              <div className="text-2xl font-bold text-white mb-1">1</div>
              <div className="text-sm text-slate-400">Alertas de Volatilidad</div>
            </CardContent>
          </Card>
        </div>

        {/* Alerts List */}
        <Card className="bg-slate-900/50 border-slate-800">
          <CardHeader>
            <CardTitle className="text-white">Mis Alertas</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {alerts.map((alert) => (
                <div key={alert.id} className="flex flex-col md:flex-row items-start md:items-center justify-between p-4 bg-slate-800/50 rounded-lg border border-slate-700 hover:bg-slate-800 transition-colors">
                  <div className="flex items-start gap-4 flex-1">
                    <div className={`p-2 rounded-lg ${
                      alert.type === "Caída" ? "bg-red-500/20" :
                      alert.type === "Objetivo" ? "bg-emerald-500/20" :
                      "bg-orange-500/20"
                    }`}>
                      {alert.type === "Caída" ? <TrendingDown className="w-5 h-5 text-red-400" /> :
                       alert.type === "Objetivo" ? <Target className="w-5 h-5 text-emerald-400" /> :
                       <TrendingUp className="w-5 h-5 text-orange-400" />}
                    </div>
                    <div className="flex-1">
                      <div className="flex items-center gap-2 mb-2">
                        <span className="text-white font-semibold">{alert.asset}</span>
                        <Badge variant={alert.triggered ? "default" : "outline"} className={alert.triggered ? "bg-orange-500/20 text-orange-400 border-orange-500/50" : "border-slate-600 text-slate-400"}>
                          {alert.type}
                        </Badge>
                        {alert.triggered && (
                          <Badge className="bg-red-500/20 text-red-400 border-red-500/50">
                            <Bell className="w-3 h-3 mr-1" />
                            Activada
                          </Badge>
                        )}
                      </div>
                      <p className="text-slate-400 text-sm mb-1">{alert.condition}</p>
                      <p className="text-slate-500 text-xs">Valor actual: {alert.current}</p>
                    </div>
                  </div>
                  <div className="flex items-center gap-4 mt-4 md:mt-0">
                    <Switch checked={alert.status === "Activa"} />
                    <Button variant="outline" size="sm" className="border-slate-700 text-slate-300 hover:bg-slate-800">
                      Editar
                    </Button>
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