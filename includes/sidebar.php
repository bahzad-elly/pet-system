<?php
/**
 * includes/sidebar.php
 * Shared sidebar navigation component.
 * Requires: $user_initial, $user_fullname, $user_role (set in the parent page)
 * Requires: $active_page  (e.g. 'dashboard', 'view_animals', 'add_animal', etc.)
 * Requires: session already started & $_SESSION available
 */
$_avail_count = 0;
try {
    global $pdo;
    $_avail_count = $pdo->query("SELECT COUNT(*) FROM animals WHERE status='Available'")->fetchColumn() ?: 0;
} catch (Exception $_e) {}

$_nav = [
    ['section' => 'overview_sec'],
    ['href' => 'dashboard.php',        'icon' => 'fa-chart-pie',   'key' => 'dashboard',   'page' => 'dashboard'],
    ['section' => 'animals_sec'],
    ['href' => 'view_animals.php',     'icon' => 'fa-paw',         'key' => 'animals',     'page' => 'view_animals',    'pill' => $_avail_count],
    ['href' => 'add_animal.php',       'icon' => 'fa-plus',        'key' => 'add_animal',  'page' => 'add_animal'],
    ['section' => 'people_sec'],
    ['href' => 'view_adopters.php',    'icon' => 'fa-heart',       'key' => 'adopters',    'page' => 'view_adopters'],
    ['href' => 'register_adopter.php', 'icon' => 'fa-user-plus',   'key' => 'add_adopter', 'page' => 'register_adopter'],
    ['section' => 'system_sec'],
    ['href' => 'reports.php',          'icon' => 'fa-chart-line',  'key' => 'reports',     'page' => 'reports'],
];
if ($_SESSION['role'] === 'admin') {
    $_nav[] = ['href' => 'view_users.php', 'icon' => 'fa-users-gear', 'key' => 'admin_ctrl', 'page' => 'view_users', 'style' => 'color:var(--y)'];
}
?>
<aside class="sbar" id="sbar">
  <div class="sbar-logo">
    <div class="sl-icon">🐾</div>
    <div class="sl-txt"><h1>PetAdopt</h1><span data-i18n="shelter_sys">Shelter System</span></div>
  </div>
  <button class="sbar-toggle" onclick="toggleSbar()"><i class="fas fa-chevron-left" id="sbIcon"></i></button>

  <a href="profile.php" class="sbar-user" style="text-decoration:none;cursor:pointer;" title="View Profile">
    <div class="su-av"><?= $user_initial ?></div>
    <div class="su-info">
      <div class="su-name"><?= $user_fullname ?></div>
      <div class="su-role"><?= $user_role ?></div>
    </div>
  </a>

  <nav class="sbar-nav">
    <?php foreach ($_nav as $_item): ?>
      <?php if (isset($_item['section'])): ?>
        <div class="s-sec" data-i18n="<?= $_item['section'] ?>"><?= ucfirst(str_replace('_sec', '', $_item['section'])) ?></div>
      <?php else:
        $_is_on = (isset($active_page) && $active_page === $_item['page']);
        $_icon_style = isset($_item['style']) ? ' style="' . $_item['style'] . '"' : '';
      ?>
        <a href="<?= $_item['href'] ?>" class="ni<?= $_is_on ? ' on' : '' ?>">
          <i class="fas <?= $_item['icon'] ?>"<?= $_icon_style ?>></i>
          <span class="nl" data-i18n="<?= $_item['key'] ?>"><?= $_item['key'] ?></span>
          <?php if (!empty($_item['pill'])): ?>
            <span class="npill"><?= $_item['pill'] ?></span>
          <?php endif; ?>
        </a>
      <?php endif; ?>
    <?php endforeach; ?>
  </nav>

  <div class="sbar-bottom">
    <a href="profile.php" class="ni<?= (isset($active_page) && $active_page === 'profile') ? ' on' : '' ?>">
      <i class="fas fa-user-circle"></i>
      <span class="nl" data-i18n="my_profile">My Profile</span>
    </a>
    <a href="logout.php" class="ni">
      <i class="fas fa-right-from-bracket" style="color:var(--r)"></i>
      <span class="nl" style="color:var(--r)" data-i18n="sign_out">Sign Out</span>
    </a>
  </div>
</aside>
