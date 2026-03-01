/**
 * includes/realtime.js
 * Shared real-time AJAX utility for PetAdopt system.
 * Handles: live search, debouncing, skeleton loaders, toast notifications, auto-refresh.
 */

/* ═══════════════════════════════════════════════
   GLOBAL UTILITIES
═══════════════════════════════════════════════ */

/** Debounce: delay function call until user stops typing */
function debounce(fn, ms = 320) {
    let t;
    return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
}

/** Show/hide a loading shimmer overlay on a table body */
function setTableLoading(tbody, cols = 5) {
    tbody.style.opacity = '0.45';
    tbody.style.pointerEvents = 'none';
}
function clearTableLoading(tbody) {
    tbody.style.opacity = '1';
    tbody.style.pointerEvents = '';
}

/* ═══════════════════════════════════════════════
   TOAST NOTIFICATION
═══════════════════════════════════════════════ */
(function injectToastCSS() {
    if (document.getElementById('rt-toast-css')) return;
    const s = document.createElement('style');
    s.id = 'rt-toast-css';
    s.textContent = `
    #rt-toasts { position:fixed; bottom:22px; right:22px; z-index:9999; display:flex; flex-direction:column; gap:10px; }
    .rt-toast { display:flex; align-items:center; gap:10px; padding:12px 18px; border-radius:12px;
                font-family:'Plus Jakarta Sans',sans-serif; font-size:.83rem; font-weight:700;
                background:var(--card); border:1px solid var(--border); box-shadow:0 8px 32px rgba(0,0,0,.4);
                animation:rtSlideIn .3s cubic-bezier(.16,1,.3,1) both; min-width:220px; max-width:340px; }
    .rt-toast.success { border-color:rgba(16,185,129,.4); color:var(--g); }
    .rt-toast.error   { border-color:rgba(239,68,68,.4);  color:var(--r); }
    .rt-toast.info    { border-color:rgba(59,130,246,.4); color:var(--b); }
    .rt-toast.warning { border-color:rgba(245,158,11,.4); color:var(--y); }
    .rt-toast-icon { font-size:1rem; }
    .rt-toast-exit { animation:rtSlideOut .3s ease forwards; }
    @keyframes rtSlideIn  { from{opacity:0;transform:translateX(30px)} to{opacity:1;transform:none} }
    @keyframes rtSlideOut { to{opacity:0;transform:translateX(30px)} }
    /* Live indicator pulse */
    .rt-live-dot { display:inline-block; width:7px; height:7px; border-radius:50%; background:var(--g);
                   box-shadow:0 0 0 0 rgba(16,185,129,.5); animation:rtPulse 2s infinite; margin-right:5px; }
    @keyframes rtPulse { 0%{box-shadow:0 0 0 0 rgba(16,185,129,.5)} 70%{box-shadow:0 0 0 8px rgba(16,185,129,0)} 100%{box-shadow:0 0 0 0 rgba(16,185,129,0)} }
    /* Result counter badge */
    .rt-count { display:inline-flex; align-items:center; gap:5px; font-size:.72rem; font-weight:800;
                color:var(--text2); background:var(--bg3); border:1px solid var(--border);
                border-radius:20px; padding:3px 10px; transition:.2s; }
  `;
    document.head.appendChild(s);

    // Create toast container
    const c = document.createElement('div');
    c.id = 'rt-toasts';
    document.body.appendChild(c);
})();

function showToast(msg, type = 'info', duration = 3000) {
    const icons = { success: 'fa-check-circle', error: 'fa-circle-exclamation', info: 'fa-circle-info', warning: 'fa-triangle-exclamation' };
    const t = document.createElement('div');
    t.className = `rt-toast ${type}`;
    t.innerHTML = `<i class="fas ${icons[type] || icons.info} rt-toast-icon"></i><span>${msg}</span>`;
    document.getElementById('rt-toasts').appendChild(t);
    setTimeout(() => {
        t.classList.add('rt-toast-exit');
        setTimeout(() => t.remove(), 350);
    }, duration);
}

/* ═══════════════════════════════════════════════
   LIVE SEARCH ENGINE
   Usage:
     liveSearch({
       input: '#searchInput',          // input element selector
       filters: ['#statusSel'],        // optional extra filter selectors
       endpoint: 'api/animals.php',    // API endpoint
       tableBody: '#animalsBody',      // tbody to replace
       countEl: '#resultCount',        // optional count badge selector
       extraParams: { limit: 25 },     // optional fixed params
     });
═══════════════════════════════════════════════ */
function liveSearch({ input, filters = [], endpoint, tableBody, countEl, extraParams = {} }) {
    const inp = typeof input === 'string' ? document.querySelector(input) : input;
    const tbody = typeof tableBody === 'string' ? document.querySelector(tableBody) : tableBody;
    const countEl_ = countEl ? (typeof countEl === 'string' ? document.querySelector(countEl) : countEl) : null;
    if (!inp || !tbody) return;

    let controller = null;

    async function doFetch() {
        if (controller) controller.abort();
        controller = new AbortController();

        const params = new URLSearchParams({ ...extraParams, search: inp.value.trim() });
        filters.forEach(sel => {
            const el = typeof sel === 'object' ? sel : document.querySelector(sel);
            if (el) params.set(el.name || el.id, el.value);
        });

        setTableLoading(tbody);
        try {
            const res = await fetch(`${endpoint}?${params.toString()}`, { signal: controller.signal });
            if (!res.ok) throw new Error('Server error');
            const data = await res.json();
            if (data.error) throw new Error(data.error);

            tbody.innerHTML = data.html;
            clearTableLoading(tbody);

            if (countEl_ && data.total !== undefined) {
                countEl_.textContent = data.total + ' result' + (data.total !== 1 ? 's' : '');
            }

            // Re-translate if language is active
            if (typeof applyLanguage === 'function') applyLanguage();
        } catch (err) {
            if (err.name !== 'AbortError') {
                clearTableLoading(tbody);
                showToast('Failed to load data. Check connection.', 'error');
            }
        }
    }

    const debouncedFetch = debounce(doFetch, 280);
    inp.addEventListener('input', debouncedFetch);
    filters.forEach(sel => {
        const el = typeof sel === 'object' ? sel : document.querySelector(sel);
        if (el) el.addEventListener('change', doFetch);
    });

    // Initial fetch
    doFetch();

    // Return doFetch so callers can trigger manually
    return doFetch;
}

/* ═══════════════════════════════════════════════
   AUTO-REFRESH POLLING
   Usage: startAutoRefresh(fetchFn, intervalMs)
   Returns a handle (clearInterval id)
═══════════════════════════════════════════════ */
function startAutoRefresh(fetchFn, intervalMs = 15000) {
    return setInterval(fetchFn, intervalMs);
}

/* ═══════════════════════════════════════════════
   AJAX FORM SUBMIT (no page reload)
   Usage:
     ajaxForm('#medForm', 'api/health.php', onSuccess)
═══════════════════════════════════════════════ */
function ajaxForm(formSel, endpoint, onSuccess) {
    const form = typeof formSel === 'string' ? document.querySelector(formSel) : formSel;
    if (!form) return;
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = form.querySelector('[type=submit]');
        if (btn) { btn.disabled = true; btn.style.opacity = '.6'; }

        const body = new FormData(form);
        try {
            const res = await fetch(endpoint, { method: 'POST', body });
            const data = await res.json();
            if (data.error) throw new Error(data.error);
            if (btn) { btn.disabled = false; btn.style.opacity = ''; }
            form.reset();
            showToast('Record saved!', 'success');
            if (onSuccess) onSuccess(data);
        } catch (err) {
            if (btn) { btn.disabled = false; btn.style.opacity = ''; }
            showToast(err.message || 'Failed to save. Try again.', 'error');
        }
    });
}

/* ═══════════════════════════════════════════════
   INJECT LIVE INDICATOR into a card header title
═══════════════════════════════════════════════ */
function addLiveDot(el) {
    if (!el) return;
    const dot = document.createElement('span');
    dot.className = 'rt-live-dot';
    el.prepend(dot);
}
