import { useState } from "react";
import { Link, useNavigate } from "react-router";
import { Card, CardContent, CardHeader, CardTitle } from "../components/ui/card";
import { Input } from "../components/ui/input";
import { Label } from "../components/ui/label";
import { Button } from "../components/ui/button";
import { Checkbox } from "../components/ui/checkbox";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../components/ui/select";
import { UserPlus, Eye, EyeOff, AlertCircle } from "lucide-react";

export function Register() {
  const navigate = useNavigate();
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [formData, setFormData] = useState({
    name: "",
    email: "",
    password: "",
    confirmPassword: "",
    role: "",
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    // Validar que las contraseñas coincidan
    if (formData.password !== formData.confirmPassword) {
      alert("Las contraseñas no coinciden");
      return;
    }
    
    // Obtener usuarios existentes del localStorage
    const existingUsersStr = localStorage.getItem("mucapital_users");
    const existingUsers = existingUsersStr ? JSON.parse(existingUsersStr) : [];
    
    // Verificar si el email ya está registrado
    const emailExists = existingUsers.some((user: any) => user.email === formData.email);
    if (emailExists) {
      alert("Este correo electrónico ya está registrado");
      return;
    }
    
    // Crear nuevo usuario
    const newUser = {
      name: formData.name,
      email: formData.email,
      password: formData.password, // En producción, esto debería estar encriptado
      role: formData.role,
      createdAt: new Date().toISOString(),
    };
    
    // Agregar el nuevo usuario al array
    existingUsers.push(newUser);
    
    // Guardar en localStorage
    localStorage.setItem("mucapital_users", JSON.stringify(existingUsers));
    
    // Mostrar mensaje de éxito
    alert("¡Cuenta creada exitosamente! Ahora puedes iniciar sesión.");
    
    // Redirigir al login
    navigate("/auth/login");
  };

  return (
    <Card className="bg-slate-900/50 border-slate-800 backdrop-blur-sm">
      <CardHeader>
        <div className="flex items-center justify-center mb-4">
          <div className="bg-gradient-to-br from-emerald-500 to-green-500 p-3 rounded-xl shadow-lg shadow-emerald-500/50">
            <UserPlus className="w-8 h-8 text-white" />
          </div>
        </div>
        <CardTitle className="text-2xl text-center text-white mb-2">
          Crear Cuenta
        </CardTitle>
        <p className="text-center text-slate-400 text-sm">
          Únete a MU Capital y empieza a gestionar tus inversiones
        </p>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="name" className="text-slate-300">
              Nombre Completo
            </Label>
            <Input
              id="name"
              type="text"
              placeholder="Juan Pérez"
              value={formData.name}
              onChange={(e) => setFormData({ ...formData, name: e.target.value })}
              className="bg-slate-800/50 border-slate-700 text-white"
              required
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="email" className="text-slate-300">
              Correo Electrónico
            </Label>
            <Input
              id="email"
              type="email"
              placeholder="tu@email.com"
              value={formData.email}
              onChange={(e) => setFormData({ ...formData, email: e.target.value })}
              className="bg-slate-800/50 border-slate-700 text-white"
              required
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="role" className="text-slate-300">
              Rol
            </Label>
            <Select
              value={formData.role}
              onValueChange={(value) => setFormData({ ...formData, role: value })}
              required
            >
              <SelectTrigger className="bg-slate-800/50 border-slate-700 text-white">
                <SelectValue placeholder="Selecciona tu rol" />
              </SelectTrigger>
              <SelectContent className="bg-slate-800 border-slate-700">
                <SelectItem value="admin">Administrador</SelectItem>
                <SelectItem value="investor">Inversionista</SelectItem>
              </SelectContent>
            </Select>
          </div>

          {formData.role === "investor" && (
            <div className="bg-cyan-500/10 border border-cyan-500/30 rounded-lg p-3">
              <div className="flex gap-2">
                <AlertCircle className="w-4 h-4 text-cyan-400 mt-0.5 flex-shrink-0" />
                <p className="text-sm text-cyan-300">
                  Si eres Inversionista, tu administrador asignado gestionará tu portafolio
                </p>
              </div>
            </div>
          )}

          <div className="space-y-2">
            <Label htmlFor="password" className="text-slate-300">
              Contraseña
            </Label>
            <div className="relative">
              <Input
                id="password"
                type={showPassword ? "text" : "password"}
                placeholder="••••••••"
                value={formData.password}
                onChange={(e) =>
                  setFormData({ ...formData, password: e.target.value })
                }
                className="bg-slate-800/50 border-slate-700 text-white pr-10"
                required
              />
              <button
                type="button"
                onClick={() => setShowPassword(!showPassword)}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white"
              >
                {showPassword ? (
                  <EyeOff className="w-4 h-4" />
                ) : (
                  <Eye className="w-4 h-4" />
                )}
              </button>
            </div>
          </div>

          <div className="space-y-2">
            <Label htmlFor="confirmPassword" className="text-slate-300">
              Confirmar Contraseña
            </Label>
            <div className="relative">
              <Input
                id="confirmPassword"
                type={showConfirmPassword ? "text" : "password"}
                placeholder="••••••••"
                value={formData.confirmPassword}
                onChange={(e) =>
                  setFormData({ ...formData, confirmPassword: e.target.value })
                }
                className="bg-slate-800/50 border-slate-700 text-white pr-10"
                required
              />
              <button
                type="button"
                onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white"
              >
                {showConfirmPassword ? (
                  <EyeOff className="w-4 h-4" />
                ) : (
                  <Eye className="w-4 h-4" />
                )}
              </button>
            </div>
          </div>

          <div className="flex items-start gap-2">
            <Checkbox id="terms" required />
            <label htmlFor="terms" className="text-sm text-slate-300 cursor-pointer">
              Acepto los{" "}
              <span className="text-cyan-400 hover:text-cyan-300">
                términos y condiciones
              </span>{" "}
              y la{" "}
              <span className="text-cyan-400 hover:text-cyan-300">
                política de privacidad
              </span>
            </label>
          </div>

          <Button
            type="submit"
            className="w-full bg-gradient-to-r from-emerald-500 to-green-500 hover:from-emerald-600 hover:to-green-600 text-white shadow-lg shadow-emerald-500/30"
          >
            Crear Cuenta
          </Button>

          <div className="text-center text-sm text-slate-400">
            ¿Ya tienes una cuenta?{" "}
            <Link to="/auth/login" className="text-cyan-400 hover:text-cyan-300">
              Inicia sesión
            </Link>
          </div>
        </form>
      </CardContent>
    </Card>
  );
}