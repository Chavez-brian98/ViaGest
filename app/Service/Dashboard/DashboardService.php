<?php

namespace App\Service\Dashboard;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    private array $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

    public function resumen(): array
    {
        return [
            'metricas' => $this->metricas(),
            'ventasPorMes' => $this->ventasPorMes(),
            'ventasPorServicio' => $this->ventasPorServicio(),
            'ventasPorSucursal' => $this->ventasPorSucursal(),
            'cuotasProximas' => $this->cuotasProximas(),
        ];
    }

    private function metricas(): array
    {
        $ventas = DB::table('v_venta_totales')
            ->where('estado', '!=', 'cancelada')
            ->get(['fecha_venta', 'total', 'utilidad_bruta', 'estado']);

        $mesActual = CarbonImmutable::now()->format('Y-m');
        $mesAnterior = CarbonImmutable::now()->subMonthNoOverflow()->format('Y-m');

        $delMes = $ventas->filter(fn ($venta) => str_starts_with((string) $venta->fecha_venta, $mesActual));
        $anterior = $ventas->filter(fn ($venta) => str_starts_with((string) $venta->fecha_venta, $mesAnterior));

        $ventasMes = (float) $delMes->sum('total');
        $ventasMesAnterior = (float) $anterior->sum('total');

        return [
            'ventasMes' => $ventasMes,
            'utilidadMes' => (float) $delMes->sum('utilidad_bruta'),
            'variacionVentas' => $ventasMesAnterior > 0
                ? (($ventasMes - $ventasMesAnterior) / $ventasMesAnterior) * 100
                : null,
            'saldoPorCobrar' => (float) DB::table('v_ventas_saldo')->where('saldo', '>', 0)->sum('saldo'),
            'cuposDisponibles' => (int) DB::table('v_cupos_disponibles')
                ->where('fecha_inicio', '>=', CarbonImmutable::now()->toDateString())
                ->sum('disponible'),
            'ventasAbiertas' => $ventas->whereIn('estado', ['reservada', 'confirmada', 'en_curso'])->count(),
        ];
    }

    private function ventasPorMes(): array
    {
        $inicio = CarbonImmutable::now()->startOfMonth()->subMonths(5);

        $ventas = DB::table('v_venta_totales')
            ->where('estado', '!=', 'cancelada')
            ->where('fecha_venta', '>=', $inicio->toDateString())
            ->get(['fecha_venta', 'total']);

        $labels = [];
        $series = [];

        for ($i = 0; $i < 6; $i++) {
            $mes = $inicio->addMonthsNoOverflow($i);
            $labels[] = $this->meses[$mes->month - 1];
            $series[] = round((float) $ventas
                ->filter(fn ($venta) => str_starts_with((string) $venta->fecha_venta, $mes->format('Y-m')))
                ->sum('total'), 2);
        }

        return ['labels' => $labels, 'series' => $series];
    }

    private function ventasPorServicio(): array
    {
        $filas = DB::table('venta_servicios as vs')
            ->join('servicios as s', 's.id', '=', 'vs.servicio_id')
            ->where('vs.estado', '!=', 'cancelado')
            ->selectRaw('s.nombre as etiqueta, SUM(vs.cantidad * vs.precio_unitario) as total')
            ->groupBy('s.nombre')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        return [
            'labels' => $filas->pluck('etiqueta')->all(),
            'series' => $filas->map(fn ($fila) => round((float) $fila->total, 2))->all(),
        ];
    }

    private function ventasPorSucursal(): array
    {
        $filas = DB::table('v_venta_totales as t')
            ->join('sucursales as s', 's.id', '=', 't.sucursal_id')
            ->where('t.estado', '!=', 'cancelada')
            ->selectRaw('s.nombre as etiqueta, SUM(t.total) as total')
            ->groupBy('s.nombre')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $filas->pluck('etiqueta')->all(),
            'series' => $filas->map(fn ($fila) => round((float) $fila->total, 2))->all(),
        ];
    }

    private function cuotasProximas(): array
    {
        return DB::table('v_cuotas_cobro_estado as c')
            ->join('ventas as v', 'v.id', '=', 'c.venta_id')
            ->join('clientes as cl', 'cl.id', '=', 'v.cliente_id')
            ->whereIn('c.estado_pago', ['pendiente', 'parcial', 'vencida'])
            ->orderBy('c.fecha_vencimiento')
            ->limit(5)
            ->get([
                'c.numero', 'c.fecha_vencimiento', 'c.saldo', 'c.estado_pago',
                'v.codigo as venta_codigo',
                'cl.nombres', 'cl.apellidos', 'cl.razon_social',
            ])
            ->map(fn ($fila) => [
                'cliente' => $this->nombreCliente($fila),
                'venta' => $fila->venta_codigo,
                'cuota' => (int) $fila->numero,
                'vence' => CarbonImmutable::parse($fila->fecha_vencimiento)->format('d/m/Y'),
                'saldo' => (float) $fila->saldo,
                'estado' => $fila->estado_pago,
            ])
            ->all();
    }

    private function nombreCliente(object $fila): string
    {
        $persona = trim(($fila->nombres ?? '').' '.($fila->apellidos ?? ''));

        return $persona !== '' ? $persona : ($fila->razon_social ?? 'Cliente');
    }
}
