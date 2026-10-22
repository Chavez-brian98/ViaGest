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
            'graficas' => $this->graficas(),
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
            'cuotasVencidas' => (int) DB::table('v_cuotas_cobro_estado')->where('estado_pago', 'vencida')->count(),
            'cuposDisponibles' => (int) DB::table('v_cupos_disponibles')
                ->where('fecha_inicio', '>=', CarbonImmutable::now()->toDateString())
                ->sum('disponible'),
            'ventasAbiertas' => $ventas->whereIn('estado', ['reservada', 'confirmada', 'en_curso'])->count(),
        ];
    }

    private function graficas(): array
    {
        return [
            'evolucion12Meses' => $this->evolucion12Meses(),
            'ventasPorSucursalMes' => $this->ventasPorSucursalMes(),
            'ventasPorTipoServicio' => $this->ventasPorTipoServicio(),
            'estadoCobranza' => $this->estadoCobranza(),
            'topVendedores' => $this->topVendedores(),
            'cuposCriticos' => $this->cuposCriticos(),
        ];
    }

    private function evolucion12Meses(): array
    {
        $inicio = CarbonImmutable::now()->startOfMonth()->subMonths(11);
        $ventas = DB::table('v_venta_totales')->where('estado', '!=', 'cancelada')->where('fecha_venta', '>=', $inicio->toDateString())->get(['fecha_venta', 'total']);

        $labels = [];
        $series = [];
        for ($i = 0; $i < 12; $i++) {
            $mes = $inicio->addMonthsNoOverflow($i);
            $labels[] = $this->meses[$mes->month - 1].' '.$mes->format('y');
            $series[] = round((float) $ventas->filter(fn ($venta) => str_starts_with((string) $venta->fecha_venta, $mes->format('Y-m')))->sum('total'), 2);
        }
        return ['labels' => $labels, 'series' => $series];
    }

    private function ventasPorSucursalMes(): array
    {
        $mesActual = CarbonImmutable::now()->format('Y-m');
        $driver = DB::getDriverName();
        $query = DB::table('v_venta_totales as t')->join('sucursales as s', 's.id', '=', 't.sucursal_id')->where('t.estado', '!=', 'cancelada');
        if ($driver === 'sqlite') {
            $query->whereRaw("strftime('%Y-%m', t.fecha_venta) = ?", [$mesActual]);
        } elseif ($driver === 'mysql') {
            $query->whereRaw("DATE_FORMAT(t.fecha_venta, '%Y-%m') = ?", [$mesActual]);
        } else {
            $query->whereRaw("to_char(t.fecha_venta, 'YYYY-MM') = ?", [$mesActual]);
        }
        $filas = $query->selectRaw('s.nombre as etiqueta, SUM(t.total) as total')->groupBy('s.nombre')->orderByDesc('total')->get();
        return ['labels' => $filas->pluck('etiqueta')->all(), 'series' => $filas->map(fn ($fila) => round((float) $fila->total, 2))->all()];
    }

    private function ventasPorTipoServicio(): array
    {
        $filas = DB::table('venta_servicios as vs')
            ->join('servicios as s', 's.id', '=', 'vs.servicio_id')
            ->join('ventas as v', 'v.id', '=', 'vs.venta_id')
            ->where('vs.estado', '!=', 'cancelado')
            ->where('v.estado', '!=', 'cancelada')
            ->where('v.fecha_venta', '>=', CarbonImmutable::now()->startOfMonth()->subMonths(2)->toDateString())
            ->selectRaw('s.tipo as etiqueta, SUM(vs.cantidad * vs.precio_unitario) as total')
            ->groupBy('s.tipo')
            ->orderByDesc('total')
            ->get();

        $mapa = ['vuelo' => 'Vuelos', 'hospedaje' => 'Hospedajes', 'transporte' => 'Transporte', 'actividad' => 'Actividades', 'paquete' => 'Paquetes', 'otro' => 'Otros'];
        return ['labels' => $filas->map(fn ($fila) => $mapa[$fila->etiqueta] ?? ucfirst((string) $fila->etiqueta))->all(), 'series' => $filas->map(fn ($fila) => round((float) $fila->total, 2))->all()];
    }

    private function estadoCobranza(): array
    {
        $pagado = (float) DB::table('pagos_cliente')->where('estado', 'aplicado')->sum('monto');
        $pendiente = (float) DB::table('v_cuotas_cobro_estado')->where('estado_pago', 'pendiente')->sum('saldo');
        $parcial = (float) DB::table('v_cuotas_cobro_estado')->where('estado_pago', 'parcial')->sum('saldo');
        $vencido = (float) DB::table('v_cuotas_cobro_estado')->where('estado_pago', 'vencida')->sum('saldo');
        return ['labels' => ['Pagado', 'Parcial', 'Pendiente', 'Vencido'], 'series' => [round($pagado, 2), round($parcial, 2), round($pendiente, 2), round($vencido, 2)]];
    }

    private function topVendedores(): array
    {
        $filas = DB::table('v_venta_totales as t')
            ->join('empleados as e', 'e.id', '=', 't.vendedor_id')
            ->where('t.estado', '!=', 'cancelada')
            ->where('t.fecha_venta', '>=', CarbonImmutable::now()->startOfMonth()->toDateString())
            ->selectRaw("TRIM(CONCAT(COALESCE(e.nombres,''),' ',COALESCE(e.apellidos,''))) as vendedor, SUM(t.total) as total")
            ->groupBy('e.id', 'e.nombres', 'e.apellidos')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
        return ['labels' => $filas->pluck('vendedor')->all(), 'series' => $filas->map(fn ($fila) => round((float) $fila->total, 2))->all()];
    }

    private function cuposCriticos(): array
    {
        $filas = DB::table('v_cupos_disponibles as c')
            ->join('servicios as s', 's.id', '=', 'c.servicio_id')
            ->where('c.fecha_inicio', '>=', CarbonImmutable::now()->toDateString())
            ->where('c.disponible', '>', 0)
            ->where('c.disponible', '<=', 6)
            ->selectRaw("CONCAT(s.nombre,' (',c.fecha_inicio,')') as etiqueta, c.disponible")
            ->orderBy('c.disponible')
            ->limit(6)
            ->get();
        return ['labels' => $filas->pluck('etiqueta')->all(), 'series' => $filas->pluck('disponible')->map(fn ($v) => (int) $v)->all()];
    }

    public function ventasPorMes(int $meses = 12): array
    {
        $inicio = CarbonImmutable::now()->startOfMonth()->subMonths($meses - 1);
        $ventas = DB::table('v_venta_totales')->where('estado', '!=', 'cancelada')->where('fecha_venta', '>=', $inicio->toDateString())->get(['fecha_venta', 'total']);
        $etiquetas = [];
        $montos = [];
        for ($i = 0; $i < $meses; $i++) {
            $mes = $inicio->addMonthsNoOverflow($i);
            $etiquetas[] = $this->meses[$mes->month - 1].' '.$mes->format('y');
            $montos[] = round((float) $ventas->filter(fn ($venta) => str_starts_with((string) $venta->fecha_venta, $mes->format('Y-m')))->sum('total'), 2);
        }
        return ['labels' => $etiquetas, 'data' => $montos];
    }

    public function ventasPorServicio(): array
    {
        $filas = DB::table('venta_servicios as vs')
            ->join('servicios as s', 's.id', '=', 'vs.servicio_id')
            ->join('ventas as v', 'v.id', '=', 'vs.venta_id')
            ->where('vs.estado', '!=', 'cancelado')
            ->where('v.estado', '!=', 'cancelada')
            ->where('v.fecha_venta', '>=', CarbonImmutable::now()->startOfMonth()->subMonths(2)->toDateString())
            ->selectRaw('s.tipo as tipo, SUM(vs.cantidad * vs.precio_unitario) as total')
            ->groupBy('s.tipo')
            ->orderByDesc('total')
            ->get();

        $mapa = ['vuelo' => 'Vuelos', 'hospedaje' => 'Hospedajes', 'transporte' => 'Transporte', 'actividad' => 'Actividades', 'paquete' => 'Paquetes', 'otro' => 'Otros'];
        return $filas->map(fn ($fila) => ['label' => $mapa[$fila->tipo] ?? ucfirst((string) $fila->tipo), 'value' => round((float) $fila->total, 2)])->all();
    }

    public function ventasPorSucursal(): array
    {
        $mesActual = CarbonImmutable::now()->format('Y-m');
        $driver = DB::getDriverName();
        $query = DB::table('v_venta_totales as t')->join('sucursales as s', 's.id', '=', 't.sucursal_id')->where('t.estado', '!=', 'cancelada');
        if ($driver === 'sqlite') {
            $query->whereRaw("strftime('%Y-%m', t.fecha_venta) = ?", [$mesActual]);
        } elseif ($driver === 'mysql') {
            $query->whereRaw("DATE_FORMAT(t.fecha_venta, '%Y-%m') = ?", [$mesActual]);
        } else {
            $query->whereRaw("to_char(t.fecha_venta, 'YYYY-MM') = ?", [$mesActual]);
        }
        $filas = $query->selectRaw('s.nombre as sucursal, SUM(t.total) as total')->groupBy('s.nombre')->orderByDesc('total')->get();
        return $filas->map(fn ($fila) => ['label' => $fila->sucursal, 'value' => round((float) $fila->total, 2)])->all();
    }

    public function cuotasProximas(int $limite = 8): array
    {
        return DB::table('v_cuotas_cobro_estado as c')
            ->join('ventas as v', 'v.id', '=', 'c.venta_id')
            ->join('clientes as cl', 'cl.id', '=', 'v.cliente_id')
            ->whereIn('c.estado_pago', ['pendiente', 'parcial', 'vencida'])
            ->orderByRaw("CASE WHEN c.estado_pago='vencida' THEN 0 WHEN c.estado_pago='parcial' THEN 1 ELSE 2 END")
            ->orderBy('c.fecha_vencimiento')
            ->limit($limite)
            ->get([
                'c.numero', 'c.fecha_vencimiento', 'c.monto', 'c.pagado', 'c.saldo', 'c.estado_pago',
                'v.codigo as venta_codigo',
                'cl.nombres', 'cl.apellidos', 'cl.razon_social',
            ])
            ->map(function ($fila) {
                $persona = trim(($fila->nombres ?? '').' '.($fila->apellidos ?? ''));
                $cliente = $persona !== '' ? $persona : ($fila->razon_social ?? 'Cliente');
                return [
                    'venta' => $fila->venta_codigo,
                    'cliente' => $cliente,
                    'cuota' => (int) $fila->numero,
                    'vence' => CarbonImmutable::parse($fila->fecha_vencimiento)->format('d/m/Y'),
                    'saldo' => (float) $fila->saldo,
                    'estado' => $fila->estado_pago,
                ];
            })->all();
    }
}
