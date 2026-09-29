import { useState } from "react";
import { useNavigate } from "react-router";
import { Card, CardContent, CardHeader, CardTitle } from "../components/ui/card";
import { Button } from "../components/ui/button";
import { Input } from "../components/ui/input";
import { Label } from "../components/ui/label";
import { Textarea } from "../components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../components/ui/select";
import { ArrowLeft, Briefcase, Check } from "lucide-react";
import { portafolioService } from "../../services/portafolioService";

const estrategiaMap: Record<string, 'growth' | 'income' | 'balanced' | 'especulativo'> = {
  conservative: 'income',
  moderate: 'balanced',
  aggressive: 'growth',
};

export function CreatePortfolio() {
  const navigate = useNavigate();
  const [submitting, setSubmitting] = useState(false);
  const [formData, setFormData] = useState({
    name: "",
    description: "",
    type: "",
    initialInvestment: "",
    currency: "USD",
  });

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      setSubmitting(true);
      const portafolio = await portafolioService.create({
        nombre: formData.name,
        descripcion: formData.description,
        estrategia: estrategiaMap[formData.type],
        moneda: formData.currency,
      });
      navigate("/app/portfolio-created", { state: { portfolio: portafolio } });
    } catch {
      alert("Error al crear el portafolio. Intenta de nuevo.");
    } finally {
      setSubmitting(false);
    }
  };

  const handleCancel = () => {
    navigate("/app/portfolios");
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
          Volver a Mis Portafolios
        </button>

        {/* Header */}
        <div className="mb-8">
          <div className="flex items-center gap-3 mb-4">
            <div className="bg-gradient-to-br from-cyan-400 to-emerald-500 p-3 rounded-xl shadow-lg shadow-cyan-500/50">
              <Briefcase className="w-6 h-6 text-white" />
            </div>
            <h1 className="text-3xl font-bold text-white bg-gradient-to-r from-cyan-400 to-emerald-400 bg-clip-text text-transparent">
              Crear Nuevo Portafolio
            </h1>
          </div>
          <p className="text-slate-400">
            Configura tu nuevo portafolio de inversión personalizado
          </p>
        </div>

        {/* Form */}
        <Card className="bg-slate-900/50 border-slate-800 backdrop-blur-sm">
          <CardHeader>
            <CardTitle className="text-white">Información del Portafolio</CardTitle>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-6">
              <div className="space-y-2">
                <Label htmlFor="name" className="text-slate-300 text-base">
                  Nombre del Portafolio *
                </Label>
                <Input
                  id="name"
                  type="text"
                  placeholder="Ej: Portafolio de Tecnología"
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  className="bg-slate-800/50 border-slate-700 text-white placeholder:text-slate-500 h-12 text-base"
                  required
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="description" className="text-slate-300 text-base">
                  Descripción
                </Label>
                <Textarea
                  id="description"
                  placeholder="Describe el objetivo y estrategia de este portafolio..."
                  value={formData.description}
                  onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                  className="bg-slate-800/50 border-slate-700 text-white placeholder:text-slate-500 min-h-[120px] text-base resize-none"
                  rows={5}
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="type" className="text-slate-300 text-base">
                  Tipo de Portafolio *
                </Label>
                <Select
                  value={formData.type}
                  onValueChange={(value) => setFormData({ ...formData, type: value })}
                  required
                >
                  <SelectTrigger className="bg-slate-800/50 border-slate-700 text-white h-12 text-base">
                    <SelectValue placeholder="Selecciona el nivel de riesgo" />
                  </SelectTrigger>
                  <SelectContent className="bg-slate-800 border-slate-700">
                    <SelectItem value="conservative" className="text-base">
                      <div className="flex items-center gap-3 py-1">
                        <div className="w-3 h-3 rounded-full bg-emerald-500"></div>
                        <div>
                          <div className="text-white font-medium">Conservador</div>
                          <div className="text-slate-400 text-xs">Bajo riesgo, rendimientos estables</div>
                        </div>
                      </div>
                    </SelectItem>
                    <SelectItem value="moderate" className="text-base">
                      <div className="flex items-center gap-3 py-1">
                        <div className="w-3 h-3 rounded-full bg-orange-500"></div>
                        <div>
                          <div className="text-white font-medium">Moderado</div>
                          <div className="text-slate-400 text-xs">Riesgo medio, balance entre seguridad y crecimiento</div>
                        </div>
                      </div>
                    </SelectItem>
                    <SelectItem value="aggressive" className="text-base">
                      <div className="flex items-center gap-3 py-1">
                        <div className="w-3 h-3 rounded-full bg-red-500"></div>
                        <div>
                          <div className="text-white font-medium">Agresivo</div>
                          <div className="text-slate-400 text-xs">Alto riesgo, potencial de altos rendimientos</div>
                        </div>
                      </div>
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="space-y-2">
                  <Label htmlFor="investment" className="text-slate-300 text-base">
                    Inversión Inicial *
                  </Label>
                  <div className="relative">
                    <span className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-base font-medium">
                      $
                    </span>
                    <Input
                      id="investment"
                      type="number"
                      placeholder="0.00"
                      value={formData.initialInvestment}
                      onChange={(e) => setFormData({ ...formData, initialInvestment: e.target.value })}
                      className="bg-slate-800/50 border-slate-700 text-white placeholder:text-slate-500 h-12 pl-8 text-base"
                      min="0"
                      step="0.01"
                      required
                    />
                  </div>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="currency" className="text-slate-300 text-base">
                    Moneda *
                  </Label>
                  <Select
                    value={formData.currency}
                    onValueChange={(value) => setFormData({ ...formData, currency: value })}
                  >
                    <SelectTrigger className="bg-slate-800/50 border-slate-700 text-white h-12 text-base">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent className="bg-slate-800 border-slate-700">
                      <SelectItem value="USD" className="text-base">
                        <div className="flex items-center gap-2">
                          <span className="text-lg">🇺🇸</span>
                          <span className="text-white">USD - Dólar Estadounidense</span>
                        </div>
                      </SelectItem>
                      <SelectItem value="MXN" className="text-base">
                        <div className="flex items-center gap-2">
                          <span className="text-lg">🇲🇽</span>
                          <span className="text-white">MXN - Peso Mexicano</span>
                        </div>
                      </SelectItem>
                      <SelectItem value="EUR" className="text-base">
                        <div className="flex items-center gap-2">
                          <span className="text-lg">🇪🇺</span>
                          <span className="text-white">EUR - Euro</span>
                        </div>
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>

              <div className="bg-cyan-500/10 border border-cyan-500/30 rounded-lg p-4">
                <div className="flex gap-3">
                  <div className="bg-cyan-500/20 p-2 rounded-lg h-fit">
                    <Check className="w-5 h-5 text-cyan-400" />
                  </div>
                  <div>
                    <h4 className="text-white font-medium mb-1">
                      ¿Por qué crear un portafolio?
                    </h4>
                    <p className="text-slate-300 text-sm leading-relaxed">
                      Organiza tus inversiones en portafolios separados para diferentes
                      objetivos financieros. Esto te permite hacer un seguimiento más
                      efectivo y tomar decisiones estratégicas basadas en cada meta.
                    </p>
                  </div>
                </div>
              </div>

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
                  disabled={submitting}
                  className="flex-1 bg-gradient-to-r from-cyan-500 to-emerald-500 hover:from-cyan-600 hover:to-emerald-600 text-white shadow-lg shadow-cyan-500/30 h-12 text-base font-semibold"
                >
                  <Briefcase className="w-5 h-5 mr-2" />
                  {submitting ? "Creando..." : "Crear Portafolio"}
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>

        <div className="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
          <Card className="bg-slate-900/30 border-slate-800">
            <CardContent className="p-4">
              <div className="text-emerald-400 font-semibold mb-2">💡 Conservador</div>
              <p className="text-slate-400 text-sm">
                Ideal para preservar capital con bajo riesgo. 60% bonos, 40% acciones.
              </p>
            </CardContent>
          </Card>
          <Card className="bg-slate-900/30 border-slate-800">
            <CardContent className="p-4">
              <div className="text-orange-400 font-semibold mb-2">⚖️ Moderado</div>
              <p className="text-slate-400 text-sm">
                Balance entre crecimiento y estabilidad. 50% acciones, 50% bonos.
              </p>
            </CardContent>
          </Card>
          <Card className="bg-slate-900/30 border-slate-800">
            <CardContent className="p-4">
              <div className="text-red-400 font-semibold mb-2">🚀 Agresivo</div>
              <p className="text-slate-400 text-sm">
                Máximo potencial de crecimiento. 80% acciones, 20% alternativos.
              </p>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}
