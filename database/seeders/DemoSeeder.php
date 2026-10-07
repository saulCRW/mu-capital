<?php

namespace Database\Seeders;

use App\Modules\Analytics\Services\SnapshotService;
use App\Modules\Assets\Models\Asset;
use App\Modules\Billing\Models\Plan;
use App\Modules\Clients\Models\Client;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Portfolios\Models\TargetAllocation;
use App\Modules\Prices\Models\Benchmark;
use App\Modules\Prices\Models\BenchmarkValue;
use App\Modules\Prices\Models\FxRate;
use App\Modules\Prices\Models\Price;
use App\Modules\Prices\Services\Providers\FxProvider;
use App\Modules\Prices\Services\Providers\PriceProvider;
use App\Modules\Transactions\Models\Transaction;
use App\Modules\Transactions\Services\LedgerValidator;
use App\Modules\Users\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Datos de demostración: un despacho con usuarios, clientes, portafolios y transacciones.
 *
 * Los datos de MERCADO son reales: precios de cierre, valores del IPC y del S&P 500,
 * tipo de cambio USD/MXN y dividendos por título se traen de los proveedores
 * (Yahoo Finance; Banxico para el tipo de cambio si hay BANXICO_TOKEN). Por eso
 * este seeder necesita internet. Cada compra y venta se registra al cierre real
 * de su fecha y cada dividendo es el real por título × los títulos que se tenían.
 *
 * Lo ilustrativo son los clientes y sus decisiones (cuánto depositan, qué compran
 * y cuándo); la comisión de 0.25 % por operación es un supuesto de arancel del broker.
 */
class DemoSeeder extends Seeder
{
    private const DESDE = '2026-01-02';

    /** Días antes de DESDE que también se traen, para tener precio y tipo de cambio desde el primer día. */
    private const MARGEN = 15;

    private const COMISION_PCT = 0.0025; // 0.25% por operación de compra/venta (supuesto)

    /** @var array<string, array<string, string>> simbolo => [fecha => cierre] */
    private array $cierres = [];

    /** @var array<string, string> fecha => pesos por dólar */
    private array $usdMxn = [];

    public function run(PriceProvider $precios, FxProvider $fx, LedgerValidator $ledger): void
    {
        $hoy = CarbonImmutable::today()->toDateString();
        $desdeDatos = CarbonImmutable::parse(self::DESDE)->subDays(self::MARGEN)->toDateString();

        // ── Usuarios y despacho ─────────────────────────────────────────
        User::create([
            'organization_id' => null,
            'nombre' => 'Superadmin MU Intelligence',
            'email' => 'superadmin@mu-intelligence.test',
            'password' => 'password',
            'rol' => User::ROL_SUPERADMIN,
        ]);

        $org = Organization::create([
            'nombre' => 'Despacho Demo',
            'plan_id' => Plan::where('nombre', 'Profesional')->value('id'),
            'estado' => Organization::ESTADO_ACTIVA,
            'fecha_alta' => '2026-01-01',
            'tasa_libre_riesgo' => '0.105000', // parámetro del despacho (editable), no dato de mercado
        ]);

        $usuario = fn (string $nombre, string $email, string $rol) => User::create([
            'organization_id' => $org->id,
            'nombre' => $nombre,
            'email' => $email,
            'password' => 'password',
            'rol' => $rol,
        ]);

        $usuario('Admin Demo', 'admin@demo.test', User::ROL_ADMIN);
        $laura = $usuario('Laura Méndez', 'asesor1@demo.test', User::ROL_ASESOR);
        $jorge = $usuario('Jorge Ruiz', 'asesor2@demo.test', User::ROL_ASESOR);
        $usuario('Ana Torres', 'cliente@demo.test', User::ROL_CLIENTE);

        // ── Activos ─────────────────────────────────────────────────────
        $activos = [
            ['simbolo' => 'AAPL',    'nombre' => 'Apple Inc.',                    'tipo' => 'accion',   'sector' => 'Tecnología', 'pais' => 'Estados Unidos', 'moneda' => 'USD', 'exchange' => 'NASDAQ'],
            ['simbolo' => 'MSFT',    'nombre' => 'Microsoft Corp.',               'tipo' => 'accion',   'sector' => 'Tecnología', 'pais' => 'Estados Unidos', 'moneda' => 'USD', 'exchange' => 'NASDAQ'],
            ['simbolo' => 'VOO',     'nombre' => 'Vanguard S&P 500 ETF',          'tipo' => 'etf',      'sector' => 'Diversificado', 'pais' => 'Estados Unidos', 'moneda' => 'USD', 'exchange' => 'NYSE Arca'],
            ['simbolo' => 'NAFTRAC', 'nombre' => 'iShares NAFTRAC',               'tipo' => 'etf',      'sector' => 'Diversificado', 'pais' => 'México', 'moneda' => 'MXN', 'exchange' => 'BMV'],
            ['simbolo' => 'WALMEX',  'nombre' => 'Wal-Mart de México',            'tipo' => 'accion',   'sector' => 'Consumo',    'pais' => 'México',         'moneda' => 'MXN', 'exchange' => 'BMV'],
            ['simbolo' => 'BTC',     'nombre' => 'Bitcoin',                       'tipo' => 'cripto',   'sector' => null,         'pais' => null,             'moneda' => 'USD', 'exchange' => null],
            ['simbolo' => 'MXN',     'nombre' => 'Efectivo en pesos mexicanos',   'tipo' => 'efectivo', 'sector' => null,         'pais' => 'México',         'moneda' => 'MXN', 'exchange' => null],
            ['simbolo' => 'USD',     'nombre' => 'Efectivo en dólares',           'tipo' => 'efectivo', 'sector' => null,         'pais' => 'Estados Unidos', 'moneda' => 'USD', 'exchange' => null],
        ];
        $asset = [];
        foreach ($activos as $a) {
            $asset[$a['simbolo']] = Asset::create($a);
        }

        // ── Precios de cierre reales ────────────────────────────────────
        $filas = [];
        foreach ($asset as $simbolo => $a) {
            $simboloProveedor = $precios->simboloPara($a);
            if ($simboloProveedor === null) {
                continue;
            }
            $this->cierres[$simbolo] = $this->exigir($precios->cierres($simboloProveedor, $desdeDatos, $hoy), $simboloProveedor);
            foreach ($this->cierres[$simbolo] as $fecha => $cierre) {
                $filas[] = ['asset_id' => $a->id, 'fecha' => $fecha, 'cierre' => $cierre];
            }
        }
        $this->insertar(Price::class, $filas);

        // ── Tipo de cambio USD → MXN real ───────────────────────────────
        $this->usdMxn = $this->exigir($fx->tasasAPesos('USD', $desdeDatos, $hoy), 'USD/MXN');
        $this->insertar(FxRate::class, array_map(
            fn ($fecha, $tasa) => ['moneda_origen' => 'USD', 'moneda_destino' => 'MXN', 'fecha' => $fecha, 'tasa' => $tasa],
            array_keys($this->usdMxn), $this->usdMxn,
        ));

        // ── Benchmarks reales ───────────────────────────────────────────
        $sp500 = Benchmark::create(['nombre' => 'S&P 500', 'simbolo_proveedor' => '^GSPC']);
        $ipc = Benchmark::create(['nombre' => 'IPC', 'simbolo_proveedor' => '^MXX']);
        $filas = [];
        foreach ([$sp500, $ipc] as $benchmark) {
            foreach ($this->exigir($precios->cierres($benchmark->simbolo_proveedor, $desdeDatos, $hoy), $benchmark->simbolo_proveedor) as $fecha => $valor) {
                $filas[] = ['benchmark_id' => $benchmark->id, 'fecha' => $fecha, 'valor' => $valor];
            }
        }
        $this->insertar(BenchmarkValue::class, $filas);

        // ── Clientes y portafolios ──────────────────────────────────────
        $cliente = fn (User $asesor, string $nombre, string $contacto, string $perfil) => Client::create([
            'organization_id' => $org->id,
            'asesor_id' => $asesor->id,
            'nombre' => $nombre,
            'contacto' => $contacto,
            'perfil_riesgo' => $perfil,
        ]);

        $ana = $cliente($laura, 'Ana Torres', 'ana.torres@example.com', 'moderado');
        User::where('email', 'cliente@demo.test')->update(['client_id' => $ana->id]);
        $carlos = $cliente($laura, 'Carlos Vega', 'carlos.vega@example.com', 'agresivo');
        $ortiz = $cliente($jorge, 'Familia Ortiz', 'ortiz@example.com', 'conservador');

        $p1 = Portfolio::create(['organization_id' => $org->id, 'client_id' => $ana->id, 'nombre' => 'Patrimonial', 'moneda_base' => 'MXN', 'benchmark_id' => $ipc->id]);
        $p2 = Portfolio::create(['organization_id' => $org->id, 'client_id' => $carlos->id, 'nombre' => 'Crecimiento USD', 'moneda_base' => 'USD', 'benchmark_id' => $sp500->id]);
        $p3 = Portfolio::create(['organization_id' => $org->id, 'client_id' => $ortiz->id, 'nombre' => 'Conservador', 'moneda_base' => 'MXN', 'benchmark_id' => $ipc->id]);

        // ── Transacciones (decisiones ilustrativas, a precios reales) ───
        //   COMPRA/VENTA: cantidad = títulos, precio = cierre real de esa fecha.
        //   DEPOSITO/RETIRO/DIVIDENDO: cantidad = monto, precio = 1.
        //   tipo_cambio = real de la fecha (moneda de la transacción → moneda base).
        $operaciones = [
            [$p1, $laura, Transaction::DEPOSITO, '2026-01-05', null, 1000000, 'Aportación inicial'],
            [$p1, $laura, Transaction::COMPRA, '2026-01-06', 'NAFTRAC', 2000, null],
            [$p1, $laura, Transaction::COMPRA, '2026-01-06', 'WALMEX', 3000, null],
            [$p1, $laura, Transaction::COMPRA, '2026-01-07', 'AAPL', 100, null],
            [$p1, $laura, Transaction::VENTA, '2026-06-15', 'NAFTRAC', 1000, null],

            [$p2, $laura, Transaction::DEPOSITO, '2026-01-05', null, 120000, 'Aportación inicial'],
            [$p2, $laura, Transaction::COMPRA, '2026-01-06', 'VOO', 80, null],
            [$p2, $laura, Transaction::COMPRA, '2026-01-06', 'MSFT', 50, null],
            [$p2, $laura, Transaction::COMPRA, '2026-01-07', 'BTC', 0.25, null],
            [$p2, $laura, Transaction::VENTA, '2026-08-20', 'MSFT', 20, null],
            [$p2, $laura, Transaction::RETIRO, '2026-09-01', null, 5000, 'Retiro solicitado por el cliente'],

            [$p3, $jorge, Transaction::DEPOSITO, '2026-01-05', null, 500000, 'Aportación inicial'],
            [$p3, $jorge, Transaction::COMPRA, '2026-01-06', 'NAFTRAC', 3000, null],
        ];
        foreach ($operaciones as [$p, $autor, $tipo, $fecha, $simbolo, $cantidad, $nota]) {
            $this->registrar($p, $autor, $asset, $tipo, $fecha, $simbolo, $cantidad, $nota);
        }

        // Dividendos reales: monto por título (ex-dividendo) × títulos que se tenían ese día.
        foreach ([$p1, $p2, $p3] as $p) {
            foreach (array_unique(array_filter(array_column(array_filter($operaciones, fn ($o) => $o[0] === $p), 4))) as $simbolo) {
                $simboloProveedor = $precios->simboloPara($asset[$simbolo]);
                foreach ($precios->dividendos($simboloProveedor, self::DESDE, $hoy) as $fecha => $porTitulo) {
                    $titulos = $this->titulosAl($operaciones, $p, $simbolo, $fecha);
                    if ($titulos > 0) {
                        $this->registrar($p, $p === $p3 ? $jorge : $laura, $asset, Transaction::DIVIDENDO, $fecha, $simbolo, round($titulos * (float) $porTitulo, 2),
                            sprintf('Dividendo de %s %s por título × %s', rtrim(rtrim($porTitulo, '0'), '.'), $asset[$simbolo]->moneda, $titulos));
                    }
                }
            }
        }

        // ── Asignación objetivo (portafolio 1, por clase de activo) ─────
        foreach (['accion' => 50, 'etf' => 20, 'efectivo' => 30] as $clave => $pct) {
            TargetAllocation::create([
                'organization_id' => $org->id,
                'portfolio_id' => $p1->id,
                'dimension' => TargetAllocation::DIMENSION_CLASE,
                'clave' => $clave,
                'porcentaje_objetivo' => $pct,
            ]);
        }

        // ── Validación del libro y snapshots diarios ────────────────────
        $snapshots = app(SnapshotService::class);
        foreach ([$p1, $p2, $p3] as $p) {
            $error = $ledger->primerError($this->movimientos($p), $p->moneda_base);
            if ($error !== null) {
                throw new RuntimeException("Las transacciones de demo del portafolio {$p->nombre} no cuadran con los precios reales: {$error}");
            }
            $snapshots->recalcular($p);
        }
    }

    private function registrar(Portfolio $p, User $autor, array $asset, string $tipo, string $fecha, ?string $simbolo, float $cantidad, ?string $nota): void
    {
        $moneda = $simbolo !== null ? $asset[$simbolo]->moneda : $p->moneda_base;
        $esOperacion = in_array($tipo, [Transaction::COMPRA, Transaction::VENTA], true);
        $precio = $esOperacion ? (float) $this->ultimo($this->cierres[$simbolo], $fecha, $simbolo) : 1.0;

        Transaction::create([
            'organization_id' => $p->organization_id,
            'portfolio_id' => $p->id,
            'asset_id' => $simbolo !== null ? $asset[$simbolo]->id : null,
            'tipo' => $tipo,
            'fecha' => $fecha,
            'cantidad' => $this->dec($cantidad),
            'precio' => $this->dec($precio),
            'comision' => $this->dec($esOperacion ? round($cantidad * $precio * self::COMISION_PCT, 2) : 0.0),
            'moneda' => $moneda,
            'tipo_cambio' => $this->dec($this->tipoCambio($moneda, $p->moneda_base, $fecha)),
            'nota' => $nota,
            'created_by' => $autor->id,
        ]);
    }

    /** Títulos de un activo en el portafolio antes de la fecha (ex-dividendo: cuenta lo comprado hasta el día anterior). */
    private function titulosAl(array $operaciones, Portfolio $p, string $simbolo, string $fecha): float
    {
        $titulos = 0.0;
        foreach ($operaciones as [$portafolio, , $tipo, $f, $s, $cantidad]) {
            if ($portafolio === $p && $s === $simbolo && $f < $fecha) {
                $titulos += match ($tipo) {
                    Transaction::COMPRA => $cantidad,
                    Transaction::VENTA => -$cantidad,
                    default => 0,
                };
            }
        }

        return $titulos;
    }

    /** @return list<array<string, mixed>> movimientos del portafolio en el formato de LedgerValidator */
    private function movimientos(Portfolio $p): array
    {
        return $p->transactions()->with('asset:id,simbolo')->get()->map(fn (Transaction $t) => [
            'id' => $t->id, 'tipo' => $t->tipo, 'fecha' => $t->fecha->toDateString(), 'asset_id' => $t->asset_id,
            'simbolo' => $t->asset?->simbolo, 'cantidad' => $t->cantidad, 'precio' => $t->precio,
            'comision' => $t->comision, 'tipo_cambio' => $t->tipo_cambio,
        ])->all();
    }

    private function tipoCambio(string $origen, string $destino, string $fecha): float
    {
        if ($origen === $destino) {
            return 1.0;
        }
        $usdMxn = (float) $this->ultimo($this->usdMxn, $fecha, 'USD/MXN');

        return match ([$origen, $destino]) {
            ['USD', 'MXN'] => $usdMxn,
            ['MXN', 'USD'] => round(1 / $usdMxn, 6),
        };
    }

    /** Último valor en o antes de la fecha (días sin cotización: feriados y fines de semana). */
    private function ultimo(array $serie, string $fecha, string $nombre): string
    {
        $valor = null;
        foreach ($serie as $f => $v) {
            if ($f > $fecha) {
                break;
            }
            $valor = $v;
        }

        return $valor ?? throw new RuntimeException("No hay dato real de {$nombre} en o antes del {$fecha}.");
    }

    /** @param  array<string, string>  $serie */
    private function exigir(array $serie, string $nombre): array
    {
        if ($serie === []) {
            throw new RuntimeException("El proveedor no devolvió datos de {$nombre}. El seeder de demo necesita internet para traer datos de mercado reales.");
        }

        return $serie;
    }

    /** Formatea a 6 decimales como texto, para no perder precisión al guardar en DECIMAL. */
    private function dec(float $valor): string
    {
        return number_format($valor, 6, '.', '');
    }

    /**
     * Inserta muchas filas en bloques de 500 (más rápido que create() una por una).
     *
     * @param  class-string<Model>  $modelo
     */
    private function insertar(string $modelo, array $filas): void
    {
        $ahora = now();
        foreach (array_chunk($filas, 500) as $bloque) {
            $modelo::insert(array_map(fn ($f) => $f + ['created_at' => $ahora, 'updated_at' => $ahora], $bloque));
        }
    }
}
