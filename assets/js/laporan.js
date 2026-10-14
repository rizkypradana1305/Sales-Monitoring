/* Laporan page */
(function () {
    if (!window.Chart) return;
    const data = window.__laporanData || {};

    const c1 = document.getElementById('chartSalesAlloc');
    if (c1) {
        new Chart(c1, {
            type: 'bar',
            data: {
                labels: data.sales_labels || [],
                datasets: [{ label: 'Pelanggan', data: data.sales_jml || [], backgroundColor: '#E31E24', borderRadius: 6 }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
        });
    }

    const c2 = document.getElementById('chartTrenJarak');
    if (c2) {
        new Chart(c2, {
            type: 'line',
            data: {
                labels: data.run_labels || [],
                datasets: [{
                    label: 'Total Jarak (km)',
                    data: data.run_jarak || [],
                    borderColor: '#2563EB',
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    fill: true, tension: 0.3, pointRadius: 4, pointBackgroundColor: '#2563EB'
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });
    }
})();

function exportLaporan() {
    // Compile from tables on the page
    let csv = 'LAPORAN ALOKASI SALES TELKOM MADIUN\nTanggal: ' + new Date().toLocaleDateString('id-ID') + '\n\n=== Ringkasan Sales ===\nNo;Nama;Wilayah;Kapasitas;Pelanggan;Status\n';
    const tables = document.querySelectorAll('.card .data-table');
    if (tables[0]) {
        tables[0].querySelectorAll('tbody tr').forEach((tr, i) => {
            const c = tr.querySelectorAll('td');
            if (c.length >= 7) csv += [i+1, `"${c[1].textContent.trim()}"`, c[2].textContent.trim(), c[3].textContent.trim(), c[4].textContent.trim(), c[6].textContent.trim()].join(';') + '\n';
        });
    }
    csv += '\n=== Riwayat Run ===\nKode;Parameter;Status;Teralokasi;Jarak;Durasi;Tanggal\n';
    if (tables[1]) {
        tables[1].querySelectorAll('tbody tr').forEach(tr => {
            const c = tr.querySelectorAll('td');
            if (c.length >= 7) csv += c[0].textContent.trim() + ';' + c[1].textContent.trim() + ';' + c[2].textContent.trim() + ';' + c[3].textContent.trim() + ';' + c[4].textContent.trim() + ';' + c[5].textContent.trim() + ';' + c[6].textContent.trim() + '\n';
        });
    }
    const blob = new Blob(["\uFEFF" + csv], { type: 'text/csv;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = 'laporan_' + new Date().toISOString().slice(0,10) + '.csv';
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
    URL.revokeObjectURL(url);
    showToast('Laporan diekspor', 'success');
}
