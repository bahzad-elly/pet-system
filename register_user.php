<?php
// register_user.php
session_start();
require_once 'db.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header("location: dashboard.php"); exit;
}

$user_fullname = htmlspecialchars($_SESSION['fullname']);
$user_role     = htmlspecialchars(ucfirst($_SESSION['role']));
$user_initial  = strtoupper(substr($_SESSION['fullname'], 0, 1));
$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname']); $username = trim($_POST['username']);
    $email    = trim($_POST['email']);    $password = $_POST['password'];
    $role     = $_POST['role'];
    $hashed_password = hash('sha256', $password);
    $sql = "INSERT INTO users (username, password, role, fullname, email) VALUES (:username, :password, :role, :fullname, :email)";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':username'=>$username,':password'=>$hashed_password,':role'=>$role,':fullname'=>$fullname,':email'=>$email]);
        $message = 'success';
    } catch (PDOException $e) { $message = ($e->getCode() == 23000) ? 'duplicate' : 'error'; }
}

$active_page = 'view_users';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🐾 PetAdopt — Register User</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<?php require_once 'includes/layout_css.php'; ?>
<style>
.content{display:block;}
.card{max-width:600px;}
.sep{border-top:1px solid var(--border);margin:20px 0;}
.ct i{color:var(--y);}
</style>
</head>
<body>

<?php require_once 'includes/sidebar.php'; ?>

<div class="main">
  <?php
    $page_title_key     = 'reg_user_title';
    $page_title_default = 'Register User';
    require_once 'includes/header.php';
  ?>

  <div class="content">
    <div class="card">
      <div class="ch">
        <div class="ct"><i class="fas fa-user-shield"></i><span data-i18n="reg_staff_admin">Register Staff / Admin</span></div>
        <a href="view_users.php" class="btn btn-gh"><i class="fas fa-arrow-left"></i><span data-i18n="back_users">Back to Users</span></a>
      </div>
      <?php if ($message==='success'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i><span data-i18n="success_msg">User registered successfully!</span></div>
      <?php elseif ($message==='duplicate'): ?><div class="alert alert-error"><i class="fas fa-circle-exclamation"></i><span data-i18n="dup_error">Username or Email already exists.</span></div>
      <?php elseif (!empty($message)): ?><div class="alert alert-error"><i class="fas fa-circle-exclamation"></i><span data-i18n="error_msg">An error occurred.</span></div>
      <?php endif; ?>
      <form action="register_user.php" method="POST">
        <div class="fg"><label data-i18n="fullname_lbl">Full Name</label><input type="text" name="fullname" class="fi" required></div>
        <div class="fg"><label data-i18n="email_lbl">Email</label><input type="email" name="email" class="fi" required></div>
        <div class="fg"><label data-i18n="username_lbl">Username</label><input type="text" name="username" class="fi" required></div>
        <div class="fg"><label data-i18n="password_lbl">Password</label><input type="password" name="password" class="fi" required></div>
        <div class="fg">
          <label data-i18n="role_lbl">Role</label>
          <select name="role" class="fi" required>
            <option value="staff" data-i18n="staff_opt">Staff</option>
            <option value="admin" data-i18n="admin_opt">Admin</option>
          </select>
        </div>
        <div class="sep"></div>
        <button type="submit" class="btn btn-p"><i class="fas fa-user-plus"></i><span data-i18n="reg_btn">Register User</span></button>
      </form>
    </div>
  </div>
</div>

<script>
const trPage = {
  en:{reg_user_title:"Register User",reg_staff_admin:"Register Staff / Admin",back_users:"Back to Users",success_msg:"User registered successfully!",dup_error:"Username or Email already exists.",error_msg:"An error occurred.",fullname_lbl:"Full Name",email_lbl:"Email",username_lbl:"Username",password_lbl:"Password",role_lbl:"Role",staff_opt:"Staff",admin_opt:"Admin",reg_btn:"Register User"},
  ku:{reg_user_title:"تۆمارکردنی بەکارهێنەر",reg_staff_admin:"تۆمارکردنی ستاف / بەڕێوەبەر",back_users:"گەڕانەوە بۆ بەکارهێنەرەکان",success_msg:"بەکارهێنەر بە سەرکەوتوویی تۆمارکرا!",dup_error:"ناوی بەکارهێنەر یان ئیمەیڵ پێشتر بوونی هەیە.",error_msg:"هەڵەیەک ڕوویدا.",fullname_lbl:"ناوی تەواو",email_lbl:"ئیمەیڵ",username_lbl:"ناوی بەکارهێنەر",password_lbl:"وشەی نهێنی",role_lbl:"ڕۆڵ",staff_opt:"ستاف",admin_opt:"بەڕێوەبەر",reg_btn:"تۆمارکردنی بەکارهێنەر"}
};
</script>
<?php require_once 'includes/layout_js.php'; ?>
</body>
</html>