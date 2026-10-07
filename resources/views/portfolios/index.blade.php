@extends('layouts.app')

@section('title', 'Mis portafolios')

@section('content')
    <div class="page-head">
        <h1>Mis portafolios</h1>
        <a class="btn" href="{{ route('portfolios.create') }}">Nuevo portafolio</a>
    </div>

    @if ($portfolios->isEmpty())
        <p class="muted">Todavía no tienes portafolios. Crea uno y registra tu primer depósito, o importa tus movimientos desde un CSV.</p>
    @else
        <table>
            <thead>
                <tr><th>Nombre</th><th>Moneda base</th><th>Benchmark</th><th class="num">Transacciones</th><th></th></tr>
            </thead>
            <tbody>
                @foreach ($portfolios as $p)
                    <tr>
                        <td><a href="{{ route('portfolios.show', $p) }}">{{ $p->nombre }}</a></td>
                        <td>{{ $p->moneda_base }}</td>
                        <td>{{ $p->benchmark?->nombre ?? '—' }}</td>
                        <td class="num">{{ number_format($p->transactions_count) }}</td>
                        <td><a href="{{ route('portfolios.reporte', $p) }}">Reporte</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
