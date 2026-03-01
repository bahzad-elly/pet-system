<?php
// dashboard.php
session_start();
require_once 'db.php'; // Ensure your database connection file is included

// Security Check: Redirect to login if not authenticated
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

// User details for the UI
$user_fullname = htmlspecialchars($_SESSION['fullname']);
$user_role = htmlspecialchars(ucfirst($_SESSION['role']));
$user_initial = strtoupper(substr($_SESSION['fullname'], 0, 1));

// 1. FETCH DASHBOARD STATS
$stats = [
    'total'     => $pdo->query("SELECT COUNT(*) FROM animals")->fetchColumn() ?: 0,
    'available' => $pdo->query("SELECT COUNT(*) FROM animals WHERE status='Available'")->fetchColumn() ?: 0,
    'adopted'   => $pdo->query("SELECT COUNT(*) FROM animals WHERE status='Adopted'")->fetchColumn() ?: 0,
    'pending'   => $pdo->query("SELECT COUNT(*) FROM animals WHERE status='Pending'")->fetchColumn() ?: 0,
    'medical'   => $pdo->query("SELECT COUNT(*) FROM animals WHERE status='Medical Care'")->fetchColumn() ?: 0,
];

// 2. FETCH SPECIES BREAKDOWN
$species_stmt = $pdo->query("SELECT species, COUNT(*) as count FROM animals GROUP BY species");
$species_data = $species_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$dogs = isset($species_data['Dog']) ? $species_data['Dog'] : 0;
$cats = isset($species_data['Cat']) ? $species_data['Cat'] : 0;
$other = array_sum($species_data) - $dogs - $cats;
$total_sp = max($stats['total'], 1); // Prevent division by zero for percentages

// 3. FETCH GENDER BREAKDOWN
$male_count = $pdo->query("SELECT COUNT(*) FROM animals WHERE gender='Male'")->fetchColumn() ?: 0;
$female_count = $pdo->query("SELECT COUNT(*) FROM animals WHERE gender='Female'")->fetchColumn() ?: 0;

// 4. FETCH RECENT ANIMALS (Last 5)
$recent_animals = $pdo->query("SELECT name, species, breed, age, status FROM animals ORDER BY animal_id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// Helper function for animal status badges
function getStatusBadge($status) {
    switch ($status) {
        case 'Available': return '<span class="bdg bg">Available</span>';
        case 'Adopted': return '<span class="bdg bb">Adopted</span>';
        case 'Pending': return '<span class="bdg by">Pending</span>';
        case 'Medical Care': return '<span class="bdg bpu">Medical Care</span>';
        default: return '<span class="bdg bgray">'.htmlspecialchars($status).'</span>';
    }
}

// 5. FETCH UPCOMING VACCINATIONS
$upcoming_vaccines = [];
try {
    $upcoming_vaccines = $pdo->query("SELECT an.name as animal_name, vt.vaccine_name, av.nextDate 
                                      FROM animal_vaccination av
                                      JOIN animals an ON av.animal_id = an.animal_id
                                      JOIN vaccination_types vt ON av.vtype_id = vt.vtype_id
                                      WHERE av.nextDate IS NOT NULL 
                                      ORDER BY av.nextDate ASC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { /* Ignore if table empty */ }

// 6. FETCH ACTIVITY FEED
$activity = [];
try {
    $activity = $pdo->query("SELECT u.fullname, a.actiontype, a.targettable, a.details, a.created_at 
                             FROM user_activity_log a 
                             JOIN users u ON a.uid = u.uid 
                             ORDER BY a.created_at DESC LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { /* Ignore if table empty */ }

// Helper function for activity feed icons
function getActIcon($type) {
    $type = strtolower($type);
    if (strpos($type, 'add') !== false || strpos($type, 'create') !== false) return ['icon' => 'fa-plus', 'color' => 'var(--g)', 'bg' => 'var(--gl)'];
    if (strpos($type, 'edit') !== false || strpos($type, 'update') !== false) return ['icon' => 'fa-pen', 'color' => 'var(--pu)', 'bg' => 'var(--pul)'];
    if (strpos($type, 'delete') !== false) return ['icon' => 'fa-trash', 'color' => 'var(--r)', 'bg' => 'var(--rl)'];
    return ['icon' => 'fa-bolt', 'color' => 'var(--y)', 'bg' => 'var(--yl)']; // Default
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🐾 PetAdopt — Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
[data-theme="dark"] {
  --bg:#0a0a0a;--bg2:#111;--bg3:#1a1a1a;--bg4:#222;
  --card:#141414;--border:#242424;--border2:#2e2e2e;
  --text:#f0f0f0;--text2:#777;--text3:#383838;
  --shadow:0 16px 56px rgba(0,0,0,.8);--shadow2:0 4px 20px rgba(0,0,0,.5);
  --glass:rgba(255,255,255,.025);
}
[data-theme="light"] {
  --bg:#f5f0eb;--bg2:#fff;--bg3:#f0ebe4;--bg4:#e8e2db;
  --card:#fff;--border:#e5dfd8;--border2:#d5cfc8;
  --text:#1a1208;--text2:#7a6e65;--text3:#c0b8af;
  --shadow:0 12px 40px rgba(0,0,0,.08);--shadow2:0 4px 16px rgba(0,0,0,.06);
  --glass:rgba(0,0,0,.015);
}
:root {
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
a {text-decoration:none; color:inherit;}

/* ── SIDEBAR ─────────────────────────────── */
.sbar{
  width:230px;min-width:230px;background:var(--bg2);
  border-right:1px solid var(--border);
  display:flex;flex-direction:column;
  transition:width .35s cubic-bezier(.4,0,.2,1),min-width .35s cubic-bezier(.4,0,.2,1);
  position:relative;z-index:100;overflow:hidden;
}
.sbar.mini{width:62px;min-width:62px;}

.sbar-logo{
  display:flex;align-items:center;gap:12px;
  padding:20px 16px 16px;border-bottom:1px solid var(--border);
}
.sl-icon{
  width:38px;height:38px;min-width:38px;
  background:linear-gradient(135deg,var(--p),#fb923c);
  border-radius:11px;display:flex;align-items:center;justify-content:center;
  font-size:1.15rem;box-shadow:0 4px 16px rgba(249,115,22,.4);
  flex-shrink:0;
}
.sl-txt{overflow:hidden;white-space:nowrap;transition:opacity .25s,width .35s;}
.sbar.mini .sl-txt{opacity:0;width:0;pointer-events:none;}
.sl-txt h1{font-family:'Bebas Neue';font-size:1.18rem;letter-spacing:2.5px;}
.sl-txt span{font-size:.6rem;font-weight:700;color:var(--text2);letter-spacing:1.8px;text-transform:uppercase;}

.sbar-toggle{
  position:absolute;right:-13px;top:24px;
  width:26px;height:26px;background:var(--bg3);
  border:1px solid var(--border2);border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  cursor:pointer;font-size:.7rem;color:var(--text2);z-index:10;
  transition:background .2s,transform .2s;
}
.sbar-toggle:hover{background:var(--border2);color:var(--text);}
.sbar.mini .sbar-toggle i{transform:rotate(180deg);}

.sbar-user{
  padding:14px 16px;border-bottom:1px solid var(--border);
  display:flex;align-items:center;gap:10px;overflow:hidden;
}
.su-av{
  width:34px;height:34px;min-width:34px;border-radius:10px;
  background:linear-gradient(135deg,var(--p),var(--y));color:#000;
  display:flex;align-items:center;justify-content:center;
  font-weight:900;font-size:.85rem;flex-shrink:0;
}
.su-info{overflow:hidden;white-space:nowrap;transition:opacity .25s,width .35s;}
.sbar.mini .su-info{opacity:0;width:0;pointer-events:none;}
.su-name{font-weight:800;font-size:.83rem;overflow:hidden;text-overflow:ellipsis;}
.su-role{font-size:.68rem;font-weight:600;color:var(--p);margin-top:1px;}

.sbar-nav{flex:1;overflow-y:auto;overflow-x:hidden;padding:10px 8px;}
.s-sec{
  font-size:.58rem;font-weight:800;color:var(--text3);
  letter-spacing:2px;text-transform:uppercase;
  padding:12px 10px 5px;white-space:nowrap;
  overflow:hidden;transition:opacity .2s;
}
.sbar.mini .s-sec{opacity:0;}

.ni{
  display:flex;align-items:center;gap:10px;
  padding:9px 10px;border-radius:9px;cursor:pointer;
  color:var(--text2);font-size:.82rem;font-weight:600;
  transition:.15s;position:relative;white-space:nowrap;
  margin-bottom:1px;
}
.ni:hover{background:var(--bg3);color:var(--text);}
.ni.on{background:var(--pl);color:var(--p);font-weight:700;}
.ni.on::before{
  content:'';position:absolute;left:0;top:22%;bottom:22%;
  width:3px;border-radius:2px;background:var(--p);
}
.ni i{font-size:.92rem;min-width:18px;text-align:center;flex-shrink:0;}
.nl{transition:opacity .25s;overflow:hidden;flex:1;}
.sbar.mini .nl{opacity:0;width:0;pointer-events:none;}
.npill{
  background:var(--p);color:#fff;font-size:.6rem;
  font-weight:800;padding:2px 7px;border-radius:20px;
  flex-shrink:0;transition:opacity .25s;
}
.sbar.mini .npill{opacity:0;}
.npill.warn{background:var(--r);}

.sbar-bottom{padding:12px 8px;border-top:1px solid var(--border);}

/* ── MAIN ─────────────────────────────────── */
.main{flex:1;display:flex;flex-direction:column;overflow:hidden;min-width:0;}

.topbar{
  height:62px;min-height:62px;background:var(--bg2);
  border-bottom:1px solid var(--border);
  padding:0 24px;display:flex;align-items:center;gap:14px;
}
.tb-clock{
  font-size:.78rem;font-weight:800;color:var(--text2);
  padding:6px 12px;background:var(--bg3);
  border:1px solid var(--border);border-radius:8px;
  font-variant-numeric:tabular-nums;letter-spacing:.5px;
}
.topbar-title{font-family:'Bebas Neue';font-size:1.45rem;letter-spacing:1.5px;}
.tb-right{margin-left:auto;display:flex;align-items:center;gap:8px;}

.tb-btn{
  width:37px;height:37px;background:var(--bg3);
  border:1px solid var(--border);border-radius:9px;
  display:flex;align-items:center;justify-content:center;
  cursor:pointer;color:var(--text2);transition:.15s;
  font-size:.88rem;position:relative;
}
.tb-btn:hover{color:var(--text);border-color:var(--border2);}
.tb-dot{
  position:absolute;top:7px;right:7px;width:7px;height:7px;
  border-radius:50%;background:var(--r);border:2px solid var(--bg2);
}

.theme-sw{
  display:flex;align-items:center;background:var(--bg3);
  border:1px solid var(--border);border-radius:20px;padding:3px;gap:2px;
}
.t-opt{
  width:30px;height:27px;border-radius:15px;
  display:flex;align-items:center;justify-content:center;
  font-size:.76rem;transition:.2s;color:var(--text2);cursor:pointer;
}
.t-opt.on{background:var(--card);color:var(--text);box-shadow:var(--shadow2);}

.lang-sw{display:flex;align-items:center;background:var(--bg3);border:1px solid var(--border);border-radius:20px;padding:3px;gap:2px;}
.lang-opt{width:36px;height:27px;border-radius:15px;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:800;transition:.2s;color:var(--text2);cursor:pointer;}
.lang-opt.on{background:var(--card);color:var(--text);box-shadow:var(--shadow2);}

.u-av{
  width:37px;height:37px;border-radius:50%;color:#000;
  background:linear-gradient(135deg,var(--p),var(--y));
  display:flex;align-items:center;justify-content:center;
  font-weight:900;font-size:.88rem;cursor:pointer;
  box-shadow:0 2px 12px rgba(249,115,22,.35);
}

/* ── CONTENT ─────────────────────────────── */
.content{flex:1;overflow-y:auto;padding:24px;display:flex;flex-direction:column;gap:20px;}

/* ── STAT CARDS ──────────────────────────── */
.stats-row{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;}
.sc{
  background:var(--card);border:1px solid var(--border);
  border-radius:14px;padding:18px 20px;
  display:flex;align-items:center;gap:14px;
  transition:.25s;cursor:default;position:relative;overflow:hidden;
  animation:fadeUp .5s ease both;
}
.sc:nth-child(1){animation-delay:.05s}
.sc:nth-child(2){animation-delay:.10s}
.sc:nth-child(3){animation-delay:.15s}
.sc:nth-child(4){animation-delay:.20s}
.sc:nth-child(5){animation-delay:.25s}
@keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
.sc:hover{transform:translateY(-3px);box-shadow:var(--shadow);}
.sc::after{
  content:'';position:absolute;right:-16px;top:-16px;
  width:68px;height:68px;border-radius:50%;opacity:.08;
}
.sc.or::after{background:var(--p);}
.sc.gr::after{background:var(--g);}
.sc.bl::after{background:var(--b);}
.sc.yw::after{background:var(--y);}
.sc.pu::after{background:var(--pu);}
.sic{
  width:46px;height:46px;min-width:46px;
  border-radius:12px;display:flex;align-items:center;
  justify-content:center;font-size:1.05rem;
}
.sic.or{background:var(--pl);color:var(--p);}
.sic.gr{background:var(--gl);color:var(--g);}
.sic.bl{background:var(--bl);color:var(--b);}
.sic.yw{background:var(--yl);color:var(--y);}
.sic.pu{background:var(--pul);color:var(--pu);}
.sv h3{font-size:1.7rem;font-weight:900;line-height:1;font-variant-numeric:tabular-nums;}
.sv p{color:var(--text2);font-size:.7rem;font-weight:700;margin-top:3px;letter-spacing:.3px;}

/* ── ROW 2 ──────────────────────────────── */
.row2{display:grid;grid-template-columns:2fr 1fr;gap:18px;}

/* ── CARD ───────────────────────────────── */
.card{
  background:var(--card);border:1px solid var(--border);
  border-radius:14px;padding:20px;
  animation:fadeUp .5s ease both;animation-delay:.3s;
}
.ch{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:8px;}
.ct{font-family:'Bebas Neue';font-size:1.05rem;letter-spacing:1.2px;display:flex;align-items:center;gap:8px;}
.ct i{color:var(--p);}

/* ── SEG CTRL ───────────────────────────── */
.seg{display:flex;background:var(--bg3);border:1px solid var(--border);border-radius:9px;padding:3px;gap:2px;}
.seg-opt{padding:5px 16px;border-radius:7px;font-weight:700;font-size:.76rem;cursor:pointer;transition:.2s;color:var(--text2);}
.seg-opt.on{background:var(--card);color:var(--text);box-shadow:var(--shadow2);}

/* ── BAR CHART ──────────────────────────── */
.chart-wrap{display:flex;align-items:flex-end;gap:6px;height:160px;padding-top:10px;}
.cc{flex:1;display:flex;flex-direction:column;align-items:center;gap:5px;min-width:0;}
.cb{
  width:100%;border-radius:5px 5px 0 0;min-height:3px;
  background:linear-gradient(180deg,var(--p),var(--pd));
  transition:height 1.2s cubic-bezier(.16,1,.3,1);
  position:relative;cursor:pointer;
}
.cb:hover{filter:brightness(1.15);}
.cb::after{
  content:attr(data-val);
  position:absolute;top:-20px;left:50%;transform:translateX(-50%);
  font-size:.62rem;font-weight:800;color:var(--p);
  opacity:0;transition:.2s;white-space:nowrap;
}
.cb:hover::after{opacity:1;}
.cl{font-size:.6rem;font-weight:700;color:var(--text2);}

/* ── SPECIES BREAKDOWN ──────────────────── */
.sp-item{display:flex;align-items:center;gap:11px;margin-bottom:16px;}
.sp-emoji{font-size:1.4rem;}
.sp-info{flex:1;}
.sp-row{display:flex;justify-content:space-between;margin-bottom:5px;}
.sp-name{font-size:.8rem;font-weight:700;}
.sp-count{font-size:.8rem;font-weight:900;}
.pb{height:6px;border-radius:4px;background:var(--bg3);overflow:hidden;}
.pf{height:100%;border-radius:4px;transition:width 1.4s cubic-bezier(.16,1,.3,1);}

/* ── ROW 3 ──────────────────────────────── */
.row3{display:grid;grid-template-columns:1fr 1fr;gap:18px;}
.right-col{display:flex;flex-direction:column;gap:18px;}

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

/* ── BTN ─────────────────────────────────── */
.btn{padding:7px 14px;border-radius:8px;font-family:'Plus Jakarta Sans';font-weight:700;font-size:.78rem;cursor:pointer;border:none;transition:.15s;display:inline-flex;align-items:center;gap:6px;white-space:nowrap;}
.btn-gh{background:var(--bg3);color:var(--text);border:1px solid var(--border);}
.btn-gh:hover{background:var(--border);}
.btn-sm{padding:6px 12px;}

/* ── ACTIVITY ───────────────────────────── */
.act-item{display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:9px;margin-bottom:6px;background:var(--bg3);transition:.15s;}
.act-item:hover{background:var(--bg4);}
.act-ico{width:32px;height:32px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:.88rem;flex-shrink:0;}
.act-text{flex:1;min-width:0;}
.act-title{font-weight:700;font-size:.8rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.act-sub{font-size:.68rem;color:var(--text2);margin-top:2px;}

/* ── VACC ITEM ──────────────────────────── */
.vacc-item{display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:9px;margin-bottom:6px;border:1px solid var(--border);}
.vacc-icon{width:32px;height:32px;border-radius:9px;background:var(--yl);color:var(--y);display:flex;align-items:center;justify-content:center;font-size:.88rem;flex-shrink:0;}
.vacc-info{flex:1;}
.vacc-name{font-weight:700;font-size:.8rem;}
.vacc-due{font-size:.68rem;color:var(--r);font-weight:700;margin-top:2px;}
.vacc-due.ok{color:var(--text2);}

/* ── EMPTY ───────────────────────────────── */
.empty{text-align:center;padding:30px;color:var(--text2);}
.empty i{font-size:2rem;margin-bottom:8px;opacity:.2;display:block;}
.empty p{font-weight:700;font-size:.82rem;}

/* ── NOTIF PANEL ─────────────────────────── */
.np{
  position:fixed;top:72px;right:16px;width:300px;
  background:var(--card);border:1px solid var(--border);
  border-radius:14px;box-shadow:var(--shadow);
  z-index:500;display:none;
  animation:fadeUp .25s ease;
}
.np.show{display:block;}
.np-h{padding:13px 16px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;}
.np-h h4{font-weight:800;font-size:.88rem;}
.np-i{padding:11px 16px;border-bottom:1px solid var(--border);display:flex;gap:10px;cursor:pointer;transition:.15s;}
.np-i:last-child{border-bottom:none;}
.np-i:hover{background:var(--bg3);}
.np-ic{width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0;}

@media(max-width:1100px){.stats-row{grid-template-columns:repeat(3,1fr);}}
@media(max-width:900px){.row2,.row3{grid-template-columns:1fr;}}
@media(max-width:600px){.sbar{display:none;}.stats-row{grid-template-columns:1fr 1fr;}}
</style>
</head>
<body>

<div class="np" id="notifPanel">
  <div class="np-h">
    <h4>Notifications</h4>
    <span style="font-size:.7rem;font-weight:700;color:var(--p);cursor:pointer" onclick="markRead()">Mark all read</span>
  </div>
  <div class="np-i">
    <div class="np-ic" style="background:var(--gl);color:var(--g)">🐕</div>
    <div><div style="font-weight:700;font-size:.82rem">System Active</div><div style="font-size:.72rem;color:var(--text2);margin-top:2px">Welcome to the Pet Adoption System</div><div style="font-size:.67rem;color:var(--text3);margin-top:3px">Just now</div></div>
  </div>
</div>

<aside class="sbar" id="sbar">
  <div class="sbar-logo">
    <div class="sl-icon">🐾</div>
    <div class="sl-txt"><h1>PetAdopt</h1><span>Shelter System</span></div>
  </div>
  <button class="sbar-toggle" onclick="toggleSbar()"><i class="fas fa-chevron-left" id="sbIcon"></i></button>
  
  <div class="sbar-user">
    <div class="su-av"><?= $user_initial ?></div>
    <div class="su-info">
      <div class="su-name"><?= $user_fullname ?></div>
      <div class="su-role"><?= $user_role ?></div>
    </div>
  </div>
  
  <nav class="sbar-nav">
    <div class="s-sec" data-i18n="overview">Overview</div>
    <a href="dashboard.php" class="ni on"><i class="fas fa-chart-pie"></i><span class="nl" data-i18n="dashboard">Dashboard</span></a>
    
    <div class="s-sec" data-i18n="animals_sec">Animals</div>
    <a href="view_animals.php" class="ni"><i class="fas fa-paw"></i><span class="nl" data-i18n="animals">Animals</span><span class="npill"><?= $stats['available'] ?></span></a>
    <a href="add_animal.php" class="ni"><i class="fas fa-plus"></i><span class="nl" data-i18n="add_animal">Add Animal</span></a>
    
    <div class="s-sec" data-i18n="people_sec">People</div>
    <a href="view_adopters.php" class="ni"><i class="fas fa-heart"></i><span class="nl" data-i18n="adopters">Adopters</span></a>
    <a href="register_adopter.php" class="ni"><i class="fas fa-user-plus"></i><span class="nl" data-i18n="add_adopter">Add Adopter</span></a>
    
    <div class="s-sec" data-i18n="system_sec">System</div>
    <a href="reports.php" class="ni"><i class="fas fa-chart-line"></i><span class="nl" data-i18n="reports">Reports</span></a>
    
    <?php if ($_SESSION['role'] === 'admin'): ?>
        <a href="view_users.php" class="ni"><i class="fas fa-users-gear" style="color:var(--y)"></i><span class="nl" data-i18n="admin_ctrl">Admin Controls</span></a>
    <?php endif; ?>
  </nav>
  
  <div class="sbar-bottom">
    <a href="logout.php" class="ni"><i class="fas fa-right-from-bracket" style="color:var(--r)"></i><span class="nl" style="color:var(--r)" data-i18n="sign_out">Sign Out</span></a>
  </div>
</aside>

<div class="main">
  <header class="topbar">
    <span class="tb-clock" id="clock">00:00</span>
    <span class="topbar-title" data-i18n="dashboard">Dashboard</span>
    <div class="tb-right">
      <div class="lang-sw">
        <div class="lang-opt on" id="langEn" onclick="setLanguage('en')">EN</div>
        <div class="lang-opt" id="langKu" onclick="setLanguage('ku')">KU</div>
      </div>
      <div class="theme-sw">
        <div class="t-opt on" id="tDark" onclick="setTheme('dark')"><i class="fas fa-moon"></i></div>
        <div class="t-opt" id="tLight" onclick="setTheme('light')"><i class="fas fa-sun"></i></div>
      </div>
      <button class="tb-btn" onclick="toggleNotif()" id="notifBtn">
        <i class="fas fa-bell"></i>
        <div class="tb-dot" id="notifDot"></div>
      </button>
      <button class="tb-btn" onclick="window.location.reload()"><i class="fas fa-rotate"></i></button>
      <div class="u-av"><?= $user_initial ?></div>
    </div>
  </header>

  <div class="content">

    <div class="stats-row">
      <div class="sc or">
        <div class="sic or"><i class="fas fa-paw"></i></div>
        <div class="sv"><h3><?= $stats['total'] ?></h3><p data-i18n="total_animals">Total Animals</p></div>
      </div>
      <div class="sc gr">
        <div class="sic gr"><i class="fas fa-check-circle"></i></div>
        <div class="sv"><h3><?= $stats['available'] ?></h3><p data-i18n="available">Available</p></div>
      </div>
      <div class="sc bl">
        <div class="sic bl"><i class="fas fa-house-heart"></i></div>
        <div class="sv"><h3><?= $stats['adopted'] ?></h3><p data-i18n="adopted">Adopted</p></div>
      </div>
      <div class="sc yw">
        <div class="sic yw"><i class="fas fa-clock"></i></div>
        <div class="sv"><h3><?= $stats['pending'] ?></h3><p data-i18n="pending">Pending</p></div>
      </div>
      <div class="sc pu">
        <div class="sic pu"><i class="fas fa-stethoscope"></i></div>
        <div class="sv"><h3><?= $stats['medical'] ?></h3><p data-i18n="medical_care">Medical Care</p></div>
      </div>
    </div>

    <div class="row2">
      <div class="card">
        <div class="ch">
          <div class="ct"><i class="fas fa-chart-bar"></i> Intake Chart</div>
          <div class="seg" id="chartSeg">
            <div class="seg-opt on" onclick="switchChart('weekly',this)">Weekly</div>
            <div class="seg-opt" onclick="switchChart('monthly',this)">Monthly</div>
          </div>
        </div>
        <div class="chart-wrap" id="mainChart"></div>
      </div>

      <div class="card">
        <div class="ch"><div class="ct"><i class="fas fa-fire"></i> By Species</div></div>
        <div style="margin-top:4px">
          <div class="sp-item">
            <span class="sp-emoji">🐕</span>
            <div class="sp-info">
              <div class="sp-row"><span class="sp-name">Dogs</span><span class="sp-count"><?= $dogs ?></span></div>
              <div class="pb"><div class="pf" style="width:<?= round(($dogs/$total_sp)*100) ?>%;background:var(--p)"></div></div>
            </div>
          </div>
          <div class="sp-item">
            <span class="sp-emoji">🐈</span>
            <div class="sp-info">
              <div class="sp-row"><span class="sp-name">Cats</span><span class="sp-count"><?= $cats ?></span></div>
              <div class="pb"><div class="pf" style="width:<?= round(($cats/$total_sp)*100) ?>%;background:var(--b)"></div></div>
            </div>
          </div>
          <div class="sp-item">
            <span class="sp-emoji">🐾</span>
            <div class="sp-info">
              <div class="sp-row"><span class="sp-name">Other</span><span class="sp-count"><?= $other ?></span></div>
              <div class="pb"><div class="pf" style="width:<?= round(($other/$total_sp)*100) ?>%;background:var(--pu)"></div></div>
            </div>
          </div>
        </div>

        <div style="border-top:1px solid var(--border);margin-top:10px;padding-top:14px">
          <div class="ct" style="margin-bottom:12px"><i class="fas fa-venus-mars" style="color:var(--pu)"></i> By Gender</div>
          <div style="display:flex;gap:10px">
            <div style="flex:1;background:var(--bg3);border-radius:10px;padding:12px;text-align:center">
              <div style="font-size:1.4rem;font-weight:900;color:var(--b)"><?= $male_count ?></div>
              <div style="font-size:.68rem;font-weight:700;color:var(--text2);margin-top:2px">Male</div>
            </div>
            <div style="flex:1;background:var(--bg3);border-radius:10px;padding:12px;text-align:center">
              <div style="font-size:1.4rem;font-weight:900;color:var(--p)"><?= $female_count ?></div>
              <div style="font-size:.68rem;font-weight:700;color:var(--text2);margin-top:2px">Female</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row3">
      
      <div class="card" style="animation-delay:.4s">
        <div class="ch">
          <div class="ct"><i class="fas fa-paw"></i> Recent Animals</div>
          <a href="view_animals.php" class="btn btn-gh btn-sm">All →</a>
        </div>
        <div class="tw">
          <table>
            <thead>
              <tr><th>Name</th><th>Species</th><th>Breed</th><th>Age (yrs)</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php if (count($recent_animals) > 0): ?>
                    <?php foreach ($recent_animals as $a): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($a['name']) ?></strong></td>
                            <td><?= htmlspecialchars($a['species']) ?></td>
                            <td style="color:var(--text2)"><?= htmlspecialchars($a['breed'] ?: '-') ?></td>
                            <td><?= htmlspecialchars($a['age'] ?: '?') ?></td>
                            <td><?= getStatusBadge($a['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center; padding: 20px; color:var(--text2)">No animals registered yet.</td></tr>
                <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="right-col">
        
        <div class="card" style="animation-delay:.45s">
          <div class="ch"><div class="ct"><i class="fas fa-syringe" style="color:var(--y)"></i> Upcoming Vaccines</div></div>
          
          <?php if (count($upcoming_vaccines) > 0): ?>
              <?php foreach ($upcoming_vaccines as $v): 
                  $is_overdue = (strtotime($v['nextDate']) < time());
                  $badge = $is_overdue ? '<span class="bdg br">Overdue</span>' : '<span class="bdg by">Soon</span>';
                  $date_class = $is_overdue ? '' : 'ok';
              ?>
                  <div class="vacc-item">
                    <div class="vacc-icon"><i class="fas fa-syringe"></i></div>
                    <div class="vacc-info">
                      <div class="vacc-name"><?= htmlspecialchars($v['animal_name'] . ' — ' . $v['vaccine_name']) ?></div>
                      <div class="vacc-due <?= $date_class ?>"><?= $is_overdue ? 'Was due ' : 'Due ' ?><?= htmlspecialchars($v['nextDate']) ?></div>
                    </div>
                    <?= $badge ?>
                  </div>
              <?php endforeach; ?>
          <?php else: ?>
              <div class="empty" style="padding:15px"><i class="fas fa-check-circle" style="color:var(--g)"></i><p>All vaccinations are up to date!</p></div>
          <?php endif; ?>
        </div>

        <div class="card" style="animation-delay:.5s">
          <div class="ch"><div class="ct"><i class="fas fa-bolt" style="color:var(--y)"></i> Activity Feed</div></div>
          
          <?php if (count($activity) > 0): ?>
              <?php foreach ($activity as $act): 
                  $icons = getActIcon($act['actiontype']);
              ?>
                  <div class="act-item">
                    <div class="act-ico" style="background:<?= $icons['bg'] ?>;color:<?= $icons['color'] ?>"><i class="fas <?= $icons['icon'] ?>"></i></div>
                    <div class="act-text">
                      <div class="act-title"><?= htmlspecialchars($act['details'] ?: $act['actiontype'] . ' on ' . $act['targettable']) ?></div>
                      <div class="act-sub"><?= htmlspecialchars($act['fullname']) ?> · <?= date('M d, H:i', strtotime($act['created_at'])) ?></div>
                    </div>
                  </div>
              <?php endforeach; ?>
          <?php else: ?>
               <div class="empty" style="padding:15px"><p>No recent activity.</p></div>
          <?php endif; ?>
        </div>
        
      </div>
    </div>

  </div></div><script>
// Translations
const tr={
  en:{overview:"Overview",animals_sec:"Animals",people_sec:"People",system_sec:"System",dashboard:"Dashboard",animals:"Animals",add_animal:"Add Animal",adopters:"Adopters",add_adopter:"Add Adopter",reports:"Reports",admin_ctrl:"Admin Controls",sign_out:"Sign Out",total_animals:"Total Animals",available:"Available",adopted:"Adopted",pending:"Pending",medical_care:"Medical Care"},
  ku:{overview:"پوختە",animals_sec:"ئاژەڵەکان",people_sec:"کەسەکان",system_sec:"سیستەم",dashboard:"داشبۆرد",animals:"ئاژەڵەکان",add_animal:"زیادکردنی ئاژەڵ",adopters:"خاوەن نوێکان",add_adopter:"زیادکردنی وەرگر",reports:"ڕاپۆرتەکان",admin_ctrl:"بەڕێوەبردن",sign_out:"دەرچوون",total_animals:"کۆی ئاژەڵەکان",available:"بەردەستە",adopted:"قبووڵکرا",pending:"چاوەڕوان",medical_care:"چارەسەری پزیشکی"}
};
let lang=localStorage.getItem('lang')||'en';
let theme=localStorage.getItem('theme')||'dark';
function T(k){return(tr[lang]||{})[k]||tr.en[k]||k;}
function setLanguage(l){
  lang=l;localStorage.setItem('lang',l);
  const isKu=l==='ku';
  document.documentElement.lang=l;
  document.documentElement.dir=isKu?'rtl':'ltr';
  document.getElementById('langEn').classList.toggle('on',l==='en');
  document.getElementById('langKu').classList.toggle('on',l==='ku');
  document.querySelectorAll('[data-i18n]').forEach(el=>{
    const k=el.getAttribute('data-i18n');
    if(T(k))el.textContent=T(k);
  });
}

// Clock
function tick(){
  const n=new Date();
  document.getElementById('clock').textContent=
    String(n.getHours()).padStart(2,'0')+':'+String(n.getMinutes()).padStart(2,'0');
}
tick(); setInterval(tick,10000);

// Sidebar toggle
function toggleSbar(){
  document.getElementById('sbar').classList.toggle('mini');
}

// Theme
function setTheme(t){
  theme=t;localStorage.setItem('theme',t);
  document.documentElement.setAttribute('data-theme',t);
  document.getElementById('tDark').classList.toggle('on',t==='dark');
  document.getElementById('tLight').classList.toggle('on',t==='light');
}

// Notifications
function toggleNotif(){
  document.getElementById('notifPanel').classList.toggle('show');
}
function markRead(){
  document.getElementById('notifDot').style.display='none';
  document.getElementById('notifPanel').classList.remove('show');
}
document.addEventListener('click',e=>{
  const np=document.getElementById('notifPanel');
  const nb=document.getElementById('notifBtn');
  if(np.classList.contains('show')&&!np.contains(e.target)&&!nb.contains(e.target))
    np.classList.remove('show');
});

// Chart data
const chartData = {
  weekly:{labels:['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],vals:[2,5,3,7,4,6,3]},
  monthly:{labels:['Sep','Oct','Nov','Dec','Jan','Feb'],vals:[8,12,7,15,10,9]}
};
let chartPeriod='weekly';

function renderChart(){
  const d=chartData[chartPeriod];
  const max=Math.max(...d.vals,1);
  document.getElementById('mainChart').innerHTML=d.labels.map((l,i)=>`
    <div class="cc">
      <div class="cb" data-val="${d.vals[i]}" style="height:0;background:linear-gradient(180deg,var(--p),var(--pd))"></div>
      <div class="cl">${l}</div>
    </div>`).join('');
  setTimeout(()=>{
    document.querySelectorAll('.cb').forEach((el,i)=>{
      el.style.height=((d.vals[i]/max)*145)+'px';
    });
  },50);
}
function switchChart(p,el){
  chartPeriod=p;
  document.querySelectorAll('#chartSeg .seg-opt').forEach(x=>x.classList.remove('on'));
  el.classList.add('on');
  renderChart();
}

// Apply saved prefs on load
setTheme(theme);
setLanguage(lang);

// Progress bars animate on load
window.addEventListener('load',()=>{
  renderChart();
  document.querySelectorAll('.pf').forEach(el=>{
    const w=el.style.width;
    el.style.width='0';
    setTimeout(()=>el.style.width=w, 100);
  });
});
</script>
</body>
</html>