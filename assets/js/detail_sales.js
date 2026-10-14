/* Detail Sales page */
(function () {
    const data = window.__detailData || {};
    const sales = data.sales;
    const pelanggan = data.pelanggan || [];

    // Build distance chart data
    const distances = pelanggan.map(p => {
        const km = haversine(sales.latitude, sales.longitude, p.latitude, p.longitude);
        return { name: p.nama, km: km };
    }).sort((a, b) => a.km - b.km);

    if (window.Chart && distances.length) {
        const ctx = document.getElementById('chartJarak');
        if (ctx) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: distances.map(d => d.name.length > 12 ? d.name.slice(0, 12) + '…' : d.name),
                    datasets: [{
                        label: 'Jarak (km)',
                        data: distances.map(d => d.km.toFixed(2)),
                        backgroundColor: '#E31E24',
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        }
    }

    // Init map
    if (window.L) {
        const map = L.map('mapDetail').setView([sales.latitude, sales.longitude], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        const salesIcon = L.divIcon({
            className: '',
            html: `<div style="width:36px;height:36px;background:#E31E24;color:white;border-radius:50% 50% 50% 0;transform:rotate(-45deg);display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:13px;border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.3)"><span style="transform:rotate(45deg)">${escapeHtml(initials(sales.nama))}</span></div>`,
            iconSize: [36, 36], iconAnchor: [18, 36]
        });
        L.marker([sales.latitude, sales.longitude], { icon: salesIcon }).addTo(map).bindPopup(`<strong>${escapeHtml(sales.nama)}</strong><br>Basis`);

        pelanggan.forEach(p => {
            const color = p.potensi === 'Tinggi' ? '#22C55E' : (p.potensi === 'Sedang' ? '#F59E0B' : '#9CA3AF');
            L.circleMarker([p.latitude, p.longitude], {
                radius: 7, color: color, fillColor: color, fillOpacity: 0.7, weight: 2
            }).addTo(map).bindPopup(`<strong>${escapeHtml(p.nama)}</strong><br>${escapeHtml(p.alamat||'')}<br>Potensi: ${p.potensi} (${p.skor_potensi})`);
        });
        setTimeout(() => map.invalidateSize(), 200);
    }

    function haversine(lat1, lon1, lat2, lon2) {
        const R = 6371;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLon/2) * Math.sin(dLon/2);
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    }
    function initials(name) {
        if (!name) return '?';
        return name.split(' ').slice(0, 2).map(w => w.charAt(0).toUpperCase()).join('');
    }
})();

function exportDetail() {
    const data = window.__detailData || {};
    let csv = `Detail Sales: ${data.sales.nama}\nWilayah: ${data.sales.wilayah}\nKapasitas: ${data.sales.kapasitas}\n\nNo;Nama;Wilayah;Potensi;Skor;Status\n`;
    (data.pelanggan || []).forEach((p, i) => {
        csv += [i+1, `"${p.nama}"`, p.wilayah, p.potensi, p.skor_potensi, p.status].join(';') + '\n';
    });
    const blob = new Blob(["\uFEFF" + csv], { type: 'text/csv;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = 'detail_sales_' + data.sales.id + '.csv';
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
    URL.revokeObjectURL(url);
    showToast('Detail diekspor', 'success');
}
