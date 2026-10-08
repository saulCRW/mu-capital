@extends('layouts.app')

@section('title', 'Plan')

@section('content')
    <h1>Plan {{ $plan->nombre }}</h1>
    <p class="muted">Límites de tu cuenta. Al llegar a uno puedes seguir consultando y editando, pero no agregar más.</p>

    @include('billing.partials.uso')
@endsection
