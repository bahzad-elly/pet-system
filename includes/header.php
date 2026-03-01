<?php
/**
 * includes/header.php
 * Shared topbar header component — notification bell is always visible.
 * Requires: $user_initial, $user_fullname, $user_role (set in parent page)
 * Requires: $page_title_key, $page_title_default
 */
?>
<!-- ── Notification Panel ──────────────────────────────── -->
<div class="np" id="notifPanel">
  <div class="np-h">
    <div style="display:flex;align-items:center;gap:8px;">
      <i class="fas fa-bell" style="color:var(--p)"></i>
      <h4>Notifications</h4>
      <span class="np-badge" id="notifBadgePanel" style="display:none"></span>
    </div>
    <button class="np-mark-btn" id="markReadBtn" onclick="markAllRead()">Mark all read</button>
  </div>
  <div id="notifList" class="np-list">
    <div class="np-loading"><i class="fas fa-spinner fa-spin"></i>&nbsp; Loading…</div>
  </div>
  <div class="np-footer">
    <span style="font-size:.7rem;color:var(--text2)">Last 12 hours of activity</span>
  </div>
</div>

<!-- ── Profile Dropdown ────────────────────────────────── -->
<div id="profileDropdown" class="profile-dropdown" style="display:none;">
  <div class="pd-arrow"></div>
  <div class="pd-header">
    <div class="pd-av"><?= $user_initial ?></div>
    <div class="pd-info">
      <div class="pd-name"><?= $user_fullname ?></div>
      <div class="pd-role"><?= $user_role ?></div>
    </div>
  </div>
  <div class="pd-divider"></div>
  <a href="profile.php" class="pd-item"><i class="fas fa-user-circle"></i><span data-i18n="my_profile">My Profile</span></a>
  <div class="pd-divider"></div>
  <a href="logout.php" class="pd-item pd-item-danger"><i class="fas fa-right-from-bracket"></i><span data-i18n="sign_out">Sign Out</span></a>
</div>

<header class="topbar">
  <span class="tb-clock" id="clock">00:00</span>
  <span class="topbar-title" data-i18n="<?= $page_title_key ?? 'page' ?>"><?= $page_title_default ?? 'PetAdopt' ?></span>
  <div class="tb-right">
    <div class="lang-sw">
      <div class="lang-opt on" id="langEn" onclick="setLanguage('en')">EN</div>
      <div class="lang-opt" id="langKu" onclick="setLanguage('ku')">KU</div>
    </div>
    <div class="theme-sw">
      <div class="t-opt on" id="tDark" onclick="setTheme('dark')"><i class="fas fa-moon"></i></div>
      <div class="t-opt" id="tLight" onclick="setTheme('light')"><i class="fas fa-sun"></i></div>
    </div>
    <!-- Always-visible notification bell -->
    <button class="tb-btn" onclick="toggleNotif()" id="notifBtn" title="Notifications">
      <i class="fas fa-bell"></i>
      <div class="tb-dot" id="notifDot" style="display:none;"></div>
    </button>
    <button class="tb-btn" onclick="window.location.reload()"><i class="fas fa-rotate"></i></button>
    <!-- Clickable profile avatar -->
    <div class="u-av" id="profileAvatarBtn" onclick="toggleProfileDropdown()" title="My Profile">
      <?= $user_initial ?>
    </div>
  </div>
</header>
