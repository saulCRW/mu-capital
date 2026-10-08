{{--
    Modal sobre el <dialog> nativo del navegador (foco atrapado y Esc para cerrar ya incluidos).
    Se abre con cualquier botón [data-abrir-modal="id"] y se cierra con [data-cerrar-modal],
    con Esc o con clic fuera del cuadro.
    Uso: <x-modal id="ayuda" titulo="Ayuda"> contenido <x-slot:pie> botones </x-slot:pie> </x-modal>
--}}
@props(['id', 'titulo'])
<dialog id="{{ $id }}" class="modal" aria-labelledby="{{ $id }}-titulo">
    <div class="modal-head">
        <h2 id="{{ $id }}-titulo">{{ $titulo }}</h2>
        <button type="button" class="modal-cerrar" data-cerrar-modal aria-label="Cerrar">&times;</button>
    </div>
    <div class="modal-body">{{ $slot }}</div>
    @isset($pie)
        <div class="modal-pie">{{ $pie }}</div>
    @endisset
</dialog>
@once
    <script>
        document.addEventListener('click', (evento) => {
            const abrir = evento.target.closest('[data-abrir-modal]');
            if (abrir) {
                document.getElementById(abrir.dataset.abrirModal)?.showModal();
                return;
            }
            if (evento.target.closest('[data-cerrar-modal]')) {
                evento.target.closest('dialog')?.close();
                return;
            }
            // Clic en el fondo: el evento llega al propio <dialog>, no a su contenido.
            if (evento.target instanceof HTMLDialogElement && evento.target.classList.contains('modal')) {
                evento.target.close();
            }
        });
    </script>
@endonce
