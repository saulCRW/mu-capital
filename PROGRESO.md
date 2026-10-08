# PROGRESO — MU Intelligence

Especificación: `PROYECTO.md` (si el código difiere por decisiones posteriores, el código manda).
Última actualización: 07/10/2026.

## Fase actual
**Fase 5 — Sistema de diseño.** Entregada; espera validación ("APROBADO").

## Terminado
- **Fase 1:** Laravel 13, `.env` (APP_URL http://mu-intelligence.test, BD mu_intelligence), módulos en `app/Modules`, migraciones y seeders de demo.
- **Fase 2:** login, recuperación de contraseña, registro, usuarios y roles (ver decisión 1).
- **Fase 3:** portafolios, activos y transacciones con validación del libro y auditoría.
- **Fase 4:** los 15 cálculos con pruebas unitarias.
- **Fase 5:** tokens claro/oscuro en un solo archivo (`layouts/partials/styles.blade.php`), acento neutro, bordes ≤ 6 px, sin degradados ni sombras grandes, escala 12/14/16/20/28, colores solo con significado (verde, rojo, ámbar). Componentes: botón, input, tabla (encabezado fijo), KPI, pestañas (`<x-tabs>`), modal (`<x-modal>`, `<x-confirmar>`) y skeleton.
- Adelantado de fases posteriores: dashboard, saldo actual, compra/venta, rebalanceo, importación CSV/Excel, reporte trimestral PDF, plan y límites, sincronización de mercado.

## Falta
- **Fase 6:** gráfica del portafolio vs benchmark con 1M/3M/YTD/1A/Todo; asignación y top en el resumen; holdings ordenable/agrupable con subtotales y "mostrar vendidas"; alerta de desviación en el dashboard; gráficas con TradingView Lightweight Charts (hoy SVG propio).
- **Fase 7:** pantalla de Análisis (riesgo, heatmap mensual, contribución); pantalla de Precios (captura manual e importación CSV de precios y tipos de cambio).
- **Fase 8:** reporte CSV; API `/api/v1` en `routes/api.php`.
- Comentarios del código que citan "CLAUDE.md" deben citar `PROYECTO.md`.

## Decisiones
1. Sin despachos, asesores ni clientes: cada usuario se registra y gestiona sus propios portafolios (`user_id`); roles `superadmin` y `usuario`. Las migraciones iniciales de `organizations`/`clients` siguen en el historial.
2. Precios y tipos de cambio se sincronizan de Yahoo Finance y Banxico (la especificación pedía solo captura manual/CSV).
3. Extras fuera de la especificación: Mercado en vivo con watchlist, Inicio y saldo en vivo, importación .xlsx.
   **Pendiente de tu decisión:** confirmar 1–3 o quitarlos. Mientras tanto se quedan.
4. Fase 5: el destello verde/rojo de precios en vivo se conserva (informa si subió o bajó).

## Siguiente paso
Validar la Fase 5 con la checklist; con "APROBADO", empezar la Fase 6.
