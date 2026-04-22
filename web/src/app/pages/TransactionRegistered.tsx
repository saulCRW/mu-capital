import { useEffect } from "react";
import { useNavigate, useLocation } from "react-router";
import { Card, CardContent } from "../components/ui/card";
import { Button } from "../components/ui/button";
import { CheckCircle, ArrowRight, Plus, TrendingUp, TrendingDown } from "lucide-react";
import { motion } from "motion/react";
import { Badge } from "../components/ui/badge";

export function TransactionRegistered() {
  const navigate = useNavigate();
  const location = useLocation();
  const transaction = location.state?.transaction;

  const getStatusBadge = (status: string) => {
    switch (status) {
      case "completed":
        return <Badge className="bg-cyan-500/20 text-cyan-400 border-cyan-500/50">Completada</Badge>;
      case "pending":
        return <Badge className="bg-yellow-500/20 text-yellow-400 border-yellow-500/50">Pendiente</Badge>;
      case "cancelled":
        return <Badge className="bg-red-500/20 text-red-400 border-red-500/50">Cancelada</Badge>;
      default:
        return null;
    }
  };

  const formatCurrency = (value: number) =>
    new Intl.NumberFormat("es-US", {
      style: "currency",
      currency: "USD",
      minimumFractionDigits: 2,
    }).format(value);

  return (
    <div className="min-h-screen flex items-center justify-center py-8 px-4">
      <div className="max-w-2xl w-full">
        <motion.div
          initial={{ scale: 0.8, opacity: 0 }}
          animate={{ scale: 1, opacity: 1 }}
          transition={{ duration: 0.5, ease: "easeOut" }}
        >
          {/* Success Icon */}
          <div className="flex justify-center mb-8">
            <motion.div
              initial={{ scale: 0 }}
              animate={{ scale: 1 }}
              transition={{ delay: 0.2, type: "spring", stiffness: 200 }}
              className={`${
                transaction?.type === "buy"
                  ? "bg-gradient-to-br from-emerald-500 to-green-500 shadow-emerald-500/50"
                  : "bg-gradient-to-br from-orange-500 to-pink-500 shadow-orange-500/50"
              } p-6 rounded-full shadow-2xl`}
            >
              <CheckCircle className="w-16 h-16 text-white" />
            </motion.div>
          </div>

          {/* Success Message */}
          <motion.div
            initial={{ y: 20, opacity: 0 }}
            animate={{ y: 0, opacity: 1 }}
            transition={{ delay: 0.3 }}
            className="text-center mb-8"
          >
            <h1 className="text-4xl font-bold text-white mb-3 bg-gradient-to-r from-cyan-400 to-emerald-400 bg-clip-text text-transparent">
              ¡Transacción Registrada!
            </h1>
            <p className="text-slate-400 text-lg">
              Tu operación ha sido registrada exitosamente
            </p>
          </motion.div>

          {/* Transaction Details */}
          {transaction && (
            <motion.div
              initial={{ y: 20, opacity: 0 }}
              animate={{ y: 0, opacity: 1 }}
              transition={{ delay: 0.4 }}
            >
              <Card className="bg-slate-900/50 border-slate-800 backdrop-blur-sm mb-6">
                <CardContent className="p-8">
                  <div className="space-y-6">
                    {/* Transaction Type */}
                    <div className="flex items-center justify-between">
                      <span className="text-slate-400">Tipo de Transacción</span>
                      <div className="flex items-center gap-2">
                        {transaction.type === "buy" ? (
                          <>
                            <div className="bg-emerald-500/20 p-2 rounded-lg">
                              <TrendingUp className="w-5 h-5 text-emerald-400" />
                            </div>
                            <span className="text-emerald-400 font-semibold text-lg">
                              Compra
                            </span>
                          </>
                        ) : (
                          <>
                            <div className="bg-orange-500/20 p-2 rounded-lg">
                              <TrendingDown className="w-5 h-5 text-orange-400" />
                            </div>
                            <span className="text-orange-400 font-semibold text-lg">
                              Venta
                            </span>
                          </>
                        )}
                      </div>
                    </div>

                    {/* Asset */}
                    <div className="flex items-center justify-between">
                      <span className="text-slate-400">Activo</span>
                      <span className="text-white font-semibold text-lg">
                        {transaction.asset}
                      </span>
                    </div>

                    {/* Quantity */}
                    <div className="flex items-center justify-between">
                      <span className="text-slate-400">Cantidad</span>
                      <span className="text-white font-semibold">
                        {parseFloat(transaction.quantity).toLocaleString()}
                      </span>
                    </div>

                    {/* Unit Price */}
                    <div className="flex items-center justify-between">
                      <span className="text-slate-400">Precio Unitario</span>
                      <span className="text-white font-semibold">
                        {formatCurrency(parseFloat(transaction.unitPrice))}
                      </span>
                    </div>

                    {/* Total */}
                    <div className="pt-4 border-t border-slate-700">
                      <div className="flex items-center justify-between">
                        <span className="text-slate-400 text-lg">Total</span>
                        <span className="text-cyan-400 font-bold text-2xl">
                          {formatCurrency(transaction.total)}
                        </span>
                      </div>
                    </div>

                    {/* Portfolio */}
                    <div className="flex items-center justify-between">
                      <span className="text-slate-400">Portafolio</span>
                      <span className="text-white font-semibold">
                        {transaction.portfolio}
                      </span>
                    </div>

                    {/* Date */}
                    <div className="flex items-center justify-between">
                      <span className="text-slate-400">Fecha</span>
                      <span className="text-white">
                        {new Date(transaction.date).toLocaleDateString('es-ES', {
                          year: 'numeric',
                          month: 'long',
                          day: 'numeric',
                        })}
                      </span>
                    </div>

                    {/* Status */}
                    <div className="flex items-center justify-between">
                      <span className="text-slate-400">Estado</span>
                      {getStatusBadge(transaction.status)}
                    </div>

                    {/* Notes */}
                    {transaction.notes && (
                      <div className="pt-4 border-t border-slate-700">
                        <div className="text-slate-400 text-sm mb-2">Notas</div>
                        <div className="text-slate-300 bg-slate-800/50 p-3 rounded-lg">
                          {transaction.notes}
                        </div>
                      </div>
                    )}
                  </div>
                </CardContent>
              </Card>
            </motion.div>
          )}

          {/* Info Box */}
          <motion.div
            initial={{ y: 20, opacity: 0 }}
            animate={{ y: 0, opacity: 1 }}
            transition={{ delay: 0.5 }}
          >
            <Card className="bg-gradient-to-br from-cyan-600/20 to-emerald-600/20 border-cyan-500/30 backdrop-blur-sm mb-6">
              <CardContent className="p-6">
                <h3 className="text-white font-semibold mb-3">
                  La transacción ha sido procesada
                </h3>
                <p className="text-slate-300 text-sm leading-relaxed">
                  Puedes ver esta transacción en tu historial. Tu portafolio se ha
                  actualizado automáticamente para reflejar esta operación.
                </p>
              </CardContent>
            </Card>
          </motion.div>

          {/* Action Buttons */}
          <motion.div
            initial={{ y: 20, opacity: 0 }}
            animate={{ y: 0, opacity: 1 }}
            transition={{ delay: 0.6 }}
            className="flex flex-col sm:flex-row gap-4"
          >
            <Button
              onClick={() => navigate("/app/transactions")}
              className="flex-1 bg-gradient-to-r from-cyan-500 to-emerald-500 hover:from-cyan-600 hover:to-emerald-600 text-white shadow-lg shadow-cyan-500/30 h-12 text-base font-semibold"
            >
              Ver Historial
              <ArrowRight className="w-5 h-5 ml-2" />
            </Button>
            <Button
              onClick={() => navigate("/app/transactions/create")}
              variant="outline"
              className="flex-1 sm:flex-none border-slate-700 text-slate-300 hover:bg-slate-800 h-12 text-base"
            >
              <Plus className="w-5 h-5 mr-2" />
              Nueva Transacción
            </Button>
          </motion.div>
        </motion.div>
      </div>
    </div>
  );
}
