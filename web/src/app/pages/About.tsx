import { Card, CardContent } from "../components/ui/card";
import {
  Shield,
  Users,
  Target,
  Award,
  TrendingUp,
  Globe,
  BarChart3,
  Lock,
} from "lucide-react";

export function About() {
  const values = [
    {
      icon: Shield,
      title: "Seguridad",
      description:
        "Implementamos los más altos estándares de seguridad y custodia institucional para proteger los activos de nuestros inversionistas.",
      gradient: "from-cyan-500 to-blue-500",
      shadow: "shadow-cyan-500/50",
    },
    {
      icon: TrendingUp,
      title: "Transparencia",
      description:
        "Reportes detallados y acceso en tiempo real a toda la información del fondo para garantizar total transparencia.",
      gradient: "from-emerald-500 to-green-500",
      shadow: "shadow-emerald-500/50",
    },
    {
      icon: Target,
      title: "Excelencia",
      description:
        "Buscamos constantemente superar los benchmarks del mercado mediante análisis riguroso y estrategias innovadoras.",
      gradient: "from-orange-500 to-pink-500",
      shadow: "shadow-orange-500/50",
    },
    {
      icon: Users,
      title: "Compromiso",
      description:
        "Nos comprometemos con el éxito financiero de nuestros inversionistas mediante atención personalizada y dedicada.",
      gradient: "from-purple-500 to-fuchsia-500",
      shadow: "shadow-purple-500/50",
    },
  ];

  const team = [
    {
      name: "María Rodríguez",
      role: "CEO & Fundadora",
      description:
        "15+ años en finanzas tradicionales, especialista en gestión de activos y pionera en inversiones de criptoactivos.",
      color: "from-cyan-500 to-blue-500",
    },
    {
      name: "Carlos Mendoza",
      role: "Director de Inversiones",
      description:
        "Experto en análisis cuantitativo con un MBA de Wharton. Anteriormente en Goldman Sachs y Binance.",
      color: "from-emerald-500 to-green-500",
    },
    {
      name: "Ana Silva",
      role: "Directora de Tecnología",
      description:
        "Ingeniera de software con 10+ años en blockchain y sistemas de trading algorítmico de alto rendimiento.",
      color: "from-orange-500 to-pink-500",
    },
    {
      name: "Jorge Ramírez",
      role: "Director de Riesgos",
      description:
        "Especialista en gestión de riesgos financieros, certificado FRM con experiencia en mercados emergentes.",
      color: "from-purple-500 to-fuchsia-500",
    },
  ];

  const stats = [
    { icon: Globe, value: "15+", label: "Países con Inversionistas", gradient: "from-cyan-500 to-blue-500", shadow: "shadow-cyan-500/50" },
    { icon: Award, value: "5+", label: "Años de Experiencia", gradient: "from-emerald-500 to-green-500", shadow: "shadow-emerald-500/50" },
    { icon: Users, value: "1,200+", label: "Inversionistas Activos", gradient: "from-orange-500 to-pink-500", shadow: "shadow-orange-500/50" },
    { icon: BarChart3, value: "$250M+", label: "Activos Bajo Gestión", gradient: "from-purple-500 to-fuchsia-500", shadow: "shadow-purple-500/50" },
  ];

  return (
    <div className="min-h-screen py-8">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Hero Section */}
        <div className="text-center mb-16">
          <h1 className="text-4xl md:text-5xl font-bold text-white mb-6 bg-gradient-to-r from-cyan-400 via-emerald-400 to-cyan-400 bg-clip-text text-transparent">
            Sobre MU Capital
          </h1>
          <p className="text-lg md:text-xl text-slate-300 max-w-3xl mx-auto">
            Somos un fondo de inversión especializado en criptoactivos, dedicado a
            proporcionar rendimientos superiores mediante estrategias profesionales de
            gestión de activos digitales.
          </p>
        </div>

        {/* Stats */}
        <div className="grid grid-cols-2 md:grid-cols-4 gap-6 mb-16">
          {stats.map((stat, index) => (
            <Card key={index} className="bg-slate-900/50 border-slate-800 hover:scale-105 transition-transform">
              <CardContent className="p-6 text-center">
                <div className={`bg-gradient-to-br ${stat.gradient} w-12 h-12 rounded-lg flex items-center justify-center mx-auto mb-4 shadow-lg ${stat.shadow}`}>
                  <stat.icon className="w-6 h-6 text-white" />
                </div>
                <div className={`text-2xl md:text-3xl font-bold text-white mb-1 bg-gradient-to-r ${stat.gradient} bg-clip-text text-transparent`}>
                  {stat.value}
                </div>
                <div className="text-sm text-slate-400">{stat.label}</div>
              </CardContent>
            </Card>
          ))}
        </div>

        {/* Mission & Vision */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-8 mb-16">
          <Card className="bg-gradient-to-br from-cyan-600/20 to-blue-800/20 border-cyan-500/30 hover:scale-105 transition-transform">
            <CardContent className="p-8">
              <div className="bg-gradient-to-br from-cyan-500 to-blue-500 w-12 h-12 rounded-lg flex items-center justify-center mb-4 shadow-lg shadow-cyan-500/50">
                <Target className="w-6 h-6 text-white" />
              </div>
              <h2 className="text-2xl font-bold text-white mb-4 bg-gradient-to-r from-cyan-400 to-blue-400 bg-clip-text text-transparent">Nuestra Misión</h2>
              <p className="text-slate-300 leading-relaxed">
                Democratizar el acceso a inversiones profesionales en criptoactivos,
                ofreciendo gestión de activos de clase mundial que antes solo estaba
                disponible para inversionistas institucionales. Buscamos maximizar los
                retornos mientras gestionamos activamente los riesgos inherentes al
                mercado de criptomonedas.
              </p>
            </CardContent>
          </Card>

          <Card className="bg-gradient-to-br from-emerald-600/20 to-green-800/20 border-emerald-500/30 hover:scale-105 transition-transform">
            <CardContent className="p-8">
              <div className="bg-gradient-to-br from-emerald-500 to-green-500 w-12 h-12 rounded-lg flex items-center justify-center mb-4 shadow-lg shadow-emerald-500/50">
                <TrendingUp className="w-6 h-6 text-white" />
              </div>
              <h2 className="text-2xl font-bold text-white mb-4 bg-gradient-to-r from-emerald-400 to-green-400 bg-clip-text text-transparent">Nuestra Visión</h2>
              <p className="text-slate-300 leading-relaxed">
                Convertirnos en el fondo de inversión líder en América Latina
                especializado en criptoactivos, reconocido por nuestro rendimiento
                excepcional, transparencia absoluta y compromiso inquebrantable con el
                éxito de nuestros inversionistas. Queremos ser el puente entre las
                finanzas tradicionales y el futuro descentralizado.
              </p>
            </CardContent>
          </Card>
        </div>

        {/* Values */}
        <div className="mb-16">
          <h2 className="text-3xl font-bold text-white text-center mb-8 bg-gradient-to-r from-orange-400 to-pink-400 bg-clip-text text-transparent">
            Nuestros Valores
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {values.map((value, index) => (
              <Card key={index} className="bg-slate-900/50 border-slate-800 hover:scale-105 transition-transform">
                <CardContent className="p-6">
                  <div className={`bg-gradient-to-br ${value.gradient} w-12 h-12 rounded-lg flex items-center justify-center mb-4 shadow-lg ${value.shadow}`}>
                    <value.icon className="w-6 h-6 text-white" />
                  </div>
                  <h3 className="text-lg font-semibold text-white mb-2">
                    {value.title}
                  </h3>
                  <p className="text-slate-400 text-sm leading-relaxed">
                    {value.description}
                  </p>
                </CardContent>
              </Card>
            ))}
          </div>
        </div>

        {/* Team */}
        <div className="mb-16">
          <h2 className="text-3xl font-bold text-white text-center mb-8 bg-gradient-to-r from-purple-400 to-fuchsia-400 bg-clip-text text-transparent">
            Nuestro Equipo
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {team.map((member, index) => (
              <Card key={index} className="bg-slate-900/50 border-slate-800 hover:scale-105 transition-transform">
                <CardContent className="p-6">
                  <div className="flex items-start gap-4">
                    <div className={`bg-gradient-to-br ${member.color} w-16 h-16 rounded-full flex items-center justify-center flex-shrink-0 shadow-lg`}>
                      <span className="text-2xl text-white font-bold">
                        {member.name.charAt(0)}
                      </span>
                    </div>
                    <div className="flex-1">
                      <h3 className="text-lg font-semibold text-white mb-1">
                        {member.name}
                      </h3>
                      <p className="text-cyan-400 text-sm mb-3">{member.role}</p>
                      <p className="text-slate-400 text-sm leading-relaxed">
                        {member.description}
                      </p>
                    </div>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        </div>

        {/* Security & Compliance */}
        <Card className="bg-slate-900/50 border-slate-800 mb-16">
          <CardContent className="p-8">
            <div className="flex items-start gap-6">
              <div className="bg-gradient-to-br from-emerald-500 to-green-600 p-4 rounded-lg flex-shrink-0 shadow-lg shadow-emerald-500/50">
                <Lock className="w-8 h-8 text-white" />
              </div>
              <div className="flex-1">
                <h2 className="text-2xl font-bold text-white mb-4 bg-gradient-to-r from-emerald-400 to-green-400 bg-clip-text text-transparent">
                  Seguridad y Cumplimiento
                </h2>
                <p className="text-slate-300 mb-4 leading-relaxed">
                  En MU Capital, la seguridad de los activos de nuestros inversionistas
                  es nuestra máxima prioridad. Implementamos múltiples capas de
                  protección y cumplimos con todas las regulaciones aplicables.
                </p>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                  <div className="flex items-start gap-3">
                    <div className="w-2 h-2 bg-emerald-500 rounded-full mt-2 shadow-lg shadow-emerald-500/50"></div>
                    <div>
                      <p className="text-white font-medium mb-1">
                        Custodia Institucional
                      </p>
                      <p className="text-slate-400 text-sm">
                        Almacenamiento en frío con custodios regulados de primer nivel
                      </p>
                    </div>
                  </div>
                  <div className="flex items-start gap-3">
                    <div className="w-2 h-2 bg-cyan-500 rounded-full mt-2 shadow-lg shadow-cyan-500/50"></div>
                    <div>
                      <p className="text-white font-medium mb-1">Auditorías Regulares</p>
                      <p className="text-slate-400 text-sm">
                        Auditorías trimestrales por firmas independientes
                      </p>
                    </div>
                  </div>
                  <div className="flex items-start gap-3">
                    <div className="w-2 h-2 bg-orange-500 rounded-full mt-2 shadow-lg shadow-orange-500/50"></div>
                    <div>
                      <p className="text-white font-medium mb-1">
                        Seguro de Activos
                      </p>
                      <p className="text-slate-400 text-sm">
                        Cobertura de seguro completa para todos los activos digitales
                      </p>
                    </div>
                  </div>
                  <div className="flex items-start gap-3">
                    <div className="w-2 h-2 bg-purple-500 rounded-full mt-2 shadow-lg shadow-purple-500/50"></div>
                    <div>
                      <p className="text-white font-medium mb-1">
                        Cumplimiento Normativo
                      </p>
                      <p className="text-slate-400 text-sm">
                        Cumplimiento total con KYC, AML y regulaciones locales
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Call to Action */}
        <Card className="bg-gradient-to-br from-cyan-600 via-emerald-600 to-cyan-600 border-0 shadow-2xl shadow-cyan-500/50">
          <CardContent className="p-8 md:p-12 text-center">
            <h2 className="text-3xl font-bold text-white mb-4">
              ¿Listo para Comenzar tu Viaje de Inversión?
            </h2>
            <p className="text-lg text-cyan-100 mb-8 max-w-2xl mx-auto">
              Únete a MU Capital y accede a gestión profesional de criptoactivos con
              transparencia total y rendimientos superiores.
            </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <a
                href="mailto:info@mucapital.com"
                className="inline-flex items-center justify-center px-8 py-3 bg-white text-emerald-600 font-medium rounded-lg hover:bg-slate-100 transition-colors shadow-lg"
              >
                Contactar a un Asesor
              </a>
              <a
                href="/dashboard"
                className="inline-flex items-center justify-center px-8 py-3 bg-transparent border-2 border-white text-white font-medium rounded-lg hover:bg-white/10 transition-colors"
              >
                Ver Dashboard
              </a>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}