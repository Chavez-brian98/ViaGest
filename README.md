# VIAGEST

## Introducción

Sistema de Gestión Empresarial (ERP) de uso estrictamente interno, desarrollado a la medida de una agencia de viajes. Centraliza en una sola plataforma lo que normalmente se reparte entre hojas de cálculo, correos y documentos sueltos: clientes, paquetes turísticos, proveedores, cobros, pagos y comisiones.

Es una aplicación web tradicional bajo el patrón MVC: las pantallas se generan en el servidor y no hay portal público ni acceso para clientes.

## Descripción del proyecto

El sistema permite crear, costear y vender paquetes turísticos integrales (vuelos, hospedaje, transporte y actividades) dentro de un flujo unificado:

1. **Captación:** se registra el prospecto y se le da seguimiento.
2. **Cotización:** se arma un presupuesto con fecha de caducidad a partir de los servicios del catálogo.
3. **Venta y reserva:** al aprobarse el presupuesto se convierte en venta, con sus viajeros y servicios.
4. **Logística:** se coordinan proveedores, cupos y guías, y se generan en PDF los vouchers, itinerarios y contratos.
5. **Finanzas:** se controlan los cobros al cliente, los pagos a proveedores y el cálculo automático de comisiones.
6. **Seguimiento:** un dashboard y reportes exportables muestran ingresos, gastos y rentabilidad.

Además:

- **Seguridad:** cada empleado inicia sesión con un rol cuyos permisos limitan qué módulos y acciones puede usar. El rol también define si ve solo su sucursal o todas.
- **Base de datos:** MySQL normalizada (3FN) con 29 tablas, estados y tipos como ENUM, claves foráneas, restricciones e índices. Los totales, saldos y cupos disponibles se calculan en vistas y no se guardan.
- **Multisucursal:** ventas, inventario y rendimiento se filtran por matriz y filiales.

## Módulos

El sistema se organiza en 7 módulos y 21 submódulos.

| Módulo | Submódulo | Funcionalidad principal |
| --- | --- | --- |
| **1. Dashboard** | Resumen Ejecutivo | Métricas clave en gráficos: ingresos, gastos, ventas mensuales y paquetes más vendidos |
| | Alertas Rápidas | Avisos automáticos de pagos pendientes, vuelos próximos y tareas de seguimiento |
| **2. Comercial y Ventas** | Prospectos (Leads) | Clientes potenciales, presupuestos temporales con caducidad y recordatorios de seguimiento |
| | Directorio de Clientes | CRM con historial de compras, pasaporte, contacto y preferencias de viaje |
| | Ventas y Reservas | Aprobación de cotizaciones y venta de paquetes completos o servicios individuales |
| **3. Catálogo y Producto** | Paquetes Turísticos | Constructor de viajes: une servicios, suma costos netos y aplica márgenes |
| | Inventario y Cupos | Control de espacios bloqueados o comprados por adelantado para evitar sobreventas |
| **4. Proveedores y Logística** | Vuelos | Aerolíneas, rutas, políticas de equipaje y contactos |
| | Hospedajes | Hoteles y hostales con tarifas netas negociadas y contactos de reserva |
| | Transporte | Conductores afiliados o flotilla propia, rutas cubiertas y tarifas |
| | Guías Turísticos | Perfiles, idiomas, especialidades, tarifas y agenda de disponibilidad |
| | Gestión Documental | Generación automática en PDF de vouchers, itinerarios y contratos |
| **5. Finanzas y Administración** | Cuentas por Cobrar | Abonos, planes de pago y saldos pendientes de clientes |
| | Cuentas por Pagar | Registro y programación de pagos a proveedores y gastos operativos |
| | Motor de Comisiones | Reglas automáticas para vendedores y pagos extra a guías y conductores |
| | Reportes | Exportación a Excel o PDF para contabilidad, rentabilidad y análisis de ventas |
| **6. Organización Interna** | Sucursales | Gestión de matriz y filiales |
| | Empleados | Ficha del personal, asignación a sucursales y datos laborales |
| **7. Configuración y Seguridad** | Roles y Permisos | Acceso a módulos y acciones según el cargo |
| | Ajustes Generales | Logo, nombre comercial, zona horaria, moneda base e imágenes de inicio de sesión |
| | Notificaciones | Reglas de alertas automáticas, internas o por correo, según eventos del sistema |

## Tecnologías

| Capa | Tecnología | Uso | Estado |
| --- | --- | --- | --- |
| Backend | PHP 8.3 y Laravel | Lógica de negocio, rutas, controladores, servicios y form requests | En uso |
| Vistas | Blade | Pantallas generadas en el servidor (MVC) | En uso |
| Base de datos | MySQL 8.0.16 o superior (InnoDB, utf8mb4) | Información transaccional del sistema | En uso |
| Acceso a datos | Eloquent ORM | Modelos y relaciones | En uso |
| Autenticación | Sesión de Laravel (guard `web`) | Inicio de sesión de los empleados, con límite de intentos | En uso |
| Roles y permisos | Tablas `roles` y `rol_permisos` | Permisos por módulo y acción | En uso |
| Gráficos | ApexCharts | Métricas del Dashboard | En uso |
| Confirmaciones | SweetAlert2 | Avisos y confirmación de acciones destructivas | En uso |
| Documentos PDF | DomPDF | Vouchers, itinerarios y contratos | Prevista |
| Exportación | Laravel Excel | Reportes en Excel | Prevista |
| Tareas y alertas | Colas y programador de Laravel | Notificaciones, recordatorios y vencimientos | Prevista |
| Correo | SMTP | Alertas automáticas por correo | Prevista |
| Servidor | Nginx en Linux | Despliegue de la aplicación | Prevista |
| Control de versiones | Git | Código fuente y colaboración | Prevista |
