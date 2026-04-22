import { useState } from "react";
import { useNavigate } from "react-router";
import { Card, CardContent, CardHeader, CardTitle } from "../components/ui/card";
import { Button } from "../components/ui/button";
import { Input } from "../components/ui/input";
import { Label } from "../components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../components/ui/select";
import { Switch } from "../components/ui/switch";
import { ArrowLeft, Bell, TrendingDown, Target, TrendingUp, AlertTriangle } from "lucide-react";

export function CreateAlert() {
  const navigate = useNavigate();
  const [alertType, setAlertType] = useState<"drop" | "target" | "volatility">("target");
  const [formData, setFormData] = useState({
    asset: "",
    condition: "",
    targetValue: "",
    notification: true,
  });

  // Mock current prices
  const assetPrices: Record<string, number> = {
    BTC: 70000,
    ETH: 4173,
    AAPL: 195,
    SPY: 425,
    TSLA: 220,
  };

  const currentValue = formData.asset ? assetPrices[formData.asset as keyof typeof assetPrices] : 0;

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    navigate("/app/alert-created", {
      state: {
        alert: {
          ...formData,
          type: alertType,
          currentValue,
        },
      },
    });
  };

  const handleCancel = () => {
    navigate("/app/alerts");
  };

  const getAlertTypeConfig = (type: string) => {
    switch (type) {
      case "drop":
        return {
          label: "Caída",
          color: "from-red-500 to-orange-500",
          bgColor: "bg-red-500/10 border-red-500/30",
          icon: TrendingDown,
          iconColor: "text-red-400",
          iconBg: "bg-red-500/20",
        };
      case "target":
        return {
          label: "Objetivo",
          color: "from-emerald-500 to-green-500",
          bgColor: "bg-emerald-500/10 border-emerald-500/30",
          icon: Target,
          iconColor: "text-emerald-400",
          iconBg: "bg-emerald-500/20",
        };
      case "volatility":
        return {
          label: "Volatilidad",
          color: "from-orange-500 to-pink-500",
          bgColor: "bg-orange-500/10 border-orange-500/30",
          icon: TrendingUp,
          iconColor: "text-orange-400",
          iconBg: "bg-orange-500/20",
        };
      default:
        return {
          label: "",
          color: "",
          bgColor: "",
          icon: Bell,
          iconColor: "",
          iconBg: "",
        };
    }
  };

  const assets = [
    { symbol: "BTC", name: "Bitcoin", icon: "₿" },
    { symbol: "ETH", name: "Ethereum", icon: "Ξ" },
    { symbol: "AAPL", name: "Apple Inc.", icon: "" },
    { symbol: "SPY", name: "S&P 500 ETF", icon: "📈" },
    { symbol: "TSLA", name: "Tesla", icon: "🚗" },
  ];

  return (
    <div className="min-h-screen py-8">
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Back Button */}
        <button
          onClick={handleCancel}
          className="inline-flex items-center text-cyan-400 hover:text-cyan-300 mb-6 transition-colors"
        >
          <ArrowLeft className="w-4 h-4 mr-2" />
          Volver a Alertas
        </button>

        {/* Header */}
        <div className="mb-8">
          <div className="flex items-center gap-3 mb-4">
            <div className="bg-gradient-to-br from-cyan-400 to-emerald-500 p-3 rounded-xl shadow-lg shadow-cyan-500/50">
              <Bell className="w-6 h-6 text-white" />
            </div>
            <div>
              <h1 className="text-3xl font-bold text-white bg-gradient-to-r from-cyan-400 to-emerald-400 bg-clip-text text-transparent">
                Nueva Alerta
              </h1>
              <p className="text-slate-400 mt-1">
                Configura alertas personalizadas para tus inversiones
              </p>
            </div>
          </div>
        </div>

        {/* Form */}
        <Card className="bg-slate-900/50 border-slate-800 backdrop-blur-sm">
          <CardHeader>
            <CardTitle className="text-white">Configuración de Alerta</CardTitle>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-6">
              {/* Alert Type Selector */}
              <div className="space-y-2">
                <Label className="text-slate-300 text-base">
                  Tipo de Alerta *
                </Label>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  {/* Drop Alert */}
                  <button
                    type="button"
                    onClick={() => setAlertType("drop")}
                    className={`p-4 rounded-lg border-2 transition-all ${
                      alertType === "drop"
                        ? "border-red-500 bg-red-500/10"
                        : "border-slate-700 bg-slate-800/30 hover:bg-slate-800/50"
                    }`}
                  >
                    <div className="flex flex-col items-center gap-2">
                      <div
                        className={`p-2 rounded-lg ${
                          alertType === "drop" ? "bg-red-500/20" : "bg-slate-700/50"
                        }`}
                      >
                        <TrendingDown
                          className={`w-6 h-6 ${
                            alertType === "drop" ? "text-red-400" : "text-slate-400"
                          }`}
                        />
                      </div>
                      <div className="text-center">
                        <div
                          className={`font-semibold ${
                            alertType === "drop" ? "text-red-400" : "text-slate-400"
                          }`}
                        >
                          Caída
                        </div>
                        <div className="text-xs text-slate-500 mt-1">
                          Alerta de precio bajo
                        </div>
                      </div>
                    </div>
                  </button>

                  {/* Target Alert */}
                  <button
                    type="button"
                    onClick={() => setAlertType("target")}
                    className={`p-4 rounded-lg border-2 transition-all ${
                      alertType === "target"
                        ? "border-emerald-500 bg-emerald-500/10"
                        : "border-slate-700 bg-slate-800/30 hover:bg-slate-800/50"
                    }`}
                  >
                    <div className="flex flex-col items-center gap-2">
                      <div
                        className={`p-2 rounded-lg ${
                          alertType === "target" ? "bg-emerald-500/20" : "bg-slate-700/50"
                        }`}
                      >
                        <Target
                          className={`w-6 h-6 ${
                            alertType === "target" ? "text-emerald-400" : "text-slate-400"
                          }`}
                        />
                      </div>
                      <div className="text-center">
                        <div
                          className={`font-semibold ${
                            alertType === "target" ? "text-emerald-400" : "text-slate-400"
                          }`}
                        >
                          Objetivo
                        </div>
                        <div className="text-xs text-slate-500 mt-1">
                          Alerta de precio alto
                        </div>
                      </div>
                    </div>
                  </button>

                  {/* Volatility Alert */}
                  <button
                    type="button"
                    onClick={() => setAlertType("volatility")}
                    className={`p-4 rounded-lg border-2 transition-all ${
                      alertType === "volatility"
                        ? "border-orange-500 bg-orange-500/10"
                        : "border-slate-700 bg-slate-800/30 hover:bg-slate-800/50"
                    }`}
                  >
                    <div className="flex flex-col items-center gap-2">
                      <div
                        className={`p-2 rounded-lg ${
                          alertType === "volatility"
                            ? "bg-orange-500/20"
                            : "bg-slate-700/50"
                        }`}
                      >
                        <TrendingUp
                          className={`w-6 h-6 ${
                            alertType === "volatility"
                              ? "text-orange-400"
                              : "text-slate-400"
                          }`}
                        />
                      </div>
                      <div className="text-center">
                        <div
                          className={`font-semibold ${
                            alertType === "volatility"
                              ? "text-orange-400"
                              : "text-slate-400"
                          }`}
                        >
                          Volatilidad
                        </div>
                        <div className="text-xs text-slate-500 mt-1">
                          Cambios bruscos
                        </div>
                      </div>
                    </div>
                  </button>
                </div>
              </div>

              {/* Asset Selection */}
              <div className="space-y-2">
                <Label htmlFor="asset" className="text-slate-300 text-base">
                  Activo *
                </Label>
                <Select
                  value={formData.asset}
                  onValueChange={(value) => setFormData({ ...formData, asset: value })}
                  required
                >
                  <SelectTrigger className="bg-slate-800/50 border-slate-700 text-white h-12 text-base">
                    <SelectValue placeholder="Selecciona un activo" />
                  </SelectTrigger>
                  <SelectContent className="bg-slate-800 border-slate-700">
                    {assets.map((asset) => (
                      <SelectItem key={asset.symbol} value={asset.symbol} className="text-base">
                        <div className="flex items-center gap-3 py-1">
                          <span className="text-xl">{asset.icon || "📊"}</span>
                          <div>
                            <div className="text-white font-medium">{asset.symbol}</div>
                            <div className="text-slate-400 text-xs">{asset.name}</div>
                          </div>
                        </div>
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              {/* Condition */}
              <div className="space-y-2">
                <Label htmlFor="condition" className="text-slate-300 text-base">
                  Condición *
                </Label>
                <Select
                  value={formData.condition}
                  onValueChange={(value) => setFormData({ ...formData, condition: value })}
                  required
                >
                  <SelectTrigger className="bg-slate-800/50 border-slate-700 text-white h-12 text-base">
                    <SelectValue placeholder="Selecciona una condición" />
                  </SelectTrigger>
                  <SelectContent className="bg-slate-800 border-slate-700">
                    <SelectItem value="less" className="text-base">
                      <div className="flex items-center gap-2 py-1">
                        <span className="text-white">Precio &lt;</span>
                        <span className="text-slate-400 text-xs">(menor que)</span>
                      </div>
                    </SelectItem>
                    <SelectItem value="greater" className="text-base">
                      <div className="flex items-center gap-2 py-1">
                        <span className="text-white">Precio &gt;</span>
                        <span className="text-slate-400 text-xs">(mayor que)</span>
                      </div>
                    </SelectItem>
                    <SelectItem value="volatility" className="text-base">
                      <div className="flex items-center gap-2 py-1">
                        <span className="text-white">Volatilidad &gt;</span>
                        <span className="text-slate-400 text-xs">(mayor que)</span>
                      </div>
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>

              {/* Target Value and Current Value */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="space-y-2">
                  <Label htmlFor="targetValue" className="text-slate-300 text-base">
                    Valor Objetivo *
                  </Label>
                  <div className="relative">
                    <span className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-base font-medium">
                      $
                    </span>
                    <Input
                      id="targetValue"
                      type="number"
                      placeholder="0.00"
                      value={formData.targetValue}
                      onChange={(e) =>
                        setFormData({ ...formData, targetValue: e.target.value })
                      }
                      className="bg-slate-800/50 border-slate-700 text-white placeholder:text-slate-500 h-12 pl-8 text-base"
                      min="0"
                      step="0.01"
                      required
                    />
                  </div>
                </div>

                <div className="space-y-2">
                  <Label className="text-slate-300 text-base">Valor Actual</Label>
                  <div className="bg-slate-800/30 border-2 border-slate-700 rounded-lg h-12 px-4 flex items-center">
                    <span className="text-slate-400 text-base font-medium mr-1">$</span>
                    <span className="text-white text-base font-semibold">
                      {currentValue.toLocaleString()}
                    </span>
                  </div>
                </div>
              </div>

              {/* Current Alert Info */}
              {formData.asset && formData.targetValue && (
                <div className={`border rounded-lg p-4 ${getAlertTypeConfig(alertType).bgColor}`}>
                  <div className="flex gap-3">
                    <div className={`${getAlertTypeConfig(alertType).iconBg} p-2 rounded-lg h-fit`}>
                      {(() => {
                        const Icon = getAlertTypeConfig(alertType).icon;
                        return (
                          <Icon className={`w-5 h-5 ${getAlertTypeConfig(alertType).iconColor}`} />
                        );
                      })()}
                    </div>
                    <div>
                      <h4 className="text-white font-medium mb-1">
                        Alerta de {getAlertTypeConfig(alertType).label}
                      </h4>
                      <p className="text-slate-300 text-sm leading-relaxed">
                        Se te notificará cuando <span className="font-semibold">{formData.asset}</span>{" "}
                        {formData.condition === "less" && "caiga por debajo de"}
                        {formData.condition === "greater" && "supere"}
                        {formData.condition === "volatility" && "tenga una volatilidad mayor a"}{" "}
                        <span className="font-semibold">${formData.targetValue}</span>
                      </p>
                    </div>
                  </div>
                </div>
              )}

              {/* Notification Toggle */}
              <div className="space-y-2">
                <div className="flex items-center justify-between p-4 bg-slate-800/30 rounded-lg border border-slate-700">
                  <div className="flex items-center gap-3">
                    <div className="bg-cyan-500/20 p-2 rounded-lg">
                      <Bell className="w-5 h-5 text-cyan-400" />
                    </div>
                    <div>
                      <Label htmlFor="notification" className="text-white font-medium cursor-pointer">
                        Notificaciones
                      </Label>
                      <p className="text-slate-400 text-sm">
                        Recibe alertas cuando se active esta condición
                      </p>
                    </div>
                  </div>
                  <Switch
                    id="notification"
                    checked={formData.notification}
                    onCheckedChange={(checked) =>
                      setFormData({ ...formData, notification: checked })
                    }
                  />
                </div>
              </div>

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
                  <Bell className="w-5 h-5 mr-2" />
                  Crear Alerta
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>

        {/* Tips Section */}
        <div className="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
          <Card className="bg-slate-900/30 border-slate-800">
            <CardContent className="p-4">
              <div className="text-red-400 font-semibold mb-2 flex items-center gap-2">
                <TrendingDown className="w-4 h-4" />
                Caída
              </div>
              <p className="text-slate-400 text-sm">
                Recibe alertas cuando el precio baje del umbral establecido.
              </p>
            </CardContent>
          </Card>
          <Card className="bg-slate-900/30 border-slate-800">
            <CardContent className="p-4">
              <div className="text-emerald-400 font-semibold mb-2 flex items-center gap-2">
                <Target className="w-4 h-4" />
                Objetivo
              </div>
              <p className="text-slate-400 text-sm">
                Notificación cuando el precio alcance tu objetivo de ganancia.
              </p>
            </CardContent>
          </Card>
          <Card className="bg-slate-900/30 border-slate-800">
            <CardContent className="p-4">
              <div className="text-orange-400 font-semibold mb-2 flex items-center gap-2">
                <TrendingUp className="w-4 h-4" />
                Volatilidad
              </div>
              <p className="text-slate-400 text-sm">
                Alerta sobre cambios bruscos en el precio del activo.
              </p>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}
