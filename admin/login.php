<?php
require_once __DIR__ . '/../db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Ensure admin_users table exists
$create = "CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$mysqli->query($create);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    if ($user === '' || $pass === '') { $error = 'Username and password required'; }
    else {
        // If no admin users exist, create a default admin (change password ASAP)
        $res = $mysqli->query("SELECT COUNT(*) AS c FROM admin_users");
        $c = $res ? (int)$res->fetch_assoc()['c'] : 0;
        if ($c === 0) {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare("INSERT INTO admin_users (username,password) VALUES (?,?)");
            $stmt->bind_param('ss', $user, $hash);
            $stmt->execute();
        }

        // lookup user
        $stmt = $mysqli->prepare("SELECT id,password FROM admin_users WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $user);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            if (password_verify($pass, $row['password'])) {
                $_SESSION['is_admin'] = true;
                $_SESSION['admin_user'] = $user;
                header('Location: /BloodDonation/admin/dashboard.php');
                exit;
            } else {
                $error = 'Invalid credentials';
            }
        } else {
            $error = 'User not found';
        }
    }
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin Login</title>
<link rel="stylesheet" href="/BloodDonation/styles.css"></head><body style="padding:2rem;">
  <main style="max-width:420px;margin:2rem auto;background:#fff;padding:1.4rem;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,0.06)">
    <h2>Admin Login</h2>
    <?php if($error): ?><div style="color:#c40024;margin-bottom:.6rem;"><?=htmlspecialchars($error)?></div><?php endif; ?>
    <form method="post">
      <label>Username<input name="username" required style="width:100%;padding:.6rem;border-radius:6px;border:1px solid #ddd;margin-bottom:.6rem;" /></label>
      <label>Password<input name="password" type="password" required style="width:100%;padding:.6rem;border-radius:6px;border:1px solid #ddd;margin-bottom:.6rem;" /></label>
      <div style="display:flex;gap:.6rem;align-items:center;"><button class="submit-btn" type="submit">Sign In</button><a href="/BloodDonation/index.html" style="margin-left:auto;">Back to site</a></div>
    </form>
    <p style="margin-top:.8rem;color:#666;font-size:.9rem">If this is the first admin login, the account you sign in with will be created. Change the password after logging in.</p>
  </main>
</body></html>
