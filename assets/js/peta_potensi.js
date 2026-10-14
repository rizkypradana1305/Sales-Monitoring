/* Map page */
let map, salesLayer = L.layerGroup(), pelangganLayer = L.layerGroup();
let heatmapLayer = null;
let allPelangganMarkers = [];

function initMap() {
    map = L.map('map').setView([-7.6298, 111.5239], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap',
        maxZoom: 19
    }).addTo(map);

    salesLayer.addTo(map);
    pelangganLayer.addTo(map);

    drawSales();
    drawPelanggan();

    document.getElementById('showSales').addEventListener('change', function() {
        if (this.checked) salesLayer.addTo(map);
        else map.removeLayer(salesLayer);
    });
    document.getElementById('showPelanggan').addEventListener('change', function() {
        if (this.checked) pelangganLayer.addTo(map);
        else map.removeLayer(pelangganLayer);
    });
    document.getElementById('filterPotensi').addEventListener('change', drawPelanggan);
    document.getElementById('filterWilayah').addEventListener('change', drawPelanggan);
}

function drawSales() {
    salesLayer.clearLayers();
    const sales = window.__salesData || [];
    sales.forEach(s => {
        const icon = L.divIcon({
            className: 'custom-marker',
            html: `<div style="width:32px;height:32px;background:#E31E24;color:white;border-radius:50% 50% 50% 0;transform:rotate(-45deg);display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:11px;border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.3)"><span style="transform:rotate(45deg)">${escapeHtml(initials(s.nama))}</span></div>`,
            iconSize: [32, 32],
            iconAnchor: [16, 32],
        });
        const m = L.marker([s.latitude, s.longitude], { icon }).addTo(salesLayer);
        m.bindPopup(`
            <div style="min-width:200px">
                <div style="font-weight:700;color:#E31E24;font-size:13px;margin-bottom:4px">${escapeHtml(s.nama)}</div>
                <div style="font-size:12px;color:#6B7280">Wilayah: ${escapeHtml(s.wilayah)}</div>
                <div style="font-size:12px;color:#6B7280">Kapasitas: ${s.kapasitas} pelanggan</div>
                <div style="font-size:12px;color:#6B7280">Telepon: ${escapeHtml(s.telepon||'-')}</div>
                <a href="detail_sales.php?id=${s.id}" style="display:inline-block;margin-top:6px;color:#2563EB;font-weight:600;font-size:12px">Lihat Detail →</a>
            </div>
        `);
    });
}

function drawPelanggan() {
    pelangganLayer.clearLayers();
    allPelangganMarkers = [];
    const potensi = document.getElementById('filterPotensi').value;
    const wilayah = document.getElementById('filterWilayah').value;
    const pelanggan = window.__pelangganData || [];
    let count = 0;
    pelanggan.forEach(p => {
        if (potensi && p.potensi !== potensi) return;
        if (wilayah && p.wilayah !== wilayah) return;
        const color = p.potensi === 'Tinggi' ? '#22C55E' : (p.potensi === 'Sedang' ? '#F59E0B' : '#9CA3AF');
        const radius = p.potensi === 'Tinggi' ? 9 : (p.potensi === 'Sedang' ? 7 : 5);
        const m = L.circleMarker([p.latitude, p.longitude], {
            radius: radius, color: color, fillColor: color,
            fillOpacity: 0.7, weight: 2
        }).addTo(pelangganLayer);
        m.bindPopup(`
            <div style="min-width:220px">
                <div style="font-weight:700;font-size:13px;margin-bottom:4px">${escapeHtml(p.nama)}</div>
                <div style="font-size:12px;color:#6B7280">${escapeHtml(p.alamat||'')}</div>
                <div style="font-size:12px;color:#6B7280;margin-top:4px">Wilayah: ${escapeHtml(p.wilayah)}</div>
                <div style="font-size:12px;color:#6B7280">Potensi: <strong style="color:${color}">${p.potensi}</strong> (${p.skor_potensi})</div>
                <div style="font-size:12px;color:#6B7280">Produk: ${escapeHtml(p.produk||'-')}</div>
                <div style="font-size:12px;color:#6B7280">Status: ${p.status}</div>
                ${p.sales_nama ? `<div style="font-size:12px;color:#2563EB;font-weight:600;margin-top:4px">PJ: ${escapeHtml(p.sales_nama)}</div>` : ''}
            </div>
        `);
        allPelangganMarkers.push(m);
        count++;
    });
}

function toggleHeatmap() {
    if (heatmapLayer) {
        map.removeLayer(heatmapLayer);
        heatmapLayer = null;
        showToast('Heatmap dinonaktifkan', 'info');
    } else {
        // Simple "heatmap" using overlapping circles
        heatmapLayer = L.layerGroup();
        const pelanggan = window.__pelangganData || [];
        pelanggan.forEach(p => {
            const color = p.potensi === 'Tinggi' ? '#E31E24' : (p.potensi === 'Sedang' ? '#F59E0B' : '#9CA3AF');
            L.circle([p.latitude, p.longitude], {
                radius: 250, color: 'transparent',
                fillColor: color, fillOpacity: 0.25
            }).addTo(heatmapLayer);
        });
        heatmapLayer.addTo(map);
        showToast('Heatmap diaktifkan', 'success');
    }
}

function resetMapView() {
    map.setView([-7.6298, 111.5239], 13);
    showToast('Tampilan peta direset', 'info');
}

function initials(name) {
    if (!name) return '?';
    return name.split(' ').slice(0, 2).map(w => w.charAt(0).toUpperCase()).join('');
}

document.addEventListener('DOMContentLoaded', initMap);
