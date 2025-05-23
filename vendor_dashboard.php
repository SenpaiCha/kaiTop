<?php
// vendor_dashboard.php
session_start();
require_once 'db.php';

// Only allow access for logged-in vendors (for demo, any logged-in user)
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit;
}

// Handle create, update, delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Handle image upload if present
        $imagePath = null;
        if (!empty($_FILES['image']['name'])) {
            $targetDir = 'uploads/';
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            $filename = uniqid('img_') . '_' . basename($_FILES['image']['name']);
            $targetFile = $targetDir . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $imagePath = $targetFile;
            }
        }
        if ($_POST['action'] === 'create') {
            $stmt = $pdo->prepare("INSERT INTO products (name, category, price, description, image, vendor) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['name'], $_POST['category'], $_POST['price'], $_POST['description'], $imagePath ? $imagePath : $_POST['image_url'], $_SESSION['username']
            ]);
        } elseif ($_POST['action'] === 'edit') {
            // If new image uploaded, use it; else keep old or use image_url
            $imageToUse = $imagePath ? $imagePath : $_POST['image_url'];
            $stmt = $pdo->prepare("UPDATE products SET name=?, category=?, price=?, description=?, image=? WHERE id=? AND vendor=?");
            $stmt->execute([
                $_POST['name'], $_POST['category'], $_POST['price'], $_POST['description'], $imageToUse, $_POST['id'], $_SESSION['username']
            ]);
        } elseif ($_POST['action'] === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id=? AND vendor=?");
            $stmt->execute([$_POST['id'], $_SESSION['username']]);
        }
    }
    header('Location: vendor_dashboard.php');
    exit;
}

// Fetch products for this vendor (including suspended)
$stmt = $pdo->prepare("SELECT * FROM products WHERE vendor=?");
$stmt->execute([$_SESSION['username']]);
$products = $stmt->fetchAll();

// Fetch categories for dropdown
$catStmt = $pdo->query("SELECT id, name FROM category ORDER BY name");
$categories = $catStmt->fetchAll();

// Fetch purchases for this vendor
$purchaseStmt = $pdo->prepare("SELECT * FROM purchases WHERE product_id IN (SELECT id FROM products WHERE vendor=?) ORDER BY purchased_at DESC LIMIT 100");
$purchaseStmt->execute([$_SESSION['username']]);
$vendorPurchases = $purchaseStmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Vendor Dashboard</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background: #f5f5f7; color: #1d1d1f; margin: 0; }
        .container { max-width: 900px; margin: 40px auto; background: #fff; border-radius: 18px; box-shadow: 0 4px 24px 0 rgba(0,0,0,0.07); padding: 32px; }
        h1 { font-size: 2rem; margin-bottom: 24px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 32px; }
        th, td { padding: 12px 8px; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #f5f5f7; }
        tr:last-child td { border-bottom: none; }
        .actions button { margin-right: 8px; }
        .form-section { margin-bottom: 32px; }
        input, select, textarea { width: 100%; padding: 8px; margin-bottom: 12px; border-radius: 8px; border: 1px solid #d2d2d7; background: #f5f5f7; }
        input[type=submit], button { background: #0071e3; color: #fff; border: none; border-radius: 8px; padding: 10px 18px; font-weight: 600; cursor: pointer; }
        input[type=submit]:hover, button:hover { background: #005bb5; }
        .edit-form { background: #f9f9fa; border-radius: 12px; padding: 16px; margin-bottom: 16px; }
    </style>
</head>
<body>
<?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) { ?>
    <div class="sidebar" style="width:200px; background:#fff; height:100vh; padding:32px 20px 20px 20px; box-shadow:2px 0 16px 0 rgba(0,0,0,0.04); border-radius:0 16px 16px 0; position:fixed; top:0; left:0; z-index:1000;">
        <?php include "sidebar.php"; ?>
    </div>
<?php } ?>
<div class="container" style="margin-left:<?php echo (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) ? '220px' : '0'; ?>;">
    <h1>Vendor Dashboard</h1>
    <div class="form-section">
        <h2>Add New Product</h2>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="create">
            <input type="text" name="name" placeholder="Product Name" required>
            <select name="category" required>
                <option value="">Select Category</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat['name']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="price" placeholder="Price" step="0.01" required>
            <input type="file" name="image" accept="image/*">
            <input type="text" name="image_url" placeholder="Or paste image URL">
            <textarea name="description" placeholder="Description" required></textarea>
            <input type="submit" value="Add Product">
        </form>
    </div>
    <h2>Your Products</h2>
    <table>
        <tr><th>Name</th><th>Category</th><th>Price</th><th>Suspended</th><th>Reason</th><th>Actions</th></tr>
        <?php foreach ($products as $p): ?>
        <tr <?php if ($p['suspended']) echo 'style="background:#ffeaea;"'; ?>>
            <td><?= htmlspecialchars($p['name']) ?></td>
            <td><?= htmlspecialchars($p['category']) ?></td>
            <td>$<?= htmlspecialchars($p['price']) ?></td>
            <td><?= $p['suspended'] ? 'Yes' : 'No' ?></td>
            <td><?= $p['suspended'] ? (isset($p['suspend_reason']) ? htmlspecialchars($p['suspend_reason']) : 'No reason provided') : '-' ?></td>
            <td class="actions">
                <form method="post" style="display:inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <button type="submit" onclick="return confirm('Delete this product?')">Delete</button>
                </form>
                <button onclick="showEditForm(<?= $p['id'] ?>)">Edit</button>
            </td>
        </tr>
        <tr id="edit-form-<?= $p['id'] ?>" style="display:none;"><td colspan="6">
            <form method="post" class="edit-form" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                <input type="text" name="name" value="<?= htmlspecialchars($p['name']) ?>" required>
                <select name="category" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat['name']) ?>" <?= $cat['name'] === $p['category'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="price" value="<?= htmlspecialchars($p['price']) ?>" step="0.01" required>
                <input type="file" name="image" accept="image/*">
                <input type="text" name="image_url" value="<?= htmlspecialchars($p['image']) ?>" placeholder="Or paste image URL">
                <textarea name="description" required><?= htmlspecialchars($p['description']) ?></textarea>
                <input type="submit" value="Save Changes">
                <button type="button" onclick="hideEditForm(<?= $p['id'] ?>)">Cancel</button>
            </form>
        </td></tr>
        <?php endforeach; ?>
    </table>
    <h2>Purchase Logs</h2>
    <div style="margin-bottom:16px;">
        <input type="text" id="purchase-log-search" placeholder="Search purchase logs..." style="width:100%;padding:8px;border-radius:8px;border:1px solid #d2d2d7;">
    </div>
    <table id="purchase-log-table">
        <tr><th>Product ID</th><th>Buyer</th><th>Quantity</th><th>Total</th><th>Date</th></tr>
        <?php foreach ($vendorPurchases as $pur): ?>
        <tr>
            <td><?= htmlspecialchars($pur['product_id']) ?></td>
            <td><?= htmlspecialchars($pur['buyer']) ?></td>
            <td><?= htmlspecialchars($pur['quantity']) ?></td>
            <td>$<?= htmlspecialchars($pur['total']) ?></td>
            <td><?= htmlspecialchars($pur['purchased_at']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <script>
    function showEditForm(id) {
        document.getElementById('edit-form-' + id).style.display = '';
    }
    function hideEditForm(id) {
        document.getElementById('edit-form-' + id).style.display = 'none';
    }
    document.getElementById('purchase-log-search').addEventListener('input', function() {
        const q = this.value.toLowerCase();
        const rows = document.querySelectorAll('#purchase-log-table tr');
        rows.forEach((row, i) => {
            if (i === 0) return; // skip header
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(q) ? '' : 'none';
        });
    });
    </script>
</div>
</body>
</html>
