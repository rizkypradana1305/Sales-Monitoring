/* Hasil Alokasi page */
(function () {
    if (!window.Chart) return;
    const data = window.__hasilData || {};
    if (!data.sales_labels || !data.sales_labels.length) return;

    const c1 = document.getElementById('chartDistribusi');
    if (c1) {
        new Chart(c1, {
            type: 'bar',
            data: {
                labels: data.sales_labels,
                datasets: [{
                    label: 'Pelanggan',
                    data: data.pelanggan_counts,
                    backgroundColor: '#E31E24',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }

    const c2 = document.getElementById('chartJarak');
    if (c2) {
        new Chart(c2, {
            type: 'bar',
            data: {
                labels: data.sales_labels,
                datasets: [{
                    label: 'Total Jarak (km)',
                    data: data.jarak_totals,
                    backgroundColor: '#2563EB',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }
})();

function exportHasil() {
    const rows = [];
    document.querySelectorAll('.card .data-table').forEach((tbl, idx) => {
        const salesNama = tbl.closest('.card').querySelector('.card-title').textContent;
        tbl.querySelectorAll('tbody tr').forEach((tr, i) => {
            const cells = tr.querySelectorAll('td');
            if (cells.length >= 7) {
                rows.push([idx + 1, salesNama, cells[1].textContent.trim(), cells[2].textContent.trim(), cells[3].textContent.trim(), cells[4].textContent.trim(), cells[5].textContent.trim(), cells[6].textContent.trim()]);
            }
        });
    });
    let csv = 'No;Sales;Pelanggan;Alamat;Potensi;Jarak_km;Skor;Status\n';
    rows.forEach(r => { csv += r.map(x => `"${String(x).replace(/"/g, '""')}"`).join(';') + '\n'; });
    const blob = new Blob(["\uFEFF" + csv], { type: 'text/csv;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = 'hasil_alokasi_' + new Date().toISOString().slice(0,10) + '.csv';
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
    URL.revokeObjectURL(url);
    showToast('Hasil alokasi diekspor', 'success');
}
