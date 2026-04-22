<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alerta;
use Illuminate\Http\Request;

class AlertaController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            Alerta::where('usuario_id', $request->user()->id)->with('activo')->get()
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'tipo'      => 'required|string|max:50',
            'nombre'    => 'required|string|max:150',
            'condicion' => 'required|array',
            'canal'     => 'nullable|in:app,email,sms,webhook',
        ]);

        $alerta = Alerta::create([
            ...$request->only(['activo_id', 'portafolio_id', 'tipo', 'nombre', 'descripcion', 'condicion', 'canal', 'webhook_url', 'expira_en']),
            'usuario_id' => $request->user()->id,
        ]);

        return response()->json($alerta, 201);
    }

    public function show(Request $request, string $id)
    {
        return response()->json(
            Alerta::where('id', $id)->where('usuario_id', $request->user()->id)->firstOrFail()
        );
    }

    public function update(Request $request, string $id)
    {
        $alerta = Alerta::where('id', $id)->where('usuario_id', $request->user()->id)->firstOrFail();
        $alerta->update($request->only(['nombre', 'descripcion', 'condicion', 'canal', 'activa', 'expira_en']));
        return response()->json($alerta);
    }

    public function destroy(Request $request, string $id)
    {
        Alerta::where('id', $id)->where('usuario_id', $request->user()->id)->firstOrFail()->delete();
        return response()->json(['message' => 'Alerta eliminada.']);
    }
}
