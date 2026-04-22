import { useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "../components/ui/card";
import { Button } from "../components/ui/button";
import { Input } from "../components/ui/input";
import { Label } from "../components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../components/ui/select";
import { User, Mail, Shield, Bell, Lock, Save } from "lucide-react";
import { Switch } from "../components/ui/switch";
import { Separator } from "../components/ui/separator";

export function Profile() {
  const [formData, setFormData] = useState({
    name: "Juan Pérez",
    email: "juan.perez@mucapital.com",
    role: "Inversionista",
    notifications: true,
    twoFactor: true,
  });

  return (
    <div className="min-h-screen py-8">
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-white mb-2 bg-gradient-to-r from-cyan-400 to-emerald-400 bg-clip-text text-transparent">
            Mi Perfil
          </h1>
          <p className="text-slate-400">Gestiona tu información personal y preferencias</p>
        </div>

        {/* Profile Picture */}
        <Card className="bg-slate-900/50 border-slate-800 mb-6">
          <CardContent className="p-6">
            <div className="flex items-center gap-6">
              <div className="bg-gradient-to-br from-cyan-500 to-emerald-500 w-24 h-24 rounded-full flex items-center justify-center shadow-lg shadow-cyan-500/30">
                <User className="w-12 h-12 text-white" />
              </div>
              <div>
                <h3 className="text-lg font-semibold text-white mb-2">{formData.name}</h3>
                <p className="text-slate-400 mb-3">{formData.email}</p>
                <Button variant="outline" className="border-slate-700 text-slate-300 hover:bg-slate-800">
                  Cambiar Foto
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Personal Information */}
        <Card className="bg-slate-900/50 border-slate-800 mb-6">
          <CardHeader>
            <CardTitle className="text-white flex items-center gap-2">
              <User className="w-5 h-5" />
              Información Personal
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="name" className="text-slate-300">Nombre Completo</Label>
                <Input
                  id="name"
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  className="bg-slate-800/50 border-slate-700 text-white"
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="email" className="text-slate-300">Correo Electrónico</Label>
                <Input
                  id="email"
                  type="email"
                  value={formData.email}
                  onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                  className="bg-slate-800/50 border-slate-700 text-white"
                />
              </div>
            </div>
            <div className="space-y-2">
              <Label htmlFor="role" className="text-slate-300">Rol</Label>
              <Select value={formData.role} onValueChange={(value) => setFormData({ ...formData, role: value })}>
                <SelectTrigger className="bg-slate-800/50 border-slate-700 text-white">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent className="bg-slate-800 border-slate-700">
                  <SelectItem value="Inversionista">Inversionista</SelectItem>
                  <SelectItem value="Analista">Analista</SelectItem>
                  <SelectItem value="Administrador">Administrador</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </CardContent>
        </Card>

        {/* Security */}
        <Card className="bg-slate-900/50 border-slate-800 mb-6">
          <CardHeader>
            <CardTitle className="text-white flex items-center gap-2">
              <Shield className="w-5 h-5" />
              Seguridad
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-6">
            <div className="flex items-center justify-between">
              <div>
                <h4 className="text-white font-medium mb-1">Autenticación de Dos Factores</h4>
                <p className="text-slate-400 text-sm">Agrega una capa extra de seguridad</p>
              </div>
              <Switch checked={formData.twoFactor} onCheckedChange={(checked) => setFormData({ ...formData, twoFactor: checked })} />
            </div>
            <Separator className="bg-slate-700" />
            <Button variant="outline" className="border-slate-700 text-slate-300 hover:bg-slate-800">
              <Lock className="w-4 h-4 mr-2" />
              Cambiar Contraseña
            </Button>
          </CardContent>
        </Card>

        {/* Notifications */}
        <Card className="bg-slate-900/50 border-slate-800 mb-6">
          <CardHeader>
            <CardTitle className="text-white flex items-center gap-2">
              <Bell className="w-5 h-5" />
              Notificaciones
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="flex items-center justify-between">
              <div>
                <h4 className="text-white font-medium mb-1">Notificaciones por Email</h4>
                <p className="text-slate-400 text-sm">Recibe alertas y actualizaciones</p>
              </div>
              <Switch checked={formData.notifications} onCheckedChange={(checked) => setFormData({ ...formData, notifications: checked })} />
            </div>
          </CardContent>
        </Card>

        {/* Save Button */}
        <Button className="w-full bg-gradient-to-r from-cyan-500 to-emerald-500 hover:from-cyan-600 hover:to-emerald-600 text-white shadow-lg shadow-cyan-500/30">
          <Save className="w-4 h-4 mr-2" />
          Guardar Cambios
        </Button>
      </div>
    </div>
  );
}
