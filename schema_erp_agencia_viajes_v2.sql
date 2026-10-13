-- =====================================================================
-- ERP AGENCIA DE VIAJES - ESQUEMA DE BASE DE DATOS (VERSION REDUCIDA)
-- 29 tablas + 5 vistas. Motor: MySQL 8.0.16+ (InnoDB, utf8mb4).
-- Requiere 8.0.16 o superior para que las restricciones CHECK se apliquen.
--
-- CONVENCIONES
--  * Tablas: snake_case, en espanol y en plural (clientes, ventas).
--  * PK: id BIGINT UNSIGNED AUTO_INCREMENT (tablas puente: PK compuesta).
--  * FK: <entidad_singular>_id. Nombre de restriccion: fk_<tabla>_<columna>.
--  * Indices: idx_<tabla>_<columnas>; unicos: uq_<tabla>_<columnas>.
--  * Dinero: DECIMAL(12,2). Porcentajes y valores de regla: DECIMAL(10,4).
--  * Estados y tipos cerrados: ENUM. Auditoria: created_at, updated_at.
--  * Pais = codigo ISO 3166-1 (CHAR(2)); moneda = codigo ISO 4217. Son
--    claves de listas estandar externas, por eso no tienen tabla propia.
--  * Datos derivados (totales, saldos, cupos disponibles, precio de venta
--    de un paquete, estado de pago de una cuota) NO se guardan: se
--    calculan en las vistas v_*.
--  * Tablas propias del framework (migraciones, tokens, colas, sesiones)
--    las crea Laravel y no se cuentan aqui.
--
-- COMO SE REDUJO (frente a la version de 58 tablas)
--  * Proveedores: una sola tabla; aerolineas, hoteles, conductores y guias
--    son proveedores de un tipo. Sus rutas, tarifas y vehiculos viven en
--    `servicios` (un vuelo, una habitacion, un traslado y una jornada de
--    guia son servicios con costo neto).
--  * Empleado y usuario son la misma tabla: las credenciales son opcionales.
--  * Permisos = pareja de ENUM (modulo, accion) dentro de rol_permisos.
--  * Se eliminan los itinerarios por dia, la gestion de plantillas y el
--    historial de PDF (los documentos se generan al momento desde los datos
--    de la venta), los seguimientos (campo en prospectos), el catalogo de
--    idiomas y de monedas, y los contactos multiples de proveedor.
--
-- EXCEPCIONES CONOCIDAS A LA NORMALIZACION (intencionales)
--  1. Costo y precio unitarios en presupuesto_servicios y venta_servicios
--     son una "foto" del momento de la cotizacion o la venta.
--  2. comisiones guarda base, valor y monto calculados (historico).
--  3. notificaciones.referencia_tipo/referencia_id es una referencia
--     polimorfica (sin FK) para enlazar la alerta con su registro.
--  4. La lista de eventos de notificacion_reglas y notificaciones se repite
--     porque MySQL no tiene tipos ENUM compartidos.
--
-- REGLAS QUE MYSQL NO PUEDE EXPRESAR (validar en la aplicacion o triggers)
--  * guia_idiomas, guia_especialidades y guia_agenda solo aplican a
--    proveedores de tipo 'guia'.
--  * Traslape de fechas en la agenda de un guia.
--  * Cupos disponibles al vender (ver v_cupos_disponibles).
--
-- SIMPLIFICACIONES DE NEGOCIO (cambian si el negocio lo requiere)
--  * Una moneda unica (configuracion_empresa.moneda_base).
--  * Un pago de cliente se aplica a una sola cuota (o a ninguna).
--  * Una cuenta por pagar se paga en un solo pago.
--  * Un cliente guarda un pasaporte vigente.
--  * Un empleado pertenece a una sucursal; el alcance del rol decide si
--    ve solo su sucursal o todas.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS erp_agencia_viajes
  CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE erp_agencia_viajes;
SET NAMES utf8mb4;

-- =====================================================================
-- 6. ORGANIZACION INTERNA Y 7. SEGURIDAD
-- =====================================================================

CREATE TABLE ciudades (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  pais_codigo  CHAR(2)         NOT NULL COMMENT 'ISO 3166-1 alfa-2',
  nombre       VARCHAR(100)    NOT NULL,
  created_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ciudades_pais_nombre (pais_codigo, nombre)
) ENGINE=InnoDB COMMENT='Ciudades y destinos (origen y destino de vuelos, hoteles, paquetes)';

CREATE TABLE sucursales (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo        VARCHAR(20)     NOT NULL,
  nombre        VARCHAR(100)    NOT NULL,
  tipo          ENUM('matriz','filial') NOT NULL DEFAULT 'filial',
  ciudad_id     BIGINT UNSIGNED NOT NULL,
  direccion     VARCHAR(255)    NULL,
  telefono      VARCHAR(25)     NULL,
  estado        ENUM('activa','inactiva') NOT NULL DEFAULT 'activa',
  matriz_unica  TINYINT GENERATED ALWAYS AS (CASE WHEN tipo = 'matriz' THEN 1 ELSE NULL END) VIRTUAL
                COMMENT 'Garantiza una sola matriz',
  created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_sucursales_codigo (codigo),
  UNIQUE KEY uq_sucursales_nombre (nombre),
  UNIQUE KEY uq_sucursales_matriz_unica (matriz_unica),
  KEY idx_sucursales_ciudad_id (ciudad_id),
  KEY idx_sucursales_estado (estado),
  CONSTRAINT fk_sucursales_ciudad_id FOREIGN KEY (ciudad_id) REFERENCES ciudades (id)
) ENGINE=InnoDB COMMENT='Matriz y filiales';

CREATE TABLE roles (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre       VARCHAR(50)     NOT NULL,
  descripcion  VARCHAR(255)    NULL,
  alcance      ENUM('propia_sucursal','todas_sucursales') NOT NULL DEFAULT 'propia_sucursal'
               COMMENT 'Decide si el rol ve solo la sucursal del empleado o todas',
  estado       ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  created_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_roles_nombre (nombre)
) ENGINE=InnoDB COMMENT='Roles de acceso (administrador, vendedor, cajero, RRHH...)';

CREATE TABLE rol_permisos (
  rol_id   BIGINT UNSIGNED NOT NULL,
  modulo   ENUM('dashboard_resumen','dashboard_alertas','prospectos','clientes','ventas_reservas',
                'paquetes','inventario_cupos','vuelos','hospedajes','transporte','guias',
                'gestion_documental','cuentas_por_cobrar','cuentas_por_pagar','comisiones',
                'reportes','sucursales','empleados','roles_permisos','ajustes_generales',
                'notificaciones') NOT NULL COMMENT 'Un valor por submodulo',
  accion   ENUM('ver','crear','editar','eliminar','exportar','aprobar') NOT NULL,
  PRIMARY KEY (rol_id, modulo, accion),
  CONSTRAINT fk_rol_permisos_rol_id FOREIGN KEY (rol_id) REFERENCES roles (id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Permiso = accion permitida sobre un submodulo, por rol';

CREATE TABLE empleados (
  id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo            VARCHAR(20)     NOT NULL,
  nombres           VARCHAR(100)    NOT NULL,
  apellidos         VARCHAR(100)    NOT NULL,
  tipo_documento    ENUM('dui','pasaporte','carnet_residente','otro') NOT NULL DEFAULT 'dui',
  numero_documento  VARCHAR(30)     NOT NULL,
  telefono          VARCHAR(25)     NULL,
  cargo             VARCHAR(80)     NOT NULL,
  sucursal_id       BIGINT UNSIGNED NOT NULL,
  tipo_contrato     ENUM('indefinido','plazo_fijo','servicios_profesionales') NOT NULL DEFAULT 'indefinido',
  fecha_ingreso     DATE            NOT NULL,
  fecha_salida      DATE            NULL,
  salario_base      DECIMAL(12,2)   NULL,
  estado            ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  email             VARCHAR(150)    NOT NULL COMMENT 'Contacto y usuario de acceso',
  password          VARCHAR(255)    NULL COMMENT 'Hash; NULL si no tiene acceso al sistema',
  rol_id            BIGINT UNSIGNED NULL COMMENT 'NULL si no tiene acceso al sistema',
  acceso            ENUM('sin_acceso','activo','bloqueado') NOT NULL DEFAULT 'sin_acceso',
  ultimo_acceso_at  TIMESTAMP       NULL,
  created_at        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_empleados_codigo (codigo),
  UNIQUE KEY uq_empleados_documento (tipo_documento, numero_documento),
  UNIQUE KEY uq_empleados_email (email),
  KEY idx_empleados_sucursal_estado (sucursal_id, estado),
  KEY idx_empleados_rol_id (rol_id),
  KEY idx_empleados_apellidos_nombres (apellidos, nombres),
  CONSTRAINT fk_empleados_sucursal_id FOREIGN KEY (sucursal_id) REFERENCES sucursales (id),
  CONSTRAINT fk_empleados_rol_id FOREIGN KEY (rol_id) REFERENCES roles (id),
  CONSTRAINT ck_empleados_fechas CHECK (fecha_salida IS NULL OR fecha_salida >= fecha_ingreso),
  CONSTRAINT ck_empleados_salario CHECK (salario_base IS NULL OR salario_base >= 0),
  CONSTRAINT ck_empleados_acceso CHECK (
    ((acceso = 'sin_acceso') = (rol_id IS NULL)) AND ((rol_id IS NULL) = (password IS NULL)))
) ENGINE=InnoDB COMMENT='Personal interno y sus credenciales de acceso (una sucursal por empleado)';

CREATE TABLE configuracion_empresa (
  id                    TINYINT UNSIGNED NOT NULL DEFAULT 1,
  nombre_comercial      VARCHAR(150)    NOT NULL,
  razon_social          VARCHAR(150)    NULL,
  documento_fiscal      VARCHAR(30)     NULL,
  email_contacto        VARCHAR(150)    NULL,
  telefono              VARCHAR(25)     NULL,
  direccion             VARCHAR(255)    NULL,
  logo_ruta             VARCHAR(255)    NULL,
  imagenes_login        JSON            NULL COMMENT 'Lista de rutas de las imagenes de inicio de sesion',
  zona_horaria          VARCHAR(64)     NOT NULL DEFAULT 'America/El_Salvador',
  moneda_base           CHAR(3)         NOT NULL DEFAULT 'USD' COMMENT 'ISO 4217',
  terminos_condiciones  MEDIUMTEXT      NULL COMMENT 'Texto del contrato de terminos y condiciones',
  created_at            TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at            TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT ck_configuracion_empresa_fila_unica CHECK (id = 1)
) ENGINE=InnoDB COMMENT='Ajustes generales: una sola fila';

-- =====================================================================
-- 4. PROVEEDORES Y LOGISTICA
-- =====================================================================

CREATE TABLE proveedores (
  id                BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
  codigo            VARCHAR(20)       NOT NULL,
  tipo              ENUM('aerolinea','hospedaje','transporte','guia','operador','otro') NOT NULL,
  nombre            VARCHAR(150)      NOT NULL COMMENT 'Para guias y conductores: nombre completo',
  razon_social      VARCHAR(150)      NULL,
  documento_fiscal  VARCHAR(30)       NULL,
  pais_codigo       CHAR(2)           NOT NULL COMMENT 'ISO 3166-1 alfa-2',
  contacto_nombre   VARCHAR(120)      NULL COMMENT 'Contacto de reservas',
  email             VARCHAR(150)      NULL,
  telefono          VARCHAR(25)       NULL,
  dias_credito      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  estado            ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  notas             TEXT              NULL COMMENT 'Politicas (p. ej. equipaje de una aerolinea), observaciones',
  created_at        TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_proveedores_codigo (codigo),
  UNIQUE KEY uq_proveedores_documento_fiscal (documento_fiscal),
  KEY idx_proveedores_tipo_estado (tipo, estado),
  KEY idx_proveedores_nombre (nombre),
  CONSTRAINT ck_proveedores_contacto CHECK (email IS NOT NULL OR telefono IS NOT NULL)
) ENGINE=InnoDB COMMENT='Aerolineas, hoteles, transportistas, guias y otros proveedores';

CREATE TABLE guia_idiomas (
  guia_id  BIGINT UNSIGNED NOT NULL COMMENT 'Proveedor de tipo guia',
  idioma   ENUM('es','en','fr','de','pt','it','zh','ja') NOT NULL COMMENT 'ISO 639-1',
  nivel    ENUM('basico','intermedio','avanzado','nativo') NOT NULL DEFAULT 'intermedio',
  PRIMARY KEY (guia_id, idioma),
  KEY idx_guia_idiomas_idioma_nivel (idioma, nivel),
  CONSTRAINT fk_guia_idiomas_guia_id FOREIGN KEY (guia_id) REFERENCES proveedores (id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Idiomas del guia (N:M)';

CREATE TABLE guia_especialidades (
  guia_id       BIGINT UNSIGNED NOT NULL COMMENT 'Proveedor de tipo guia',
  especialidad  ENUM('historia','aventura','naturaleza','gastronomia','cultura','playa','fotografia','general') NOT NULL,
  PRIMARY KEY (guia_id, especialidad),
  KEY idx_guia_especialidades_especialidad (especialidad),
  CONSTRAINT fk_guia_especialidades_guia_id FOREIGN KEY (guia_id) REFERENCES proveedores (id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Especialidades del guia (N:M)';

-- =====================================================================
-- 3. CATALOGO Y PRODUCTO
-- =====================================================================

CREATE TABLE servicios (
  id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo             VARCHAR(30)     NOT NULL,
  tipo               ENUM('vuelo','hospedaje','traslado','actividad','guia','seguro','otro') NOT NULL,
  nombre             VARCHAR(150)    NOT NULL COMMENT 'Ej.: Vuelo SAL-MIA, Habitacion doble Hotel X, Traslado aeropuerto-hotel',
  descripcion        TEXT            NULL,
  proveedor_id       BIGINT UNSIGNED NOT NULL,
  ciudad_origen_id   BIGINT UNSIGNED NULL COMMENT 'Vuelos y traslados',
  ciudad_destino_id  BIGINT UNSIGNED NULL COMMENT 'Vuelos y traslados; en hospedajes, la ciudad del hotel',
  unidad_cobro       ENUM('por_persona','por_habitacion_noche','por_vehiculo','por_dia','por_grupo','por_unidad') NOT NULL DEFAULT 'por_persona',
  costo_neto         DECIMAL(12,2)   NOT NULL COMMENT 'Tarifa neta negociada con el proveedor',
  vigente_desde      DATE            NULL,
  vigente_hasta      DATE            NULL,
  estado             ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  created_at         TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_servicios_codigo (codigo),
  KEY idx_servicios_tipo_estado (tipo, estado),
  KEY idx_servicios_proveedor_id (proveedor_id),
  KEY idx_servicios_origen_destino (ciudad_origen_id, ciudad_destino_id),
  KEY idx_servicios_destino (ciudad_destino_id),
  CONSTRAINT fk_servicios_proveedor_id FOREIGN KEY (proveedor_id) REFERENCES proveedores (id),
  CONSTRAINT fk_servicios_ciudad_origen_id FOREIGN KEY (ciudad_origen_id) REFERENCES ciudades (id),
  CONSTRAINT fk_servicios_ciudad_destino_id FOREIGN KEY (ciudad_destino_id) REFERENCES ciudades (id),
  CONSTRAINT ck_servicios_costo CHECK (costo_neto >= 0),
  CONSTRAINT ck_servicios_vigencia CHECK (vigente_hasta IS NULL OR vigente_desde IS NULL OR vigente_hasta >= vigente_desde),
  CONSTRAINT ck_servicios_vuelo CHECK (
    tipo <> 'vuelo' OR (ciudad_origen_id IS NOT NULL AND ciudad_destino_id IS NOT NULL AND ciudad_origen_id <> ciudad_destino_id)),
  CONSTRAINT ck_servicios_hospedaje CHECK (tipo <> 'hospedaje' OR ciudad_destino_id IS NOT NULL)
) ENGINE=InnoDB COMMENT='Catalogo de servicios vendibles (vuelos, hospedajes, traslados, guias...) con costo neto';

CREATE TABLE paquetes (
  id                 BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
  codigo             VARCHAR(30)       NOT NULL,
  nombre             VARCHAR(150)      NOT NULL,
  descripcion        TEXT              NULL COMMENT 'Resumen del itinerario base',
  tipo               ENUM('nacional','internacional') NOT NULL DEFAULT 'nacional',
  ciudad_destino_id  BIGINT UNSIGNED   NULL,
  duracion_dias      SMALLINT UNSIGNED NOT NULL,
  duracion_noches    SMALLINT UNSIGNED NOT NULL,
  margen_tipo        ENUM('porcentaje','monto_fijo') NOT NULL DEFAULT 'porcentaje',
  margen_valor       DECIMAL(10,2)     NOT NULL DEFAULT 0,
  estado             ENUM('borrador','activo','inactivo') NOT NULL DEFAULT 'borrador',
  created_at         TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_paquetes_codigo (codigo),
  KEY idx_paquetes_estado_tipo (estado, tipo),
  KEY idx_paquetes_ciudad_destino_id (ciudad_destino_id),
  KEY idx_paquetes_nombre (nombre),
  CONSTRAINT fk_paquetes_ciudad_destino_id FOREIGN KEY (ciudad_destino_id) REFERENCES ciudades (id),
  CONSTRAINT ck_paquetes_margen CHECK (margen_valor >= 0),
  CONSTRAINT ck_paquetes_duracion CHECK (duracion_dias >= 1 AND duracion_noches <= duracion_dias)
) ENGINE=InnoDB COMMENT='Paquetes turisticos; costo y precio se calculan en v_paquetes_precio';

CREATE TABLE paquete_servicios (
  id           BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
  paquete_id   BIGINT UNSIGNED   NOT NULL,
  servicio_id  BIGINT UNSIGNED   NOT NULL,
  dia_inicio   SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  cantidad     DECIMAL(8,2)      NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_paquete_servicios_item (paquete_id, servicio_id, dia_inicio),
  KEY idx_paquete_servicios_servicio_id (servicio_id),
  CONSTRAINT fk_paquete_servicios_paquete_id FOREIGN KEY (paquete_id) REFERENCES paquetes (id) ON DELETE CASCADE,
  CONSTRAINT fk_paquete_servicios_servicio_id FOREIGN KEY (servicio_id) REFERENCES servicios (id),
  CONSTRAINT ck_paquete_servicios_valores CHECK (cantidad > 0 AND dia_inicio >= 1)
) ENGINE=InnoDB COMMENT='Servicios que componen un paquete (N:M con atributos)';

CREATE TABLE cupos (
  id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  servicio_id       BIGINT UNSIGNED NOT NULL,
  tipo              ENUM('bloqueo','compra_adelantada') NOT NULL DEFAULT 'bloqueo',
  fecha_inicio      DATE            NOT NULL,
  fecha_fin         DATE            NOT NULL,
  cantidad_total    INT UNSIGNED    NOT NULL,
  fecha_liberacion  DATE            NULL COMMENT 'Fecha limite para devolver los espacios al proveedor',
  estado            ENUM('activo','liberado','cerrado') NOT NULL DEFAULT 'activo',
  created_at        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_cupos_servicio_fechas (servicio_id, fecha_inicio, fecha_fin),
  KEY idx_cupos_estado_liberacion (estado, fecha_liberacion),
  CONSTRAINT fk_cupos_servicio_id FOREIGN KEY (servicio_id) REFERENCES servicios (id),
  CONSTRAINT ck_cupos_cantidad CHECK (cantidad_total > 0),
  CONSTRAINT ck_cupos_fechas CHECK (fecha_fin >= fecha_inicio)
) ENGINE=InnoDB COMMENT='Allotments: espacios bloqueados o comprados por adelantado';

-- =====================================================================
-- 2. COMERCIAL Y VENTAS
-- =====================================================================

CREATE TABLE clientes (
  id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo              VARCHAR(20)     NOT NULL,
  tipo                ENUM('persona','empresa') NOT NULL DEFAULT 'persona',
  nombres             VARCHAR(100)    NULL,
  apellidos           VARCHAR(100)    NULL,
  razon_social        VARCHAR(150)    NULL,
  tipo_documento      ENUM('dui','nit','carnet_residente','otro') NULL,
  numero_documento    VARCHAR(30)     NULL,
  fecha_nacimiento    DATE            NULL,
  nacionalidad_pais   CHAR(2)         NULL COMMENT 'ISO 3166-1 alfa-2',
  email               VARCHAR(150)    NULL,
  telefono            VARCHAR(25)     NULL,
  direccion           VARCHAR(255)    NULL,
  pasaporte_numero    VARCHAR(25)     NULL,
  pasaporte_pais      CHAR(2)         NULL COMMENT 'Pais emisor, ISO 3166-1 alfa-2',
  pasaporte_vence     DATE            NULL,
  preferencias_viaje  TEXT            NULL,
  sucursal_id         BIGINT UNSIGNED NOT NULL COMMENT 'Sucursal donde se registro',
  estado              ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_clientes_codigo (codigo),
  UNIQUE KEY uq_clientes_documento (tipo_documento, numero_documento),
  UNIQUE KEY uq_clientes_pasaporte (pasaporte_pais, pasaporte_numero),
  KEY idx_clientes_email (email),
  KEY idx_clientes_telefono (telefono),
  KEY idx_clientes_apellidos_nombres (apellidos, nombres),
  KEY idx_clientes_razon_social (razon_social),
  KEY idx_clientes_sucursal_estado (sucursal_id, estado),
  KEY idx_clientes_pasaporte_vence (pasaporte_vence),
  CONSTRAINT fk_clientes_sucursal_id FOREIGN KEY (sucursal_id) REFERENCES sucursales (id),
  CONSTRAINT ck_clientes_nombre CHECK (
    (tipo = 'persona' AND nombres IS NOT NULL AND apellidos IS NOT NULL)
    OR (tipo = 'empresa' AND razon_social IS NOT NULL)),
  CONSTRAINT ck_clientes_documento CHECK ((tipo_documento IS NULL) = (numero_documento IS NULL)),
  CONSTRAINT ck_clientes_pasaporte CHECK (
    ((pasaporte_numero IS NULL) = (pasaporte_pais IS NULL)) AND ((pasaporte_numero IS NULL) = (pasaporte_vence IS NULL)))
) ENGINE=InnoDB COMMENT='CRM: clientes (personas o empresas) y viajeros, con su pasaporte vigente';

CREATE TABLE prospectos (
  id                    BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
  codigo                VARCHAR(20)       NOT NULL,
  nombres               VARCHAR(100)      NOT NULL,
  apellidos             VARCHAR(100)      NULL,
  email                 VARCHAR(150)      NULL,
  telefono              VARCHAR(25)       NULL,
  pais_destino          CHAR(2)           NULL COMMENT 'Destino de interes, ISO 3166-1 alfa-2',
  fecha_viaje_estimada  DATE              NULL,
  numero_viajeros       SMALLINT UNSIGNED NULL,
  origen                ENUM('referido','redes_sociales','sitio_web','visita_local','telefono','evento','otro') NOT NULL DEFAULT 'otro',
  estado                ENUM('nuevo','contactado','cotizado','convertido','perdido') NOT NULL DEFAULT 'nuevo',
  motivo_perdida        ENUM('precio','sin_respuesta','eligio_competencia','cambio_de_planes','otro') NULL,
  proximo_seguimiento   DATE              NULL COMMENT 'Recordatorio de seguimiento',
  vendedor_id           BIGINT UNSIGNED   NOT NULL,
  sucursal_id           BIGINT UNSIGNED   NOT NULL,
  cliente_id            BIGINT UNSIGNED   NULL COMMENT 'Se llena al convertirse en cliente',
  notas                 TEXT              NULL,
  created_at            TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at            TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_prospectos_codigo (codigo),
  UNIQUE KEY uq_prospectos_cliente_id (cliente_id),
  KEY idx_prospectos_vendedor_estado (vendedor_id, estado),
  KEY idx_prospectos_estado_seguimiento (estado, proximo_seguimiento),
  KEY idx_prospectos_sucursal_estado (sucursal_id, estado),
  KEY idx_prospectos_email (email),
  KEY idx_prospectos_telefono (telefono),
  CONSTRAINT fk_prospectos_vendedor_id FOREIGN KEY (vendedor_id) REFERENCES empleados (id),
  CONSTRAINT fk_prospectos_sucursal_id FOREIGN KEY (sucursal_id) REFERENCES sucursales (id),
  CONSTRAINT fk_prospectos_cliente_id FOREIGN KEY (cliente_id) REFERENCES clientes (id),
  CONSTRAINT ck_prospectos_contacto CHECK (email IS NOT NULL OR telefono IS NOT NULL),
  CONSTRAINT ck_prospectos_convertido CHECK (estado <> 'convertido' OR cliente_id IS NOT NULL),
  CONSTRAINT ck_prospectos_perdido CHECK (estado <> 'perdido' OR motivo_perdida IS NOT NULL)
) ENGINE=InnoDB COMMENT='Leads: clientes potenciales con recordatorio de seguimiento';

CREATE TABLE presupuestos (
  id                  BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
  codigo              VARCHAR(20)       NOT NULL,
  prospecto_id        BIGINT UNSIGNED   NULL,
  cliente_id          BIGINT UNSIGNED   NULL,
  vendedor_id         BIGINT UNSIGNED   NOT NULL,
  sucursal_id         BIGINT UNSIGNED   NOT NULL,
  paquete_id          BIGINT UNSIGNED   NULL COMMENT 'Paquete de origen, si se baso en uno',
  fecha_emision       DATE              NOT NULL,
  fecha_caducidad     DATE              NOT NULL,
  fecha_viaje_inicio  DATE              NULL,
  fecha_viaje_fin     DATE              NULL,
  numero_viajeros     SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  descuento_monto     DECIMAL(12,2)     NOT NULL DEFAULT 0,
  estado              ENUM('borrador','enviado','aprobado','rechazado','caducado','convertido') NOT NULL DEFAULT 'borrador',
  notas               TEXT              NULL,
  created_at          TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at          TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_presupuestos_codigo (codigo),
  KEY idx_presupuestos_estado_caducidad (estado, fecha_caducidad),
  KEY idx_presupuestos_vendedor_estado (vendedor_id, estado),
  KEY idx_presupuestos_sucursal_fecha (sucursal_id, fecha_emision),
  KEY idx_presupuestos_prospecto_id (prospecto_id),
  KEY idx_presupuestos_cliente_id (cliente_id),
  KEY idx_presupuestos_paquete_id (paquete_id),
  CONSTRAINT fk_presupuestos_prospecto_id FOREIGN KEY (prospecto_id) REFERENCES prospectos (id),
  CONSTRAINT fk_presupuestos_cliente_id FOREIGN KEY (cliente_id) REFERENCES clientes (id),
  CONSTRAINT fk_presupuestos_vendedor_id FOREIGN KEY (vendedor_id) REFERENCES empleados (id),
  CONSTRAINT fk_presupuestos_sucursal_id FOREIGN KEY (sucursal_id) REFERENCES sucursales (id),
  CONSTRAINT fk_presupuestos_paquete_id FOREIGN KEY (paquete_id) REFERENCES paquetes (id),
  CONSTRAINT ck_presupuestos_destinatario CHECK (prospecto_id IS NOT NULL OR cliente_id IS NOT NULL),
  CONSTRAINT ck_presupuestos_caducidad CHECK (fecha_caducidad >= fecha_emision),
  CONSTRAINT ck_presupuestos_viaje CHECK (fecha_viaje_fin IS NULL OR fecha_viaje_inicio IS NULL OR fecha_viaje_fin >= fecha_viaje_inicio),
  CONSTRAINT ck_presupuestos_descuento CHECK (descuento_monto >= 0)
) ENGINE=InnoDB COMMENT='Cotizaciones temporales con fecha de caducidad';

CREATE TABLE presupuesto_servicios (
  id                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  presupuesto_id       BIGINT UNSIGNED NOT NULL,
  servicio_id          BIGINT UNSIGNED NOT NULL,
  cantidad             DECIMAL(8,2)    NOT NULL DEFAULT 1,
  costo_neto_unitario  DECIMAL(12,2)   NOT NULL,
  precio_unitario      DECIMAL(12,2)   NOT NULL,
  fecha_inicio         DATE            NULL,
  fecha_fin            DATE            NULL,
  PRIMARY KEY (id),
  KEY idx_presupuesto_servicios_presupuesto_id (presupuesto_id),
  KEY idx_presupuesto_servicios_servicio_id (servicio_id),
  CONSTRAINT fk_presupuesto_servicios_presupuesto_id FOREIGN KEY (presupuesto_id) REFERENCES presupuestos (id) ON DELETE CASCADE,
  CONSTRAINT fk_presupuesto_servicios_servicio_id FOREIGN KEY (servicio_id) REFERENCES servicios (id),
  CONSTRAINT ck_presupuesto_servicios_montos CHECK (cantidad > 0 AND costo_neto_unitario >= 0 AND precio_unitario >= 0),
  CONSTRAINT ck_presupuesto_servicios_fechas CHECK (fecha_fin IS NULL OR fecha_inicio IS NULL OR fecha_fin >= fecha_inicio)
) ENGINE=InnoDB COMMENT='Lineas del presupuesto (costo y precio congelados al cotizar)';

CREATE TABLE ventas (
  id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo              VARCHAR(20)     NOT NULL COMMENT 'Folio de venta',
  cliente_id          BIGINT UNSIGNED NOT NULL COMMENT 'Cliente que contrata y paga',
  vendedor_id         BIGINT UNSIGNED NOT NULL,
  sucursal_id         BIGINT UNSIGNED NOT NULL,
  presupuesto_id      BIGINT UNSIGNED NULL COMMENT 'Cotizacion aprobada que origino la venta',
  paquete_id          BIGINT UNSIGNED NULL,
  tipo                ENUM('paquete','servicios_individuales') NOT NULL DEFAULT 'paquete',
  fecha_venta         DATE            NOT NULL,
  fecha_viaje_inicio  DATE            NULL,
  fecha_viaje_fin     DATE            NULL,
  descuento_monto     DECIMAL(12,2)   NOT NULL DEFAULT 0,
  estado              ENUM('reservada','confirmada','en_curso','completada','cancelada') NOT NULL DEFAULT 'reservada',
  fecha_cancelacion   DATE            NULL,
  motivo_cancelacion  VARCHAR(255)    NULL,
  notas               TEXT            NULL,
  created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ventas_codigo (codigo),
  UNIQUE KEY uq_ventas_presupuesto_id (presupuesto_id),
  KEY idx_ventas_sucursal_fecha (sucursal_id, fecha_venta),
  KEY idx_ventas_vendedor_fecha (vendedor_id, fecha_venta),
  KEY idx_ventas_cliente_id (cliente_id),
  KEY idx_ventas_estado_viaje (estado, fecha_viaje_inicio),
  KEY idx_ventas_paquete_id (paquete_id),
  KEY idx_ventas_fecha_venta (fecha_venta),
  CONSTRAINT fk_ventas_cliente_id FOREIGN KEY (cliente_id) REFERENCES clientes (id),
  CONSTRAINT fk_ventas_vendedor_id FOREIGN KEY (vendedor_id) REFERENCES empleados (id),
  CONSTRAINT fk_ventas_sucursal_id FOREIGN KEY (sucursal_id) REFERENCES sucursales (id),
  CONSTRAINT fk_ventas_presupuesto_id FOREIGN KEY (presupuesto_id) REFERENCES presupuestos (id),
  CONSTRAINT fk_ventas_paquete_id FOREIGN KEY (paquete_id) REFERENCES paquetes (id),
  CONSTRAINT ck_ventas_paquete CHECK (tipo <> 'paquete' OR paquete_id IS NOT NULL),
  CONSTRAINT ck_ventas_viaje CHECK (fecha_viaje_fin IS NULL OR fecha_viaje_inicio IS NULL OR fecha_viaje_fin >= fecha_viaje_inicio),
  CONSTRAINT ck_ventas_cancelacion CHECK (estado <> 'cancelada' OR fecha_cancelacion IS NOT NULL),
  CONSTRAINT ck_ventas_descuento CHECK (descuento_monto >= 0)
) ENGINE=InnoDB COMMENT='Ventas y reservas; el total se calcula en v_venta_totales';

CREATE TABLE venta_viajeros (
  venta_id       BIGINT UNSIGNED NOT NULL,
  cliente_id     BIGINT UNSIGNED NOT NULL COMMENT 'Cada viajero es un registro de clientes',
  tipo           ENUM('adulto','menor','infante') NOT NULL DEFAULT 'adulto',
  es_titular     TINYINT(1)      NOT NULL DEFAULT 0,
  titular_unico  BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN es_titular = 1 THEN venta_id ELSE NULL END) VIRTUAL
                 COMMENT 'Garantiza un solo titular por venta',
  PRIMARY KEY (venta_id, cliente_id),
  UNIQUE KEY uq_venta_viajeros_titular (titular_unico),
  KEY idx_venta_viajeros_cliente_id (cliente_id),
  CONSTRAINT fk_venta_viajeros_venta_id FOREIGN KEY (venta_id) REFERENCES ventas (id),
  CONSTRAINT fk_venta_viajeros_cliente_id FOREIGN KEY (cliente_id) REFERENCES clientes (id)
) ENGINE=InnoDB COMMENT='Viajeros de una venta (N:M ventas-clientes)';

CREATE TABLE venta_servicios (
  id                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  venta_id             BIGINT UNSIGNED NOT NULL,
  servicio_id          BIGINT UNSIGNED NOT NULL COMMENT 'El proveedor del servicio es el proveedor asignado (aerolinea, hotel, guia, conductor)',
  cupo_id              BIGINT UNSIGNED NULL COMMENT 'Cupo del que se descuenta, si aplica',
  cantidad             DECIMAL(8,2)    NOT NULL DEFAULT 1,
  costo_neto_unitario  DECIMAL(12,2)   NOT NULL,
  precio_unitario      DECIMAL(12,2)   NOT NULL,
  fecha_inicio         DATE            NOT NULL,
  fecha_fin            DATE            NULL,
  estado               ENUM('pendiente','solicitado','confirmado','cancelado') NOT NULL DEFAULT 'pendiente',
  localizador          VARCHAR(40)     NULL COMMENT 'Codigo de reserva del proveedor',
  PRIMARY KEY (id),
  KEY idx_venta_servicios_venta_id (venta_id),
  KEY idx_venta_servicios_servicio_inicio (servicio_id, fecha_inicio),
  KEY idx_venta_servicios_cupo_estado (cupo_id, estado),
  KEY idx_venta_servicios_inicio_estado (fecha_inicio, estado),
  KEY idx_venta_servicios_localizador (localizador),
  CONSTRAINT fk_venta_servicios_venta_id FOREIGN KEY (venta_id) REFERENCES ventas (id),
  CONSTRAINT fk_venta_servicios_servicio_id FOREIGN KEY (servicio_id) REFERENCES servicios (id),
  CONSTRAINT fk_venta_servicios_cupo_id FOREIGN KEY (cupo_id) REFERENCES cupos (id),
  CONSTRAINT ck_venta_servicios_montos CHECK (cantidad > 0 AND costo_neto_unitario >= 0 AND precio_unitario >= 0),
  CONSTRAINT ck_venta_servicios_fechas CHECK (fecha_fin IS NULL OR fecha_fin >= fecha_inicio)
) ENGINE=InnoDB COMMENT='Servicios vendidos (costo y precio congelados); base de vouchers e itinerario';

CREATE TABLE guia_agenda (
  id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  guia_id            BIGINT UNSIGNED NOT NULL COMMENT 'Proveedor de tipo guia',
  fecha_inicio       DATE            NOT NULL,
  fecha_fin          DATE            NOT NULL,
  tipo               ENUM('no_disponible','reservado') NOT NULL,
  venta_servicio_id  BIGINT UNSIGNED NULL,
  notas              VARCHAR(255)    NULL,
  created_at         TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_guia_agenda_venta_servicio_id (venta_servicio_id),
  KEY idx_guia_agenda_guia_fechas (guia_id, fecha_inicio, fecha_fin),
  CONSTRAINT fk_guia_agenda_guia_id FOREIGN KEY (guia_id) REFERENCES proveedores (id) ON DELETE CASCADE,
  CONSTRAINT fk_guia_agenda_venta_servicio_id FOREIGN KEY (venta_servicio_id) REFERENCES venta_servicios (id),
  CONSTRAINT ck_guia_agenda_fechas CHECK (fecha_fin >= fecha_inicio),
  CONSTRAINT ck_guia_agenda_reserva CHECK ((tipo = 'reservado') = (venta_servicio_id IS NOT NULL))
) ENGINE=InnoDB COMMENT='Dias no disponibles y reservas del guia; el traslape se valida en la aplicacion';

-- =====================================================================
-- 5. FINANZAS Y ADMINISTRACION
-- =====================================================================

CREATE TABLE cuotas_cobro (
  id                 BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
  venta_id           BIGINT UNSIGNED   NOT NULL,
  numero             SMALLINT UNSIGNED NOT NULL,
  fecha_vencimiento  DATE              NOT NULL,
  monto              DECIMAL(12,2)     NOT NULL,
  estado             ENUM('activa','anulada') NOT NULL DEFAULT 'activa',
  created_at         TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_cuotas_cobro_venta_numero (venta_id, numero),
  UNIQUE KEY uq_cuotas_cobro_id_venta (id, venta_id),
  KEY idx_cuotas_cobro_vencimiento (fecha_vencimiento, estado),
  CONSTRAINT fk_cuotas_cobro_venta_id FOREIGN KEY (venta_id) REFERENCES ventas (id),
  CONSTRAINT ck_cuotas_cobro_monto CHECK (monto > 0)
) ENGINE=InnoDB COMMENT='Plan de pagos del cliente; el estado de pago se calcula en v_cuotas_cobro_estado';

CREATE TABLE pagos_cliente (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  folio           VARCHAR(20)     NOT NULL COMMENT 'Numero de recibo',
  venta_id        BIGINT UNSIGNED NOT NULL,
  cuota_id        BIGINT UNSIGNED NULL COMMENT 'Cuota a la que se aplica; NULL si es un abono libre',
  sucursal_id     BIGINT UNSIGNED NOT NULL COMMENT 'Sucursal donde se recibio el pago',
  fecha_pago      DATE            NOT NULL,
  monto           DECIMAL(12,2)   NOT NULL,
  metodo_pago     ENUM('efectivo','transferencia','tarjeta','cheque','deposito','otro') NOT NULL,
  referencia      VARCHAR(80)     NULL,
  registrado_por  BIGINT UNSIGNED NOT NULL COMMENT 'Empleado (cajero) que registro el pago',
  estado          ENUM('aplicado','anulado') NOT NULL DEFAULT 'aplicado',
  created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_pagos_cliente_folio (folio),
  KEY idx_pagos_cliente_venta_estado (venta_id, estado),
  KEY idx_pagos_cliente_cuota_venta (cuota_id, venta_id),
  KEY idx_pagos_cliente_sucursal_fecha (sucursal_id, fecha_pago),
  KEY idx_pagos_cliente_fecha (fecha_pago),
  KEY idx_pagos_cliente_registrado_por (registrado_por),
  CONSTRAINT fk_pagos_cliente_venta_id FOREIGN KEY (venta_id) REFERENCES ventas (id),
  CONSTRAINT fk_pagos_cliente_cuota_venta FOREIGN KEY (cuota_id, venta_id) REFERENCES cuotas_cobro (id, venta_id),
  CONSTRAINT fk_pagos_cliente_sucursal_id FOREIGN KEY (sucursal_id) REFERENCES sucursales (id),
  CONSTRAINT fk_pagos_cliente_registrado_por FOREIGN KEY (registrado_por) REFERENCES empleados (id),
  CONSTRAINT ck_pagos_cliente_monto CHECK (monto > 0)
) ENGINE=InnoDB COMMENT='Abonos y pagos de clientes; la FK compuesta obliga a que la cuota sea de la misma venta';

CREATE TABLE cuentas_por_pagar (
  id                     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo                 VARCHAR(20)     NOT NULL,
  tipo                   ENUM('proveedor','gasto_operativo') NOT NULL,
  proveedor_id           BIGINT UNSIGNED NULL,
  categoria_gasto        ENUM('alquiler','servicios_basicos','planilla','publicidad','impuestos','mantenimiento','suministros','otro') NULL,
  venta_servicio_id      BIGINT UNSIGNED NULL COMMENT 'Servicio vendido que origina la deuda con el proveedor',
  sucursal_id            BIGINT UNSIGNED NOT NULL,
  descripcion            VARCHAR(255)    NOT NULL,
  numero_documento       VARCHAR(50)     NULL COMMENT 'Factura o documento del proveedor',
  fecha_emision          DATE            NOT NULL,
  fecha_vencimiento      DATE            NOT NULL,
  fecha_programada_pago  DATE            NULL,
  monto                  DECIMAL(12,2)   NOT NULL,
  estado                 ENUM('pendiente','pagada','anulada') NOT NULL DEFAULT 'pendiente',
  fecha_pago             DATE            NULL,
  metodo_pago            ENUM('efectivo','transferencia','tarjeta','cheque','deposito','otro') NULL,
  referencia_pago        VARCHAR(80)     NULL,
  created_at             TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at             TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_cuentas_por_pagar_codigo (codigo),
  KEY idx_cuentas_por_pagar_proveedor_venc (proveedor_id, fecha_vencimiento),
  KEY idx_cuentas_por_pagar_estado_programada (estado, fecha_programada_pago),
  KEY idx_cuentas_por_pagar_sucursal_venc (sucursal_id, fecha_vencimiento),
  KEY idx_cuentas_por_pagar_venta_servicio_id (venta_servicio_id),
  CONSTRAINT fk_cuentas_por_pagar_proveedor_id FOREIGN KEY (proveedor_id) REFERENCES proveedores (id),
  CONSTRAINT fk_cuentas_por_pagar_venta_servicio_id FOREIGN KEY (venta_servicio_id) REFERENCES venta_servicios (id),
  CONSTRAINT fk_cuentas_por_pagar_sucursal_id FOREIGN KEY (sucursal_id) REFERENCES sucursales (id),
  CONSTRAINT ck_cuentas_por_pagar_tipo CHECK (
    (tipo = 'proveedor' AND proveedor_id IS NOT NULL)
    OR (tipo = 'gasto_operativo' AND categoria_gasto IS NOT NULL)),
  CONSTRAINT ck_cuentas_por_pagar_fechas CHECK (fecha_vencimiento >= fecha_emision),
  CONSTRAINT ck_cuentas_por_pagar_monto CHECK (monto > 0),
  CONSTRAINT ck_cuentas_por_pagar_pago CHECK (
    ((estado = 'pagada') = (fecha_pago IS NOT NULL)) AND (estado <> 'pagada' OR metodo_pago IS NOT NULL))
) ENGINE=InnoDB COMMENT='Pagos programados a proveedores y gastos operativos (un pago por cuenta)';

CREATE TABLE reglas_comision (
  id             BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
  nombre         VARCHAR(100)      NOT NULL,
  beneficiario   ENUM('vendedor','guia','conductor') NOT NULL COMMENT 'Guia y conductor = proveedor de tipo guia o transporte',
  metodo         ENUM('porcentaje','monto_fijo') NOT NULL,
  valor          DECIMAL(10,4)     NOT NULL,
  base_calculo   ENUM('precio_venta','utilidad') NULL COMMENT 'Obligatoria si el metodo es porcentaje',
  venta_minima   DECIMAL(12,2)     NULL,
  tipo_servicio  ENUM('vuelo','hospedaje','traslado','actividad','guia','seguro','otro') NULL,
  sucursal_id    BIGINT UNSIGNED   NULL,
  vigente_desde  DATE              NOT NULL,
  vigente_hasta  DATE              NULL,
  prioridad      SMALLINT UNSIGNED NOT NULL DEFAULT 100 COMMENT 'Menor numero = se evalua primero',
  estado         ENUM('activa','inactiva') NOT NULL DEFAULT 'activa',
  created_at     TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_reglas_comision_nombre (nombre),
  KEY idx_reglas_comision_busqueda (beneficiario, estado, vigente_desde, vigente_hasta),
  KEY idx_reglas_comision_sucursal_id (sucursal_id),
  CONSTRAINT fk_reglas_comision_sucursal_id FOREIGN KEY (sucursal_id) REFERENCES sucursales (id),
  CONSTRAINT ck_reglas_comision_valor CHECK (valor >= 0),
  CONSTRAINT ck_reglas_comision_base CHECK (metodo <> 'porcentaje' OR base_calculo IS NOT NULL),
  CONSTRAINT ck_reglas_comision_vigencia CHECK (vigente_hasta IS NULL OR vigente_hasta >= vigente_desde)
) ENGINE=InnoDB COMMENT='Reglas automaticas del motor de comisiones';

CREATE TABLE comisiones (
  id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  regla_id           BIGINT UNSIGNED NOT NULL,
  venta_id           BIGINT UNSIGNED NOT NULL,
  venta_servicio_id  BIGINT UNSIGNED NULL COMMENT 'Servicio concreto (guias y conductores)',
  empleado_id        BIGINT UNSIGNED NULL COMMENT 'Beneficiario interno (vendedor)',
  proveedor_id       BIGINT UNSIGNED NULL COMMENT 'Beneficiario externo (guia o conductor)',
  base_monto         DECIMAL(12,2)   NOT NULL,
  valor_aplicado     DECIMAL(10,4)   NOT NULL,
  monto              DECIMAL(12,2)   NOT NULL,
  estado             ENUM('pendiente','aprobada','pagada','anulada') NOT NULL DEFAULT 'pendiente',
  fecha_calculo      DATE            NOT NULL,
  fecha_pago         DATE            NULL,
  created_at         TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_comisiones_empleado_estado (empleado_id, estado),
  KEY idx_comisiones_proveedor_estado (proveedor_id, estado),
  KEY idx_comisiones_venta_id (venta_id),
  KEY idx_comisiones_venta_servicio_id (venta_servicio_id),
  KEY idx_comisiones_regla_id (regla_id),
  KEY idx_comisiones_fecha_calculo (fecha_calculo),
  CONSTRAINT fk_comisiones_regla_id FOREIGN KEY (regla_id) REFERENCES reglas_comision (id),
  CONSTRAINT fk_comisiones_venta_id FOREIGN KEY (venta_id) REFERENCES ventas (id),
  CONSTRAINT fk_comisiones_venta_servicio_id FOREIGN KEY (venta_servicio_id) REFERENCES venta_servicios (id),
  CONSTRAINT fk_comisiones_empleado_id FOREIGN KEY (empleado_id) REFERENCES empleados (id),
  CONSTRAINT fk_comisiones_proveedor_id FOREIGN KEY (proveedor_id) REFERENCES proveedores (id),
  CONSTRAINT ck_comisiones_beneficiario CHECK ((empleado_id IS NULL) <> (proveedor_id IS NULL)),
  CONSTRAINT ck_comisiones_monto CHECK (monto >= 0),
  CONSTRAINT ck_comisiones_pagada CHECK (estado <> 'pagada' OR fecha_pago IS NOT NULL)
) ENGINE=InnoDB COMMENT='Comisiones calculadas (historico) para vendedores, guias y conductores';

-- =====================================================================
-- 7.x NOTIFICACIONES Y AUDITORIA
-- =====================================================================

CREATE TABLE notificacion_reglas (
  id                 BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
  nombre             VARCHAR(100)      NOT NULL,
  evento             ENUM('pago_cliente_por_vencer','pago_cliente_vencido','pago_proveedor_por_vencer',
                          'vuelo_proximo','presupuesto_por_caducar','seguimiento_pendiente',
                          'cupo_por_liberar','venta_confirmada','venta_cancelada') NOT NULL,
  canal              ENUM('interna','correo','ambos') NOT NULL DEFAULT 'interna',
  dias_anticipacion  SMALLINT UNSIGNED NULL,
  destinatario       ENUM('responsable','rol') NOT NULL DEFAULT 'responsable',
  rol_id             BIGINT UNSIGNED   NULL,
  estado             ENUM('activa','inactiva') NOT NULL DEFAULT 'activa',
  created_at         TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_notificacion_reglas_nombre (nombre),
  KEY idx_notificacion_reglas_evento_estado (evento, estado),
  KEY idx_notificacion_reglas_rol_id (rol_id),
  CONSTRAINT fk_notificacion_reglas_rol_id FOREIGN KEY (rol_id) REFERENCES roles (id),
  CONSTRAINT ck_notificacion_reglas_rol CHECK (destinatario <> 'rol' OR rol_id IS NOT NULL)
) ENGINE=InnoDB COMMENT='Reglas que definen que evento genera alerta, por que canal y a quien';

CREATE TABLE notificaciones (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  empleado_id      BIGINT UNSIGNED NOT NULL COMMENT 'Destinatario',
  regla_id         BIGINT UNSIGNED NULL,
  evento           ENUM('pago_cliente_por_vencer','pago_cliente_vencido','pago_proveedor_por_vencer',
                        'vuelo_proximo','presupuesto_por_caducar','seguimiento_pendiente',
                        'cupo_por_liberar','venta_confirmada','venta_cancelada') NOT NULL,
  canal            ENUM('interna','correo') NOT NULL DEFAULT 'interna',
  titulo           VARCHAR(150)    NOT NULL,
  mensaje          VARCHAR(500)    NOT NULL,
  referencia_tipo  ENUM('prospecto','presupuesto','venta','cuota_cobro','cuenta_por_pagar',
                        'venta_servicio','cupo') NULL,
  referencia_id    BIGINT UNSIGNED NULL,
  leida_at         TIMESTAMP       NULL,
  enviada_at       TIMESTAMP       NULL,
  created_at       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_notificaciones_empleado_leida (empleado_id, leida_at, created_at),
  KEY idx_notificaciones_referencia (referencia_tipo, referencia_id),
  KEY idx_notificaciones_regla_id (regla_id),
  KEY idx_notificaciones_evento_fecha (evento, created_at),
  CONSTRAINT fk_notificaciones_empleado_id FOREIGN KEY (empleado_id) REFERENCES empleados (id),
  CONSTRAINT fk_notificaciones_regla_id FOREIGN KEY (regla_id) REFERENCES notificacion_reglas (id),
  CONSTRAINT ck_notificaciones_referencia CHECK ((referencia_tipo IS NULL) = (referencia_id IS NULL))
) ENGINE=InnoDB COMMENT='Bandeja de alertas internas y registro de correos enviados';

CREATE TABLE bitacora_auditoria (
  id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  empleado_id       BIGINT UNSIGNED NULL COMMENT 'Quien realizo la accion',
  accion            ENUM('crear','actualizar','eliminar','inicio_sesion','cierre_sesion','exportar') NOT NULL,
  tabla             VARCHAR(64)     NULL,
  registro_id       BIGINT UNSIGNED NULL,
  datos_anteriores  JSON            NULL,
  datos_nuevos      JSON            NULL,
  ip                VARCHAR(45)     NULL,
  created_at        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_bitacora_auditoria_tabla_registro (tabla, registro_id),
  KEY idx_bitacora_auditoria_empleado_fecha (empleado_id, created_at),
  KEY idx_bitacora_auditoria_fecha (created_at),
  CONSTRAINT fk_bitacora_auditoria_empleado_id FOREIGN KEY (empleado_id) REFERENCES empleados (id)
) ENGINE=InnoDB COMMENT='Quien hizo que y cuando en informacion critica';

-- =====================================================================
-- VISTAS (datos derivados: nunca se guardan en tablas)
-- =====================================================================

CREATE OR REPLACE VIEW v_paquetes_precio AS
SELECT c.paquete_id,
       c.codigo,
       c.nombre,
       c.costo_neto_total,
       c.margen_tipo,
       c.margen_valor,
       CASE c.margen_tipo
         WHEN 'porcentaje' THEN ROUND(c.costo_neto_total * (1 + c.margen_valor / 100), 2)
         ELSE ROUND(c.costo_neto_total + c.margen_valor, 2)
       END AS precio_venta
FROM (
  SELECT p.id AS paquete_id, p.codigo, p.nombre, p.margen_tipo, p.margen_valor,
         COALESCE(SUM(ps.cantidad * s.costo_neto), 0) AS costo_neto_total
  FROM paquetes p
  LEFT JOIN paquete_servicios ps ON ps.paquete_id = p.id
  LEFT JOIN servicios s ON s.id = ps.servicio_id
  GROUP BY p.id, p.codigo, p.nombre, p.margen_tipo, p.margen_valor
) c;

CREATE OR REPLACE VIEW v_venta_totales AS
SELECT v.id AS venta_id,
       v.codigo,
       v.sucursal_id,
       v.vendedor_id,
       v.fecha_venta,
       v.estado,
       COALESCE(SUM(vs.cantidad * vs.costo_neto_unitario), 0)                       AS costo_total,
       COALESCE(SUM(vs.cantidad * vs.precio_unitario), 0)                           AS subtotal,
       v.descuento_monto                                                            AS descuento,
       COALESCE(SUM(vs.cantidad * vs.precio_unitario), 0) - v.descuento_monto       AS total,
       COALESCE(SUM(vs.cantidad * vs.precio_unitario), 0) - v.descuento_monto
         - COALESCE(SUM(vs.cantidad * vs.costo_neto_unitario), 0)                   AS utilidad_bruta
FROM ventas v
LEFT JOIN venta_servicios vs ON vs.venta_id = v.id AND vs.estado <> 'cancelado'
GROUP BY v.id, v.codigo, v.sucursal_id, v.vendedor_id, v.fecha_venta, v.estado, v.descuento_monto;

CREATE OR REPLACE VIEW v_ventas_saldo AS
SELECT t.venta_id,
       t.codigo,
       t.sucursal_id,
       t.total,
       COALESCE(p.pagado, 0)           AS pagado,
       t.total - COALESCE(p.pagado, 0) AS saldo
FROM v_venta_totales t
LEFT JOIN (
  SELECT venta_id, SUM(monto) AS pagado
  FROM pagos_cliente
  WHERE estado = 'aplicado'
  GROUP BY venta_id
) p ON p.venta_id = t.venta_id;

CREATE OR REPLACE VIEW v_cuotas_cobro_estado AS
SELECT q.cuota_id, q.venta_id, q.numero, q.fecha_vencimiento, q.monto, q.pagado,
       q.monto - q.pagado AS saldo,
       CASE
         WHEN q.monto - q.pagado <= 0        THEN 'pagada'
         WHEN q.fecha_vencimiento < CURDATE() THEN 'vencida'
         WHEN q.pagado > 0                    THEN 'parcial'
         ELSE 'pendiente'
       END AS estado_pago
FROM (
  SELECT c.id AS cuota_id, c.venta_id, c.numero, c.fecha_vencimiento, c.monto,
         COALESCE(SUM(CASE WHEN pc.estado = 'aplicado' THEN pc.monto END), 0) AS pagado
  FROM cuotas_cobro c
  LEFT JOIN pagos_cliente pc ON pc.cuota_id = c.id
  WHERE c.estado = 'activa'
  GROUP BY c.id, c.venta_id, c.numero, c.fecha_vencimiento, c.monto
) q;

CREATE OR REPLACE VIEW v_cupos_disponibles AS
SELECT cu.id AS cupo_id,
       cu.servicio_id,
       cu.fecha_inicio,
       cu.fecha_fin,
       cu.cantidad_total,
       COALESCE(SUM(vs.cantidad), 0)                     AS vendido,
       cu.cantidad_total - COALESCE(SUM(vs.cantidad), 0) AS disponible
FROM cupos cu
LEFT JOIN venta_servicios vs ON vs.cupo_id = cu.id AND vs.estado <> 'cancelado'
GROUP BY cu.id, cu.servicio_id, cu.fecha_inicio, cu.fecha_fin, cu.cantidad_total;

-- =====================================================================
-- DATOS INICIALES
-- =====================================================================

INSERT INTO configuracion_empresa (id, nombre_comercial) VALUES (1, 'Mi Agencia de Viajes');

INSERT INTO roles (nombre, descripcion, alcance) VALUES
  ('administrador',    'Acceso total al sistema',                       'todas_sucursales'),
  ('vendedor',         'Prospectos, clientes, cotizaciones y ventas',   'propia_sucursal'),
  ('cajero',           'Cobros, pagos y caja',                          'propia_sucursal'),
  ('recursos_humanos', 'Empleados y sucursales',                        'todas_sucursales'),
  ('operaciones',      'Proveedores, cupos y documentos de viaje',      'propia_sucursal');

INSERT INTO rol_permisos (rol_id, modulo, accion)
SELECT r.id, m.modulo, a.accion
FROM roles r
CROSS JOIN (
  SELECT 'dashboard_resumen' AS modulo UNION ALL SELECT 'dashboard_alertas' UNION ALL SELECT 'prospectos'
  UNION ALL SELECT 'clientes' UNION ALL SELECT 'ventas_reservas' UNION ALL SELECT 'paquetes'
  UNION ALL SELECT 'inventario_cupos' UNION ALL SELECT 'vuelos' UNION ALL SELECT 'hospedajes'
  UNION ALL SELECT 'transporte' UNION ALL SELECT 'guias' UNION ALL SELECT 'gestion_documental'
  UNION ALL SELECT 'cuentas_por_cobrar' UNION ALL SELECT 'cuentas_por_pagar' UNION ALL SELECT 'comisiones'
  UNION ALL SELECT 'reportes' UNION ALL SELECT 'sucursales' UNION ALL SELECT 'empleados'
  UNION ALL SELECT 'roles_permisos' UNION ALL SELECT 'ajustes_generales' UNION ALL SELECT 'notificaciones'
) m
CROSS JOIN (
  SELECT 'ver' AS accion UNION ALL SELECT 'crear' UNION ALL SELECT 'editar'
  UNION ALL SELECT 'eliminar' UNION ALL SELECT 'exportar' UNION ALL SELECT 'aprobar'
) a
WHERE r.nombre = 'administrador';
