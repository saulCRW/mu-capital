import { ReactNode } from "react";
import { Outlet, Link, useLocation } from "react-router";
import { Home, Briefcase, ArrowRightLeft, Bell, User, LogOut, Eye } from "lucide-react";
import { Button } from "./ui/button";
import { Badge } from "./ui/badge";

export function InvestorLayout() {
  const location = useLocation();

  const navigation = [
    { name: "Dashboard", href: "/investor/dashboard", icon: Home },
    { name: "Mi Portafolio", href: "/investor/portfolio", icon: Briefcase },
    { name: "Movimientos", href: "/investor/transactions", icon: ArrowRightLeft },
    { name: "Alertas", href: "/investor/alerts", icon: Bell },
    { name: "Perfil", href: "/investor/profile", icon: User },
  ];

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-950 via-purple-950 to-slate-900">
      {/* Navbar */}
      <nav className="bg-slate-900/80 backdrop-blur-sm border-b border-slate-800 sticky top-0 z-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between h-16">
            {/* Logo */}
            <Link to="/investor/dashboard" className="flex items-center gap-2">
              <div className="bg-gradient-to-br from-cyan-400 to-emerald-500 p-2 rounded-lg shadow-lg shadow-cyan-500/50">
                <svg
                  className="w-6 h-6 text-white"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"
                  />
                </svg>
              </div>
              <span className="text-xl font-bold text-white">MU Capital</span>
            </Link>

            {/* Navigation */}
            <div className="hidden md:flex items-center gap-1">
              {navigation.map((item) => {
                const isActive = location.pathname === item.href;
                const Icon = item.icon;
                return (
                  <Link key={item.name} to={item.href}>
                    <Button
                      variant="ghost"
                      className={
                        isActive
                          ? "text-cyan-400 bg-cyan-500/10"
                          : "text-slate-400 hover:text-white hover:bg-slate-800"
                      }
                    >
                      <Icon className="w-4 h-4 mr-2" />
                      {item.name}
                    </Button>
                  </Link>
                );
              })}
            </div>

            {/* Right Section */}
            <div className="flex items-center gap-3">
              <Badge className="bg-cyan-500/20 text-cyan-400 border-cyan-500/50 hidden sm:inline-flex">
                <Eye className="w-3 h-3 mr-1" />
                Modo Inversionista
              </Badge>
              <Link to="/auth/login">
                <Button
                  variant="ghost"
                  className="text-slate-400 hover:text-white hover:bg-slate-800"
                >
                  <LogOut className="w-4 h-4 mr-2" />
                  Cerrar Sesión
                </Button>
              </Link>
            </div>
          </div>
        </div>

        {/* Mobile Navigation */}
        <div className="md:hidden border-t border-slate-800">
          <div className="flex overflow-x-auto">
            {navigation.map((item) => {
              const isActive = location.pathname === item.href;
              const Icon = item.icon;
              return (
                <Link
                  key={item.name}
                  to={item.href}
                  className={`flex-1 flex flex-col items-center gap-1 py-3 text-xs ${
                    isActive ? "text-cyan-400" : "text-slate-400"
                  }`}
                >
                  <Icon className="w-5 h-5" />
                  <span>{item.name}</span>
                </Link>
              );
            })}
          </div>
        </div>
      </nav>

      {/* Main Content */}
      <main>
        <Outlet />
      </main>
    </div>
  );
}
