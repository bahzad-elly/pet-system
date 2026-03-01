<?php
// view_adopters.php
session_start();
require_once 'db.php';

// Security Check
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

$user = [
  'id'       => $_SESSION['uid'],
  'username' => $_SESSION['username'],
  'fullname' => $_SESSION['fullname'],
  'role'     => $_SESSION['role'],
  'initial'  => strtoupper(substr($_SESSION['fullname'], 0, 1)),
];

// Handle Search
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "SELECT adopterId, fname, lname, phone, address, preference, created_at FROM adopters";
$params = [];

if (!empty($search_query)) {
    $sql .= " WHERE fname LIKE :search OR lname LIKE :search OR phone LIKE :search";
    $params[':search'] = '%' . $search_query . '%';
}

$sql .= " ORDER BY created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $adopters = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🏡 Manage Adopters - PetAdopt</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ════ THEME TOKENS ════ */
[data-theme="dark"] {
  --bg:#0a0a0a;--bg2:#131313;--bg3:#1c1c1c;
  --card:#161616;--border:#282828;--border2:#333;
  --text:#f2f2f2;--text2:#888;--text3:#444;
  --shadow:0 12px 48px rgba(0,0,0,.7);--shadow2:0 4px 16px rgba(0,0,0,.5);
}
[data-theme="light"] {
  --bg:#f5f0eb;--bg2:#ffffff;--bg3:#f0ebe5;
  --card:#ffffff;--border:#e5dfd9;--border2:#d5cfc9;
  --text:#1a1208;--text2:#7a6e65;--text3:#b0a89f;
  --shadow:0 12px 48px rgba(0,0,0,.1);--shadow2:0 4px 16px rgba(0,0,0,.07);
}
:root {
  --p:#f97316;--pd:#ea6c10;--pl:rgba(249,115,22,.12);
  --y:#f59e0b;--yl:rgba(245,158,11,.12);
  --g:#10b981;--gl:rgba(16,185,129,.12);
  --b:#3b82f6;--bl:rgba(59,130,246,.12);
  --pu:#8b5cf6;--pul:rgba(139,92,246,.12);
  --r:#ef4444;--rl:rgba(239,68,68,.12);
  --rad:14px;--rad2:10px;--rad3:8px;
}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;overflow:hidden;transition:background .3s,color .3s;}
a{text-decoration:none;color:inherit;}

/* ── RTL support for Kurdish ── */
[dir="rtl"] .sbar{border-right:none;border-left:1px solid var(--border);}
[dir="rtl"] .ni.on::before{left:auto;right:0;}
[dir="rtl"] .sbar-toggle{right:auto;left:-14px;}
[dir="rtl"] .tb-right{margin-left:0;margin-right:auto;}
[dir="rtl"] th { text-align: right; }

/* ════ SIDEBAR & TOPBAR ════ */
.sbar{width:230px;min-width:230px;background:var(--bg2);border-right:1px solid var(--border);display:flex;flex-direction:column;position:relative;z-index:100;transition:.3s;}
.sbar.mini{width:60px;min-width:60px;}
.sbar-logo{display:flex;align-items:center;gap:11px;padding:18px 14px 14px;border-bottom:1px solid var(--border);}
.sl-icon{width:36px;height:36px;min-width:36px;background:linear-gradient(135deg,var(--p),#fb923c);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;box-shadow:0 4px 14px rgba(249,115,22,.35);}
.sl-txt h1{font-family:'Bebas Neue';font-size:1.15rem;letter-spacing:2px;white-space:nowrap;}
.sl-txt span{font-size:.62rem;font-weight:700;color:var(--text2);letter-spacing:1.5px;text-transform:uppercase;}
.sbar.mini .sl-txt{opacity:0;width:0;overflow:hidden;}
.sbar-toggle{position:absolute;right:-14px;top:22px;width:28px;height:28px;background:var(--bg3);border:1px solid var(--border);border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.75rem;color:var(--text2);z-index:2;}
.sbar.mini .sbar-toggle i{transform:rotate(180deg);}
.sbar-user{padding:12px 14px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;}
.su-av{width:32px;height:32px;min-width:32px;border-radius:9px;background:linear-gradient(135deg,var(--p),var(--y));display:flex;align-items:center;justify-content:center;font-weight:900;font-size:.8rem;color:#000;}
.su-info{overflow:hidden;transition:.3s;}
.sbar.mini .su-info{opacity:0;width:0;}
.su-name{font-weight:800;font-size:.82rem;white-space:nowrap;}
.su-role{font-size:.68rem;font-weight:600;color:var(--text2);}
.sbar-nav{flex:1;overflow-y:auto;padding:8px;}
.s-sec{font-size:.6rem;font-weight:800;color:var(--text3);letter-spacing:2px;text-transform:uppercase;padding:10px 8px 5px;}
.sbar.mini .s-sec{opacity:0;overflow:hidden;white-space:nowrap;}
.ni{display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:var(--rad3);cursor:pointer;color:var(--text2);font-size:.82rem;font-weight:600;transition:.15s;position:relative;white-space:nowrap;}
.ni:hover{background:var(--bg3);color:var(--text);}
.ni.on{background:var(--pl);color:var(--p);font-weight:700;}
.ni.on::before{content:'';position:absolute;left:0;top:20%;bottom:20%;width:3px;border-radius:2px;background:var(--p);}
.ni i{font-size:.95rem;min-width:18px;text-align:center;}
.nl{transition:.25s;overflow:hidden;flex:1;}
.sbar.mini .nl{opacity:0;width:0;}

.main{flex:1;display:flex;flex-direction:column;overflow:hidden;}
.topbar{height:60px;min-height:60px;background:var(--bg2);border-bottom:1px solid var(--border);padding:0 22px;display:flex;align-items:center;gap:12px;}
.topbar-title{font-family:'Bebas Neue';font-size:1.4rem;letter-spacing:1px;}
.tb-right{margin-left:auto;display:flex;align-items:center;gap:8px;}
.tb-btn{width:36px;height:36px;background:var(--bg3);border:1px solid var(--border);border-radius:var(--rad3);display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--text2);transition:.15s;}
.tb-btn:hover{color:var(--text);border-color:var(--border2);}
.theme-sw, .lang-sw{display:flex;align-items:center;background:var(--bg3);border:1px solid var(--border);border-radius:20px;padding:3px;cursor:pointer;gap:2px;}
.t-opt, .lang-opt{width:28px;height:26px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:.75rem;transition:.2s;color:var(--text2);}
.lang-opt { width:36px; font-weight: 800; font-size: .7rem; }
.t-opt.on, .lang-opt.on{background:var(--card);color:var(--text);box-shadow:var(--shadow2);}

/* ════ UI COMPONENTS ════ */
.content{flex:1;overflow-y:auto;padding:22px;}
.card{background:var(--card);border:1px solid var(--border);border-radius:var(--rad);padding:20px;}
.ch{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:15px;}
.ct{font-family:'Bebas Neue';font-size:1.3rem;letter-spacing:1px;display:flex;align-items:center;gap:8px;}
.ct i{color:var(--p);}

/* BUTTONS & INPUTS */
.btn{padding:9px 16px;border-radius:var(--rad3);font-family:'Plus Jakarta Sans';font-weight:700;font-size:.82rem;cursor:pointer;border:none;transition:.15s;display:inline-flex;align-items:center;gap:7px;white-space:nowrap;}
.btn-p{background:var(--p);color:#fff;} .btn-p:hover{background:var(--pd);transform:translateY(-1px);}
.btn-g{background:var(--g);color:#fff;} .btn-g:hover{background:#0da271;}
.btn-b{background:var(--b);color:#fff;} .btn-b:hover{background:#2563eb;}
.btn-r{background:var(--r);color:#fff;} .btn-r:hover{background:#dc2626;}
.btn-gh{background:var(--bg3);color:var(--text);border:1px solid var(--border);} .btn-gh:hover{background:var(--border);}
.btn-sm{padding:6px 12px;font-size:.76rem;}
.btn-xs{padding:6px 10px;font-size:.7rem;border-radius:5px;}

.fi{background:var(--bg3);border:1.5px solid var(--border);border-radius:var(--rad3);padding:8px 12px;color:var(--text);font-size:.85rem;font-weight:500;transition:.2s;outline:none;}
.fi:focus{border-color:var(--p);box-shadow:0 0 0 3px var(--pl);}

/* TABLE */
.tw{overflow-x:auto;}
table{width:100%;border-collapse:collapse;}
th{text-align:left;padding:12px 14px;font-size:.68rem;font-weight:800;letter-spacing:1px;color:var(--text2);text-transform:uppercase;border-bottom:1px solid var(--border);white-space:nowrap;}
td{padding:15px 14px;border-bottom:1px solid var(--border);font-size:.84rem;font-weight:500;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:rgba(255,255,255,0.02);}
[data-theme="light"] tr:hover td{background:rgba(0,0,0,0.02);}

/* BADGES */
.bdg{padding:4px 10px;border-radius:20px;font-size:.7rem;font-weight:800;letter-spacing:.4px;white-space:nowrap;display:inline-block;}
.bgray{background:var(--bg3);color:var(--text2);}

/* MODAL */
.ov{position:fixed;inset:0;background:rgba(0,0,0,.8);backdrop-filter:blur(8px);z-index:7000;display:flex;align-items:center;justify-content:center;padding:20px;opacity:0;pointer-events:none;transition:.25s;}
.ov.show{opacity:1;pointer-events:all;}
.cfm{background:var(--card);border:1px solid var(--border);border-radius:20px;padding:36px 30px;max-width:380px;width:100%;text-align:center;transform:scale(.88);transition:.3s;}
.ov.show .cfm{transform:scale(1);}
.cfm-icon{font-size:2.5rem;margin-bottom:12px;}
.cfm-title{font-family:'Bebas Neue';font-size:1.5rem;letter-spacing:1px;margin-bottom:6px;}
.cfm-btns{display:flex;gap:10px;justify-content:center;margin-top:20px;}
</style>
</head>
<body>

<aside class="sbar" id="sbar">
  <div class="sbar-logo">
    <div class="sl-icon">🐾</div>
    <div class="sl-txt"><h1>PetAdopt</h1><span data-i18n="shelter_sys">Shelter System</span></div>
  </div>
  <button class="sbar-toggle" onclick="toggleSbar()"><i class="fas fa-chevron-left"></i></button>
  <div class="sbar-user">
    <div class="su-av"><?= $user['initial'] ?></div>
    <div class="su-info">
      <div class="su-name"><?= htmlspecialchars($user['fullname']) ?></div>
      <div class="su-role"><?= htmlspecialchars(ucfirst($user['role'])) ?></div>
    </div>
  </div>
  <nav class="sbar-nav">
    <div class="s-sec" data-i18n="overview">Overview</div>
    <div class="ni" onclick="window.location.href='dashboard.php'"><i class="fas fa-chart-pie"></i><span class="nl" data-i18n="dashboard">Dashboard</span></div>
    
    <div class="s-sec" data-i18n="animals_sec">Animals</div>
    <div class="ni" onclick="window.location.href='view_animals.php'"><i class="fas fa-paw"></i><span class="nl" data-i18n="animals">Animals</span></div>
    
    <div class="s-sec" data-i18n="people_sec">People</div>
    <div class="ni on" onclick="window.location.href='view_adopters.php'"><i class="fas fa-heart"></i><span class="nl" data-i18n="adopters">Adopters</span></div>
    <div class="ni" onclick="window.location.href='register_adopter.php'"><i class="fas fa-plus"></i><span class="nl" data-i18n="add_adopter">Add Adopter</span></div>
    
    <div class="s-sec" data-i18n="system_sec">System</div>
    <div class="ni" onclick="window.location.href='reports.php'"><i class="fas fa-chart-line"></i><span class="nl" data-i18n="reports">Reports</span></div>
    <?php if($user['role'] == 'admin'): ?>
        <div class="ni" onclick="window.location.href='view_users.php'"><i class="fas fa-users-gear" style="color:var(--y)"></i><span class="nl" data-i18n="admin_controls">Admin Controls</span></div>
    <?php endif; ?>
  </nav>
  <div class="sbar-bottom">
    <div class="ni" onclick="window.location.href='logout.php'"><i class="fas fa-right-from-bracket" style="color:var(--r)"></i><span class="nl" style="color:var(--r)" data-i18n="sign_out">Sign Out</span></div>
  </div>
</aside>

<div class="main">
  <header class="topbar">
    <span class="topbar-title" data-i18n="manage_adopters">Manage Adopters</span>
    <div class="tb-right">
      <div class="lang-sw">
        <div class="lang-opt on" id="langEn" onclick="setLanguage('en')">EN</div>
        <div class="lang-opt" id="langKu" onclick="setLanguage('ku')">KU</div>
      </div>
      <div class="theme-sw">
        <div class="t-opt on" id="tDark" onclick="setTheme('dark')"><i class="fas fa-moon"></i></div>
        <div class="t-opt" id="tLight" onclick="setTheme('light')"><i class="fas fa-sun"></i></div>
      </div>
      <div class="u-av"><?= $user['initial'] ?></div>
    </div>
  </header>

  <div class="content">
    <div class="card">
      <div class="ch">
        <div class="ct"><i class="fas fa-heart" style="color:var(--b)"></i><span data-i18n="adopter_directory">Adopter Directory</span></div>
        
        <form method="GET" action="view_adopters.php" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin:0;">
          <input type="text" name="search" class="fi" style="width:220px;" placeholder="🔍 Search Name or Phone..." value="<?= htmlspecialchars($search_query) ?>">
          
          <button type="submit" class="btn btn-gh btn-sm" data-i18n="search">Search</button>
          <?php if(!empty($search_query)): ?>
              <a href="view_adopters.php" class="btn btn-gh btn-sm" style="color:var(--r);" data-i18n="clear">Clear</a>
          <?php endif; ?>
          
          <a href="register_adopter.php" class="btn btn-p btn-sm"><i class="fas fa-plus"></i> <span data-i18n="add_adopter">Add Adopter</span></a>
        </form>
      </div>

      <div class="tw">
        <table>
          <thead>
            <tr>
              <th data-i18n="name">Name</th>
              <th data-i18n="phone">Phone</th>
              <th data-i18n="address">Address</th>
              <th data-i18n="preference">Preference</th>
              <th data-i18n="joined">Joined</th>
              <th data-i18n="actions">Actions</th>
            </tr>
          </thead>
          <tbody>
              <?php if (count($adopters) > 0): ?>
                  <?php foreach ($adopters as $adopter): ?>
                      <tr>
                          <td><strong><?= htmlspecialchars($adopter['fname'] . ' ' . $adopter['lname']) ?></strong></td>
                          <td style="color:var(--p); font-weight: 600;"><?= htmlspecialchars($adopter['phone']) ?></td>
                          <td style="color:var(--text2)"><?= htmlspecialchars($adopter['address']) ?></td>
                          <td><span class="bdg bgray"><?= htmlspecialchars($adopter['preference'] ?: 'None specified') ?></span></td>
                          <td style="color:var(--text2)"><?= date('M d, Y', strtotime($adopter['created_at'])) ?></td>
                          <td>
                              <div style="display:flex;gap:5px">
                                  <a href="record_adoption.php?adopter_id=<?= $adopter['adopterId'] ?>" class="btn btn-b btn-xs" title="Process Adoption"><i class="fas fa-house-heart"></i> Process</a>
                              </div>
                          </td>
                      </tr>
                  <?php endforeach; ?>
              <?php else: ?>
                  <tr>
                      <td colspan="6" style="text-align: center; padding: 40px; color: var(--text2);">
                          <i class="fas fa-search" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i>
                          <p data-i18n="no_adopters_found">No adopters found matching your search.</p>
                      </td>
                  </tr>
              <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>

<script>
// ════ TRANSLATIONS ════
const translations = {
  en: {
    shelter_sys:"Shelter System", overview:"Overview", animals_sec:"Animals", people_sec:"People", system_sec:"System",
    dashboard:"Dashboard", animals:"Animals", adopters:"Adopters", reports:"Reports", admin_controls:"Admin Controls",
    sign_out:"Sign Out", manage_adopters:"Manage Adopters", adopter_directory:"Adopter Directory", search:"Search", clear:"Clear",
    add_adopter:"Add Adopter", name:"Name", phone:"Phone", address:"Address", preference:"Preference", joined:"Joined", actions:"Actions",
    no_adopters_found:"No adopters found matching your search."
  },
  ku: {
    shelter_sys:"سیستەمی پەناگا", overview:"پوختە", animals_sec:"ئاژەڵەکان", people_sec:"کەسەکان", system_sec:"سیستەم",
    dashboard:"داشبۆرد", animals:"ئاژەڵەکان", adopters:"خاوەن نوێکان", reports:"ڕاپۆرتەکان", admin_controls:"بەڕێوەبردن",
    sign_out:"دەرچوون", manage_adopters:"بەڕێوەبردنی وەرگرەکان", adopter_directory:"لیستی وەرگرەکان", search:"گەڕان", clear:"پاککردنەوە",
    add_adopter:"زیادکردنی وەرگر", name:"ناو", phone:"تەلەفۆن", address:"ناونیشان", preference:"ئارەزوو", joined:"بەشداربوو", actions:"کردارەکان",
    no_adopters_found:"هیچ وەرگرێک نەدۆزرایەوە بۆ ئەم گەڕانە."
  }
};

let currentLang = 'en';
function T(key){ return (translations[currentLang]||{})[key] || (translations.en[key] || key); }

function setLanguage(lang) {
  currentLang = lang;
  const isKu = lang === 'ku';
  document.documentElement.lang = lang;
  document.documentElement.dir = isKu ? 'rtl' : 'ltr';
  
  ['langEn'].forEach(id=>{ const el=document.getElementById(id); if(el) el.classList.toggle('on', lang==='en'); });
  ['langKu'].forEach(id=>{ const el=document.getElementById(id); if(el) el.classList.toggle('on', lang==='ku'); });
  
  document.querySelectorAll('[data-i18n]').forEach(el=>{
    const k=el.getAttribute('data-i18n');
    if(T(k)) el.textContent=T(k);
  });
}

// ════ UI FUNCTIONS ════
function toggleSbar() { document.getElementById('sbar').classList.toggle('mini'); }

function setTheme(t) {
  document.documentElement.setAttribute('data-theme', t);
  document.getElementById('tDark').classList.toggle('on', t==='dark');
  document.getElementById('tLight').classList.toggle('on', t==='light');
}
</script>

</body>
</html>