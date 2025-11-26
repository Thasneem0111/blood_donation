<?php
// Include from admin pages to require admin login
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['is_admin'])) {
    // not logged in -> redirect to login
    header('Location: /BloodDonation/admin/login.php');
    exit;
}
?>
