/* Data Sales page */

function applyFilters() {
    const q = document.getElementById('searchInput').value.trim();
    const s = document.getElementById('fStatus').value;
    const w = document.getElementById('fWilayah').value;
    const params = new URLSearchParams();
    if (q) params.set('q', q);
    if (s) params.set('status', s);
    if (w) params.set('wilayah', w);
    document.location.href = 'data_sales.php?' + params.toString();
}

function exportSalesCSV() {
    const rows = window.__salesData || [];
    let csv = 'No;Nama;Email;Telepon;Wilayah;Kapasitas;Pelanggan;Status\n';
    rows.forEach((s, i) => {
        csv += [i+1, `"${s.nama}"`, `"${s.email||''}"`, s.telepon||'', s.wilayah, s.kapasitas, s.jml_pelanggan, s.status].join(';') + '\n';
    });
    const blob = new Blob(["\uFEFF" + csv], { type: 'text/csv;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = 'data_sales_' + new Date().toISOString().slice(0,10) + '.csv';
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
    URL.revokeObjectURL(url);
    showToast('CSV berhasil diunduh', 'success');
}

function openSalesModal(id) {
    const sales = id ? (window.__salesData || []).find(s => Number(s.id) === Number(id)) : null;
    const wilayahList = window.__wilayahList || [];
    const wilayahOptions = ['Madiun Kota','Madiun Barat','Madiun Timur','Madiun Selatan','Madiun Utara','Kartoharjo','Taman','Mangunharjo','Caruban','Kare','Mejayan','Wungu','Jiwan']
        .map(w => `<option value="${w}" ${sales && sales.wilayah===w?'selected':''}>${w}</option>`).join('');

    openModal(`
        <div class="modal-overlay" style="display:flex">
            <div class="modal modal-lg">
                <div class="modal-header">
                    <h3>${sales ? 'Edit Sales' : 'Tambah Sales Baru'}</h3>
                    <button class="modal-close" data-close-modal>&times;</button>
                </div>
                <div class="modal-body">
                    <form id="salesForm">
                        <input type="hidden" name="id" value="${sales ? sales.id : ''}">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nama Lengkap <span style="color:#E31E24">*</span></label>
                                <input type="text" class="form-control" name="nama" value="${sales ? escapeAttr(sales.nama) : ''}" required placeholder="cth: Tatang Mu'alifin">
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" class="form-control" name="email" value="${sales ? escapeAttr(sales.email||'') : ''}" placeholder="nama@telkom.co.id">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Telepon</label>
                                <input type="text" class="form-control" name="telepon" value="${sales ? escapeAttr(sales.telepon||'') : ''}" placeholder="08xxxxxxxxxx">
                            </div>
                            <div class="form-group">
                                <label>NIK</label>
                                <input type="text" class="form-control" name="nik" value="${sales ? escapeAttr(sales.nik||'') : ''}" placeholder="3501010101900001">
                            </div>
                        </div>
                        <div class="form-row-3">
                            <div class="form-group">
                                <label>Wilayah <span style="color:#E31E24">*</span></label>
                                <select class="form-control" name="wilayah" required>
                                    <option value="">- Pilih -</option>
                                    ${wilayahOptions}
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Kapasitas (pelanggan) <span style="color:#E31E24">*</span></label>
                                <input type="number" class="form-control" name="kapasitas" min="1" max="50" value="${sales ? sales.kapasitas : 15}" required>
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select class="form-control" name="status">
                                    <option value="Aktif" ${sales && sales.status==='Aktif'?'selected':''}>Aktif</option>
                                    <option value="Cuti" ${sales && sales.status==='Cuti'?'selected':''}>Cuti</option>
                                    <option value="Nonaktif" ${sales && sales.status==='Nonaktif'?'selected':''}>Nonaktif</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Latitude <span style="color:#E31E24">*</span></label>
                                <input type="number" step="0.000001" class="form-control" name="latitude" value="${sales ? sales.latitude : '-7.629800'}" required>
                                <small style="font-size:11px;color:#9CA3AF">Klik pada peta untuk mengisi otomatis</small>
                            </div>
                            <div class="form-group">
                                <label>Longitude <span style="color:#E31E24">*</span></label>
                                <input type="number" step="0.000001" class="form-control" name="longitude" value="${sales ? sales.longitude : '111.523900'}" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Tanggal Bergabung</label>
                            <input type="date" class="form-control" name="tanggal_bergabung" value="${sales && sales.tanggal_bergabung ? sales.tanggal_bergabung.slice(0,10) : ''}">
                        </div>
                        <div id="mapPicker" style="height:200px;border-radius:8px;border:1px solid #E5E7EB;margin-top:8px;"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline" data-close-modal>Batal</button>
                    <button class="btn btn-primary" onclick="saveSales()">${sales ? 'Simpan Perubahan' : 'Simpan Sales'}</button>
                </div>
            </div>
        </div>
    `);

    // Init mini map for picking location
    setTimeout(initPickerMap, 100);
}

function initPickerMap() {
    const latInput = document.querySelector('#salesForm [name="latitude"]');
    const lngInput = document.querySelector('#salesForm [name="longitude"]');
    const el = document.getElementById('mapPicker');
    if (!el || !window.L) return;
    const lat = parseFloat(latInput.value) || -7.6298;
    const lng = parseFloat(lngInput.value) || 111.5239;
    const map = L.map(el).setView([lat, lng], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: 'OpenStreetMap'
    }).addTo(map);
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

function saveSales() {
    const form = document.getElementById('salesForm');
    const formData = new FormData(form);
    fetch('ajax/sales_save.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.ok) {
                closeModal();
                showToast(res.msg || 'Sales berhasil disimpan', 'success');
                setTimeout(() => location.reload(), 600);
            } else {
                showToast(res.msg || 'Gagal menyimpan', 'error');
            }
        })
        .catch(() => showToast('Kesalahan jaringan', 'error'));
}

function deleteSales(id, nama) {
    confirmDialog({
        title: 'Hapus Sales',
        message: `Yakin ingin menghapus "${nama}"? Data pelanggan terkait akan dilepas dari sales ini.`,
        confirmText: 'Hapus',
        onConfirm: () => {
            fetch('ajax/sales_delete.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ id }) })
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
