<?php
// add_animal.php
session_start();
require_once 'db.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php"); exit;
}

$user_fullname = htmlspecialchars($_SESSION['fullname']);
$user_role = htmlspecialchars(ucfirst($_SESSION['role']));
$user_initial = strtoupper(substr($_SESSION['fullname'], 0, 1));
$message = '';

$intake_sources = [];
try {
    $stmt = $pdo->query("SELECT iid, sname FROM intake_source");
    $intake_sources = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $species = trim($_POST['species']);
    $breed = trim($_POST['breed']);
    $gender = $_POST['gender'];
    $age = !empty($_POST['age']) ? $_POST['age'] : null;
    $status = $_POST['status'];
    $intake_id = !empty($_POST['intake_id']) ? $_POST['intake_id'] : null;
    
    $photo_path = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['photo']['type'], $allowed_types)) {
            $filename = time() . '_' . basename($_FILES['photo']['name']);
            $target_dir = "uploads/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
            $target_file = $target_dir . $filename;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
                $photo_path = $filename;
            } else { $message = 'error_upload'; }
        } else { $message = 'error_filetype'; }
    }

    if (empty($message)) {
        $sql = "INSERT INTO animals (name, gender, species, breed, age, status, photo, intake_id) VALUES (:name, :gender, :species, :breed, :age, :status, :photo, :intake_id)";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':name', $name); $stmt->bindParam(':gender', $gender);
            $stmt->bindParam(':species', $species); $stmt->bindParam(':breed', $breed);
            $stmt->bindParam(':age', $age); $stmt->bindParam(':status', $status);
            $stmt->bindParam(':photo', $photo_path); $stmt->bindParam(':intake_id', $intake_id);
            if ($stmt->execute()) {
                $animal_id = $pdo->lastInsertId();
                $log_sql = "INSERT INTO user_activity_log (uid, actiontype, targettable, targetid, details) VALUES (:uid, 'Create', 'animals', :targetid, 'Registered new animal')";
                $pdo->prepare($log_sql)->execute([':uid' => $_SESSION['uid'], ':targetid' => $animal_id]);
                $message = 'success_' . htmlspecialchars($name);
            }
        } catch (PDOException $e) { $message = 'error_db'; }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🐾 PetAdopt — Add Animal</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
[data-theme="dark"]{--bg:#0a0a0a;--bg2:#111;--bg3:#1a1a1a;--bg4:#222;--card:#141414;--border:#242424;--border2:#2e2e2e;--text:#f0f0f0;--text2:#777;--text3:#383838;--shadow:0 16px 56px rgba(0,0,0,.8);--shadow2:0 4px 20px rgba(0,0,0,.5);--glass:rgba(255,255,255,.025);}
[data-theme="light"]{--bg:#f5f0eb;--bg2:#fff;--bg3:#f0ebe4;--bg4:#e8e2db;--card:#fff;--border:#e5dfd8;--border2:#d5cfc8;--text:#1a1208;--text2:#7a6e65;--text3:#c0b8af;--shadow:0 12px 40px rgba(0,0,0,.08);--shadow2:0 4px 16px rgba(0,0,0,.06);--glass:rgba(0,0,0,.015);}
:root{--p:#f97316;--pd:#ea6c10;--pl:rgba(249,115,22,.12);--g:#10b981;--gl:rgba(16,185,129,.12);--b:#3b82f6;--y:#f59e0b;--r:#ef4444;--rl:rgba(239,68,68,.12);}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;overflow:hidden;}
::-webkit-scrollbar{width:4px;}::-webkit-scrollbar-thumb{background:var(--border2);border-radius:4px;}
a{text-decoration:none;color:inherit;}
[dir="rtl"] .sbar{border-right:none;border-left:1px solid var(--border);}
[dir="rtl"] .ni.on::before{left:auto;right:0;}
[dir="rtl"] .sbar-toggle{right:auto;left:-13px;}
[dir="rtl"] .tb-right{margin-left:0;margin-right:auto;}
.sbar{width:230px;min-width:230px;background:var(--bg2);border-right:1px solid var(--border);display:flex;flex-direction:column;transition:width .35s cubic-bezier(.4,0,.2,1),min-width .35s cubic-bezier(.4,0,.2,1);position:relative;z-index:100;overflow:hidden;}
.sbar.mini{width:62px;min-width:62px;}
.sbar-logo{display:flex;align-items:center;gap:12px;padding:20px 16px 16px;border-bottom:1px solid var(--border);}
.sl-icon{width:38px;height:38px;min-width:38px;background:linear-gradient(135deg,var(--p),#fb923c);border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:1.15rem;box-shadow:0 4px 16px rgba(249,115,22,.4);flex-shrink:0;}
.sl-txt{overflow:hidden;white-space:nowrap;transition:opacity .25s,width .35s;}
.sbar.mini .sl-txt{opacity:0;width:0;pointer-events:none;}
.sl-txt h1{font-family:'Bebas Neue';font-size:1.18rem;letter-spacing:2.5px;}
.sl-txt span{font-size:.6rem;font-weight:700;color:var(--text2);letter-spacing:1.8px;text-transform:uppercase;}
.sbar-toggle{position:absolute;right:-13px;top:24px;width:26px;height:26px;background:var(--bg3);border:1px solid var(--border2);border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.7rem;color:var(--text2);z-index:10;}
.sbar.mini .sbar-toggle i{transform:rotate(180deg);}
.sbar-user{padding:14px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;overflow:hidden;}
.su-av{width:34px;height:34px;min-width:34px;border-radius:10px;background:linear-gradient(135deg,var(--p),var(--y));color:#000;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:.85rem;flex-shrink:0;}
.su-info{overflow:hidden;white-space:nowrap;transition:opacity .25s,width .35s;}
.sbar.mini .su-info{opacity:0;width:0;pointer-events:none;}
.su-name{font-weight:800;font-size:.83rem;}
.su-role{font-size:.68rem;font-weight:600;color:var(--p);margin-top:1px;}
.sbar-nav{flex:1;overflow-y:auto;overflow-x:hidden;padding:10px 8px;}
.s-sec{font-size:.58rem;font-weight:800;color:var(--text3);letter-spacing:2px;text-transform:uppercase;padding:12px 10px 5px;white-space:nowrap;overflow:hidden;transition:opacity .2s;}
.sbar.mini .s-sec{opacity:0;}
.ni{display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:9px;cursor:pointer;color:var(--text2);font-size:.82rem;font-weight:600;transition:.15s;position:relative;white-space:nowrap;margin-bottom:1px;}
.ni:hover{background:var(--bg3);color:var(--text);}
.ni.on{background:var(--pl);color:var(--p);font-weight:700;}
.ni.on::before{content:'';position:absolute;left:0;top:22%;bottom:22%;width:3px;border-radius:2px;background:var(--p);}
.ni i{font-size:.92rem;min-width:18px;text-align:center;flex-shrink:0;}
.nl{transition:opacity .25s;overflow:hidden;flex:1;}
.sbar.mini .nl{opacity:0;width:0;pointer-events:none;}
.sbar-bottom{padding:12px 8px;border-top:1px solid var(--border);}
.main{flex:1;display:flex;flex-direction:column;overflow:hidden;min-width:0;}
.topbar{height:62px;min-height:62px;background:var(--bg2);border-bottom:1px solid var(--border);padding:0 24px;display:flex;align-items:center;gap:14px;}
.tb-clock{font-size:.78rem;font-weight:800;color:var(--text2);padding:6px 12px;background:var(--bg3);border:1px solid var(--border);border-radius:8px;}
.topbar-title{font-family:'Bebas Neue';font-size:1.45rem;letter-spacing:1.5px;}
.tb-right{margin-left:auto;display:flex;align-items:center;gap:8px;}
.tb-btn{width:37px;height:37px;background:var(--bg3);border:1px solid var(--border);border-radius:9px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--text2);transition:.15s;font-size:.88rem;}
.tb-btn:hover{color:var(--text);border-color:var(--border2);}
.theme-sw,.lang-sw{display:flex;align-items:center;background:var(--bg3);border:1px solid var(--border);border-radius:20px;padding:3px;gap:2px;}
.t-opt{width:30px;height:27px;border-radius:15px;display:flex;align-items:center;justify-content:center;font-size:.76rem;transition:.2s;color:var(--text2);cursor:pointer;}
.t-opt.on{background:var(--card);color:var(--text);box-shadow:var(--shadow2);}
.lang-opt{width:36px;height:27px;border-radius:15px;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:800;transition:.2s;color:var(--text2);cursor:pointer;}
.lang-opt.on{background:var(--card);color:var(--text);box-shadow:var(--shadow2);}
.u-av{width:37px;height:37px;border-radius:50%;color:#000;background:linear-gradient(135deg,var(--p),var(--y));display:flex;align-items:center;justify-content:center;font-weight:900;font-size:.88rem;cursor:pointer;box-shadow:0 2px 12px rgba(249,115,22,.35);}
.content{flex:1;overflow-y:auto;padding:24px;}
.card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:24px;animation:fadeUp .5s ease both;max-width:700px;}
@keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
.ch{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:10px;}
.ct{font-family:'Bebas Neue';font-size:1.1rem;letter-spacing:1.2px;display:flex;align-items:center;gap:8px;}
.ct i{color:var(--p);}
.fg{margin-bottom:18px;}
.fg label{display:block;font-size:.72rem;font-weight:800;letter-spacing:.8px;text-transform:uppercase;color:var(--text2);margin-bottom:7px;}
.fi{width:100%;background:var(--bg3);border:1.5px solid var(--border);border-radius:9px;padding:10px 13px;color:var(--text);font-size:.85rem;font-weight:500;transition:.2s;outline:none;font-family:'Plus Jakarta Sans',sans-serif;}
.fi:focus{border-color:var(--p);box-shadow:0 0 0 3px var(--pl);}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.btn{padding:10px 18px;border-radius:9px;font-family:'Plus Jakarta Sans';font-weight:700;font-size:.84rem;cursor:pointer;border:none;transition:.15s;display:inline-flex;align-items:center;gap:7px;}
.btn-p{background:linear-gradient(135deg,var(--p),var(--pd));color:#fff;box-shadow:0 4px 16px rgba(249,115,22,.3);}
.btn-p:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(249,115,22,.4);}
.btn-gh{background:var(--bg3);color:var(--text);border:1px solid var(--border);}
.btn-gh:hover{background:var(--border);}
.alert{padding:12px 16px;border-radius:10px;margin-bottom:18px;display:flex;align-items:center;gap:10px;font-size:.83rem;font-weight:600;}
.alert-success{background:var(--gl);color:var(--g);border:1px solid rgba(16,185,129,.3);}
.alert-error{background:var(--rl);color:var(--r);border:1px solid rgba(239,68,68,.3);}
.sep{border-top:1px solid var(--border);margin:20px 0;}
@media(max-width:600px){.grid-2{grid-template-columns:1fr;}.sbar{display:none;}}
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
    <div class="su-av"><?= $user_initial ?></div>
    <div class="su-info"><div class="su-name"><?= $user_fullname ?></div><div class="su-role"><?= $user_role ?></div></div>
  </div>
  <nav class="sbar-nav">
    <div class="s-sec" data-i18n="overview">Overview</div>
    <a href="dashboard.php" class="ni"><i class="fas fa-chart-pie"></i><span class="nl" data-i18n="dashboard">Dashboard</span></a>
    <div class="s-sec" data-i18n="animals_sec">Animals</div>
    <a href="view_animals.php" class="ni"><i class="fas fa-paw"></i><span class="nl" data-i18n="animals">Animals</span></a>
    <a href="add_animal.php" class="ni on"><i class="fas fa-plus"></i><span class="nl" data-i18n="add_animal">Add Animal</span></a>
    <div class="s-sec" data-i18n="people_sec">People</div>
    <a href="view_adopters.php" class="ni"><i class="fas fa-heart"></i><span class="nl" data-i18n="adopters">Adopters</span></a>
    <a href="register_adopter.php" class="ni"><i class="fas fa-user-plus"></i><span class="nl" data-i18n="add_adopter">Add Adopter</span></a>
    <div class="s-sec" data-i18n="system_sec">System</div>
    <a href="reports.php" class="ni"><i class="fas fa-chart-line"></i><span class="nl" data-i18n="reports">Reports</span></a>
    <?php if($_SESSION['role']==='admin'): ?><a href="view_users.php" class="ni"><i class="fas fa-users-gear" style="color:var(--y)"></i><span class="nl" data-i18n="admin_ctrl">Admin Controls</span></a><?php endif; ?>
  </nav>
  <div class="sbar-bottom"><a href="logout.php" class="ni"><i class="fas fa-right-from-bracket" style="color:var(--r)"></i><span class="nl" style="color:var(--r)" data-i18n="sign_out">Sign Out</span></a></div>
</aside>

<div class="main">
  <header class="topbar">
    <span class="tb-clock" id="clock">00:00</span>
    <span class="topbar-title" data-i18n="add_animal">Add Animal</span>
    <div class="tb-right">
      <div class="lang-sw"><div class="lang-opt on" id="langEn" onclick="setLanguage('en')">EN</div><div class="lang-opt" id="langKu" onclick="setLanguage('ku')">KU</div></div>
      <div class="theme-sw"><div class="t-opt on" id="tDark" onclick="setTheme('dark')"><i class="fas fa-moon"></i></div><div class="t-opt" id="tLight" onclick="setTheme('light')"><i class="fas fa-sun"></i></div></div>
      <button class="tb-btn" onclick="window.location.reload()"><i class="fas fa-rotate"></i></button>
      <div class="u-av"><?= $user_initial ?></div>
    </div>
  </header>
  <div class="content">
    <div class="card">
      <div class="ch">
        <div class="ct"><i class="fas fa-paw"></i><span data-i18n="register_animal">Register New Animal</span></div>
        <a href="view_animals.php" class="btn btn-gh"><i class="fas fa-arrow-left"></i><span data-i18n="back_animals">Back to Animals</span></a>
      </div>

      <?php if(!empty($message)): ?>
        <?php if(str_starts_with($message,'success_')): ?>
          <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= str_replace('success_', '', $message) ?> <span data-i18n="registered_ok">registered successfully!</span></span></div>
        <?php else: ?>
          <div class="alert alert-error"><i class="fas fa-circle-exclamation"></i><span data-i18n="error_msg">An error occurred. Please try again.</span></div>
        <?php endif; ?>
      <?php endif; ?>

      <form action="add_animal.php" method="POST" enctype="multipart/form-data">
        <div class="fg"><label data-i18n="animal_name">Name</label><input type="text" name="name" class="fi" required></div>
        <div class="grid-2">
          <div class="fg"><label data-i18n="species">Species (e.g., Dog, Cat)</label><input type="text" name="species" class="fi" required></div>
          <div class="fg"><label data-i18n="breed">Breed</label><input type="text" name="breed" class="fi"></div>
        </div>
        <div class="grid-2">
          <div class="fg">
            <label data-i18n="gender">Gender</label>
            <select name="gender" class="fi">
              <option value="Unknown" data-i18n="unknown">Unknown</option>
              <option value="Male" data-i18n="male">Male</option>
              <option value="Female" data-i18n="female">Female</option>
            </select>
          </div>
          <div class="fg"><label data-i18n="age_yrs">Age (Years)</label><input type="number" name="age" class="fi" min="0"></div>
        </div>
        <div class="fg">
          <label data-i18n="status">Status</label>
          <select name="status" class="fi" required>
            <option value="Available" data-i18n="available">Available</option>
            <option value="Pending" data-i18n="pending">Pending</option>
            <option value="Medical Care" data-i18n="medical_care">Medical Care</option>
            <option value="Adopted" data-i18n="adopted">Adopted</option>
          </select>
        </div>
        <div class="fg">
          <label data-i18n="intake_src">Intake Source</label>
          <select name="intake_id" class="fi">
            <option value="" data-i18n="select_source">-- Select Source --</option>
            <?php foreach($intake_sources as $src): ?><option value="<?= $src['iid'] ?>"><?= htmlspecialchars($src['sname']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="fg"><label data-i18n="photo_opt">Animal Photo (Optional)</label><input type="file" name="photo" class="fi" accept="image/*"></div>
        <div class="sep"></div>
        <button type="submit" class="btn btn-p"><i class="fas fa-paw"></i><span data-i18n="register_btn">Register Animal</span></button>
      </form>
    </div>
  </div>
</div>

<script>
const tr={en:{shelter_sys:"Shelter System",overview:"Overview",animals_sec:"Animals",people_sec:"People",system_sec:"System",dashboard:"Dashboard",animals:"Animals",add_animal:"Add Animal",adopters:"Adopters",add_adopter:"Add Adopter",reports:"Reports",admin_ctrl:"Admin Controls",sign_out:"Sign Out",register_animal:"Register New Animal",back_animals:"Back to Animals",registered_ok:"registered successfully!",error_msg:"An error occurred. Please try again.",animal_name:"Name",species:"Species (e.g., Dog, Cat)",breed:"Breed",gender:"Gender",unknown:"Unknown",male:"Male",female:"Female",age_yrs:"Age (Years)",status:"Status",available:"Available",pending:"Pending",medical_care:"Medical Care",adopted:"Adopted",intake_src:"Intake Source",select_source:"-- Select Source --",photo_opt:"Animal Photo (Optional)",register_btn:"Register Animal"},ku:{shelter_sys:"سیستەمی پەناگا",overview:"پوختە",animals_sec:"ئاژەڵەکان",people_sec:"کەسەکان",system_sec:"سیستەم",dashboard:"داشبۆرد",animals:"ئاژەڵەکان",add_animal:"زیادکردنی ئاژەڵ",adopters:"خاوەن نوێکان",add_adopter:"زیادکردنی وەرگر",reports:"ڕاپۆرتەکان",admin_ctrl:"بەڕێوەبردن",sign_out:"دەرچوون",register_animal:"تۆمارکردنی ئاژەڵی نوێ",back_animals:"گەڕانەوە بۆ ئاژەڵەکان",registered_ok:"بە سەرکەوتوویی تۆمارکرا!",error_msg:"هەڵەیەک ڕوویدا. تکایە دووبارە هەوڵبدەرەوە.",animal_name:"ناو",species:"جۆر (بۆ نموونە، سەگ، پشیلە)",breed:"نەژاد",gender:"ڕەگەز",unknown:"نەزانراو",male:"نێر",female:"مێ",age_yrs:"تەمەن (ساڵ)",status:"دەربارە",available:"بەردەستە",pending:"چاوەڕوان",medical_care:"چارەسەری پزیشکی",adopted:"قبووڵکرا",intake_src:"سەرچاوەی وەرگرتن",select_source:"-- سەرچاوە هەڵبژێرە --",photo_opt:"وێنەی ئاژەڵ (دڵخواز)",register_btn:"تۆمارکردنی ئاژەڵ"}};
let lang=localStorage.getItem('lang')||'en',theme=localStorage.getItem('theme')||'dark';
function T(k){return(tr[lang]||{})[k]||tr.en[k]||k;}
function setLanguage(l){lang=l;localStorage.setItem('lang',l);const isKu=l==='ku';document.documentElement.lang=l;document.documentElement.dir=isKu?'rtl':'ltr';document.getElementById('langEn').classList.toggle('on',l==='en');document.getElementById('langKu').classList.toggle('on',l==='ku');document.querySelectorAll('[data-i18n]').forEach(el=>{const k=el.getAttribute('data-i18n');if(T(k))el.textContent=T(k);});}
function setTheme(t){theme=t;localStorage.setItem('theme',t);document.documentElement.setAttribute('data-theme',t);document.getElementById('tDark').classList.toggle('on',t==='dark');document.getElementById('tLight').classList.toggle('on',t==='light');}
function toggleSbar(){document.getElementById('sbar').classList.toggle('mini');}
function tick(){const n=new Date();document.getElementById('clock').textContent=String(n.getHours()).padStart(2,'0')+':'+String(n.getMinutes()).padStart(2,'0');}
tick();setInterval(tick,10000);
setTheme(theme);setLanguage(lang);
</script>
</body>
</html>