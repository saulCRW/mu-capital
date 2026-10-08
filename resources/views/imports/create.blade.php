@extends('layouts.app')

@section('title', 'Importar transacciones')

@section('content')
    <p class="muted"><a href="{{ route('portfolios.show', $portfolio) }}">{{ $portfolio->nombre }}</a> /</p>
    <h1>Importar transacciones</h1>
    <p class="muted">Sube un archivo CSV o Excel (.xlsx) (por ejemplo, el estado de cuenta de tu broker). Antes de guardar verás una vista previa con lo que se va a registrar y los errores de cada fila.</p>

    <form method="POST" action="{{ route('portfolios.importar.preview', $portfolio) }}" enctype="multipart/form-data" class="narrow">
        @csrf
        <label for="archivo">Archivo CSV o Excel</label>
        <input id="archivo" type="file" name="archivo" accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required style="padding-top:6px">
        @error('archivo') <div class="error">{{ $message }}</div> @enderror
        <div class="actions">
            <button type="submit">Ver vista previa</button>
            <a class="btn btn-secondary" href="{{ route('portfolios.show', $portfolio) }}">Cancelar</a>
        </div>
    </form>

    <h2>Formato</h2>
    <p><a href="{{ route('portfolios.importar.plantilla', $portfolio) }}">Descargar plantilla de ejemplo</a></p>
    <div style="overflow-x:auto">
        <table style="max-width:820px">
            <thead><tr><th>Columna</th><th>Cuándo</th><th>Ejemplo</th></tr></thead>
            <tbody>
                <tr><td>tipo</td><td>Siempre: {{ implode(', ', \App\Modules\Transactions\Models\Transaction::TIPOS) }}</td><td>COMPRA</td></tr>
                <tr><td>fecha</td><td>Siempre: AAAA-MM-DD o DD/MM/AAAA</td><td>06/01/2026</td></tr>
                <tr><td>cantidad</td><td>Siempre: títulos en compras y ventas, factor en splits, monto en lo demás</td><td>500</td></tr>
                <tr><td>simbolo</td><td>COMPRA, VENTA, DIVIDENDO y SPLIT; debe existir en Activos</td><td>NAFTRAC</td></tr>
                <tr><td>exchange</td><td>Solo si hay varios activos con el mismo símbolo</td><td>BMV</td></tr>
                <tr><td>precio</td><td>COMPRA y VENTA, en la moneda del activo</td><td>58.20</td></tr>
                <tr><td>comision, moneda, tipo_cambio, nota</td><td>Opcionales (sin tipo de cambio se usa el último registrado)</td><td>25</td></tr>
            </tbody>
        </table>
    </div>
    <p class="help">En Excel se lee la primera hoja, con el encabezado en la fila 1; las fechas pueden ir con formato de fecha. En CSV: separador coma, punto y coma o tabulador; números con punto decimal (se aceptan comas de miles). Hasta {{ number_format(\App\Modules\Imports\Services\CsvTransactionParser::MAX_FILAS) }} filas por archivo.</p>
@endsection
