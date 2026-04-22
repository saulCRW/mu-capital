import { useEffect } from "react";
import { useNavigate, useLocation } from "react-router";
import { Card, CardContent } from "../components/ui/card";
import { Button } from "../components/ui/button";
import { CheckCircle, ArrowRight, Plus, Shield, BarChart3, Users, Copy, Check } from "lucide-react";
import { motion } from "motion/react";
import { Badge } from "../components/ui/badge";
import { useState } from "react";

export function UserCreated() {
  const navigate = useNavigate();
  const location = useLocation();
  const user = location.state?.user;
  const [copied, setCopied] = useState(false);

  const copyToClipboard = (text: string) => {
    navigator.clipboard.writeText(text);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  const getRoleConfig = (role: string) => {
    switch (role) {
      case "admin":
        return {
          label: "Administrador",
          color: "from-purple-500 to-fuchsia-500",
          shadowColor: "shadow-purple-500/50",
          icon: Shield,
          iconColor: "text-purple-400",
          bgColor: "bg-purple-500/10 border-purple-500/30",
        };
      case "analyst":
        return {
          label: "Analista",
          color: "from-cyan-500 to-blue-500",
          shadowColor: "shadow-cyan-500/50",
          icon: BarChart3,
          iconColor: "text-cyan-400",
          bgColor: "bg-cyan-500/10 border-cyan-500/30",
        };
      case "investor":
        return {
          label: "Inversionista",
          color: "from-emerald-500 to-green-500",
          shadowColor: "shadow-emerald-500/50",
          icon: Users,
          iconColor: "text-emerald-400",
          bgColor: "bg-emerald-500/10 border-emerald-500/30",
        };
      default:
        return {
          label: "",
          color: "from-cyan-500 to-emerald-500",
          shadowColor: "shadow-cyan-500/50",
          icon: Users,
          iconColor: "text-cyan-400",
          bgColor: "bg-cyan-500/10 border-cyan-500/30",
        };
    }
  };

  const config = getRoleConfig(user?.role || "");
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
              ¡Usuario Creado Exitosamente!
            </h1>
            <p className="text-slate-400 text-lg">
              El nuevo usuario ha sido añadido a la plataforma
            </p>
          </motion.div>

          {/* User Details */}
          {user && (
            <motion.div
              initial={{ y: 20, opacity: 0 }}
              animate={{ y: 0, opacity: 1 }}
              transition={{ delay: 0.4 }}
            >
              <Card className="bg-slate-900/50 border-slate-800 backdrop-blur-sm mb-6">
                <CardContent className="p-8">
                  <div className="space-y-6">
                    {/* Name */}
                    <div className="flex items-center justify-between">
                      <span className="text-slate-400">Nombre Completo</span>
                      <span className="text-white font-semibold text-lg">{user.name}</span>
                    </div>

                    {/* Email */}
                    <div className="flex items-center justify-between">
                      <span className="text-slate-400">Correo Electrónico</span>
                      <span className="text-white font-medium">
                        {user.email}@mucapital.com
                      </span>
                    </div>

                    {/* Role */}
                    <div className="flex items-center justify-between">
                      <span className="text-slate-400">Rol</span>
                      <div className="flex items-center gap-2">
                        <div className={`${config.bgColor.split(" ")[0]}/20 p-2 rounded-lg`}>
                          <Icon className={`w-5 h-5 ${config.iconColor}`} />
                        </div>
                        <span className={`${config.iconColor} font-semibold text-lg`}>
                          {config.label}
                        </span>
                      </div>
                    </div>

                    {/* Status */}
                    <div className="flex items-center justify-between">
                      <span className="text-slate-400">Estado</span>
                      <Badge
                        className={
                          user.status
                            ? "bg-emerald-500/20 text-emerald-400 border-emerald-500/50"
                            : "bg-slate-500/20 text-slate-400 border-slate-500/50"
                        }
                      >
                        {user.status ? "Activo" : "Inactivo"}
                      </Badge>
                    </div>

                    {/* Portfolios */}
                    <div className="flex items-center justify-between">
                      <span className="text-slate-400">Portafolios Asignados</span>
                      <span className="text-white font-semibold">{user.portfolios}</span>
                    </div>

                    {/* Password */}
                    <div className="pt-4 border-t border-slate-700">
                      <div className="space-y-2">
                        <span className="text-slate-400 text-sm">Contraseña Temporal</span>
                        <div className="flex items-center gap-2">
                          <div className="flex-1 bg-slate-800/50 border border-slate-700 rounded-lg p-3 font-mono text-white">
                            {user.password}
                          </div>
                          <Button
                            onClick={() => copyToClipboard(user.password)}
                            variant="outline"
                            size="sm"
                            className="border-slate-700 text-slate-300 hover:bg-slate-800 h-10 px-4"
                          >
                            {copied ? (
                              <>
                                <Check className="w-4 h-4 mr-2" />
                                Copiado
                              </>
                            ) : (
                              <>
                                <Copy className="w-4 h-4 mr-2" />
                                Copiar
                              </>
                            )}
                          </Button>
                        </div>
                        <p className="text-xs text-slate-500">
                          ⚠️ Comparte esta contraseña de forma segura con el usuario
                        </p>
                      </div>
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
                  <div className={`${config.bgColor.split(" ")[0]}/30 p-2 rounded-lg h-fit`}>
                    <Icon className={`w-5 h-5 ${config.iconColor}`} />
                  </div>
                  <div>
                    <h3 className="text-white font-semibold mb-2">
                      Acceso Configurado
                    </h3>
                    <p className="text-slate-300 text-sm leading-relaxed mb-3">
                      El usuario <span className="font-semibold">{user?.name}</span> ahora
                      tiene acceso a la plataforma con rol de{" "}
                      <span className="font-semibold">{config.label}</span>.
                    </p>
                    <div className="bg-slate-900/50 border border-slate-700 rounded-lg p-3">
                      <p className="text-slate-300 text-xs">
                        <strong className="text-white">Importante:</strong> El usuario deberá
                        cambiar su contraseña temporal en el primer inicio de sesión por
                        motivos de seguridad.
                      </p>
                    </div>
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
              onClick={() => navigate("/app/admin")}
              className="flex-1 bg-gradient-to-r from-cyan-500 to-emerald-500 hover:from-cyan-600 hover:to-emerald-600 text-white shadow-lg shadow-cyan-500/30 h-12 text-base font-semibold"
            >
              Ver Gestión de Usuarios
              <ArrowRight className="w-5 h-5 ml-2" />
            </Button>
            <Button
              onClick={() => navigate("/app/admin/users/create")}
              variant="outline"
              className="flex-1 sm:flex-none border-slate-700 text-slate-300 hover:bg-slate-800 h-12 text-base"
            >
              <Plus className="w-5 h-5 mr-2" />
              Crear Otro
            </Button>
          </motion.div>
        </motion.div>
      </div>
    </div>
  );
}
