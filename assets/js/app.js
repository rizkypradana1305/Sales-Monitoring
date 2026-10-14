/* ============================================================
   Sales Territory Optimizer - Main JS
   ============================================================ */

// ---------- Global search ----------
document.addEventListener('DOMContentLoaded', function () {
    const s = document.getElementById('globalSearch');
    if (s) {
        s.addEventListener('keypress', function (e) {
            if (e.key === 'Enter' && s.value.trim()) {
                window.location.href = 'data_pelanggan.php?q=' + encodeURIComponent(s.value.trim());
            }
        });
    }
});

// ---------- Modal helpers ----------
function openModal(html) {
    const root = document.getElementById('modal-root');
    root.innerHTML = html;
    const overlay = root.querySelector('.modal-overlay');
    requestAnimationFrame(() => overlay.classList.add('show'));
    document.body.style.overflow = 'hidden';
    // wire close buttons
    overlay.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', closeModal);
    });
    overlay.addEventListener('click', e => {
        if (e.target === overlay) closeModal();
    });
    document.addEventListener('keydown', escClose);
    return overlay;
}
function escClose(e) { if (e.key === 'Escape') closeModal(); }
function closeModal() {
    const root = document.getElementById('modal-root');
    const overlay = root.querySelector('.modal-overlay');
    if (overlay) {
        overlay.classList.remove('show');
        setTimeout(() => root.innerHTML = '', 200);
    }
    document.body.style.overflow = '';
    document.removeEventListener('keydown', escClose);
}

// ---------- Confirm modal ----------
function confirmDialog(opts) {
    const o = Object.assign({
        title: 'Konfirmasi', message: 'Apakah Anda yakin?',
        confirmText: 'Hapus', confirmClass: 'btn-danger', onConfirm: () => {}
    }, opts);
    openModal(`
        <div class="modal-overlay" style="display:flex">
            <div class="modal" style="max-width:420px">
                <div class="modal-header">
                    <h3>${escapeHtml(o.title)}</h3>
                    <button class="modal-close" data-close-modal>&times;</button>
                </div>
                <div class="modal-body">
                    <p style="font-size:14px;color:#4B5563">${escapeHtml(o.message)}</p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline" data-close-modal>Batal</button>
                    <button class="btn ${o.confirmClass}" id="confirmBtn">${escapeHtml(o.confirmText)}</button>
                </div>
            </div>
        </div>
    `);
    document.getElementById('confirmBtn').addEventListener('click', () => {
        closeModal();
        o.onConfirm();
    });
}

// ---------- Toast notifications ----------
function showToast(message, type = 'success') {
    const colors = { success: '#22C55E', error: '#DC2626', warning: '#F59E0B', info: '#2563EB' };
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed; top: 20px; right: 20px;
        background: white; color: #1F2937;
        padding: 14px 18px; border-radius: 8px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        border-left: 4px solid ${colors[type] || colors.success};
        z-index: 1000; min-width: 280px; font-size: 14px;
        display: flex; align-items: center; gap: 10px;
        animation: slideIn 0.25s ease;
    `;
    toast.innerHTML = `<span style="color:${colors[type] || colors.success};font-weight:700;">●</span> ${escapeHtml(message)}`;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.transition = 'opacity 0.3s, transform 0.3s';
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(20px)';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}
const style = document.createElement('style');
style.textContent = '@keyframes slideIn { from { transform: translateX(40px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }';
document.head.appendChild(style);

// ---------- AJAX helper ----------
function ajax(url, opts = {}) {
    opts.method = opts.method || 'GET';
    opts.headers = opts.headers || {};
    if (opts.body && !(opts.body instanceof FormData)) {
        opts.headers['Content-Type'] = 'application/json';
        opts.body = JSON.stringify(opts.body);
    }
    return fetch(url, opts).then(r => {
        if (r.redirected && r.url.includes('login.php')) {
            window.location.href = 'login.php';
            return Promise.reject(new Error('Session expired'));
        }
        return r.json();
    });
}

// ---------- Form submit via AJAX ----------
function submitFormAjax(form, url, opts = {}) {
    const formData = new FormData(form);
    return fetch(url, { method: 'POST', body: formData })
        .then(r => r.json());
}

// ---------- Escape HTML ----------
function escapeHtml(s) {
    if (s == null) return '';
    return String(s).replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;' }[c]));
}

// ---------- Format number (Indonesian) ----------
function fmtNum(n, dec = 0) {
    return Number(n || 0).toLocaleString('id-ID', { minimumFractionDigits: dec, maximumFractionDigits: dec });
}

// ---------- Sidebar toggle (mobile) ----------
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('show');
}

// ---------- Pagination / Search for tables ----------
function initTableSearch(inputId, tableId, columnCount = -1) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    if (!input || !table) return;
    input.addEventListener('input', function () {
        const q = this.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(q) ? '' : 'none';
        });
    });
}

// ---------- Animated number counter ----------
function animateCounter(el, target, duration = 800) {
    if (!el) return;
    const start = 0;
    const startTime = performance.now();
    function update(now) {
        const elapsed = now - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const current = Math.floor(start + (target - start) * easeOut(progress));
        el.textContent = fmtNum(current);
        if (progress < 1) requestAnimationFrame(update);
        else el.textContent = fmtNum(target);
    }
    requestAnimationFrame(update);
}
function easeOut(t) { return 1 - Math.pow(1 - t, 3); }

document.addEventListener('DOMContentLoaded', function () {
    // Animate stat cards
    document.querySelectorAll('[data-counter]').forEach(el => {
        const target = parseFloat(el.dataset.counter);
        animateCounter(el, target);
    });
});
