import { Outlet } from "react-router";
import { TrendingUp, Shield, Lock } from "lucide-react";

export function AuthLayout() {
  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-950 via-purple-950 to-slate-950 flex">
      {/* Left Side - Branding */}
      <div className="hidden lg:flex lg:w-1/2 relative overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-br from-cyan-600/20 via-emerald-600/20 to-transparent"></div>
        <div className="relative z-10 flex flex-col justify-center items-start p-12 max-w-xl mx-auto">
          <div className="flex items-center gap-3 mb-8">
            <div className="bg-gradient-to-br from-cyan-400 to-emerald-500 p-3 rounded-xl shadow-lg shadow-cyan-500/50">
              <TrendingUp className="w-8 h-8 text-white" />
            </div>
            <span className="text-3xl font-bold bg-gradient-to-r from-cyan-400 to-emerald-400 bg-clip-text text-transparent">
              MU Capital
            </span>
          </div>
          
          <h1 className="text-4xl font-bold text-white mb-4">
            Gestión Inteligente de Inversiones
          </h1>
          
          <p className="text-slate-300 text-lg mb-8">
            Plataforma profesional para la gestión y análisis de tus portafolios de inversión
            con herramientas avanzadas de análisis de riesgo.
          </p>

          <div className="space-y-4 w-full">
            <div className="flex items-start gap-3">
              <div className="bg-gradient-to-br from-cyan-500 to-blue-500 p-2 rounded-lg shadow-lg shadow-cyan-500/30">
                <Shield className="w-5 h-5 text-white" />
              </div>
              <div>
                <h3 className="text-white font-semibold mb-1">Seguridad de Clase Mundial</h3>
                <p className="text-slate-400 text-sm">
                  Autenticación de dos factores y encriptación end-to-end
                </p>
              </div>
            </div>

            <div className="flex items-start gap-3">
              <div className="bg-gradient-to-br from-emerald-500 to-green-500 p-2 rounded-lg shadow-lg shadow-emerald-500/30">
                <TrendingUp className="w-5 h-5 text-white" />
              </div>
              <div>
                <h3 className="text-white font-semibold mb-1">Análisis Avanzado</h3>
                <p className="text-slate-400 text-sm">
                  Sharpe Ratio, VaR, Monte Carlo y optimización de Markowitz
                </p>
              </div>
            </div>

            <div className="flex items-start gap-3">
              <div className="bg-gradient-to-br from-orange-500 to-pink-500 p-2 rounded-lg shadow-lg shadow-orange-500/30">
                <Lock className="w-5 h-5 text-white" />
              </div>
              <div>
                <h3 className="text-white font-semibold mb-1">Datos en Tiempo Real</h3>
                <p className="text-slate-400 text-sm">
                  Actualización automática de precios y alertas inteligentes
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Right Side - Auth Form */}
      <div className="w-full lg:w-1/2 flex items-center justify-center p-8">
        <div className="w-full max-w-md">
          <Outlet />
        </div>
      </div>
    </div>
  );
}
