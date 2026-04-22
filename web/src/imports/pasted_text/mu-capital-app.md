PANTALLA 1 – Login Único
Crea una pantalla de inicio de sesión para MU Capital:

Logo MU Capital centrado
Título: "Bienvenido a MU Capital"
Campos:

Email (input)
Contraseña (input password)


Botón "Iniciar Sesión" (teal)
El sistema detecta automáticamente el rol según la cuenta:

Si es Administrador → navega al Dashboard Admin
Si es Inversionista → navega al Dashboard Inversionista


Sin selector manual de rol en el login
Fondo púrpura oscuro, mismo sistema de diseño


PANTALLA 2 – Registro / Crear Cuenta
Formulario de registro con:

Nombre completo
Email
Contraseña
Confirmar contraseña
Rol (dropdown con únicamente dos opciones: Administrador / Inversionista)
Botón "Crear Cuenta" (teal)
Nota: "Si eres Inversionista, tu administrador asignado gestionará tu portafolio"


PANTALLAS DEL ADMINISTRADOR – Ya existentes, sin cambios
Mantener todas las pantallas ya diseñadas:

Dashboard Admin
Gestión de Usuarios (donde ve a Juan, María, Carlos, etc. con sus datos)
Portafolios
Transacciones
Alertas Inteligentes
Administración (Usuarios / Activos Globales / Reportes)

El administrador ve y gestiona los datos de cada inversionista. Lo que el admin registra de Juan (portafolio, transacciones, alertas) es exactamente lo que Juan verá en su perfil.

PANTALLAS DEL INVERSIONISTA – Solo Lectura

PANTALLA 3 – Dashboard Inversionista
Header: Logo MU Capital / Dashboard / Mi Portafolio / Movimientos / Alertas / Perfil / Cerrar Sesión

Badge en header: "Modo Inversionista" en teal para diferenciarlo visualmente del admin

Contenido:

Saludo: "Bienvenido, Juan" (nombre del inversionista logueado)
3 tarjetas de resumen (datos que el admin registró para Juan):

💰 Valor Total: $245,000
📈 Rendimiento: +27.65%
📊 Activos en portafolio: número


Gráfica de rendimiento histórico (visual, solo lectura)
Sección "Últimos Movimientos" — mismos datos que el admin ve en la tabla de Juan
Sección "Mis Alertas Activas" — las mismas alertas que el admin configuró para Juan
Cero botones de acción financiera


PANTALLA 4 – Mi Portafolio

Título: "Mi Portafolio"
Banner informativo: "Tu portafolio es gestionado por tu administrador asignado"
Tarjetas: Valor Actual / Inversión Inicial / Ganancia o Pérdida / Rendimiento %
Estos datos son exactamente los que el admin registró para Juan en la pantalla de Gestión de Usuarios
Tabla de activos (solo lectura):

Activo / Cantidad / Precio Actual / Valor Total / Variación %
Badges verde (positivo) / rojo (negativo)


Gráfica donut de distribución del portafolio
Sin botones de compra, venta, edición ni creación
Ícono 🔒 en la tabla indicando solo lectura


PANTALLA 5 – Mis Movimientos

Título: "Mis Movimientos"
Subtítulo: "Historial de operaciones realizadas por tu administrador"
Filtros visuales decorativos (Tipo / Estado / Fecha) sin interacción real
Tabla idéntica a la que el admin ve para Juan:

Fecha / Tipo / Activo / Cantidad / Precio / Total / Estado
Badges: Compra verde / Venta naranja / Completada teal / Pendiente amarillo


Sin botón "Nueva Transacción" ni "Exportar CSV"
Nota al pie: "Todas las operaciones son ejecutadas por tu administrador"
Ícono 🔒 visible en la sección


PANTALLA 6 – Mis Alertas

Título: "Mis Alertas"
Subtítulo: "Alertas configuradas por tu administrador para tu portafolio"
3 tarjetas resumen: Alertas de Caída / Objetivo / Volatilidad (mismos números que el admin ve)
Lista de alertas — exactamente las mismas que el admin configuró para Juan:

Activo / Tipo / Condición / Valor Actual / Estado
Badges en rojo/verde/naranja


Toggles visibles pero bloqueados (muestran estado pero no son clicables)
Sin botón "Nueva Alerta" ni "Editar"
Banner: "Las alertas son configuradas y gestionadas por tu administrador"
Ícono 🔒 en cada alerta


PANTALLA 7 – Mi Perfil

Título: "Mi Perfil"
Avatar con iniciales del inversionista
Datos personales (solo lectura, los mismos que el admin registró):

Nombre: Juan
Email: juan@mucapital.com
Rol: Inversionista (badge)
Estado: Activo
Fecha de registro


Sección "Mi Administrador Asignado":

Nombre y email del admin que gestiona a Juan
Botón "Contactar" → abre modal con email del administrador


Sección "Seguridad":

Botón "Cambiar Contraseña" (único botón funcional)


Sección "Preferencias" (sí editables):

Idioma / Moneda de visualización




SINCRONIZACIÓN DE DATOS ADMIN ↔ INVERSIONISTA
Documenta en el prototipo con anotaciones o flechas que:

Los datos que el Admin ve de Juan en Gestión de Usuarios = los datos que Juan ve en su Dashboard
Las transacciones que el Admin registra para Juan = el historial que Juan ve en Movimientos
Las alertas que el Admin configura para Juan = las alertas que Juan ve en su pantalla
El valor del portafolio que el Admin asignó a Juan = el valor que Juan ve en Mi Portafolio


RESTRICCIONES VISUALES DEL INVERSIONISTA
En todas las pantallas del inversionista:

Ícono 🔒 en secciones de solo lectura
Tooltip al intentar clicar elementos bloqueados: "Solo lectura – Contacta a tu administrador"
No existen los botones: Nueva Transacción / Crear Portafolio / Nueva Alerta / Nuevo Usuario / Exportar CSV / Editar
Badge "Modo Inversionista" permanente en el header


FLUJO DE NAVEGACIÓN COMPLETO

Login → detecta rol → Admin va a Dashboard Admin / Inversionista va a Dashboard Inversionista
Registro → dropdown rol: solo Administrador o Inversionista
Navbar Inversionista: Dashboard → Mi Portafolio → Movimientos → Alertas → Perfil
Todas las transiciones: Smart Animate 300ms Slide from Right