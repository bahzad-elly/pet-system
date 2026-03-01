<?php
// edit_user.php
session_start();
require_once 'db.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header("location: dashboard.php"); exit;
}

$user_fullname = htmlspecialchars($_SESSION['fullname']);
$user_role = htmlspecialchars(ucfirst($_SESSION['role']));
$user_initial = strtoupper(substr($_SESSION['fullname'], 0, 1));
$message = '';
$uid = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($uid === 0) die("Invalid User ID.");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname']); $email = trim($_POST['email']);
    $username = trim($_POST['username']); $role = $_POST['role'];
    $sql = "UPDATE users SET fullname=:fullname, email=:email, username=:username, role=:role WHERE uid=:uid";
    if (!empty($_POST['password'])) {
        $sql = "UPDATE users SET fullname=:fullname, email=:email, username=:username, role=:role, password=:password WHERE uid=:uid";
    }
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':fullname',$fullname);$stmt->bindParam(':email',$email);
        $stmt->bindParam(':username',$username);$stmt->bindParam(':role',$role);$stmt->bindParam(':uid',$uid);
        if (!empty($_POST['password'])) { $hashed = hash('sha256',$_POST['password']); $stmt->bindParam(':password',$hashed); }
        if ($stmt->execute()) {
            $pdo->prepare("INSERT INTO user_activity_log (uid, actiontype, targettable, targetid, details) VALUES (:u,'Update','users',:t,'Updated user account')")->execute([':u'=>$_SESSION['uid'],':t'=>$uid]);
            $message = 'success';
        }
    } catch (PDOException $e) { $message = ($e->getCode()==23000) ? 'duplicate' : 'error'; }
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE uid = :uid");
$stmt->execute([':uid' => $uid]);
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user_data) die("User not found.");
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🐾 PetAdopt — Edit User</title>
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
.theme-sw,.lang-sw{display:flex;align-items:center;background:var(--bg3);border:1px solid var(--border);border-radius:20px;padding:3px;gap:2px;}
.t-opt{width:30px;height:27px;border-radius:15px;display:flex;align-items:center;justify-content:center;font-size:.76rem;transition:.2s;color:var(--text2);cursor:pointer;}
.t-opt.on{background:var(--card);color:var(--text);box-shadow:var(--shadow2);}
.lang-opt{width:36px;height:27px;border-radius:15px;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:800;transition:.2s;color:var(--text2);cursor:pointer;}
.lang-opt.on{background:var(--card);color:var(--text);box-shadow:var(--shadow2);}
.u-av{width:37px;height:37px;border-radius:50%;color:#000;background:linear-gradient(135deg,var(--p),var(--y));display:flex;align-items:center;justify-content:center;font-weight:900;font-size:.88rem;cursor:pointer;}
.content{flex:1;overflow-y:auto;padding:24px;}
.card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:24px;animation:fadeUp .5s ease both;max-width:600px;}
@keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
.ch{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:10px;}
.ct{font-family:'Bebas Neue';font-size:1.1rem;letter-spacing:1.2px;display:flex;align-items:center;gap:8px;}
.ct i{color:var(--y);}
.fg{margin-bottom:18px;}
.fg label{display:block;font-size:.72rem;font-weight:800;letter-spacing:.8px;text-transform:uppercase;color:var(--text2);margin-bottom:7px;}
.fi{width:100%;background:var(--bg3);border:1.5px solid var(--border);border-radius:9px;padding:10px 13px;color:var(--text);font-size:.85rem;font-weight:500;transition:.2s;outline:none;font-family:'Plus Jakarta Sans',sans-serif;}
.fi:focus{border-color:var(--p);box-shadow:0 0 0 3px var(--pl);}
.btn{padding:10px 18px;border-radius:9px;font-family:'Plus Jakarta Sans';font-weight:700;font-size:.84rem;cursor:pointer;border:none;transition:.15s;display:inline-flex;align-items:center;gap:7px;}
.btn-y{background:var(--y);color:#000;} .btn-y:hover{transform:translateY(-1px);}
.btn-gh{background:var(--bg3);color:var(--text);border:1px solid var(--border);} .btn-gh:hover{background:var(--border);}
.alert{padding:12px 16px;border-radius:10px;margin-bottom:18px;display:flex;align-items:center;gap:10px;font-size:.83rem;font-weight:600;}
.alert-success{background:var(--gl);color:var(--g);border:1px solid rgba(16,185,129,.3);}
.alert-error{background:var(--rl);color:var(--r);border:1px solid rgba(239,68,68,.3);}
.sep{border-top:1px solid var(--border);margin:20px 0;}
.pw-note{font-size:.72rem;color:var(--text2);margin-top:5px;font-weight:600;}
@media(max-width:600px){.sbar{display:none;}}
</style>
</head>
<body>
<aside class="sbar" id="sbar">
  <div class="sbar-logo"><div class="sl-icon">🐾</div><div class="sl-txt"><h1>PetAdopt</h1><span data-i18n="shelter_sys">Shelter System</span></div></div>
  <button class="sbar-toggle" onclick="toggleSbar()"><i class="fas fa-chevron-left"></i></button>
  <div class="sbar-user"><div class="su-av"><?= $user_initial ?></div><div class="su-info"><div class="su-name"><?= $user_fullname ?></div><div class="su-role"><?= $user_role ?></div></div></div>
  <nav class="sbar-nav">
    <div class="s-sec" data-i18n="overview">Overview</div>
    <a href="dashboard.php" class="ni"><i class="fas fa-chart-pie"></i><span class="nl" data-i18n="dashboard">Dashboard</span></a>
    <div class="s-sec" data-i18n="animals_sec">Animals</div>
    <a href="view_animals.php" class="ni"><i class="fas fa-paw"></i><span class="nl" data-i18n="animals">Animals</span></a>
    <a href="add_animal.php" class="ni"><i class="fas fa-plus"></i><span class="nl" data-i18n="add_animal">Add Animal</span></a>
    <div class="s-sec" data-i18n="people_sec">People</div>
    <a href="view_adopters.php" class="ni"><i class="fas fa-heart"></i><span class="nl" data-i18n="adopters">Adopters</span></a>
    <a href="register_adopter.php" class="ni"><i class="fas fa-user-plus"></i><span class="nl" data-i18n="add_adopter">Add Adopter</span></a>
    <div class="s-sec" data-i18n="system_sec">System</div>
    <a href="reports.php" class="ni"><i class="fas fa-chart-line"></i><span class="nl" data-i18n="reports">Reports</span></a>
    <a href="view_users.php" class="ni on"><i class="fas fa-users-gear" style="color:var(--y)"></i><span class="nl" data-i18n="admin_ctrl">Admin Controls</span></a>
  </nav>
  <div class="sbar-bottom"><a href="logout.php" class="ni"><i class="fas fa-right-from-bracket" style="color:var(--r)"></i><span class="nl" style="color:var(--r)" data-i18n="sign_out">Sign Out</span></a></div>
</aside>

<div class="main">
  <header class="topbar">
    <span class="tb-clock" id="clock">00:00</span>
    <span class="topbar-title"><span data-i18n="edit_user">Edit User</span>: <?= htmlspecialchars($user_data['username']) ?></span>
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
        <div class="ct"><i class="fas fa-user-pen"></i><span data-i18n="edit_user">Edit User</span></div>
        <a href="view_users.php" class="btn btn-gh"><i class="fas fa-arrow-left"></i><span data-i18n="back_users">Back to Users</span></a>
      </div>
      <?php if($message==='success'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i><span data-i18n="success_msg">User updated successfully!</span></div>
      <?php elseif($message==='duplicate'): ?><div class="alert alert-error"><i class="fas fa-circle-exclamation"></i><span data-i18n="dup_error">Username or Email already exists.</span></div>
      <?php elseif(!empty($message)): ?><div class="alert alert-error"><i class="fas fa-circle-exclamation"></i><span data-i18n="error_msg">An error occurred. Please try again.</span></div>
      <?php endif; ?>
      <form action="edit_user.php?id=<?= $uid ?>" method="POST">
        <div class="fg"><label data-i18n="fullname_lbl">Full Name</label><input type="text" name="fullname" class="fi" value="<?= htmlspecialchars($user_data['fullname']) ?>" required></div>
        <div class="fg"><label data-i18n="email_lbl">Email</label><input type="email" name="email" class="fi" value="<?= htmlspecialchars($user_data['email']) ?>" required></div>
        <div class="fg"><label data-i18n="username_lbl">Username</label><input type="text" name="username" class="fi" value="<?= htmlspecialchars($user_data['username']) ?>" required></div>
        <div class="fg">
          <label data-i18n="new_pw">New Password</label>
          <input type="password" name="password" class="fi" placeholder="Leave blank to keep current">
          <p class="pw-note" data-i18n="pw_hint">Leave blank to keep the existing password unchanged.</p>
        </div>
        <div class="fg">
          <label data-i18n="role_lbl">Role</label>
          <select name="role" class="fi">
            <option value="staff" <?= $user_data['role']==='staff'?'selected':'' ?> data-i18n="staff_opt">Staff</option>
            <option value="admin" <?= $user_data['role']==='admin'?'selected':'' ?> data-i18n="admin_opt">Admin</option>
          </select>
        </div>
        <div class="sep"></div>
        <button type="submit" class="btn btn-y"><i class="fas fa-floppy-disk"></i><span data-i18n="update_btn">Update User</span></button>
      </form>
    </div>
  </div>
</div>

<script>
const tr={en:{shelter_sys:"Shelter System",overview:"Overview",animals_sec:"Animals",people_sec:"People",system_sec:"System",dashboard:"Dashboard",animals:"Animals",add_animal:"Add Animal",adopters:"Adopters",add_adopter:"Add Adopter",reports:"Reports",admin_ctrl:"Admin Controls",sign_out:"Sign Out",edit_user:"Edit User",back_users:"Back to Users",success_msg:"User updated successfully!",dup_error:"Username or Email already exists.",error_msg:"An error occurred.",fullname_lbl:"Full Name",email_lbl:"Email",username_lbl:"Username",new_pw:"New Password",pw_hint:"Leave blank to keep the existing password unchanged.",role_lbl:"Role",staff_opt:"Staff",admin_opt:"Admin",update_btn:"Update User"},ku:{shelter_sys:"سیستەمی پەناگا",overview:"پوختە",animals_sec:"ئاژەڵەکان",people_sec:"کەسەکان",system_sec:"سیستەم",dashboard:"داشبۆرد",animals:"ئاژەڵەکان",add_animal:"زیادکردنی ئاژەڵ",adopters:"خاوەن نوێکان",add_adopter:"زیادکردنی وەرگر",reports:"ڕاپۆرتەکان",admin_ctrl:"بەڕێوەبردن",sign_out:"دەرچوون",edit_user:"دەستکاریکردنی بەکارهێنەر",back_users:"گەڕانەوە بۆ بەکارهێنەرەکان",success_msg:"بەکارهێنەر بە سەرکەوتوویی نوێکرایەوە!",dup_error:"ناوی بەکارهێنەر یان ئیمەیڵ پێشتر بوونی هەیە.",error_msg:"هەڵەیەک ڕوویدا.",fullname_lbl:"ناوی تەواو",email_lbl:"ئیمەیڵ",username_lbl:"ناوی بەکارهێنەر",new_pw:"وشەی نهێنی نوێ",pw_hint:"بۆ هێشتنەوەی وشەی نهێنی بەتاڵ بهێڵەوە.",role_lbl:"ڕۆڵ",staff_opt:"ستاف",admin_opt:"بەڕێوەبەر",update_btn:"نوێکردنەوەی بەکارهێنەر"}};
let lang=localStorage.getItem('lang')||'en',theme=localStorage.getItem('theme')||'dark';
function T(k){return(tr[lang]||{})[k]||tr.en[k]||k;}
function setLanguage(l){lang=l;localStorage.setItem('lang',l);const isKu=l==='ku';document.documentElement.lang=l;document.documentElement.dir=isKu?'rtl':'ltr';document.getElementById('langEn').classList.toggle('on',l==='en');document.getElementById('langKu').classList.toggle('on',l==='ku');document.querySelectorAll('[data-i18n]').forEach(el=>{const k=el.getAttribute('data-i18n');if(T(k))el.textContent=T(k);});}
function setTheme(t){theme=t;localStorage.setItem('theme',t);document.documentElement.setAttribute('data-theme',t);document.getElementById('tDark').classList.toggle('on',t==='dark');document.getElementById('tLight').classList.toggle('on',t==='light');}
function toggleSbar(){document.getElementById('sbar').classList.toggle('mini');}
function tick(){const n=new Date();document.getElementById('clock').textContent=String(n.getHours()).padStart(2,'0')+':'+String(n.getMinutes()).padStart(2,'0');}
tick();setInterval(tick,10000);setTheme(theme);setLanguage(lang);
</script>
</body>
</html>