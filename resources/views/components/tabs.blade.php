{{--
    Pestañas. Cada pestaña es un enlace (<a>); la activa lleva aria-current="page".
    Uso: <x-tabs etiqueta="Dimensión"><a href="..." aria-current="page">Clase</a> ...</x-tabs>
--}}
@props(['etiqueta'])
<nav {{ $attributes->merge(['class' => 'tabs']) }} aria-label="{{ $etiqueta }}">
    {{ $slot }}
</nav>
