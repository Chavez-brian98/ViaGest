# ViaGest - AGENTS.md

ERP interno para agencia de viajes (Laravel 13, PHP ^8.3, Blade server-side, Vite + Tailwind 4). No hay API ni portal publico: todo se sirve por `routes/web.php`.

## Estado actual del login (leer antes de tocar auth)

- El scaffold esta incompleto: `routes/web.php` solo tiene `/` -> `welcome`; no existen rutas ni vistas de login. `LoginController` esta vacio, `LoginRequest::authorize()` devuelve `false` y `LoginService::logout()` no compila.
- Requisito de diseno: **card de credenciales centrada** sobre un **fondo de pantalla completa con espacio para un video**. Usa una capa de video absoluta (`object-cover`) detras de la card (`relative z-10`), no reutilices `welcome.blade.php` (es el scaffold con CSS inline gigante).
- Crea la vista en `resources/views/auth/` y cargala con `@vite(['resources/css/app.css', 'resources/js/app.js'])` + `@fonts` para conservar Instrument Sans.
- `configuracion_empresa.imagenes_login` es un JSON de **imagenes**, no de video. Si el video debe ser configurable, agrega una migracion nueva (despues de `000030`); si es fijo, usa un asset en `public/` y `asset()`.
- Solo existe el modelo `Empleado`; el resto del esquema no tiene modelos Eloquent. Los seeders no crean empleados, asi que no hay usuario de prueba por defecto.
- `App\Models\Empleado` declara `protected $Fillable` (mayuscula); no lo usa Laravel. Al tocarlo, corrige a `$fillable`.

## Auth

- La tabla de acceso es `empleados` (email/password/rol_id/acceso), NO `users`. `config/auth.php` apunta a `App\Models\Empleado`, aunque el provider siga llamandose `users`. No reintroduzcas `users`.
- El esquema exige coherencia entre `acceso`, `rol_id` y `password` (`ck_empleados_acceso`); valida esa regla en la app antes de guardar.
- Namespaces/ubicaciones actuales: controlador en `app/Http/Controllers/auth` con `App\Http\Controllers\auth` (minuscula), request en `App\Http\Requests\Auth` (mayuscula) y servicio en `App\Service\Auth` (singular). En Linux deben coincidir exactamente carpeta y namespace.

## Fuente de verdad del dominio

- `schema_erp_agencia_viajes_v2.sql` (raiz) define 29 tablas + 5 vistas MySQL 8. Es el contrato del modelo de datos; consultalo antes de tocar tablas.
- Convenciones del esquema: snake_case en espanol y plural; PK `id`; FK `<entidad>_id`; `ENUM` para estados/tipos; dinero `DECIMAL(12,2)`; porcentajes/valores `DECIMAL(10,4)`; `created_at`/`updated_at`.
- Datos derivados (totales, saldos, cupos disponibles, precio de paquete) NO se guardan en tablas: viven en las vistas `v_*`. No agregues columnas para ellos.
- Reglas de negocio (solapamiento de agenda de guias, cupos al vender, coherencia pasaporte/documento) se validan en la aplicacion, no solo en la BD.

## Migraciones (leer antes de crear/editar)

- Una migracion por tabla, en `database/migrations/2026_10_13_0000NN_*`, numeradas en orden de dependencias FK. Al agregar tablas, continua despues de `000030`. No reordenes ni consolides.
- Deben ser **portables** (solo Schema/Blueprint). La suite de tests corre en sqlite `:memory:` (`phpunit.xml`), asi que NO uses DDL crudo de MySQL, funciones MySQL ni columnas generadas especificas. Las vistas (`2026_10_13_000030_create_erp_views.php`) ramifican por driver (`DB::getDriverName()`).
- Los `CHECK` del SQL **no** se traducen a migraciones (Blueprint no los soporta): aplicalos en la app o en validacion. No los agregues como SQL crudo.
- Se usan nombres de constraint por defecto de Laravel (no los `fk_*`/`idx_*` del SQL). Los nombres `uq_*`/`idx_*` explicitos solo donde el SQL los exigia.
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
- Solo hay tests de ejemplo; `composer test` pasa aunque el login siga roto.

## Datos iniciales

- `DatabaseSeeder` llama a `ConfiguracionEmpresaSeeder` (fila id=1), `RolesSeeder` (5 roles) y `RolPermisosSeeder` (permisos de `administrador`). Permiso = par (`modulo`, `accion`) en `rol_permisos`.
