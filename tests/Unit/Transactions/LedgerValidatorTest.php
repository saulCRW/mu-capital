<?php

namespace Tests\Unit\Transactions;

use App\Modules\Transactions\Services\LedgerValidator;
use PHPUnit\Framework\TestCase;

class LedgerValidatorTest extends TestCase
{
    private LedgerValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new LedgerValidator;
    }

    /** Atajo para escribir movimientos en las pruebas. */
    private function m(int $id, string $tipo, string $fecha, ?int $activo, string $cantidad, string $precio = '1', string $comision = '0', string $tc = '1'): array
    {
        return [
            'id' => $id, 'tipo' => $tipo, 'fecha' => $fecha, 'asset_id' => $activo, 'simbolo' => $activo ? "ACT{$activo}" : null,
            'cantidad' => $cantidad, 'precio' => $precio, 'comision' => $comision, 'tipo_cambio' => $tc,
        ];
    }

    /**
     * Ejemplo verificable a mano (moneda base MXN, tc = 1):
     *   DEPOSITO 10,000                          → 10,000
     *   COMPRA 10 @ 500, comisión 25  (−5,025)    →  4,975
     *   VENTA   4 @ 600, comisión 12  (+2,388)    →  7,363
     *   DIVIDENDO 50                              →  7,413
     *   RETIRO 1,000                              →  6,413
     *   COMISION 13                               →  6,400
     *   Posición: 10 − 4 = 6 títulos
     */
    public function test_ejemplo_verificable_a_mano(): void
    {
        $r = $this->validator->reproducir([
            $this->m(1, 'DEPOSITO', '2026-01-02', null, '10000'),
            $this->m(2, 'COMPRA', '2026-01-03', 7, '10', '500', '25'),
            $this->m(3, 'VENTA', '2026-02-03', 7, '4', '600', '12'),
            $this->m(4, 'DIVIDENDO', '2026-03-03', 7, '50'),
            $this->m(5, 'RETIRO', '2026-04-03', null, '1000'),
            $this->m(6, 'COMISION', '2026-05-04', null, '13'),
        ], 'MXN');

        $this->assertNull($r['error']);
        $this->assertSame('6400.000000', $r['efectivo']);
        $this->assertSame('6.000000', $r['posiciones'][7]);
    }

    public function test_no_permite_vender_mas_de_lo_que_se_tiene(): void
    {
        $error = $this->validator->primerError([
            $this->m(1, 'DEPOSITO', '2026-01-02', null, '10000'),
            $this->m(2, 'COMPRA', '2026-01-03', 7, '10', '100'),
            $this->m(3, 'VENTA', '2026-01-10', 7, '11', '100'),
        ], 'MXN');

        $this->assertSame('El 10/01/2026 se venderían 11 ACT7, pero en esa fecha solo se tendrían 10.', $error);
    }

    public function test_no_permite_gastar_efectivo_inexistente(): void
    {
        $error = $this->validator->primerError([
            $this->m(1, 'DEPOSITO', '2026-01-02', null, '1000'),
            $this->m(2, 'COMPRA', '2026-01-03', 7, '10', '100', '5'), // cuesta 1,005
        ], 'MXN');

        $this->assertSame('El 03/01/2026 el efectivo quedaría en -5.00 MXN. No se puede gastar efectivo inexistente.', $error);
    }

    public function test_borrar_un_deposito_antiguo_rompe_una_compra_posterior(): void
    {
        // Libro original válido: depósito en enero paga la compra de febrero.
        $libro = [
            $this->m(1, 'DEPOSITO', '2026-01-02', null, '5000'),
            $this->m(2, 'COMPRA', '2026-02-02', 7, '10', '400'),
        ];
        $this->assertNull($this->validator->primerError($libro, 'MXN'));

        // Sin el depósito (simula borrarlo) la compra de febrero ya no tiene fondos.
        $this->assertNotNull($this->validator->primerError([$libro[1]], 'MXN'));
    }

    public function test_venta_fechada_antes_de_la_compra_es_rechazada(): void
    {
        $error = $this->validator->primerError([
            $this->m(1, 'DEPOSITO', '2026-01-02', null, '5000'),
            $this->m(2, 'COMPRA', '2026-03-01', 7, '10', '100'),
            $this->m(3, 'VENTA', '2026-02-01', 7, '5', '100'),
        ], 'MXN');

        $this->assertStringStartsWith('El 01/02/2026 se venderían 5 ACT7', $error);
    }

    public function test_split_multiplica_la_posicion(): void
    {
        $base = [
            $this->m(1, 'DEPOSITO', '2026-01-02', null, '5000'),
            $this->m(2, 'COMPRA', '2026-01-05', 7, '10', '100'),
            $this->m(3, 'SPLIT', '2026-02-02', 7, '2', '0'),
        ];

        $r = $this->validator->reproducir([...$base, $this->m(4, 'VENTA', '2026-03-02', 7, '20', '50')], 'MXN');
        $this->assertNull($r['error']);
        $this->assertSame('0.000000', $r['posiciones'][7]);

        $this->assertNotNull($this->validator->primerError([...$base, $this->m(4, 'VENTA', '2026-03-02', 7, '21', '50')], 'MXN'));
    }

    public function test_en_el_mismo_dia_las_entradas_van_antes_que_las_salidas(): void
    {
        // La compra se capturó primero (id menor) pero el depósito del mismo día la fondea.
        $this->assertNull($this->validator->primerError([
            $this->m(1, 'COMPRA', '2026-01-05', 7, '10', '100'),
            $this->m(2, 'DEPOSITO', '2026-01-05', null, '1000'),
        ], 'MXN'));

        // Rebalanceo: vender A y comprar B el mismo día con lo obtenido.
        $this->assertNull($this->validator->primerError([
            $this->m(1, 'DEPOSITO', '2026-01-02', null, '1000'),
            $this->m(2, 'COMPRA', '2026-01-03', 7, '10', '100'),
            $this->m(3, 'COMPRA', '2026-02-02', 8, '5', '120'),
            $this->m(4, 'VENTA', '2026-02-02', 7, '10', '100'),
        ], 'MXN'));
    }

    /**
     * Con tipo de cambio (base MXN):
     *   DEPOSITO 1,000 MXN                               → 1,000
     *   COMPRA 10 @ 5 USD, tc 18 → 10 × 5 × 18 = 900      →   100
     *   COMPRA  1 @ 6 USD, tc 18 → 108 > 100              → error
     */
    public function test_convierte_a_moneda_base_con_el_tipo_de_cambio(): void
    {
        $libro = [
            $this->m(1, 'DEPOSITO', '2026-01-02', null, '1000'),
            $this->m(2, 'COMPRA', '2026-01-03', 7, '10', '5', '0', '18'),
        ];
        $this->assertSame('100.000000', $this->validator->reproducir($libro, 'MXN')['efectivo']);

        $libro[] = $this->m(3, 'COMPRA', '2026-01-04', 7, '1', '6', '0', '18');
        $this->assertStringContainsString('-8.00 MXN', $this->validator->primerError($libro, 'MXN'));
    }

    public function test_efecto_en_efectivo_de_cada_tipo(): void
    {
        $this->assertSame('100.000000', LedgerValidator::efectoEnEfectivo($this->m(1, 'DEPOSITO', '2026-01-01', null, '100')));
        $this->assertSame('-100.000000', LedgerValidator::efectoEnEfectivo($this->m(1, 'RETIRO', '2026-01-01', null, '100')));
        $this->assertSame('-1025.000000', LedgerValidator::efectoEnEfectivo($this->m(1, 'COMPRA', '2026-01-01', 7, '10', '100', '25')));
        $this->assertSame('975.000000', LedgerValidator::efectoEnEfectivo($this->m(1, 'VENTA', '2026-01-01', 7, '10', '100', '25')));
        $this->assertSame('30.000000', LedgerValidator::efectoEnEfectivo($this->m(1, 'DIVIDENDO', '2026-01-01', 7, '30')));
        $this->assertSame('12.500000', LedgerValidator::efectoEnEfectivo($this->m(1, 'INTERES', '2026-01-01', null, '12.5')));
        $this->assertSame('-7.000000', LedgerValidator::efectoEnEfectivo($this->m(1, 'COMISION', '2026-01-01', null, '7')));
        $this->assertSame('0.000000', LedgerValidator::efectoEnEfectivo($this->m(1, 'SPLIT', '2026-01-01', 7, '2', '0')));
    }
}
