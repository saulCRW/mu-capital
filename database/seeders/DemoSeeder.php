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
use App\Modules\Transactions\Models\Transaction;
use App\Modules\Users\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Datos de demostración: un despacho con usuarios, clientes, portafolios,
 * activos, precios, tipos de cambio, benchmarks y transacciones.
 *
 * Los precios son FICTICIOS y deterministas (semilla fija), así que cada
 * "migrate:fresh --seed" produce exactamente los mismos números.
 * Al final se generan los portfolio_snapshots; audit_log queda vacío.
 */
class DemoSeeder extends Seeder
{
    private const DESDE = '2026-01-02';
    private const HASTA = '2026-09-30';
    private const SEMILLA = 2026;
    private const COMISION_PCT = 0.0025; // 0.25% por operación de compra/venta

    /** @var array<string, array<string, float>> simbolo => [fecha => cierre] */
    private array $cierres = [];

    /** @var array<string, float> fecha => USD/MXN */
    private array $usdMxn = [];

    public function run(): void
    {
        mt_srand(self::SEMILLA);
        $dias = $this->diasHabiles();

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
            'tasa_libre_riesgo' => '0.105000',
        ]);

        $usuario = fn (string $nombre, string $email, string $rol) => User::create([
            'organization_id' => $org->id,
            'nombre' => $nombre,
            'email' => $email,
            'password' => 'password',
            'rol' => $rol,
        ]);

        $admin = $usuario('Admin Demo', 'admin@demo.test', User::ROL_ADMIN);
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

        // ── Precios diarios ficticios (caminata aleatoria con semilla fija) ──
        // [precio inicial, volatilidad diaria]
        $series = [
            'AAPL' => [240.0, 0.015],
            'MSFT' => [430.0, 0.014],
            'VOO' => [540.0, 0.009],
            'NAFTRAC' => [60.0, 0.010],
            'WALMEX' => [58.0, 0.013],
            'BTC' => [95000.0, 0.030],
        ];
        foreach ($series as $simbolo => [$inicio, $vol]) {
            $this->cierres[$simbolo] = $this->caminata($dias, $inicio, $vol, 0.0003);
        }

        // SPLIT 2:1 de MSFT (ficticio) el 2026-05-15: desde ese día el precio se divide entre 2.
        foreach ($this->cierres['MSFT'] as $fecha => $cierre) {
            if ($fecha >= '2026-05-15') {
                $this->cierres['MSFT'][$fecha] = round($cierre / 2, 6);
            }
        }

        $filas = [];
        foreach ($this->cierres as $simbolo => $serie) {
            foreach ($serie as $fecha => $cierre) {
                $filas[] = ['asset_id' => $asset[$simbolo]->id, 'fecha' => $fecha, 'cierre' => $this->dec($cierre)];
            }
        }
        $this->insertar(Price::class, $filas);

        // ── Tipo de cambio USD → MXN ────────────────────────────────────
        $this->usdMxn = $this->caminata($dias, 18.50, 0.005, 0.0);
        $filas = [];
        foreach ($this->usdMxn as $fecha => $tasa) {
            $filas[] = ['moneda_origen' => 'USD', 'moneda_destino' => 'MXN', 'fecha' => $fecha, 'tasa' => $this->dec($tasa)];
        }
        $this->insertar(FxRate::class, $filas);

        // ── Benchmarks ──────────────────────────────────────────────────
        $sp500 = Benchmark::create(['nombre' => 'S&P 500']);
        $ipc = Benchmark::create(['nombre' => 'IPC']);
        $cetes = Benchmark::create(['nombre' => 'CETES 28']);

        $filas = [];
        foreach ($this->caminata($dias, 5900.0, 0.009, 0.0003) as $fecha => $v) {
            $filas[] = ['benchmark_id' => $sp500->id, 'fecha' => $fecha, 'valor' => $this->dec($v)];
        }
        foreach ($this->caminata($dias, 57000.0, 0.008, 0.0002) as $fecha => $v) {
            $filas[] = ['benchmark_id' => $ipc->id, 'fecha' => $fecha, 'valor' => $this->dec($v)];
        }
        // CETES 28 como índice base 100 que crece al 10.5% anual compuesto por día natural.
        $base = CarbonImmutable::parse(self::DESDE);
        foreach ($dias as $fecha) {
            $t = $base->diffInDays(CarbonImmutable::parse($fecha)) / 365;
            $filas[] = ['benchmark_id' => $cetes->id, 'fecha' => $fecha, 'valor' => $this->dec(100 * (1.105 ** $t))];
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
        $carlos = $cliente($laura, 'Carlos Vega', 'carlos.vega@example.com', 'agresivo');
        $ortiz = $cliente($jorge, 'Familia Ortiz', 'ortiz@example.com', 'conservador');

        $p1 = Portfolio::create(['organization_id' => $org->id, 'client_id' => $ana->id, 'nombre' => 'Patrimonial', 'moneda_base' => 'MXN', 'benchmark_id' => $ipc->id]);
        $p2 = Portfolio::create(['organization_id' => $org->id, 'client_id' => $carlos->id, 'nombre' => 'Crecimiento USD', 'moneda_base' => 'USD', 'benchmark_id' => $sp500->id]);
        $p3 = Portfolio::create(['organization_id' => $org->id, 'client_id' => $ortiz->id, 'nombre' => 'Conservador', 'moneda_base' => 'MXN', 'benchmark_id' => $cetes->id]);

        // ── Transacciones ───────────────────────────────────────────────
        // Convención usada en los seeders (ver SUPOSICIÓN en la entrega):
        //   COMPRA/VENTA: cantidad = títulos, precio = precio unitario.
        //   DEPOSITO/RETIRO/DIVIDENDO/INTERES/COMISION: cantidad = monto, precio = 1.
        //   SPLIT: cantidad = factor (2 = 2 por 1), precio = 0.
        //   tipo_cambio = moneda de la transacción → moneda base del portafolio.
        $tx = function (Portfolio $p, User $autor, string $tipo, string $fecha, ?string $simbolo, float $cantidad, ?float $precio = null, ?string $nota = null) use ($asset) {
            $moneda = $simbolo !== null && isset($asset[$simbolo]) ? $asset[$simbolo]->moneda : $p->moneda_base;
            $esOperacion = in_array($tipo, [Transaction::COMPRA, Transaction::VENTA], true);

            if ($esOperacion && $precio === null) {
                $precio = $this->cierres[$simbolo][$fecha];
            }
            $precio ??= $tipo === Transaction::SPLIT ? 0.0 : 1.0;
            $comision = $esOperacion ? round($cantidad * $precio * self::COMISION_PCT, 2) : 0.0;

            Transaction::create([
                'organization_id' => $p->organization_id,
                'portfolio_id' => $p->id,
                'asset_id' => $simbolo !== null ? $asset[$simbolo]->id : null,
                'tipo' => $tipo,
                'fecha' => $fecha,
                'cantidad' => $this->dec($cantidad),
                'precio' => $this->dec($precio),
                'comision' => $this->dec($comision),
                'moneda' => $moneda,
                'tipo_cambio' => $this->dec($this->tipoCambio($moneda, $p->moneda_base, $fecha)),
                'nota' => $nota,
                'created_by' => $autor->id,
            ]);
        };

        // Portafolio 1 — Ana Torres, base MXN.
        $tx($p1, $laura, Transaction::DEPOSITO, '2026-01-05', null, 1000000, nota: 'Aportación inicial');
        $tx($p1, $laura, Transaction::COMPRA, '2026-01-06', 'NAFTRAC', 2000);
        $tx($p1, $laura, Transaction::COMPRA, '2026-01-06', 'WALMEX', 3000);
        $tx($p1, $laura, Transaction::COMPRA, '2026-01-07', 'AAPL', 100);
        $tx($p1, $laura, Transaction::DIVIDENDO, '2026-04-15', 'WALMEX', 2400, nota: 'Dividendo WALMEX');
        $tx($p1, $laura, Transaction::VENTA, '2026-06-15', 'NAFTRAC', 1000);
        $tx($p1, $laura, Transaction::INTERES, '2026-07-01', null, 1500, nota: 'Intereses de la cuenta');
        $tx($p1, $laura, Transaction::COMISION, '2026-08-03', null, 250, nota: 'Comisión por custodia');

        // Portafolio 2 — Carlos Vega, base USD.
        $tx($p2, $laura, Transaction::DEPOSITO, '2026-01-05', null, 100000, nota: 'Aportación inicial');
        $tx($p2, $laura, Transaction::COMPRA, '2026-01-06', 'VOO', 80);
        $tx($p2, $laura, Transaction::COMPRA, '2026-01-06', 'MSFT', 50);
        $tx($p2, $laura, Transaction::COMPRA, '2026-01-07', 'BTC', 0.25);
        $tx($p2, $laura, Transaction::DIVIDENDO, '2026-03-27', 'VOO', 140, nota: 'Dividendo trimestral VOO');
        $tx($p2, $laura, Transaction::SPLIT, '2026-05-15', 'MSFT', 2, nota: 'Split 2 por 1');
        $tx($p2, $laura, Transaction::VENTA, '2026-08-20', 'MSFT', 40);
        $tx($p2, $laura, Transaction::RETIRO, '2026-09-01', null, 5000, nota: 'Retiro solicitado por el cliente');

        // Portafolio 3 — Familia Ortiz, base MXN.
        $tx($p3, $jorge, Transaction::DEPOSITO, '2026-01-05', null, 500000, nota: 'Aportación inicial');
        $tx($p3, $jorge, Transaction::COMPRA, '2026-01-06', 'NAFTRAC', 3000);
        $tx($p3, $jorge, Transaction::INTERES, '2026-06-30', null, 2000, nota: 'Intereses de la cuenta');

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

        // ── Snapshots diarios (para gráficas, TWR y riesgo) ─────────────
        $snapshots = app(SnapshotService::class);
        foreach ([$p1, $p2, $p3] as $p) {
            $snapshots->recalcular($p);
        }
    }

    /** @return list<string> fechas Y-m-d de lunes a viernes entre DESDE y HASTA. */
    private function diasHabiles(): array
    {
        $dias = [];
        for ($d = CarbonImmutable::parse(self::DESDE); $d->lte(self::HASTA); $d = $d->addDay()) {
            if (! $d->isWeekend()) {
                $dias[] = $d->toDateString();
            }
        }

        return $dias;
    }

    /**
     * Serie de precios ficticia: cada día el precio cambia un % aleatorio
     * (entre -vol y +vol) más una tendencia pequeña.
     *
     * @param  list<string>  $dias
     * @return array<string, float>
     */
    private function caminata(array $dias, float $inicio, float $vol, float $tendencia): array
    {
        $serie = [];
        $precio = $inicio;
        foreach ($dias as $i => $fecha) {
            if ($i > 0) {
                $cambio = (mt_rand(-1000, 1000) / 1000) * $vol + $tendencia;
                $precio *= 1 + $cambio;
            }
            $serie[$fecha] = round($precio, 6);
        }

        return $serie;
    }

    private function tipoCambio(string $origen, string $destino, string $fecha): float
    {
        if ($origen === $destino) {
            return 1.0;
        }

        return match ([$origen, $destino]) {
            ['USD', 'MXN'] => $this->usdMxn[$fecha],
            ['MXN', 'USD'] => round(1 / $this->usdMxn[$fecha], 6),
        };
    }

    /** Formatea a 6 decimales como texto, para no perder precisión al guardar en DECIMAL. */
    private function dec(float $valor): string
    {
        return number_format($valor, 6, '.', '');
    }

    /**
     * Inserta muchas filas en bloques de 500 (más rápido que create() una por una).
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelo
     */
    private function insertar(string $modelo, array $filas): void
    {
        $ahora = now();
        foreach (array_chunk($filas, 500) as $bloque) {
            $modelo::insert(array_map(fn ($f) => $f + ['created_at' => $ahora, 'updated_at' => $ahora], $bloque));
        }
    }
}
