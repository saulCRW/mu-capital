# PROGRESO — MU Intelligence

Especificación: `PROYECTO.md` (si el código difiere por decisiones posteriores, el código manda).
Última actualización: 07/10/2026.

## Fase actual
**Fase 7 — Análisis, rebalanceo, precios e importación.** Entregada; espera validación ("APROBADO").

## Terminado
- **Fase 1:** Laravel 13, `.env` (APP_URL http://mu-intelligence.test, BD mu_intelligence), módulos en `app/Modules`, migraciones y seeders de demo.
- **Fase 2:** login, recuperación de contraseña, registro, usuarios y roles (ver decisión 1).
- **Fase 3:** portafolios, activos y transacciones con validación del libro y auditoría.
- **Fase 4:** los 15 cálculos con pruebas unitarias.
- **Fase 5:** tokens claro/oscuro en un solo archivo (`layouts/partials/styles.blade.php`), acento neutro, bordes ≤ 6 px, sin degradados ni sombras grandes, escala 12/14/16/20/28, colores solo con significado (verde, rojo, ámbar). Componentes: botón, input, tabla (encabezado fijo), KPI, pestañas (`<x-tabs>`), modal (`<x-modal>`, `<x-confirmar>`), skeleton y gráfica de líneas (`<x-grafica-lineas>`).
- **Fase 6:** página del portafolio con KPI y pestañas Resumen (gráfica TWR vs benchmark 1M/3M/YTD/1A/Todo con Lightweight Charts, asignación, top), Holdings (ordenable, agrupable con subtotales, vendidas) y Transacciones.
- **Fase 7:** pestaña Análisis (TWR, XIRR, benchmark, volatilidad, drawdown, beta, Sharpe, Sortino por periodo; heatmap de rendimiento mensual; contribución por activo); pantalla Precios del superadmin (captura manual e importación CSV/Excel de cierres y tipos de cambio, con recálculo de valuaciones); rebalanceo e importación de transacciones (ya existían).
- Propuestas aprobadas: alerta de desviación contra la asignación objetivo en el Inicio; gráfica del Inicio con Lightweight Charts.
- Adelantado de la Fase 8: reporte trimestral PDF, administración de usuarios, plan y límites.

## Falta
- **Fase 8:** reporte CSV del estado del portafolio; API `/api/v1` en `routes/api.php`; revisar administración y límites contra la especificación.
- El reporte trimestral (HTML/PDF) sigue con gráficas SVG propias: el PDF no ejecuta JavaScript.

## Decisiones
1. (Aprobada) Sin despachos, asesores ni clientes: cada usuario se registra y gestiona sus propios portafolios (`user_id`); roles `superadmin` y `usuario`. Las migraciones iniciales de `organizations`/`clients` siguen en el historial.
2. (Aprobada) Precios y tipos de cambio se sincronizan de Yahoo Finance y Banxico, además de la captura manual/CSV.
3. (Aprobada) Extras: Mercado en vivo con watchlist, Inicio y saldo en vivo, importación .xlsx.
4. Fase 5: el destello verde/rojo de precios en vivo se conserva (informa si subió o bajó).
5. Fase 6: la gráfica compara rendimiento acumulado (TWR, %) contra el benchmark, no el valor en pesos.
6. Fase 6: Lightweight Charts va como archivo en `public/vendor/lightweight-charts-5.2.1` (con su LICENSE), sin compilar con Vite.
7. Fase 7: la pantalla de Precios es solo del superadmin, porque precios y tipos de cambio son de toda la plataforma (un usuario cambiaría las valuaciones de otros).
8. Fase 7: la contribución por activo es desde el inicio; las métricas de riesgo, del periodo elegido.

## Siguiente paso
Validar la Fase 7 con la checklist; con "APROBADO", empezar la Fase 8 (reporte CSV, API v1, administración y límites).
