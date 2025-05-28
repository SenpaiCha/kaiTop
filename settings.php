<?php
// settings.php: User/vendor password change page
session_start();
require_once 'db.php';
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}
$user = $_SESSION['username'];
$err = $msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST['old_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (!$old || !$new || !$confirm) {
        $err = 'All fields are required.';
    } elseif ($new !== $confirm) {
        $err = 'New passwords do not match.';
    } else {
        $stmt = $pdo->prepare('SELECT passWord FROM users WHERE userName=?');
        $stmt->execute([$user]);
        $hash = $stmt->fetchColumn();
        if (!$hash || !password_verify($old, $hash)) {
            $err = 'Old password is incorrect.';
        } else {
            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET passWord=? WHERE userName=?');
            $stmt->execute([$newHash, $user]);
            $msg = 'Password changed successfully!';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Change Password - kaiTOP</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background: #f5f5f7; color: #1d1d1f; margin: 0; }
        .container { max-width: 400px; margin: 60px auto; background: #fff; border-radius: 18px; box-shadow: 0 4px 24px 0 rgba(0,0,0,0.07); padding: 32px; }
        h1 { font-size: 1.5rem; margin-bottom: 24px; }
        input[type=password] { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #d2d2d7; margin-bottom: 16px; }
        input[type=submit] { background: #0071e3; color: #fff; border: none; border-radius: 8px; padding: 10px 18px; font-weight: 600; cursor: pointer; }
        input[type=submit]:hover { background: #005bb5; }
        .error { color: #dc3545; margin-bottom: 12px; }
        .success { color: #28a745; margin-bottom: 12px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Change Password</h1>
    <?php if ($err): ?><div class="error"><?= htmlspecialchars($err) ?></div><?php endif; ?>
    <?php if ($msg): ?><div class="success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <form method="post">
        <input type="password" name="old_password" placeholder="Old Password" required>
        <input type="password" name="new_password" placeholder="New Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
        <input type="submit" value="Change Password">
    </form>
</div>
</body>
</html>
