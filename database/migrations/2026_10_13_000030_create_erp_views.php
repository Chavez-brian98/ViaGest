<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $views = [
        'v_paquetes_precio',
        'v_venta_totales',
        'v_ventas_saldo',
        'v_cuotas_cobro_estado',
        'v_cupos_disponibles',
    ];

    public function up(): void
    {
        $curdate = $this->currentDateExpression();

        $this->createView('v_paquetes_precio', <<<'SQL'
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
            ) c
            SQL);

        $this->createView('v_venta_totales', <<<'SQL'
            SELECT v.id AS venta_id,
                   v.codigo,
                   v.sucursal_id,
                   v.vendedor_id,
                   v.fecha_venta,
                   v.estado,
                   COALESCE(SUM(vs.cantidad * vs.costo_neto_unitario), 0)                 AS costo_total,
                   COALESCE(SUM(vs.cantidad * vs.precio_unitario), 0)                     AS subtotal,
                   v.descuento_monto                                                      AS descuento,
                   COALESCE(SUM(vs.cantidad * vs.precio_unitario), 0) - v.descuento_monto AS total,
                   COALESCE(SUM(vs.cantidad * vs.precio_unitario), 0) - v.descuento_monto
                     - COALESCE(SUM(vs.cantidad * vs.costo_neto_unitario), 0)             AS utilidad_bruta
            FROM ventas v
            LEFT JOIN venta_servicios vs ON vs.venta_id = v.id AND vs.estado <> 'cancelado'
            GROUP BY v.id, v.codigo, v.sucursal_id, v.vendedor_id, v.fecha_venta, v.estado, v.descuento_monto
            SQL);

        $this->createView('v_ventas_saldo', <<<'SQL'
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
            ) p ON p.venta_id = t.venta_id
            SQL);

        $this->createView('v_cuotas_cobro_estado', <<<SQL
            SELECT q.cuota_id, q.venta_id, q.numero, q.fecha_vencimiento, q.monto, q.pagado,
                   q.monto - q.pagado AS saldo,
                   CASE
                     WHEN q.monto - q.pagado <= 0         THEN 'pagada'
                     WHEN q.fecha_vencimiento < {$curdate} THEN 'vencida'
                     WHEN q.pagado > 0                     THEN 'parcial'
                     ELSE 'pendiente'
                   END AS estado_pago
            FROM (
              SELECT c.id AS cuota_id, c.venta_id, c.numero, c.fecha_vencimiento, c.monto,
                     COALESCE(SUM(CASE WHEN pc.estado = 'aplicado' THEN pc.monto END), 0) AS pagado
              FROM cuotas_cobro c
              LEFT JOIN pagos_cliente pc ON pc.cuota_id = c.id
              WHERE c.estado = 'activa'
              GROUP BY c.id, c.venta_id, c.numero, c.fecha_vencimiento, c.monto
            ) q
            SQL);

        $this->createView('v_cupos_disponibles', <<<'SQL'
            SELECT cu.id AS cupo_id,
                   cu.servicio_id,
                   cu.fecha_inicio,
                   cu.fecha_fin,
                   cu.cantidad_total,
                   COALESCE(SUM(vs.cantidad), 0)                     AS vendido,
                   cu.cantidad_total - COALESCE(SUM(vs.cantidad), 0) AS disponible
            FROM cupos cu
            LEFT JOIN venta_servicios vs ON vs.cupo_id = cu.id AND vs.estado <> 'cancelado'
            GROUP BY cu.id, cu.servicio_id, cu.fecha_inicio, cu.fecha_fin, cu.cantidad_total
            SQL);
    }

    public function down(): void
    {
        foreach (array_reverse($this->views) as $view) {
            DB::statement("DROP VIEW IF EXISTS {$view}");
        }
    }

    private function createView(string $name, string $sql): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("DROP VIEW IF EXISTS {$name}");
            DB::statement("CREATE VIEW {$name} AS {$sql}");

            return;
        }

        DB::statement("CREATE OR REPLACE VIEW {$name} AS {$sql}");
    }

    private function currentDateExpression(): string
    {
        return DB::getDriverName() === 'sqlite' ? "DATE('now')" : 'CURDATE()';
    }
};
