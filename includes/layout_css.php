<?php
/**
 * includes/layout_css.php
 * Shared CSS for all pages (theme vars, sidebar, topbar, common elements).
 * Include inside <style> tags or as a standalone file within <head>.
 */
?>
<style>
[data-theme="dark"]{
  --bg:#0a0a0a;--bg2:#111;--bg3:#1a1a1a;--bg4:#222;
  --card:#141414;--border:#242424;--border2:#2e2e2e;
  --text:#f0f0f0;--text2:#777;--text3:#383838;
  --shadow:0 16px 56px rgba(0,0,0,.8);--shadow2:0 4px 20px rgba(0,0,0,.5);
  --glass:rgba(255,255,255,.025);
}
[data-theme="light"]{
  --bg:#f5f0eb;--bg2:#fff;--bg3:#f0ebe4;--bg4:#e8e2db;
  --card:#fff;--border:#e5dfd8;--border2:#d5cfc8;
  --text:#1a1208;--text2:#7a6e65;--text3:#c0b8af;
  --shadow:0 12px 40px rgba(0,0,0,.08);--shadow2:0 4px 16px rgba(0,0,0,.06);
  --glass:rgba(0,0,0,.015);
}
:root{
  --p:#f97316;--pd:#ea6c10;--pl:rgba(249,115,22,.12);
  --g:#10b981;--gl:rgba(16,185,129,.12);
  --b:#3b82f6;--bl:rgba(59,130,246,.12);
  --y:#f59e0b;--yl:rgba(245,158,11,.12);
  --pu:#8b5cf6;--pul:rgba(139,92,246,.12);
  --r:#ef4444;--rl:rgba(239,68,68,.12);
}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
html{scroll-behavior:smooth;}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;overflow:hidden;}
::-webkit-scrollbar{width:4px;}
::-webkit-scrollbar-thumb{background:var(--border2);border-radius:4px;}
a{text-decoration:none;color:inherit;}

/* RTL Support */
[dir="rtl"] .sbar{border-right:none;border-left:1px solid var(--border);}
[dir="rtl"] .ni.on::before{left:auto;right:0;}
[dir="rtl"] .sbar-toggle{right:auto;left:-13px;}
[dir="rtl"] .tb-right{margin-left:0;margin-right:auto;}
[dir="rtl"] th{text-align:right;}

/* ── SIDEBAR ─────────────────────────────── */
.sbar{width:230px;min-width:230px;height:100vh;background:var(--bg2);border-right:1px solid var(--border);display:flex;flex-direction:column;transition:width .35s cubic-bezier(.4,0,.2,1),min-width .35s cubic-bezier(.4,0,.2,1);position:relative;z-index:100;overflow-x:hidden;flex-shrink:0;}
.sbar.mini{width:62px;min-width:62px;}
.sbar-logo{display:flex;align-items:center;gap:12px;padding:20px 16px 16px;border-bottom:1px solid var(--border);}
.sl-icon{width:38px;height:38px;min-width:38px;background:linear-gradient(135deg,var(--p),#fb923c);border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:1.15rem;box-shadow:0 4px 16px rgba(249,115,22,.4);flex-shrink:0;}
.sl-txt{overflow:hidden;white-space:nowrap;transition:opacity .25s,width .35s;}
.sbar.mini .sl-txt{opacity:0;width:0;pointer-events:none;}
.sl-txt h1{font-family:'Bebas Neue';font-size:1.18rem;letter-spacing:2.5px;}
.sl-txt span{font-size:.6rem;font-weight:700;color:var(--text2);letter-spacing:1.8px;text-transform:uppercase;}
.sbar-toggle{position:absolute;right:-13px;top:24px;width:26px;height:26px;background:var(--bg3);border:1px solid var(--border2);border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.7rem;color:var(--text2);z-index:10;transition:background .2s,transform .2s;}
.sbar-toggle:hover{background:var(--border2);color:var(--text);}
.sbar.mini .sbar-toggle i{transform:rotate(180deg);}
.sbar-user{padding:14px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;overflow:hidden;transition:.15s;}
.sbar-user:hover{background:var(--bg3);}
.su-av{width:34px;height:34px;min-width:34px;border-radius:10px;background:linear-gradient(135deg,var(--p),var(--y));color:#000;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:.85rem;flex-shrink:0;}
.su-info{overflow:hidden;white-space:nowrap;transition:opacity .25s,width .35s;}
.sbar.mini .su-info{opacity:0;width:0;pointer-events:none;}
.su-name{font-weight:800;font-size:.83rem;overflow:hidden;text-overflow:ellipsis;}
.su-role{font-size:.68rem;font-weight:600;color:var(--p);margin-top:1px;}
.sbar-nav{flex:1;overflow-y:auto;overflow-x:hidden;padding:10px 8px;min-height:0;}
.s-sec{font-size:.58rem;font-weight:800;color:var(--text3);letter-spacing:2px;text-transform:uppercase;padding:12px 10px 5px;white-space:nowrap;overflow:hidden;transition:opacity .2s;}
.sbar.mini .s-sec{opacity:0;}
.ni{display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:9px;cursor:pointer;color:var(--text2);font-size:.82rem;font-weight:600;transition:.15s;position:relative;white-space:nowrap;margin-bottom:1px;}
.ni:hover{background:var(--bg3);color:var(--text);}
.ni.on{background:var(--pl);color:var(--p);font-weight:700;}
.ni.on::before{content:'';position:absolute;left:0;top:22%;bottom:22%;width:3px;border-radius:2px;background:var(--p);}
.ni i{font-size:.92rem;min-width:18px;text-align:center;flex-shrink:0;}
.nl{transition:opacity .25s;overflow:hidden;flex:1;}
.sbar.mini .nl{opacity:0;width:0;pointer-events:none;}
.npill{background:var(--p);color:#fff;font-size:.6rem;font-weight:800;padding:2px 7px;border-radius:20px;flex-shrink:0;transition:opacity .25s;}
.sbar.mini .npill{opacity:0;}
.sbar-bottom{padding:12px 8px;border-top:1px solid var(--border);}

/* ── MAIN ─────────────────────────────────── */
.main{flex:1;display:flex;flex-direction:column;overflow:hidden;min-width:0;}

/* ── TOPBAR ─────────────────────────────── */
.topbar{height:62px;min-height:62px;background:var(--bg2);border-bottom:1px solid var(--border);padding:0 24px;display:flex;align-items:center;gap:14px;position:relative;}
.tb-clock{font-size:.78rem;font-weight:800;color:var(--text2);padding:6px 12px;background:var(--bg3);border:1px solid var(--border);border-radius:8px;font-variant-numeric:tabular-nums;letter-spacing:.5px;}
.topbar-title{font-family:'Bebas Neue';font-size:1.45rem;letter-spacing:1.5px;}
.tb-right{margin-left:auto;display:flex;align-items:center;gap:8px;}
.tb-btn{width:37px;height:37px;background:var(--bg3);border:1px solid var(--border);border-radius:9px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--text2);transition:.15s;font-size:.88rem;position:relative;}
.tb-btn:hover{color:var(--text);border-color:var(--border2);}
.tb-dot{position:absolute;top:7px;right:7px;width:7px;height:7px;border-radius:50%;background:var(--r);border:2px solid var(--bg2);}
.theme-sw{display:flex;align-items:center;background:var(--bg3);border:1px solid var(--border);border-radius:20px;padding:3px;gap:2px;}
.t-opt{width:30px;height:27px;border-radius:15px;display:flex;align-items:center;justify-content:center;font-size:.76rem;transition:.2s;color:var(--text2);cursor:pointer;}
.t-opt.on{background:var(--card);color:var(--text);box-shadow:var(--shadow2);}
.lang-sw{display:flex;align-items:center;background:var(--bg3);border:1px solid var(--border);border-radius:20px;padding:3px;gap:2px;}
.lang-opt{width:36px;height:27px;border-radius:15px;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:800;transition:.2s;color:var(--text2);cursor:pointer;}
.lang-opt.on{background:var(--card);color:var(--text);box-shadow:var(--shadow2);}
.u-av{width:37px;height:37px;border-radius:50%;color:#000;background:linear-gradient(135deg,var(--p),var(--y));display:flex;align-items:center;justify-content:center;font-weight:900;font-size:.88rem;cursor:pointer;box-shadow:0 2px 12px rgba(249,115,22,.35);transition:.2s;}
.u-av:hover{transform:scale(1.07);box-shadow:0 4px 18px rgba(249,115,22,.5);}

/* ── PROFILE DROPDOWN ───────────────────── */
.profile-dropdown{
  position:fixed;top:72px;right:14px;width:220px;
  background:var(--card);border:1px solid var(--border2);
  border-radius:14px;box-shadow:var(--shadow);
  z-index:600;animation:fadeUp .2s ease;
}
.pd-arrow{position:absolute;top:-7px;right:16px;width:12px;height:12px;background:var(--card);border-top:1px solid var(--border2);border-left:1px solid var(--border2);transform:rotate(45deg);}
.pd-header{padding:14px 16px;display:flex;align-items:center;gap:10px;}
.pd-av{width:38px;height:38px;min-width:38px;border-radius:50%;background:linear-gradient(135deg,var(--p),var(--y));color:#000;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:.95rem;}
.pd-name{font-weight:800;font-size:.85rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.pd-role{font-size:.68rem;font-weight:600;color:var(--p);margin-top:1px;}
.pd-divider{height:1px;background:var(--border);}
.pd-item{display:flex;align-items:center;gap:10px;padding:11px 16px;font-size:.82rem;font-weight:600;color:var(--text2);transition:.15s;cursor:pointer;}
.pd-item:hover{background:var(--bg3);color:var(--text);}
.pd-item i{font-size:.88rem;min-width:16px;text-align:center;}
.pd-item-danger{color:var(--r)!important;}
.pd-item-danger:hover{background:var(--rl)!important;color:var(--r)!important;}

/* ── CONTENT ─────────────────────────────── */
.content{flex:1;overflow-y:auto;padding:24px;display:flex;flex-direction:column;gap:20px;}

/* ── CARD ───────────────────────────────── */
.card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:20px;animation:fadeUp .5s ease both;}
@keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
.ch{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px;}
.ct{font-family:'Bebas Neue';font-size:1.05rem;letter-spacing:1.2px;display:flex;align-items:center;gap:8px;}
.ct i{color:var(--p);}

/* ── TABLE ──────────────────────────────── */
.tw{overflow-x:auto;}
table{width:100%;border-collapse:collapse;}
th{text-align:left;padding:9px 14px;font-size:.66rem;font-weight:800;letter-spacing:1.2px;color:var(--text2);text-transform:uppercase;border-bottom:1px solid var(--border);}
td{padding:12px 14px;border-bottom:1px solid var(--border);font-size:.83rem;font-weight:500;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:var(--glass);}

/* ── BADGE ──────────────────────────────── */
.bdg{padding:3px 9px;border-radius:20px;font-size:.65rem;font-weight:800;letter-spacing:.3px;white-space:nowrap;}
.bg{background:var(--gl);color:var(--g);}
.br{background:var(--rl);color:var(--r);}
.by{background:var(--yl);color:var(--y);}
.bb{background:var(--bl);color:var(--b);}
.bpu{background:var(--pul);color:var(--pu);}
.bgray{background:var(--bg3);color:var(--text2);}

/* ── BUTTONS ─────────────────────────────── */
.btn{padding:7px 14px;border-radius:8px;font-family:'Plus Jakarta Sans';font-weight:700;font-size:.78rem;cursor:pointer;border:none;transition:.15s;display:inline-flex;align-items:center;gap:6px;white-space:nowrap;}
.btn-p{background:linear-gradient(135deg,var(--p),var(--pd));color:#fff;box-shadow:0 4px 16px rgba(249,115,22,.25);} .btn-p:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(249,115,22,.4);}
.btn-y{background:var(--y);color:#000;} .btn-y:hover{background:#e09000;}
.btn-r{background:var(--r);color:#fff;} .btn-r:hover{background:#dc2626;}
.btn-b{background:var(--b);color:#fff;} .btn-b:hover{background:#2563eb;}
.btn-g{background:var(--g);color:#fff;} .btn-g:hover{background:#059669;}
.btn-gh{background:var(--bg3);color:var(--text);border:1px solid var(--border);} .btn-gh:hover{background:var(--border);}
.btn-sm{padding:5px 10px;font-size:.72rem;}

/* ── FORM ───────────────────────────────── */
.fg{margin-bottom:18px;}
.fg label{display:block;font-size:.72rem;font-weight:800;letter-spacing:.8px;text-transform:uppercase;color:var(--text2);margin-bottom:7px;}
.fi{width:100%;background:var(--bg3);border:1.5px solid var(--border);border-radius:9px;padding:10px 13px;color:var(--text);font-size:.85rem;font-weight:500;transition:.2s;outline:none;font-family:'Plus Jakarta Sans',sans-serif;}
.fi:focus{border-color:var(--p);box-shadow:0 0 0 3px var(--pl);}
select.fi{cursor:pointer;}

/* ── EMPTY ───────────────────────────────── */
.empty{text-align:center;padding:40px;color:var(--text2);}
.empty i{font-size:2.5rem;margin-bottom:10px;opacity:.2;display:block;}
.empty p{font-weight:700;font-size:.82rem;}

/* ── ALERT ───────────────────────────────── */
.alert{padding:12px 16px;border-radius:10px;margin-bottom:18px;display:flex;align-items:center;gap:10px;font-size:.83rem;font-weight:600;}
.alert-success{background:var(--gl);color:var(--g);border:1px solid rgba(16,185,129,.3);}
.alert-error{background:var(--rl);color:var(--r);border:1px solid rgba(239,68,68,.3);}
.alert-info{background:var(--bl);color:var(--b);border:1px solid rgba(59,130,246,.3);}

@media(max-width:600px){.sbar{display:none;}}

/* ── NOTIFICATION PANEL ──────────────────────────────────── */
.np{
  position:fixed;top:70px;right:14px;width:340px;
  background:var(--card);border:1px solid var(--border2);
  border-radius:16px;box-shadow:var(--shadow);
  z-index:700;display:none;
  animation:fadeUp .22s cubic-bezier(.16,1,.3,1);
  overflow:hidden;
}
.np.show{display:block;}
.np-h{
  padding:13px 16px;border-bottom:1px solid var(--border);
  display:flex;justify-content:space-between;align-items:center;
  position:sticky;top:0;background:var(--card);z-index:1;
}
.np-h h4{font-weight:800;font-size:.88rem;color:var(--text);}
.np-badge{
  background:var(--r);color:#fff;font-size:.62rem;font-weight:900;
  min-width:18px;height:18px;border-radius:9px;
  display:inline-flex;align-items:center;justify-content:center;padding:0 5px;
}
.np-mark-btn{
  font-size:.7rem;font-weight:700;color:var(--p);background:none;
  border:none;cursor:pointer;padding:4px 8px;border-radius:6px;
  font-family:'Plus Jakarta Sans',sans-serif;transition:.15s;
}
.np-mark-btn:hover{background:var(--pl);}
.np-list{max-height:360px;overflow-y:auto;}
.np-item{
  display:flex;align-items:flex-start;gap:11px;padding:11px 16px;
  border-bottom:1px solid var(--border);cursor:pointer;
  transition:.15s;position:relative;
}
.np-item:last-child{border-bottom:none;}
.np-item:hover{background:var(--bg3);}
.np-item.is-new{background:rgba(249,115,22,.04);}
.np-item.is-new::before{
  content:'';position:absolute;left:0;top:0;bottom:0;width:3px;
  background:var(--p);border-radius:0 2px 2px 0;
}
.np-ico{
  width:34px;height:34px;min-width:34px;border-radius:10px;
  display:flex;align-items:center;justify-content:center;font-size:.88rem;
  flex-shrink:0;margin-top:1px;
}
.np-txt{flex:1;min-width:0;}
.np-title{font-weight:700;font-size:.8rem;color:var(--text);
  overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.np-sub{font-size:.7rem;color:var(--text2);margin-top:3px;font-weight:600;}
.np-new-dot{
  width:7px;height:7px;border-radius:50%;background:var(--p);
  flex-shrink:0;margin-top:6px;
}
.np-loading{padding:28px;text-align:center;color:var(--text2);font-size:.82rem;font-weight:600;}
.np-empty{padding:36px;text-align:center;color:var(--text2);}
.np-empty i{font-size:2rem;opacity:.2;display:block;margin-bottom:8px;}
.np-empty p{font-size:.8rem;font-weight:700;}
.np-footer{
  padding:10px 16px;border-top:1px solid var(--border);
  display:flex;justify-content:center;
  background:var(--card);
}
/* Bell badge (on tb-btn) */
.tb-badge{
  position:absolute;top:5px;right:5px;
  min-width:16px;height:16px;border-radius:8px;
  background:var(--r);color:#fff;font-size:.55rem;font-weight:900;
  display:flex;align-items:center;justify-content:center;padding:0 4px;
  border:2px solid var(--bg2);
  animation:badgePop .3s cubic-bezier(.68,-0.55,.27,1.55);
}
@keyframes badgePop{from{transform:scale(0)}to{transform:scale(1)}}
</style>
