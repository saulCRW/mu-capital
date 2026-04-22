import { Link } from "react-router";
import { TrendingUp, Shield, BarChart3, Users, ArrowRight, CheckCircle2 } from "lucide-react";
import { Button } from "../components/ui/button";
import { Card, CardContent } from "../components/ui/card";

export function Home() {
  const features = [
    {
      icon: Shield,
      title: "Seguridad Garantizada",
      description: "Custodia institucional y protocolos de seguridad de nivel bancario.",
      gradient: "from-cyan-500 to-blue-500",
      shadow: "shadow-cyan-500/50",
    },
    {
      icon: BarChart3,
      title: "Análisis Profesional",
      description: "Equipo experto en análisis técnico y fundamental de criptoactivos.",
      gradient: "from-emerald-500 to-green-500",
      shadow: "shadow-emerald-500/50",
    },
    {
      icon: TrendingUp,
      title: "Alto Rendimiento",
      description: "Estrategias optimizadas para maximizar el retorno de inversión.",
      gradient: "from-orange-500 to-pink-500",
      shadow: "shadow-orange-500/50",
    },
    {
      icon: Users,
      title: "Gestión Personalizada",
      description: "Atención dedicada y estrategias adaptadas a tu perfil de inversión.",
      gradient: "from-purple-500 to-fuchsia-500",
      shadow: "shadow-purple-500/50",
    },
  ];

  const stats = [
    { value: "$250M+", label: "Activos Bajo Gestión", color: "from-cyan-400 to-blue-400" },
    { value: "45%", label: "Retorno Anual Promedio", color: "from-emerald-400 to-green-400" },
    { value: "1,200+", label: "Inversionistas Activos", color: "from-orange-400 to-pink-400" },
    { value: "24/7", label: "Monitoreo de Mercado", color: "from-purple-400 to-fuchsia-400" },
  ];

  const benefits = [
    "Diversificación profesional en múltiples criptoactivos",
    "Rebalanceo automático del portafolio",
    "Reportes mensuales detallados de rendimiento",
    "Acceso a oportunidades de inversión exclusivas",
    "Sin comisiones de entrada o salida",
  ];

  return (
    <div className="text-white">
      {/* Hero Section */}
      <section className="relative overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-br from-cyan-600/20 via-emerald-600/20 to-transparent"></div>
        <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-pink-500/10 via-transparent to-transparent"></div>
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 md:py-32 relative">
          <div className="text-center max-w-4xl mx-auto">
            <h1 className="text-4xl md:text-6xl font-bold mb-6 bg-gradient-to-r from-cyan-400 via-emerald-400 to-cyan-400 bg-clip-text text-transparent animate-pulse">
              Invierte en el Futuro de las Finanzas
            </h1>
            <p className="text-lg md:text-xl text-slate-300 mb-8">
              MU Capital es tu socio estratégico en el mundo de las inversiones en
              criptoactivos. Gestión profesional, transparencia total y rendimientos
              superiores.
            </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <Button
                asChild
                size="lg"
                className="bg-gradient-to-r from-cyan-500 to-emerald-500 hover:from-cyan-600 hover:to-emerald-600 text-white text-lg px-8 shadow-lg shadow-cyan-500/30"
              >
                <Link to="/auth/login">
                  Iniciar Sesión <ArrowRight className="ml-2 w-5 h-5" />
                </Link>
              </Button>
              <Button
                asChild
                size="lg"
                variant="outline"
                className="border-cyan-500 text-cyan-400 hover:bg-cyan-500/10 text-lg px-8"
              >
                <Link to="/about">Conocer Más</Link>
              </Button>
            </div>
          </div>
        </div>
      </section>

      {/* Stats Section */}
      <section className="border-y border-cyan-500/20 bg-slate-950/50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-8">
            {stats.map((stat, index) => (
              <div key={index} className="text-center">
                <div className={`text-3xl md:text-4xl font-bold bg-gradient-to-r ${stat.color} bg-clip-text text-transparent mb-2`}>
                  {stat.value}
                </div>
                <div className="text-sm text-slate-400">{stat.label}</div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Features Section */}
      <section className="py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold mb-4 bg-gradient-to-r from-cyan-400 to-emerald-400 bg-clip-text text-transparent">
              ¿Por Qué Elegir MU Capital?
            </h2>
            <p className="text-slate-400 text-lg max-w-2xl mx-auto">
              Combinamos experiencia, tecnología y análisis profesional para
              ofrecerte las mejores oportunidades de inversión.
            </p>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {features.map((feature, index) => (
              <Card key={index} className="bg-slate-900/50 border-slate-800 hover:bg-slate-900 transition-all hover:scale-105">
                <CardContent className="p-6">
                  <div className={`bg-gradient-to-br ${feature.gradient} w-12 h-12 rounded-lg flex items-center justify-center mb-4 shadow-lg ${feature.shadow}`}>
                    <feature.icon className="w-6 h-6 text-white" />
                  </div>
                  <h3 className="text-lg font-semibold mb-2 text-white">
                    {feature.title}
                  </h3>
                  <p className="text-slate-400 text-sm">{feature.description}</p>
                </CardContent>
              </Card>
            ))}
          </div>
        </div>
      </section>

      {/* Benefits Section */}
      <section className="py-20 bg-slate-950/50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div>
              <h2 className="text-3xl md:text-4xl font-bold mb-6 bg-gradient-to-r from-orange-400 to-pink-400 bg-clip-text text-transparent">
                Beneficios Exclusivos para Inversionistas
              </h2>
              <p className="text-slate-400 mb-8">
                Al invertir con MU Capital, obtienes acceso a un ecosistema completo
                de servicios diseñados para maximizar tu rentabilidad y minimizar
                riesgos.
              </p>
              <div className="space-y-4">
                {benefits.map((benefit, index) => (
                  <div key={index} className="flex items-start gap-3">
                    <CheckCircle2 className="w-6 h-6 text-emerald-500 flex-shrink-0 mt-0.5" />
                    <span className="text-slate-300">{benefit}</span>
                  </div>
                ))}
              </div>
              <Button
                asChild
                size="lg"
                className="mt-8 bg-gradient-to-r from-orange-500 to-pink-500 hover:from-orange-600 hover:to-pink-600 text-white shadow-lg shadow-orange-500/30"
              >
                <Link to="/portfolio">
                  Ver Portafolio <ArrowRight className="ml-2 w-5 h-5" />
                </Link>
              </Button>
            </div>
            <div className="grid grid-cols-2 gap-4">
              <Card className="bg-gradient-to-br from-cyan-500/10 to-cyan-600/10 border-cyan-500/30 shadow-lg shadow-cyan-500/20">
                <CardContent className="p-6">
                  <div className="text-3xl font-bold text-cyan-400 mb-2">98%</div>
                  <div className="text-sm text-slate-300">Tasa de Satisfacción</div>
                </CardContent>
              </Card>
              <Card className="bg-gradient-to-br from-emerald-500/10 to-emerald-600/10 border-emerald-500/30 mt-8 shadow-lg shadow-emerald-500/20">
                <CardContent className="p-6">
                  <div className="text-3xl font-bold text-emerald-400 mb-2">5+</div>
                  <div className="text-sm text-slate-300">Años de Experiencia</div>
                </CardContent>
              </Card>
              <Card className="bg-gradient-to-br from-orange-500/10 to-orange-600/10 border-orange-500/30 -mt-8 shadow-lg shadow-orange-500/20">
                <CardContent className="p-6">
                  <div className="text-3xl font-bold text-orange-400 mb-2">15+</div>
                  <div className="text-sm text-slate-300">Criptoactivos Gestionados</div>
                </CardContent>
              </Card>
              <Card className="bg-gradient-to-br from-pink-500/10 to-pink-600/10 border-pink-500/30 shadow-lg shadow-pink-500/20">
                <CardContent className="p-6">
                  <div className="text-3xl font-bold text-pink-400 mb-2">24/7</div>
                  <div className="text-sm text-slate-300">Soporte Disponible</div>
                </CardContent>
              </Card>
            </div>
          </div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="py-20">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
          <Card className="bg-gradient-to-br from-cyan-600 via-emerald-600 to-cyan-600 border-0 shadow-2xl shadow-cyan-500/50">
            <CardContent className="p-8 md:p-12 text-center">
              <h2 className="text-3xl md:text-4xl font-bold mb-4">
                Comienza tu Viaje de Inversión Hoy
              </h2>
              <p className="text-lg text-cyan-100 mb-8 max-w-2xl mx-auto">
                Únete a miles de inversionistas que confían en MU Capital para
                hacer crecer su patrimonio en el mercado de criptoactivos.
              </p>
              <Button
                asChild
                size="lg"
                className="bg-white text-emerald-600 hover:bg-slate-100 text-lg px-8 shadow-lg"
              >
                <Link to="/dashboard">
                  Explorar Dashboard <ArrowRight className="ml-2 w-5 h-5" />
                </Link>
              </Button>
            </CardContent>
          </Card>
        </div>
      </section>
    </div>
  );
}