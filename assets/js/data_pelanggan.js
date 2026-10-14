/* Data Pelanggan page */
function applyFilters() {
    const q = document.getElementById('searchInput').value.trim();
    const s = document.getElementById('fStatus').value;
    const p = document.getElementById('fPotensi').value;
    const sa = document.getElementById('fSales').value;
    const params = new URLSearchParams();
    if (q) params.set('q', q);
    if (s) params.set('status', s);
    if (p) params.set('potensi', p);
    if (sa) params.set('sales_id', sa);
    document.location.href = 'data_pelanggan.php?' + params.toString();
}

function exportPelangganCSV() {
    const rows = window.__pelangganData || [];
    let csv = 'No;Nama;Alamat;Wilayah;Potensi;Skor;Produk;Status;Sales\n';
    rows.forEach((p, i) => {
        csv += [i+1, `"${p.nama}"`, `"${p.alamat||''}"`, p.wilayah, p.potensi, p.skor_potensi, `"${p.produk||''}"`, p.status, `"${p.sales_nama||''}"`].join(';') + '\n';
    });
    const blob = new Blob(["\uFEFF" + csv], { type: 'text/csv;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = 'data_pelanggan_' + new Date().toISOString().slice(0,10) + '.csv';
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
    URL.revokeObjectURL(url);
    showToast('CSV berhasil diunduh', 'success');
}

function openPelangganModal(id) {
    const pel = id ? (window.__pelangganData || []).find(p => Number(p.id) === Number(id)) : null;
    const salesList = window.__salesList || [];
    const salesOpts = salesList.map(s => `<option value="${s.id}" ${pel && Number(pel.sales_id)===Number(s.id)?'selected':''}>${escapeHtml(s.nama)}</option>`).join('');

    openModal(`
        <div class="modal-overlay" style="display:flex">
            <div class="modal modal-lg">
                <div class="modal-header">
                    <h3>${pel ? 'Edit Pelanggan' : 'Tambah Pelanggan Baru'}</h3>
                    <button class="modal-close" data-close-modal>&times;</button>
                </div>
                <div class="modal-body">
                    <form id="pelangganForm">
                        <input type="hidden" name="id" value="${pel ? pel.id : ''}">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nama Pelanggan <span style="color:#E31E24">*</span></label>
                                <input type="text" class="form-control" name="nama" value="${pel ? escapeAttr(pel.nama) : ''}" required>
                            </div>
                            <div class="form-group">
                                <label>Wilayah <span style="color:#E31E24">*</span></label>
                                <select class="form-control" name="wilayah" required>
                                    <option value="">- Pilih -</option>
                                    ${['Madiun Kota','Madiun Barat','Madiun Timur','Madiun Selatan','Madiun Utara','Kartoharjo','Taman','Mangunharjo'].map(w=>`<option value="${w}" ${pel&&pel.wilayah===w?'selected':''}>${w}</option>`).join('')}
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Alamat</label>
                            <textarea class="form-control" name="alamat">${pel ? escapeHtml(pel.alamat||'') : ''}</textarea>
                        </div>
                        <div class="form-row-3">
                            <div class="form-group">
                                <label>Potensi</label>
                                <select class="form-control" name="potensi">
                                    <option value="Tinggi" ${pel&&pel.potensi==='Tinggi'?'selected':''}>Tinggi</option>
                                    <option value="Sedang" ${pel&&pel.potensi==='Sedang'?'selected':''}>Sedang</option>
                                    <option value="Rendah" ${pel&&pel.potensi==='Rendah'?'selected':''}>Rendah</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Skor Potensi (0-100)</label>
                                <input type="number" class="form-control" name="skor_potensi" min="0" max="100" value="${pel ? pel.skor_potensi : 50}">
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select class="form-control" name="status">
                                    <option value="Prospek" ${pel&&pel.status==='Prospek'?'selected':''}>Prospek</option>
                                    <option value="Aktif" ${pel&&pel.status==='Aktif'?'selected':''}>Aktif</option>
                                    <option value="Pending" ${pel&&pel.status==='Pending'?'selected':''}>Pending</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Produk</label>
                                <select class="form-control" name="produk">
                                    <option value="">- Pilih -</option>
                                    ${['IndiHome','Internet Bisnis','Wifi ID','Astinet','IIX / IX','VPN IP','Cloud Server'].map(x=>`<option value="${x}" ${pel&&pel.produk===x?'selected':''}>${x}</option>`).join('')}
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Sales Penanggung Jawab</label>
                                <select class="form-control" name="sales_id">
                                    <option value="">- Belum dialokasi -</option>
                                    ${salesOpts}
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Latitude <span style="color:#E31E24">*</span></label>
                                <input type="number" step="0.000001" class="form-control" name="latitude" value="${pel ? pel.latitude : '-7.629800'}" required>
                            </div>
                            <div class="form-group">
                                <label>Longitude <span style="color:#E31E24">*</span></label>
                                <input type="number" step="0.000001" class="form-control" name="longitude" value="${pel ? pel.longitude : '111.523900'}" required>
                            </div>
                        </div>
                        <div id="mapPicker" style="height:200px;border-radius:8px;border:1px solid #E5E7EB;margin-top:8px;"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline" data-close-modal>Batal</button>
                    <button class="btn btn-primary" onclick="savePelanggan()">${pel ? 'Simpan Perubahan' : 'Simpan Pelanggan'}</button>
                </div>
            </div>
        </div>
    `);
    setTimeout(initPickerMap, 100);
}

function initPickerMap() {
    const latInput = document.querySelector('#pelangganForm [name="latitude"]');
    const lngInput = document.querySelector('#pelangganForm [name="longitude"]');
    const el = document.getElementById('mapPicker');
    if (!el || !window.L) return;
    const lat = parseFloat(latInput.value) || -7.6298;
    const lng = parseFloat(lngInput.value) || 111.5239;
    const map = L.map(el).setView([lat, lng], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
    let marker = L.marker([lat, lng], { draggable: true }).addTo(map);
    marker.on('dragend', e => {
        const p = e.target.getLatLng();
        latInput.value = p.lat.toFixed(6);
        lngInput.value = p.lng.toFixed(6);
    });
    map.on('click', e => {
        marker.setLatLng(e.latlng);
        latInput.value = e.latlng.lat.toFixed(6);
        lngInput.value = e.latlng.lng.toFixed(6);
    });
    setTimeout(() => map.invalidateSize(), 200);
}

function savePelanggan() {
    const form = document.getElementById('pelangganForm');
    const formData = new FormData(form);
    fetch('ajax/pelanggan_save.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.ok) {
                closeModal();
                showToast(res.msg, 'success');
                setTimeout(() => location.reload(), 600);
            } else {
                showToast(res.msg, 'error');
            }
        });
}

function deletePelanggan(id, nama) {
    confirmDialog({
        title: 'Hapus Pelanggan',
        message: `Yakin ingin menghapus "${nama}"? Tindakan ini tidak dapat dibatalkan.`,
        confirmText: 'Hapus',
        onConfirm: () => {
            fetch('ajax/pelanggan_delete.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ id }) })
                .then(r => r.json())
                .then(res => {
                    showToast(res.msg, res.ok ? 'success' : 'error');
                    if (res.ok) setTimeout(() => location.reload(), 600);
                });
        }
    });
}

function escapeAttr(s) {
    if (s == null) return '';
    return String(s).replace(/"/g, '&quot;');
}
