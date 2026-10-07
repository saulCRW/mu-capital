{{--
    Tema claro/oscuro. Va en el <head> para aplicarse antes de pintar (sin parpadeo):
    usa la preferencia guardada en este navegador o, si no hay, la del sistema.
    Cualquier botón con [data-cambiar-tema] la alterna y la guarda.
--}}
<script>
    (() => {
        const raiz = document.documentElement;
        let guardado = null;
        try { guardado = localStorage.getItem('mu-tema'); } catch (e) {}
        const sistemaOscuro = window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches;
        raiz.dataset.theme = guardado ?? (sistemaOscuro ? 'dark' : 'light');

        document.addEventListener('click', (evento) => {
            const boton = evento.target.closest('[data-cambiar-tema]');
            if (!boton) return;
            raiz.dataset.theme = raiz.dataset.theme === 'dark' ? 'light' : 'dark';
            try { localStorage.setItem('mu-tema', raiz.dataset.theme); } catch (e) {}
        });
    })();
</script>
<style>
    .tema-boton { display: inline-flex; align-items: center; gap: 8px; }
    .tema-boton .claro, :root[data-theme="dark"] .tema-boton .oscuro { display: none; }
    :root[data-theme="dark"] .tema-boton .claro { display: inline-flex; }
    .tema-boton svg { width: 16px; height: 16px; }
</style>
