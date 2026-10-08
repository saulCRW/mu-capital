{{-- Pestaña "Transacciones": tabla filtrable y paginada con alta, edición y baja. --}}
    <form method="GET" class="filters">
        <input type="hidden" name="vista" value="transacciones">
        <div>
            <label for="tipo">Tipo</label>
            <select id="tipo" name="tipo">
                <option value="">Todos</option>
                @foreach (\App\Modules\Transactions\Models\Transaction::TIPOS as $tipo)
                    <option value="{{ $tipo }}" @selected(($filtros['tipo'] ?? null) === $tipo)>{{ $tipo }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="asset_id">Activo</label>
            <select id="asset_id" name="asset_id">
                <option value="">Todos</option>
                @foreach ($activos as $activo)
                    <option value="{{ $activo->id }}" @selected((int) ($filtros['asset_id'] ?? 0) === $activo->id)>{{ $activo->simbolo }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="desde">Desde</label>
            <input id="desde" type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}">
        </div>
        <div>
            <label for="hasta">Hasta</label>
            <input id="hasta" type="date" name="hasta" value="{{ $filtros['hasta'] ?? '' }}">
        </div>
        <button type="submit" class="btn btn-secondary">Filtrar</button>
        <a href="{{ route('portfolios.show', [$portfolio, 'vista' => 'transacciones']) }}" class="btn btn-secondary">Limpiar</a>
    </form>

    @if ($transactions->isEmpty())
        <p class="muted">{{ $filtros ? 'Ninguna transacción coincide con los filtros.' : 'Este portafolio todavía no tiene transacciones. Empieza con un DEPOSITO.' }}</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Fecha</th><th>Tipo</th><th>Activo</th>
                    <th class="num">Cantidad</th><th class="num">Precio</th><th class="num">Comisión</th>
                    <th>Moneda</th><th class="num">Tipo cambio</th><th>Nota</th><th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($transactions as $t)
                    <tr>
                        <td class="num" style="text-align:left">{{ $t->fecha->format('d/m/Y') }}</td>
                        <td>{{ $t->tipo }}</td>
                        <td>
                            @if ($t->asset)
                                <a href="{{ route('portfolios.activo', [$portfolio, $t->asset_id]) }}">{{ $t->asset->simbolo }}</a>
                            @else
                                —
                            @endif
                        </td>
                        @if (in_array($t->tipo, $salidas, true))
                            <td class="num neg">-{{ $cantidad($t->cantidad) }}</td>
                        @else
                            <td class="num">{{ $cantidad($t->cantidad) }}</td>
                        @endif
                        <td class="num">{{ in_array($t->tipo, ['COMPRA', 'VENTA'], true) ? $dinero($t->precio) : '—' }}</td>
                        <td class="num">{{ (float) $t->comision ? $dinero($t->comision) : '—' }}</td>
                        <td>{{ $t->moneda }}</td>
                        <td class="num">{{ $t->moneda === $portfolio->moneda_base ? '—' : $cantidad($t->tipo_cambio) }}</td>
                        <td class="muted">{{ \Illuminate\Support\Str::limit($t->nota, 40) }}</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('transactions.edit', $t) }}">Editar</a>
                                <x-confirmar :accion="route('transactions.destroy', $t)" titulo="Eliminar transacción"
                                    :mensaje="'Se borrará esta transacción ('.$t->tipo.' del '.$t->fecha->format('d/m/Y').') y se recalculará el portafolio desde esa fecha.'" />
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{ $transactions->links('pagination::bootstrap-3') }}
    @endif
