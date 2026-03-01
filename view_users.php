<?php
// view_users.php — Real-time AJAX version (admin only)
session_start();
require_once 'db.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header("location: dashboard.php"); exit;
}

$user_fullname = htmlspecialchars($_SESSION['fullname']);
$user_role     = htmlspecialchars(ucfirst($_SESSION['role']));
$user_initial  = strtoupper(substr($_SESSION['fullname'], 0, 1));
$active_page   = 'view_users';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🐾 PetAdopt — Users</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<?php require_once 'includes/layout_css.php'; ?>
<style>
.content{display:block;}
.fi-inline{background:var(--bg3);border:1.5px solid var(--border);border-radius:8px;padding:8px 12px;color:var(--text);font-size:.82rem;font-weight:500;transition:.2s;outline:none;font-family:'Plus Jakarta Sans',sans-serif;}
.fi-inline:focus{border-color:var(--p);box-shadow:0 0 0 3px var(--pl);}
.badge-admin{background:var(--rl);color:var(--r);}
.badge-staff{background:var(--bl);color:var(--b);}
.user-av{width:32px;height:32px;border-radius:9px;background:linear-gradient(135deg,var(--p),var(--y));display:flex;align-items:center;justify-content:center;font-weight:900;font-size:.8rem;color:#000;flex-shrink:0;}
</style>
</head>
<body>

<?php require_once 'includes/sidebar.php'; ?>

<div class="main">
  <?php
    $page_title_key     = 'system_users';
    $page_title_default = 'System Users';
    require_once 'includes/header.php';
  ?>

  <div class="content">
    <div class="card">
      <div class="ch">
        <div style="display:flex;align-items:center;gap:10px;">
          <div class="ct"><i class="fas fa-users-gear"></i><span data-i18n="system_users" id="usersTitle">System Users</span></div>
          <span class="rt-count" id="usersCount">Loading…</span>
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
          <input type="text" id="userSearch" class="fi-inline" style="width:220px;" placeholder="🔍 Search name, email, role…" autocomplete="off">
          <button id="userClear" class="btn btn-gh btn-sm" style="display:none;color:var(--r)"><i class="fas fa-xmark"></i></button>
          <a href="register_user.php" class="btn btn-p btn-sm"><i class="fas fa-plus"></i><span data-i18n="reg_user">Register User</span></a>
        </div>
      </div>
      <div style="overflow-x:auto">
        <table>
          <thead><tr>
            <th data-i18n="user_th">User</th>
            <th data-i18n="username_th">Username</th>
            <th data-i18n="email_th">Email</th>
            <th data-i18n="role_th">Role</th>
            <th data-i18n="registered_th">Registered</th>
            <th data-i18n="actions_th">Actions</th>
          </tr></thead>
          <tbody id="usersBody">
            <tr><td colspan="6"><div class="empty"><i class="fas fa-spinner fa-spin"></i><p>Loading users…</p></div></td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
const trPage = {
  en:{system_users:"System Users",reg_user:"Register User",user_th:"User",username_th:"Username",email_th:"Email",role_th:"Role",registered_th:"Registered",actions_th:"Actions"},
  ku:{system_users:"بەکارهێنەرانی سیستەم",reg_user:"تۆمارکردنی بەکارهێنەری نوێ",user_th:"بەکارهێنەر",username_th:"ناوی بەکارهێنەر",email_th:"ئیمەیڵ",role_th:"ڕۆڵ",registered_th:"تۆمارکراوە",actions_th:"کردارەکان"}
};
</script>
<?php require_once 'includes/layout_js.php'; ?>
<script src="includes/realtime.js"></script>
<script>
addLiveDot(document.getElementById('usersTitle'));

const doUserSearch = liveSearch({
  input:     '#userSearch',
  endpoint:  'api/users.php',
  tableBody: '#usersBody',
  countEl:   '#usersCount',
});

const userInp   = document.getElementById('userSearch');
const userClear = document.getElementById('userClear');
userInp.addEventListener('input', () => {
  userClear.style.display = userInp.value ? 'inline-flex' : 'none';
});
userClear.addEventListener('click', () => {
  userInp.value = ''; userClear.style.display = 'none'; doUserSearch();
});

startAutoRefresh(doUserSearch, 20000);
</script>
</body>
</html>