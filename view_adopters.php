<?php
// view_adopters.php — Real-time AJAX version
session_start();
require_once 'db.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php"); exit;
}

$user_fullname = htmlspecialchars($_SESSION['fullname']);
$user_role     = htmlspecialchars(ucfirst($_SESSION['role']));
$user_initial  = strtoupper(substr($_SESSION['fullname'], 0, 1));
$active_page   = 'view_adopters';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🐾 PetAdopt — Adopters</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<?php require_once 'includes/layout_css.php'; ?>
<style>
.content{display:block;}
.fi-inline{background:var(--bg3);border:1.5px solid var(--border);border-radius:8px;padding:8px 12px;color:var(--text);font-size:.82rem;font-weight:500;transition:.2s;outline:none;font-family:'Plus Jakarta Sans',sans-serif;}
.fi-inline:focus{border-color:var(--p);box-shadow:0 0 0 3px var(--pl);}
</style>
</head>
<body>

<?php require_once 'includes/sidebar.php'; ?>

<div class="main">
  <?php
    $page_title_key     = 'manage_adopters';
    $page_title_default = 'Manage Adopters';
    require_once 'includes/header.php';
  ?>

  <div class="content">
    <div class="card">
      <div class="ch">
        <div style="display:flex;align-items:center;gap:10px;">
          <div class="ct"><i class="fas fa-heart" style="color:var(--b)"></i><span data-i18n="adopter_directory" id="adopterTitle">Adopter Directory</span></div>
          <span class="rt-count" id="adopterCount">Loading…</span>
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
          <input type="text" id="adopterSearch" class="fi-inline" style="width:240px;" placeholder="🔍 Search name, phone, address…" autocomplete="off">
          <button id="adopterClear" class="btn btn-gh btn-sm" style="display:none;color:var(--r)"><i class="fas fa-xmark"></i> <span>Clear</span></button>
          <a href="register_adopter.php" class="btn btn-p btn-sm"><i class="fas fa-plus"></i> <span data-i18n="add_adopter">Add Adopter</span></a>
        </div>
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
          <tbody id="adoptersBody">
            <tr><td colspan="6"><div class="empty"><i class="fas fa-spinner fa-spin"></i><p>Loading adopters…</p></div></td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
const trPage = {
  en:{manage_adopters:"Manage Adopters",adopter_directory:"Adopter Directory",name:"Name",phone:"Phone",address:"Address",preference:"Preference",joined:"Joined",actions:"Actions",add_adopter:"Add Adopter"},
  ku:{manage_adopters:"بەڕێوەبردنی وەرگرەکان",adopter_directory:"لیستی وەرگرەکان",name:"ناو",phone:"تەلەفۆن",address:"ناونیشان",preference:"ئارەزوو",joined:"بەشداربووە",actions:"کردارەکان",add_adopter:"زیادکردنی وەرگر"}
};
</script>
<?php require_once 'includes/layout_js.php'; ?>
<script src="includes/realtime.js"></script>
<script>
addLiveDot(document.getElementById('adopterTitle'));

const doAdopterSearch = liveSearch({
  input:     '#adopterSearch',
  endpoint:  'api/adopters.php',
  tableBody: '#adoptersBody',
  countEl:   '#adopterCount',
});

const adopterInp   = document.getElementById('adopterSearch');
const adopterClear = document.getElementById('adopterClear');
adopterInp.addEventListener('input', () => {
  adopterClear.style.display = adopterInp.value ? 'inline-flex' : 'none';
});
adopterClear.addEventListener('click', () => {
  adopterInp.value = '';
  adopterClear.style.display = 'none';
  doAdopterSearch();
});

startAutoRefresh(doAdopterSearch, 20000);
</script>
</body>
</html>