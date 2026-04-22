<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaccion;
use Illuminate\Http\Request;

class TransaccionController extends Controller
{
    public function index(Request $request)
    {
        $transacciones = Transaccion::where('usuario_id', $request->user()->id)
            ->with(['activo', 'portafolio'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($transacciones);
    }

    public function store(Request $request)
    {
        $request->validate([
            'portafolio_id'  => 'required|exists:portafolios,id',
            'activo_id'      => 'required|exists:activos,id',
            'tipo'           => 'required|in:compra,venta,deposito,retiro,dividendo,fee',
            'cantidad'       => 'required|numeric|min:0',
            'precio_unitario' => 'required|numeric|min:0',
            'monto_total'    => 'required|numeric|min:0',
        ]);

        $transaccion = Transaccion::create([
            ...$request->only(['portafolio_id', 'activo_id', 'tipo', 'estado', 'cantidad', 'precio_unitario', 'monto_total', 'comision', 'moneda', 'exchange', 'tx_hash', 'notas', 'ejecutada_en']),
            'usuario_id' => $request->user()->id,
        ]);

        return response()->json($transaccion->load('activo'), 201);
    }

    public function show(Request $request, string $id)
    {
        $transaccion = Transaccion::where('id', $id)
            ->where('usuario_id', $request->user()->id)
            ->with(['activo', 'portafolio'])
            ->firstOrFail();

        return response()->json($transaccion);
    }

    public function update(Request $request, string $id)
    {
        $transaccion = Transaccion::where('id', $id)
            ->where('usuario_id', $request->user()->id)
            ->firstOrFail();

        $transaccion->update($request->only(['estado', 'notas', 'ejecutada_en']));

        return response()->json($transaccion);
    }

    public function destroy(Request $request, string $id)
    {
        Transaccion::where('id', $id)
            ->where('usuario_id', $request->user()->id)
            ->firstOrFail()
            ->delete();

        return response()->json(['message' => 'Transacción eliminada.']);
    }
}
