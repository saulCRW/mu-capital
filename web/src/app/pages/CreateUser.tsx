import { useState } from "react";
import { useNavigate } from "react-router";
import { Card, CardContent, CardHeader, CardTitle } from "../components/ui/card";
import { Button } from "../components/ui/button";
import { Input } from "../components/ui/input";
import { Label } from "../components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../components/ui/select";
import { Switch } from "../components/ui/switch";
import { ArrowLeft, UserPlus, Shield, BarChart3, Users, Key, RefreshCw } from "lucide-react";

export function CreateUser() {
  const navigate = useNavigate();
  const [formData, setFormData] = useState({
    name: "",
    email: "",
    role: "",
    status: true,
    portfolios: "",
    password: "",
  });

  const [passwordGenerated, setPasswordGenerated] = useState(false);

  const generatePassword = () => {
    const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*";
    let password = "";
    for (let i = 0; i < 12; i++) {
      password += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    setFormData({ ...formData, password });
    setPasswordGenerated(true);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    navigate("/app/user-created", {
      state: {
        user: formData,
      },
    });
  };

  const handleCancel = () => {
    navigate("/app/admin");
  };

  const getRoleConfig = (role: string) => {
    switch (role) {
      case "admin":
        return {
          label: "Administrador",
          color: "from-purple-500 to-fuchsia-500",
          icon: Shield,
          iconColor: "text-purple-400",
          bgColor: "bg-purple-500/20",
          description: "Acceso total al sistema",
        };
      case "analyst":
        return {
          label: "Analista",
          color: "from-cyan-500 to-blue-500",
          icon: BarChart3,
          iconColor: "text-cyan-400",
          bgColor: "bg-cyan-500/20",
          description: "Análisis y reportes",
        };
      case "investor":
        return {
          label: "Inversionista",
          color: "from-emerald-500 to-green-500",
          icon: Users,
          iconColor: "text-emerald-400",
          bgColor: "bg-emerald-500/20",
          description: "Gestión de portafolios",
        };
      default:
        return {
          label: "",
          color: "",
          icon: UserPlus,
          iconColor: "",
          bgColor: "",
          description: "",
        };
    }
  };

  return (
    <div className="min-h-screen py-8">
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Back Button */}
        <button
          onClick={handleCancel}
          className="inline-flex items-center text-cyan-400 hover:text-cyan-300 mb-6 transition-colors"
        >
          <ArrowLeft className="w-4 h-4 mr-2" />
          Volver a Gestión de Usuarios
        </button>

        {/* Header */}
        <div className="mb-8">
          <div className="flex items-center gap-3 mb-4">
            <div className="bg-gradient-to-br from-cyan-400 to-emerald-500 p-3 rounded-xl shadow-lg shadow-cyan-500/50">
              <UserPlus className="w-6 h-6 text-white" />
            </div>
            <div>
              <h1 className="text-3xl font-bold text-white bg-gradient-to-r from-cyan-400 to-emerald-400 bg-clip-text text-transparent">
                Nuevo Usuario
              </h1>
              <p className="text-slate-400 mt-1">
                Agrega un nuevo usuario a la plataforma MU Capital
              </p>
            </div>
          </div>
        </div>

        {/* Form */}
        <Card className="bg-slate-900/50 border-slate-800 backdrop-blur-sm">
          <CardHeader>
            <CardTitle className="text-white">Información del Usuario</CardTitle>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-6">
              {/* Full Name */}
              <div className="space-y-2">
                <Label htmlFor="name" className="text-slate-300 text-base">
                  Nombre Completo *
                </Label>
                <Input
                  id="name"
                  type="text"
                  placeholder="Ej: María García López"
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  className="bg-slate-800/50 border-slate-700 text-white placeholder:text-slate-500 h-12 text-base"
                  required
                />
              </div>

              {/* Email */}
              <div className="space-y-2">
                <Label htmlFor="email" className="text-slate-300 text-base">
                  Correo Electrónico *
                </Label>
                <div className="relative">
                  <Input
                    id="email"
                    type="email"
                    placeholder="usuario"
                    value={formData.email}
                    onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                    className="bg-slate-800/50 border-slate-700 text-white placeholder:text-slate-500 h-12 text-base pr-40"
                    required
                  />
                  <span className="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 text-base">
                    @mucapital.com
                  </span>
                </div>
                <p className="text-xs text-slate-500">
                  El correo debe usar el dominio @mucapital.com
                </p>
              </div>

              {/* Role Selection */}
              <div className="space-y-2">
                <Label htmlFor="role" className="text-slate-300 text-base">
                  Rol *
                </Label>
                <Select
                  value={formData.role}
                  onValueChange={(value) => setFormData({ ...formData, role: value })}
                  required
                >
                  <SelectTrigger className="bg-slate-800/50 border-slate-700 text-white h-12 text-base">
                    <SelectValue placeholder="Selecciona un rol" />
                  </SelectTrigger>
                  <SelectContent className="bg-slate-800 border-slate-700">
                    <SelectItem value="admin" className="text-base">
                      <div className="flex items-center gap-3 py-2">
                        <div className="bg-purple-500/20 p-2 rounded-lg">
                          <Shield className="w-4 h-4 text-purple-400" />
                        </div>
                        <div>
                          <div className="text-white font-medium">Administrador</div>
                          <div className="text-slate-400 text-xs">
                            Acceso total al sistema
                          </div>
                        </div>
                      </div>
                    </SelectItem>
                    <SelectItem value="analyst" className="text-base">
                      <div className="flex items-center gap-3 py-2">
                        <div className="bg-cyan-500/20 p-2 rounded-lg">
                          <BarChart3 className="w-4 h-4 text-cyan-400" />
                        </div>
                        <div>
                          <div className="text-white font-medium">Analista</div>
                          <div className="text-slate-400 text-xs">
                            Análisis y reportes
                          </div>
                        </div>
                      </div>
                    </SelectItem>
                    <SelectItem value="investor" className="text-base">
                      <div className="flex items-center gap-3 py-2">
                        <div className="bg-emerald-500/20 p-2 rounded-lg">
                          <Users className="w-4 h-4 text-emerald-400" />
                        </div>
                        <div>
                          <div className="text-white font-medium">Inversionista</div>
                          <div className="text-slate-400 text-xs">
                            Gestión de portafolios
                          </div>
                        </div>
                      </div>
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>

              {/* Status Toggle */}
              <div className="space-y-2">
                <div className="flex items-center justify-between p-4 bg-slate-800/30 rounded-lg border border-slate-700">
                  <div className="flex items-center gap-3">
                    <div
                      className={`${
                        formData.status ? "bg-emerald-500/20" : "bg-slate-500/20"
                      } p-2 rounded-lg transition-colors`}
                    >
                      <UserPlus
                        className={`w-5 h-5 ${
                          formData.status ? "text-emerald-400" : "text-slate-400"
                        }`}
                      />
                    </div>
                    <div>
                      <Label htmlFor="status" className="text-white font-medium cursor-pointer">
                        Estado del Usuario
                      </Label>
                      <p className="text-slate-400 text-sm">
                        {formData.status ? "Usuario activo" : "Usuario inactivo"}
                      </p>
                    </div>
                  </div>
                  <Switch
                    id="status"
                    checked={formData.status}
                    onCheckedChange={(checked) =>
                      setFormData({ ...formData, status: checked })
                    }
                  />
                </div>
              </div>

              {/* Number of Portfolios */}
              <div className="space-y-2">
                <Label htmlFor="portfolios" className="text-slate-300 text-base">
                  Número de Portafolios Asignados *
                </Label>
                <Input
                  id="portfolios"
                  type="number"
                  placeholder="0"
                  value={formData.portfolios}
                  onChange={(e) => setFormData({ ...formData, portfolios: e.target.value })}
                  className="bg-slate-800/50 border-slate-700 text-white placeholder:text-slate-500 h-12 text-base"
                  min="0"
                  required
                />
                <p className="text-xs text-slate-500">
                  Cantidad de portafolios que el usuario podrá gestionar
                </p>
              </div>

              {/* Temporary Password */}
              <div className="space-y-2">
                <Label htmlFor="password" className="text-slate-300 text-base">
                  Contraseña Temporal *
                </Label>
                <div className="flex gap-2">
                  <Input
                    id="password"
                    type="text"
                    placeholder="Genera una contraseña automáticamente"
                    value={formData.password}
                    onChange={(e) => {
                      setFormData({ ...formData, password: e.target.value });
                      setPasswordGenerated(false);
                    }}
                    className="bg-slate-800/50 border-slate-700 text-white placeholder:text-slate-500 h-12 text-base flex-1"
                    required
                  />
                  <Button
                    type="button"
                    onClick={generatePassword}
                    variant="outline"
                    className="border-slate-700 text-slate-300 hover:bg-slate-800 h-12 px-6"
                  >
                    <RefreshCw className="w-4 h-4 mr-2" />
                    Generar
                  </Button>
                </div>
                {passwordGenerated && (
                  <div className="flex items-center gap-2 text-emerald-400 text-sm">
                    <Key className="w-4 h-4" />
                    <span>Contraseña generada automáticamente</span>
                  </div>
                )}
                <p className="text-xs text-slate-500">
                  El usuario deberá cambiar esta contraseña en su primer inicio de sesión
                </p>
              </div>

              {/* Role Info Box */}
              {formData.role && (
                <div
                  className={`border rounded-lg p-4 bg-${
                    getRoleConfig(formData.role).bgColor
                  }/10 border-${getRoleConfig(formData.role).iconColor.split("-")[1]}-500/30`}
                >
                  <div className="flex gap-3">
                    <div
                      className={`${getRoleConfig(formData.role).bgColor} p-2 rounded-lg h-fit`}
                    >
                      {(() => {
                        const Icon = getRoleConfig(formData.role).icon;
                        return (
                          <Icon
                            className={`w-5 h-5 ${getRoleConfig(formData.role).iconColor}`}
                          />
                        );
                      })()}
                    </div>
                    <div>
                      <h4 className="text-white font-medium mb-1">
                        Rol: {getRoleConfig(formData.role).label}
                      </h4>
                      <p className="text-slate-300 text-sm leading-relaxed">
                        {getRoleConfig(formData.role).description}. El usuario tendrá acceso a las
                        funcionalidades correspondientes a este rol.
                      </p>
                    </div>
                  </div>
                </div>
              )}

              {/* Action Buttons */}
              <div className="flex flex-col-reverse sm:flex-row gap-4 pt-4">
                <Button
                  type="button"
                  onClick={handleCancel}
                  variant="outline"
                  className="flex-1 sm:flex-none border-slate-700 text-slate-300 hover:bg-slate-800 h-12 text-base"
                >
                  Cancelar
                </Button>
                <Button
                  type="submit"
                  className="flex-1 bg-gradient-to-r from-cyan-500 to-emerald-500 hover:from-cyan-600 hover:to-emerald-600 text-white shadow-lg shadow-cyan-500/30 h-12 text-base font-semibold"
                >
                  <UserPlus className="w-5 h-5 mr-2" />
                  Crear Usuario
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>

        {/* Tips Section */}
        <div className="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
          <Card className="bg-slate-900/30 border-slate-800">
            <CardContent className="p-4">
              <div className="text-purple-400 font-semibold mb-2 flex items-center gap-2">
                <Shield className="w-4 h-4" />
                Administrador
              </div>
              <p className="text-slate-400 text-sm">
                Acceso completo a la plataforma, gestión de usuarios y configuración.
              </p>
            </CardContent>
          </Card>
          <Card className="bg-slate-900/30 border-slate-800">
            <CardContent className="p-4">
              <div className="text-cyan-400 font-semibold mb-2 flex items-center gap-2">
                <BarChart3 className="w-4 h-4" />
                Analista
              </div>
              <p className="text-slate-400 text-sm">
                Acceso a herramientas de análisis, reportes y visualización de datos.
              </p>
            </CardContent>
          </Card>
          <Card className="bg-slate-900/30 border-slate-800">
            <CardContent className="p-4">
              <div className="text-emerald-400 font-semibold mb-2 flex items-center gap-2">
                <Users className="w-4 h-4" />
                Inversionista
              </div>
              <p className="text-slate-400 text-sm">
                Gestión de portafolios personales y seguimiento de inversiones.
              </p>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}
