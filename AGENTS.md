# ViaGest - AGENTS.md

ERP para agencia de viajes (Laravel 13, PHP ^8.3, Vite + Tailwind 4).

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

## Auth

- La tabla de acceso es `empleados` (email/password/rol_id/acceso), NO `users`. Modelo: `App\Models\Empleado`; `config/auth.php` apunta ahi. No reintroduzcas `users`.

## Comandos (Windows / PowerShell)

- Instalar: `composer install`, luego `npm install`.
- Base de datos: `php artisan migrate --seed` (o `php artisan migrate:fresh --seed`).
- Tests: `composer test` o `php artisan test` (sqlite en memoria).
- Formato: `vendor\bin\pint` (usar en archivos tocados; `--test` solo verifica). Correr antes de dar por terminado.
- Frontend: `npm run dev`, build `npm run build`.
- `composer dev` ejecuta `php artisan dev` (sirve todo el stack).

## Config de BD

- `.env` trae `DB_CONNECTION=sqlite` con `DB_DATABASE=laravel` (valor invalido como ruta). Para MySQL real: `DB_CONNECTION=mysql` + host/credenciales. La app productiva es MySQL 8.0.16+.
- phpunit fuerza sqlite `:memory:`; los tests no dependen de MySQL.

## Datos iniciales

- `DatabaseSeeder` llama a `ConfiguracionEmpresaSeeder` (fila id=1), `RolesSeeder` (5 roles) y `RolPermisosSeeder` (permisos de `administrador`). Permiso = par (`modulo`, `accion`) en `rol_permisos`.
