<?php
// admin_dashboard.php
session_start();
require_once 'db.php';

// Only allow access for admin (roleId == 3)
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["roleId"] != 3) {
    header("Location: login.php");
    exit;
}

// Handle user, role, product, and cash management
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'suspend_product') {
            $stmt = $pdo->prepare("UPDATE products SET suspended = 1, suspend_reason = ? WHERE id = ?");
            $stmt->execute([$_POST['suspend_reason'], $_POST['product_id']]);
        } elseif ($_POST['action'] === 'unsuspend_product') {
            $stmt = $pdo->prepare("UPDATE products SET suspended = 0, suspend_reason = NULL WHERE id = ?");
            $stmt->execute([$_POST['product_id']]);
        } elseif ($_POST['action'] === 'delete_user') {
            $stmt = $pdo->prepare("DELETE FROM users WHERE userName = ?");
            $stmt->execute([$_POST['username']]);
        } elseif ($_POST['action'] === 'add_cash') {
            $stmt = $pdo->prepare("UPDATE users SET cash = cash + ? WHERE userName = ?");
            $stmt->execute([$_POST['amount'], $_POST['username']]);
        } elseif ($_POST['action'] === 'change_role') {
            $stmt = $pdo->prepare("UPDATE users SET roleId = ? WHERE userName = ?");
            $stmt->execute([$_POST['roleId'], $_POST['username']]);
        }
    }
    header('Location: admin_dashboard.php');
    exit;
}

// Fetch all users, roles, products, and purchase logs
$users = $pdo->query("SELECT * FROM users")->fetchAll();
// Hardcode roles for display
$roles = [
    ["id" => 1, "name" => "Customer"],
    ["id" => 2, "name" => "Vendor"],
    ["id" => 3, "name" => "Admin"]
];
$products = $pdo->query("SELECT * FROM products")->fetchAll();
$purchase_logs = $pdo->query("SELECT * FROM purchase_logs ORDER BY log_time DESC LIMIT 100")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background: #f5f5f7; color: #1d1d1f; margin: 0; }
        .container { max-width: 1200px; margin: 40px auto; background: #fff; border-radius: 18px; box-shadow: 0 4px 24px 0 rgba(0,0,0,0.07); padding: 32px; }
        h1 { font-size: 2rem; margin-bottom: 24px; }
        h2 { margin-top: 32px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 32px; }
        th, td { padding: 12px 8px; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #f5f5f7; }
        tr:last-child td { border-bottom: none; }
        .actions button, .actions input[type=submit] { margin-right: 8px; }
        input, select { padding: 6px; border-radius: 6px; border: 1px solid #d2d2d7; }
        input[type=submit], button { background: #0071e3; color: #fff; border: none; border-radius: 6px; padding: 8px 14px; font-weight: 600; cursor: pointer; }
        input[type=submit]:hover, button:hover { background: #005bb5; }
    </style>
</head>
<body>
<?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) { ?>
    <div class="sidebar" style="width:200px; background:#fff; height:100vh; padding:32px 20px 20px 20px; box-shadow:2px 0 16px 0 rgba(0,0,0,0.04); border-radius:0 16px 16px 0; position:fixed; top:0; left:0; z-index:1000;">
        <?php include "sidebar.php"; ?>
    </div>
<?php } ?>
<div class="container" style="margin-left:220px;">
    <h1>Admin Dashboard</h1>
    <h2>Users</h2>
    <div style="margin-bottom:16px;">
        <input type="text" id="admin-user-search" placeholder="Search users..." style="width:100%;padding:8px;border-radius:8px;border:1px solid #d2d2d7;">
    </div>
    <table id="admin-user-table">
        <tr><th>Username</th><th>Role</th><th>Cash</th><th>Actions</th></tr>
        <?php $userCount = 0; foreach ($users as $u): if ($userCount++ >= 10) break; ?>
        <tr>
            <td><?= htmlspecialchars($u['userName']) ?></td>
            <td><?= htmlspecialchars($roles[array_search($u['roleId'], array_column($roles, 'id'))]['name']) ?></td>
            <td>$<?= htmlspecialchars($u['cash']) ?></td>
            <td class="actions">
                <form method="post" style="display:inline;">
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="username" value="<?= $u['userName'] ?>">
                    <input type="submit" value="Delete" onclick="return confirm('Delete this user?')">
                </form>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="action" value="add_cash">
                    <input type="hidden" name="username" value="<?= $u['userName'] ?>">
                    <input type="number" name="amount" step="0.01" placeholder="Amount" style="width:80px;">
                    <input type="submit" value="Add Cash">
                </form>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="action" value="change_role">
                    <input type="hidden" name="username" value="<?= $u['userName'] ?>">
                    <select name="roleId">
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= $r['id'] ?>" <?= $u['roleId'] == $r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="submit" value="Change Role">
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <script>
    document.getElementById('admin-user-search').addEventListener('input', function() {
        const q = this.value.toLowerCase();
        const rows = document.querySelectorAll('#admin-user-table tr');
        rows.forEach((row, i) => {
            if (i === 0) return;
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(q) ? '' : 'none';
        });
    });
    </script>

    <h2>Products</h2>
    <div style="margin-bottom:16px;">
        <input type="text" id="admin-product-search" placeholder="Search products..." style="width:100%;padding:8px;border-radius:8px;border:1px solid #d2d2d7;">
    </div>
    <table id="admin-product-table">
        <tr><th>Name</th><th>Vendor</th><th>Category</th><th>Price</th><th>Suspended</th><th>Actions</th></tr>
        <?php $prodCount = 0; foreach ($products as $p): if ($prodCount++ >= 10) break; ?>
        <tr>
            <td><?= htmlspecialchars($p['name']) ?></td>
            <td><?= htmlspecialchars($p['vendor']) ?></td>
            <td><?= htmlspecialchars($p['category']) ?></td>
            <td>$<?= htmlspecialchars($p['price']) ?></td>
            <td><?= $p['suspended'] ? 'Yes' : 'No' ?></td>
            <td class="actions">
                <?php if (!$p['suspended']): ?>
                <form method="post" style="display:inline;" onsubmit="return suspendPopup(this, <?= $p['id'] ?>)">
                    <input type="hidden" name="action" value="suspend_product">
                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                    <input type="hidden" name="suspend_reason" value="">
                    <input type="submit" value="Suspend">
                </form>
                <?php else: ?>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="action" value="unsuspend_product">
                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                    <input type="submit" value="Unsuspend">
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <script>
    document.getElementById('admin-product-search').addEventListener('input', function() {
        const q = this.value.toLowerCase();
        const rows = document.querySelectorAll('#admin-product-table tr');
        rows.forEach((row, i) => {
            if (i === 0) return;
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(q) ? '' : 'none';
        });
    });
    </script>

    <h2>Purchase Logs</h2>
    <div style="margin-bottom:16px;">
        <input type="text" id="admin-purchase-log-search" placeholder="Search purchase logs..." style="width:100%;padding:8px;border-radius:8px;border:1px solid #d2d2d7;">
    </div>
    <table id="admin-purchase-log-table">
        <tr><th>Vendor</th><th>Product ID</th><th>Buyer</th><th>Quantity</th><th>Total</th><th>Log Time</th></tr>
        <?php $logCount = 0; foreach ($purchase_logs as $log): if ($logCount++ >= 10) break; ?>
        <tr>
            <td><?= htmlspecialchars($log['vendor']) ?></td>
            <td><?= htmlspecialchars($log['product_id']) ?></td>
            <td><?= htmlspecialchars($log['buyer']) ?></td>
            <td><?= htmlspecialchars($log['quantity']) ?></td>
            <td>$<?= htmlspecialchars($log['total']) ?></td>
            <td><?= htmlspecialchars($log['log_time']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <script>
    document.getElementById('admin-purchase-log-search').addEventListener('input', function() {
        const q = this.value.toLowerCase();
        const rows = document.querySelectorAll('#admin-purchase-log-table tr');
        rows.forEach((row, i) => {
            if (i === 0) return;
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(q) ? '' : 'none';
        });
    });
    </script>
</div>
<script>
function suspendPopup(form, productId) {
    const reason = prompt('Enter reason for suspension:');
    if (reason === null) return false;
    form.suspend_reason.value = reason;
    return true;
}
</script>
</body>
</html>
