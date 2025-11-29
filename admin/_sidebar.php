<?php
// Shared admin sidebar used by all admin pages
// highlights the current page's nav item by comparing the current script name
$current = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
function navItem($href, $label, $current){
    $cls = 'nav-item' . ($current === $href ? ' active' : '');
    return "<a class=\"$cls\" href=\"$href\">$label</a>";
}
?>
<style>
  .admin-sidebar a{ text-decoration:none; }
</style>
<aside class="admin-sidebar" role="navigation">
  <h3 style="margin-bottom:1rem;">Admin</h3>
  <?= navItem('dashboard.php', '🏠 Dashboard', $current) ?>
  <?= navItem('seekers.php', '🧾 Blood Seeker', $current) ?>
  <?= navItem('donors.php', '🩸 Blood Donor', $current) ?>
  <?= navItem('notifications.php', '🔔 Notifications', $current) ?>
  <?= navItem('settings.php', '⚙️ Settings', $current) ?>
  <?= navItem('logout.php', '🚪 Logout', $current) ?>
</aside>
