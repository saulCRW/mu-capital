import { Card, CardContent, CardHeader, CardTitle } from "../components/ui/card";
import { Button } from "../components/ui/button";
import { Badge } from "../components/ui/badge";
import { Bell, TrendingDown, TrendingUp, Target, Plus, Settings } from "lucide-react";
import { Switch } from "../components/ui/switch";
import { Link } from "react-router";
import { useAlertas } from "../../controllers/useAlertas";

export function Alerts() {
  const { alertas, loading, toggleActiva } = useAlertas();

  const tipoConfig = (tipo: string) => {
    if (tipo === "precio_minimo") return { label: "Caída", icon: TrendingDown, iconClass: "text-red-400", bgClass: "bg-red-500/20" };
    if (tipo === "precio_maximo") return { label: "Objetivo", icon: Target, iconClass: "text-emerald-400", bgClass: "bg-emerald-500/20" };
    return { label: "Volatilidad", icon: TrendingUp, iconClass: "text-orange-400", bgClass: "bg-orange-500/20" };
  };

  const caidas = alertas.filter(a => a.tipo === "precio_minimo").length;
  const objetivos = alertas.filter(a => a.tipo === "precio_maximo").length;
  const volatilidad = alertas.filter(a => a.tipo === "variacion_porcentual").length;

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
              <div className="text-2xl font-bold text-white mb-1">{caidas}</div>
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
              <div className="text-2xl font-bold text-white mb-1">{objetivos}</div>
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
              <div className="text-2xl font-bold text-white mb-1">{volatilidad}</div>
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
            {loading ? (
              <div className="text-center text-slate-400 py-8">Cargando alertas...</div>
            ) : alertas.length === 0 ? (
              <div className="text-center text-slate-400 py-8">No tienes alertas configuradas aún.</div>
            ) : (
              <div className="space-y-4">
                {alertas.map((alert) => {
                  const config = tipoConfig(alert.tipo);
                  const Icon = config.icon;
                  const condicionTexto = alert.condicion?.[0]
                    ? `${alert.condicion[0].campo} ${alert.condicion[0].operador} ${alert.condicion[0].valor}`
                    : alert.nombre;
                  return (
                    <div key={alert.id} className="flex flex-col md:flex-row items-start md:items-center justify-between p-4 bg-slate-800/50 rounded-lg border border-slate-700 hover:bg-slate-800 transition-colors">
                      <div className="flex items-start gap-4 flex-1">
                        <div className={`p-2 rounded-lg ${config.bgClass}`}>
                          <Icon className={`w-5 h-5 ${config.iconClass}`} />
                        </div>
                        <div className="flex-1">
                          <div className="flex items-center gap-2 mb-2">
                            <span className="text-white font-semibold">
                              {alert.activo?.simbolo ?? "—"}
                            </span>
                            <Badge variant={alert.activada_en ? "default" : "outline"} className={
                              alert.activada_en
                                ? "bg-orange-500/20 text-orange-400 border-orange-500/50"
                                : "border-slate-600 text-slate-400"
                            }>
                              {config.label}
                            </Badge>
                            {alert.activada_en && (
                              <Badge className="bg-red-500/20 text-red-400 border-red-500/50">
                                <Bell className="w-3 h-3 mr-1" />
                                Activada
                              </Badge>
                            )}
                          </div>
                          <p className="text-slate-400 text-sm mb-1">{condicionTexto}</p>
                          <p className="text-slate-500 text-xs">{alert.nombre}</p>
                        </div>
                      </div>
                      <div className="flex items-center gap-4 mt-4 md:mt-0">
                        <Switch
                          checked={alert.activa}
                          onCheckedChange={(checked) => toggleActiva(alert.id, checked)}
                        />
                        <Button variant="outline" size="sm" className="border-slate-700 text-slate-300 hover:bg-slate-800">
                          Editar
                        </Button>
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
