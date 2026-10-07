<?php

namespace App\Modules\Imports\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Imports\Requests\ImportRequest;
use App\Modules\Imports\Services\ImportService;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Transactions\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends Controller
{
    public function __construct(private ImportService $service) {}

    public function create(Portfolio $portfolio): View
    {
        Gate::authorize('create', [Transaction::class, $portfolio]);

        return view('imports.create', ['portfolio' => $portfolio]);
    }

    /** Paso 1: lee el archivo y muestra la vista previa. Si todo es válido, la guarda en sesión para confirmar. */
    public function preview(ImportRequest $request, Portfolio $portfolio): View
    {
        $vista = $this->service->previsualizar($portfolio, $request->file('archivo')->get(), $request->user());

        $request->session()->forget(self::clave($portfolio));
        if ($vista['valido']) {
            $request->session()->put(self::clave($portfolio), $vista['filas']);
        }

        return view('imports.preview', ['portfolio' => $portfolio, 'archivo' => $request->file('archivo')->getClientOriginalName()] + $vista);
    }

    /** Paso 2: registra lo de la vista previa (se vuelve a validar contra el libro actual). */
    public function confirm(Request $request, Portfolio $portfolio): RedirectResponse
    {
        Gate::authorize('create', [Transaction::class, $portfolio]);

        $filas = $request->session()->pull(self::clave($portfolio));
        if ($filas === null) {
            return redirect()->route('portfolios.importar', $portfolio)
                ->withErrors(['general' => 'La vista previa ya no está disponible; vuelve a subir el archivo.']);
        }

        try {
            $total = $this->service->importar($portfolio, $filas, $request->user());
        } catch (ValidationException $e) {
            return redirect()->route('portfolios.importar', $portfolio)->withErrors($e->errors());
        }

        return redirect()->route('portfolios.show', $portfolio)->with('status', "Se importaron {$total} transacciones.");
    }

    public function plantilla(Portfolio $portfolio): StreamedResponse
    {
        Gate::authorize('create', [Transaction::class, $portfolio]);

        $filas = [
            ['tipo', 'fecha', 'simbolo', 'exchange', 'cantidad', 'precio', 'comision', 'moneda', 'tipo_cambio', 'nota'],
            ['DEPOSITO', '2026-01-05', '', '', '100000', '', '', '', '', 'Aportación inicial'],
            ['COMPRA', '2026-01-06', 'NAFTRAC', 'BMV', '500', '58.20', '25', '', '', ''],
            ['COMPRA', '2026-01-06', 'AAPL', 'NASDAQ', '10', '245.10', '1', '', '17.95', 'Tipo de cambio opcional'],
            ['DIVIDENDO', '2026-03-15', 'NAFTRAC', 'BMV', '320.50', '', '', '', '', ''],
        ];

        return response()->streamDownload(function () use ($filas) {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF"); // BOM: Excel abre los acentos bien
            foreach ($filas as $fila) {
                fputcsv($salida, $fila, ',', '"', '');
            }
            fclose($salida);
        }, 'plantilla-transacciones.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private static function clave(Portfolio $portfolio): string
    {
        return "importacion.{$portfolio->id}";
    }
}
