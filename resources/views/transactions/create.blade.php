@extends('layouts.app')

@section('title', 'Nueva transacción')

@section('content')
    <p class="muted"><a href="{{ route('portfolios.show', $portfolio) }}">{{ $portfolio->nombre }}</a> /</p>
    <h1>Nueva transacción</h1>
    <form method="POST" action="{{ route('portfolios.transactions.store', $portfolio) }}" class="narrow">
        @include('transactions._form', ['transaction' => null])
        <div class="actions">
            <button type="submit">Registrar</button>
            <a class="btn btn-secondary" href="{{ route('portfolios.show', $portfolio) }}">Cancelar</a>
        </div>
    </form>
@endsection
