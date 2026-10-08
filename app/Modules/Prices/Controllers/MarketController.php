<?php

namespace App\Modules\Prices\Controllers;

use App\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Assets\Models\Asset;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Prices\Models\PriceAlert;
use App\Modules\Prices\Requests\PriceAlertRequest;
use App\Modules\Prices\Requests\WatchlistRequest;
use App\Modules\Prices\Services\PriceAlertService;
use App\Modules\Prices\Services\QuoteService;
use App\Modules\Transactions\Models\Transaction;
use App\Modules\Users\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Mercado en vivo: los activos de la cartera del usuario más los de su
 * watchlist. La página se actualiza sola consultando cotizaciones() cada pocos segundos.
 */
class MarketController extends Controller
{
    public function index(Request $request): View
    {
        $mios = self::enCartera();
        $seguidos = $request->user()->watchlist()->pluck('assets.id')->all();
        $activos = self::visibles($request->user());

        return view('mercado', [
            // Primero los activos que el usuario tiene en sus portafolios.
            'activos' => $activos->sortByDesc(fn (Asset $a) => in_array($a->id, $mios, true))->values(),
            'mios' => $mios,
            'seguidos' => $seguidos,
            'disponibles' => Asset::where('tipo', '!=', 'efectivo')->whereNotIn('id', $activos->pluck('id'))->orderBy('simbolo')->get(['id', 'simbolo', 'nombre', 'exchange']),
            // Primero las activas; luego las disparadas, de la más reciente a la más vieja.
            'alertas' => PriceAlert::de($request->user()->id)->with('asset:id,simbolo,moneda')->orderByRaw('disparada_en IS NOT NULL')->orderByDesc('disparada_en')->orderBy('id')->get(),
        ]);
    }

    /**
     * GET /api/v1/mercado/cotizaciones: lo que el usuario ve en Mercado, o los activos de un portafolio (?portfolio=ID).
     * En Mercado también revisa las alertas del usuario con el precio del momento; las que se disparan
     * vienen en "alertas" para avisarle sin recargar.
     */
    public function cotizaciones(Request $request, QuoteService $cotizaciones, PriceAlertService $alertas): JsonResponse
    {
        if ($request->filled('portfolio')) {
            $portfolio = Portfolio::findOrFail($request->integer('portfolio'));
            Gate::authorize('view', $portfolio);
            $activos = Asset::whereIn('id', $portfolio->transactions()->whereNotNull('asset_id')->select('asset_id'))->get();

            return ApiResponse::ok($cotizaciones->cotizar($activos));
        }

        $datos = $cotizaciones->cotizar(self::visibles($request->user()), conIndices: true);
        $precios = array_map(fn ($c) => $c['precio'], array_filter($datos['activos']));
        $datos['alertas'] = $alertas->revisar($precios, $request->user()->id)
            ->map(fn (PriceAlert $a) => ['id' => $a->id, 'mensaje' => PriceAlertService::mensaje($a)])->all();

        return ApiResponse::ok($datos);
    }

    public function crearAlerta(PriceAlertRequest $request): RedirectResponse
    {
        $alerta = PriceAlert::create($request->validated() + ['user_id' => $request->user()->id]);

        return redirect()->route('mercado')->with('status', 'Alerta creada: te avisamos cuando '.$alerta->asset->simbolo.' '
            .($alerta->condicion === PriceAlert::ARRIBA ? 'suba' : 'baje').' a '.number_format((float) $alerta->precio, 2, '.', ',').' '.$alerta->asset->moneda.'.');
    }

    public function borrarAlerta(Request $request, PriceAlert $alerta): RedirectResponse
    {
        abort_unless($alerta->user_id === $request->user()->id, 404);
        $alerta->delete();

        return redirect()->route('mercado')->with('status', 'Alerta eliminada.');
    }

    /** "Entendido": los avisos de alertas disparadas dejan de mostrarse. */
    public function alertasVistas(Request $request): RedirectResponse
    {
        PriceAlert::de($request->user()->id)->sinVer()->update(['vista_en' => now()]);

        return back();
    }

    public function seguir(WatchlistRequest $request): RedirectResponse
    {
        $request->user()->watchlist()->syncWithoutDetaching([$request->integer('asset_id')]);

        return redirect()->route('mercado')->with('status', 'Activo agregado a tu lista.');
    }

    public function dejarDeSeguir(Request $request, Asset $asset): RedirectResponse
    {
        $request->user()->watchlist()->detach($asset->id);

        return redirect()->route('mercado')->with('status', "{$asset->simbolo} ya no está en tu lista.");
    }

    /** @return list<int> activos con movimientos en los portafolios del usuario (el Global Scope limita a los suyos) */
    private static function enCartera(): array
    {
        return Transaction::whereNotNull('asset_id')->distinct()->pluck('asset_id')->all();
    }

    /** @return Collection<int, Asset> los de su cartera más los de su watchlist, por símbolo */
    private static function visibles(User $user): Collection
    {
        return Asset::where('tipo', '!=', 'efectivo')
            ->where(fn ($q) => $q->whereIn('id', self::enCartera())->orWhereIn('id', $user->watchlist()->select('assets.id')))
            ->orderBy('simbolo')
            ->get();
    }
}
