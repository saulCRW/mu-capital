# PROGRESO — MU Intelligence

Especificación: `PROYECTO.md` (si el código difiere por decisiones posteriores, el código manda).
Última actualización: 07/10/2026.

## Fase actual
**Las 8 fases de `PROYECTO.md` están terminadas y aprobadas** (Fase 8 aprobada el 07/10/2026).

## Terminado
- **Fase 1:** Laravel 13, `.env` (APP_URL http://mu-intelligence.test, BD mu_intelligence), módulos en `app/Modules`, migraciones y seeders de demo.
- **Fase 2:** login, recuperación de contraseña, registro, usuarios y roles (ver decisión 1).
- **Fase 3:** portafolios, activos y transacciones con validación del libro y auditoría.
- **Fase 4:** los 15 cálculos con pruebas unitarias.
- **Fase 5:** sistema de diseño claro/oscuro en un solo archivo de tokens; componentes botón, input, tabla, KPI, pestañas, modal, skeleton y gráfica de líneas.
- **Fase 6:** página del portafolio con KPI y pestañas Resumen, Holdings y Transacciones.
- **Fase 7:** pestaña Análisis, pantalla Precios (superadmin), rebalanceo, importación CSV/Excel; alerta de desviación y gráfica Lightweight en el Inicio.
- **Fase 8:** reporte trimestral en PDF (ya existía) y estado del portafolio en CSV (botón "Exportar CSV"); administración: el superadmin ve plan, estado y uso contra límites de cada usuario; límites de plan con mensaje claro; API interna `/api/v1` con respuesta `{ ok, data, error }`.

## Ajustes posteriores a las fases (aprobados el 07/10/2026)
- Pantalla Precios → pestaña Índices: crear índices (ej. CETES) y capturar/importar sus valores (sección 4).
- Inicio y saldo en vivo avisan en ámbar cuando no hay conexión con el proveedor (sección 7, estados de error).
- Tablet y teléfono: bajo 1280 px cada tabla se desplaza de lado dentro de su espacio y la página no se desborda; en teléfono los KPI van en dos columnas (sección 7). Medido en 390, 768, 1024, 1100, 1280 y 1366 px.

## Funciones nuevas fuera de la especificación (aprobadas el 07/10/2026)
- **Respaldo de la base:** `php artisan base:respaldar` (mysqldump + gzip en `storage/app/private/respaldos`, conserva 14), programado diario a las 2:00. Requiere `RESPALDO_MYSQLDUMP` en `.env` y que corra el programador de Laravel (`php artisan schedule:work` o una tarea de Windows).
- **Alertas de precio (Mercado):** "sube a / baja a" un precio; se revisan con el precio del momento mientras Mercado está abierto y con cada cierre de la sincronización; aviso en Inicio y Mercado hasta "Entendido". Máximo 50 activas por usuario.
- **Dividendos esperados (menú Dividendos):** calendario mensual; esperado = títulos al corte (día anterior a la fecha ex) × dividendo por título; "Registrar cobro" abre la transacción DIVIDENDO ya llenada.

## API v1 (sesión del navegador, solo lectura)
- `GET /api/v1/portfolios` · `/portfolios/{id}` (KPI a hoy) · `/portfolios/{id}/holdings?vendidas=1` · `/portfolios/{id}/transactions?tipo=&asset_id=&desde=&hasta=&page=`
- `GET /api/v1/mercado/cotizaciones[?portfolio=ID]` · `/api/v1/dashboard/vivo` (las usan Mercado, el saldo y el Inicio en vivo)
- Errores: `no_autenticado` 401, `prohibido` 403, `no_encontrado` 404, `validacion` 422 (con `campos`), `demasiadas_solicitudes` 429.

## Decisiones
1. (Aprobada) Sin despachos, asesores ni clientes: cada usuario gestiona sus propios portafolios (`user_id`); roles `superadmin` y `usuario`. Las migraciones se consolidaron al modelo actual (07/10/2026); el esquema resultante se verificó idéntico al de la base local.
2. (Aprobada) Precios y tipos de cambio se sincronizan de Yahoo Finance y Banxico, además de la captura manual/CSV.
3. (Aprobada) Extras: Mercado en vivo con watchlist, Inicio y saldo en vivo, importación .xlsx.
4. El destello verde/rojo de precios en vivo se conserva (informa si subió o bajó).
5. La gráfica del portafolio compara rendimiento (TWR, %) contra el benchmark, no el valor en pesos.
6. Lightweight Charts va en `public/vendor/lightweight-charts-5.2.1` (con LICENSE), sin compilar con Vite.
7. La pantalla de Precios es solo del superadmin (los precios son de toda la plataforma).
8. Contribución por activo desde el inicio; métricas de riesgo del periodo elegido.
9. Fase 8: los límites del plan se validan en los servicios (al crear portafolios y transacciones, al importar y al abrir posiciones), no en un middleware: el middleware no sabe cuántas filas trae una importación ni si una compra abre una posición nueva.
10. Fase 8: la API usa la sesión del navegador (grupo "web"); no se instaló Sanctum. Los roles no se cambian desde la administración (solo hay superadmin y usuario).
11. Fase 8: llegar a un límite del plan se muestra en ámbar (alerta), no en rojo.

## Repositorio
Privado en GitHub: https://github.com/MarcoGz15/mu-intelligence (rama `main`).

## Siguiente paso
Ninguna fase pendiente. Lo siguiente lo decide el usuario (mejoras o nuevas funciones, siempre propuestas antes de hacerlas).
