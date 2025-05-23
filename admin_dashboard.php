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
        } elseif ($_POST['action'] === 'reset_password') {
            $resetUser = $_POST['username'] ?? '';
            $newPass = $_POST['new_password'] ?? '';
            if ($resetUser && $newPass) {
                $hash = password_hash($newPass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('UPDATE users SET passWord=? WHERE userName=?');
                $stmt->execute([$hash, $resetUser]);
            }
        }
    }
    // Category management logic
    if (isset($_POST['add_category']) && !empty($_POST['new_category'])) {
        $catName = trim($_POST['new_category']);
        $stmt = $pdo->prepare('INSERT INTO category (name) VALUES (?)');
        $stmt->execute([$catName]);
        header('Location: admin_dashboard.php'); exit;
    }
    if (isset($_POST['delete_category_id'])) {
        $catId = $_POST['delete_category_id'];
        $stmt = $pdo->prepare('DELETE FROM category WHERE id=?');
        $stmt->execute([$catId]);
        header('Location: admin_dashboard.php'); exit;
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
        .actions input[type=submit][value="Delete"] {
            background: #dc3545 !important;
            color: #fff !important;
            border: none;
        }
        .actions input[type=submit][value="Delete"]:hover {
            background: #b02a37 !important;
        }
        .btn-green {
            background: #28a745 !important;
            color: #fff !important;
        }
        .btn-green:hover {
            background: #218838 !important;
        }
        .btn-yellow {
            background: #ffb300 !important;
            color: #fff !important;
        }
        .btn-yellow:hover {
            background: #ff9800 !important;
        }
        .btn-blue {
            background: #0071e3 !important;
            color: #fff !important;
        }
        .btn-blue:hover {
            background: #005bb5 !important;
        }
        .btn-red {
            background: #dc3545 !important;
            color: #fff !important;
        }
        .btn-red:hover {
            background: #b02a37 !important;
        }
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
        <!-- User rows will be rendered by JS -->
    </table>
    <div id="user-pagination" style="text-align:center;margin-bottom:32px;"></div>
    <script>
    const users = <?php echo json_encode($users); ?>;
    const roles = <?php echo json_encode($roles); ?>;
    const userRowsPerPage = 10;
    let userPage = 1;
    let userSearch = '';
    function renderUserTable() {
        const table = document.getElementById('admin-user-table');
        // Remove all rows except header
        while (table.rows.length > 1) table.deleteRow(1);
        let filtered = users.filter(u => {
            const text = (u.userName + ' ' + roles.find(r=>r.id==u.roleId).name + ' ' + u.cash).toLowerCase();
            return text.includes(userSearch);
        });
        const totalPages = Math.ceil(filtered.length / userRowsPerPage);
        userPage = Math.max(1, Math.min(userPage, totalPages || 1));
        const start = (userPage-1)*userRowsPerPage;
        const end = start+userRowsPerPage;
        filtered.slice(start, end).forEach(u => {
            const tr = table.insertRow();
            tr.insertCell().textContent = u.userName;
            tr.insertCell().textContent = roles.find(r=>r.id==u.roleId).name;
            tr.insertCell().textContent = '$'+u.cash;
            const td = tr.insertCell();
            td.className = 'actions';
            td.innerHTML = `
            <form method="post" style="display:inline;">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="username" value="${u.userName}">
                <input type="submit" value="Delete" onclick="return confirm('Delete this user?')">
            </form>
            <form method="post" style="display:inline;">
                <input type="hidden" name="action" value="add_cash">
                <input type="hidden" name="username" value="${u.userName}">
                <input type="number" name="amount" step="0.01" placeholder="Amount" style="width:80px;">
                <input type="submit" value="Add Cash" class="btn-blue">
            </form>
            <form method="post" style="display:inline;">
                <input type="hidden" name="action" value="change_role">
                <input type="hidden" name="username" value="${u.userName}">
                <select name="roleId">
                    ${roles.map(r=>`<option value="${r.id}"${u.roleId==r.id?' selected':''}>${r.name}</option>`).join('')}
                </select>
                <input type="submit" value="Change Role" class="btn-blue">
            </form>
            <form method="post" style="display:inline;" onsubmit="return resetPasswordPrompt(this, '${u.userName}')">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="username" value="${u.userName}">
                <input type="hidden" name="new_password" value="">
                <input type="submit" value="Reset Password" class="btn-yellow">
            </form>`;
        });
        // Pagination
        const pagDiv = document.getElementById('user-pagination');
        pagDiv.innerHTML = '';
        if (totalPages > 1) {
            for (let i=1; i<=totalPages; i++) {
                const btn = document.createElement('button');
                btn.textContent = i;
                btn.style.margin = '0 4px';
                btn.style.background = (i===userPage)?'#0071e3':'#eee';
                btn.style.color = (i===userPage)?'#fff':'#333';
                btn.style.border = 'none';
                btn.style.borderRadius = '4px';
                btn.style.padding = '4px 10px';
                btn.onclick = ()=>{userPage=i;renderUserTable();};
                pagDiv.appendChild(btn);
            }
        }
    }
    document.getElementById('admin-user-search').addEventListener('input', function() {
        userSearch = this.value.toLowerCase();
        userPage = 1;
        renderUserTable();
    });
    renderUserTable();
    </script>

    <h2>Products</h2>
    <div style="margin-bottom:16px;">
        <input type="text" id="admin-product-search" placeholder="Search products..." style="width:100%;padding:8px;border-radius:8px;border:1px solid #d2d2d7;">
    </div>
    <table id="admin-product-table">
        <tr><th>Name</th><th>Vendor</th><th>Category</th><th>Price</th><th>Suspended</th><th>Actions</th></tr>
        <!-- Product rows will be rendered by JS -->
    </table>
    <div id="product-pagination" style="text-align:center;margin-bottom:32px;"></div>
    <script>
    const products = <?php echo json_encode($products); ?>;
    const productRowsPerPage = 10;
    let productPage = 1;
    let productSearch = '';
    function renderProductTable() {
        const table = document.getElementById('admin-product-table');
        while (table.rows.length > 1) table.deleteRow(1);
        let filtered = products.filter(p => {
            const text = (p.name + ' ' + p.vendor + ' ' + p.category + ' ' + p.price + ' ' + (p.suspended?'Yes':'No')).toLowerCase();
            return text.includes(productSearch);
        });
        const totalPages = Math.ceil(filtered.length / productRowsPerPage);
        productPage = Math.max(1, Math.min(productPage, totalPages || 1));
        const start = (productPage-1)*productRowsPerPage;
        const end = start+productRowsPerPage;
        filtered.slice(start, end).forEach(p => {
            const tr = table.insertRow();
            tr.insertCell().textContent = p.name;
            tr.insertCell().textContent = p.vendor;
            tr.insertCell().textContent = p.category;
            tr.insertCell().textContent = '$'+p.price;
            tr.insertCell().textContent = p.suspended ? 'Yes' : 'No';
            const td = tr.insertCell();
            td.className = 'actions';
            if (!p.suspended) {
                td.innerHTML = `<form method="post" style="display:inline;" onsubmit="return suspendPopup(this, ${p.id})">
                    <input type="hidden" name="action" value="suspend_product">
                    <input type="hidden" name="product_id" value="${p.id}">
                    <input type="hidden" name="suspend_reason" value="">
                    <input type="submit" value="Suspend">
                </form>`;
            } else {
                td.innerHTML = `<form method="post" style="display:inline;">
                    <input type="hidden" name="action" value="unsuspend_product">
                    <input type="hidden" name="product_id" value="${p.id}">
                    <input type="submit" value="Unsuspend">
                </form>`;
            }
        });
        // Pagination
        const pagDiv = document.getElementById('product-pagination');
        pagDiv.innerHTML = '';
        if (totalPages > 1) {
            for (let i=1; i<=totalPages; i++) {
                const btn = document.createElement('button');
                btn.textContent = i;
                btn.style.margin = '0 4px';
                btn.style.background = (i===productPage)?'#0071e3':'#eee';
                btn.style.color = (i===productPage)?'#fff':'#333';
                btn.style.border = 'none';
                btn.style.borderRadius = '4px';
                btn.style.padding = '4px 10px';
                btn.onclick = ()=>{productPage=i;renderProductTable();};
                pagDiv.appendChild(btn);
            }
        }
    }
    document.getElementById('admin-product-search').addEventListener('input', function() {
        productSearch = this.value.toLowerCase();
        productPage = 1;
        renderProductTable();
    });
    renderProductTable();
    </script>

    <h2>Purchase Logs</h2>
    <div style="margin-bottom:16px;">
        <input type="text" id="admin-purchase-log-search" placeholder="Search purchase logs..." style="width:100%;padding:8px;border-radius:8px;border:1px solid #d2d2d7;">
    </div>
    <table id="admin-purchase-log-table">
        <tr><th>Vendor</th><th>Product ID</th><th>Buyer</th><th>Quantity</th><th>Total</th><th>Log Time</th></tr>
        <!-- Purchase log rows will be rendered by JS -->
    </table>
    <div id="log-pagination" style="text-align:center;margin-bottom:32px;"></div>
    <script>
    const logs = <?php echo json_encode($purchase_logs); ?>;
    const logRowsPerPage = 10;
    let logPage = 1;
    let logSearch = '';
    function renderLogTable() {
        const table = document.getElementById('admin-purchase-log-table');
        while (table.rows.length > 1) table.deleteRow(1);
        let filtered = logs.filter(l => {
            const text = (l.vendor + ' ' + l.product_id + ' ' + l.buyer + ' ' + l.quantity + ' ' + l.total + ' ' + l.log_time).toLowerCase();
            return text.includes(logSearch);
        });
        const totalPages = Math.ceil(filtered.length / logRowsPerPage);
        logPage = Math.max(1, Math.min(logPage, totalPages || 1));
        const start = (logPage-1)*logRowsPerPage;
        const end = start+logRowsPerPage;
        filtered.slice(start, end).forEach(l => {
            const tr = table.insertRow();
            tr.insertCell().textContent = l.vendor;
            tr.insertCell().textContent = l.product_id;
            tr.insertCell().textContent = l.buyer;
            tr.insertCell().textContent = l.quantity;
            tr.insertCell().textContent = '$'+l.total;
            tr.insertCell().textContent = l.log_time;
        });
        // Pagination
        const pagDiv = document.getElementById('log-pagination');
        pagDiv.innerHTML = '';
        if (totalPages > 1) {
            for (let i=1; i<=totalPages; i++) {
                const btn = document.createElement('button');
                btn.textContent = i;
                btn.style.margin = '0 4px';
                btn.style.background = (i===logPage)?'#0071e3':'#eee';
                btn.style.color = (i===logPage)?'#fff':'#333';
                btn.style.border = 'none';
                btn.style.borderRadius = '4px';
                btn.style.padding = '4px 10px';
                btn.onclick = ()=>{logPage=i;renderLogTable();};
                pagDiv.appendChild(btn);
            }
        }
    }
    document.getElementById('admin-purchase-log-search').addEventListener('input', function() {
        logSearch = this.value.toLowerCase();
        logPage = 1;
        renderLogTable();
    });
    renderLogTable();
    </script>

    <h2>Manage Categories</h2>
    <form method="post" style="margin-bottom:16px;display:flex;gap:12px;align-items:center;">
        <input type="text" name="new_category" placeholder="New category name" required style="padding:8px;border-radius:6px;">
        <input type="submit" name="add_category" value="Add Category" class="btn-green">
    </form>
    <table style="max-width:400px;">
        <tr><th>Category</th><th>Actions</th></tr>
        <?php
        // Fetch categories
        $categories = $pdo->query("SELECT * FROM category ORDER BY name ASC")->fetchAll();
        foreach ($categories as $cat): ?>
        <tr>
            <td><?= htmlspecialchars($cat['name']) ?></td>
            <td>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="delete_category_id" value="<?= $cat['id'] ?>">
                    <input type="submit" value="Delete" class="btn-red" onclick="return confirm('Delete this category?')">
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <div id="resetPasswordModal" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.25);z-index:2000;align-items:center;justify-content:center;">
        <div style="background:#fff;padding:32px 24px;border-radius:12px;min-width:320px;max-width:90vw;box-shadow:0 8px 32px 0 rgba(0,0,0,0.15);position:relative;">
            <form method="post" id="resetPasswordForm" style="display:flex;flex-direction:column;gap:16px;">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="reset_user" id="reset_user_modal">
                <div style="font-weight:600;font-size:1.1em;">Reset password for <span id="reset_user_label"></span></div>
                <input type="password" name="new_password" placeholder="New password" required style="padding:8px;border-radius:6px;">
                <div style="display:flex;gap:12px;">
                    <input type="submit" value="Reset Password" style="background:#ffb300;color:#fff;padding:8px 18px;border-radius:6px;border:none;font-weight:600;">
                    <button type="button" onclick="closeResetPasswordModal()" style="background:#eee;color:#333;padding:8px 18px;border-radius:6px;border:none;font-weight:600;">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    <script>
    function showResetPasswordModal(username) {
        document.getElementById('reset_user_modal').value = username;
        document.getElementById('reset_user_label').textContent = username;
        document.getElementById('resetPasswordModal').style.display = 'flex';
    }
    function closeResetPasswordModal() {
        document.getElementById('resetPasswordModal').style.display = 'none';
    }
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
