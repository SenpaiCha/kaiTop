<?php
session_start();
require_once 'db.php';

$error = '';
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    $roleId = 1; // Default to customer
    if (!$username || !$password || !$confirm) {
        $error = 'All fields are required.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check if username exists
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE userName = ?');
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Username already taken.';
        } else {
            // Insert user
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (userName, password, roleId, cash) VALUES (?, ?, ?, 0)');
            $stmt->execute([$username, $hash, $roleId]);
            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register - kaiTOP</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background: #f5f5f7; color: #1d1d1f; margin: 0; }
        .container { max-width: 400px; margin: 60px auto; background: #fff; border-radius: 18px; box-shadow: 0 4px 24px 0 rgba(0,0,0,0.07); padding: 32px; }
        h1 { font-size: 2rem; margin-bottom: 24px; text-align: center; }
        label { font-weight: 600; display: block; margin-bottom: 8px; }
        input { width: 100%; padding: 10px; margin-bottom: 18px; border-radius: 8px; border: 1px solid #d2d2d7; background: #f5f5f7; font-size: 1rem; }
        button { background: #0071e3; color: #fff; border: none; border-radius: 8px; padding: 12px 24px; font-weight: 600; font-size: 1rem; cursor: pointer; width: 100%; }
        button:hover { background: #005bb5; }
        .error { color: #c00; text-align: center; margin-bottom: 16px; }
        .success { color: #009688; text-align: center; margin-bottom: 16px; }
        .login-link { text-align: center; margin-top: 18px; }
        .login-link a { color: #0071e3; text-decoration: underline; }
    </style>
</head>
<body>
<div class="container">
    <h1>Register</h1>
    <?php if ($success): ?>
        <div class="success">Registration successful! <a href="login.php">Login here</a>.</div>
    <?php else: ?>
        <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            <label for="confirm">Confirm Password</label>
            <input type="password" id="confirm" name="confirm" required>
            <button type="submit">Register</button>
        </form>
        <div class="login-link">Already have an account? <a href="login.php">Login</a></div>
    <?php endif; ?>
</div>
</body>
</html>
