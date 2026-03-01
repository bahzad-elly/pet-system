<?php
// login.php
session_start();
require_once 'db.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = trim($_POST['username']);
    $pass = trim($_POST['password']);
    
    // Hash the password using SHA-256 as required by the project specifications
    $hashed_password = hash('sha256', $pass);

    $sql = "SELECT uid, username, role, fullname FROM users WHERE username = :username AND password = :password LIMIT 1";
    
    if ($stmt = $pdo->prepare($sql)) {
        $stmt->bindParam(':username', $user, PDO::PARAM_STR);
        $stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
        
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Store data in session variables
            $_SESSION['loggedin'] = true;
            $_SESSION['uid'] = $row['uid'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['fullname'] = $row['fullname'];
            
            header("location: dashboard.php");
            exit;
        } else {
            $error = "invalid_credentials";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🐾 PetAdopt — Login</title>
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
  --p:#f97316;--pd:#ea6c10;--pl:rgba(249,115,22,.15);
  --g:#10b981;--b:#3b82f6;--r:#ef4444;
}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
html{scroll-behavior:smooth;}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;align-items:stretch;}
a{text-decoration:none;color:inherit;}

/* ── LEFT PANEL ── */
.left-panel{
  flex:1;background:linear-gradient(145deg,#0d0d0d 0%,#1a0a00 50%,#0d0d0d 100%);
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  padding:60px 50px;position:relative;overflow:hidden;
}
[data-theme="light"] .left-panel{background:linear-gradient(145deg,#fff8f0 0%,#ffe8d0 50%,#fff8f0 100%);}
.left-panel::before{
  content:'';position:absolute;inset:0;
  background:radial-gradient(ellipse at 30% 50%,rgba(249,115,22,.15) 0%,transparent 60%);
}
.left-topo{
  position:absolute;inset:0;opacity:.04;
  background-image:repeating-linear-gradient(0deg,var(--p) 0,var(--p) 1px,transparent 1px,transparent 40px),
                   repeating-linear-gradient(90deg,var(--p) 0,var(--p) 1px,transparent 1px,transparent 40px);
}
.lp-content{position:relative;z-index:1;text-align:center;max-width:400px;}
.lp-logo{
  width:80px;height:80px;background:linear-gradient(135deg,var(--p),#fb923c);
  border-radius:22px;display:flex;align-items:center;justify-content:center;
  font-size:2.2rem;box-shadow:0 8px 32px rgba(249,115,22,.5);margin:0 auto 28px;
  animation:float 3s ease-in-out infinite;
}
@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}
.lp-title{font-family:'Bebas Neue';font-size:3.8rem;letter-spacing:4px;line-height:1;}
.lp-sub{color:var(--text2);font-size:.88rem;font-weight:600;letter-spacing:1.5px;text-transform:uppercase;margin-top:8px;}
.lp-desc{color:var(--text2);font-size:.82rem;font-weight:500;line-height:1.7;margin-top:24px;max-width:300px;margin-left:auto;margin-right:auto;}
.lp-stats{display:flex;gap:20px;margin-top:36px;justify-content:center;flex-wrap:wrap;}
.ls-item{background:var(--glass);border:1px solid var(--border);border-radius:12px;padding:14px 20px;text-align:center;backdrop-filter:blur(10px);}
.ls-num{font-family:'Bebas Neue';font-size:1.8rem;letter-spacing:2px;color:var(--p);}
.ls-lbl{font-size:.64rem;font-weight:700;color:var(--text2);letter-spacing:1px;text-transform:uppercase;margin-top:2px;}

/* ── RIGHT PANEL (FORM) ── */
.right-panel{
  width:480px;min-width:380px;background:var(--bg2);
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  padding:48px 48px;border-left:1px solid var(--border);
}
.rp-top{width:100%;display:flex;justify-content:space-between;align-items:center;margin-bottom:48px;}
.rp-brand{font-family:'Bebas Neue';font-size:1rem;letter-spacing:2px;color:var(--text2);}
.rp-controls{display:flex;align-items:center;gap:8px;}

/* theme switcher */
.theme-sw{display:flex;align-items:center;background:var(--bg3);border:1px solid var(--border);border-radius:20px;padding:3px;gap:2px;}
.t-opt{width:30px;height:27px;border-radius:15px;display:flex;align-items:center;justify-content:center;font-size:.76rem;transition:.2s;color:var(--text2);cursor:pointer;}
.t-opt.on{background:var(--card);color:var(--text);box-shadow:var(--shadow2);}

/* lang switcher */
.lang-sw{display:flex;align-items:center;background:var(--bg3);border:1px solid var(--border);border-radius:20px;padding:3px;gap:2px;}
.lang-opt{width:36px;height:27px;border-radius:15px;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:800;transition:.2s;color:var(--text2);cursor:pointer;}
.lang-opt.on{background:var(--card);color:var(--text);box-shadow:var(--shadow2);}

.form-wrap{width:100%;max-width:360px;}
.fw-header{margin-bottom:32px;}
.fw-title{font-family:'Bebas Neue';font-size:2.2rem;letter-spacing:2px;line-height:1;}
.fw-sub{color:var(--text2);font-size:.82rem;font-weight:600;margin-top:6px;}

.fg{margin-bottom:20px;}
.fg label{display:block;font-size:.75rem;font-weight:800;letter-spacing:.8px;text-transform:uppercase;color:var(--text2);margin-bottom:8px;}
.fi{
  width:100%;background:var(--bg3);border:1.5px solid var(--border);
  border-radius:10px;padding:12px 14px;color:var(--text);
  font-size:.9rem;font-weight:500;transition:.2s;outline:none;
  font-family:'Plus Jakarta Sans',sans-serif;
}
.fi:focus{border-color:var(--p);box-shadow:0 0 0 4px var(--pl);}
.fi-icon{position:relative;}
.fi-icon i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--text2);font-size:.85rem;}
.fi-icon .fi{padding-left:38px;}

.err-box{
  background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);
  border-radius:10px;padding:12px 14px;margin-bottom:20px;
  display:flex;align-items:center;gap:10px;font-size:.82rem;font-weight:600;color:var(--r);
}

.btn-submit{
  width:100%;padding:13px;background:linear-gradient(135deg,var(--p),var(--pd));
  color:#fff;border:none;border-radius:10px;font-family:'Plus Jakarta Sans',sans-serif;
  font-weight:800;font-size:.92rem;cursor:pointer;transition:.2s;
  display:flex;align-items:center;justify-content:center;gap:8px;letter-spacing:.3px;
  box-shadow:0 6px 24px rgba(249,115,22,.35);margin-top:8px;
}
.btn-submit:hover{transform:translateY(-2px);box-shadow:0 10px 32px rgba(249,115,22,.45);}
.btn-submit:active{transform:none;}

.rp-footer{margin-top:48px;font-size:.72rem;font-weight:600;color:var(--text2);text-align:center;width:100%;}

/* RTL support */
[dir="rtl"] .fi-icon i{left:auto;right:14px;}
[dir="rtl"] .fi-icon .fi{padding-left:14px;padding-right:38px;}
[dir="rtl"] .err-box{flex-direction:row-reverse;}

@media(max-width:800px){.left-panel{display:none;}.right-panel{width:100%;border-left:none;min-width:unset;}}
</style>
</head>
<body>

<!-- LEFT PANEL -->
<div class="left-panel">
  <div class="left-topo"></div>
  <div class="lp-content">
    <div class="lp-logo">🐾</div>
    <div class="lp-title">PetAdopt</div>
    <div class="lp-sub" data-i18n="shelter_sys">Shelter Management System</div>
    <div class="lp-desc" data-i18n="lp_desc">Manage animal intakes, adoptions, medical records, and more — all in one place.</div>
    <div class="lp-stats">
      <div class="ls-item">
        <div class="ls-num">🐕</div>
        <div class="ls-lbl" data-i18n="dogs">Dogs</div>
      </div>
      <div class="ls-item">
        <div class="ls-num">🐈</div>
        <div class="ls-lbl" data-i18n="cats">Cats</div>
      </div>
      <div class="ls-item">
        <div class="ls-num">🏡</div>
        <div class="ls-lbl" data-i18n="adoptions">Adoptions</div>
      </div>
    </div>
  </div>
</div>

<!-- RIGHT PANEL -->
<div class="right-panel">
  <div class="rp-top">
    <div class="rp-brand">🐾 PETADOPT</div>
    <div class="rp-controls">
      <div class="lang-sw">
        <div class="lang-opt on" id="langEn" onclick="setLanguage('en')">EN</div>
        <div class="lang-opt" id="langKu" onclick="setLanguage('ku')">KU</div>
      </div>
      <div class="theme-sw">
        <div class="t-opt on" id="tDark" onclick="setTheme('dark')"><i class="fas fa-moon"></i></div>
        <div class="t-opt" id="tLight" onclick="setTheme('light')"><i class="fas fa-sun"></i></div>
      </div>
    </div>
  </div>

  <div class="form-wrap">
    <div class="fw-header">
      <div class="fw-title" data-i18n="welcome_back">Welcome Back</div>
      <div class="fw-sub" data-i18n="sign_in_sub">Sign in to your account to continue</div>
    </div>

    <?php if(!empty($error)): ?>
    <div class="err-box">
      <i class="fas fa-circle-exclamation"></i>
      <span data-i18n="error_credentials">Invalid username or password. Please try again.</span>
    </div>
    <?php endif; ?>

    <form action="login.php" method="post">
      <div class="fg">
        <label data-i18n="username_lbl">Username</label>
        <div class="fi-icon">
          <i class="fas fa-user"></i>
          <input type="text" name="username" class="fi" required placeholder="e.g. admin" autocomplete="username">
        </div>
      </div>
      <div class="fg">
        <label data-i18n="password_lbl">Password</label>
        <div class="fi-icon">
          <i class="fas fa-lock"></i>
          <input type="password" name="password" class="fi" required placeholder="••••••••" autocomplete="current-password">
        </div>
      </div>
      <button type="submit" class="btn-submit">
        <i class="fas fa-right-to-bracket"></i>
        <span data-i18n="sign_in_btn">Sign In</span>
      </button>
    </form>
  </div>

  <div class="rp-footer" data-i18n="footer_note">Pet Adoption Management System &mdash; Secure Access</div>
</div>

<script>
const tr = {
  en: {
    shelter_sys:"Shelter Management System",
    lp_desc:"Manage animal intakes, adoptions, medical records, and more — all in one place.",
    dogs:"Dogs", cats:"Cats", adoptions:"Adoptions",
    welcome_back:"Welcome Back", sign_in_sub:"Sign in to your account to continue",
    error_credentials:"Invalid username or password. Please try again.",
    username_lbl:"Username", password_lbl:"Password",
    sign_in_btn:"Sign In", footer_note:"Pet Adoption Management System — Secure Access"
  },
  ku: {
    shelter_sys:"سیستەمی بەڕێوەبردنی پەناگا",
    lp_desc:"بەڕێوەبردنی دروستکردنی ئاژەڵ، قبووڵکردن، تۆمارە پزیشکیەکان و زیاتر — هەمووی لە شوێنێکدا.",
    dogs:"سەگەکان", cats:"پشیلەکان", adoptions:"قبووڵکردنەکان",
    welcome_back:"بەخێربێیت", sign_in_sub:"چوونەژوورەوە بۆ ئەکاونتەکەت",
    error_credentials:"ناوی بەکارهێنەر یان وشەی نهێنی هەڵەیە. تکایە دووبارە هەوڵبدەرەوە.",
    username_lbl:"ناوی بەکارهێنەر", password_lbl:"وشەی نهێنی",
    sign_in_btn:"چوونەژوورەوە", footer_note:"سیستەمی بەڕێوەبردنی قبووڵکردنی ئاژەڵ — هاتنە ناو بە پارێز"
  }
};

let lang = localStorage.getItem('lang') || 'en';
let theme = localStorage.getItem('theme') || 'dark';

function T(k){ return (tr[lang]||{})[k] || tr.en[k] || k; }

function setLanguage(l) {
  lang = l; localStorage.setItem('lang',l);
  const isKu = l==='ku';
  document.documentElement.lang = l;
  document.documentElement.dir = isKu ? 'rtl' : 'ltr';
  document.getElementById('langEn').classList.toggle('on', l==='en');
  document.getElementById('langKu').classList.toggle('on', l==='ku');
  document.querySelectorAll('[data-i18n]').forEach(el=>{
    const k=el.getAttribute('data-i18n');
    if(T(k)) el.textContent=T(k);
  });
}

function setTheme(t) {
  theme = t; localStorage.setItem('theme',t);
  document.documentElement.setAttribute('data-theme',t);
  document.getElementById('tDark').classList.toggle('on', t==='dark');
  document.getElementById('tLight').classList.toggle('on', t==='light');
}

// Apply saved prefs on load
setTheme(theme);
setLanguage(lang);
</script>
</body>
</html>