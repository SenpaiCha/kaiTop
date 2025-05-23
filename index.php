<?php
session_start();
require_once 'db.php';

// Fetch categories for dropdown
$catStmt = $pdo->query("SELECT id, name FROM category ORDER BY name");
$categories = $catStmt->fetchAll();

// Fetch products for display (with optional filter)
$category = isset($_GET['category']) ? $_GET['category'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$params = [];
$sql = "SELECT * FROM products WHERE suspended = 0";
if ($category !== 'all') {
    $sql .= " AND category = ?";
    $params[] = $category;
}
if ($search !== '') {
    $sql .= " AND name LIKE ?";
    $params[] = "%$search%";
}
$sql .= " ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Home - kaiTOP</title>
    <style>
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f5f7;
            color: #1d1d1f;
        }
        .sidebar {
            width: 200px;
            background-color: #fff;
            height: 100vh;
            padding: 32px 20px 20px 20px;
            box-shadow: 2px 0 16px 0 rgba(0,0,0,0.04);
            border-radius: 0 16px 16px 0;
            transition: transform 0.3s, left 0.3s;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }
        .sidebar.hide {
            transform: translateX(-220px);
        }
        .sidebar-toggle {
            position: fixed;
            top: 24px;
            left: 0;
            z-index: 1100;
            background: #fff;
            color: #0071e3;
            border: none;
            border-radius: 0 8px 8px 0;
            padding: 10px 16px;
            cursor: pointer;
            font-size: 20px;
            box-shadow: 0 2px 8px 0 rgba(0,0,0,0.08);
            transition: left 0.3s;
        }
        .content {
            margin-left: 0;
            padding: 40px 24px 24px 24px;
            transition: margin-left 0.3s;
            min-height: 100vh;
        }
        .logged-in .content {
            margin-left: 220px;
        }
        h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 16px;
            letter-spacing: -1px;
            color: #1d1d1f;
        }
        label {
            font-size: 1.1rem;
            color: #86868b;
        }
        select, input[type="text"] {
            border: 1px solid #d2d2d7;
            border-radius: 8px;
            font-size: 1rem;
            padding: 10px 12px;
            margin-bottom: 16px;
            background: #f5f5f7;
            color: #1d1d1f;
            width: 100%;
            box-sizing: border-box;
            outline: none;
            transition: border 0.2s;
        }
        select:focus, input[type="text"]:focus {
            border: 1.5px solid #0071e3;
        }
        #product-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 32px;
        }
        .product-item {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 24px 0 rgba(0,0,0,0.07);
            padding: 32px 20px 24px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: box-shadow 0.2s, transform 0.2s;
            cursor: pointer;
            border: none;
        }
        .product-item:hover {
            box-shadow: 0 8px 32px 0 rgba(0,113,227,0.10);
            transform: translateY(-4px) scale(1.03);
        }
        .product-item img {
            width: 140px;
            height: 140px;
            object-fit: cover;
            border-radius: 12px;
            margin-bottom: 18px;
            background: #f5f5f7;
        }
        .product-item .name {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 8px;
            text-align: center;
            color: #1d1d1f;
        }
        .product-item .category {
            color: #86868b;
            font-size: 0.98rem;
            margin-bottom: 8px;
            text-align: center;
        }
        .product-item .price {
            color: #0071e3;
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 10px;
        }
        .product-item .description {
            color: #444;
            font-size: 1rem;
            text-align: center;
            min-height: 48px;
        }
        .product-item .vendor {
            color: #0071e3;
            font-size: 0.98rem;
            margin-top: 8px;
            font-weight: 500;
            text-align: center;
        }
        @media (max-width: 900px) {
            .logged-in .content {
                margin-left: 0;
            }
            .sidebar {
                position: absolute;
                height: auto;
                min-height: 100vh;
            }
        }
    </style>
</head>
<body class="<?= isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true ? 'logged-in' : '' ?>">

<?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    // Fetch user cash
    $userStmt = $pdo->prepare("SELECT cash FROM users WHERE userName = ?");
    $userStmt->execute([$_SESSION["username"]]);
    $userCash = $userStmt->fetchColumn();
?>
    <div class="sidebar" id="sidebar" style="display: flex; flex-direction: column; height: 100vh;">
        <?php include "sidebar.php"; ?>
    </div>
    <button class="sidebar-toggle" id="sidebar-toggle" title="Hide sidebar">⮜</button>
<?php } ?>

<div class="content">
    <h1>Welcome, <?= isset($_SESSION["username"]) ? htmlspecialchars($_SESSION["username"]) : 'Guest' ?>!</h1>
    <?php if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true): ?>
        <div style="margin-bottom: 24px; text-align: right;">
            <a href="login.php" style="background:#0071e3; color:#fff; padding:10px 24px; border-radius:8px; text-decoration:none; font-weight:600; font-size:1rem; box-shadow:0 2px 8px 0 rgba(0,0,0,0.08);">Login</a>
        </div>
    <?php endif; ?>
    <div style="max-width: 600px; margin-bottom: 30px;">
        <label for="category" style="font-weight: bold;">Category:</label>
        <select id="category" style="width: 100%; padding: 8px; margin-bottom: 15px;">
            <option value="all">All</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat['name']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" id="search" placeholder="Search products..." style="width: 100%; padding: 8px; margin-bottom: 20px;">
    </div>
    <div id="product-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px;"></div>
</div>
<script id="products-json" type="application/json">
<?= json_encode($products) ?>
</script>
<script>
function fetchProducts(category, search, callback) {
    const params = new URLSearchParams({ category, search });
    fetch('index.php?' + params)
        .then(res => res.text())
        .then(html => {
            // Parse products from a hidden JSON block
            const match = html.match(/<script id=\"products-json\" type=\"application\/json\">([\s\S]*?)<\/script>/);
            if (match) {
                callback(JSON.parse(match[1]));
            } else {
                callback([]);
            }
        });
}

function renderProducts(products) {
    const list = document.getElementById('product-list');
    if (products.length === 0) {
        list.innerHTML = '<div style="grid-column: 1/-1; text-align: center; color: #888;">No products found.</div>';
        return;
    }
    list.innerHTML = products.map(p => `
        <div class="product-item" data-id="${p.id}">
            <img src="${p.image}" alt="${p.name}" />
            <div class="name">${p.name}</div>
            <div class="category">${p.category.charAt(0).toUpperCase() + p.category.slice(1)}</div>
            <div class="price">$${p.price}</div>
            <div class="description">${p.description}</div>
            <div class="vendor" style="color:#0071e3; font-size:0.98rem; margin-top:8px; font-weight:500;">Vendor: ${p.vendor}</div>
        </div>
    `).join('');
    patchProductClick(products);
}

// Add popup HTML and JS
function showSignInPopup() {
    if (document.getElementById('signin-popup')) return;
    const popup = document.createElement('div');
    popup.id = 'signin-popup';
    popup.style.position = 'fixed';
    popup.style.top = '0';
    popup.style.left = '0';
    popup.style.width = '100vw';
    popup.style.height = '100vh';
    popup.style.background = 'rgba(0,0,0,0.4)';
    popup.style.display = 'flex';
    popup.style.alignItems = 'center';
    popup.style.justifyContent = 'center';
    popup.innerHTML = `
        <div style="background:#fff; padding:32px 40px; border-radius:12px; box-shadow:0 4px 24px #0002; text-align:center; min-width:300px;">
            <h2 style="margin-bottom:16px;">Sign in required</h2>
            <p style="margin-bottom:24px;">Please sign in to view product details.</p>
            <a href="login.php" style="background:#009688; color:#fff; padding:10px 24px; border-radius:6px; text-decoration:none; font-weight:bold;">Sign In</a>
            <div style="margin-top:16px;"><a href="#" id="close-signin-popup" style="color:#888; text-decoration:underline;">Cancel</a></div>
        </div>
    `;
    document.body.appendChild(popup);
    document.getElementById('close-signin-popup').onclick = function(e) {
        e.preventDefault();
        popup.remove();
    };
}

// Add purchase system JS
function showPurchasePopup(product) {
    if (document.getElementById('purchase-popup')) return;
    const popup = document.createElement('div');
    popup.id = 'purchase-popup';
    popup.style.position = 'fixed';
    popup.style.top = '0';
    popup.style.left = '0';
    popup.style.width = '100vw';
    popup.style.height = '100vh';
    popup.style.background = 'rgba(0,0,0,0.4)';
    popup.style.display = 'flex';
    popup.style.alignItems = 'center';
    popup.style.justifyContent = 'center';
    popup.innerHTML = `
        <div style="background:#fff; padding:32px 40px; border-radius:12px; box-shadow:0 4px 24px #0002; text-align:center; min-width:320px;">
            <h2 style="margin-bottom:16px;">Buy ${product.name}</h2>
            <img src="${product.image}" style="width:120px;height:120px;object-fit:cover;border-radius:8px;margin-bottom:12px;" />
            <div style="margin-bottom:12px;">Price: <b>$${product.price}</b></div>
            <div style="margin-bottom:12px;">Vendor: <b>${product.vendor}</b></div>
            <input type="number" id="purchase-qty" value="1" min="1" style="width:60px; padding:6px; border-radius:6px; border:1px solid #ccc;"> Quantity
            <div id="purchase-error" style="color:#c00; margin:10px 0 0 0;"></div>
            <div style="margin-top:24px;">
                <button id="confirm-purchase" style="background:#0071e3;color:#fff;padding:10px 24px;border-radius:6px;border:none;font-weight:bold;">Confirm</button>
                <button id="cancel-purchase" style="margin-left:16px;color:#888;background:#eee;padding:10px 24px;border-radius:6px;border:none;font-weight:bold;">Cancel</button>
            </div>
        </div>
    `;
    document.body.appendChild(popup);
    document.getElementById('cancel-purchase').onclick = function() { popup.remove(); };
    document.getElementById('confirm-purchase').onclick = function() {
        const qty = parseInt(document.getElementById('purchase-qty').value);
        if (qty < 1) { document.getElementById('purchase-error').textContent = 'Quantity must be at least 1.'; return; }
        fetch('purchase.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: product.id, quantity: qty })
        }).then(r => r.json()).then(res => {
            if (res.success) {
                document.getElementById('purchase-error').style.color = '#090';
                document.getElementById('purchase-error').textContent = 'Purchase successful!';
                setTimeout(() => { popup.remove(); location.reload(); }, 1200);
            } else {
                document.getElementById('purchase-error').style.color = '#c00';
                document.getElementById('purchase-error').textContent = res.error || 'Purchase failed.';
            }
        });
    };
}

// Patch product click event for logged-in users
function patchProductClick(products) {
    document.querySelectorAll('.product-item').forEach((item, idx) => {
        item.onclick = function() {
            var loggedIn = <?= isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true ? 'true' : 'false' ?>;
            if (!loggedIn) {
                showSignInPopup();
            } else {
                showPurchasePopup(products[idx]);
            }
        };
    });
}

function autoLoadProducts() {
    const category = document.getElementById('category').value;
    const search = document.getElementById('search').value;
    fetchProducts(category, search, renderProducts);
}

window.addEventListener('DOMContentLoaded', function() {
    // Render initial products from PHP (for first load)
    const products = JSON.parse(document.getElementById('products-json').textContent);
    renderProducts(products);
    // Set up filter/search listeners
    document.getElementById('category').addEventListener('change', autoLoadProducts);
    document.getElementById('search').addEventListener('input', autoLoadProducts);
});

<?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) { ?>
(function() {
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebar-toggle');
    let isHidden = false;

    function updateSidebar() {
        sidebar.classList.toggle('hide', isHidden);
        toggleBtn.innerHTML = isHidden ? '⮞' : '⮜';
    }

    toggleBtn.onclick = function() {
        isHidden = !isHidden;
        updateSidebar();
    };
    updateSidebar();
})();
<?php } ?>
</script>

</body>
</html>
