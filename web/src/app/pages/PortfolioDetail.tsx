import { useParams, Link } from "react-router";
import {
  ArrowLeft,
  Plus,
  TrendingUp,
  TrendingDown,
  Download,
  Upload,
} from "lucide-react";
import { Button } from "../components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "../components/ui/card";
import { Badge } from "../components/ui/badge";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "../components/ui/table";

export function PortfolioDetail() {
  const { id } = useParams();

  const portfolio = {
    name: "Portafolio Principal",
    totalValue: 245000,
    totalInvested: 180000,
    performance: 36.11,
    roi: 65000,
  };

  const assets = [
    {
      symbol: "AAPL",
      name: "Apple Inc.",
      type: "Acción",
      quantity: 500,
      avgPrice: 150,
      currentPrice: 195,
      value: 97500,
      performance: 30.0,
      trend: "up",
    },
    {
      symbol: "BTC",
      name: "Bitcoin",
      type: "Criptomoneda",
      quantity: 1.5,
      avgPrice: 45000,
      currentPrice: 70000,
      value: 105000,
      performance: 55.56,
      trend: "up",
    },
    {
      symbol: "SPY",
      name: "S&P 500 ETF",
      type: "ETF",
      quantity: 100,
      avgPrice: 400,
      currentPrice: 425,
      value: 42500,
      performance: 6.25,
      trend: "up",
    },
  ];

  const formatCurrency = (value: number) =>
    new Intl.NumberFormat("es-US", {
      style: "currency",
      currency: "USD",
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(value);

  return (
    <div className="min-h-screen py-8">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="mb-8">
          <Link
            to="/app/portfolios"
            className="inline-flex items-center text-cyan-400 hover:text-cyan-300 mb-4"
          >
            <ArrowLeft className="w-4 h-4 mr-2" />
            Volver a Portafolios
          </Link>
          <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
              <h1 className="text-3xl font-bold text-white mb-2">
                {portfolio.name}
              </h1>
              <p className="text-slate-400">Detalle y gestión de activos</p>
            </div>
            <div className="flex gap-2">
              <Button
                variant="outline"
                className="border-slate-700 text-slate-300 hover:bg-slate-800"
              >
                <Download className="w-4 h-4 mr-2" />
                Exportar
              </Button>
              <Button className="bg-gradient-to-r from-cyan-500 to-emerald-500 hover:from-cyan-600 hover:to-emerald-600 text-white shadow-lg shadow-cyan-500/30">
                <Plus className="w-4 h-4 mr-2" />
                Agregar Activo
              </Button>
            </div>
          </div>
        </div>

        {/* Summary */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
          <Card className="bg-slate-900/50 border-slate-800">
            <CardContent className="p-6">
              <div className="text-sm text-slate-400 mb-1">Valor Total</div>
              <div className="text-2xl font-bold text-white">
                {formatCurrency(portfolio.totalValue)}
              </div>
            </CardContent>
          </Card>
          <Card className="bg-slate-900/50 border-slate-800">
            <CardContent className="p-6">
              <div className="text-sm text-slate-400 mb-1">Inversión Inicial</div>
              <div className="text-2xl font-bold text-white">
                {formatCurrency(portfolio.totalInvested)}
              </div>
            </CardContent>
          </Card>
          <Card className="bg-slate-900/50 border-slate-800">
            <CardContent className="p-6">
              <div className="text-sm text-slate-400 mb-1">ROI</div>
              <div className="text-2xl font-bold text-emerald-400">
                {formatCurrency(portfolio.roi)}
              </div>
            </CardContent>
          </Card>
          <Card className="bg-slate-900/50 border-slate-800">
            <CardContent className="p-6">
              <div className="text-sm text-slate-400 mb-1">Rendimiento</div>
              <div className="text-2xl font-bold text-emerald-400">
                +{portfolio.performance}%
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Assets Table */}
        <Card className="bg-slate-900/50 border-slate-800">
          <CardHeader>
            <CardTitle className="text-white">Activos en el Portafolio</CardTitle>
          </CardHeader>
          <CardContent>
            <Table>
              <TableHeader>
                <TableRow className="border-slate-700">
                  <TableHead className="text-slate-400">Activo</TableHead>
                  <TableHead className="text-slate-400">Tipo</TableHead>
                  <TableHead className="text-slate-400">Cantidad</TableHead>
                  <TableHead className="text-slate-400">Precio Promedio</TableHead>
                  <TableHead className="text-slate-400">Precio Actual</TableHead>
                  <TableHead className="text-slate-400">Valor Total</TableHead>
                  <TableHead className="text-slate-400">Rendimiento</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {assets.map((asset, index) => (
                  <TableRow key={index} className="border-slate-700">
                    <TableCell>
                      <div>
                        <div className="text-white font-medium">{asset.symbol}</div>
                        <div className="text-sm text-slate-400">{asset.name}</div>
                      </div>
                    </TableCell>
                    <TableCell className="text-slate-300">{asset.type}</TableCell>
                    <TableCell className="text-slate-300">{asset.quantity}</TableCell>
                    <TableCell className="text-slate-300">
                      {formatCurrency(asset.avgPrice)}
                    </TableCell>
                    <TableCell className="text-white">
                      {formatCurrency(asset.currentPrice)}
                    </TableCell>
                    <TableCell className="text-white font-medium">
                      {formatCurrency(asset.value)}
                    </TableCell>
                    <TableCell>
                      <Badge
                        className={
                          asset.trend === "up"
                            ? "bg-emerald-500/20 text-emerald-400 border-emerald-500/50"
                            : "bg-red-500/20 text-red-400 border-red-500/50"
                        }
                      >
                        {asset.trend === "up" ? (
                          <TrendingUp className="w-3 h-3 mr-1" />
                        ) : (
                          <TrendingDown className="w-3 h-3 mr-1" />
                        )}
                        +{asset.performance.toFixed(2)}%
                      </Badge>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
