import { Card, CardContent, CardHeader, CardTitle } from "../components/ui/card";
import { ArrowUpRight, ArrowDownRight, TrendingUp, TrendingDown } from "lucide-react";
import { Badge } from "../components/ui/badge";

export function Portfolio() {
  const cryptoAssets = [
    {
      name: "Bitcoin",
      symbol: "BTC",
      allocation: 35,
      value: 73657500,
      amount: 1050.82,
      price: 70000,
      change24h: 3.45,
      change7d: 8.32,
      isPositive: true,
      color: "#F7931A",
    },
    {
      name: "Ethereum",
      symbol: "ETH",
      allocation: 30,
      value: 63135000,
      amount: 15128.05,
      price: 4173,
      change24h: 5.67,
      change7d: 12.45,
      isPositive: true,
      color: "#627EEA",
    },
    {
      name: "Solana",
      symbol: "SOL",
      allocation: 15,
      value: 31567500,
      amount: 211181.43,
      price: 149.5,
      change24h: 8.23,
      change7d: 15.67,
      isPositive: true,
      color: "#14F195",
    },
    {
      name: "Cardano",
      symbol: "ADA",
      allocation: 10,
      value: 21045000,
      amount: 42090000,
      price: 0.50,
      change24h: -2.14,
      change7d: 3.21,
      isPositive: false,
      color: "#0033AD",
    },
    {
      name: "Polkadot",
      symbol: "DOT",
      allocation: 5,
      value: 10522500,
      amount: 1403000,
      price: 7.50,
      change24h: 1.89,
      change7d: -1.45,
      isPositive: true,
      color: "#E6007A",
    },
    {
      name: "Chainlink",
      symbol: "LINK",
      allocation: 3,
      value: 6313500,
      amount: 316675,
      price: 19.94,
      change24h: 4.23,
      change7d: 9.87,
      isPositive: true,
      color: "#375BD2",
    },
    {
      name: "Avalanche",
      symbol: "AVAX",
      allocation: 2,
      value: 4209000,
      amount: 125850,
      price: 33.44,
      change24h: -1.34,
      change7d: 2.56,
      isPositive: false,
      color: "#E84142",
    },
  ];

  const totalValue = cryptoAssets.reduce((sum, asset) => sum + asset.value, 0);

  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat("es-US", {
      style: "currency",
      currency: "USD",
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(value);
  };

  const formatNumber = (value: number) => {
    return new Intl.NumberFormat("es-US", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(value);
  };

  return (
    <div className="min-h-screen py-8">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Header */}
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-white mb-2 bg-gradient-to-r from-cyan-400 to-emerald-400 bg-clip-text text-transparent">Portafolio de Inversiones</h1>
          <p className="text-slate-400">
            Distribución detallada de los activos gestionados por MU Capital
          </p>
        </div>

        {/* Summary Card */}
        <Card className="bg-gradient-to-br from-cyan-600 via-emerald-600 to-cyan-600 border-0 mb-8 shadow-2xl shadow-cyan-500/50">
          <CardContent className="p-8">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div>
                <p className="text-cyan-100 text-sm mb-1">Valor Total del Portafolio</p>
                <p className="text-3xl md:text-4xl font-bold text-white">
                  {formatCurrency(totalValue)}
                </p>
              </div>
              <div>
                <p className="text-cyan-100 text-sm mb-1">Número de Activos</p>
                <p className="text-3xl md:text-4xl font-bold text-white">
                  {cryptoAssets.length}
                </p>
              </div>
              <div>
                <p className="text-cyan-100 text-sm mb-1">Rendimiento Promedio 7d</p>
                <div className="flex items-center gap-2">
                  <p className="text-3xl md:text-4xl font-bold text-white">+7.23%</p>
                  <TrendingUp className="w-8 h-8 text-emerald-300" />
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Assets Grid */}
        <div className="space-y-4">
          <h2 className="text-xl font-bold text-white mb-4 bg-gradient-to-r from-orange-400 to-pink-400 bg-clip-text text-transparent">Activos del Fondo</h2>
          {cryptoAssets.map((asset, index) => (
            <Card key={index} className="bg-slate-900/50 border-slate-800 hover:bg-slate-900 transition-all hover:scale-[1.02]">
              <CardContent className="p-6">
                <div className="grid grid-cols-1 md:grid-cols-6 gap-4 items-center">
                  {/* Asset Info */}
                  <div className="flex items-center gap-4">
                    <div
                      className="w-12 h-12 rounded-full flex items-center justify-center font-bold text-white shadow-lg"
                      style={{ 
                        backgroundColor: asset.color,
                        boxShadow: `0 0 20px ${asset.color}80`
                      }}
                    >
                      {asset.symbol[0]}
                    </div>
                    <div>
                      <h3 className="font-semibold text-white">{asset.name}</h3>
                      <p className="text-sm text-slate-400">{asset.symbol}</p>
                    </div>
                  </div>

                  {/* Allocation */}
                  <div>
                    <p className="text-sm text-slate-400 mb-1">Asignación</p>
                    <div className="flex items-center gap-2">
                      <div className="flex-1 bg-slate-800 rounded-full h-2 overflow-hidden">
                        <div
                          className="h-full rounded-full shadow-lg"
                          style={{
                            width: `${asset.allocation}%`,
                            backgroundColor: asset.color,
                            boxShadow: `0 0 10px ${asset.color}`
                          }}
                        ></div>
                      </div>
                      <span className="text-white font-medium text-sm">
                        {asset.allocation}%
                      </span>
                    </div>
                  </div>

                  {/* Value */}
                  <div>
                    <p className="text-sm text-slate-400 mb-1">Valor en USD</p>
                    <p className="font-semibold text-white">
                      {formatCurrency(asset.value)}
                    </p>
                  </div>

                  {/* Holdings */}
                  <div>
                    <p className="text-sm text-slate-400 mb-1">Cantidad</p>
                    <p className="font-semibold text-white">
                      {formatNumber(asset.amount)} {asset.symbol}
                    </p>
                  </div>

                  {/* Price */}
                  <div>
                    <p className="text-sm text-slate-400 mb-1">Precio Actual</p>
                    <p className="font-semibold text-white">{formatCurrency(asset.price)}</p>
                  </div>

                  {/* Performance */}
                  <div className="flex flex-col gap-2">
                    <div className="flex items-center gap-2">
                      <Badge
                        variant={asset.isPositive ? "default" : "destructive"}
                        className={
                          asset.isPositive
                            ? "bg-emerald-500/20 text-emerald-400 hover:bg-emerald-500/30 border-emerald-500/50 shadow-lg shadow-emerald-500/20"
                            : "bg-red-500/20 text-red-400 hover:bg-red-500/30 border-red-500/50 shadow-lg shadow-red-500/20"
                        }
                      >
                        {asset.isPositive ? (
                          <ArrowUpRight className="w-3 h-3 mr-1" />
                        ) : (
                          <ArrowDownRight className="w-3 h-3 mr-1" />
                        )}
                        {asset.change24h > 0 ? "+" : ""}
                        {asset.change24h}% (24h)
                      </Badge>
                    </div>
                    <div className="flex items-center gap-2">
                      <Badge
                        variant="outline"
                        className="border-slate-600 text-slate-300"
                      >
                        {asset.change7d > 0 ? (
                          <TrendingUp className="w-3 h-3 mr-1 text-emerald-500" />
                        ) : (
                          <TrendingDown className="w-3 h-3 mr-1 text-red-500" />
                        )}
                        {asset.change7d > 0 ? "+" : ""}
                        {asset.change7d}% (7d)
                      </Badge>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>

        {/* Info Card */}
        <Card className="bg-slate-900/50 border-slate-800 mt-8">
          <CardHeader>
            <CardTitle className="text-white bg-gradient-to-r from-purple-400 to-fuchsia-400 bg-clip-text text-transparent">Estrategia de Diversificación</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-slate-300 mb-4">
              Nuestro portafolio está diseñado para maximizar el rendimiento mientras
              minimizamos el riesgo a través de una diversificación estratégica:
            </p>
            <ul className="space-y-2 text-slate-400">
              <li className="flex items-start gap-2">
                <span className="text-cyan-400 mt-1">•</span>
                <span>
                  <strong className="text-white">Criptomonedas de alta capitalización (65%):</strong> Bitcoin
                  y Ethereum proporcionan estabilidad y liquidez.
                </span>
              </li>
              <li className="flex items-start gap-2">
                <span className="text-emerald-400 mt-1">•</span>
                <span>
                  <strong className="text-white">Proyectos de capa 1 emergentes (20%):</strong> Solana,
                  Cardano y Polkadot ofrecen alto potencial de crecimiento.
                </span>
              </li>
              <li className="flex items-start gap-2">
                <span className="text-orange-400 mt-1">•</span>
                <span>
                  <strong className="text-white">Infraestructura DeFi (15%):</strong> Chainlink y Avalanche
                  capturan el crecimiento del ecosistema descentralizado.
                </span>
              </li>
            </ul>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}