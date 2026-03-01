<?php
// edit_animal.php
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

$intake_sources = $pdo->query("SELECT iid, sname FROM intake_source")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']); $species = trim($_POST['species']);
    $breed = trim($_POST['breed']); $gender = $_POST['gender'];
    $age = !empty($_POST['age']) ? $_POST['age'] : null;
    $status = $_POST['status'];
    $intake_id = !empty($_POST['intake_id']) ? $_POST['intake_id'] : null;

    $photo_query_part = "";
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['photo']['type'], $allowed_types)) {
            $filename = time() . '_' . basename($_FILES['photo']['name']);
            if (move_uploaded_file($_FILES['photo']['tmp_name'], "uploads/" . $filename)) {
                $photo_query_part = ", photo = :photo";
            }
        } else { $message = 'error'; }
    }

    if (empty($message)) {
        $sql = "UPDATE animals SET name=:name, gender=:gender, species=:species, breed=:breed, age=:age, status=:status, intake_id=:intake_id" . $photo_query_part . " WHERE animal_id=:animal_id";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':name',$name);$stmt->bindParam(':gender',$gender);
            $stmt->bindParam(':species',$species);$stmt->bindParam(':breed',$breed);
            $stmt->bindParam(':age',$age);$stmt->bindParam(':status',$status);
            $stmt->bindParam(':intake_id',$intake_id);$stmt->bindParam(':animal_id',$animal_id);
            if (!empty($photo_query_part)) $stmt->bindParam(':photo', $filename);
            if ($stmt->execute()) {
                $pdo->prepare("INSERT INTO user_activity_log (uid, actiontype, targettable, targetid, details) VALUES (:uid,'Update','animals',:id,'Updated animal record')")->execute([':uid'=>$_SESSION['uid'],':id'=>$animal_id]);
                $message = 'success';
            }
        } catch (PDOException $e) { $message = 'error'; }
    }
}

$stmt = $pdo->prepare("SELECT * FROM animals WHERE animal_id = :id");
$stmt->execute([':id' => $animal_id]);
$animal = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$animal) die("Animal not found.");

$active_page = 'view_animals';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🐾 PetAdopt — Edit Animal</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<?php require_once 'includes/layout_css.php'; ?>
<style>
.content{display:block;}
.card{max-width:700px;}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.sep{border-top:1px solid var(--border);margin:20px 0;}
.ct i{color:var(--y);}
.photo-preview{width:60px;height:60px;border-radius:10px;object-fit:cover;border:1px solid var(--border);margin-top:6px;}
@media(max-width:600px){.grid-2{grid-template-columns:1fr;}}
</style>
</head>
<body>

<?php require_once 'includes/sidebar.php'; ?>

<div class="main">
  <?php
    $page_title_key     = 'edit_animal';
    $page_title_default = 'Edit Animal';
    require_once 'includes/header.php';
  ?>

  <div class="content">
    <div class="card">
      <div class="ch">
        <div class="ct"><i class="fas fa-pen"></i><span data-i18n="edit_animal">Edit Animal</span></div>
        <a href="view_animals.php" class="btn btn-gh"><i class="fas fa-arrow-left"></i><span data-i18n="back_animals">Back to Animals</span></a>
      </div>

      <?php if ($message==='success'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i><span data-i18n="update_ok">Animal record updated successfully!</span></div>
      <?php elseif (!empty($message)): ?><div class="alert alert-error"><i class="fas fa-circle-exclamation"></i><span data-i18n="error_msg">An error occurred. Please try again.</span></div>
      <?php endif; ?>

      <form action="edit_animal.php?id=<?= $animal_id ?>" method="POST" enctype="multipart/form-data">
        <div class="fg"><label data-i18n="animal_name">Name</label><input type="text" name="name" class="fi" value="<?= htmlspecialchars($animal['name']) ?>" required></div>
        <div class="grid-2">
          <div class="fg"><label data-i18n="species">Species</label><input type="text" name="species" class="fi" value="<?= htmlspecialchars($animal['species']) ?>" required></div>
          <div class="fg"><label data-i18n="breed">Breed</label><input type="text" name="breed" class="fi" value="<?= htmlspecialchars($animal['breed']) ?>"></div>
        </div>
        <div class="grid-2">
          <div class="fg">
            <label data-i18n="gender">Gender</label>
            <select name="gender" class="fi">
              <option value="Male"    <?= $animal['gender']=='Male'   ?'selected':'' ?> data-i18n="male">Male</option>
              <option value="Female"  <?= $animal['gender']=='Female' ?'selected':'' ?> data-i18n="female">Female</option>
              <option value="Unknown" <?= $animal['gender']=='Unknown'?'selected':'' ?> data-i18n="unknown">Unknown</option>
            </select>
          </div>
          <div class="fg"><label data-i18n="age_yrs">Age (Years)</label><input type="number" name="age" class="fi" min="0" value="<?= htmlspecialchars($animal['age']) ?>"></div>
        </div>
        <div class="fg">
          <label data-i18n="status">Status</label>
          <select name="status" class="fi" required>
            <option value="Available"   <?= $animal['status']=='Available'  ?'selected':'' ?> data-i18n="available">Available</option>
            <option value="Pending"     <?= $animal['status']=='Pending'    ?'selected':'' ?> data-i18n="pending">Pending</option>
            <option value="Medical Care"<?= $animal['status']=='Medical Care'?'selected':'' ?> data-i18n="medical_care">Medical Care</option>
            <option value="Adopted"     <?= $animal['status']=='Adopted'    ?'selected':'' ?> data-i18n="adopted">Adopted</option>
          </select>
        </div>
        <div class="fg">
          <label data-i18n="intake_src">Intake Source</label>
          <select name="intake_id" class="fi">
            <option value="">-- Select Source --</option>
            <?php foreach ($intake_sources as $src): ?><option value="<?= $src['iid'] ?>" <?= $animal['intake_id']==$src['iid']?'selected':'' ?>><?= htmlspecialchars($src['sname']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="fg">
          <label data-i18n="update_photo">Update Photo (leave blank to keep current)</label>
          <input type="file" name="photo" class="fi" accept="image/*">
          <?php if (!empty($animal['photo']) && file_exists('uploads/'.$animal['photo'])): ?>
            <img src="uploads/<?= htmlspecialchars($animal['photo']) ?>" class="photo-preview" alt="Current photo">
          <?php endif; ?>
        </div>
        <div class="sep"></div>
        <button type="submit" class="btn btn-y"><i class="fas fa-floppy-disk"></i><span data-i18n="update_btn">Update Animal</span></button>
      </form>
    </div>
  </div>
</div>

<script>
const trPage = {
  en:{edit_animal:"Edit Animal",back_animals:"Back to Animals",update_ok:"Animal record updated successfully!",error_msg:"An error occurred.",animal_name:"Name",species:"Species",breed:"Breed",gender:"Gender",unknown:"Unknown",male:"Male",female:"Female",age_yrs:"Age (Years)",status:"Status",available:"Available",pending:"Pending",medical_care:"Medical Care",adopted:"Adopted",intake_src:"Intake Source",update_photo:"Update Photo (leave blank to keep current)",update_btn:"Update Animal"},
  ku:{edit_animal:"دەستکاریکردنی ئاژەڵ",back_animals:"گەڕانەوە بۆ ئاژەڵەکان",update_ok:"تۆماری ئاژەڵ بە سەرکەوتوویی نوێکرایەوە!",error_msg:"هەڵەیەک ڕوویدا.",animal_name:"ناو",species:"جۆر",breed:"نەژاد",gender:"ڕەگەز",unknown:"نەزانراو",male:"نێر",female:"مێ",age_yrs:"تەمەن (ساڵ)",status:"دەربارە",available:"بەردەستە",pending:"چاوەڕوان",medical_care:"چارەسەری پزیشکی",adopted:"قبووڵکرا",intake_src:"سەرچاوەی وەرگرتن",update_photo:"نوێکردنەوەی وێنە",update_btn:"نوێکردنەوەی ئاژەڵ"}
};
</script>
<?php require_once 'includes/layout_js.php'; ?>
</body>
</html>