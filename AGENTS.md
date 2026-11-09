# ViaGest - AGENTS.md

ERP interno de una agencia de viajes (Laravel 13, PHP ^8.3, Blade server-side, Vite + Tailwind 4). App MVC clasica: las pantallas se renderizan en el servidor, **no hay API ni portal publico**; todo entra por `routes/web.php`. Dominio, modulos y alcance funcional: ver `README.md`.

## Como funciona el proyecto (flujo de una peticion)

- `routes/web.php` es la unica entrada web. `/` es el login (`GET` -> `login`, `POST` -> `login.store`, middleware `guest`); no existe `home`. Tras autenticar se redirige a `dashboard`. El grupo `auth` tiene: dashboard, perfil, clientes, empleados, proveedores, servicios, roles (protegido con `permiso:roles_permisos,ver`) y auditoria (protegido con `es_administrador`).
- Pipeline por peticion: ruta + middleware -> **controlador delgado** -> **FormRequest** (normaliza y valida) -> **Service** (reglas de negocio, transacciones, auditoria) -> Eloquent / Query Builder -> vista Blade (`@extends('layouts.app')`).
- Ubicaciones y convenciones: controladores en `app/Http/Controllers` (login en `auth/`, minuscula), requests en `app/Http/Requests/<Modulo>`, logica en `app/Service/<Modulo>`, modelos en `app/Models`. Vistas por modulo en `resources/views/<modulo>/` con `index`/`create`/`edit` y parciales `_tabla`/`_form` reutilizados. Sigue ese patron antes de inventar otro.
- Middleware: los alias se registran en `bootstrap/app.php` (estilo Laravel 11+, **no existe** `app/Http/Kernel.php`): `permiso:modulo,accion` (`VerificarPermiso`) y `es_administrador`. Ese archivo tambien define routing y manejo de excepciones.
- Permisos: `Empleado::tienePermiso()` revisa primero overrides por empleado en `empleado_permisos` (`efecto` = `permitir`/`denegar`) y, si no hay override, hereda de `rol_permisos`. El rol llamado exactamente `administrador` tiene bypass total. Los modulos/acciones validos son `RolService::MODULOS_ETIQUETAS` y `RolService::ACCIONES`; si agregas un modulo, actualiza ambos y el enum de la migracion `empleado_permisos`. Hoy roles, configuracion (`ajustes_generales`) y auditoria exigen middleware; clientes, empleados, proveedores y servicios solo piden `auth`. El sidebar (`config/navegacion.php`) lista todo item con `ruta`; `ruta => null` se muestra como "Pronto" deshabilitado. Los items pueden declarar `permiso => [modulo, accion]` y se ocultan si el empleado no lo tiene.
- Auditoria: los Services llaman a `AuditoriaService::registrar()` (metodo estatico) en crear/actualizar/eliminar/toggle, y `LoginService` registra inicio/cierre de sesion. Guarda JSON de antes/despues, IP y excluye `password`/`remember_token` en `app/Service/Auditoria/AuditoriaService.php:17`. Visor admin en `/auditoria`.
- Reglas de negocio en Services lanzan `DomainException`; el controlador la convierte en `with('error')` o JSON 422 (ver `RolController::actualizarEstado`). Borrado en dos pasos: primero deshabilitar (`estado = inactivo`), luego eliminar; el rol administrador es inmutable. Proveedores y servicios ademas bloquean el borrado si tienen registros relacionados.
- Listados con busqueda/filtros: el `index` renderiza el parcial `_tabla`; si `$request->ajax()` el controlador devuelve `response()->view('modulo._tabla', ...)`. El JS del modulo hace fetch del parcial (header `X-Requested-With: XMLHttpRequest`) y los toggles de estado son rutas `PATCH` que responden JSON con `expectsJson()`.
- Configuracion: fila unica en `configuracion_empresa` (id=1) con datos de empresa, logo y colores. `ConfiguracionService::actual()` cachea por request (y valida `Schema::hasTable` para tolerar entornos sin migrar); `AppServiceProvider` comparte `configuracion`, `logoUrl`, `coloresSistema` e `inicialesMarca` con `layouts.app` y `auth.login`. Los colores se inyectan como variables CSS `:root` sobre `@theme`; el logo se guarda en el disco `public` (`configuracion/`). La ruta `/configuracion` usa `permiso:ajustes_generales,ver` y `editar`.

## Auth

- La tabla de acceso es `empleados` (email/password/rol_id/acceso), **NO** `users`. `config/auth.php` usa `App\Models\Empleado` como provider `users`. No reintroduzcas `users`.
- Login exige `acceso = 'activo'`, regenera sesion y actualiza `ultimo_acceso_at` (`app/Service/Auth/LoginService.php`). El esquema pide coherencia entre `acceso`, `rol_id` y `password`; validala en la app.
- Usuarios sembrados: `admin@viagest.com` / `1234` (`AdminUserSeeder`) y un empleado demo por rol (`EmpleadosDemoSeeder`: `vendedor@viagest.com`, `cajero@viagest.com`, `rrhh@viagest.com`, `operaciones@viagest.com`, todos con clave `1234`). `RolesSeeder` crea administrador/vendedor/cajero/recursos_humanos/operaciones y `RolPermisosSeeder` define permisos por defecto para cada rol. `DemoDashboardSeeder` crea ventas demo solo si `ventas` esta vacio.
- Perfil es autoservicio: en `/perfil` solo son editables la foto y la contrasena; el resto se muestra de solo lectura. Por eso `PerfilRequest` declara esos campos como `nullable` (no `required`): un `PUT` que solo manda foto/clave debe pasar. `foto_perfil` usa el disco `public` (`storage:link`).
- Namespaces sensibles a mayusculas: `App\Http\Controllers\auth` (minuscula), `App\Http\Requests\Auth` y `App\Service\Auth`. En Linux carpeta y namespace deben coincidir.

## Modelos

- Solo existen `Empleado` (Authenticatable), `Rol`, `Sucursal`, `Cliente` y `Auditoria` (mapea `bitacora_auditoria`, sin timestamps salvo `created_at`).
- El resto de tablas **no tiene modelo**: usa `DB::table(...)` como ya se hace para `rol_permisos`/`empleado_permisos` (`Empleado`, `RolService`, `EmpleadoService`), proveedores/servicios (`ProveedorService`, `ServicioService`), dependencias de borrado y datos de dashboard. No crees un modelo por cada tabla sin necesidad.
- Proveedores y servicios no usan route model binding: los controladores reciben `int $proveedor`/`int $servicio` y las rutas declaran `->parameters(['proveedores' => 'proveedor'])`/`['servicios' => 'servicio']`. Codigos sugeridos: `EMP-####`, `PROV-####`, `SRV-####`.

## Entorno (bloqueante en esta maquina)

- No hay `.env` en el repo (gitignored). El `.env` local actual apunta a MySQL en Docker: contenedor `mybdd`, base `ViaGest`, usuario `root`, clave `1234`, host `127.0.0.1:3306`. Docker Desktop debe estar corriendo y publicar el puerto 3306; si aparece `SQLSTATE[HY000] [2002] Connection refused`, revisa `docker ps`/mapeo de puertos o valida el servidor con `docker exec mybdd mysql -uroot -p1234 -e "SELECT 1;"`.
- PHP: el proyecto exige `^8.3`. Verifica `php -v` (en esta maquina resuelve a `C:\php-8.4.23\php.exe`); si el PATH apunta a un PHP anterior (p.ej. el 8.2 de XAMPP), antepon un PHP 8.3+ al PATH antes de `composer`/`php`.
- Para un entorno desde cero: `Copy-Item .env.example .env`, `php artisan key:generate`, crear `database/database.sqlite` (no existe) y `php artisan migrate --seed`. `.env.example` es sqlite con `DB_DATABASE` comentado, por lo que el default es `database/database.sqlite`; el conector sqlite **lanza excepcion si el archivo no existe**, asi que `composer setup` falla en `migrate` si no lo creas antes.
- Los tests **requieren `APP_KEY`**: sin `.env`/key fallan con "No application encryption key has been specified". `phpunit.xml` fuerza `DB_CONNECTION=sqlite` + `DB_DATABASE=:memory:`; los tests no tocan tu BD real.
- Produccion: MySQL 8.0.16+. No agregues SQL/funciones exclusivas de MySQL en tests o migraciones.

## Comandos (Windows / PowerShell)

- Instalar: `composer install`, `npm install`, `php artisan storage:link`.
- BD: `php artisan migrate --seed` (o `migrate:fresh --seed`).
- Tests: `composer test` (limpia config y corre la suite; hoy 109 tests en verde). Un test: `php artisan test --filter=<nombre>`.
- Formato: `vendor\bin\pint` en archivos tocados (`--test` solo verifica). No hay `pint.json`: corre con defaults.
- Frontend: `npm run dev`, `npm run build`. **No hay** linter/formatter/typecheck JS ni CI (`.github` no existe).
- `composer dev` = `php artisan dev` (sirve el stack completo).
- `/public/build` esta en `.gitignore` pero se commitea parcialmente (manifest, fonts y assets base); los chunks por modulo no se trackean, asi que ejecuta `npm run build` tras tocar JS y commitea el `manifest.json` actualizado.

## Esquema y migraciones (leer antes de crear/editar)

- Fuente de verdad: `database/migrations/2026_10_13_000001`-`000029` (29 tablas), `000030` (5 vistas `v_*`), `000031` (`foto_perfil`), `000032` (`empleado_permisos`), `000033` (limpia permisos `exportar`/`aprobar`) y `000034` (colores de `configuracion_empresa`). Una migracion por tabla, numeradas por dependencias FK; al agregar, continua despues de la ultima con fecha nueva (`AAAA_MM_DD_0000NN_*`). No reordenes ni consolides.
- Convenciones: snake_case en espanol y plural; PK `id`; FK `<entidad>_id`; `ENUM` para estados/tipos; dinero `DECIMAL(12,2)`; porcentajes `DECIMAL(10,4)`.
- Unicos/indices llevan nombres explicitos `uq_*`/`idx_*` (hay ~90); las FKs usan `->constrained()` por defecto, salvo la FK compuesta `fk_pagos_cliente_cuota_venta` (`(cuota_id, venta_id) -> cuotas_cobro (id, venta_id)`), que depende del unique `uq_cuotas_cobro_id_venta`. No lo elimines.
- `empleado_permisos` usa PK compuesta `(empleado_id, modulo, accion)` y columna `efecto` (`permitir`/`denegar`); `EmpleadoService::actualizarPermisos()` borra y reinserta con `insertOrIgnore`, validando contra las constantes de `RolService`.
- Deben ser **portables** (solo Schema/Blueprint) porque los tests corren en sqlite. La migracion de vistas ramifica por driver con `DB::getDriverName()`. Los `CHECK` del diseno original no se tradujeron (Blueprint no los soporta): validalos en la app, no con SQL crudo.
- Datos derivados (totales, saldos, cupos disponibles, precio de paquete) viven en las vistas `v_*`; no agregues columnas para ellos (`DashboardService` las consulta con Query Builder y agrega en PHP).

## Vistas y frontend

- Layout unico `resources/views/layouts/app.blade.php` (sidebar colapsable con `localStorage` + topbar). Secciones `titulo`/`contenido` y `@push('scripts')`; todo `@push` debe tener su `@endpush` o Blade lanza "Cannot end a push stack without first starting one". Iconos: SVG inline en el mapa `$icons`; no hay componentes Blade ni libreria de iconos.
- Paleta/tema en `resources/css/app.css` (`@theme`): `primary #0b4f6c`, `secondary #1ca3aa`, `accent #f28f3b`, `background`, `surface`, `ink`; fuente Instrument Sans via Bunny fonts. Usa clases `bg-primary`, `text-ink`, `border-ink/10`, etc. En runtime, `layouts/app.blade.php` y `auth/login.blade.php` inyectan `:root { --color-* }` con los valores de `configuracion_empresa` (override de `@theme`). No introduzcas paletas genericas ni `tailwind.config.js` (Tailwind 4 con `@tailwindcss/vite`; no hay PostCSS ni TypeScript).
- Cada modulo de listado tiene su entry Vite propio (`resources/js/{clientes,empleados,roles,auditoria,proveedores,servicios}.js`) con `@vite(...)` en el `@push('scripts')` del index; `configuracion.js` se carga solo en `/configuracion`. **Al crear un modulo nuevo, agrega su JS al `input` de `vite.config.js`** o el build falla/queda sin asset.
- Roles no tiene vista ni ruta `edit`: la edicion y los permisos se hacen desde dialogos del index (`roles/_detalle_permisos_dialog.blade.php`) que envian `PUT roles/{role}` y `PATCH roles/{role}/permisos`. Empleados tiene su propio dialogo de overrides (`empleados/_permisos_dialog.blade.php`, `PATCH empleados/{empleado}/permisos`).
- JS de listados: busqueda con debounce, `fetch` del parcial `_tabla`, actualizacion de URL con `history.replaceState`, confirmaciones y dialogos con **SweetAlert2** (`sweetalert2` es dependencia npm). Paginacion propia en `partials/paginacion.blade.php`; los paginadores usan `->withQueryString()`.
- Dashboard: **ApexCharts** solo en `resources/js/dashboard.js` (entry separado, pesado; no lo cargues globalmente). Datos por `window.dashboardGraficas` (`@json($graficas)`), no `dashboardCharts`. Gotcha: `@json(...)` debe ir en **una sola linea**.
- Dashboard y vistas de listado no scrollean en `lg+`: shell `h-screen overflow-hidden`, filas `lg:min-h-0 lg:flex-1`; en movil el sidebar es drawer con `overflow-y-auto`.

## Tests

- Feature tests por modulo en `tests/Feature/<Modulo>/`; usan `RefreshDatabase` y `$this->withoutVite()` en `setUp`. `tests/TestCase.php` limpia el cache estatico de `ConfiguracionService` en cada test.
- `database/factories` esta vacio (aunque composer declare el namespace): no hay `factory()`. Arma fixtures con `DB::table(...)->insertGetId(...)` en `ciudades`/`sucursales`/`roles` y luego `Model::create()` (patron de `LoginTest`/`RolTest`). Respeta los NOT NULL y unicos reales: `empleados` exige `codigo`, `nombres`, `apellidos`, `sucursal_id`, `cargo`, `fecha_ingreso` y unicidad de `email` y `(tipo_documento, numero_documento)`.
- El bypass de permisos depende de que el rol se llame exactamente `administrador`; los tests crean ese rol a mano. Verifica tambien filas en `bitacora_auditoria` cuando toques Services.

## Estandares (obligatorios)

- **Responsivo**: movil, tablet y escritorio (`sm:`/`md:`/`lg:`); la card de login es la referencia visual.
- **Profesional**: usa los tokens del tema, espaciado consistente, transiciones sutiles; evita estilos inline arbitrarios.
- **UX/UI**: jerarquia clara, coherencia entre pantallas, accesibilidad (contraste, `aria-*`, labels, `autocomplete`, foco y errores).
- **Backend limpio**: controlador delgado que solo orquesta, validacion/normalizacion en `FormRequest`, reglas en `app/Service`, Eloquent o Query Builder para datos; sin logica de negocio en la vista. Textos de UI en espanol.
