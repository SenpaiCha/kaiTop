<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("Location: index.php");
    exit;
}

require_once "db.php";

$error = "";

// Handle login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"] ?? '');
    $password = $_POST["password"] ?? '';
    if (!$username || !$password) {
        $error = "Please enter username and password.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE userName = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['passWord'])) {
            $_SESSION["loggedin"] = true;
            $_SESSION["username"] = $user['userName'];
            $_SESSION["roleId"] = $user['roleId'];
            header("Location: index.php");
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>

<!-- Frontend -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - kaiTOP</title>
    <style>
        body {
            background-color: #f4f4f4;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            background: #fff;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            width: 300px;
        }
        h2 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #333;
        }
        form {
            text-align: center;
        }
        input[type="text"],
        input[type="password"] {
            width: 90%;
            padding: 0.75rem;
            margin-bottom: 1rem;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
        }
        input[type="submit"] {
            width: 70%;
            padding: 0.75rem;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #0056b3;
        }
        .error {
            color: red;
            font-size: 0.9rem;
            text-align: center;
            margin-bottom: 1rem;
        }
        .register-container {
            margin-top: 18px;
            text-align: center;
        }
        .register-container a {
            background: #0071e3;
            color: #fff;
            padding: 10px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            box-shadow: 0 2px 8px 0 rgba(0,0,0,0.08);
        }
    </style>
</head>
<body>
<div class="login-container">
    <h2>Login</h2>
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post" action="">
        <input type="text" name="username" placeholder="Username" required autofocus value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
        <input type="password" name="password" placeholder="Password" required value="<?= isset($_POST['password']) ? htmlspecialchars($_POST['password']) : '' ?>">
        <input type="submit" value="Login">
    </form>
    <div class="register-container">
        <a href="register.php">Register</a>
    </div>
</div>
</body>
</html>
