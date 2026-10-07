{{-- Estilos PROVISIONALES de la Fase 2. Se reemplazan por el sistema de diseño en la Fase 5. --}}
<style>
    :root { --accent: #1f2328; --text: #1f2328; --muted: #59636e; --border: #d1d9e0; --bg: #ffffff; --bg-soft: #f6f8fa; --danger: #b42318; --ok: #1a7f37; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: Inter, system-ui, -apple-system, "Segoe UI", sans-serif; font-size: 14px; color: var(--text); background: var(--bg); }
    a { color: var(--accent); }
    h1 { font-size: 20px; font-weight: 600; margin: 0 0 16px; }
    label { display: block; font-size: 12px; font-weight: 600; margin: 12px 0 4px; }
    input, select { width: 100%; height: 36px; padding: 0 10px; font: inherit; border: 1px solid var(--border); border-radius: 6px; background: var(--bg); color: var(--text); }
    button, .btn { display: inline-block; height: 36px; padding: 0 14px; font: inherit; font-weight: 600; line-height: 36px; border: 1px solid var(--accent); border-radius: 6px; background: var(--accent); color: #fff; cursor: pointer; text-decoration: none; }
    .btn-secondary { background: var(--bg); color: var(--text); border-color: var(--border); }
    .btn-link { background: none; border: none; color: var(--muted); padding: 0; height: auto; line-height: inherit; font-weight: 400; text-decoration: underline; }
    .error { color: var(--danger); font-size: 12px; margin-top: 4px; }
    .alert { padding: 10px 12px; border: 1px solid var(--border); border-radius: 6px; margin-bottom: 16px; background: var(--bg-soft); }
    .alert-error { border-color: var(--danger); color: var(--danger); }
    table { width: 100%; border-collapse: collapse; }
    th, td { height: 38px; padding: 0 12px; text-align: left; border-bottom: 1px solid var(--border); }
    th { font-size: 12px; font-weight: 600; color: var(--muted); }
    tbody tr:hover { background: var(--bg-soft); }
    .num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .neg { color: var(--danger); }
    .filters { display: flex; flex-wrap: wrap; gap: 8px; align-items: end; margin-bottom: 16px; }
    .filters label { margin: 0 0 4px; }
    .filters > div { min-width: 140px; }
    .help { font-size: 12px; color: var(--muted); margin-top: 4px; }
    textarea { width: 100%; padding: 8px 10px; font: inherit; border: 1px solid var(--border); border-radius: 6px; }
    .actions { display: flex; gap: 8px; align-items: center; }
    .muted { color: var(--muted); }
    .pagination { display: flex; gap: 4px; list-style: none; padding: 0; margin-top: 16px; }
    .pagination li > * { display: inline-block; min-width: 32px; padding: 4px 8px; border: 1px solid var(--border); border-radius: 6px; text-align: center; text-decoration: none; }
    .pagination .active > * { background: var(--accent); color: #fff; }
</style>
