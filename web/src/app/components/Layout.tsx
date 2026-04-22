import { Link, Outlet, useLocation } from "react-router";
import { TrendingUp, Menu, X, LayoutDashboard, Briefcase, Shield, ArrowRightLeft, Bell, Settings, LogOut, User, Users } from "lucide-react";
import { Button } from "./ui/button";
import { useState } from "react";
import { Badge } from "./ui/badge";

export function Layout() {
  const location = useLocation();
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const isAuthenticated = typeof window !== 'undefined' && localStorage.getItem("isAuthenticated") === "true";
  const isAppRoute = location.pathname.startsWith("/app");

  const publicNavigation = [
    { name: "Inicio", path: "/" },
    { name: "Nosotros", path: "/about" },
  ];

  const appNavigation = [
    { name: "Dashboard", path: "/app/dashboard", icon: LayoutDashboard },
    { name: "Portafolios", path: "/app/portfolios", icon: Briefcase },
    { name: "Análisis de Riesgo", path: "/app/risk-analysis", icon: Shield },
    { name: "Transacciones", path: "/app/transactions", icon: ArrowRightLeft },
    { name: "Alertas", path: "/app/alerts", icon: Bell, badge: 3 },
    { name: "Administración", path: "/app/admin", icon: Users },
  ];

  const navigation = isAppRoute ? appNavigation : publicNavigation;

  const isActive = (path: string) => {
    if (path === "/" && !isAppRoute) {
      return location.pathname === "/";
    }
    return location.pathname.startsWith(path);
  };

  const handleLogout = () => {
    localStorage.removeItem("isAuthenticated");
    window.location.href = "/";
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-950 via-purple-950 to-slate-950">
      {/* Header */}
      <header className="border-b border-cyan-500/20 bg-slate-950/80 backdrop-blur-sm sticky top-0 z-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            {/* Logo */}
            <Link to={isAppRoute ? "/app/dashboard" : "/"} className="flex items-center gap-2">
              <div className="bg-gradient-to-br from-cyan-400 to-emerald-500 p-2 rounded-lg shadow-lg shadow-cyan-500/50">
                <TrendingUp className="w-6 h-6 text-white" />
              </div>
              <span className="text-xl font-bold bg-gradient-to-r from-cyan-400 to-emerald-400 bg-clip-text text-transparent">MU Capital</span>
            </Link>

            {/* Desktop Navigation */}
            <nav className="hidden md:flex items-center gap-6">
              {navigation.map((item) => (
                <Link
                  key={item.path}
                  to={item.path}
                  className={`text-sm font-medium transition-colors flex items-center gap-2 ${
                    isActive(item.path)
                      ? "text-cyan-400"
                      : "text-slate-300 hover:text-cyan-300"
                  }`}
                >
                  {'icon' in item && <item.icon className="w-4 h-4" />}
                  {item.name}
                  {'badge' in item && item.badge && (
                    <Badge className="bg-orange-500 text-white text-xs">{item.badge}</Badge>
                  )}
                </Link>
              ))}
            </nav>

            {/* Right Side Actions */}
            <div className="hidden md:flex items-center gap-4">
              {isAppRoute && isAuthenticated ? (
                <>
                  <Link to="/app/profile">
                    <Button variant="ghost" size="sm" className="text-slate-300 hover:text-white">
                      <User className="w-4 h-4 mr-2" />
                      Perfil
                    </Button>
                  </Link>
                  <Button
                    onClick={handleLogout}
                    variant="ghost"
                    size="sm"
                    className="text-slate-300 hover:text-red-400"
                  >
                    <LogOut className="w-4 h-4 mr-2" />
                    Salir
                  </Button>
                </>
              ) : (
                <>
                  <Link to="/auth/login">
                    <Button variant="ghost" className="text-slate-300 hover:text-white">
                      Iniciar Sesión
                    </Button>
                  </Link>
                  <Link to="/auth/register">
                    <Button className="bg-gradient-to-r from-cyan-500 to-emerald-500 hover:from-cyan-600 hover:to-emerald-600 text-white shadow-lg shadow-cyan-500/30">
                      Comenzar
                    </Button>
                  </Link>
                </>
              )}
            </div>

            {/* Mobile menu button */}
            <button
              onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
              className="md:hidden p-2 text-slate-300 hover:text-cyan-400"
            >
              {mobileMenuOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
            </button>
          </div>
        </div>

        {/* Mobile Navigation */}
        {mobileMenuOpen && (
          <div className="md:hidden border-t border-cyan-500/20 bg-slate-950">
            <div className="px-4 py-4 space-y-3">
              {navigation.map((item) => (
                <Link
                  key={item.path}
                  to={item.path}
                  onClick={() => setMobileMenuOpen(false)}
                  className={`block px-3 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2 ${
                    isActive(item.path)
                      ? "bg-cyan-500/10 text-cyan-400"
                      : "text-slate-300 hover:bg-slate-800 hover:text-cyan-300"
                  }`}
                >
                  {'icon' in item && <item.icon className="w-4 h-4" />}
                  {item.name}
                  {'badge' in item && item.badge && (
                    <Badge className="bg-orange-500 text-white text-xs ml-auto">{item.badge}</Badge>
                  )}
                </Link>
              ))}
              {isAppRoute && isAuthenticated ? (
                <>
                  <Link
                    to="/app/profile"
                    onClick={() => setMobileMenuOpen(false)}
                    className="block px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white flex items-center gap-2"
                  >
                    <User className="w-4 h-4" />
                    Perfil
                  </Link>
                  <button
                    onClick={() => {
                      handleLogout();
                      setMobileMenuOpen(false);
                    }}
                    className="w-full text-left px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-red-400 flex items-center gap-2"
                  >
                    <LogOut className="w-4 h-4" />
                    Salir
                  </button>
                </>
              ) : (
                <>
                  <Link to="/auth/login" onClick={() => setMobileMenuOpen(false)}>
                    <Button variant="ghost" className="w-full text-slate-300 hover:text-white">
                      Iniciar Sesión
                    </Button>
                  </Link>
                  <Link to="/auth/register" onClick={() => setMobileMenuOpen(false)}>
                    <Button className="w-full bg-gradient-to-r from-cyan-500 to-emerald-500 hover:from-cyan-600 hover:to-emerald-600 text-white shadow-lg shadow-cyan-500/30">
                      Comenzar
                    </Button>
                  </Link>
                </>
              )}
            </div>
          </div>
        )}
      </header>

      {/* Main Content */}
      <main>
        <Outlet />
      </main>

      {/* Footer - Only show on public pages */}
      {!isAppRoute && (
        <footer className="border-t border-cyan-500/20 bg-slate-950/50 mt-20">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
              <div className="col-span-1 md:col-span-2">
                <div className="flex items-center gap-2 mb-4">
                  <div className="bg-gradient-to-br from-cyan-400 to-emerald-500 p-2 rounded-lg shadow-lg shadow-cyan-500/50">
                    <TrendingUp className="w-5 h-5 text-white" />
                  </div>
                  <span className="text-lg font-bold bg-gradient-to-r from-cyan-400 to-emerald-400 bg-clip-text text-transparent">MU Capital</span>
                </div>
                <p className="text-slate-400 text-sm max-w-md">
                  Plataforma inteligente de gestión de inversiones con análisis avanzado
                  y herramientas profesionales para maximizar tus rendimientos.
                </p>
              </div>
              <div>
                <h3 className="text-white font-semibold mb-4">Enlaces</h3>
                <ul className="space-y-2">
                  {publicNavigation.map((item) => (
                    <li key={item.path}>
                      <Link
                        to={item.path}
                        className="text-slate-400 hover:text-cyan-400 text-sm transition-colors"
                      >
                        {item.name}
                      </Link>
                    </li>
                  ))}
                </ul>
              </div>
              <div>
                <h3 className="text-white font-semibold mb-4">Contacto</h3>
                <ul className="space-y-2 text-slate-400 text-sm">
                  <li>info@mucapital.com</li>
                  <li>+1 (555) 123-4567</li>
                </ul>
              </div>
            </div>
            <div className="border-t border-cyan-500/20 mt-8 pt-8 text-center text-slate-400 text-sm">
              <p>© 2026 MU Capital. Todos los derechos reservados.</p>
            </div>
          </div>
        </footer>
      )}
    </div>
  );
}