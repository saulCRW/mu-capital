import { useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "../components/ui/card";
import { Button } from "../components/ui/button";
import { Input } from "../components/ui/input";
import { Label } from "../components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../components/ui/select";
import { User, Mail, Shield, Calendar, Lock, Key, Save, MessageSquare } from "lucide-react";
import { Badge } from "../components/ui/badge";

export function InvestorProfile() {
  const [preferences, setPreferences] = useState({
    language: "es",
    currency: "USD",
  });

  const userInfo = {
    name: "Carlos Mendoza",
    email: "carlos.mendoza@mucapital.com",
    role: "Inversionista",
    registrationDate: "15 de Enero, 2024",
    avatar: "CM",
  };

  const adminInfo = {
    name: "María García",
    email: "maria.garcia@mucapital.com",
    role: "Administrador",
  };

  const [showContactModal, setShowContactModal] = useState(false);

  return (
    <div className="min-h-screen py-8">
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Header */}
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-white bg-gradient-to-r from-cyan-400 to-emerald-400 bg-clip-text text-transparent mb-2">
            Mi Perfil
          </h1>
          <p className="text-slate-400">Información personal y configuración de cuenta</p>
        </div>

        {/* Profile Card */}
        <Card className="bg-slate-900/50 border-slate-800 mb-6">
          <CardContent className="p-8">
            <div className="flex flex-col md:flex-row items-center md:items-start gap-6">
              {/* Avatar */}
              <div className="bg-gradient-to-br from-cyan-500 to-emerald-500 w-24 h-24 rounded-full flex items-center justify-center text-white text-3xl font-bold shadow-lg shadow-cyan-500/30">
                {userInfo.avatar}
              </div>

              {/* User Info */}
              <div className="flex-1 text-center md:text-left">
                <h2 className="text-2xl font-bold text-white mb-2">{userInfo.name}</h2>
                <div className="space-y-2">
                  <div className="flex items-center gap-2 justify-center md:justify-start">
                    <Mail className="w-4 h-4 text-slate-400" />
                    <span className="text-slate-300">{userInfo.email}</span>
                  </div>
                  <div className="flex items-center gap-2 justify-center md:justify-start">
                    <Shield className="w-4 h-4 text-cyan-400" />
                    <Badge className="bg-cyan-500/20 text-cyan-400 border-cyan-500/50">
                      {userInfo.role}
                    </Badge>
                  </div>
                  <div className="flex items-center gap-2 justify-center md:justify-start">
                    <Calendar className="w-4 h-4 text-slate-400" />
                    <span className="text-slate-400 text-sm">
                      Miembro desde {userInfo.registrationDate}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Personal Information - Read Only */}
        <Card className="bg-slate-900/50 border-slate-800 mb-6">
          <CardHeader>
            <div className="flex items-center justify-between">
              <CardTitle className="text-white">Información Personal</CardTitle>
              <Lock className="w-4 h-4 text-slate-600" />
            </div>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label className="text-slate-400 text-sm">Nombre Completo</Label>
                <div className="bg-slate-800/30 border border-slate-700 rounded-lg px-4 py-3 text-white">
                  {userInfo.name}
                </div>
              </div>
              <div className="space-y-2">
                <Label className="text-slate-400 text-sm">Correo Electrónico</Label>
                <div className="bg-slate-800/30 border border-slate-700 rounded-lg px-4 py-3 text-white">
                  {userInfo.email}
                </div>
              </div>
            </div>
            <p className="text-xs text-slate-500 flex items-center gap-2">
              <Lock className="w-3 h-3" />
              Solo lectura – Contacta a tu administrador para modificar
            </p>
          </CardContent>
        </Card>

        {/* My Administrator */}
        <Card className="bg-slate-900/50 border-slate-800 mb-6">
          <CardHeader>
            <CardTitle className="text-white">Mi Administrador Asignado</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 p-4 bg-slate-800/50 rounded-lg border border-slate-700">
              <div className="flex items-center gap-4">
                <div className="bg-gradient-to-br from-purple-500 to-fuchsia-500 w-12 h-12 rounded-full flex items-center justify-center text-white text-lg font-bold shadow-lg shadow-purple-500/30">
                  MG
                </div>
                <div>
                  <h3 className="text-white font-semibold">{adminInfo.name}</h3>
                  <p className="text-slate-400 text-sm">{adminInfo.email}</p>
                  <Badge className="bg-purple-500/20 text-purple-400 border-purple-500/50 mt-1">
                    {adminInfo.role}
                  </Badge>
                </div>
              </div>
              <Button
                onClick={() => setShowContactModal(true)}
                className="bg-gradient-to-r from-cyan-500 to-emerald-500 hover:from-cyan-600 hover:to-emerald-600 text-white"
              >
                <MessageSquare className="w-4 h-4 mr-2" />
                Contactar
              </Button>
            </div>
          </CardContent>
        </Card>

        {/* Security */}
        <Card className="bg-slate-900/50 border-slate-800 mb-6">
          <CardHeader>
            <CardTitle className="text-white">Seguridad</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              <div className="flex items-center justify-between p-4 bg-slate-800/50 rounded-lg border border-slate-700">
                <div className="flex items-center gap-3">
                  <div className="bg-orange-500/20 p-2 rounded-lg">
                    <Key className="w-5 h-5 text-orange-400" />
                  </div>
                  <div>
                    <h4 className="text-white font-medium">Contraseña</h4>
                    <p className="text-slate-400 text-sm">Última actualización hace 30 días</p>
                  </div>
                </div>
                <Button className="bg-gradient-to-r from-orange-500 to-pink-500 hover:from-orange-600 hover:to-pink-600 text-white">
                  Cambiar Contraseña
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Preferences - Editable */}
        <Card className="bg-slate-900/50 border-slate-800 mb-6">
          <CardHeader>
            <CardTitle className="text-white">Preferencias</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="language" className="text-slate-300">
                  Idioma
                </Label>
                <Select
                  value={preferences.language}
                  onValueChange={(value) =>
                    setPreferences({ ...preferences, language: value })
                  }
                >
                  <SelectTrigger className="bg-slate-800/50 border-slate-700 text-white">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent className="bg-slate-800 border-slate-700">
                    <SelectItem value="es">Español</SelectItem>
                    <SelectItem value="en">English</SelectItem>
                    <SelectItem value="pt">Português</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-2">
                <Label htmlFor="currency" className="text-slate-300">
                  Moneda de Visualización
                </Label>
                <Select
                  value={preferences.currency}
                  onValueChange={(value) =>
                    setPreferences({ ...preferences, currency: value })
                  }
                >
                  <SelectTrigger className="bg-slate-800/50 border-slate-700 text-white">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent className="bg-slate-800 border-slate-700">
                    <SelectItem value="USD">🇺🇸 USD - Dólar</SelectItem>
                    <SelectItem value="MXN">🇲🇽 MXN - Peso Mexicano</SelectItem>
                    <SelectItem value="EUR">🇪🇺 EUR - Euro</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <Button className="w-full bg-gradient-to-r from-cyan-500 to-emerald-500 hover:from-cyan-600 hover:to-emerald-600 text-white">
              <Save className="w-4 h-4 mr-2" />
              Guardar Preferencias
            </Button>
          </CardContent>
        </Card>
      </div>

      {/* Contact Modal */}
      {showContactModal && (
        <div className="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
          <Card className="bg-slate-900 border-slate-800 max-w-md w-full">
            <CardHeader>
              <CardTitle className="text-white">Contactar Administrador</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="bg-slate-800/50 border border-slate-700 rounded-lg p-4">
                <div className="flex items-center gap-3 mb-4">
                  <div className="bg-gradient-to-br from-purple-500 to-fuchsia-500 w-12 h-12 rounded-full flex items-center justify-center text-white text-lg font-bold">
                    MG
                  </div>
                  <div>
                    <h3 className="text-white font-semibold">{adminInfo.name}</h3>
                    <p className="text-slate-400 text-sm">{adminInfo.email}</p>
                  </div>
                </div>
                <div className="space-y-2">
                  <Label className="text-slate-300">Enviar correo a:</Label>
                  <Input
                    type="email"
                    value={adminInfo.email}
                    readOnly
                    className="bg-slate-800 border-slate-700 text-white"
                  />
                </div>
              </div>
              <div className="flex gap-3">
                <Button
                  onClick={() => setShowContactModal(false)}
                  variant="outline"
                  className="flex-1 border-slate-700 text-slate-300 hover:bg-slate-800"
                >
                  Cancelar
                </Button>
                <Button
                  onClick={() => {
                    window.location.href = `mailto:${adminInfo.email}`;
                    setShowContactModal(false);
                  }}
                  className="flex-1 bg-gradient-to-r from-cyan-500 to-emerald-500 hover:from-cyan-600 hover:to-emerald-600 text-white"
                >
                  Abrir Email
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>
      )}
    </div>
  );
}
