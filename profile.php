<?php
// profile.php
session_start();
require_once 'db.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php"); exit;
}

$user_fullname = htmlspecialchars($_SESSION['fullname']);
$user_role     = htmlspecialchars(ucfirst($_SESSION['role']));
$user_initial  = strtoupper(substr($_SESSION['fullname'], 0, 1));
$uid           = $_SESSION['uid'];

// Fetch full user record
$user = $pdo->prepare("SELECT uid, username, fullname, email, role, created_at FROM users WHERE uid = :uid");
$user->execute([':uid' => $uid]);
$profile = $user->fetch(PDO::FETCH_ASSOC);

$msg = '';
$msg_type = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_info') {
        $new_fullname = trim($_POST['fullname'] ?? '');
        $new_email    = trim($_POST['email'] ?? '');

        if (empty($new_fullname) || empty($new_email)) {
            $msg = 'name_email_required'; $msg_type = 'error';
        } else {
            // Check email uniqueness (excluding self)
            $check = $pdo->prepare("SELECT uid FROM users WHERE email = :email AND uid != :uid");
            $check->execute([':email' => $new_email, ':uid' => $uid]);
            if ($check->fetch()) {
                $msg = 'email_taken'; $msg_type = 'error';
            } else {
                $upd = $pdo->prepare("UPDATE users SET fullname = :fn, email = :em, updated_at = NOW() WHERE uid = :uid");
                if ($upd->execute([':fn' => $new_fullname, ':em' => $new_email, ':uid' => $uid])) {
                    $_SESSION['fullname'] = $new_fullname;
                    $user_fullname = htmlspecialchars($new_fullname);
                    $user_initial  = strtoupper(substr($new_fullname, 0, 1));
                    $profile['fullname'] = $new_fullname;
                    $profile['email']    = $new_email;
                    $msg = 'profile_updated'; $msg_type = 'success';
                } else {
                    $msg = 'update_failed'; $msg_type = 'error';
                }
            }
        }
    } elseif ($action === 'change_password') {
        $cur_pass  = $_POST['current_password'] ?? '';
        $new_pass  = $_POST['new_password'] ?? '';
        $conf_pass = $_POST['confirm_password'] ?? '';

        // Fetch current hashed password
        $ph = $pdo->prepare("SELECT password FROM users WHERE uid = :uid");
        $ph->execute([':uid' => $uid]);
        $stored = $ph->fetchColumn();

        $hashed_current = hash('sha256', $cur_pass);
        if ($hashed_current !== $stored) {
            $msg = 'wrong_password'; $msg_type = 'error';
        } elseif (strlen($new_pass) < 6) {
            $msg = 'pass_too_short'; $msg_type = 'error';
        } elseif ($new_pass !== $conf_pass) {
            $msg = 'pass_mismatch'; $msg_type = 'error';
        } else {
            $new_hashed = hash('sha256', $new_pass);
            $upd2 = $pdo->prepare("UPDATE users SET password = :pw, updated_at = NOW() WHERE uid = :uid");
            if ($upd2->execute([':pw' => $new_hashed, ':uid' => $uid])) {
                $msg = 'pass_changed'; $msg_type = 'success';
            } else {
                $msg = 'update_failed'; $msg_type = 'error';
            }
        }
    }
}

// Fetch recent activity for this user
$my_activity = [];
try {
    $act = $pdo->prepare("SELECT actiontype, targettable, details, created_at FROM user_activity_log WHERE uid = :uid ORDER BY created_at DESC LIMIT 8");
    $act->execute([':uid' => $uid]);
    $my_activity = $act->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$active_page = 'profile';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🐾 PetAdopt — My Profile</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<?php require_once 'includes/layout_css.php'; ?>
<style>
.content{display:flex;flex-direction:column;gap:20px;}
.profile-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;}
.profile-hero{
  background:var(--card);border:1px solid var(--border);border-radius:14px;
  padding:28px;display:flex;align-items:center;gap:20px;
  background-image:linear-gradient(135deg, var(--pl) 0%, transparent 60%);
  grid-column:1/-1;
}
.hero-av{
  width:70px;height:70px;min-width:70px;border-radius:50%;
  background:linear-gradient(135deg,var(--p),var(--y));color:#000;
  display:flex;align-items:center;justify-content:center;
  font-weight:900;font-size:1.9rem;
  box-shadow:0 4px 20px rgba(249,115,22,.4);
  flex-shrink:0;
}
.hero-info h2{font-family:'Bebas Neue';font-size:1.6rem;letter-spacing:1.5px;}
.hero-info .hero-role{font-size:.75rem;font-weight:700;color:var(--p);letter-spacing:.5px;text-transform:uppercase;margin-top:2px;}
.hero-info .hero-since{font-size:.72rem;color:var(--text2);margin-top:6px;}
.hero-stats{margin-left:auto;display:flex;gap:20px;}
.hs-item{text-align:center;}
.hs-num{font-size:1.5rem;font-weight:900;color:var(--p);font-family:'Bebas Neue';}
.hs-label{font-size:.65rem;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.5px;}
.sep{border-top:1px solid var(--border);margin:16px 0;}
.act-chip{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:20px;font-size:.7rem;font-weight:800;}
.act-chip.create{background:var(--gl);color:var(--g);}
.act-chip.edit{background:var(--pul);color:var(--pu);}
.act-chip.delete{background:var(--rl);color:var(--r);}
.act-chip.other{background:var(--yl);color:var(--y);}
.act-row{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border);}
.act-row:last-child{border-bottom:none;}
.act-row .act-det{flex:1;font-size:.8rem;font-weight:600;}
.act-row .act-time{font-size:.67rem;color:var(--text2);}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
@media(max-width:900px){.profile-grid{grid-template-columns:1fr;}.hero-stats{display:none;}}
@media(max-width:600px){.grid-2{grid-template-columns:1fr;}.sbar{display:none;}}
</style>
</head>
<body>

<?php require_once 'includes/sidebar.php'; ?>

<div class="main">
  <?php
    $page_title_key     = 'my_profile';
    $page_title_default = 'My Profile';
    require_once 'includes/header.php';
  ?>

  <div class="content">

    <?php if (!empty($msg)): ?>
      <div class="alert alert-<?= $msg_type === 'success' ? 'success' : 'error' ?>">
        <i class="fas fa-<?= $msg_type === 'success' ? 'check-circle' : 'circle-exclamation' ?>"></i>
        <span data-i18n="<?= $msg ?>"><?= $msg ?></span>
      </div>
    <?php endif; ?>

    <!-- Profile Hero -->
    <div class="profile-hero">
      <div class="hero-av"><?= $user_initial ?></div>
      <div class="hero-info">
        <h2><?= htmlspecialchars($profile['fullname']) ?></h2>
        <div class="hero-role">
          <i class="fas <?= $profile['role'] === 'admin' ? 'fa-shield-halved' : 'fa-user-tie' ?>"></i>
          <?= ucfirst(htmlspecialchars($profile['role'])) ?>
        </div>
        <div class="hero-since"><i class="fas fa-calendar-days"></i> Member since <?= date('F Y', strtotime($profile['created_at'])) ?></div>
      </div>
      <div class="hero-stats">
        <div class="hs-item">
          <div class="hs-num"><?= count($my_activity) ?></div>
          <div class="hs-label">Actions</div>
        </div>
      </div>
    </div>

    <div class="profile-grid">

      <!-- Edit Profile Info -->
      <div class="card">
        <div class="ch"><div class="ct"><i class="fas fa-user-pen"></i> <span data-i18n="edit_profile">Edit Profile</span></div></div>
        <form method="POST" action="profile.php">
          <input type="hidden" name="action" value="update_info">
          <div class="fg">
            <label data-i18n="full_name_lbl">Full Name</label>
            <input type="text" name="fullname" class="fi" value="<?= htmlspecialchars($profile['fullname']) ?>" required>
          </div>
          <div class="fg">
            <label data-i18n="email_lbl">Email Address</label>
            <input type="email" name="email" class="fi" value="<?= htmlspecialchars($profile['email']) ?>" required>
          </div>
          <div class="fg" style="margin-bottom:0">
            <label data-i18n="username_lbl">Username</label>
            <input type="text" class="fi" value="<?= htmlspecialchars($profile['username']) ?>" disabled style="opacity:.5;cursor:not-allowed;">
          </div>
          <div class="sep"></div>
          <button type="submit" class="btn btn-p"><i class="fas fa-save"></i> <span data-i18n="save_changes">Save Changes</span></button>
        </form>
      </div>

      <!-- Change Password -->
      <div class="card">
        <div class="ch"><div class="ct"><i class="fas fa-lock" style="color:var(--pu)"></i> <span data-i18n="change_pass">Change Password</span></div></div>
        <form method="POST" action="profile.php">
          <input type="hidden" name="action" value="change_password">
          <div class="fg">
            <label data-i18n="cur_pass_lbl">Current Password</label>
            <input type="password" name="current_password" class="fi" required>
          </div>
          <div class="fg">
            <label data-i18n="new_pass_lbl">New Password</label>
            <input type="password" name="new_password" class="fi" minlength="6" required>
          </div>
          <div class="fg" style="margin-bottom:0">
            <label data-i18n="conf_pass_lbl">Confirm New Password</label>
            <input type="password" name="confirm_password" class="fi" required>
          </div>
          <div class="sep"></div>
          <button type="submit" class="btn btn-gh"><i class="fas fa-key"></i> <span data-i18n="update_pass">Update Password</span></button>
        </form>
      </div>

      <!-- Account Info (readonly) -->
      <div class="card">
        <div class="ch"><div class="ct"><i class="fas fa-circle-info" style="color:var(--b)"></i> <span data-i18n="account_info">Account Info</span></div></div>
        <div style="display:flex;flex-direction:column;gap:12px;">
          <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid var(--border);">
            <span style="font-size:.78rem;color:var(--text2);font-weight:700;" data-i18n="uid_lbl">User ID</span>
            <span style="font-weight:800;font-size:.85rem;">#<?= $profile['uid'] ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid var(--border);">
            <span style="font-size:.78rem;color:var(--text2);font-weight:700;" data-i18n="role_lbl">Role</span>
            <span class="bdg <?= $profile['role'] === 'admin' ? 'br' : 'bb' ?>"><?= ucfirst(htmlspecialchars($profile['role'])) ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid var(--border);">
            <span style="font-size:.78rem;color:var(--text2);font-weight:700;" data-i18n="created_at_lbl">Joined</span>
            <span style="font-weight:700;font-size:.8rem;"><?= date('M d, Y', strtotime($profile['created_at'])) ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 0;">
            <span style="font-size:.78rem;color:var(--text2);font-weight:700;" data-i18n="username_lbl">Username</span>
            <span style="font-weight:700;font-size:.8rem;color:var(--p);">@<?= htmlspecialchars($profile['username']) ?></span>
          </div>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="card">
        <div class="ch"><div class="ct"><i class="fas fa-bolt" style="color:var(--y)"></i> <span data-i18n="my_activity">My Activity</span></div></div>
        <?php if (count($my_activity) > 0): ?>
          <?php foreach ($my_activity as $act):
            $atype = strtolower($act['actiontype']);
            $chip_class = (strpos($atype,'add')!==false||strpos($atype,'create')!==false)?'create':((strpos($atype,'edit')!==false||strpos($atype,'update')!==false)?'edit':((strpos($atype,'delete')!==false)?'delete':'other'));
          ?>
          <div class="act-row">
            <div class="act-chip <?= $chip_class ?>"><i class="fas <?= $chip_class==='create'?'fa-plus':($chip_class==='edit'?'fa-pen':($chip_class==='delete'?'fa-trash':'fa-bolt')) ?>"></i><?= htmlspecialchars(ucfirst($act['actiontype'])) ?></div>
            <div class="act-det"><?= htmlspecialchars($act['details'] ?: $act['actiontype'].' on '.$act['targettable']) ?></div>
            <div class="act-time"><?= date('M d, H:i', strtotime($act['created_at'])) ?></div>
          </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="empty" style="padding:20px"><i class="fas fa-bolt"></i><p data-i18n="no_activity">No activity recorded yet.</p></div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<script>
const trPage = {
  en:{
    my_profile:"My Profile",edit_profile:"Edit Profile",full_name_lbl:"Full Name",
    email_lbl:"Email Address",username_lbl:"Username",save_changes:"Save Changes",
    change_pass:"Change Password",cur_pass_lbl:"Current Password",
    new_pass_lbl:"New Password",conf_pass_lbl:"Confirm New Password",
    update_pass:"Update Password",account_info:"Account Info",
    uid_lbl:"User ID",role_lbl:"Role",created_at_lbl:"Joined",
    my_activity:"My Activity",no_activity:"No activity recorded yet.",
    profile_updated:"Profile updated successfully!",
    email_taken:"That email is already in use.",
    name_email_required:"Name and email are required.",
    wrong_password:"Current password is incorrect.",
    pass_too_short:"New password must be at least 6 characters.",
    pass_mismatch:"Passwords do not match.",
    pass_changed:"Password changed successfully!",
    update_failed:"Update failed. Please try again."
  },
  ku:{
    my_profile:"پرۆفایلەکەم",edit_profile:"دەستکاریکردنی پرۆفایل",full_name_lbl:"ناوی تەواو",
    email_lbl:"ئیمەیڵ",username_lbl:"ناوی بەکارهێنەر",save_changes:"پاشەکەوتکردن",
    change_pass:"گۆڕینی وشەی نهێنی",cur_pass_lbl:"وشەی نهێنی ئێستا",
    new_pass_lbl:"وشەی نهێنی نوێ",conf_pass_lbl:"دووپاتکردنەوەی وشەی نهێنی نوێ",
    update_pass:"نوێکردنەوەی وشەی نهێنی",account_info:"زانیاری هەژمار",
    uid_lbl:"ئیدی بەکارهێنەر",role_lbl:"ڕۆڵ",created_at_lbl:"بەشداربووە لە",
    my_activity:"چالاکییەکانم",no_activity:"هیچ چالاکییەک تۆمارنەکراوە.",
    profile_updated:"پرۆفایل بە سەرکەوتوویی نوێکرایەوە!",
    email_taken:"ئەم ئیمەیڵە بەکارهێنراوە.",
    name_email_required:"ناو و ئیمەیڵ پێویستە.",
    wrong_password:"وشەی نهێنی ئێستا هەڵەیە.",
    pass_too_short:"وشەی نهێنی نوێ دەبێت لانیکەم ٦ پیت بێت.",
    pass_mismatch:"وشەکانی نهێنی یەکسان نین.",
    pass_changed:"وشەی نهێنی بە سەرکەوتوویی گۆڕدرا!",
    update_failed:"نوێکردنەوە سەرنەکەوت. تکایە دووبارە هەوڵبدەرەوە."
  }
};
</script>
<?php require_once 'includes/layout_js.php'; ?>
</body>
</html>
