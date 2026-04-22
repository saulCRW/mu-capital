import { useEffect } from "react";
import { useNavigate, useLocation } from "react-router";
import { Card, CardContent } from "../components/ui/card";
import { Button } from "../components/ui/button";
import { CheckCircle, ArrowRight, Plus, TrendingDown, Target, TrendingUp, Bell } from "lucide-react";
import { motion } from "motion/react";
import { Badge } from "../components/ui/badge";

export function AlertCreated() {
  const navigate = useNavigate();
  const location = useLocation();
  const alert = location.state?.alert;

  const getAlertTypeConfig = (type: string) => {
    switch (type) {
      case "drop":
        return {
          label: "Caída",
          color: "from-red-500 to-orange-500",
          shadowColor: "shadow-red-500/50",
          icon: TrendingDown,
          iconColor: "text-red-400",
          bgColor: "bg-red-500/10 border-red-500/30",
        };
      case "target":
        return {
          label: "Objetivo",
          color: "from-emerald-500 to-green-500",
          shadowColor: "shadow-emerald-500/50",
          icon: Target,
          iconColor: "text-emerald-400",
          bgColor: "bg-emerald-500/10 border-emerald-500/30",
        };
      case "volatility":
        return {
          label: "Volatilidad",
          color: "from-orange-500 to-pink-500",
          shadowColor: "shadow-orange-500/50",
          icon: TrendingUp,
          iconColor: "text-orange-400",
          bgColor: "bg-orange-500/10 border-orange-500/30",
        };
      default:
        return {
          label: "",
          color: "from-cyan-500 to-emerald-500",
          shadowColor: "shadow-cyan-500/50",
          icon: Bell,
          iconColor: "text-cyan-400",
          bgColor: "bg-cyan-500/10 border-cyan-500/30",
        };
    }
  };

  const getConditionLabel = (condition: string) => {
    switch (condition) {
      case "less":
        return "Precio <";
      case "greater":
        return "Precio >";
      case "volatility":
        return "Volatilidad >";
      default:
        return "";
    }
  };

  const config = getAlertTypeConfig(alert?.type || "");
  const Icon = config.icon;

  return (
    <div className="min-h-screen flex items-center justify-center py-8 px-4">
      <div className="max-w-2xl w-full">
        <motion.div
          initial={{ scale: 0.8, opacity: 0 }}
          animate={{ scale: 1, opacity: 1 }}
          transition={{ duration: 0.5, ease: "easeOut" }}
        >
          {/* Success Icon */}
          <div className="flex justify-center mb-8">
            <motion.div
              initial={{ scale: 0 }}
              animate={{ scale: 1 }}
              transition={{ delay: 0.2, type: "spring", stiffness: 200 }}
              className={`bg-gradient-to-br ${config.color} p-6 rounded-full shadow-2xl ${config.shadowColor}`}
            >
              <CheckCircle className="w-16 h-16 text-white" />
            </motion.div>
          </div>

          {/* Success Message */}
          <motion.div
            initial={{ y: 20, opacity: 0 }}
            animate={{ y: 0, opacity: 1 }}
            transition={{ delay: 0.3 }}
            className="text-center mb-8"
          >
            <h1 className="text-4xl font-bold text-white mb-3 bg-gradient-to-r from-cyan-400 to-emerald-400 bg-clip-text text-transparent">
              ¡Alerta Creada Exitosamente!
            </h1>
            <p className="text-slate-400 text-lg">
              Te notificaremos cuando se active esta condición
            </p>
          </motion.div>

          {/* Alert Details */}
          {alert && (
            <motion.div
              initial={{ y: 20, opacity: 0 }}
              animate={{ y: 0, opacity: 1 }}
              transition={{ delay: 0.4 }}
            >
              <Card className="bg-slate-900/50 border-slate-800 backdrop-blur-sm mb-6">
                <CardContent className="p-8">
                  <div className="space-y-6">
                    {/* Alert Type */}
                    <div className="flex items-center justify-between">
                      <span className="text-slate-400">Tipo de Alerta</span>
                      <div className="flex items-center gap-2">
                        <div className={`${config.bgColor.split(' ')[0]}/20 p-2 rounded-lg`}>
                          <Icon className={`w-5 h-5 ${config.iconColor}`} />
                        </div>
                        <span className={`${config.iconColor} font-semibold text-lg`}>
                          {config.label}
                        </span>
                      </div>
                    </div>

                    {/* Asset */}
                    <div className="flex items-center justify-between">
                      <span className="text-slate-400">Activo</span>
                      <span className="text-white font-semibold text-lg">{alert.asset}</span>
                    </div>

                    {/* Condition */}
                    <div className="flex items-center justify-between">
                      <span className="text-slate-400">Condición</span>
                      <span className="text-white font-semibold">
                        {getConditionLabel(alert.condition)}
                      </span>
                    </div>

                    {/* Target Value */}
                    <div className="flex items-center justify-between">
                      <span className="text-slate-400">Valor Objetivo</span>
                      <span className="text-cyan-400 font-bold text-xl">
                        ${parseFloat(alert.targetValue).toLocaleString()}
                      </span>
                    </div>

                    {/* Current Value */}
                    <div className="pt-4 border-t border-slate-700">
                      <div className="flex items-center justify-between">
                        <span className="text-slate-400">Valor Actual</span>
                        <span className="text-white font-semibold">
                          ${alert.currentValue.toLocaleString()}
                        </span>
                      </div>
                    </div>

                    {/* Notification Status */}
                    <div className="flex items-center justify-between">
                      <span className="text-slate-400">Notificaciones</span>
                      <Badge
                        className={
                          alert.notification
                            ? "bg-emerald-500/20 text-emerald-400 border-emerald-500/50"
                            : "bg-slate-500/20 text-slate-400 border-slate-500/50"
                        }
                      >
                        {alert.notification ? "Activadas" : "Desactivadas"}
                      </Badge>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </motion.div>
          )}

          {/* Info Box */}
          <motion.div
            initial={{ y: 20, opacity: 0 }}
            animate={{ y: 0, opacity: 1 }}
            transition={{ delay: 0.5 }}
          >
            <Card className={`border backdrop-blur-sm mb-6 ${config.bgColor}`}>
              <CardContent className="p-6">
                <div className="flex gap-3">
                  <div className={`${config.bgColor.split(' ')[0]}/30 p-2 rounded-lg h-fit`}>
                    <Bell className={`w-5 h-5 ${config.iconColor}`} />
                  </div>
                  <div>
                    <h3 className="text-white font-semibold mb-2">
                      Alerta Configurada
                    </h3>
                    <p className="text-slate-300 text-sm leading-relaxed">
                      Monitorearemos continuamente el precio de{" "}
                      <span className="font-semibold">{alert?.asset}</span> y te
                      enviaremos una notificación cuando se cumpla la condición
                      establecida.
                    </p>
                  </div>
                </div>
              </CardContent>
            </Card>
          </motion.div>

          {/* Action Buttons */}
          <motion.div
            initial={{ y: 20, opacity: 0 }}
            animate={{ y: 0, opacity: 1 }}
            transition={{ delay: 0.6 }}
            className="flex flex-col sm:flex-row gap-4"
          >
            <Button
              onClick={() => navigate("/app/alerts")}
              className="flex-1 bg-gradient-to-r from-cyan-500 to-emerald-500 hover:from-cyan-600 hover:to-emerald-600 text-white shadow-lg shadow-cyan-500/30 h-12 text-base font-semibold"
            >
              Ver Mis Alertas
              <ArrowRight className="w-5 h-5 ml-2" />
            </Button>
            <Button
              onClick={() => navigate("/app/alerts/create")}
              variant="outline"
              className="flex-1 sm:flex-none border-slate-700 text-slate-300 hover:bg-slate-800 h-12 text-base"
            >
              <Plus className="w-5 h-5 mr-2" />
              Crear Otra
            </Button>
          </motion.div>
        </motion.div>
      </div>
    </div>
  );
}
