# PROGRESO — MU Intelligence

Especificación: `PROYECTO.md` (si el código difiere por decisiones posteriores, el código manda).
Última actualización: 07/10/2026.

## Fase actual
**Fase 6 — Pantallas de resumen, holdings y transacciones.** Entregada; espera validación ("APROBADO").

## Terminado
- **Fase 1:** Laravel 13, `.env` (APP_URL http://mu-intelligence.test, BD mu_intelligence), módulos en `app/Modules`, migraciones y seeders de demo.
- **Fase 2:** login, recuperación de contraseña, registro, usuarios y roles (ver decisión 1).
- **Fase 3:** portafolios, activos y transacciones con validación del libro y auditoría.
- **Fase 4:** los 15 cálculos con pruebas unitarias.
- **Fase 5:** tokens claro/oscuro en un solo archivo (`layouts/partials/styles.blade.php`), acento neutro, bordes ≤ 6 px, sin degradados ni sombras grandes, escala 12/14/16/20/28, colores solo con significado (verde, rojo, ámbar). Componentes: botón, input, tabla (encabezado fijo), KPI, pestañas (`<x-tabs>`), modal (`<x-modal>`, `<x-confirmar>`) y skeleton.
- **Fase 6:** página del portafolio con KPI (valor total, efectivo, G/P total, rendimiento anualizado, holdings) y pestañas: Resumen (gráfica TWR vs benchmark 1M/3M/YTD/1A/Todo con TradingView Lightweight Charts en `public/vendor`, asignación por clase/sector/moneda, top ganadores/perdedores), Holdings (ordenable, agrupable por moneda/tipo/sector/exchange con subtotales, "mostrar posiciones vendidas") y Transacciones.
- Adelantado de fases posteriores: dashboard, saldo actual, compra/venta, rebalanceo, importación CSV/Excel, reporte trimestral PDF, plan y límites, sincronización de mercado.

## Falta
- Pantalla 2: alerta de desviación contra la asignación objetivo en el dashboard (no está en ninguna fase de la sección 9; propuesta para la Fase 7 con rebalanceo).
- La gráfica del Inicio y del reporte trimestral siguen en SVG propio (el reporte PDF no puede usar JavaScript).
- **Fase 7:** pantalla de Análisis (riesgo, heatmap mensual, contribución); pantalla de Precios (captura manual e importación CSV de precios y tipos de cambio).
- **Fase 8:** reporte CSV; API `/api/v1` en `routes/api.php`.
- Comentarios del código que citan "CLAUDE.md" deben citar `PROYECTO.md`.

## Decisiones
1. Sin despachos, asesores ni clientes: cada usuario se registra y gestiona sus propios portafolios (`user_id`); roles `superadmin` y `usuario`. Las migraciones iniciales de `organizations`/`clients` siguen en el historial.
2. Precios y tipos de cambio se sincronizan de Yahoo Finance y Banxico (la especificación pedía solo captura manual/CSV).
3. Extras fuera de la especificación: Mercado en vivo con watchlist, Inicio y saldo en vivo, importación .xlsx.
   **Pendiente de tu decisión:** confirmar 1–3 o quitarlos. Mientras tanto se quedan.
4. Fase 5: el destello verde/rojo de precios en vivo se conserva (informa si subió o bajó).
5. Fase 6: la gráfica compara rendimiento acumulado (TWR, %) del portafolio contra el benchmark, no el valor en pesos (el valor incluye depósitos y no es comparable con un índice).
6. Fase 6: Lightweight Charts va como archivo en `public/vendor/lightweight-charts-5.2.1` (con su LICENSE) para no requerir compilar con Vite.

## Siguiente paso
Validar la Fase 6 con la checklist; con "APROBADO", empezar la Fase 7 (Análisis, Precios).
