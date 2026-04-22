<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activo;
use Illuminate\Http\Request;

class ActivoController extends Controller
{
    public function index(Request $request)
    {
        $activos = Activo::where('activo', true)
            ->when($request->tipo, fn($q) => $q->where('tipo', $request->tipo))
            ->when($request->search, fn($q) => $q->where('nombre', 'like', "%{$request->search}%")
                                                   ->orWhere('simbolo', 'like', "%{$request->search}%"))
            ->get();

        return response()->json($activos);
    }

    public function store(Request $request)
    {
        $request->validate([
            'simbolo' => 'required|string|max:20|unique:activos',
            'nombre'  => 'required|string|max:150',
            'tipo'    => 'required|in:crypto,accion,etf,bono,commodity,fiat',
        ]);

        $activo = Activo::create($request->only(['simbolo', 'nombre', 'tipo', 'descripcion', 'exchange', 'moneda_base', 'decimales', 'logo_url']));

        return response()->json($activo, 201);
    }

    public function show(string $id)
    {
        $activo = Activo::with(['historialPrecios' => fn($q) => $q->orderBy('timestamp', 'desc')->limit(30)])
            ->findOrFail($id);

        return response()->json($activo);
    }

    public function update(Request $request, string $id)
    {
        $activo = Activo::findOrFail($id);
        $activo->update($request->only(['nombre', 'descripcion', 'exchange', 'activo', 'logo_url']));
        return response()->json($activo);
    }

    public function destroy(string $id)
    {
        Activo::findOrFail($id)->delete();
        return response()->json(['message' => 'Activo eliminado.']);
    }
}
