<?php
if (!isset($_SESSION)) session_start();

$roleId = $_SESSION['roleId'] ?? null;
?>

<style>
.sidebar {
    width: 200px;
    background-color: #f8f9fa;
    padding: 15px;
    height: 100vh;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.sidebar h3 {
    margin-top: 0;
    margin-bottom: 1rem;
}

.sidebar ul {
    list-style-type: none;
    padding: 0;
    margin: 0;
}

.sidebar ul li {
    margin-bottom: 10px;
}

.sidebar ul li a {
    text-decoration: none;
    color: #333;
    font-weight: 600;
}

.sidebar ul li a:hover {
    color: #007BFF;
}

.title-side-bar h2 {
    color: #dc3545;
    text-align: center;
}

.logout-button {
    background-color: #dc3545; /* Bootstrap red */
    color: white;
    border: none;
    padding: 10px;
    border-radius: 5px;
    font-weight: 600;
    cursor: pointer;
    width: 80%;
    text-align: center;
    text-decoration: none;
    display: block;
}

.logout-button:hover {
    background-color: #b02a37;
}
</style>

<div class="sidebar">
    <div>
        <div class="title-side-bar">
            <h2>kaiTOP</h2>
        </div>
        <ul>
            <?php if ($roleId == 3): // Admin ?>
                <li><a href="admin_dashboard.php">Admin Dashboard</a></li>
            <?php elseif ($roleId == 2): // Vendor ?>
                <li><a href="vendor_dashboard.php">Vendor Dashboard</a></li>
                <li><a href="store.php">Store Page</a></li>
                <li><a href="support.php">Support</a></li>
                <li><a href="settings.php">Settings</a></li>
            <?php elseif ($roleId == 1): // Customer ?>
                <li><a href="store.php">Store Page</a></li>
                <li><a href="support.php">Support</a></li>
                <li><a href="settings.php">Settings</a></li>
            <?php else: ?>
                <li>No access</li>
            <?php endif; ?>
        </ul>
    </div>

    <div>
        <a href="logout.php" class="logout-button">Logout</a>
    </div>
</div>
