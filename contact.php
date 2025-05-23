<?php
session_start();
require_once 'db.php';

// Fetch products for dropdown
$prodStmt = $pdo->query("SELECT id, name FROM products ORDER BY name");
$allProducts = $prodStmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Contact Support - kaiTOP</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background: #f5f5f7; color: #1d1d1f; margin: 0; }
        .main-flex { display: flex; }
        .sidebar { width: 200px; background-color: #fff; height: 100vh; padding: 32px 20px 20px 20px; box-shadow: 2px 0 16px 0 rgba(0,0,0,0.04); border-radius: 0 16px 16px 0; position: fixed; top: 0; left: 0; z-index: 1000; display: flex; flex-direction: column; }
        .container { max-width: 500px; margin: 60px auto; background: #fff; border-radius: 18px; box-shadow: 0 4px 24px 0 rgba(0,0,0,0.07); padding: 32px; margin-left: 220px; }
        h1 { font-size: 2rem; margin-bottom: 24px; text-align: center; }
        .contact-info { font-size: 1.1rem; color: #222; margin-top: 32px; text-align: center; }
        .contact-info strong { color: #0071e3; }
        @media (max-width: 900px) {
            .container { margin-left: 0; }
            .sidebar { position: absolute; height: auto; min-height: 100vh; }
        }
    </style>
</head>
<body>
<div class="main-flex">
    <?php include 'sidebar.php'; ?>
    <div class="container">
        <h1>Contact Support</h1>
        <div class="contact-info">
            For any support or inquiries, please contact us at:<br><br>
            <strong>Email:</strong> support@clannect.tech<br>
            <strong>Phone:</strong> +66 1234 5678<br>
            <strong>Line ID:</strong> kaitop-support<br>
            <br>
            Our team will get back to you as soon as possible.
        </div>
    </div>
</div>
</body>
</html>
