<?php

namespace App\Modules\Analytics\Services;

use App\Modules\Transactions\Models\Transaction;
use App\Modules\Transactions\Services\LedgerValidator;
use LogicException;

/**
 * Construye el estado del portafolio a partir del libro de transacciones.
 * Cálculos 1–7 de PROYECTO.md (sección 5) (todo el dinero en moneda base, bcmath, nunca float):
 *
 *  1. Posición por activo: + compras − ventas; el SPLIT multiplica por el factor.
 *  2. Costo promedio ponderado: costo total (incluye comisiones) ÷ títulos.
 *     Al vender, el costo baja en proporción a los títulos vendidos; el
 *     promedio no cambia. El SPLIT no cambia el costo total (el promedio se divide).
 *  3. Efectivo = depósitos − retiros − compras + ventas + dividendos + intereses − comisiones.
 *  4. Valor de mercado = títulos × último cierre × tipo de cambio de esa fecha.
 *  5. G/P no realizada = valor de mercado − costo; % = G/P ÷ costo.
 *  6. G/P realizada (al vender) = (títulos × precio − comisión) × tc − costo promedio × títulos.
 *  7. Ingresos = dividendos + intereses.
 */
final class PortfolioLedger
{
    private const S = 6;

    private const S_INT = 12;

    private string $efectivo = '0';

    /** @var array<int|string, array<string, string|null>> estado por activo */
    private array $activos = [];

    /** Totales del portafolio en moneda base. */
    private array $totales = [
        'depositos' => '0', 'retiros' => '0', 'dividendos' => '0',
        'intereses' => '0', 'comisiones' => '0',
        // Intereses sin activo menos comisiones sueltas: lo que no se atribuye a ningún activo.
        'otros' => '0',
    ];

    /** @var array<string, string> moneda => último tipo de cambio usado en una transacción */
    private array $ultimoTc = [];

    public function __construct(private readonly string $monedaBase) {}

    /** Crea el libro y aplica las transacciones en orden cronológico. */
    public static function reproducir(array $transacciones, string $monedaBase): self
    {
        $libro = new self($monedaBase);
        foreach (LedgerValidator::ordenar($transacciones) as $t) {
            $libro->aplicar($t);
        }

        return $libro;
    }

    /**
     * Aplica UNA transacción. Deben llegar en el orden de LedgerValidator::ordenar().
     *
     * @param  array{tipo:string, asset_id?:int|null, cantidad:string, precio:string, comision:string, moneda?:string, tipo_cambio:string}  $t
     */
    public function aplicar(array $t): void
    {
        $tc = $t['tipo_cambio'];
        $moneda = $t['moneda'] ?? $this->monedaBase;
        $id = $t['asset_id'] ?? null;

        if ($moneda !== $this->monedaBase) {
            $this->ultimoTc[$moneda] = $tc;
        }

        $this->efectivo = bcadd($this->efectivo, LedgerValidator::efectoEnEfectivo($t), self::S);
        $importeBase = bcmul($t['cantidad'], $tc, self::S); // para tipos donde cantidad = monto

        if ($id !== null && ! isset($this->activos[$id])) {
            $this->activos[$id] = [
                'moneda' => $moneda, 'cantidad' => '0', 'costo_base' => '0', 'costo_local' => '0',
                'invertido_base' => '0', 'realizada' => '0', 'ingresos' => '0', 'ultimo_precio' => null,
            ];
        }

        switch ($t['tipo']) {
            case Transaction::COMPRA:
                $a = &$this->activos[$id];
                $costoLocal = bcadd(bcmul($t['cantidad'], $t['precio'], self::S_INT), $t['comision'], self::S_INT);
                $costoBase = bcmul($costoLocal, $tc, self::S);
                $a['cantidad'] = bcadd($a['cantidad'], $t['cantidad'], self::S);
                $a['costo_local'] = bcadd($a['costo_local'], $costoLocal, self::S);
                $a['costo_base'] = bcadd($a['costo_base'], $costoBase, self::S);
                $a['invertido_base'] = bcadd($a['invertido_base'], $costoBase, self::S);
                $a['ultimo_precio'] = $t['precio'];
                unset($a);
                break;

            case Transaction::VENTA:
                $a = &$this->activos[$id];
                if (bccomp($t['cantidad'], $a['cantidad'], self::S) > 0) {
                    throw new LogicException('Venta mayor que la posición: el libro debió validarse con LedgerValidator.');
                }
                $costoVendidoBase = bcdiv(bcmul($a['costo_base'], $t['cantidad'], self::S_INT), $a['cantidad'], self::S);
                $costoVendidoLocal = bcdiv(bcmul($a['costo_local'], $t['cantidad'], self::S_INT), $a['cantidad'], self::S);
                $netoBase = bcmul(bcsub(bcmul($t['cantidad'], $t['precio'], self::S_INT), $t['comision'], self::S_INT), $tc, self::S);

                $a['realizada'] = bcadd($a['realizada'], bcsub($netoBase, $costoVendidoBase, self::S), self::S);
                $a['cantidad'] = bcsub($a['cantidad'], $t['cantidad'], self::S);
                $a['costo_base'] = bcsub($a['costo_base'], $costoVendidoBase, self::S);
                $a['costo_local'] = bcsub($a['costo_local'], $costoVendidoLocal, self::S);
                if (bccomp($a['cantidad'], '0', self::S) === 0) {
                    $a['costo_base'] = '0';
                    $a['costo_local'] = '0';
                }
                $a['ultimo_precio'] = $t['precio'];
                unset($a);
                break;

            case Transaction::SPLIT:
                $a = &$this->activos[$id];
                $a['cantidad'] = bcmul($a['cantidad'], $t['cantidad'], self::S);
                if ($a['ultimo_precio'] !== null) {
                    $a['ultimo_precio'] = bcdiv($a['ultimo_precio'], $t['cantidad'], self::S);
                }
                unset($a);
                break;

            case Transaction::DIVIDENDO:
                $this->activos[$id]['ingresos'] = bcadd($this->activos[$id]['ingresos'], $importeBase, self::S);
                $this->totales['dividendos'] = bcadd($this->totales['dividendos'], $importeBase, self::S);
                break;

            case Transaction::INTERES:
                $this->totales['intereses'] = bcadd($this->totales['intereses'], $importeBase, self::S);
                if ($id !== null) {
                    $this->activos[$id]['ingresos'] = bcadd($this->activos[$id]['ingresos'], $importeBase, self::S);
                } else {
                    $this->totales['otros'] = bcadd($this->totales['otros'], $importeBase, self::S);
                }
                break;

            case Transaction::COMISION:
                $this->totales['comisiones'] = bcadd($this->totales['comisiones'], $importeBase, self::S);
                $this->totales['otros'] = bcsub($this->totales['otros'], $importeBase, self::S);
                break;

            case Transaction::DEPOSITO:
                $this->totales['depositos'] = bcadd($this->totales['depositos'], $importeBase, self::S);
                break;

            case Transaction::RETIRO:
                $this->totales['retiros'] = bcadd($this->totales['retiros'], $importeBase, self::S);
                break;
        }
    }

    /** Solo valor total y efectivo (rápido, para generar snapshots diarios). */
    public function valorTotal(string $fecha, PriceBook $precios): string
    {
        $total = $this->efectivo;
        foreach ($this->activos as $id => $a) {
            if (bccomp($a['cantidad'], '0', self::S) !== 0) {
                [, , $valor] = $this->valorDe($id, $a, $fecha, $precios);
                $total = bcadd($total, $valor, self::S);
            }
        }

        return $total;
    }

    public function efectivo(): string
    {
        return $this->efectivo;
    }

    /**
     * Estado completo a una fecha: posiciones valuadas y totales.
     *
     * @return array{posiciones: list<array<string, mixed>>, efectivo: string, valor_posiciones: string, valor_total: string,
     *               aportaciones_netas: string, gp_no_realizada: string, gp_realizada: string, gp_total: string,
     *               ingresos: string, depositos: string, retiros: string, dividendos: string, intereses: string,
     *               comisiones: string, otros: string}
     */
    public function valuar(string $fecha, PriceBook $precios): array
    {
        $posiciones = [];
        $valorPosiciones = '0';
        $noRealizada = '0';
        $realizada = '0';

        foreach ($this->activos as $id => $a) {
            [$precio, $tasa, $valor] = $this->valorDe($id, $a, $fecha, $precios);
            $abierta = bccomp($a['cantidad'], '0', self::S) !== 0;
            $gpNoRealizada = $abierta ? bcsub($valor, $a['costo_base'], self::S) : '0';

            $posiciones[] = [
                'asset_id' => $id,
                'moneda' => $a['moneda'],
                'cantidad' => $a['cantidad'],
                'costo_base' => $a['costo_base'],
                'costo_promedio_local' => $abierta ? bcdiv($a['costo_local'], $a['cantidad'], self::S) : null,
                'costo_promedio_base' => $abierta ? bcdiv($a['costo_base'], $a['cantidad'], self::S) : null,
                'precio' => $precio,
                'tasa' => $tasa,
                'valor_base' => $valor,
                'gp_no_realizada' => $gpNoRealizada,
                'gp_no_realizada_pct' => self::razon($gpNoRealizada, $a['costo_base']),
                'gp_realizada' => $a['realizada'],
                'ingresos' => $a['ingresos'],
                'invertido_base' => $a['invertido_base'],
                // Rendimiento de la posición: todo lo ganado ÷ todo lo invertido en ella.
                'rendimiento_pct' => self::razon(
                    bcadd(bcadd($gpNoRealizada, $a['realizada'], self::S), $a['ingresos'], self::S),
                    $a['invertido_base'],
                ),
            ];

            $valorPosiciones = bcadd($valorPosiciones, $valor, self::S);
            $noRealizada = bcadd($noRealizada, $gpNoRealizada, self::S);
            $realizada = bcadd($realizada, $a['realizada'], self::S);
        }

        $valorTotal = bcadd($valorPosiciones, $this->efectivo, self::S);
        $aportaciones = bcsub($this->totales['depositos'], $this->totales['retiros'], self::S);

        return [
            'posiciones' => $posiciones,
            'efectivo' => $this->efectivo,
            'valor_posiciones' => $valorPosiciones,
            'valor_total' => $valorTotal,
            'aportaciones_netas' => $aportaciones,
            'gp_no_realizada' => $noRealizada,
            'gp_realizada' => $realizada,
            'gp_total' => bcsub($valorTotal, $aportaciones, self::S),
            'ingresos' => bcadd($this->totales['dividendos'], $this->totales['intereses'], self::S),
        ] + $this->totales;
    }

    /** @return array{0: ?string, 1: ?string, 2: string} [precio, tasa, valor en base] */
    private function valorDe(int|string $id, array $a, string $fecha, PriceBook $precios): array
    {
        // Sin cierre registrado se usa el precio de la última operación del activo.
        $precio = $precios->precio($id, $fecha) ?? $a['ultimo_precio'];
        // Sin tipo de cambio registrado se usa el de la última transacción en esa moneda.
        $tasa = $precios->tasa($a['moneda'], $this->monedaBase, $fecha) ?? $this->ultimoTc[$a['moneda']] ?? null;

        if ($precio === null || $tasa === null || bccomp($a['cantidad'], '0', self::S) === 0) {
            return [$precio, $tasa, '0'];
        }

        return [$precio, $tasa, bcmul(bcmul($a['cantidad'], $precio, self::S_INT), $tasa, self::S)];
    }

    private static function razon(string $numerador, string $denominador): ?float
    {
        return bccomp($denominador, '0', self::S) > 0 ? (float) bcdiv($numerador, $denominador, 10) : null;
    }
}
