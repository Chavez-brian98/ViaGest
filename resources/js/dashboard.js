import ApexCharts from 'apexcharts';

const data = window.dashboardCharts || {};

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

render('#chartVentasMes', {
    ...baseOptions,
    chart: { ...baseOptions.chart, type: 'area' },
    series: [{ name: 'Ventas', data: data.ventasPorMes?.series ?? [] }],
    xaxis: { categories: data.ventasPorMes?.labels ?? [], axisBorder: { show: false }, axisTicks: { show: false } },
    colors: ['#1ca3aa'],
    stroke: { curve: 'smooth', width: 3 },
    fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.04, stops: [0, 100] } },
    yaxis: { labels: { formatter: money } },
    tooltip: { ...baseOptions.tooltip, y: { formatter: money } },
});

render('#chartVentasServicio', {
    ...baseOptions,
    chart: { ...baseOptions.chart, type: 'donut' },
    series: data.ventasPorServicio?.series ?? [],
    labels: data.ventasPorServicio?.labels ?? [],
    colors: ['#0b4f6c', '#1ca3aa', '#f28f3b', '#5c8fa6', '#8fd3d6', '#f7b578'],
    legend: { position: 'bottom', fontSize: '12px', markers: { size: 6 } },
    plotOptions: { pie: { donut: { size: '68%' } } },
    tooltip: { ...baseOptions.tooltip, y: { formatter: money } },
});

render('#chartVentasSucursal', {
    ...baseOptions,
    chart: { ...baseOptions.chart, type: 'bar' },
    series: [{ name: 'Ventas', data: data.ventasPorSucursal?.series ?? [] }],
    xaxis: { categories: data.ventasPorSucursal?.labels ?? [], axisBorder: { show: false }, axisTicks: { show: false } },
    colors: ['#0b4f6c'],
    plotOptions: { bar: { borderRadius: 6, columnWidth: '45%' } },
    yaxis: { labels: { formatter: money } },
    tooltip: { ...baseOptions.tooltip, y: { formatter: money } },
});
