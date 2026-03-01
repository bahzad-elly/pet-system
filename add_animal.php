<?php
// add_animal.php
session_start();
require_once 'db.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php"); exit;
}

$user_fullname = htmlspecialchars($_SESSION['fullname']);
$user_role     = htmlspecialchars(ucfirst($_SESSION['role']));
$user_initial  = strtoupper(substr($_SESSION['fullname'], 0, 1));
$message = '';

$intake_sources = [];
try {
    $stmt = $pdo->query("SELECT iid, sname FROM intake_source");
    $intake_sources = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name      = trim($_POST['name']);
    $species   = trim($_POST['species']);
    $breed     = trim($_POST['breed']);
    $gender    = $_POST['gender'];
    $age       = !empty($_POST['age'])       ? $_POST['age']       : null;
    $status    = $_POST['status'];
    $intake_id = !empty($_POST['intake_id']) ? $_POST['intake_id'] : null;

    $photo_path = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['photo']['type'], $allowed_types)) {
            $filename   = time() . '_' . basename($_FILES['photo']['name']);
            $target_dir = "uploads/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_dir.$filename)) {
                $photo_path = $filename;
            } else { $message = 'error_upload'; }
        } else { $message = 'error_filetype'; }
    }

    if (empty($message)) {
        $sql = "INSERT INTO animals (name, gender, species, breed, age, status, photo, intake_id) VALUES (:name, :gender, :species, :breed, :age, :status, :photo, :intake_id)";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':name'=>$name,':gender'=>$gender,':species'=>$species,':breed'=>$breed,':age'=>$age,':status'=>$status,':photo'=>$photo_path,':intake_id'=>$intake_id]);
            $animal_id = $pdo->lastInsertId();
            $pdo->prepare("INSERT INTO user_activity_log (uid, actiontype, targettable, targetid, details) VALUES (:uid, 'Create', 'animals', :id, 'Registered new animal')")->execute([':uid'=>$_SESSION['uid'],':id'=>$animal_id]);
            $message = 'success_'.htmlspecialchars($name);
        } catch (PDOException $e) { $message = 'error_db'; }
    }
}

$active_page = 'add_animal';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🐾 PetAdopt — Add Animal</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<?php require_once 'includes/layout_css.php'; ?>
<style>
.content{display:block;}
.card{max-width:700px;}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.sep{border-top:1px solid var(--border);margin:20px 0;}
.btn-p{background:linear-gradient(135deg,var(--p),var(--pd));color:#fff;box-shadow:0 4px 16px rgba(249,115,22,.3);}
.btn-p:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(249,115,22,.4);}
@media(max-width:600px){.grid-2{grid-template-columns:1fr;}}
</style>
</head>
<body>

<?php require_once 'includes/sidebar.php'; ?>

<div class="main">
  <?php
    $page_title_key     = 'add_animal';
    $page_title_default = 'Add Animal';
    require_once 'includes/header.php';
  ?>

  <div class="content">
    <div class="card">
      <div class="ch">
        <div class="ct"><i class="fas fa-paw"></i><span data-i18n="register_animal">Register New Animal</span></div>
        <a href="view_animals.php" class="btn btn-gh"><i class="fas fa-arrow-left"></i><span data-i18n="back_animals">Back to Animals</span></a>
      </div>

      <?php if (!empty($message)): ?>
        <?php if (str_starts_with($message,'success_')): ?>
          <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= str_replace('success_','',$message) ?> <span data-i18n="registered_ok">registered successfully!</span></span></div>
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
            <?php foreach ($intake_sources as $src): ?><option value="<?= $src['iid'] ?>"><?= htmlspecialchars($src['sname']) ?></option><?php endforeach; ?>
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
const trPage = {
  en:{register_animal:"Register New Animal",back_animals:"Back to Animals",registered_ok:"registered successfully!",error_msg:"An error occurred. Please try again.",animal_name:"Name",species:"Species (e.g., Dog, Cat)",breed:"Breed",gender:"Gender",unknown:"Unknown",male:"Male",female:"Female",age_yrs:"Age (Years)",status:"Status",available:"Available",pending:"Pending",medical_care:"Medical Care",adopted:"Adopted",intake_src:"Intake Source",select_source:"-- Select Source --",photo_opt:"Animal Photo (Optional)",register_btn:"Register Animal"},
  ku:{register_animal:"تۆمارکردنی ئاژەڵی نوێ",back_animals:"گەڕانەوە بۆ ئاژەڵەکان",registered_ok:"بە سەرکەوتوویی تۆمارکرا!",error_msg:"هەڵەیەک ڕوویدا. تکایە دووبارە هەوڵبدەرەوە.",animal_name:"ناو",species:"جۆر (بۆ نموونە، سەگ، پشیلە)",breed:"نەژاد",gender:"ڕەگەز",unknown:"نەزانراو",male:"نێر",female:"مێ",age_yrs:"تەمەن (ساڵ)",status:"دەربارە",available:"بەردەستە",pending:"چاوەڕوان",medical_care:"چارەسەری پزیشکی",adopted:"قبووڵکرا",intake_src:"سەرچاوەی وەرگرتن",select_source:"-- سەرچاوە هەڵبژێرە --",photo_opt:"وێنەی ئاژەڵ (دڵخواز)",register_btn:"تۆمارکردنی ئاژەڵ"}
};
</script>
<?php require_once 'includes/layout_js.php'; ?>
</body>
</html>