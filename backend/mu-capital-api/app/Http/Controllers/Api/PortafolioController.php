<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Portafolio;
use Illuminate\Http\Request;

class PortafolioController extends Controller
{
    public function index(Request $request)
    {
        $portafolios = Portafolio::where('usuario_id', $request->user()->id)
            ->with('activos')
            ->get();

        return response()->json($portafolios);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre'     => 'required|string|max:150',
            'estrategia' => 'nullable|in:growth,income,balanced,especulativo',
            'moneda'     => 'nullable|string|max:10',
            'color'      => 'nullable|string|max:7',
        ]);

        $portafolio = Portafolio::create([
            ...$request->only(['nombre', 'descripcion', 'estrategia', 'moneda', 'benchmark', 'color', 'es_publico']),
            'usuario_id' => $request->user()->id,
        ]);

        return response()->json($portafolio, 201);
    }

    public function show(Request $request, string $id)
    {
        $portafolio = Portafolio::where('id', $id)
            ->where('usuario_id', $request->user()->id)
            ->with(['activos', 'transacciones.activo'])
            ->firstOrFail();

        return response()->json($portafolio);
    }

    public function update(Request $request, string $id)
    {
        $portafolio = Portafolio::where('id', $id)
            ->where('usuario_id', $request->user()->id)
            ->firstOrFail();

        $portafolio->update($request->only(['nombre', 'descripcion', 'estrategia', 'moneda', 'benchmark', 'color', 'es_publico', 'activo']));

        return response()->json($portafolio);
    }

    public function destroy(Request $request, string $id)
    {
        $portafolio = Portafolio::where('id', $id)
            ->where('usuario_id', $request->user()->id)
            ->firstOrFail();

        $portafolio->delete();

        return response()->json(['message' => 'Portafolio eliminado.']);
    }
}
