<?php
// record_adoption.php
session_start();
require_once 'db.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php"); exit;
}

$user_fullname = htmlspecialchars($_SESSION['fullname']);
$user_role     = htmlspecialchars(ucfirst($_SESSION['role']));
$user_initial  = strtoupper(substr($_SESSION['fullname'], 0, 1));
$message = '';
$pre_selected_adopter = isset($_GET['adopter_id']) ? intval($_GET['adopter_id']) : 0;

$adopters          = $pdo->query("SELECT adopterId, fname, lname FROM adopters ORDER BY fname ASC")->fetchAll(PDO::FETCH_ASSOC);
$available_animals = $pdo->query("SELECT animal_id, name, species, breed FROM animals WHERE status = 'Available' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $adopter_id   = intval($_POST['adopter_id']);
    $animal_id    = intval($_POST['animal_id']);
    $adoptiondate = $_POST['adoptiondate'];
    $user_id      = $_SESSION['uid'];

    if (empty($adopter_id) || empty($animal_id) || empty($adoptiondate)) {
        $message = 'error_fields';
    } else {
        try {
            $pdo->beginTransaction();
            $pdo->prepare("INSERT INTO adoption (adopter_id, animal_id, adoptiondate, user_id) VALUES (:a,:b,:c,:d)")->execute([':a'=>$adopter_id,':b'=>$animal_id,':c'=>$adoptiondate,':d'=>$user_id]);
            $new_id = $pdo->lastInsertId();
            $pdo->prepare("UPDATE animals SET status='Adopted' WHERE animal_id=:a")->execute([':a'=>$animal_id]);
            $pdo->prepare("INSERT INTO user_activity_log (uid, actiontype, targettable, targetid, details) VALUES (:u,'Create','adoption',:t,'Processed adoption for animal ID $animal_id')")->execute([':u'=>$user_id,':t'=>$new_id]);
            $pdo->commit();
            $message = 'success';
            $available_animals = $pdo->query("SELECT animal_id, name, species, breed FROM animals WHERE status = 'Available' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $pdo->rollBack();
            $message = ($e->getCode() == 23000) ? 'error_already' : 'error_db';
        }
    }
}

$active_page = 'view_adopters';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🐾 PetAdopt — Record Adoption</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<?php require_once 'includes/layout_css.php'; ?>
<style>
.content{display:block;}
.card{max-width:640px;}
.sep{border-top:1px solid var(--border);margin:20px 0;}
.ct i{color:var(--g);}
.info-box{background:var(--bg3);border-radius:10px;padding:14px 16px;margin-bottom:18px;font-size:.82rem;color:var(--text2);display:flex;align-items:center;gap:10px;}
.info-box i{color:var(--b);}
</style>
</head>
<body>

<?php require_once 'includes/sidebar.php'; ?>

<div class="main">
  <?php
    $page_title_key     = 'record_adopt';
    $page_title_default = 'Record Adoption';
    require_once 'includes/header.php';
  ?>

  <div class="content">
    <div class="card">
      <div class="ch">
        <div class="ct"><i class="fas fa-house-heart"></i><span data-i18n="adoption_tx">Adoption Transaction</span></div>
        <a href="view_adopters.php" class="btn btn-gh"><i class="fas fa-arrow-left"></i><span data-i18n="back_adopters">Back to Adopters</span></a>
      </div>

      <?php if ($message==='success'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i><span data-i18n="success_msg">Adoption recorded successfully! Animal status updated to Adopted.</span></div>
      <?php elseif ($message==='error_already'): ?><div class="alert alert-error"><i class="fas fa-circle-exclamation"></i><span data-i18n="error_already">This animal has already been adopted!</span></div>
      <?php elseif (!empty($message)): ?><div class="alert alert-error"><i class="fas fa-circle-exclamation"></i><span data-i18n="error_msg2">Please fill in all required fields correctly.</span></div>
      <?php endif; ?>

      <div class="info-box"><i class="fas fa-circle-info"></i><span data-i18n="adopt_info">Only animals with "Available" status can be adopted. The animal's status will automatically update to "Adopted" after confirmation.</span></div>

      <form action="record_adoption.php" method="POST">
        <div class="fg">
          <label data-i18n="select_adopter">Select Adopter</label>
          <select name="adopter_id" class="fi" required>
            <option value="" data-i18n="choose_adopter">-- Choose Adopter --</option>
            <?php foreach ($adopters as $ad): ?>
            <option value="<?= $ad['adopterId'] ?>" <?= $pre_selected_adopter==$ad['adopterId']?'selected':'' ?>><?= htmlspecialchars($ad['fname'].' '.$ad['lname']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="fg">
          <label data-i18n="select_animal">Select Animal (Available Only)</label>
          <select name="animal_id" class="fi" required>
            <option value="" data-i18n="choose_animal">-- Choose Animal --</option>
            <?php foreach ($available_animals as $an): ?>
            <option value="<?= $an['animal_id'] ?>"><?= htmlspecialchars($an['name'].' ('.$an['species'].' - '.$an['breed'].')') ?></option>
            <?php endforeach; ?>
            <?php if (empty($available_animals)): ?><option value="" disabled data-i18n="no_avail">No available animals at the moment.</option><?php endif; ?>
          </select>
        </div>
        <div class="fg">
          <label data-i18n="adopt_date">Date of Adoption</label>
          <input type="date" name="adoptiondate" class="fi" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="sep"></div>
        <button type="submit" class="btn btn-g"><i class="fas fa-house-heart"></i><span data-i18n="confirm_btn">Confirm Adoption</span></button>
      </form>
    </div>
  </div>
</div>

<script>
const trPage = {
  en:{record_adopt:"Record Adoption",adoption_tx:"Adoption Transaction",back_adopters:"Back to Adopters",success_msg:"Adoption recorded successfully! Animal status updated to Adopted.",error_already:"This animal has already been adopted!",error_msg2:"Please fill in all required fields correctly.",adopt_info:'Only animals with "Available" status can be adopted.',select_adopter:"Select Adopter",choose_adopter:"-- Choose Adopter --",select_animal:"Select Animal (Available Only)",choose_animal:"-- Choose Animal --",no_avail:"No available animals at the moment.",adopt_date:"Date of Adoption",confirm_btn:"Confirm Adoption"},
  ku:{record_adopt:"تۆمارکردنی قبووڵکردن",adoption_tx:"ئامالی قبووڵکردن",back_adopters:"گەڕانەوە بۆ وەرگرەکان",success_msg:"قبووڵکردن بە سەرکەوتوویی تۆمارکرا!",error_already:"ئەم ئاژەڵە پێشتر قبووڵکراوە!",error_msg2:"تکایە هەموو خانەکانی پێویست دروست پڕبکەرەوە.",adopt_info:"تەنها ئاژەڵانی دەربارەی «بەردەست» دەتوانرێت قبووڵبکرێن.",select_adopter:"وەرگر هەڵبژێرە",choose_adopter:"-- وەرگر هەڵبژێرە --",select_animal:"ئاژەڵ هەڵبژێرە (تەنها بەردەستەکان)",choose_animal:"-- ئاژەڵ هەڵبژێرە --",no_avail:"ئێستا هیچ ئاژەڵی بەردەست نییە.",adopt_date:"بەرواری قبووڵکردن",confirm_btn:"دووپاتکردنەوەی قبووڵکردن"}
};
</script>
<?php require_once 'includes/layout_js.php'; ?>
</body>
</html>