import ApexCharts from 'apexcharts';

const graficas = window.dashboardGraficas || {};

const money = (value) =>
    new Intl.NumberFormat('es-SV', {
        style: 'currency',
        currency: 'USD',
        maximumFractionDigits: 0,
    }).format(Number(value) || 0);

const baseOptions = {
    chart: {
        height: '100%',
        fontFamily: 'inherit',
        toolbar: { show: false },
        animations: { easing: 'easeinout', speed: 400 },
    },
    dataLabels: { enabled: false },
    grid: { borderColor: 'rgba(31, 41, 51, 0.1)', strokeDashArray: 4, padding: { left: 8, right: 8 } },
    tooltip: { theme: 'light' },
};

const render = (selector, options) => {
    const element = document.querySelector(selector);

    if (!element) {
        return;
    }

    new ApexCharts(element, options).render();
};

render('#chartEvolucion12Meses', {
    ...baseOptions,
    chart: { ...baseOptions.chart, type: 'area' },
    series: [{ name: 'Ventas', data: graficas.evolucion12Meses?.series ?? [] }],
    xaxis: { categories: graficas.evolucion12Meses?.labels ?? [], axisBorder: { show: false }, axisTicks: { show: false } },
    colors: ['#1ca3aa'],
    stroke: { curve: 'smooth', width: 3 },
    fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.04, stops: [0, 100] } },
    yaxis: { labels: { formatter: money } },
    tooltip: { ...baseOptions.tooltip, y: { formatter: money } },
});

render('#chartVentasPorSucursalMes', {
    ...baseOptions,
    chart: { ...baseOptions.chart, type: 'bar' },
    series: [{ name: 'Ventas', data: graficas.ventasPorSucursalMes?.series ?? [] }],
    xaxis: { categories: graficas.ventasPorSucursalMes?.labels ?? [], axisBorder: { show: false }, axisTicks: { show: false } },
    colors: ['#0b4f6c'],
    plotOptions: { bar: { borderRadius: 6, horizontal: true, barHeight: '55%' } },
    yaxis: { labels: { formatter: money } },
    tooltip: { ...baseOptions.tooltip, y: { formatter: money } },
});

render('#chartVentasPorTipoServicio', {
    ...baseOptions,
    chart: { ...baseOptions.chart, type: 'donut' },
    series: graficas.ventasPorTipoServicio?.series ?? [],
    labels: graficas.ventasPorTipoServicio?.labels ?? [],
    colors: ['#0b4f6c', '#1ca3aa', '#f28f3b', '#5c8fa6', '#8fd3d6'],
    legend: { position: 'bottom', fontSize: '12px', markers: { size: 6 } },
    plotOptions: { pie: { donut: { size: '68%' } } },
    tooltip: { ...baseOptions.tooltip, y: { formatter: money } },
});

render('#chartEstadoCobranza', {
    ...baseOptions,
    chart: { ...baseOptions.chart, type: 'bar' },
    series: [{ name: 'Monto', data: graficas.estadoCobranza?.series ?? [] }],
    xaxis: { categories: graficas.estadoCobranza?.labels ?? [], axisBorder: { show: false }, axisTicks: { show: false } },
    colors: ['#10b981', '#f59e0b', '#94a3b8', '#ef4444'],
    plotOptions: { bar: { borderRadius: 6, columnWidth: '45%' } },
    yaxis: { labels: { formatter: money } },
    tooltip: { ...baseOptions.tooltip, y: { formatter: money } },
});

render('#chartTopVendedores', {
    ...baseOptions,
    chart: { ...baseOptions.chart, type: 'bar' },
    series: [{ name: 'Ventas', data: graficas.topVendedores?.series ?? [] }],
    xaxis: { categories: graficas.topVendedores?.labels ?? [], axisBorder: { show: false }, axisTicks: { show: false } },
    colors: ['#0b4f6c'],
    plotOptions: { bar: { borderRadius: 6, horizontal: true, barHeight: '55%' } },
    yaxis: { labels: { formatter: money } },
    tooltip: { ...baseOptions.tooltip, y: { formatter: money } },
});

render('#chartCuposCriticos', {
    ...baseOptions,
    chart: { ...baseOptions.chart, type: 'bar' },
    series: [{ name: 'Disponibles', data: graficas.cuposCriticos?.series ?? [] }],
    xaxis: { categories: graficas.cuposCriticos?.labels ?? [], axisBorder: { show: false }, axisTicks: { show: false } },
    colors: ['#f28f3b'],
    plotOptions: { bar: { borderRadius: 6, horizontal: true, barHeight: '55%' } },
    yaxis: { labels: { formatter: (v) => `${Math.round(v)}` } },
    tooltip: { ...baseOptions.tooltip, y: { formatter: (v) => `${Math.round(v)} cupos` } },
});
