# ViaGest - AGENTS.md

ERP interno de una agencia de viajes (Laravel 13, PHP ^8.3, Blade server-side, Vite + Tailwind 4). App MVC clasica: las pantallas se renderizan en el servidor, **no hay API ni portal publico**; todo entra por `routes/web.php`. Dominio, modulos y alcance funcional: ver `README.md`.

## Como funciona el proyecto (flujo de una peticion)

- `routes/web.php` es la unica entrada web. `/` es el login (`GET` -> `login`, `POST` -> `login.store`, middleware `guest`); no existe `home`. Tras autenticar se redirige a `dashboard`. El resto vive en un grupo `auth`: dashboard, perfil, clientes, empleados, roles (protegido con `permiso:roles_permisos,ver`) y auditoria (protegido con `es_administrador`).
- Pipeline por peticion: ruta + middleware -> **controlador delgado** -> **FormRequest** (normaliza y valida) -> **Service** (reglas de negocio, transacciones, auditoria) -> Eloquent / Query Builder -> vista Blade (`@extends('layouts.app')`).
- Ubicaciones y convenciones: controladores en `app/Http/Controllers` (login en `auth/`, minuscula), requests en `app/Http/Requests/<Modulo>`, logica en `app/Service/<Modulo>`, modelos en `app/Models`. Vistas por modulo en `resources/views/<modulo>/` con `index`/`create`/`edit` y parciales `_tabla`/`_form` reutilizados. Sigue ese patron antes de inventar otro.
- Middleware: los alias se registran en `bootstrap/app.php` (estilo Laravel 11+, **no existe** `app/Http/Kernel.php`): `permiso:modulo,accion` (`VerificarPermiso`) y `es_administrador`. Ese archivo tambien define routing y manejo de excepciones.
- Permisos: tabla `rol_permisos` (`modulo`, `accion`) consultada por `Empleado::tienePermiso()`; el rol llamado exactamente `administrador` tiene bypass total. Hoy solo roles exige permiso; clientes y empleados solo piden `auth`. El sidebar (`config/navegacion.php`) lista todo item con `ruta`; `ruta => null` se muestra como "Pronto" deshabilitado (no valida permisos).
- Auditoria: los Services llaman a `AuditoriaService::registrar()` (metodo estatico) en crear/actualizar/eliminar/toggle, y `LoginService` registra inicio/cierre de sesion. Guarda JSON de antes/despues, IP y excluye `password`/`remember_token` en `app/Service/Auditoria/AuditoriaService.php:17`. Visor admin en `/auditoria`.
- Reglas de negocio en Services lanzan `DomainException`; el controlador la convierte en `with('error')` o JSON 422 (ver `RolController::actualizarEstado`). Borrado en dos pasos: primero deshabilitar (`estado = inactivo`), luego eliminar; el rol administrador es inmutable.
- Listados con busqueda/filtros: el `index` renderiza el parcial `_tabla`; si `$request->ajax()` el controlador devuelve `response()->view('modulo._tabla', ...)`. El JS del modulo hace fetch del parcial (header `X-Requested-With: XMLHttpRequest`) y los toggles de estado son rutas `PATCH` que responden JSON con `expectsJson()`.

## Auth

- La tabla de acceso es `empleados` (email/password/rol_id/acceso), **NO** `users`. `config/auth.php` usa `App\Models\Empleado` como provider `users`. No reintroduzcas `users`.
- Login exige `acceso = 'activo'`, regenera sesion y actualiza `ultimo_acceso_at` (`app/Service/Auth/LoginService.php`). El esquema pide coherencia entre `acceso`, `rol_id` y `password`; validala en la app.
- Usuario sembrado: `admin@viagest.com` / `1234` (`AdminUserSeeder`). `DemoDashboardSeeder` crea ventas demo solo si `ventas` esta vacio.
- `foto_perfil` usa el disco `public` (`storage:link`); edicion de perfil y cambio de clave en `/perfil`.
- Namespaces sensibles a mayusculas: `App\Http\Controllers\auth` (minuscula), `App\Http\Requests\Auth` y `App\Service\Auth`. En Linux carpeta y namespace deben coincidir.

## Modelos

- Solo existen `Empleado` (Authenticatable), `Rol`, `Sucursal`, `Cliente` y `Auditoria` (mapea `bitacora_auditoria`, sin timestamps salvo `created_at`).
- El resto de tablas **no tiene modelo**: usa `DB::table(...)` como ya se hace para `rol_permisos` (`RolService`), dependencias de borrado (`ClienteService::tieneRegistrosRelacionados`) y datos de dashboard. No crees un modelo por cada tabla sin necesidad.

## Entorno (bloqueante en esta maquina)

- El `php` del PATH es XAMPP (`C:\xampp\php`, **8.2.12**) y `composer install` falla (el proyecto exige `^8.3` y el lock trae paquetes que piden `>=8.4.1`). Usa PHP 8.4.16 en `C:\php\php.exe`: `$env:PATH = "C:\php;$env:PATH"` antes de `composer`/`php`.
- No hay `.env` en el repo (gitignored). Para trabajar: `Copy-Item .env.example .env`, `php artisan key:generate`, crear `database/database.sqlite` (no existe) y `php artisan migrate --seed`. `.env.example` es sqlite con `DB_DATABASE` comentado, por lo que el default es `database/database.sqlite`; el conector sqlite **lanza excepcion si el archivo no existe**, asi que `composer setup` falla en `migrate` si no lo creas antes.
- Los tests **requieren `APP_KEY`**: sin `.env`/key fallan 61 de 74 con "No application encryption key has been specified". Con `APP_KEY` definido los 74 pasan (verificado con `C:\php`). `phpunit.xml` fuerza `DB_CONNECTION=sqlite` + `DB_DATABASE=:memory:`; los tests no tocan tu BD real.
- Produccion: MySQL 8.0.16+. No agregues SQL/funciones exclusivas de MySQL en tests o migraciones.

## Comandos (Windows / PowerShell)

- Instalar: `composer install`, `npm install`, `php artisan storage:link`.
- BD: `php artisan migrate --seed` (o `migrate:fresh --seed`).
- Tests: `composer test` (limpia config y corre la suite). Un test: `php artisan test --filter=<nombre>`.
- Formato: `vendor\bin\pint` en archivos tocados (`--test` solo verifica). No hay `pint.json`: corre con defaults.
- Frontend: `npm run dev`, `npm run build`. **No hay** linter/formatter/typecheck JS ni CI (`.github` no existe).
- `composer dev` = `php artisan dev` (sirve el stack completo).
- `public/build/` esta en `.gitignore` pero **se commitea** (manifest y assets trackeados); tras `npm run build` aparece `M public/build/...` y forma parte del cambio.

## Esquema y migraciones (leer antes de crear/editar)

- Fuente de verdad: `database/migrations/2026_10_13_000001`-`000029` (29 tablas), `000030` (5 vistas `v_*`) y `000031` (`foto_perfil`). Una migracion por tabla, numeradas por dependencias FK; al agregar, continua despues de la ultima con fecha nueva (`AAAA_MM_DD_0000NN_*`). No reordenes ni consolides.
- Convenciones: snake_case en espanol y plural; PK `id`; FK `<entidad>_id`; `ENUM` para estados/tipos; dinero `DECIMAL(12,2)`; porcentajes `DECIMAL(10,4)`.
- Unicos/indices llevan nombres explicitos `uq_*`/`idx_*` (hay ~90); las FKs usan `->constrained()` por defecto, salvo la FK compuesta `fk_pagos_cliente_cuota_venta` (`(cuota_id, venta_id) -> cuotas_cobro (id, venta_id)`), que depende del unique `uq_cuotas_cobro_id_venta`. No lo elimines.
- Deben ser **portables** (solo Schema/Blueprint) porque los tests corren en sqlite. La migracion de vistas ramifica por driver con `DB::getDriverName()`. Los `CHECK` del diseno original no se tradujeron (Blueprint no los soporta): validalos en la app, no con SQL crudo.
- Datos derivados (totales, saldos, cupos disponibles, precio de paquete) viven en las vistas `v_*`; no agregues columnas para ellos (`DashboardService` las consulta con Query Builder y agrega en PHP).

## Vistas y frontend

- Layout unico `resources/views/layouts/app.blade.php` (sidebar colapsable con `localStorage` + topbar). Secciones `titulo`/`contenido` y `@push('scripts')`. Iconos: SVG inline en el mapa `$icons`; no hay componentes Blade ni libreria de iconos.
- Paleta/tema en `resources/css/app.css` (`@theme`): `primary #0b4f6c`, `secondary #1ca3aa`, `accent #f28f3b`, `background`, `surface`, `ink`; fuente Instrument Sans via Bunny fonts. Usa clases `bg-primary`, `text-ink`, `border-ink/10`, etc. No introduzcas paletas genericas ni `tailwind.config.js` (Tailwind 4 con `@tailwindcss/vite`; no hay PostCSS ni TypeScript).
- Cada modulo de listado tiene su entry Vite propio (`resources/js/clientes.js`, `empleados.js`, `roles.js`, `auditoria.js`) con `@vite(...)` en el `@push('scripts')` del index. **Al crear un modulo nuevo, agrega su JS al `input` de `vite.config.js`** o el build falla/queda sin asset.
- JS de listados: busqueda con debounce, `fetch` del parcial `_tabla`, actualizacion de URL con `history.replaceState`, confirmaciones y dialogos con **SweetAlert2** (`sweetalert2` es dependencia npm). Paginacion propia en `partials/paginacion.blade.php`; los paginadores usan `->withQueryString()`.
- Dashboard: **ApexCharts** solo en `resources/js/dashboard.js` (entry separado, pesado; no lo cargues globalmente). Datos por `window.dashboardGraficas` (`@json($graficas)`), no `dashboardCharts`. Gotcha: `@json(...)` debe ir en **una sola linea**.
- Dashboard y vistas de listado no scrollean en `lg+`: shell `h-screen overflow-hidden`, filas `lg:min-h-0 lg:flex-1`; en movil el sidebar es drawer con `overflow-y-auto`.

## Tests

- Feature tests por modulo en `tests/Feature/<Modulo>/`; usan `RefreshDatabase` y `$this->withoutVite()` en `setUp`.
- No existe `database/factories` (aunque composer declare el namespace): no hay `factory()`. Arma fixtures con `DB::table(...)->insertGetId(...)` en `ciudades`/`sucursales`/`roles` y luego `Model::create()` (patron de `LoginTest`/`RolTest`).
- El bypass de permisos depende de que el rol se llame exactamente `administrador`; los tests crean ese rol a mano. Verifica tambien filas en `bitacora_auditoria` cuando toques Services.

## Estandares (obligatorios)

- **Responsivo**: movil, tablet y escritorio (`sm:`/`md:`/`lg:`); la card de login es la referencia visual.
- **Profesional**: usa los tokens del tema, espaciado consistente, transiciones sutiles; evita estilos inline arbitrarios.
- **UX/UI**: jerarquia clara, coherencia entre pantallas, accesibilidad (contraste, `aria-*`, labels, `autocomplete`, foco y errores).
- **Backend limpio**: controlador delgado que solo orquesta, validacion/normalizacion en `FormRequest`, reglas en `app/Service`, Eloquent o Query Builder para datos; sin logica de negocio en la vista. Textos de UI en espanol.
