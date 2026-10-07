{{--
    Sistema de diseño de MU Intelligence.
    - Tokens de color en :root (tema claro) y [data-theme="dark"] (tema oscuro). El tema lo
      decide layouts.partials.tema (preferencia guardada o la del sistema). Las páginas sin
      ese script (reporte imprimible y PDF) se quedan en claro.
    - Las vistas usan los tokens; ningún color de la interfaz va fijo en una vista.
--}}
<style>
    :root {
        color-scheme: light;
        --page: #f3f5fa;
        --bg: #ffffff;
        --bg-soft: #f5f7fb;
        --bg-hover: #eef2f8;
        --border: #e2e7f0;
        --border-strong: #cfd7e4;
        --text: #0f172a;
        --muted: #64748b;
        --accent: #3b5bdb;
        --accent-hover: #2f4bc4;
        --accent-soft: #eef1ff;
        --accent-2: #7c3aed;
        --on-accent: #ffffff;
        --ok: #15803d;
        --ok-soft: #e9f7ef;
        --danger: #c0262d;
        --danger-soft: #fdeeee;
        --serie-0: #2a78d6;
        --serie-1: #eb6834;
        --shadow-sm: 0 1px 2px rgba(15, 23, 42, .05), 0 1px 3px rgba(15, 23, 42, .04);
        --shadow: 0 8px 24px rgba(15, 23, 42, .07);
        --ring: 0 0 0 3px rgba(59, 91, 219, .18);
        --radius: 12px;
        --radius-sm: 9px;
        --side-bg: linear-gradient(180deg, #0f172a 0%, #131d3b 100%);
        --side-text: #c7d0e0;
        --side-muted: #8592ab;
        --side-hover: rgba(255, 255, 255, .06);
        --side-active: rgba(99, 125, 255, .16);
    }
    :root[data-theme="dark"] {
        color-scheme: dark;
        --page: #0a0f1c;
        --bg: #111a2e;
        --bg-soft: #16213a;
        --bg-hover: #1b2846;
        --border: #24324f;
        --border-strong: #31416a;
        --text: #e6eaf3;
        --muted: #95a1ba;
        --accent: #7b93ff;
        --accent-hover: #96a8ff;
        --accent-soft: rgba(123, 147, 255, .14);
        --accent-2: #a78bfa;
        --on-accent: #0a0f1c;
        --ok: #4ade80;
        --ok-soft: rgba(74, 222, 128, .12);
        --danger: #f87171;
        --danger-soft: rgba(248, 113, 113, .12);
        --serie-0: #3987e5;
        --serie-1: #d95926;
        --shadow-sm: 0 1px 2px rgba(0, 0, 0, .3);
        --shadow: 0 10px 30px rgba(0, 0, 0, .35);
        --ring: 0 0 0 3px rgba(123, 147, 255, .25);
        --side-bg: linear-gradient(180deg, #080d19 0%, #0d1528 100%);
        --side-active: rgba(123, 147, 255, .18);
    }

    * { box-sizing: border-box; }
    [hidden] { display: none !important; }
    html { -webkit-text-size-adjust: 100%; }
    body { margin: 0; font-family: Inter, "Segoe UI Variable", "Segoe UI", system-ui, -apple-system, sans-serif; font-size: 14px; line-height: 1.5; color: var(--text); background: var(--page); -webkit-font-smoothing: antialiased; }
    a { color: var(--accent); text-decoration-thickness: 1px; text-underline-offset: 2px; }
    a:hover { color: var(--accent-hover); }
    h1 { font-size: 24px; font-weight: 650; letter-spacing: -.015em; margin: 0 0 16px; }
    h2 { font-size: 16px; font-weight: 600; letter-spacing: -.005em; margin: 32px 0 12px; }
    ::selection { background: var(--accent-soft); }

    /* ── Formularios ─────────────────────────────────────────── */
    label { display: block; font-size: 12px; font-weight: 600; color: var(--text); margin: 14px 0 6px; }
    input, select, textarea { width: 100%; font: inherit; color: var(--text); background: var(--bg); border: 1px solid var(--border-strong); border-radius: var(--radius-sm); transition: border-color .15s, box-shadow .15s; }
    input, select { height: 40px; padding: 0 12px; }
    textarea { padding: 10px 12px; }
    input:hover, select:hover, textarea:hover { border-color: var(--muted); }
    input:focus, select:focus, textarea:focus { outline: none; border-color: var(--accent); box-shadow: var(--ring); }
    input[type="file"] { padding-top: 8px; }
    input::placeholder { color: var(--muted); opacity: .7; }

    button, .btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; height: 40px; padding: 0 16px; font: inherit; font-weight: 600; line-height: 1; white-space: nowrap; border: 1px solid transparent; border-radius: var(--radius-sm); background: var(--accent); color: var(--on-accent); cursor: pointer; text-decoration: none; box-shadow: 0 1px 2px rgba(15, 23, 42, .12), inset 0 1px 0 rgba(255, 255, 255, .12); transition: background-color .15s, box-shadow .15s, transform .05s; }
    button:hover, .btn:hover { background: var(--accent-hover); color: var(--on-accent); }
    button:active, .btn:active { transform: translateY(1px); }
    button:focus-visible, .btn:focus-visible, a:focus-visible { outline: none; box-shadow: var(--ring); }
    .btn-secondary { background: var(--bg); color: var(--text); border-color: var(--border-strong); box-shadow: var(--shadow-sm); }
    .btn-secondary:hover { background: var(--bg-hover); color: var(--text); }
    .btn-link { background: none; border: none; box-shadow: none; color: var(--muted); padding: 0; height: auto; font-weight: 500; text-decoration: underline; text-underline-offset: 2px; }
    .btn-link:hover { background: none; color: var(--text); }

    .error { color: var(--danger); font-size: 12px; margin-top: 6px; }
    .help { font-size: 12px; color: var(--muted); margin-top: 6px; }
    .muted { color: var(--muted); }
    .actions { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
    .filters { display: flex; flex-wrap: wrap; gap: 10px; align-items: end; margin-bottom: 16px; padding: 14px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow-sm); }
    .filters label { margin: 0 0 6px; }
    .filters > div { min-width: 150px; }

    .alert { display: flex; gap: 10px; align-items: flex-start; padding: 12px 14px; border: 1px solid var(--border); border-left: 3px solid var(--accent); border-radius: var(--radius-sm); margin-bottom: 16px; background: var(--accent-soft); color: var(--text); }
    .alert-error { border-left-color: var(--danger); background: var(--danger-soft); color: var(--danger); }

    /* ── Tablas ──────────────────────────────────────────────── */
    table { width: 100%; border-collapse: collapse; }
    th, td { height: 40px; padding: 0 12px; text-align: left; border-bottom: 1px solid var(--border); }
    th { font-size: 11px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: var(--muted); }
    .num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .neg { color: var(--danger); }
    .pos { color: var(--ok); }

    /* ── Tarjetas de indicadores ─────────────────────────────── */
    .tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 14px; margin-bottom: 20px; }
    .tile { position: relative; padding: 16px 18px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow-sm); overflow: hidden; }
    .tile .label { font-size: 11px; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; color: var(--muted); }
    .tile .value { font-size: 22px; font-weight: 650; letter-spacing: -.02em; margin-top: 6px; font-variant-numeric: tabular-nums; }
    .tile .sub { font-size: 12px; margin-top: 4px; font-variant-numeric: tabular-nums; }
    .tiles .tile:first-child::before { content: ""; position: absolute; inset: 0 0 auto 0; height: 3px; background: linear-gradient(90deg, var(--accent), var(--accent-2)); }

    .panel { padding: 18px 20px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow-sm); }

    .chip { display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; border: 1px solid var(--border-strong); color: var(--muted); background: var(--bg); }
    .chip.abierto { color: var(--ok); border-color: color-mix(in srgb, var(--ok) 45%, transparent); background: var(--ok-soft); }

    .pagination { display: flex; gap: 6px; list-style: none; padding: 0; margin-top: 16px; }
    .pagination li > * { display: inline-block; min-width: 34px; padding: 6px 10px; border: 1px solid var(--border); border-radius: var(--radius-sm); text-align: center; text-decoration: none; background: var(--bg); }
    .pagination .active > * { background: var(--accent); border-color: var(--accent); color: var(--on-accent); }

    /* Precio que cambia en vivo: destello verde/rojo. */
    .cotizacion { transition: background-color 1.2s ease; border-radius: 6px; }
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
