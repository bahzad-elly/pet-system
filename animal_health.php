<?php
// animal_health.php
session_start();
require_once 'db.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php"); exit;
}

$user_fullname = htmlspecialchars($_SESSION['fullname']);
$user_role     = htmlspecialchars(ucfirst($_SESSION['role']));
$user_initial  = strtoupper(substr($_SESSION['fullname'], 0, 1));
$message   = '';
$animal_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($animal_id === 0) die("Invalid Animal ID.");

$stmt = $pdo->prepare("SELECT name, species, breed FROM animals WHERE animal_id = :id");
$stmt->execute([':id' => $animal_id]);
$animal = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$animal) die("Animal not found.");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action_type = $_POST['form_action'];
    if ($action_type == 'add_medical') {
        $visit_type = trim($_POST['visit_type']); $diagnoses = trim($_POST['diagnoses']);
        $treatment = trim($_POST['treatment']); $treatedBy = trim($_POST['treatedBy']);
        try {
            $pdo->prepare("INSERT INTO medical_record (animal_id, visit_type, diagnoses, treatment, treatedBy) VALUES (:a,:b,:c,:d,:e)")->execute([':a'=>$animal_id,':b'=>$visit_type,':c'=>$diagnoses,':d'=>$treatment,':e'=>$treatedBy]);
            $message = 'med_ok';
        } catch (PDOException $e) { $message = 'error'; }
    } elseif ($action_type == 'add_vaccine') {
        $vtype_id = intval($_POST['vtype_id']); $date = $_POST['date'];
        $nextDate = !empty($_POST['nextDate']) ? $_POST['nextDate'] : null;
        try {
            $pdo->prepare("INSERT INTO animal_vaccination (animal_id, vtype_id, date, nextDate, userId) VALUES (:a,:b,:c,:d,:e)")->execute([':a'=>$animal_id,':b'=>$vtype_id,':c'=>$date,':d'=>$nextDate,':e'=>$_SESSION['uid']]);
            $message = 'vac_ok';
        } catch (PDOException $e) { $message = 'error'; }
    }
}

$medical_records = $pdo->prepare("SELECT * FROM medical_record WHERE animal_id = :id ORDER BY created_at DESC");
$medical_records->execute([':id' => $animal_id]);
$medical_records = $medical_records->fetchAll(PDO::FETCH_ASSOC);

$stmt_vac = $pdo->prepare("SELECT av.*, vt.vaccine_name FROM animal_vaccination av JOIN vaccination_types vt ON av.vtype_id=vt.vtype_id WHERE av.animal_id=:id ORDER BY av.date DESC");
$stmt_vac->execute([':id' => $animal_id]);
$vaccinations = $stmt_vac->fetchAll(PDO::FETCH_ASSOC);

$vaccine_types = $pdo->query("SELECT vtype_id, vaccine_name FROM vaccination_types ORDER BY vaccine_name ASC")->fetchAll(PDO::FETCH_ASSOC);

$active_page = 'view_animals';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🐾 Health Profile — <?= htmlspecialchars($animal['name']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<?php require_once 'includes/layout_css.php'; ?>
<style>
.content{flex:1;overflow-y:auto;padding:24px;display:flex;flex-direction:column;gap:18px;}
.animal-header{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:18px 22px;animation:fadeUp .4s ease both;display:flex;align-items:center;gap:16px;}
.ah-icon{width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,#8b5cf6,var(--b));display:flex;align-items:center;justify-content:center;font-size:1.6rem;flex-shrink:0;}
.ah-name{font-family:'Bebas Neue';font-size:1.4rem;letter-spacing:1px;}
.ah-sub{font-size:.8rem;color:var(--text2);font-weight:600;margin-top:2px;}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:18px;}
.ct i{color:var(--g);}
.sub-form{background:var(--bg3);border-radius:10px;padding:16px;margin-bottom:18px;}
.sub-title{font-size:.78rem;font-weight:800;color:var(--text2);letter-spacing:.5px;text-transform:uppercase;margin-bottom:12px;display:flex;align-items:center;gap:6px;}
.sub-title i{color:var(--p);}
textarea.fi{resize:vertical;min-height:60px;}
@media(max-width:900px){.row2{grid-template-columns:1fr;}}
</style>
</head>
<body>

<?php require_once 'includes/sidebar.php'; ?>

<div class="main">
  <?php
    $page_title_key     = 'health_profile';
    $page_title_default = 'Health Profile';
    require_once 'includes/header.php';
  ?>

  <div class="content">
    <div class="animal-header">
      <div class="ah-icon">🏥</div>
      <div>
        <div class="ah-name"><?= htmlspecialchars($animal['name']) ?></div>
        <div class="ah-sub"><?= htmlspecialchars($animal['species']) ?> · <?= htmlspecialchars($animal['breed'] ?: '—') ?></div>
      </div>
      <div style="margin-left:auto">
        <a href="view_animals.php" class="btn btn-gh"><i class="fas fa-arrow-left"></i><span data-i18n="back_animals">Back to Animals</span></a>
      </div>
    </div>

    <?php if ($message==='med_ok'||$message==='vac_ok'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i><span data-i18n="record_ok">Record added successfully!</span></div>
    <?php elseif ($message==='error'): ?><div class="alert alert-error"><i class="fas fa-circle-exclamation"></i><span data-i18n="error_msg">An error occurred.</span></div>
    <?php endif; ?>

    <div class="row2">
      <!-- Medical Records -->
      <div class="card">
        <div class="ch"><div class="ct"><i class="fas fa-notes-medical"></i><span data-i18n="medical_records">Medical Records</span></div></div>
        <div class="sub-form">
          <div class="sub-title"><i class="fas fa-plus"></i><span data-i18n="new_med_visit">+ New Medical Visit</span></div>
          <form action="animal_health.php?id=<?= $animal_id ?>" method="POST" data-form="medical">
            <input type="hidden" name="form_action" value="add_medical">
            <div class="fg"><label data-i18n="visit_type">Visit Type</label><input type="text" name="visit_type" class="fi" placeholder="e.g., Checkup, Surgery" required></div>
            <div class="fg"><label data-i18n="diagnoses">Diagnoses</label><textarea name="diagnoses" class="fi" rows="2"></textarea></div>
            <div class="fg"><label data-i18n="treatment">Treatment</label><textarea name="treatment" class="fi" rows="2" required></textarea></div>
            <div class="fg"><label data-i18n="vet_name">Vet Name</label><input type="text" name="treatedBy" class="fi"></div>
            <button type="submit" class="btn btn-g"><i class="fas fa-floppy-disk"></i><span data-i18n="save_record">Save Record</span></button>
          </form>
        </div>
        <table>
          <thead><tr><th data-i18n="date">Date</th><th data-i18n="type_treatment">Type / Treatment</th><th data-i18n="vet">Vet</th></tr></thead>
          <tbody id="medBody">
            <?php if (count($medical_records)>0): foreach ($medical_records as $med): ?>
            <tr>
              <td style="color:var(--text2);white-space:nowrap"><?= date('Y-m-d', strtotime($med['created_at'])) ?></td>
              <td><strong><?= htmlspecialchars($med['visit_type']) ?></strong><br><span style="color:var(--text2);font-size:.75rem">Dx: <?= htmlspecialchars($med['diagnoses']) ?></span><br><span style="color:var(--text2);font-size:.75rem">Tx: <?= htmlspecialchars($med['treatment']) ?></span></td>
              <td><?= htmlspecialchars($med['treatedBy']) ?></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="3"><div class="empty" data-i18n="no_med_records">No medical records found.</div></td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Vaccination Records -->
      <div class="card">
        <div class="ch"><div class="ct"><i class="fas fa-syringe" style="color:var(--y)"></i><span data-i18n="vaccination_history">Vaccination History</span></div></div>
        <div class="sub-form">
          <div class="sub-title"><i class="fas fa-plus"></i><span data-i18n="new_vaccination">+ New Vaccination</span></div>
          <form action="animal_health.php?id=<?= $animal_id ?>" method="POST" data-form="vaccine">
            <input type="hidden" name="form_action" value="add_vaccine">
            <div class="fg">
              <label data-i18n="vaccine_type">Vaccine Type</label>
              <select name="vtype_id" class="fi" required>
                <option value="" data-i18n="select_vaccine">-- Select Vaccine --</option>
                <?php foreach ($vaccine_types as $vt): ?><option value="<?= $vt['vtype_id'] ?>"><?= htmlspecialchars($vt['vaccine_name']) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="fg"><label data-i18n="date_given">Date Administered</label><input type="date" name="date" class="fi" value="<?= date('Y-m-d') ?>" required></div>
            <div class="fg"><label data-i18n="next_due">Next Due Date (Optional)</label><input type="date" name="nextDate" class="fi"></div>
            <button type="submit" class="btn btn-b"><i class="fas fa-floppy-disk"></i><span data-i18n="save_vacc">Save Vaccination</span></button>
          </form>
        </div>
        <table>
          <thead><tr><th data-i18n="vaccine">Vaccine</th><th data-i18n="date_given">Date Given</th><th data-i18n="next_due">Next Due</th></tr></thead>
          <tbody id="vacBody">
            <?php if (count($vaccinations)>0): foreach ($vaccinations as $vac): ?>
            <tr>
              <td><strong><?= htmlspecialchars($vac['vaccine_name']) ?></strong></td>
              <td><?= htmlspecialchars($vac['date']) ?></td>
              <td style="color:var(--r);font-weight:700"><?= $vac['nextDate'] ? htmlspecialchars($vac['nextDate']) : '<span style="color:var(--text2)">N/A</span>' ?></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="3"><div class="empty" data-i18n="no_vaccinations">No vaccinations recorded.</div></td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
const trPage = {
  en:{health_profile:"Health Profile",back_animals:"Back to Animals",record_ok:"Record added successfully!",error_msg:"An error occurred.",medical_records:"Medical Records",new_med_visit:"+ New Medical Visit",visit_type:"Visit Type",diagnoses:"Diagnoses",treatment:"Treatment",vet_name:"Vet Name",save_record:"Save Record",date:"Date",type_treatment:"Type / Treatment",vet:"Vet",no_med_records:"No medical records found.",vaccination_history:"Vaccination History",new_vaccination:"+ New Vaccination",vaccine_type:"Vaccine Type",select_vaccine:"-- Select Vaccine --",date_given:"Date Administered",next_due:"Next Due Date",save_vacc:"Save Vaccination",vaccine:"Vaccine",no_vaccinations:"No vaccinations recorded."},
  ku:{health_profile:"پرۆفایلی تەندروستی",back_animals:"گەڕانەوە بۆ ئاژەڵەکان",record_ok:"تۆمار بە سەرکەوتوویی زیادکرا!",error_msg:"هەڵەیەک ڕوویدا.",medical_records:"تۆمارە پزیشکیەکان",new_med_visit:"+ سەردانی پزیشکی نوێ",visit_type:"جۆری سەردان",diagnoses:"تەشخیس",treatment:"چارەسەر",vet_name:"ناوی پزیشک",save_record:"پاراستنی تۆمار",date:"بەروار",type_treatment:"جۆر / چارەسەر",vet:"پزیشک",no_med_records:"هیچ تۆماری پزیشکی نەدۆزرایەوە.",vaccination_history:"مێژووی دەرمانکردن",new_vaccination:"+ دەرمانکردنی نوێ",vaccine_type:"جۆری دەرمان",select_vaccine:"-- جۆری دەرمان هەڵبژێرە --",date_given:"بەرواری دراو",next_due:"بەرواری داهاتوو",save_vacc:"پاراستنی دەرمانکردن",vaccine:"دەرمان",no_vaccinations:"هیچ دەرمانکردنێک تۆمارنەکراوە."}
};
</script>
<?php require_once 'includes/layout_js.php'; ?>
<script src="includes/realtime.js"></script>
<script>
/* ── AJAX Health Forms ───────────────────────────────────────
   Forms submit to api/health.php via AJAX; records update without reload.
──────────────────────────────────────────────── */
const ANIMAL_ID = <?= $animal_id ?>;

async function refreshHealth() {
  try {
    const res  = await fetch(`api/health.php?animal_id=${ANIMAL_ID}`);
    if (!res.ok) return;
    const data = await res.json();
    const medBody = document.getElementById('medBody');
    const vacBody = document.getElementById('vacBody');
    if (medBody) medBody.innerHTML = data.med_html;
    if (vacBody) vacBody.innerHTML = data.vac_html;
  } catch(e) {}
}

// Medical form AJAX
const medForm = document.querySelector('form[data-form="medical"]');
if (medForm) {
  medForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = medForm.querySelector('[type=submit]');
    btn.disabled = true; btn.style.opacity = '.6';
    try {
      const res  = await fetch('api/health_save.php', { method:'POST', body: new FormData(medForm) });
      const data = await res.json();
      if (data.error) throw new Error(data.error);
      medForm.reset();
      showToast('Medical record saved!', 'success');
      refreshHealth();
    } catch(err) {
      showToast(err.message || 'Failed to save', 'error');
    }
    btn.disabled = false; btn.style.opacity = '';
  });
}

// Vaccine form AJAX
const vacForm = document.querySelector('form[data-form="vaccine"]');
if (vacForm) {
  vacForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = vacForm.querySelector('[type=submit]');
    btn.disabled = true; btn.style.opacity = '.6';
    try {
      const res  = await fetch('api/health_save.php', { method:'POST', body: new FormData(vacForm) });
      const data = await res.json();
      if (data.error) throw new Error(data.error);
      vacForm.reset();
      // Re-set today's date
      const dateInput = vacForm.querySelector('[name=date]');
      if (dateInput) dateInput.value = new Date().toISOString().slice(0,10);
      showToast('Vaccination saved!', 'success');
      refreshHealth();
    } catch(err) {
      showToast(err.message || 'Failed to save', 'error');
    }
    btn.disabled = false; btn.style.opacity = '';
  });
}

// Initial load + 15s auto-refresh
refreshHealth();
startAutoRefresh(refreshHealth, 15000);
</script>
</body>
</html>