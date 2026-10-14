/* Dashboard charts */
(function () {
    if (!window.Chart) return;
    const data = window.__chartData || {};
    const primary = '#E31E24';
    const blue = '#2563EB';
    const green = '#22C55E';
    const yellow = '#F59E0B';
    const gray = '#9CA3AF';

    // Bar: pelanggan per sales
    const c1 = document.getElementById('chartSales');
    if (c1) {
        new Chart(c1, {
            type: 'bar',
            data: {
                labels: data.sales.labels || [],
                datasets: [{
                    label: 'Pelanggan',
                    data: data.sales.data || [],
                    backgroundColor: primary,
                    borderRadius: 6,
                    barPercentage: 0.7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } },
                    x: { ticks: { maxRotation: 35, minRotation: 0, autoSkip: false } }
                }
            }
        });
    }

    // Stacked bar: per wilayah
    const c2 = document.getElementById('chartWilayah');
    if (c2) {
        new Chart(c2, {
            type: 'bar',
            data: {
                labels: data.wilayah.labels || [],
                datasets: [
                    { label: 'Tinggi', data: data.wilayah.tinggi || [], backgroundColor: primary, borderRadius: 4 },
                    { label: 'Sedang', data: data.wilayah.sedang || [], backgroundColor: blue, borderRadius: 4 },
                    { label: 'Rendah', data: data.wilayah.rendah || [], backgroundColor: gray, borderRadius: 4 },
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: {
                    x: { stacked: true, beginAtZero: true },
                    y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } }
                },
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    // Doughnut: status
    const c3 = document.getElementById('chartStatus');
    if (c3) {
        new Chart(c3, {
            type: 'doughnut',
            data: {
                labels: data.status.labels || [],
                datasets: [{
                    data: data.status.data || [],
                    backgroundColor: [green, primary, yellow],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '65%',
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }
})();
