<?php
// view_animals.php — Real-time AJAX version
session_start();
require_once 'db.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php"); exit;
}

$user_fullname = htmlspecialchars($_SESSION['fullname']);
$user_role     = htmlspecialchars(ucfirst($_SESSION['role']));
$user_initial  = strtoupper(substr($_SESSION['fullname'], 0, 1));
$active_page   = 'view_animals';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🐾 PetAdopt — Animals</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<?php require_once 'includes/layout_css.php'; ?>
<style>
.content{display:block;}
.filter-bar{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:18px;}
.fi-inline{background:var(--bg3);border:1.5px solid var(--border);border-radius:8px;padding:8px 12px;color:var(--text);font-size:.82rem;font-weight:500;transition:.2s;outline:none;font-family:'Plus Jakarta Sans',sans-serif;}
.fi-inline:focus{border-color:var(--p);box-shadow:0 0 0 3px var(--pl);}
select.fi-inline{cursor:pointer;}
.animal-photo{width:38px;height:38px;object-fit:cover;border-radius:9px;border:1px solid var(--border);}
.no-photo{width:38px;height:38px;border-radius:9px;background:var(--bg3);display:flex;align-items:center;justify-content:center;font-size:1.1rem;}
.live-header{display:flex;align-items:center;gap:10px;}
/* Skeleton shimmer for loading */
@keyframes shimmer{0%{background-position:-400px 0}100%{background-position:400px 0}}
.shimmer{background:linear-gradient(90deg,var(--bg3) 25%,var(--bg4,#242424) 50%,var(--bg3) 75%);background-size:800px 100%;animation:shimmer 1.4s infinite;}
</style>
</head>
<body>

<?php require_once 'includes/sidebar.php'; ?>

<div class="main">
  <?php
    $page_title_key     = 'animal_dir';
    $page_title_default = 'Animal Directory';
    require_once 'includes/header.php';
  ?>

  <div class="content">
    <div class="card">
      <div class="ch">
        <div class="live-header">
          <div class="ct"><i class="fas fa-paw"></i><span data-i18n="animal_dir" id="animalDirTitle">Animal Directory</span></div>
          <span class="rt-count" id="animalCount">Loading…</span>
        </div>
        <a href="add_animal.php" class="btn btn-p"><i class="fas fa-plus"></i><span data-i18n="add_animal">Add Animal</span></a>
      </div>

      <div class="filter-bar">
        <input type="text" id="animalSearch" class="fi-inline" placeholder="🔍 Search name, breed, species…" autocomplete="off" style="min-width:220px;">
        <select id="animalStatus" name="status" class="fi-inline">
          <option value="" data-i18n="all_statuses">All Statuses</option>
          <option value="Available">Available</option>
          <option value="Pending">Pending</option>
          <option value="Medical Care">Medical Care</option>
          <option value="Adopted">Adopted</option>
        </select>
        <button id="clearFilters" class="btn btn-gh" style="display:none;color:var(--r)"><i class="fas fa-xmark"></i> <span data-i18n="clear">Clear</span></button>
      </div>

      <div class="tw">
        <table>
          <thead>
            <tr>
              <th data-i18n="photo">Photo</th>
              <th data-i18n="name">Name</th>
              <th data-i18n="species_breed">Species / Breed</th>
              <th data-i18n="age_gender">Age / Gender</th>
              <th data-i18n="status">Status</th>
              <th data-i18n="actions">Actions</th>
            </tr>
          </thead>
          <tbody id="animalsBody">
            <tr><td colspan="6"><div class="empty"><i class="fas fa-spinner fa-spin"></i><p>Loading animals…</p></div></td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
const trPage = {
  en:{animal_dir:"Animal Directory",photo:"Photo",name:"Name",species_breed:"Species / Breed",age_gender:"Age / Gender",status:"Status",actions:"Actions",clear:"Clear",all_statuses:"All Statuses",add_animal:"Add Animal"},
  ku:{animal_dir:"لیستی ئاژەڵەکان",photo:"وێنە",name:"ناو",species_breed:"جۆر / نەژاد",age_gender:"تەمەن / ڕەگەز",status:"دەربارە",actions:"کردارەکان",clear:"پاك",all_statuses:"هەموو دەربارەکان",add_animal:"زیادکردنی ئاژەڵ"}
};
</script>
<?php require_once 'includes/layout_js.php'; ?>
<script src="includes/realtime.js"></script>
<script>
// ── Live search setup ──────────────────────────────
addLiveDot(document.getElementById('animalDirTitle'));

const doSearch = liveSearch({
  input:      '#animalSearch',
  filters:    ['#animalStatus'],
  endpoint:   'api/animals.php',
  tableBody:  '#animalsBody',
  countEl:    '#animalCount',
});

// ── Clear button logic ─────────────────────────────
const searchInput  = document.getElementById('animalSearch');
const statusSelect = document.getElementById('animalStatus');
const clearBtn     = document.getElementById('clearFilters');

function updateClearBtn() {
  clearBtn.style.display = (searchInput.value || statusSelect.value) ? 'inline-flex' : 'none';
}
searchInput.addEventListener('input', updateClearBtn);
statusSelect.addEventListener('change', updateClearBtn);
clearBtn.addEventListener('click', () => {
  searchInput.value = '';
  statusSelect.value = '';
  clearBtn.style.display = 'none';
  doSearch();
});

// ── Auto-refresh every 20s ─────────────────────────
startAutoRefresh(doSearch, 20000);
</script>
</body>
</html>