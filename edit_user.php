<?php
// edit_user.php
session_start();
require_once 'db.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header("location: dashboard.php"); exit;
}

$user_fullname = htmlspecialchars($_SESSION['fullname']);
$user_role     = htmlspecialchars(ucfirst($_SESSION['role']));
$user_initial  = strtoupper(substr($_SESSION['fullname'], 0, 1));
$message = '';
$uid = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($uid === 0) die("Invalid User ID.");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname']); $email = trim($_POST['email']);
    $username = trim($_POST['username']); $role = $_POST['role'];
    $sql = "UPDATE users SET fullname=:fullname, email=:email, username=:username, role=:role WHERE uid=:uid";
    if (!empty($_POST['password'])) {
        $sql = "UPDATE users SET fullname=:fullname, email=:email, username=:username, role=:role, password=:password WHERE uid=:uid";
    }
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':fullname',$fullname);$stmt->bindParam(':email',$email);
        $stmt->bindParam(':username',$username);$stmt->bindParam(':role',$role);$stmt->bindParam(':uid',$uid);
        if (!empty($_POST['password'])) { $hashed = hash('sha256',$_POST['password']); $stmt->bindParam(':password',$hashed); }
        if ($stmt->execute()) {
            $pdo->prepare("INSERT INTO user_activity_log (uid, actiontype, targettable, targetid, details) VALUES (:u,'Update','users',:t,'Updated user account')")->execute([':u'=>$_SESSION['uid'],':t'=>$uid]);
            $message = 'success';
        }
    } catch (PDOException $e) { $message = ($e->getCode()==23000) ? 'duplicate' : 'error'; }
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE uid = :uid");
$stmt->execute([':uid' => $uid]);
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user_data) die("User not found.");

$active_page = 'view_users';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🐾 PetAdopt — Edit User</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<?php require_once 'includes/layout_css.php'; ?>
<style>
.content{display:block;}
.card{max-width:600px;}
.sep{border-top:1px solid var(--border);margin:20px 0;}
.ct i{color:var(--y);}
.pw-note{font-size:.72rem;color:var(--text2);margin-top:5px;font-weight:600;}
</style>
</head>
<body>

<?php require_once 'includes/sidebar.php'; ?>

<div class="main">
  <?php
    $page_title_key     = 'edit_user';
    $page_title_default = 'Edit User';
    require_once 'includes/header.php';
  ?>

  <div class="content">
    <div class="card">
      <div class="ch">
        <div class="ct"><i class="fas fa-user-pen"></i><span data-i18n="edit_user">Edit User</span></div>
        <a href="view_users.php" class="btn btn-gh"><i class="fas fa-arrow-left"></i><span data-i18n="back_users">Back to Users</span></a>
      </div>
      <?php if ($message==='success'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i><span data-i18n="success_msg">User updated successfully!</span></div>
      <?php elseif ($message==='duplicate'): ?><div class="alert alert-error"><i class="fas fa-circle-exclamation"></i><span data-i18n="dup_error">Username or Email already exists.</span></div>
      <?php elseif (!empty($message)): ?><div class="alert alert-error"><i class="fas fa-circle-exclamation"></i><span data-i18n="error_msg">An error occurred.</span></div>
      <?php endif; ?>
      <form action="edit_user.php?id=<?= $uid ?>" method="POST">
        <div class="fg"><label data-i18n="fullname_lbl">Full Name</label><input type="text" name="fullname" class="fi" value="<?= htmlspecialchars($user_data['fullname']) ?>" required></div>
        <div class="fg"><label data-i18n="email_lbl">Email</label><input type="email" name="email" class="fi" value="<?= htmlspecialchars($user_data['email']) ?>" required></div>
        <div class="fg"><label data-i18n="username_lbl">Username</label><input type="text" name="username" class="fi" value="<?= htmlspecialchars($user_data['username']) ?>" required></div>
        <div class="fg">
          <label data-i18n="new_pw">New Password</label>
          <input type="password" name="password" class="fi" placeholder="Leave blank to keep current">
          <p class="pw-note" data-i18n="pw_hint">Leave blank to keep the existing password unchanged.</p>
        </div>
        <div class="fg">
          <label data-i18n="role_lbl">Role</label>
          <select name="role" class="fi">
            <option value="staff" <?= $user_data['role']==='staff'?'selected':'' ?> data-i18n="staff_opt">Staff</option>
            <option value="admin" <?= $user_data['role']==='admin'?'selected':'' ?> data-i18n="admin_opt">Admin</option>
          </select>
        </div>
        <div class="sep"></div>
        <button type="submit" class="btn btn-y"><i class="fas fa-floppy-disk"></i><span data-i18n="update_btn">Update User</span></button>
      </form>
    </div>
  </div>
</div>

<script>
const trPage = {
  en:{edit_user:"Edit User",back_users:"Back to Users",success_msg:"User updated successfully!",dup_error:"Username or Email already exists.",error_msg:"An error occurred.",fullname_lbl:"Full Name",email_lbl:"Email",username_lbl:"Username",new_pw:"New Password",pw_hint:"Leave blank to keep the existing password unchanged.",role_lbl:"Role",staff_opt:"Staff",admin_opt:"Admin",update_btn:"Update User"},
  ku:{edit_user:"دەستکاریکردنی بەکارهێنەر",back_users:"گەڕانەوە بۆ بەکارهێنەرەکان",success_msg:"بەکارهێنەر بە سەرکەوتوویی نوێکرایەوە!",dup_error:"ناوی بەکارهێنەر یان ئیمەیڵ پێشتر بوونی هەیە.",error_msg:"هەڵەیەک ڕوویدا.",fullname_lbl:"ناوی تەواو",email_lbl:"ئیمەیڵ",username_lbl:"ناوی بەکارهێنەر",new_pw:"وشەی نهێنی نوێ",pw_hint:"بۆ هێشتنەوەی وشەی نهێنی بەتاڵ بهێڵەوە.",role_lbl:"ڕۆڵ",staff_opt:"ستاف",admin_opt:"بەڕێوەبەر",update_btn:"نوێکردنەوەی بەکارهێنەر"}
};
</script>
<?php require_once 'includes/layout_js.php'; ?>
</body>
</html>