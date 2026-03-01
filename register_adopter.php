<?php
// register_adopter.php
session_start();
require_once 'db.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php"); exit;
}

$user_fullname = htmlspecialchars($_SESSION['fullname']);
$user_role     = htmlspecialchars(ucfirst($_SESSION['role']));
$user_initial  = strtoupper(substr($_SESSION['fullname'], 0, 1));
$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fname  = trim($_POST['fname']); $lname  = trim($_POST['lname']);
    $dob    = !empty($_POST['dob']) ? $_POST['dob'] : null;
    $phone  = trim($_POST['phone']); $address = trim($_POST['address']);
    $preference = trim($_POST['preference']);

    $sql = "INSERT INTO adopters (fname, lname, DoB, phone, address, preference) VALUES (:fname, :lname, :dob, :phone, :address, :preference)";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':fname'=>$fname,':lname'=>$lname,':dob'=>$dob,':phone'=>$phone,':address'=>$address,':preference'=>$preference]);
        $adopter_id = $pdo->lastInsertId();
        $pdo->prepare("INSERT INTO user_activity_log (uid, actiontype, targettable, targetid, details) VALUES (:uid,'Create','adopters',:id,'Registered new adopter: $fname $lname')")->execute([':uid'=>$_SESSION['uid'],':id'=>$adopter_id]);
        $message = 'success';
    } catch (PDOException $e) { $message = 'error'; }
}

$active_page = 'register_adopter';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🐾 PetAdopt — Register Adopter</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<?php require_once 'includes/layout_css.php'; ?>
<style>
.content{display:block;}
.card{max-width:680px;}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.sep{border-top:1px solid var(--border);margin:20px 0;}
.ct i{color:var(--b);}
textarea.fi{resize:vertical;min-height:80px;}
@media(max-width:600px){.grid-2{grid-template-columns:1fr;}}
</style>
</head>
<body>

<?php require_once 'includes/sidebar.php'; ?>

<div class="main">
  <?php
    $page_title_key     = 'reg_adopter';
    $page_title_default = 'Register Adopter';
    require_once 'includes/header.php';
  ?>

  <div class="content">
    <div class="card">
      <div class="ch">
        <div class="ct"><i class="fas fa-user-plus"></i><span data-i18n="reg_new_adopter">Register New Adopter</span></div>
        <a href="view_adopters.php" class="btn btn-gh"><i class="fas fa-arrow-left"></i><span data-i18n="back_adopters">Back to Adopters</span></a>
      </div>

      <?php if ($message==='success'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i><span data-i18n="success_msg">Adopter registered successfully!</span></div>
      <?php elseif (!empty($message)): ?><div class="alert alert-error"><i class="fas fa-circle-exclamation"></i><span data-i18n="error_msg">An error occurred. Please try again.</span></div>
      <?php endif; ?>

      <form action="register_adopter.php" method="POST">
        <div class="grid-2">
          <div class="fg"><label data-i18n="fname">First Name</label><input type="text" name="fname" class="fi" required></div>
          <div class="fg"><label data-i18n="lname">Last Name</label><input type="text" name="lname" class="fi" required></div>
        </div>
        <div class="grid-2">
          <div class="fg"><label data-i18n="dob">Date of Birth</label><input type="date" name="dob" class="fi"></div>
          <div class="fg"><label data-i18n="phone">Phone Number</label><input type="text" name="phone" class="fi"></div>
        </div>
        <div class="fg"><label data-i18n="address">Address</label><textarea name="address" class="fi" placeholder="Full street address..."></textarea></div>
        <div class="fg"><label data-i18n="preference">Adoption Preferences</label><textarea name="preference" class="fi" placeholder="e.g., Looking for a small dog..."></textarea></div>
        <div class="sep"></div>
        <button type="submit" class="btn btn-b"><i class="fas fa-user-plus"></i><span data-i18n="reg_btn">Register Adopter</span></button>
      </form>
    </div>
  </div>
</div>

<script>
const trPage = {
  en:{reg_adopter:"Register Adopter",reg_new_adopter:"Register New Adopter",back_adopters:"Back to Adopters",success_msg:"Adopter registered successfully!",error_msg:"An error occurred.",fname:"First Name",lname:"Last Name",dob:"Date of Birth",phone:"Phone Number",address:"Address",preference:"Adoption Preferences",reg_btn:"Register Adopter"},
  ku:{reg_adopter:"تۆمارکردنی وەرگر",reg_new_adopter:"تۆمارکردنی وەرگری نوێ",back_adopters:"گەڕانەوە بۆ وەرگرەکان",success_msg:"وەرگر بە سەرکەوتوویی تۆمارکرا!",error_msg:"هەڵەیەک ڕوویدا.",fname:"ناوی یەکەم",lname:"ناوی کۆتایی",dob:"بەرواری لەدایکبوون",phone:"ژمارەی تەلەفۆن",address:"ناونیشان",preference:"ئارەزووی قبووڵکردن",reg_btn:"تۆمارکردنی وەرگر"}
};
</script>
<?php require_once 'includes/layout_js.php'; ?>
</body>
</html>