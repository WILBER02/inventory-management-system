
<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/db.php";

$movement_type = $_GET["movement_type"] ?? "";
$product_id = filter_input(INPUT_GET, "product_id", FILTER_VALIDATE_INT);
$date_from = $_GET["date_from"] ?? "";
$date_to = $_GET["date_to"] ?? "";

if (!in_array($movement_type, ["Stock In", "Stock Out", "Adjustment"], true)) {
    $movement_type = "";
}

$products = $conn->query("
    SELECT
        id,
        product_name,
        sku
    FROM products
    ORDER BY product_name ASC
");

$total_products = 0;
$total_stock = 0;
$low_stock = 0;
$out_of_stock = 0;
$inventory_value = 0;
$total_stock_in = 0;
$total_stock_out = 0;
$total_adjustments = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM products
");

if ($result) {
    $total_products = (int)$result->fetch_assoc()["total"];
}

$result = $conn->query("
    SELECT COALESCE(SUM(quantity), 0) AS total
    FROM products
");

if ($result) {
    $total_stock = (int)$result->fetch_assoc()["total"];
}

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM products
    WHERE quantity > 0
    AND quantity <= minimum_stock
");

if ($result) {
    $low_stock = (int)$result->fetch_assoc()["total"];
}

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM products
    WHERE quantity = 0
");

if ($result) {
    $out_of_stock = (int)$result->fetch_assoc()["total"];
}

$result = $conn->query("
    SELECT COALESCE(SUM(price * quantity), 0) AS total
    FROM products
");

if ($result) {
    $inventory_value = (float)$result->fetch_assoc()["total"];
}

$result = $conn->query("
    SELECT COALESCE(SUM(quantity), 0) AS total
    FROM stock_movements
    WHERE movement_type = 'Stock In'
");

if ($result) {
    $total_stock_in = (int)$result->fetch_assoc()["total"];
}

$result = $conn->query("
    SELECT COALESCE(SUM(quantity), 0) AS total
    FROM stock_movements
    WHERE movement_type = 'Stock Out'
");

if ($result) {
    $total_stock_out = (int)$result->fetch_assoc()["total"];
}

$result = $conn->query("
    SELECT COALESCE(SUM(quantity), 0) AS total
    FROM stock_movements
    WHERE movement_type = 'Adjustment'
");

if ($result) {
    $total_adjustments = (int)$result->fetch_assoc()["total"];
}

$sql = "
    SELECT
        stock_movements.id,
        products.product_name,
        products.sku,
        stock_movements.movement_type,
        stock_movements.quantity,
        stock_movements.previous_quantity,
        stock_movements.new_quantity,
        users.full_name,
        stock_movements.created_at
    FROM stock_movements
    INNER JOIN products
        ON stock_movements.product_id = products.id
    LEFT JOIN users
        ON stock_movements.created_by = users.id
    WHERE 1=1
";

$params = [];
$types = "";

if ($movement_type !== "") {
    $sql .= " AND stock_movements.movement_type = ?";
    $params[] = $movement_type;
    $types .= "s";
}

if ($product_id) {
    $sql .= " AND stock_movements.product_id = ?";
    $params[] = $product_id;
    $types .= "i";
}

if ($date_from !== "") {
    $sql .= " AND DATE(stock_movements.created_at) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if ($date_to !== "") {
    $sql .= " AND DATE(stock_movements.created_at) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$sql .= " ORDER BY stock_movements.created_at DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$movements = $stmt->get_result();

$filtered_movements = $movements->num_rows;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Reports | Inventory Management System
    </title>

    <link
        rel="stylesheet"
        href="css/style.css?v=4"
    >

</head>

<body>

<div class="dashboard-layout">

    <aside class="sidebar">

        <div class="sidebar-logo">

            <h2>
                Inventory
            </h2>

            <span>
                Management System
            </span>

        </div>


        <nav class="sidebar-nav">

            <a
                href="dashboard.php"
                class="nav-link"
            >
                <span>▣</span>
                Dashboard
            </a>


            <a
                href="products.php"
                class="nav-link"
            >
                <span>▤</span>
                Products
            </a>


            <a
                href="stock_management.php"
                class="nav-link"
            >
                <span>↕</span>
                Stock Management
            </a>


            <a
                href="categories.php"
                class="nav-link"
            >
                <span>▥</span>
                Categories
            </a>


            <a
                href="suppliers.php"
                class="nav-link"
            >
                <span>◉</span>
                Suppliers
            </a>


            <a
                href="reports.php"
                class="nav-link active"
            >
                <span>▤</span>
                Reports
            </a>

        </nav>


        <div class="sidebar-bottom">

            <div class="user-box">

                <div class="user-avatar">

                    <?php
                    echo strtoupper(
                        substr(
                            $_SESSION["full_name"],
                            0,
                            1
                        )
                    );
                    ?>

                </div>


                <div>

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $_SESSION["full_name"]
                        );
                        ?>
                    </strong>

                    <small>
                        <?php
                        echo htmlspecialchars(
                            $_SESSION["role"]
                        );
                        ?>
                    </small>

                </div>

            </div>


            <a
                href="logout.php"
                class="logout-link"
            >
                Logout
            </a>

        </div>

    </aside>


    <main class="main-content">

        <header class="top-header">

            <div>

                <h1>
                    Reports
                </h1>

                <p>
                    Analyze inventory levels and stock movement activity.
                </p>

            </div>


            <div class="header-user">

                <span>
                    Welcome,
                </span>

                <strong>
                    <?php
                    echo htmlspecialchars(
                        $_SESSION["full_name"]
                    );
                    ?>
                </strong>

            </div>

        </header>


        <section class="reports-container">


            <div class="report-summary-grid">

                <div class="report-card">

                    <span>
                        Total Products
                    </span>

                    <strong>
                        <?php
                        echo number_format($total_products);
                        ?>
                    </strong>

                </div>


                <div class="report-card">

                    <span>
                        Total Stock
                    </span>

                    <strong>
                        <?php
                        echo number_format($total_stock);
                        ?>
                    </strong>

                </div>


                <div class="report-card">

                    <span>
                        Inventory Value
                    </span>

                    <strong>
                        KES
                        <?php
                        echo number_format(
                            $inventory_value,
                            2
                        );
                        ?>
                    </strong>

                </div>


                <div class="report-card">

                    <span>
                        Low Stock
                    </span>

                    <strong>
                        <?php
                        echo number_format($low_stock);
                        ?>
                    </strong>

                </div>


                <div class="report-card">

                    <span>
                        Out of Stock
                    </span>

                    <strong>
                        <?php
                        echo number_format($out_of_stock);
                        ?>
                    </strong>

                </div>


                <div class="report-card">

                    <span>
                        Stock In
                    </span>

                    <strong>
                        <?php
                        echo number_format($total_stock_in);
                        ?>
                    </strong>

                </div>


                <div class="report-card">

                    <span>
                        Stock Out
                    </span>

                    <strong>
                        <?php
                        echo number_format($total_stock_out);
                        ?>
                    </strong>

                </div>


                <div class="report-card">

                    <span>
                        Adjustments
                    </span>

                    <strong>
                        <?php
                        echo number_format($total_adjustments);
                        ?>
                    </strong>

                </div>

            </div>


            <div class="panel report-filter-panel">

                <div class="panel-header">

                    <div>

                        <h3>
                            Report Filters
                        </h3>

                        <p>
                            Filter stock movement records by type, product, or date.
                        </p>

                    </div>

                </div>


                <form
                    method="GET"
                    action="reports.php"
                    class="report-filters"
                >


                    <div class="report-filter-field">

                        <label>
                            Movement Type
                        </label>

                        <select
                            name="movement_type"
                            class="form-control"
                        >

                            <option value="">
                                All Movements
                            </option>

                            <option
                                value="Stock In"
                                <?php
                                echo $movement_type === "Stock In"
                                    ? "selected"
                                    : "";
                                ?>
                            >
                                Stock In
                            </option>

                            <option
                                value="Stock Out"
                                <?php
                                echo $movement_type === "Stock Out"
                                    ? "selected"
                                    : "";
                                ?>
                            >
                                Stock Out
                            </option>

                            <option
                                value="Adjustment"
                                <?php
                                echo $movement_type === "Adjustment"
                                    ? "selected"
                                    : "";
                                ?>
                            >
                                Adjustment
                            </option>

                        </select>

                    </div>


                    <div class="report-filter-field">

                        <label>
                            Product
                        </label>

                        <select
                            name="product_id"
                            class="form-control"
                        >

                            <option value="">
                                All Products
                            </option>

                            <?php if ($products): ?>

                                <?php while ($product = $products->fetch_assoc()): ?>

                                    <option
                                        value="<?php echo $product["id"]; ?>"
                                        <?php
                                        echo $product_id == $product["id"]
                                            ? "selected"
                                            : "";
                                        ?>
                                    >
                                        <?php
                                        echo htmlspecialchars(
                                            $product["product_name"]
                                        );
                                        ?>
                                        -
                                        <?php
                                        echo htmlspecialchars(
                                            $product["sku"]
                                        );
                                        ?>
                                    </option>

                                <?php endwhile; ?>

                            <?php endif; ?>

                        </select>

                    </div>


                    <div class="report-filter-field">

                        <label>
                            From Date
                        </label>

                        <input
                            type="date"
                            name="date_from"
                            class="form-control"
                            value="<?php echo htmlspecialchars($date_from); ?>"
                        >

                    </div>


                    <div class="report-filter-field">

                        <label>
                            To Date
                        </label>

                        <input
                            type="date"
                            name="date_to"
                            class="form-control"
                            value="<?php echo htmlspecialchars($date_to); ?>"
                        >

                    </div>


                    <div class="report-filter-actions">

                        <button
                            type="submit"
                            class="button button-primary"
                        >
                            Apply Filters
                        </button>


                        <a
                            href="reports.php"
                            class="button button-secondary"
                        >
                            Reset
                        </a>


                        <button
                            type="button"
                            class="button button-primary"
                            onclick="window.print()"
                        >
                            Print Report
                        </button>

                    </div>

                </form>

            </div>


            <div class="panel report-movements-panel">

                <div class="panel-header">

                    <div>

                        <h3>
                            Stock Movement Report
                        </h3>

                        <p>
                            <?php
                            echo number_format($filtered_movements);
                            ?>
                            movement<?php echo $filtered_movements == 1 ? "" : "s"; ?>
                            found.
                        </p>

                    </div>

                </div>


                <div class="table-container">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    Product
                                </th>

                                <th>
                                    SKU
                                </th>

                                <th>
                                    Movement
                                </th>

                                <th>
                                    Quantity
                                </th>

                                <th>
                                    Previous
                                </th>

                                <th>
                                    New Stock
                                </th>

                                <th>
                                    Performed By
                                </th>

                                <th>
                                    Date
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php if ($movements->num_rows > 0): ?>

                            <?php while ($movement = $movements->fetch_assoc()): ?>

                                <tr>

                                    <td>

                                        <strong>
                                            <?php
                                            echo htmlspecialchars(
                                                $movement["product_name"]
                                            );
                                            ?>
                                        </strong>

                                    </td>


                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $movement["sku"]
                                        );
                                        ?>

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

                                        <?php
                                        echo number_format(
                                            $movement["quantity"]
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <?php
                                        echo number_format(
                                            $movement["previous_quantity"]
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <?php
                                        echo number_format(
                                            $movement["new_quantity"]
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $movement["full_name"] ?? "System"
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <?php
                                        echo date(
                                            "d M Y, H:i",
                                            strtotime(
                                                $movement["created_at"]
                                            )
                                        );
                                        ?>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>

                                <td
                                    colspan="8"
                                    class="empty-state"
                                >
                                    No stock movements found for the selected filters.
                                </td>

                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </section>

    </main>

</div>

</body>

</html>

