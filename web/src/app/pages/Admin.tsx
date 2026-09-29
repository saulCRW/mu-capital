import { Card, CardContent, CardHeader, CardTitle } from "../components/ui/card";
import { Button } from "../components/ui/button";
import { Input } from "../components/ui/input";
import { Badge } from "../components/ui/badge";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "../components/ui/table";
import { Users, Briefcase, TrendingUp, DollarSign, Plus, Search, MoreVertical } from "lucide-react";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "../components/ui/tabs";
import { Link } from "react-router";
import { useUsuarios } from "../../controllers/useUsuarios";
import { usePortafolios } from "../../controllers/usePortafolios";

const globalAssets = [
  { name: "Bitcoin", symbol: "BTC", users: 45, totalValue: 12500000, allocation: 35 },
  { name: "Ethereum", symbol: "ETH", users: 38, totalValue: 9800000, allocation: 28 },
  { name: "Apple", symbol: "AAPL", users: 52, totalValue: 8200000, allocation: 23 },
  { name: "S&P 500", symbol: "SPY", users: 29, totalValue: 4900000, allocation: 14 },
];

const formatCurrency = (value: number) =>
  new Intl.NumberFormat("es-US", { style: "currency", currency: "USD", minimumFractionDigits: 0 }).format(value);

export function Admin() {
  const { usuarios, loading: loadingUsuarios, error } = useUsuarios();
  const { portafolios, loading: loadingPortafolios } = usePortafolios();

  return (
    <div className="min-h-screen py-8">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-white mb-2 bg-gradient-to-r from-cyan-400 to-emerald-400 bg-clip-text text-transparent">
            Panel de Administración
          </h1>
          <p className="text-slate-400">Gestión global de usuarios, activos y reportes</p>
        </div>

        {/* Global Stats */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
          <Card className="bg-slate-900/50 border-slate-800">
            <CardContent className="p-6">
              <div className="flex items-start justify-between mb-4">
                <div className="bg-gradient-to-br from-cyan-500 to-blue-500 p-2 rounded-lg shadow-lg shadow-cyan-500/50">
                  <Users className="w-5 h-5 text-white" />
                </div>
              </div>
              <div className="text-2xl font-bold text-white mb-1">
                {loadingUsuarios ? "—" : usuarios.length}
              </div>
              <div className="text-sm text-slate-400">Usuarios Totales</div>
            </CardContent>
          </Card>

          <Card className="bg-slate-900/50 border-slate-800">
            <CardContent className="p-6">
              <div className="flex items-start justify-between mb-4">
                <div className="bg-gradient-to-br from-emerald-500 to-green-500 p-2 rounded-lg shadow-lg shadow-emerald-500/50">
                  <Briefcase className="w-5 h-5 text-white" />
                </div>
              </div>
              <div className="text-2xl font-bold text-white mb-1">
                {loadingPortafolios ? "—" : portafolios.length}
              </div>
              <div className="text-sm text-slate-400">Portafolios Activos</div>
            </CardContent>
          </Card>

          <Card className="bg-slate-900/50 border-slate-800">
            <CardContent className="p-6">
              <div className="flex items-start justify-between mb-4">
                <div className="bg-gradient-to-br from-orange-500 to-pink-500 p-2 rounded-lg shadow-lg shadow-orange-500/50">
                  <DollarSign className="w-5 h-5 text-white" />
                </div>
              </div>
              <div className="text-2xl font-bold text-white mb-1">$35.4M</div>
              <div className="text-sm text-slate-400">AUM Total</div>
            </CardContent>
          </Card>

          <Card className="bg-slate-900/50 border-slate-800">
            <CardContent className="p-6">
              <div className="flex items-start justify-between mb-4">
                <div className="bg-gradient-to-br from-purple-500 to-fuchsia-500 p-2 rounded-lg shadow-lg shadow-purple-500/50">
                  <TrendingUp className="w-5 h-5 text-white" />
                </div>
              </div>
              <div className="text-2xl font-bold text-white mb-1">+28.5%</div>
              <div className="text-sm text-slate-400">Rendimiento Global</div>
            </CardContent>
          </Card>
        </div>

        {/* Admin Tabs */}
        <Tabs defaultValue="users" className="space-y-6">
          <TabsList className="bg-slate-900/50 border border-slate-800">
            <TabsTrigger value="users" className="data-[state=active]:bg-cyan-500/20 data-[state=active]:text-cyan-400">
              Usuarios
            </TabsTrigger>
            <TabsTrigger value="assets" className="data-[state=active]:bg-cyan-500/20 data-[state=active]:text-cyan-400">
              Activos Globales
            </TabsTrigger>
            <TabsTrigger value="reports" className="data-[state=active]:bg-cyan-500/20 data-[state=active]:text-cyan-400">
              Reportes
            </TabsTrigger>
          </TabsList>

          {/* Users Tab */}
          <TabsContent value="users">
            <Card className="bg-slate-900/50 border-slate-800">
              <CardHeader>
                <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                  <CardTitle className="text-white">Gestión de Usuarios</CardTitle>
                  <div className="flex gap-2">
                    <div className="relative">
                      <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                      <Input placeholder="Buscar usuario..." className="bg-slate-800/50 border-slate-700 text-white pl-10" />
                    </div>
                    <Link to="/app/admin/users/create">
                      <Button className="bg-gradient-to-r from-cyan-500 to-emerald-500 hover:from-cyan-600 hover:to-emerald-600 text-white shadow-lg shadow-cyan-500/30 transition-all hover:scale-105">
                        <Plus className="w-4 h-4 mr-2" />
                        Nuevo Usuario
                      </Button>
                    </Link>
                  </div>
                </div>
              </CardHeader>
              <CardContent>
                {loadingUsuarios ? (
                  <div className="text-center text-slate-400 py-8">Cargando usuarios...</div>
                ) : usuarios.length === 0 ? (
                  <div className="text-center text-slate-400 py-8">
                    {error ?? "No hay usuarios registrados todavía."}
                  </div>
                ) : (
                  <Table>
                    <TableHeader>
                      <TableRow className="border-slate-700">
                        <TableHead className="text-slate-400">Usuario</TableHead>
                        <TableHead className="text-slate-400">Rol</TableHead>
                        <TableHead className="text-slate-400">Estado</TableHead>
                        <TableHead className="text-slate-400">Acciones</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {usuarios.map((user) => (
                        <TableRow key={user.id} className="border-slate-700">
                          <TableCell>
                            <div>
                              <div className="text-white font-medium">{user.nombre} {user.apellido}</div>
                              <div className="text-sm text-slate-400">{user.email}</div>
                            </div>
                          </TableCell>
                          <TableCell>
                            <Badge className="bg-emerald-500/20 text-emerald-400 border-emerald-500/50">
                              {user.rol?.nombre}
                            </Badge>
                          </TableCell>
                          <TableCell>
                            <Badge className={user.activo
                              ? "bg-emerald-500/20 text-emerald-400 border-emerald-500/50"
                              : "bg-slate-500/20 text-slate-400 border-slate-500/50"
                            }>
                              {user.activo ? "Activo" : "Inactivo"}
                            </Badge>
                          </TableCell>
                          <TableCell>
                            <Button variant="ghost" size="sm" className="text-slate-400 hover:text-white">
                              <MoreVertical className="w-4 h-4" />
                            </Button>
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                )}
              </CardContent>
            </Card>
          </TabsContent>

          {/* Assets Tab */}
          <TabsContent value="assets">
            <Card className="bg-slate-900/50 border-slate-800">
              <CardHeader>
                <CardTitle className="text-white">Activos Globales</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="space-y-4">
                  {globalAssets.map((asset, index) => (
                    <div key={index} className="p-4 bg-slate-800/50 rounded-lg border border-slate-700">
                      <div className="flex items-center justify-between mb-3">
                        <div>
                          <h4 className="text-white font-semibold">{asset.name} ({asset.symbol})</h4>
                          <p className="text-slate-400 text-sm">{asset.users} usuarios invirtiendo</p>
                        </div>
                        <div className="text-right">
                          <div className="text-white font-semibold">{formatCurrency(asset.totalValue)}</div>
                          <div className="text-slate-400 text-sm">{asset.allocation}% del total</div>
                        </div>
                      </div>
                      <div className="h-2 bg-slate-700 rounded-full overflow-hidden">
                        <div className="h-full bg-gradient-to-r from-cyan-500 to-emerald-500 rounded-full" style={{ width: `${asset.allocation}%` }} />
                      </div>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          {/* Reports Tab */}
          <TabsContent value="reports">
            <Card className="bg-slate-900/50 border-slate-800">
              <CardHeader>
                <CardTitle className="text-white">Reportes Globales</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="space-y-4">
                  <Button variant="outline" className="w-full border-slate-700 text-slate-300 hover:bg-slate-800 justify-start">
                    <TrendingUp className="w-4 h-4 mr-2" />
                    Reporte de Rendimiento Mensual
                  </Button>
                  <Button variant="outline" className="w-full border-slate-700 text-slate-300 hover:bg-slate-800 justify-start">
                    <Users className="w-4 h-4 mr-2" />
                    Reporte de Actividad de Usuarios
                  </Button>
                  <Button variant="outline" className="w-full border-slate-700 text-slate-300 hover:bg-slate-800 justify-start">
                    <Briefcase className="w-4 h-4 mr-2" />
                    Reporte de Portafolios
                  </Button>
                  <Button variant="outline" className="w-full border-slate-700 text-slate-300 hover:bg-slate-800 justify-start">
                    <DollarSign className="w-4 h-4 mr-2" />
                    Reporte Financiero Consolidado
                  </Button>
                </div>
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>
      </div>
    </div>
  );
}
