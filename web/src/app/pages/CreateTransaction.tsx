import { useState } from "react";
import { useNavigate } from "react-router";
import { Card, CardContent, CardHeader, CardTitle } from "../components/ui/card";
import { Button } from "../components/ui/button";
import { Input } from "../components/ui/input";
import { Label } from "../components/ui/label";
import { Textarea } from "../components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../components/ui/select";
import { ArrowLeft, ArrowRightLeft, TrendingUp, TrendingDown, Calculator } from "lucide-react";
import { Badge } from "../components/ui/badge";
import { useActivos } from "../../controllers/useActivos";
import { usePortafolios } from "../../controllers/usePortafolios";
import { transaccionService } from "../../services/transaccionService";
import { ResumenOperacion } from "../components/ResumenOperacion";

export function CreateTransaction() {
  const navigate = useNavigate();
  const { activos } = useActivos();
  const { portafolios } = usePortafolios();
  const [transactionType, setTransactionType] = useState<"compra" | "venta">("compra");
  const [submitting, setSubmitting] = useState(false);
  const [formData, setFormData] = useState({
    activo_id: "",
    quantity: "",
    unitPrice: "",
    portafolio_id: "",
    notes: "",
  });

  const cantidad = parseFloat(formData.quantity) || 0;
  const precioUnitario = parseFloat(formData.unitPrice) || 0;
  const total = cantidad * precioUnitario;

  const portafolioSel = portafolios.find((p) => String(p.id) === formData.portafolio_id);
  const activoSel = activos.find((a) => String(a.id) === formData.activo_id);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      setSubmitting(true);
      const transaccion = await transaccionService.create({
        tipo: transactionType,
        activo_id: parseInt(formData.activo_id),
        portafolio_id: parseInt(formData.portafolio_id),
        cantidad,
        precio_unitario: precioUnitario,
        monto_total: total,
        notas: formData.notes || undefined,
      });
      navigate("/app/transaction-registered", { state: { transaction: transaccion } });
    } catch {
      alert("Error al registrar la transacción. Intenta de nuevo.");
    } finally {
      setSubmitting(false);
    }
  };

  const handleCancel = () => {
    navigate("/app/transactions");
  };

  return (
    <div className="min-h-screen py-8">
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <button
          onClick={handleCancel}
          className="inline-flex items-center text-cyan-400 hover:text-cyan-300 mb-6 transition-colors"
        >
          <ArrowLeft className="w-4 h-4 mr-2" />
          Volver a Transacciones
        </button>

        <div className="mb-8">
          <div className="flex items-center gap-3 mb-4">
            <div className="bg-gradient-to-br from-cyan-400 to-emerald-500 p-3 rounded-xl shadow-lg shadow-cyan-500/50">
              <ArrowRightLeft className="w-6 h-6 text-white" />
            </div>
            <div>
              <h1 className="text-3xl font-bold text-white bg-gradient-to-r from-cyan-400 to-emerald-400 bg-clip-text text-transparent">
                Nueva Transacción
              </h1>
              <p className="text-slate-400 mt-1">
                Registra una nueva operación de compra o venta
              </p>
            </div>
          </div>
        </div>

        <Card className="bg-slate-900/50 border-slate-800 backdrop-blur-sm">
          <CardHeader>
            <CardTitle className="text-white">Detalles de la Transacción</CardTitle>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-6">
              {/* Transaction Type Toggle */}
              <div className="space-y-2">
                <Label className="text-slate-300 text-base">Tipo de Transacción *</Label>
                <div className="grid grid-cols-2 gap-4">
                  <button
                    type="button"
                    onClick={() => setTransactionType("compra")}
                    className={`p-4 rounded-lg border-2 transition-all ${
                      transactionType === "compra"
                        ? "border-emerald-500 bg-emerald-500/10"
                        : "border-slate-700 bg-slate-800/30 hover:bg-slate-800/50"
                    }`}
                  >
                    <div className="flex items-center justify-center gap-3">
                      <div className={`p-2 rounded-lg ${transactionType === "compra" ? "bg-emerald-500/20" : "bg-slate-700/50"}`}>
                        <TrendingUp className={`w-5 h-5 ${transactionType === "compra" ? "text-emerald-400" : "text-slate-400"}`} />
                      </div>
                      <div className="text-left">
                        <div className={`font-semibold ${transactionType === "compra" ? "text-emerald-400" : "text-slate-400"}`}>
                          Compra
                        </div>
                        <div className="text-xs text-slate-500">Adquirir activos</div>
                      </div>
                    </div>
                  </button>

                  <button
                    type="button"
                    onClick={() => setTransactionType("venta")}
                    className={`p-4 rounded-lg border-2 transition-all ${
                      transactionType === "venta"
                        ? "border-orange-500 bg-orange-500/10"
                        : "border-slate-700 bg-slate-800/30 hover:bg-slate-800/50"
                    }`}
                  >
                    <div className="flex items-center justify-center gap-3">
                      <div className={`p-2 rounded-lg ${transactionType === "venta" ? "bg-orange-500/20" : "bg-slate-700/50"}`}>
                        <TrendingDown className={`w-5 h-5 ${transactionType === "venta" ? "text-orange-400" : "text-slate-400"}`} />
                      </div>
                      <div className="text-left">
                        <div className={`font-semibold ${transactionType === "venta" ? "text-orange-400" : "text-slate-400"}`}>
                          Venta
                        </div>
                        <div className="text-xs text-slate-500">Vender activos</div>
                      </div>
                    </div>
                  </button>
                </div>
              </div>

              {/* Portfolio: va antes del activo para poder mostrar la referencia */}
              <div className="space-y-2">
                <Label htmlFor="portfolio" className="text-slate-300 text-base">Portafolio Destino *</Label>
                <Select
                  value={formData.portafolio_id}
                  onValueChange={(value) => setFormData({ ...formData, portafolio_id: value })}
                  required
                >
                  <SelectTrigger className="bg-slate-800/50 border-slate-700 text-white h-12 text-base">
                    <SelectValue placeholder="Selecciona un portafolio" />
                  </SelectTrigger>
                  <SelectContent className="bg-slate-800 border-slate-700">
                    {portafolios.map((p) => (
                      <SelectItem key={p.id} value={String(p.id)} className="text-base">
                        <div className="flex items-center gap-2 py-1">
                          <span className="text-white">{p.nombre}</span>
                        </div>
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              {/* Asset Selection */}
              <div className="space-y-2">
                <Label htmlFor="asset" className="text-slate-300 text-base">Activo *</Label>
                <Select
                  value={formData.activo_id}
                  onValueChange={(value) => setFormData({ ...formData, activo_id: value })}
                  required
                >
                  <SelectTrigger className="bg-slate-800/50 border-slate-700 text-white h-12 text-base">
                    <SelectValue placeholder="Selecciona un activo" />
                  </SelectTrigger>
                  <SelectContent className="bg-slate-800 border-slate-700">
                    {activos.map((activo) => (
                      <SelectItem key={activo.id} value={String(activo.id)} className="text-base">
                        <div className="flex items-center gap-3 py-1">
                          <div>
                            <div className="text-white font-medium">{activo.simbolo}</div>
                            <div className="text-slate-400 text-xs">{activo.nombre}</div>
                          </div>
                        </div>
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              {/* Quantity and Unit Price */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="space-y-2">
                  <Label htmlFor="quantity" className="text-slate-300 text-base">Cantidad *</Label>
                  <Input
                    id="quantity"
                    type="number"
                    placeholder="0.00"
                    value={formData.quantity}
                    onChange={(e) => setFormData({ ...formData, quantity: e.target.value })}
                    className="bg-slate-800/50 border-slate-700 text-white placeholder:text-slate-500 h-12 text-base"
                    min="0"
                    step="0.00000001"
                    required
                  />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="unitPrice" className="text-slate-300 text-base">Precio Unitario *</Label>
                  <div className="relative">
                    <span className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-base font-medium">$</span>
                    <Input
                      id="unitPrice"
                      type="number"
                      placeholder="0.00"
                      value={formData.unitPrice}
                      onChange={(e) => setFormData({ ...formData, unitPrice: e.target.value })}
                      className="bg-slate-800/50 border-slate-700 text-white placeholder:text-slate-500 h-12 pl-8 text-base"
                      min="0"
                      step="0.01"
                      required
                    />
                  </div>
                </div>
              </div>

              {/* Total */}
              <div className="space-y-2">
                <Label className="text-slate-300 text-base">Total Calculado</Label>
                <div className={`p-4 rounded-lg border-2 ${total > 0 ? "border-cyan-500/30 bg-cyan-500/10" : "border-slate-700 bg-slate-800/30"}`}>
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                      <div className="bg-cyan-500/20 p-2 rounded-lg">
                        <Calculator className="w-5 h-5 text-cyan-400" />
                      </div>
                      <span className="text-slate-400">Cantidad × Precio</span>
                    </div>
                    <div className="text-2xl font-bold text-white">
                      ${total.toLocaleString("es-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                    </div>
                  </div>
                </div>
              </div>

              {/* Referencia en vivo antes de ejecutar */}
              <div className="space-y-2">
                <Label className="text-slate-300 text-base">Referencia antes de ejecutar</Label>
                <ResumenOperacion
                  portafolio={portafolioSel}
                  activo={activoSel}
                  cantidad={cantidad}
                  precioUnitario={precioUnitario}
                  tipo={transactionType}
                />
              </div>

              {/* Notes */}
              <div className="space-y-2">
                <Label htmlFor="notes" className="text-slate-300 text-base">Notas Opcionales</Label>
                <Textarea
                  id="notes"
                  placeholder="Agrega notas sobre esta transacción..."
                  value={formData.notes}
                  onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                  className="bg-slate-800/50 border-slate-700 text-white placeholder:text-slate-500 min-h-[100px] text-base resize-none"
                  rows={4}
                />
              </div>

              {/* Info Box */}
              <div className={`${transactionType === "compra" ? "bg-emerald-500/10 border-emerald-500/30" : "bg-orange-500/10 border-orange-500/30"} border rounded-lg p-4`}>
                <div className="flex gap-3">
                  <div className={`${transactionType === "compra" ? "bg-emerald-500/20" : "bg-orange-500/20"} p-2 rounded-lg h-fit`}>
                    {transactionType === "compra" ? (
                      <TrendingUp className="w-5 h-5 text-emerald-400" />
                    ) : (
                      <TrendingDown className="w-5 h-5 text-orange-400" />
                    )}
                  </div>
                  <div>
                    <h4 className="text-white font-medium mb-1">
                      {transactionType === "compra" ? "Operación de Compra" : "Operación de Venta"}
                    </h4>
                    <p className="text-slate-300 text-sm leading-relaxed">
                      {transactionType === "compra"
                        ? "Esta transacción agregará los activos a tu portafolio y se reflejará en tu balance total."
                        : "Esta transacción reducirá tus activos del portafolio y liberará capital disponible."
                      }
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
                  <ArrowRightLeft className="w-5 h-5 mr-2" />
                  {submitting ? "Registrando..." : "Registrar Transacción"}
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
