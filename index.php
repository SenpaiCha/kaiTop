<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Welcome</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
        }
        .sidebar {
            width: 200px;
            float: left;
            background-color: #f2f2f2;
            height: 100vh;
            padding: 20px;
        }
        .content {
            margin-left: 220px;
            padding: 20px;
        }
        a {
            text-decoration: none;
            display: block;
            margin: 10px 0;
            color: #333;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<?php include "sidebar.php"; ?>

<div class="content">
    <h1>Welcome, <?= htmlspecialchars($_SESSION["username"]) ?>!</h1>
    <p>This is your dashboard.</p>
    <p><a href="logout.php">Logout</a></p>
</div>

</body>
</html>
