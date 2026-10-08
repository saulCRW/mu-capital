{{--
    Sistema de diseño de MU Intelligence (PROYECTO.md, sección 7). Único archivo de tokens.
    - Tokens en :root (tema claro) y [data-theme="dark"] (tema oscuro). El tema lo decide
      layouts.partials.tema (preferencia guardada o la del sistema). Las páginas sin ese
      script (reporte imprimible y PDF) se quedan en claro.
    - Base neutra. El color solo con significado: --ok ganancia, --danger pérdida, --warn alerta.
      --accent es neutro a propósito: es la única variable de marca y se puede cambiar aquí.
    - Escala tipográfica 12 / 14 / 16 / 20 / 28 px; cuerpo 14 px; números tabulares.
    - Bordes de máximo 6 px; sin degradados ni sombras grandes (solo el modal flota).
    - Las vistas usan los tokens; ningún color de la interfaz va fijo en una vista.
    Componentes base: botón (button, .btn), input, tabla, KPI (.tiles/.tile), pestañas
    (<x-tabs>), modal (<x-modal>, <x-confirmar>) y skeleton (.skeleton).
--}}
<style>
    :root {
        color-scheme: light;
        --page: #f6f7f9;
        --bg: #ffffff;
        --bg-soft: #f3f4f6;
        --bg-hover: #f0f1f3;
        --border: #e3e5e8;
        --border-strong: #c9cdd3;
        --text: #111418;
        --muted: #5b6470;
        --accent: #1f2329;
        --accent-hover: #3a4049;
        --accent-soft: #eceef1;
        --on-accent: #ffffff;
        --ok: #137a3a;
        --ok-soft: #e8f5ec;
        --danger: #c0262d;
        --danger-soft: #fcecec;
        --on-danger: #ffffff;
        --warn: #a15c07;
        --warn-soft: #fdf3e2;
        --serie-0: #2a78d6;
        --serie-1: #eb6834;
        --shadow-modal: 0 8px 24px rgba(17, 20, 24, .16);
        --ring: 0 0 0 2px var(--bg), 0 0 0 4px var(--border-strong);
        --radius: 6px;
        --radius-sm: 4px;
        --fs-1: 12px;
        --fs-2: 14px;
        --fs-3: 16px;
        --fs-4: 20px;
        --fs-5: 28px;
    }
    :root[data-theme="dark"] {
        color-scheme: dark;
        --page: #0e1013;
        --bg: #15181c;
        --bg-soft: #1b1f24;
        --bg-hover: #20252b;
        --border: #2a2f36;
        --border-strong: #3a4049;
        --text: #e8eaed;
        --muted: #9aa2ad;
        --accent: #e8eaed;
        --accent-hover: #c9cdd3;
        --accent-soft: #23282e;
        --on-accent: #111418;
        --ok: #4ade80;
        --ok-soft: rgba(74, 222, 128, .12);
        --danger: #f87171;
        --danger-soft: rgba(248, 113, 113, .12);
        --on-danger: #111418;
        --warn: #fbbf24;
        --warn-soft: rgba(251, 191, 36, .12);
        --serie-0: #3987e5;
        --serie-1: #d95926;
        --shadow-modal: 0 8px 24px rgba(0, 0, 0, .5);
    }

    * { box-sizing: border-box; }
    [hidden] { display: none !important; }
    html { -webkit-text-size-adjust: 100%; }
    body { margin: 0; font-family: Inter, "Segoe UI Variable", "Segoe UI", system-ui, -apple-system, sans-serif; font-size: var(--fs-2); line-height: 1.5; color: var(--text); background: var(--page); -webkit-font-smoothing: antialiased; }
    a { color: var(--text); text-decoration-thickness: 1px; text-underline-offset: 2px; text-decoration-color: var(--border-strong); }
    a:hover { text-decoration-color: currentColor; }
    h1 { font-size: var(--fs-4); font-weight: 600; letter-spacing: -.01em; margin: 0 0 16px; }
    h2 { font-size: var(--fs-3); font-weight: 600; margin: 32px 0 12px; }
    ::selection { background: var(--accent-soft); }

    /* ── Formularios ─────────────────────────────────────────── */
    label { display: block; font-size: var(--fs-1); font-weight: 600; color: var(--text); margin: 14px 0 6px; }
    input, select, textarea { width: 100%; font: inherit; color: var(--text); background: var(--bg); border: 1px solid var(--border-strong); border-radius: var(--radius-sm); }
    input, select { height: 36px; padding: 0 10px; }
    textarea { padding: 8px 10px; }
    input:hover, select:hover, textarea:hover { border-color: var(--muted); }
    input:focus, select:focus, textarea:focus { outline: none; border-color: var(--text); }
    input[type="file"] { padding-top: 6px; }
    input::placeholder { color: var(--muted); }

    button, .btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; height: 36px; padding: 0 14px; font: inherit; font-weight: 600; line-height: 1; white-space: nowrap; border: 1px solid var(--accent); border-radius: var(--radius-sm); background: var(--accent); color: var(--on-accent); cursor: pointer; text-decoration: none; }
    button:hover, .btn:hover { background: var(--accent-hover); border-color: var(--accent-hover); color: var(--on-accent); }
    button:focus-visible, .btn:focus-visible, a:focus-visible, summary:focus-visible { outline: none; box-shadow: var(--ring); }
    button:disabled { opacity: .5; cursor: not-allowed; }
    .btn-secondary { background: var(--bg); color: var(--text); border-color: var(--border-strong); }
    .btn-secondary:hover { background: var(--bg-hover); border-color: var(--border-strong); color: var(--text); }
    .btn-danger { background: var(--danger); border-color: var(--danger); color: var(--on-danger); }
    .btn-danger:hover { background: var(--danger); border-color: var(--danger); color: var(--on-danger); filter: brightness(.92); }
    .btn-link { background: none; border: none; color: var(--muted); padding: 0; height: auto; font-weight: 500; text-decoration: underline; text-underline-offset: 2px; }
    .btn-link:hover { background: none; color: var(--text); }

    .error { color: var(--danger); font-size: var(--fs-1); margin-top: 6px; }
    .help { font-size: var(--fs-1); color: var(--muted); margin-top: 6px; }
    .muted { color: var(--muted); }
    .actions { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
    .filters { display: flex; flex-wrap: wrap; gap: 10px; align-items: end; margin-bottom: 16px; padding: 12px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius); }
    .filters label { margin: 0 0 6px; }
    .filters > div { min-width: 150px; }

    .alert { display: flex; gap: 10px; align-items: flex-start; padding: 10px 12px; border: 1px solid var(--border); border-left: 3px solid var(--muted); border-radius: var(--radius-sm); margin-bottom: 16px; background: var(--bg); color: var(--text); }
    .alert-error { border-left-color: var(--danger); background: var(--danger-soft); color: var(--danger); }
    .alert-warn { border-left-color: var(--warn); background: var(--warn-soft); }

    /* ── Tablas: filas de 40 px, separadores de 1 px, encabezado fijo ── */
    table { width: 100%; border-collapse: collapse; }
    th, td { height: 40px; padding: 0 12px; text-align: left; border-bottom: 1px solid var(--border); }
    th { font-size: var(--fs-1); font-weight: 600; color: var(--muted); }
    thead th { position: sticky; top: 0; z-index: 1; }
    .num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .neg { color: var(--danger); }
    .pos { color: var(--ok); }
    .warn { color: var(--warn); }

    /* ── KPI ─────────────────────────────────────────────────── */
    .tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 12px; margin-bottom: 20px; }
    .tile { position: relative; padding: 14px 16px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
    .tile .label { font-size: var(--fs-1); font-weight: 500; color: var(--muted); }
    .tile .value { font-size: var(--fs-4); font-weight: 600; margin-top: 4px; font-variant-numeric: tabular-nums; }
    .tile .sub { font-size: var(--fs-1); margin-top: 2px; font-variant-numeric: tabular-nums; }
    /* Teléfono (solo consulta): dos KPI por fila para ver más sin desplazarse. */
    @media (max-width: 600px) {
        .tiles { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
        .tile { padding: 10px 12px; }
        .tile .value { font-size: var(--fs-3); }
    }

    .panel { padding: 16px 18px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius); }

    .chip { display: inline-flex; align-items: center; padding: 1px 6px; border-radius: var(--radius-sm); font-size: var(--fs-1); font-weight: 500; border: 1px solid var(--border-strong); color: var(--muted); background: var(--bg); }
    .chip.abierto { color: var(--ok); border-color: var(--ok); }
    .chip.alerta { color: var(--warn); border-color: var(--warn); }

    .pagination { display: flex; gap: 6px; list-style: none; padding: 0; margin-top: 16px; }
    .pagination li > * { display: inline-block; min-width: 34px; padding: 6px 10px; border: 1px solid var(--border); border-radius: var(--radius-sm); text-align: center; text-decoration: none; background: var(--bg); }
    .pagination .active > * { background: var(--accent); border-color: var(--accent); color: var(--on-accent); }

    /* ── Pestañas (componente tabs) ─────────────────────────────────── */
    .tabs { display: flex; flex-wrap: wrap; gap: 4px; margin-bottom: 16px; border-bottom: 1px solid var(--border); }
    .tabs a { padding: 8px 12px; text-decoration: none; color: var(--muted); border-bottom: 2px solid transparent; margin-bottom: -1px; }
    .tabs a:hover { color: var(--text); }
    .tabs a[aria-current="page"] { color: var(--text); border-bottom-color: var(--text); font-weight: 600; }

    /* ── Modal (componente modal): dialog nativo ──────────────────── */
    dialog.modal { width: min(440px, calc(100vw - 32px)); padding: 0; color: var(--text); background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow-modal); }
    dialog.modal::backdrop { background: rgba(17, 20, 24, .45); }
    dialog.modal .modal-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 14px 16px; border-bottom: 1px solid var(--border); }
    dialog.modal .modal-head h2 { margin: 0; font-size: var(--fs-3); }
    dialog.modal .modal-cerrar { width: 28px; height: 28px; padding: 0; background: none; border: none; color: var(--muted); font-size: 18px; }
    dialog.modal .modal-cerrar:hover { color: var(--text); background: var(--bg-hover); }
    dialog.modal .modal-body { padding: 16px; }
    dialog.modal .modal-body p { margin: 0; }
    dialog.modal .modal-pie { display: flex; justify-content: flex-end; flex-wrap: wrap; gap: 8px; padding: 12px 16px; border-top: 1px solid var(--border); background: var(--bg-soft); }

    /* ── Skeleton (estado de carga) ──────────────────────────── */
    .skeleton { display: inline-block; height: 12px; min-width: 48px; border-radius: var(--radius-sm); background: var(--bg-soft); vertical-align: middle; }
    .skeleton.bloque { display: block; height: 20px; width: 60%; margin-top: 6px; }

    /* Precio que cambia en vivo: destello verde/rojo (informa si subió o bajó). */
    .cotizacion { transition: background-color 1.2s ease; border-radius: var(--radius-sm); }
    .cotizacion.sube { background-color: var(--ok-soft); transition: none; }
    .cotizacion.baja { background-color: var(--danger-soft); transition: none; }

    /* Gráficas SVG (SvgLineChart): los atributos traen colores para PDF; aquí siguen al tema. */
    svg .c-grid { stroke: var(--border); }
    svg .c-label { fill: var(--muted); }
    svg .c-ink { fill: var(--text); }
    svg .c-guia { stroke: var(--muted); }
    svg .c-ring { stroke: var(--bg); }
    svg .c-s0 { stroke: var(--serie-0); }
    svg .c-s1 { stroke: var(--serie-1); }
    svg circle.c-s0 { fill: var(--serie-0); stroke: var(--bg); }
    svg circle.c-s1 { fill: var(--serie-1); stroke: var(--bg); }
</style>
