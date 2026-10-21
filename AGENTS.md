# ViaGest - AGENTS.md

ERP interno para agencia de viajes (Laravel 13, PHP ^8.3, Blade server-side, Vite + Tailwind 4). No hay API ni portal publico: todo se sirve por `routes/web.php`.

## Login (estado actual)

- Rutas: `GET /` (`login`) y `POST /` (`login.store`); el login vive en la raiz (no existe `/login`). Logout: `POST /logout` (`logout`). Vista en `resources/views/auth/login.blade.php`, controlador `App\Http\Controllers\auth\LoginController` y logica en `App\Service\Auth\LoginService`.
- UI: card de credenciales centrada sobre fondo de pantalla completa; el video de fondo va en `public/videos/login.mp4` (capa `object-cover`). Si no existe el archivo, queda un gradiente de respaldo.
- Autenticacion contra `empleados` exigiendo `acceso = 'activo'`; al entrar se regenera la sesion y se actualiza `ultimo_acceso_at`.
- `configuracion_empresa.imagenes_login` es un JSON de **imagenes**, no de video. Si el video debe ser configurable, agrega una migracion nueva (despues de `000030`); si es fijo, usa un asset en `public/` y `asset()`.
- Solo existe el modelo `Empleado`; el resto del esquema no tiene modelos Eloquent. Los seeders no crean empleados, asi que no hay usuario de prueba por defecto.

## Auth

- La tabla de acceso es `empleados` (email/password/rol_id/acceso), NO `users`. `config/auth.php` apunta a `App\Models\Empleado`, aunque el provider siga llamandose `users`. No reintroduzcas `users`.
- El esquema exige coherencia entre `acceso`, `rol_id` y `password` (`ck_empleados_acceso`); valida esa regla en la app antes de guardar.
- Namespaces/ubicaciones actuales: controlador en `app/Http/Controllers/auth` con `App\Http\Controllers\auth` (minuscula), request en `App\Http\Requests\Auth` (mayuscula) y servicio en `App\Service\Auth` (singular). En Linux deben coincidir exactamente carpeta y namespace.

## Fuente de verdad del dominio

- El esquema vive en las migraciones `database/migrations/2026_10_13_*`: `000001`-`000029` son las 29 tablas y `000030` las 5 vistas `v_*`. El antiguo `schema_erp_agencia_viajes_v2.sql` ya no esta en el repo; no lo busques como contrato.
- Convenciones del esquema: snake_case en espanol y plural; PK `id`; FK `<entidad>_id`; `ENUM` para estados/tipos; dinero `DECIMAL(12,2)`; porcentajes/valores `DECIMAL(10,4)`; `created_at`/`updated_at`.
- Datos derivados (totales, saldos, cupos disponibles, precio de paquete) NO se guardan en tablas: viven en las vistas `v_*`. No agregues columnas para ellos.
- Reglas de negocio (solapamiento de agenda de guias, cupos al vender, coherencia pasaporte/documento) se validan en la aplicacion, no solo en la BD.

## Migraciones (leer antes de crear/editar)

- Una migracion por tabla, en `database/migrations/2026_10_13_0000NN_*`, numeradas en orden de dependencias FK. Al agregar tablas, continua despues de `000030`. No reordenes ni consolides.
- Deben ser **portables** (solo Schema/Blueprint). La suite de tests corre en sqlite `:memory:` (`phpunit.xml`), asi que NO uses DDL crudo de MySQL, funciones MySQL ni columnas generadas especificas. Las vistas (`2026_10_13_000030_create_erp_views.php`) ramifican por driver (`DB::getDriverName()`).
- Los `CHECK` del diseno original **no** se traducen a migraciones (Blueprint no los soporta): aplicalos en la app o en validacion. No los agregues como SQL crudo.
- Se usan nombres de constraint por defecto de Laravel (no `fk_*`/`idx_*` heredados). Los nombres `uq_*`/`idx_*` explicitos solo donde el diseno original los exigia.
- Dependencia especial: `pagos_cliente` tiene FK compuesta `(cuota_id, venta_id) -> cuotas_cobro (id, venta_id)`. Conserva el unique `uq_cuotas_cobro_id_venta` o esa FK falla.

## Comandos (Windows / PowerShell)

- Instalar: `composer install`, luego `npm install`.
- Base de datos: `php artisan migrate --seed` (o `php artisan migrate:fresh --seed`).
- Tests: `composer test` o `php artisan test` (sqlite en memoria). Un test puntual: `php artisan test --filter=<nombre>`.
- Formato: `vendor\bin\pint` (usar en archivos tocados; `--test` solo verifica). Correr antes de dar por terminado.
- Frontend: `npm run dev`, build `npm run build`. No hay linter/formatter JS ni typecheck.
- `composer dev` ejecuta `php artisan dev` (sirve todo el stack).

## Config de BD

- En este checkout `.env` apunta a MySQL local (`DB_DATABASE=Viagest`, usuario root). `.env.example` sigue con `DB_CONNECTION=sqlite` y `DB_DATABASE=laravel`, que no es una ruta valida.
- phpunit fuerza sqlite `:memory:`; los tests no dependen de MySQL. La app productiva es MySQL 8.0.16+.
- `database/factories` esta vacio: no hay `factory()`. `LoginTest` arma sus fixtures con `DB::table(...)->insertGetId(...)` en `ciudades`/`sucursales`/`roles` y luego `Empleado::create()`.
- Los tests de vista usan `$this->withoutVite()` porque el manifiesto de Vite no existe en CI; los tests de login estan en `tests/Feature/Auth/LoginTest.php`.

## Vistas (Blade)

- `resources/views/layouts/app.blade.php` es el primer layout del proyecto (sidebar colapsable + topbar). Las vistas autenticadas lo extienden con `@extends('layouts.app')` y llenan `@section('titulo')`, `@section('contenido')` y `@push('scripts')`. El menu se define en `config/navegacion.php`.
- `resources/views/auth/login.blade.php` sigue siendo una pagina HTML completa e independiente (con `@fonts` y `@vite`); `welcome.blade.php` es el scaffold de Laravel.
- No hay componentes Blade ni libreria de iconos: los iconos del sidebar son SVG inline (mapa `$icons` en el layout). No hay Font Awesome.
- Tailwind 4 se importa en `resources/css/app.css` con `@import 'tailwindcss'` y el plugin `@tailwindcss/vite`; no hay `tailwind.config.js`, PostCSS ni TypeScript. `resources/js/app.js` solo controla el sidebar.

## Dashboard y layout autenticado

- Al autenticar, `LoginController` redirige a `route('dashboard')` (ya no a `home`). `GET /dashboard` (`auth`) -> `App\Http\Controllers\DashboardController` -> `App\Service\Dashboard\DashboardService`.
- `DashboardService` consulta las vistas `v_*` con Query Builder y agrega en PHP (evita funciones MySQL para mantener portabilidad sqlite/MySQL). Devuelve `metricas` + `ventasPorMes`/`ventasPorServicio`/`ventasPorSucursal`/`cuotasProximas`.
- Graficas con **ApexCharts** (dependencia npm). Se importa solo en `resources/js/dashboard.js` (entry Vite separado; pesa ~900 kB, no lo cargues globalmente). Los datos viajan a JS por `window.dashboardCharts` con `@json`.
- El dashboard no scrollea en `lg+`: el shell es `h-screen overflow-hidden` y las filas de graficas usan `lg:min-h-0 lg:flex-1`. En movil si hay scroll (drawer + `overflow-y-auto`).
- Gotcha: `@json(...)` de Blade se rompe si el array va en varias lineas; pasalo en una sola linea.

## Paleta de colores (temporal, hasta nuevo aviso)

Definida como tokens de Tailwind 4 en `@theme` dentro de `resources/css/app.css`; usa las clases `bg-primary`, `text-ink`, `bg-secondary/15`, `border-ink/10`, etc.:

- `--color-primary` `#0B4F6C` (azul oceano): botones, item activo del sidebar, brand.
- `--color-secondary` `#1CA3AA` (turquesa): focos/hover, tints claros, avatar.
- `--color-accent` `#F28F3B` (naranja): detalles decorativos y ring de foco del boton de login.
- `--color-background` `#F5F8FA` (blanco hielo): fondo de paginas e inputs.
- `--color-surface` `#FFFFFF`: cards y barras superiores.
- `--color-ink` `#1F2933` (gris carbón): texto principal; `text-ink/45`-`/70` para tenue.
- Colores semanticos (rose=error, emerald=variacion positiva, amber=pendiente) se conservan SOLO para estados de datos, no para marca. Las graficas ApexCharts (`resources/js/dashboard.js`) usan los mismos hex: si cambias la paleta, cambia ambos lugares.

## Estandares de diseno y codigo (obligatorios)

- **Responsivo**: toda vista debe funcionar en movil, tablet y escritorio (grids/flex + breakpoints `sm:`/`md:`/`lg:`). La card de login (`auth/login.blade.php`) es el ejemplo a seguir.
- **Profesional**: tipografia y fuentes consistentes (`--font-sans` en `resources/css/app.css`), espaciado cuidado, paleta coherente (ver seccion "Paleta de colores"; nada de colores de marca fuera de los tokens) y transiciones sutiles. Evita estilos inline arbitrarios.
- **UX/UI**: jerarquia visual clara, coherencia y uniformidad entre pantallas, navegacion simple, y accesibilidad (contraste, `aria-hidden`, labels, `autocomplete`, estados de foco y de error). Reusa las clases de la card de login.
- **Backend limpio**: aplica SOLID, DRY y KISS al estilo ya establecido: controlador delgado que solo orquesta, validacion en `FormRequest`, logica de negocio en `app/Service`, modelos Eloquent para acceso a datos. Sin SQL crudo ni logica de negocio en la vista.

## Datos iniciales

- `DatabaseSeeder` llama a `ConfiguracionEmpresaSeeder` (fila id=1), `RolesSeeder` (5 roles) y `RolPermisosSeeder` (permisos de `administrador`). Permiso = par (`modulo`, `accion`) en `rol_permisos`.
