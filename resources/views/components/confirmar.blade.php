{{--
    Botón que pide confirmación en un modal antes de enviar una acción irreversible (por omisión, DELETE).
    Uso: <x-confirmar :accion="route('portfolios.destroy', $p)" titulo="Eliminar portafolio" mensaje="..." />
--}}
@props(['accion', 'titulo', 'mensaje', 'boton' => 'Eliminar', 'metodo' => 'DELETE', 'clase' => 'btn-link'])
@php($id = 'confirmar-'.md5($accion.$metodo))
<button type="button" class="{{ $clase }}" data-abrir-modal="{{ $id }}">{{ $boton }}</button>
<x-modal :id="$id" :titulo="$titulo">
    <p>{{ $mensaje }}</p>
    <x-slot:pie>
        <button type="button" class="btn btn-secondary" data-cerrar-modal>Cancelar</button>
        <form method="POST" action="{{ $accion }}">
            @csrf
            @method($metodo)
            <button type="submit" class="btn btn-danger">{{ $boton }}</button>
        </form>
    </x-slot:pie>
</x-modal>
