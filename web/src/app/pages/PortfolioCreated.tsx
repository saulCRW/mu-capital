import { useEffect } from "react";
import { useNavigate, useLocation } from "react-router";
import { Card, CardContent } from "../components/ui/card";
import { Button } from "../components/ui/button";
import { CheckCircle, ArrowRight, TrendingUp, Plus } from "lucide-react";
import { motion } from "motion/react";

export function PortfolioCreated() {
  const navigate = useNavigate();
  const location = useLocation();
  const portfolio = location.state?.portfolio;

  useEffect(() => {
    // Simulate confetti or celebration effect
    const timer = setTimeout(() => {
      // Optional: Auto-redirect after 3 seconds
      // navigate("/app/portfolios");
    }, 3000);

    return () => clearTimeout(timer);
  }, [navigate]);

  const getRiskColor = (type: string) => {
    switch (type) {
      case "conservative":
        return "from-emerald-500 to-green-500";
      case "moderate":
        return "from-orange-500 to-yellow-500";
      case "aggressive":
        return "from-red-500 to-pink-500";
      default:
        return "from-cyan-500 to-emerald-500";
    }
  };

  const getRiskLabel = (type: string) => {
    switch (type) {
      case "conservative":
        return "Conservador";
      case "moderate":
        return "Moderado";
      case "aggressive":
        return "Agresivo";
      default:
        return "";
    }
  };

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
              className="bg-gradient-to-br from-emerald-500 to-green-500 p-6 rounded-full shadow-2xl shadow-emerald-500/50"
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
              ¡Portafolio Creado Exitosamente!
            </h1>
            <p className="text-slate-400 text-lg">
              Tu nuevo portafolio está listo para empezar a invertir
            </p>
          </motion.div>

          {/* Portfolio Details */}
          {portfolio && (
            <motion.div
              initial={{ y: 20, opacity: 0 }}
              animate={{ y: 0, opacity: 1 }}
              transition={{ delay: 0.4 }}
            >
              <Card className="bg-slate-900/50 border-slate-800 backdrop-blur-sm mb-6">
                <CardContent className="p-8">
                  <div className="space-y-6">
                    {/* Portfolio Name */}
                    <div>
                      <div className="text-slate-400 text-sm mb-2">
                        Nombre del Portafolio
                      </div>
                      <div className="text-white text-2xl font-bold">
                        {portfolio.name}
                      </div>
                    </div>

                    {/* Description */}
                    {portfolio.description && (
                      <div>
                        <div className="text-slate-400 text-sm mb-2">Descripción</div>
                        <div className="text-slate-300">{portfolio.description}</div>
                      </div>
                    )}

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                      {/* Type */}
                      <div>
                        <div className="text-slate-400 text-sm mb-2">Tipo</div>
                        <div
                          className={`inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gradient-to-r ${getRiskColor(
                            portfolio.type
                          )} bg-opacity-20`}
                        >
                          <div
                            className={`w-3 h-3 rounded-full bg-gradient-to-r ${getRiskColor(
                              portfolio.type
                            )}`}
                          ></div>
                          <span className="text-white font-semibold">
                            {getRiskLabel(portfolio.type)}
                          </span>
                        </div>
                      </div>

                      {/* Initial Investment */}
                      <div>
                        <div className="text-slate-400 text-sm mb-2">
                          Inversión Inicial
                        </div>
                        <div className="text-white text-xl font-bold">
                          ${parseFloat(portfolio.initialInvestment).toLocaleString()}{" "}
                          {portfolio.currency}
                        </div>
                      </div>

                      {/* Currency */}
                      <div>
                        <div className="text-slate-400 text-sm mb-2">Moneda</div>
                        <div className="text-white text-xl font-bold">
                          {portfolio.currency === "USD" && "🇺🇸 USD"}
                          {portfolio.currency === "MXN" && "🇲🇽 MXN"}
                          {portfolio.currency === "EUR" && "🇪🇺 EUR"}
                        </div>
                      </div>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </motion.div>
          )}

          {/* Next Steps */}
          <motion.div
            initial={{ y: 20, opacity: 0 }}
            animate={{ y: 0, opacity: 1 }}
            transition={{ delay: 0.5 }}
          >
            <Card className="bg-gradient-to-br from-cyan-600/20 to-emerald-600/20 border-cyan-500/30 backdrop-blur-sm mb-6">
              <CardContent className="p-6">
                <h3 className="text-white font-semibold mb-4 flex items-center gap-2">
                  <TrendingUp className="w-5 h-5 text-cyan-400" />
                  Próximos Pasos
                </h3>
                <ul className="space-y-3">
                  <li className="flex items-start gap-3">
                    <div className="bg-cyan-500/20 p-1 rounded mt-0.5">
                      <CheckCircle className="w-4 h-4 text-cyan-400" />
                    </div>
                    <span className="text-slate-300">
                      Agrega activos a tu portafolio para comenzar a invertir
                    </span>
                  </li>
                  <li className="flex items-start gap-3">
                    <div className="bg-cyan-500/20 p-1 rounded mt-0.5">
                      <CheckCircle className="w-4 h-4 text-cyan-400" />
                    </div>
                    <span className="text-slate-300">
                      Configura alertas automáticas para monitorear tu inversión
                    </span>
                  </li>
                  <li className="flex items-start gap-3">
                    <div className="bg-cyan-500/20 p-1 rounded mt-0.5">
                      <CheckCircle className="w-4 h-4 text-cyan-400" />
                    </div>
                    <span className="text-slate-300">
                      Revisa el análisis de riesgo para optimizar tu estrategia
                    </span>
                  </li>
                </ul>
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
              onClick={() => navigate("/app/portfolios")}
              className="flex-1 bg-gradient-to-r from-cyan-500 to-emerald-500 hover:from-cyan-600 hover:to-emerald-600 text-white shadow-lg shadow-cyan-500/30 h-12 text-base font-semibold"
            >
              Ver Mis Portafolios
              <ArrowRight className="w-5 h-5 ml-2" />
            </Button>
            <Button
              onClick={() => navigate("/app/portfolios/create")}
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
