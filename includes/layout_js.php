<?php
/**
 * includes/layout_js.php
 * Shared JavaScript for all pages.
 * Must be included AFTER the page-specific translations object is defined.
 * 
 * Each page defines its own translation keys and then includes this file.
 * This file MERGES with the base translations defined below.
 */
?>
<script>
// Base translations — shared across all pages
const trBase = {
  en: {
    shelter_sys:"Shelter System",overview_sec:"Overview",animals_sec:"Animals",
    people_sec:"People",system_sec:"System",
    dashboard:"Dashboard",animals:"Animals",add_animal:"Add Animal",
    adopters:"Adopters",add_adopter:"Add Adopter",reports:"Reports",
    admin_ctrl:"Admin Controls",sign_out:"Sign Out",my_profile:"My Profile"
  },
  ku: {
    shelter_sys:"سیستەمی پەناگا",overview_sec:"پوختە",animals_sec:"ئاژەڵەکان",
    people_sec:"کەسەکان",system_sec:"سیستەم",
    dashboard:"داشبۆرد",animals:"ئاژەڵەکان",add_animal:"زیادکردنی ئاژەڵ",
    adopters:"خاوەن نوێکان",add_adopter:"زیادکردنی وەرگر",reports:"ڕاپۆرتەکان",
    admin_ctrl:"بەڕێوەبردن",sign_out:"دەرچوون",my_profile:"پرۆفایلەکەم"
  }
};

// Merge with page-specific translations if they exist
if (typeof trPage !== 'undefined') {
  for (const lang in trPage) {
    if (!trBase[lang]) trBase[lang] = {};
    Object.assign(trBase[lang], trPage[lang]);
  }
}
const tr = trBase;

let lang = localStorage.getItem('lang') || 'en';
let theme = localStorage.getItem('theme') || 'dark';

function T(k) { return (tr[lang] || {})[k] || (tr.en || {})[k] || k; }

function setLanguage(l) {
  lang = l;
  localStorage.setItem('lang', l);
  const isKu = l === 'ku';
  document.documentElement.lang = l;
  document.documentElement.dir = isKu ? 'rtl' : 'ltr';
  const elEn = document.getElementById('langEn');
  const elKu = document.getElementById('langKu');
  if (elEn) elEn.classList.toggle('on', l === 'en');
  if (elKu) elKu.classList.toggle('on', l === 'ku');
  document.querySelectorAll('[data-i18n]').forEach(el => {
    const k = el.getAttribute('data-i18n');
    if (T(k)) el.textContent = T(k);
  });
}

function setTheme(t) {
  theme = t;
  localStorage.setItem('theme', t);
  document.documentElement.setAttribute('data-theme', t);
  const elDark = document.getElementById('tDark');
  const elLight = document.getElementById('tLight');
  if (elDark) elDark.classList.toggle('on', t === 'dark');
  if (elLight) elLight.classList.toggle('on', t === 'light');
}

function toggleSbar() {
  document.getElementById('sbar').classList.toggle('mini');
}

function tick() {
  const n = new Date();
  const el = document.getElementById('clock');
  if (el) el.textContent = String(n.getHours()).padStart(2, '0') + ':' + String(n.getMinutes()).padStart(2, '0');
}

// Profile dropdown
function toggleProfileDropdown() {
  const pd = document.getElementById('profileDropdown');
  if (pd) pd.style.display = pd.style.display === 'none' ? 'block' : 'none';
}

// Close dropdown on outside click
document.addEventListener('click', e => {
  const pd = document.getElementById('profileDropdown');
  const av = document.getElementById('profileAvatarBtn');
  if (pd && av && pd.style.display !== 'none' && !pd.contains(e.target) && !av.contains(e.target)) {
    pd.style.display = 'none';
  }
});

// Init
tick();
setInterval(tick, 10000);
setTheme(theme);
setLanguage(lang);
</script>

<script>
/* ═══════════════════════════════════════════════════════════
   NOTIFICATION SYSTEM — shared across all pages
   - Polls api/notifications.php every 30 seconds
   - Tracks "seen" notification IDs in localStorage
   - Shows unread badge on the bell button
═══════════════════════════════════════════════════════════ */

const NOTIF_SEEN_KEY = 'petadopt_seen_notifs';

function getSeenIds() {
  try { return JSON.parse(localStorage.getItem(NOTIF_SEEN_KEY) || '[]'); }
  catch { return []; }
}
function saveSeenIds(ids) {
  // Keep only last 200 to avoid bloat
  localStorage.setItem(NOTIF_SEEN_KEY, JSON.stringify(ids.slice(-200)));
}

function markAllRead() {
  const items = document.querySelectorAll('.np-item[data-id]');
  const ids = getSeenIds();
  items.forEach(el => {
    const id = parseInt(el.dataset.id);
    if (!ids.includes(id)) ids.push(id);
    el.classList.remove('is-new');
    const dot = el.querySelector('.np-new-dot');
    if (dot) dot.remove();
  });
  saveSeenIds(ids);
  // Hide badge
  const badge = document.getElementById('notifBadgePanel');
  const bellBadge = document.getElementById('bellBadge');
  const dot = document.getElementById('notifDot');
  if (badge)     { badge.style.display = 'none'; }
  if (bellBadge) { bellBadge.remove(); }
  if (dot)       { dot.style.display = 'none'; }
}

async function loadNotifications() {
  const seen = getSeenIds();
  try {
    const res  = await fetch('api/notifications.php?seen=' + encodeURIComponent(JSON.stringify(seen)));
    if (!res.ok) return;
    const data = await res.json();
    if (data.error) return;

    renderNotifications(data.items, data.unread);
  } catch (e) {}
}

function timeAgoLabel(ts) {
  const diff = Math.floor((Date.now() - new Date(ts).getTime()) / 1000);
  if (diff < 60)        return 'just now';
  if (diff < 3600)      return Math.floor(diff/60) + 'm ago';
  if (diff < 86400)     return Math.floor(diff/3600) + 'h ago';
  return new Date(ts).toLocaleDateString('en-US',{month:'short',day:'numeric'});
}

function renderNotifications(items, unread) {
  const list = document.getElementById('notifList');
  if (!list) return;

  // Update bell badge
  const bellBtn = document.getElementById('notifBtn');
  let bellBadge = document.getElementById('bellBadge');
  if (unread > 0) {
    if (!bellBadge && bellBtn) {
      bellBadge = document.createElement('span');
      bellBadge.id = 'bellBadge';
      bellBadge.className = 'tb-badge';
      bellBtn.appendChild(bellBadge);
    }
    if (bellBadge) bellBadge.textContent = unread > 9 ? '9+' : unread;

    const dot = document.getElementById('notifDot');
    if (dot) dot.style.display = 'block';

    const panelBadge = document.getElementById('notifBadgePanel');
    if (panelBadge) { panelBadge.style.display = 'inline-flex'; panelBadge.textContent = unread; }
  } else {
    if (bellBadge) bellBadge.remove();
    const dot = document.getElementById('notifDot');
    if (dot) dot.style.display = 'none';
    const panelBadge = document.getElementById('notifBadgePanel');
    if (panelBadge) panelBadge.style.display = 'none';
  }

  if (items.length === 0) {
    list.innerHTML = `<div class="np-empty">
      <i class="fas fa-check-circle" style="color:var(--g);opacity:.7"></i>
      <p>No activity in the last 12 hours.</p>
    </div>`;
    return;
  }

  list.innerHTML = items.map(item => `
    <div class="np-item${item.isNew ? ' is-new' : ''}" data-id="${item.id}">
      <div class="np-ico" style="background:${item.bg};color:${item.color}">
        <i class="fas ${item.icon}"></i>
      </div>
      <div class="np-txt">
        <div class="np-title" title="${item.title}">${item.title}</div>
        <div class="np-sub">${item.sub}</div>
      </div>
      ${item.isNew ? '<div class="np-new-dot"></div>' : ''}
    </div>
  `).join('');

  // Mark items as seen when panel is open OR auto-mark after 5s
  if (document.getElementById('notifPanel')?.classList.contains('show')) {
    autoMarkSeen(items);
  }
}

function autoMarkSeen(items) {
  setTimeout(() => {
    const ids = getSeenIds();
    items.forEach(i => { if (!ids.includes(i.id)) ids.push(i.id); });
    saveSeenIds(ids);
    // Clear visual indicators
    document.querySelectorAll('.np-item.is-new').forEach(el => {
      el.classList.remove('is-new');
      const dot = el.querySelector('.np-new-dot');
      if (dot) dot.remove();
    });
    const panel  = document.getElementById('notifBadgePanel');
    const bell   = document.getElementById('bellBadge');
    const dot    = document.getElementById('notifDot');
    if (panel)  { panel.style.display = 'none'; }
    if (bell)   { bell.remove(); }
    if (dot)    { dot.style.display = 'none'; }
  }, 5000); // auto-clear after 5 seconds of reading
}

function toggleNotif() {
  const np = document.getElementById('notifPanel');
  if (!np) return;
  const isOpen = np.classList.contains('show');
  np.classList.toggle('show');
  // Close profile dropdown if open
  const pd = document.getElementById('profileDropdown');
  if (pd) pd.style.display = 'none';
  // Load notifications on open
  if (!isOpen) {
    loadNotifications();
  }
}

// Close panel when clicking outside
document.addEventListener('click', e => {
  const np  = document.getElementById('notifPanel');
  const btn = document.getElementById('notifBtn');
  if (np && btn && np.classList.contains('show')
      && !np.contains(e.target) && !btn.contains(e.target)) {
    np.classList.remove('show');
  }
});

// Initial load + poll every 30 seconds
loadNotifications();
setInterval(loadNotifications, 30000);
</script>
