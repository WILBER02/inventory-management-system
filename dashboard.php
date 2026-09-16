<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/db.php";

$total_products = 0;
$total_stock = 0;
$low_stock = 0;
$out_of_stock = 0;
$inventory_value = 0;

$result = $conn->query("SELECT COUNT(*) AS total FROM products");

if ($result) {
    $row = $result->fetch_assoc();
    $total_products = $row["total"];
}

$result = $conn->query("SELECT COALESCE(SUM(quantity), 0) AS total FROM products");

if ($result) {
    $row = $result->fetch_assoc();
    $total_stock = $row["total"];
}

$result = $conn->query("SELECT COUNT(*) AS total FROM products WHERE quantity > 0 AND quantity <= minimum_stock");

if ($result) {
    $row = $result->fetch_assoc();
    $low_stock = $row["total"];
}

$result = $conn->query("SELECT COUNT(*) AS total FROM products WHERE quantity = 0");

if ($result) {
    $row = $result->fetch_assoc();
    $out_of_stock = $row["total"];
}

$result = $conn->query("SELECT COALESCE(SUM(price * quantity), 0) AS total FROM products");

if ($result) {
    $row = $result->fetch_assoc();
    $inventory_value = $row["total"];
}

$movements = $conn->query("
    SELECT 
        stock_movements.id,
        products.product_name,
        stock_movements.movement_type,
        stock_movements.quantity,
        stock_movements.previous_quantity,
        stock_movements.new_quantity,
        stock_movements.created_at
    FROM stock_movements
    INNER JOIN products ON stock_movements.product_id = products.id
    ORDER BY stock_movements.created_at DESC
    LIMIT 8
");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Inventory Management System</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<div class="dashboard-layout">

    <aside class="sidebar">

        <div class="sidebar-logo">
            <h2>Inventory</h2>
            <span>Management System</span>
        </div>

        <nav class="sidebar-nav">

            <a href="dashboard.php" class="nav-link active">
                <span>▣</span>
                Dashboard
            </a>

            <a href="products.php" class="nav-link">
                <span>▤</span>
                Products
            </a>

            <a href="#" class="nav-link">
                <span>↕</span>
                Stock Management
            </a>

            <a href="#" class="nav-link">
                <span>▥</span>
                Categories
            </a>

            <a href="#" class="nav-link">
                <span>◉</span>
                Suppliers
            </a>

            <a href="#" class="nav-link">
                <span>▤</span>
                Reports
            </a>

        </nav>

        <div class="sidebar-bottom">

            <div class="user-box">

                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION["full_name"], 0, 1)); ?>
                </div>

                <div>
                    <strong>
                        <?php echo htmlspecialchars($_SESSION["full_name"]); ?>
                    </strong>

                    <small>
                        <?php echo htmlspecialchars($_SESSION["role"]); ?>
                    </small>
                </div>

            </div>

            <a href="logout.php" class="logout-link">
                Logout
            </a>

        </div>

    </aside>


    <main class="main-content">

        <header class="top-header">

            <div>
                <h1>Dashboard</h1>
                <p>Overview of your inventory and stock levels.</p>
            </div>

            <div class="header-user">
                <span>Welcome,</span>
                <strong>
                    <?php echo htmlspecialchars($_SESSION["full_name"]); ?>
                </strong>
            </div>

        </header>


        <section class="stats-grid">

            <div class="stat-card">

                <div class="stat-icon blue">
                    📦
                </div>

                <div>
                    <span>Total Products</span>
                    <h2>
                        <?php echo number_format($total_products); ?>
                    </h2>
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon green">
                    📊
                </div>

                <div>
                    <span>Total Stock</span>
                    <h2>
                        <?php echo number_format($total_stock); ?>
                    </h2>
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon orange">
                    ⚠
                </div>

                <div>
                    <span>Low Stock</span>
                    <h2>
                        <?php echo number_format($low_stock); ?>
                    </h2>
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon red">
                    !
                </div>

                <div>
                    <span>Out of Stock</span>
                    <h2>
                        <?php echo number_format($out_of_stock); ?>
                    </h2>
                </div>

            </div>

        </section>


        <section class="dashboard-grid">

            <div class="panel inventory-value">

                <div class="panel-header">

                    <div>
                        <h3>Inventory Value</h3>
                        <p>Total estimated value of current stock</p>
                    </div>

                </div>

                <div class="value-display">
                    KES <?php echo number_format($inventory_value, 2); ?>
                </div>

            </div>


            <div class="panel quick-actions">

                <div class="panel-header">

                    <div>
                        <h3>Quick Actions</h3>
                        <p>Common inventory operations</p>
                    </div>

                </div>

                <div class="action-buttons">

                    <a href="add_product.php" class="action-button">
                        <span>+</span>
                        Add Product
                    </a>

                    <a href="#" class="action-button">
                        <span>↓</span>
                        Stock In
                    </a>

                    <a href="#" class="action-button">
                        <span>↑</span>
                        Stock Out
                    </a>

                </div>

            </div>

        </section>


        <section class="panel movements-panel">

            <div class="panel-header">

                <div>
                    <h3>Recent Stock Movements</h3>
                    <p>Latest inventory transactions</p>
                </div>

                <a href="#" class="view-all">
                    View All
                </a>

            </div>


            <div class="table-container">

                <table>

                    <thead>

                        <tr>
                            <th>Product</th>
                            <th>Movement</th>
                            <th>Quantity</th>
                            <th>Previous</th>
                            <th>New Stock</th>
                            <th>Date</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php if ($movements && $movements->num_rows > 0): ?>

                        <?php while ($movement = $movements->fetch_assoc()): ?>

                            <tr>

                                <td>
                                    <strong>
                                        <?php echo htmlspecialchars($movement["product_name"]); ?>
                                    </strong>
                                </td>

                                <td>

                                    <?php if ($movement["movement_type"] === "Stock In"): ?>

                                        <span class="movement-badge stock-in">
                                            Stock In
                                        </span>

                                    <?php elseif ($movement["movement_type"] === "Stock Out"): ?>

                                        <span class="movement-badge stock-out">
                                            Stock Out
                                        </span>

                                    <?php else: ?>

                                        <span class="movement-badge adjustment">
                                            Adjustment
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>
                                    <?php echo number_format($movement["quantity"]); ?>
                                </td>

                                <td>
                                    <?php echo number_format($movement["previous_quantity"]); ?>
                                </td>

                                <td>
                                    <?php echo number_format($movement["new_quantity"]); ?>
                                </td>

                                <td>
                                    <?php echo date("d M Y, H:i", strtotime($movement["created_at"])); ?>
                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="6" class="empty-state">
                                No stock movements recorded yet.
                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>

</body>
</html>