@extends('layouts.app')

@section('title', 'Editar transacción')

@section('content')
    <p class="muted"><a href="{{ route('portfolios.show', $portfolio) }}">{{ $portfolio->nombre }}</a> /</p>
    <h1>Editar transacción</h1>
    <form method="POST" action="{{ route('transactions.update', $transaction) }}" class="narrow dos-columnas">
        @method('PUT')
        @include('transactions._form', ['transaction' => $transaction])
        <div class="actions">
            <button type="submit">Guardar cambios</button>
            <a class="btn btn-secondary" href="{{ route('portfolios.show', $portfolio) }}">Cancelar</a>
        </div>
    </form>
@endsection
