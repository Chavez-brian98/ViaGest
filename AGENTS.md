# ViaGest - AGENTS.md

ERP interno de agencia de viajes (Laravel 13, PHP ^8.3, Blade server-side, Vite 8 + Tailwind 4, ApexCharts). No hay API ni portal publico: todo se sirve por `routes/web.php`.

## Comandos (Windows / PowerShell)

- Instalar: `composer install`, `npm install` (`.npmrc` tiene `ignore-scripts=true`) y `php artisan storage:link` (fotos de perfil).
- BD: `php artisan migrate --seed` o `php artisan migrate:fresh --seed`.
- Tests: `composer test` (hace `config:clear` y corre `php artisan test` sobre sqlite `:memory:`). Un test: `php artisan test --filter=<nombre>`.
- Formato: `vendor\bin\pint` en archivos tocados; `--test` solo verifica. No hay linter/formatter JS ni typecheck; los unicos scripts son `npm run dev` y `npm run build`.
- Stack completo: `composer dev` (`php artisan dev`).
- Este checkout usa MySQL local `Viagest` (`.env`); `.env.example` queda en sqlite y `database/database.sqlite` no existe. Los tests no dependen de MySQL.
- `public/build` esta trackeado pese a figurar en `.gitignore`: evita commitear artefactos de build.

## Login y auth

- Rutas: `GET /` (`login`) y `POST /` (`login.store`); no existe `/login`. Logout: `POST /logout` (`logout`). Dashboard: `GET /dashboard` (`dashboard`).
- `config/auth.php` usa `App\Models\Empleado` con la tabla `empleados`; el provider sigue llamandose `users`. No reintroduzcas `users`.
- El login exige `acceso = 'activo'`; al entrar regenera sesion y actualiza `ultimo_acceso_at`. Logica en `App\Service\Auth\LoginService`, request en `App\Http\Requests\Auth`, controlador en `App\Http\Controllers\auth` (minuscula): respeta mayusculas/minusculas en Linux.
- `AdminUserSeeder` crea `admin@viagest.com` / `1234`; `DemoDashboardSeeder` agrega ventas/cuotas demo. `DatabaseSeeder` ejecuta ConfiguracionEmpresa, Roles, RolPermisos, AdminUser y DemoDashboard. `DatosDemoDashboardSeeder` es un duplicado no usado: edita `DemoDashboardSeeder`.
- Login: pagina standalone con `@fonts`/`@vite`, video fijo en `public/videos/login.mp4` y gradiente de respaldo. `configuracion_empresa.imagenes_login` es JSON de **imagenes**, no video; para hacerlo configurable crea una migracion nueva (despues de `000030`).
- Modelos Eloquent existentes: `App\Models\Empleado` (auth), `App\Models\Cliente`, `App\Models\Sucursal` y `App\Models\Rol`; el resto del esquema no tiene modelos.

## Perfil

- Rutas: `GET /perfil` (`perfil.edit`) y `PUT /perfil` (`perfil.update`), dentro de `auth`. Controlador `App\Http\Controllers\PerfilController` -> `App\Service\Perfil\PerfilService`; validacion en `App\Http\Requests\Perfil\PerfilRequest`.
- El footer del sidebar es el acceso al perfil: enlaza avatar/nombre/email a `perfil.edit`; el boton de logout queda fuera del enlace.
- Se editan nombres, apellidos, documento, telefono, email, cargo, sucursal, contrato, fechas, salario y contraseña. `codigo`, `rol_id`, `estado` y `acceso` son de solo lectura en el perfil.
- La foto vive en `empleados.foto_perfil` (migracion `000031`), se guarda en el disco `public` bajo `perfiles/{empleado_id}` y se elimina/reemplaza desde el mismo formulario. Requiere `storage:link`; formatos JPG/PNG/WebP hasta 2 MB.
- Cambiar contraseña exige `password_actual`; la nueva se valida con `confirmed` y minimo 8 caracteres. El cast `hashed` del modelo hace el hash.
- La vista es `resources/views/perfil/edit.blade.php`; no usa entry Vite propio, el preview de la foto es JS inline en `@push('scripts')`.

## Clientes

- CRUD en `App\Http\Controllers\ClienteController` -> `App\Service\Clientes\ClienteService`; validacion en `App\Http\Requests\Clientes\ClienteRequest`. No hay vista `show`: el detalle se muestra en un `<dialog>` de `clientes/_tabla.blade.php`.
- Rutas: `Route::resource('clientes')->except(['show'])`, `PATCH /clientes/{cliente}/estado` (`clientes.estado`, toggle desde la tabla) y `PATCH /clientes/{cliente}/deshabilitar` (`clientes.deshabilitar`, legado) dentro del grupo `auth`.
- `ClienteService::listar()` busca por codigo, nombres, apellidos, razon social, documento, email o telefono y filtra por `estado`, `tipo` y `sucursal_id`; pagina 12 con `withQueryString`. El index devuelve solo `clientes._tabla` si la peticion es AJAX.
- Reglas: `codigo` unico, `(tipo_documento, numero_documento)` unico, `(pasaporte_pais, pasaporte_numero)` unico; persona exige `nombres`, empresa exige `razon_social`; documento y pasaporte se validan en pareja. El FormRequest recorta, normaliza mayusculas/minusculas y convierte cadenas vacias a `null`.
- El estado se cambia con un toggle en la columna Estado de la tabla (`estado_destino`) y tambien en el formulario create/edit; `estado` es opcional en el request y por defecto `activo`. El toggle de la tabla envia AJAX, espera JSON y actualiza la fila sin recargar.
- Eliminar exige `estado = inactivo` y bloquea si hay filas en `ventas`, `venta_viajeros` o `prospectos` (`DomainException` -> flash `error`). El icono de basura solo aparece en inactivos.
- `resources/js/clientes.js` (entry Vite propio) hace busqueda en vivo con debounce/fetch de `_tabla`, filtros AJAX por estado/tipo/sucursal, paginacion AJAX, apertura de `<dialog>` y confirmaciones SweetAlert2 sobre `form[data-confirm]`. No cargues SweetAlert2 globalmente.

## Dominio y migraciones

- Esquema en `database/migrations/2026_10_13_000001`-`000029` (29 tablas), `000030` (5 vistas: `v_paquetes_precio`, `v_venta_totales`, `v_ventas_saldo`, `v_cuotas_cobro_estado`, `v_cupos_disponibles`) y `000031` (`empleados.foto_perfil`). Una migracion por tabla, en orden de dependencias FK; agrega tablas nuevas despues de `000031` y no reordenes/consolides.
- Deben ser portables (solo Schema/Blueprint): los tests corren sqlite `:memory:`. Nada de DDL crudo MySQL ni funciones de MySQL; evita agregar columnas generadas (las existentes con `virtualAs` en `sucursales`/`venta_viajeros` ya funcionan en sqlite). Las vistas ramifican por `DB::getDriverName()`.
- Los `CHECK` no estan en las migraciones (Blueprint no los soporta); validalos en la app. Las reglas de negocio (solapamiento de agenda de guias, cupos al vender, coherencia pasaporte/documento) tambien van en la aplicacion.
- Convenciones: snake_case en espanol y plural; PK `id`; FK `<entidad>_id`; `ENUM` para estados/tipos; dinero `DECIMAL(12,2)`; porcentajes `DECIMAL(10,4)`; `created_at`/`updated_at`.
- Datos derivados (totales, saldos, cupos disponibles, precio de paquete) viven solo en las vistas `v_*`; no agregues columnas para ellos.
- La mayoria de constraints usa nombres por defecto de Laravel. Conserva los explicitos existentes: `uq_*` y, sobre todo, `uq_cuotas_cobro_id_venta` + `fk_pagos_cliente_cuota_venta` (FK compuesta `(cuota_id, venta_id) -> cuotas_cobro (id, venta_id)`).

## Dashboard y frontend

- `DashboardController` -> `App\Service\Dashboard\DashboardService`; consulta las vistas `v_*` con Query Builder. Devuelve `metricas` y `graficas` (mas arrays planos heredados). La tabla de cuotas proximas usa paginacion (`cuotas_page`, 8 por pagina).
- El service mezcla agregacion en PHP con SQL por driver: `strftime`/`DATE_FORMAT`/`to_char` para meses y `CONCAT`/`TRIM` en algunas consultas. Si tocas esas consultas, verifica sqlite y MySQL; `DashboardTest` solo prueba el dashboard vacio, no los graficos con datos.
- ApexCharts solo se importa en `resources/js/dashboard.js` (entry Vite separado, ~900 kB); no lo cargues globalmente. Los datos llegan por `window.dashboardGraficas` con `@json($graficas)` en `@push('scripts')` (no `dashboardCharts`).
- Gotcha: `@json(...)` de Blade se rompe si el array va en varias lineas; pasalo en una sola linea.
- El shell autenticado no scrollea en `lg+`: body `h-screen overflow-hidden`, main `lg:overflow-hidden`, contenedores del dashboard `min-h-0 flex-1`. En movil hay drawer y scroll normal. Las paginas de formulario (create/edit de clientes y perfil) usan su propio contenedor `h-full overflow-y-auto`.

## Vistas y estilos

- `resources/views/layouts/app.blade.php` es el shell autenticado (sidebar + topbar). Las vistas lo extienden con `@section('titulo')`, `@section('contenido')` y `@push('scripts')`; el menu vive en `config/navegacion.php` (items con `ruta => null` se muestran deshabilitados).
- No hay componentes Blade ni libreria de iconos: los iconos son SVG inline (mapa `$icons` en el layout). `welcome.blade.php` es solo el scaffold.
- Partials compartidos: `partials.breadcrumb` (`items` con `label`/`url`, empieza en Dashboard) y `partials.paginacion` (`paginator`, `etiqueta`). En clientes la paginacion debe quedar dentro de `#clientesTabla` para que el AJAX de `resources/js/clientes.js` la intercepte; por eso no uses `$paginator->links()` directamente.
- Tailwind 4 se importa en `resources/css/app.css` con `@import 'tailwindcss'` y el plugin `@tailwindcss/vite`; no hay `tailwind.config.js`, PostCSS ni TypeScript. `resources/js/app.js` solo controla el sidebar; `resources/js/clientes.js` y `resources/js/dashboard.js` son entries separados.
- Las transiciones entre paginas usan `@view-transition { navigation: auto; }` en `app.css` (cross-document View Transitions). Los estilos `.estado-switch`/`.estado-texto` animan el toggle y respetan `prefers-reduced-motion: reduce`.
- Paleta temporal en `@theme` de `app.css`: `primary` #0b4f6c, `secondary` #1ca3aa, `accent` #f28f3b, `background` #f5f8fa, `surface` #ffffff, `ink` #1f2933. Usa `bg-primary`, `text-ink`, `border-ink/10`, etc.; los hex de ApexCharts en `dashboard.js` deben coincidir. Rose/emerald/amber solo para estados de datos y acciones destructivas/deshabilitar.
- UI: responsive (movil/tablet/escritorio), accesible (labels, `aria-*`, foco, contraste) y consistente con la card de `auth/login.blade.php`. Backend: controlador delgado, validacion en FormRequest, logica en `app/Service`, modelos Eloquent; logica de negocio fuera de Blade y SQL crudo solo si es inevitable (con rama por driver).

## Tests

- Tests con `RefreshDatabase` y `$this->withoutVite()` (el manifiesto de Vite no existe en CI). No hay factories: `LoginTest`/`DashboardTest`/`ClienteTest`/`PerfilTest` crean fixtures con `DB::table(...)->insertGetId(...)` en `ciudades`/`sucursales`/`roles` y luego `Empleado::create()`.
- `DashboardTest` cubre invitado redirigido y dashboard autenticado vacio; no crea ventas, asi que no valida las consultas de graficas.
- `ClienteTest` cubre invitado, listado/busqueda (HTML y parcial AJAX), filtros, paginacion, create/edit, alta de persona, empresa sin razon social, actualizacion, toggle de estado (redirect y JSON), deshabilitar, borrado bloqueado de activos y de inactivos con ventas, y borrado de inactivo sin relaciones.
- `PerfilTest` cubre invitado, vista del perfil, actualizacion de datos, email unico, cambio de contraseña con contraseña actual, rechazo de contraseña actual incorrecta y alta/reemplazo/eliminacion de foto con `Storage::fake('public')`.
