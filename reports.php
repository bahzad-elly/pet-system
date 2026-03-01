<?php
// reports.php
session_start();
require_once 'db.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php"); exit;
}

$user_fullname = htmlspecialchars($_SESSION['fullname']);
$user_role     = htmlspecialchars(ucfirst($_SESSION['role']));
$user_initial  = strtoupper(substr($_SESSION['fullname'], 0, 1));

$status_counts = [];
try {
    $stmt = $pdo->query("SELECT status, COUNT(*) as total FROM animals GROUP BY status");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { $status_counts[$row['status']] = $row['total']; }
} catch (PDOException $e) {}
foreach (['Available','Adopted','Pending','Medical Care'] as $s) {
    if (!isset($status_counts[$s])) $status_counts[$s] = 0;
}

$recent_adoptions = [];
try {
    $sql = "SELECT a.adoptiondate, an.name as animal_name, an.species, ad.fname, ad.lname, u.username as staff_name
            FROM adoption a
            JOIN animals an ON a.animal_id=an.animal_id
            JOIN adopters ad ON a.adopter_id=ad.adopterId
            LEFT JOIN users u ON a.user_id=u.uid
            WHERE a.adoptiondate >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ORDER BY a.adoptiondate DESC";
    $recent_adoptions = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

$total_animals = 0; $total_adopters = 0; $total_adoptions = 0;
try {
    $total_animals   = $pdo->query("SELECT COUNT(*) FROM animals")->fetchColumn();
    $total_adopters  = $pdo->query("SELECT COUNT(*) FROM adopters")->fetchColumn();
    $total_adoptions = $pdo->query("SELECT COUNT(*) FROM adoption")->fetchColumn();
} catch (PDOException $e) {}

$active_page = 'reports';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🐾 PetAdopt — Reports</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<?php require_once 'includes/layout_css.php'; ?>
<style>
.stats-row{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;}
.sc{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:18px 20px;display:flex;align-items:center;gap:14px;transition:.25s;animation:fadeUp .5s ease both;position:relative;overflow:hidden;}
.sc:hover{transform:translateY(-3px);box-shadow:var(--shadow);}
.sc::after{content:'';position:absolute;right:-16px;top:-16px;width:68px;height:68px;border-radius:50%;opacity:.08;}
.sc.or::after{background:var(--p);}.sc.gr::after{background:var(--g);}.sc.bl::after{background:var(--b);}
.sic{width:46px;height:46px;min-width:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.05rem;}
.sic.or{background:var(--pl);color:var(--p);}.sic.gr{background:var(--gl);color:var(--g);}.sic.bl{background:var(--bl);color:var(--b);}
.sv h3{font-size:1.7rem;font-weight:900;line-height:1;}
.sv p{color:var(--text2);font-size:.7rem;font-weight:700;margin-top:3px;}
.status-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;}
.sc2{border-radius:12px;padding:16px;text-align:center;}
.sc2.gr{background:var(--gl);color:var(--g);}.sc2.bl{background:var(--bl);color:var(--b);}
.sc2.yw{background:var(--yl);color:var(--y);}.sc2.pu{background:var(--pul);color:var(--pu);}
.sc2 .num{font-size:1.8rem;font-weight:900;font-family:'Bebas Neue';letter-spacing:1px;}
.sc2 .lbl{font-size:.65rem;font-weight:800;letter-spacing:.5px;text-transform:uppercase;margin-top:2px;}
@media(max-width:900px){.stats-row{grid-template-columns:repeat(3,1fr);}.status-grid{grid-template-columns:1fr 1fr;}}
@media(max-width:600px){.stats-row{grid-template-columns:1fr;}}
</style>
</head>
<body>

<?php require_once 'includes/sidebar.php'; ?>

<div class="main">
  <?php
    $page_title_key     = 'reports_title';
    $page_title_default = 'Reports & Analytics';
    require_once 'includes/header.php';
  ?>

  <div class="content">
    <div class="stats-row">
      <div class="sc or" style="animation-delay:.05s"><div class="sic or"><i class="fas fa-paw"></i></div><div class="sv"><h3><?= $total_animals ?></h3><p data-i18n="total_animals">Total Animals</p></div></div>
      <div class="sc gr" style="animation-delay:.10s"><div class="sic gr"><i class="fas fa-heart"></i></div><div class="sv"><h3><?= $total_adopters ?></h3><p data-i18n="total_adopters">Total Adopters</p></div></div>
      <div class="sc bl" style="animation-delay:.15s"><div class="sic bl"><i class="fas fa-house-heart"></i></div><div class="sv"><h3><?= $total_adoptions ?></h3><p data-i18n="total_adoptions">All-Time Adoptions</p></div></div>
    </div>

    <div class="card" style="animation-delay:.2s">
      <div class="ch"><div class="ct"><i class="fas fa-chart-pie"></i><span data-i18n="status_breakdown">Animal Status Breakdown</span></div></div>
      <div class="status-grid">
        <div class="sc2 gr"><div class="num"><?= $status_counts['Available'] ?></div><div class="lbl" data-i18n="available">Available</div></div>
        <div class="sc2 yw"><div class="num"><?= $status_counts['Pending'] ?></div><div class="lbl" data-i18n="pending">Pending</div></div>
        <div class="sc2 pu"><div class="num"><?= $status_counts['Medical Care'] ?></div><div class="lbl" data-i18n="medical_care">Medical Care</div></div>
        <div class="sc2 bl"><div class="num"><?= $status_counts['Adopted'] ?></div><div class="lbl" data-i18n="adopted">Adopted</div></div>
      </div>
    </div>

    <div class="card" style="animation-delay:.25s">
      <div class="ch"><div class="ct"><i class="fas fa-house-heart"></i><span data-i18n="recent_adoptions">Recent Adoptions (Last 30 Days)</span></div>
        <button class="btn btn-gh btn-sm" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
      </div>
      <?php if (count($recent_adoptions) > 0): ?>
      <div style="overflow-x:auto">
        <table>
          <thead><tr>
            <th data-i18n="date">Date</th>
            <th data-i18n="animal">Animal</th>
            <th data-i18n="adopter">Adopter</th>
            <th data-i18n="processed_by">Processed By</th>
          </tr></thead>
          <tbody>
            <?php foreach ($recent_adoptions as $ad): ?>
            <tr>
              <td style="color:var(--text2)"><?= date('M d, Y', strtotime($ad['adoptiondate'])) ?></td>
              <td><strong><?= htmlspecialchars($ad['animal_name']) ?></strong> <span style="color:var(--text2);font-size:.78rem">(<?= htmlspecialchars($ad['species']) ?>)</span></td>
              <td><?= htmlspecialchars($ad['fname'].' '.$ad['lname']) ?></td>
              <td style="color:var(--text2)"><?= htmlspecialchars($ad['staff_name']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
        <div class="empty"><i class="fas fa-house-heart"></i><p data-i18n="no_recent_adoptions">No adoptions recorded in the last 30 days.</p></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
const trPage = {
  en:{reports_title:"Reports & Analytics",total_animals:"Total Animals",total_adopters:"Total Adopters",total_adoptions:"All-Time Adoptions",status_breakdown:"Animal Status Breakdown",available:"Available",pending:"Pending",medical_care:"Medical Care",adopted:"Adopted",recent_adoptions:"Recent Adoptions (Last 30 Days)",date:"Date",animal:"Animal",adopter:"Adopter",processed_by:"Processed By",no_recent_adoptions:"No adoptions recorded in the last 30 days."},
  ku:{reports_title:"ڕاپۆرت و شیکاری",total_animals:"کۆی ئاژەڵەکان",total_adopters:"کۆی وەرگرەکان",total_adoptions:"کۆی قبووڵکردنەکان",status_breakdown:"دانانی دەربارەی ئاژەڵەکان",available:"بەردەستە",pending:"چاوەڕوان",medical_care:"چارەسەری پزیشکی",adopted:"قبووڵکرا",recent_adoptions:"قبووڵکردنەکانی دواین (٣٠ ڕۆژ)",date:"بەروار",animal:"ئاژەڵ",adopter:"وەرگر",processed_by:"جێبەجێکراوە لەلایەن",no_recent_adoptions:"هیچ قبووڵکردنێک تۆمارنەکراوە."}
};
</script>
<?php require_once 'includes/layout_js.php'; ?>
</body>
</html>