<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Portafolio;
use App\Models\Usuario;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    /**
     * Listado de usuarios con el resumen de su cartera.
     * Solo accesible para administradores (middleware 'admin').
     */
    public function index(Request $request)
    {
        $usuarios = Usuario::with(['rol', 'portafolios.activos'])
            ->orderBy('nombre')
            ->get()
            ->map(function (Usuario $usuario) {
                $resumen = $this->resumenCartera($usuario->portafolios);

                return [
                    'id'               => $usuario->id,
                    'nombre'           => $usuario->nombre,
                    'apellido'         => $usuario->apellido,
                    'email'            => $usuario->email,
                    'telefono'         => $usuario->telefono,
                    'pais'             => $usuario->pais,
                    'foto_perfil_url'  => $usuario->foto_perfil_url,
                    'activo'           => $usuario->activo,
                    'email_verificado' => $usuario->email_verificado,
                    'ultimo_login'     => $usuario->ultimo_login,
                    'rol'              => $usuario->rol,
                    'portafolios_count' => $usuario->portafolios->count(),
                    'activos_count'    => $resumen['activos_count'],
                    'valor_invertido'  => $resumen['valor_invertido'],
                    'valor_actual'     => $resumen['valor_actual'],
                    'rendimiento'      => $resumen['rendimiento'],
                    'portafolios'      => $usuario->portafolios->map(fn (Portafolio $p) => [
                        'id'              => $p->id,
                        'nombre'          => $p->nombre,
                        'color'           => $p->color,
                        'valor_invertido' => $this->invertidoDe($p),
                        'valor_actual'    => $this->actualDe($p),
                    ])->values(),
                ];
            });

        return response()->json($usuarios);
    }

    /**
     * Detalle de un usuario: sus portafolios con activos, transacciones y alertas.
     */
    public function show(Request $request, string $id)
    {
        $usuario = Usuario::with([
            'rol',
            'portafolios.activos',
            'alertas.activo',
        ])->findOrFail($id);

        $transacciones = $usuario->transacciones()
            ->with(['activo', 'portafolio'])
            ->latest('created_at')
            ->limit(50)
            ->get();

        $resumen = $this->resumenCartera($usuario->portafolios);

        return response()->json([
            'id'               => $usuario->id,
            'nombre'           => $usuario->nombre,
            'apellido'         => $usuario->apellido,
            'email'            => $usuario->email,
            'telefono'         => $usuario->telefono,
            'pais'             => $usuario->pais,
            'foto_perfil_url'  => $usuario->foto_perfil_url,
            'activo'           => $usuario->activo,
            'email_verificado' => $usuario->email_verificado,
            'ultimo_login'     => $usuario->ultimo_login,
            'rol'              => $usuario->rol,
            'portafolios_count' => $usuario->portafolios->count(),
            'activos_count'    => $resumen['activos_count'],
            'valor_invertido'  => $resumen['valor_invertido'],
            'valor_actual'     => $resumen['valor_actual'],
            'rendimiento'      => $resumen['rendimiento'],
            'portafolios'      => $usuario->portafolios->map(fn (Portafolio $p) => $this->detallePortafolio($p))->values(),
            'transacciones'    => $transacciones,
            'alertas'          => $usuario->alertas,
        ]);
    }

    /**
     * Normaliza un portafolio con sus activos y su valuación.
     */
    private function detallePortafolio(Portafolio $portafolio): array
    {
        $activos = $portafolio->activos->map(function ($activo) {
            $cantidad = (float) $activo->pivot->cantidad;
            $precio   = (float) ($activo->pivot->precio_promedio ?? 0);

            return [
                'id'              => $activo->id,
                'simbolo'         => $activo->simbolo,
                'nombre'          => $activo->nombre,
                'tipo'            => $activo->tipo,
                'logo_url'        => $activo->logo_url,
                'cantidad'        => $cantidad,
                'precio_promedio' => $precio,
                'valor_invertido' => round($cantidad * $precio, 2),
            ];
        })->values();

        $invertido = $this->invertidoDe($portafolio);
        $actual    = $this->actualDe($portafolio);

        return [
            'id'              => $portafolio->id,
            'usuario_id'      => $portafolio->usuario_id,
            'nombre'          => $portafolio->nombre,
            'descripcion'     => $portafolio->descripcion,
            'estrategia'      => $portafolio->estrategia,
            'moneda'          => $portafolio->moneda,
            'benchmark'       => $portafolio->benchmark,
            'color'           => $portafolio->color,
            'es_publico'      => $portafolio->es_publico,
            'activo'          => $portafolio->activo,
            'created_at'      => $portafolio->created_at,
            'valor_invertido' => $invertido,
            'valor_actual'    => round($actual, 2),
            'rendimiento'     => $invertido > 0 ? round((($actual - $invertido) / $invertido) * 100, 2) : 0.0,
            'activos'         => $activos,
        ];
    }

    /**
     * Capital aportado a un portafolio: suma de cantidad x precio promedio.
     */
    private function invertidoDe(Portafolio $portafolio): float
    {
        $total = 0.0;

        foreach ($portafolio->activos as $activo) {
            $total += (float) $activo->pivot->cantidad * (float) ($activo->pivot->precio_promedio ?? 0);
        }

        return round($total, 2);
    }

    /**
     * Valuación actual del portafolio; si no está calculada, cae al invertido.
     */
    private function actualDe(Portafolio $portafolio): float
    {
        $valor = (float) $portafolio->valor_actual;

        return round($valor > 0 ? $valor : $this->invertidoDe($portafolio), 2);
    }

    /**
     * Agrega la valuación de todos los portafolios de un usuario.
     */
    private function resumenCartera($portafolios): array
    {
        $invertido = 0.0;
        $actual    = 0.0;
        $simbolos  = [];

        foreach ($portafolios as $portafolio) {
            foreach ($portafolio->activos as $activo) {
                $simbolos[$activo->id] = true;
            }

            $invertido += $this->invertidoDe($portafolio);
            $actual    += $this->actualDe($portafolio);
        }

        return [
            'activos_count'   => count($simbolos),
            'valor_invertido' => round($invertido, 2),
            'valor_actual'    => round($actual, 2),
            'rendimiento'     => $invertido > 0 ? round((($actual - $invertido) / $invertido) * 100, 2) : 0.0,
        ];
    }
}
