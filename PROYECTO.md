# MU INTELLIGENCE — Especificación del proyecto

NOTA: Este documento es la especificación ORIGINAL. Si el código actual
ya difiere de ella por decisiones tomadas después, el código manda:
reporta la diferencia, no la reviertas sin mi aprobación.

Actúa como un ingeniero de software senior y diseñador de producto especializado
en plataformas financieras SaaS. Vamos a construir "MU Intelligence", un sistema
COMERCIAL (se va a vender a despachos y asesores de inversión) de gestión de
portafolios, inspirado en el módulo "Portfolios" de TradingView.
El nombre del producto es "MU Intelligence": úsalo en títulos, login,
encabezados, correos, reportes PDF y metadatos.

══════════════════════════════════════════════════════════════════
0. REGLAS DE TRABAJO (OBLIGATORIAS, POR ENCIMA DE TODO)
══════════════════════════════════════════════════════════════════
1. NO agregues, quites, renombres ni "mejores" nada que no esté en este
   documento: ni funciones, ni pantallas, ni columnas, ni tablas, ni archivos,
   ni dependencias. Si crees que algo falta o sobra, PROPÓNLO en una lista al
   final de tu respuesta y espera mi aprobación. Yo pruebo primero y decido.
2. Trabaja por fases (sección 9). No avances a la siguiente fase hasta que yo
   escriba "APROBADO".
3. Cuando modifiques algo existente: toca SOLO lo necesario, no refactorices
   código ajeno al cambio, y dime exactamente qué archivos cambiaste y por qué.
4. Entrega archivos completos (no fragmentos con "...resto igual").
5. Si algo es ambiguo, haz UNA suposición razonable, márcala como
   "SUPOSICIÓN:" y continúa. No inventes requisitos de negocio.
6. Al final de cada fase dame una checklist de pruebas manuales con datos de
   ejemplo concretos para que yo valide.
7. Toda fórmula financiera: primero explícala con un ejemplo numérico que yo
   pueda verificar a mano, luego codifícala con pruebas unitarias.
8. Explícame en español y de forma sencilla las convenciones de Laravel que
   uses por primera vez (estoy aprendiendo el framework; vengo de PHP MVC
   con PDO).
9. Al terminar cada paso importante (y siempre antes de que yo cierre la
   sesión), actualiza PROGRESO.md con: fase actual, qué está terminado,
   qué falta, decisiones tomadas y el siguiente paso. Mantenlo corto.

══════════════════════════════════════════════════════════════════
1. CONTEXTO DEL PRODUCTO
══════════════════════════════════════════════════════════════════
- Sistema web para asesores de inversión que gestionan portafolios de clientes.
- NO compra ni vende: solo registra, calcula, analiza y reporta.
- Datos capturados manualmente o importados por CSV. Sin integraciones
  bancarias, de brókers ni blockchain.
- Modelo SaaS: cada despacho (organización) paga un plan con límites.

══════════════════════════════════════════════════════════════════
2. ARQUITECTURA
══════════════════════════════════════════════════════════════════
Stack: Laravel (versión estable actual), PHP 8.2+, MySQL 8, Blade +
JavaScript ligero (ES modules). Sin Livewire, Inertia ni frameworks de
frontend salvo que yo lo apruebe. Cualquier paquete de Composer o npm
adicional: listarlo y justificarlo antes de instalarlo.

Patrón: monolito modular por capas, siguiendo convenciones de Laravel.
  Ruta → Middleware (auth, tenant, permisos, límites del plan)
       → Controller (delgado: valida con FormRequest y responde)
       → Service (lógica de negocio y cálculos financieros)
       → Model Eloquent (con Global Scope de organization_id)

Organización:
  app/Modules/{Auth, Organizations, Users, Clients, Portfolios,
               Transactions, Assets, Prices, Analytics, Rebalancing,
               Imports, Reports, Billing, Audit}
  (cada módulo: Controllers, Services, Models, Requests, Policies)
  database/migrations, database/seeders, tests/Unit, tests/Feature

Multi-tenant:
- Toda tabla de negocio lleva organization_id.
- Global Scope que filtra SIEMPRE por la organización del usuario.
- Policies: un asesor solo ve sus clientes; el admin ve todos.

Seguridad: usar lo nativo de Laravel (hash, CSRF, sesiones, rate limiting
en login, validación con FormRequest, escape de Blade). Credenciales solo
en .env. Auditoría con observers de Eloquent.

API interna: endpoints JSON en routes/api.php (/api/v1/...) con respuesta
{ "ok": bool, "data": ..., "error": { "code", "message" } }.

══════════════════════════════════════════════════════════════════
3. PRINCIPIO CLAVE DEL DOMINIO
══════════════════════════════════════════════════════════════════
El portafolio se construye a partir de un LIBRO DE TRANSACCIONES (ledger).
Posiciones, efectivo y rendimiento SE CALCULAN desde las transacciones;
nunca se guardan a mano. Editar o borrar una transacción recalcula todo
desde esa fecha. Dinero siempre en DECIMAL(20,6), nunca FLOAT.

══════════════════════════════════════════════════════════════════
4. MODELO DE DATOS
══════════════════════════════════════════════════════════════════
- plans (nombre, max_clientes, max_portafolios_por_cliente,
  max_holdings_por_portafolio, max_transacciones_por_portafolio)
- organizations (nombre, plan_id, estado, fecha_alta)
- users (organization_id, nombre, email, password, rol: superadmin/admin/asesor/cliente)
- clients (organization_id, asesor_id, nombre, contacto, perfil_riesgo)
- portfolios (organization_id, client_id, nombre, moneda_base, benchmark_id)
- assets (símbolo, nombre, tipo: acción/ETF/fondo/bono/cripto/divisa/efectivo,
  sector, país, moneda, exchange)
- transactions (organization_id, portfolio_id, asset_id, tipo, fecha, cantidad,
  precio, comisión, moneda, tipo_cambio, nota, created_by)
  Tipos: COMPRA, VENTA, DEPÓSITO, RETIRO, DIVIDENDO, INTERÉS, COMISIÓN, SPLIT
- prices (asset_id, fecha, cierre)            ← captura manual o CSV
- fx_rates (moneda_origen, moneda_destino, fecha, tasa)
- benchmarks (nombre, serie de valores por fecha: IPC, S&P 500, CETES...)
- target_allocations (portfolio_id, dimensión, clave, porcentaje_objetivo)
- portfolio_snapshots (portfolio_id, fecha, valor, efectivo, flujo_neto)
- audit_log (organization_id, user_id, acción, entidad, entidad_id,
  antes JSON, después JSON, fecha, ip)
Índices en todas las llaves foráneas y en (portfolio_id, fecha).

══════════════════════════════════════════════════════════════════
5. CÁLCULOS (módulo Analytics, todos con pruebas unitarias)
══════════════════════════════════════════════════════════════════
1. Posición por activo (ajustada por splits)
2. Costo promedio ponderado
3. Efectivo = depósitos − retiros − compras + ventas + dividendos
   + intereses − comisiones
4. Valor de mercado (convertido a moneda base con tipo de cambio de la fecha)
5. G/P no realizada (absoluta y %)
6. G/P realizada (al vender, contra costo promedio)
7. Ingresos (dividendos + intereses)
8. Rendimiento total y anualizado
9. TWR (time-weighted return) → desempeño del asesor
10. XIRR (money-weighted) → lo que realmente ganó el cliente
11. Comparación contra benchmark en el mismo periodo
12. Riesgo: volatilidad anualizada, beta, Sharpe, Sortino, máximo drawdown
    (tasa libre de riesgo configurable por organización)
13. Asignación % por clase de activo, sector, moneda y país
14. Top ganadores / perdedores
15. Contribución de cada activo al rendimiento total

══════════════════════════════════════════════════════════════════
6. PANTALLAS
══════════════════════════════════════════════════════════════════
1. Login / recuperar contraseña
2. Dashboard del asesor: tabla de clientes con valor total, rendimiento del
   periodo y alerta de desviación contra asignación objetivo.
3. Resumen del portafolio:
   - Fila de KPIs: valor total, efectivo, G/P total, rendimiento anualizado,
     número de holdings
   - Gráfica de valor en el tiempo vs benchmark (1M, 3M, YTD, 1A, Todo)
   - Asignación por clase, sector y moneda
   - Top ganadores / perdedores
4. Holdings (tabla tipo screener):
   símbolo, cantidad, costo prom., precio, valor, peso %, G/P no realizada,
   G/P realizada, rendimiento %. Ordenable, agrupable (moneda, tipo, sector,
   exchange) con subtotales, y opción "mostrar posiciones vendidas".
5. Transacciones: tabla filtrable + alta/edición/baja con validaciones
   (no vender más de lo que se tiene; no gastar efectivo inexistente).
6. Análisis: métricas de riesgo, heatmap de rendimiento mensual,
   contribución por activo.
7. Rebalanceo: definir asignación objetivo, ver desviación y sugerencia de
   montos a comprar/vender (solo sugerencia, no ejecuta nada).
8. Importar CSV: plantilla descargable, vista previa, validación por fila,
   reporte de errores antes de confirmar.
9. Precios: captura manual e importación CSV de precios y tipos de cambio.
10. Reportes: PDF y CSV del estado del portafolio para entregar al cliente.
11. Administración: usuarios del despacho, roles, plan actual y uso vs límites.

══════════════════════════════════════════════════════════════════
7. DISEÑO VISUAL (MUY IMPORTANTE)
══════════════════════════════════════════════════════════════════
Referencia: la sobriedad de TradingView / terminales financieras modernas.
Minimalista, intuitivo, moderno y SUPER legible. Los datos son el protagonista.

PROHIBIDO (estética genérica de IA):
- Degradados morados/azules, glassmorphism, neón, brillos
- Sombras grandes, tarjetas flotantes, bordes muy redondeados (máx. 6px)
- Emojis e ilustraciones decorativas
- Colores de acento en todos lados
- Animaciones que no aporten información

PALETA:
- Base neutra: blanco / grises / casi negro. Nada de colores de marca
  inventados por ti; define una sola variable --accent neutra que yo cambiaré.
- El color se usa SOLO con significado: verde = ganancia, rojo = pérdida,
  ámbar = alerta. Nunca como decoración.
- Modo claro y modo oscuro desde el inicio, todo con variables CSS
  (design tokens) en un solo archivo.
- Contraste mínimo WCAG AA en todo texto.

TIPOGRAFÍA:
- Una sola familia sans-serif moderna (ej. Inter) con números tabulares
  (font-variant-numeric: tabular-nums) en tablas y KPIs.
- Escala clara: 12 / 14 / 16 / 20 / 28 px. Cuerpo base 14px.
- Números alineados a la derecha; formato de moneda y miles según locale
  es-MX; negativos con signo y color, no solo color.

LAYOUT:
- Barra lateral estrecha con navegación, contenido con mucho aire.
- Tablas densas pero respirables: filas de 36–40px, separadores de 1px,
  encabezado fijo al hacer scroll, hover sutil.
- Jerarquía por tamaño y peso tipográfico, no por colores.
- Estados vacíos, de carga (skeleton) y de error en cada vista.
- Responsive: usable en tablet; en móvil, solo consulta.

GRÁFICAS:
- Series de tiempo con TradingView Lightweight Charts (open source; incluir
  la atribución que pide su licencia).
- Asignaciones con SVG propio o una librería ligera (justificar).
- Sin adornos: ejes discretos, tooltip claro, leyenda mínima.

COMPATIBILIDAD:
- Soportar las 2 últimas versiones de Chrome, Edge, Firefox y Safari.
- No usar funciones de JavaScript o CSS experimentales o sin soporte amplio.
- Probar en resoluciones de 1366x768 y 1920x1080.

RENDIMIENTO:
- Carga inicial rápida; nada de librerías pesadas.
- Tablas grandes paginadas desde el servidor.
- Gráficas leen de portfolio_snapshots, no recalculan el historial.

══════════════════════════════════════════════════════════════════
8. MODELO SaaS
══════════════════════════════════════════════════════════════════
- Límites del plan validados en backend (middleware) al crear clientes,
  portafolios, holdings y transacciones.
- Mensaje claro cuando se alcanza un límite.
- Sin pasarela de pago por ahora: el plan se asigna manualmente desde un
  panel de superadmin.

══════════════════════════════════════════════════════════════════
9. FASES DE ENTREGA
══════════════════════════════════════════════════════════════════
Fase 1: instalar Laravel, configurar .env, estructura de módulos,
        migraciones completas + seeders de demo.
Fase 2: Auth, organizaciones, usuarios, roles y multi-tenant.
Fase 3: Clientes, portafolios, activos, transacciones (CRUD + validaciones
        + auditoría).
Fase 4: Analytics con pruebas unitarias (un caso de ejemplo con números
        verificables a mano).
Fase 5: Sistema de diseño (tokens, componentes base: botón, input, tabla,
        KPI, tabs, modal) en modo claro y oscuro.
Fase 6: Pantallas de resumen, holdings y transacciones.
Fase 7: Análisis, rebalanceo, precios e importación CSV.
Fase 8: Reportes PDF/CSV, administración y límites de plan.

══════════════════════════════════════════════════════════════════
10. ENTORNO LOCAL
══════════════════════════════════════════════════════════════════
- Windows + Laragon, PHP [tu versión], MySQL [tu versión]
- Ruta del proyecto: C:\laragon\www\mu-intelligence
- Dominio local: mu-intelligence.test
- Base de datos: mu_intelligence, usuario root sin contraseña (solo local)
- Proyecto NUEVO: no reutilizar ni modificar el código de Mu Capital
  (el proyecto escolar anterior).
