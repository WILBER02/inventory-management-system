
<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/db.php";

$search = trim($_GET["search"] ?? "");
$category = $_GET["category"] ?? "";
$status = $_GET["status"] ?? "";
$success = $_GET["success"] ?? "";
$error = $_GET["error"] ?? "";

$categories = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");

$sql = "
    SELECT
        products.id,
        products.product_name,
        products.sku,
        products.price,
        products.quantity,
        products.minimum_stock,
        categories.name AS category_name,
        suppliers.name AS supplier_name
    FROM products
    LEFT JOIN categories ON products.category_id = categories.id
    LEFT JOIN suppliers ON products.supplier_id = suppliers.id
    WHERE 1=1
";

$params = [];
$types = "";

if ($search !== "") {
    $sql .= " AND (products.product_name LIKE ? OR products.sku LIKE ?)";
    $search_value = "%" . $search . "%";
    $params[] = $search_value;
    $params[] = $search_value;
    $types .= "ss";
}

if ($category !== "") {
    $sql .= " AND products.category_id = ?";
    $params[] = $category;
    $types .= "i";
}

if ($status === "in-stock") {
    $sql .= " AND products.quantity > products.minimum_stock";
} elseif ($status === "low-stock") {
    $sql .= " AND products.quantity > 0 AND products.quantity <= products.minimum_stock";
} elseif ($status === "out-of-stock") {
    $sql .= " AND products.quantity = 0";
}

$sql .= " ORDER BY products.created_at DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$products = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products | Inventory Management System</title>
    <link rel="stylesheet" href="css/style.css?v=3">
</head>

<body>

<div class="dashboard-layout">

    <aside class="sidebar">

        <div class="sidebar-logo">
            <h2>Inventory</h2>
            <span>Management System</span>
        </div>

        <nav class="sidebar-nav">

            <a href="dashboard.php" class="nav-link">
                <span>▣</span>
                Dashboard
            </a>

            <a href="products.php" class="nav-link active">
                <span>▤</span>
                Products
            </a>

            <a href="stock_management.php" class="nav-link">
                <span>↕</span>
                Stock Management
            </a>

            <a href="categories.php" class="nav-link">
                <span>▥</span>
                Categories
            </a>

            <a href="suppliers.php" class="nav-link">
                <span>◉</span>
                Suppliers
            </a>

            <a href="reports.php" class="nav-link">
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
                <h1>Products</h1>
                <p>Manage products and monitor current stock levels.</p>
            </div>

            <div class="header-user">
                <span>Welcome,</span>
                <strong>
                    <?php echo htmlspecialchars($_SESSION["full_name"]); ?>
                </strong>
            </div>

        </header>


        <section class="products-container">

            <?php if ($success !== ""): ?>

                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>

            <?php endif; ?>


            <?php if ($error !== ""): ?>

                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>

            <?php endif; ?>


            <div class="products-toolbar">

                <div>

                    <h2>Product Inventory</h2>

                    <p>
                        <?php echo $products->num_rows; ?>
                        product<?php echo $products->num_rows == 1 ? "" : "s"; ?> found
                    </p>

                </div>

                <a href="add_product.php" class="button button-primary">
                    + Add Product
                </a>

            </div>


            <div class="product-filters">

                <form method="GET" action="products.php">

                    <div class="filter-row">

                        <div class="search-field">

                            <input
                                type="text"
                                name="search"
                                class="form-control"
                                placeholder="Search product or SKU..."
                                value="<?php echo htmlspecialchars($search); ?>"
                            >

                        </div>


                        <select name="category" class="form-control">

                            <option value="">
                                All Categories
                            </option>

                            <?php if ($categories): ?>

                                <?php while ($cat = $categories->fetch_assoc()): ?>

                                    <option
                                        value="<?php echo $cat["id"]; ?>"
                                        <?php echo $category == $cat["id"] ? "selected" : ""; ?>
                                    >
                                        <?php echo htmlspecialchars($cat["name"]); ?>
                                    </option>

                                <?php endwhile; ?>

                            <?php endif; ?>

                        </select>


                        <select name="status" class="form-control">

                            <option value="">
                                All Stock Status
                            </option>

                            <option
                                value="in-stock"
                                <?php echo $status === "in-stock" ? "selected" : ""; ?>
                            >
                                In Stock
                            </option>

                            <option
                                value="low-stock"
                                <?php echo $status === "low-stock" ? "selected" : ""; ?>
                            >
                                Low Stock
                            </option>

                            <option
                                value="out-of-stock"
                                <?php echo $status === "out-of-stock" ? "selected" : ""; ?>
                            >
                                Out of Stock
                            </option>

                        </select>


                        <button type="submit" class="button button-primary">
                            Filter
                        </button>

                        <a href="products.php" class="button button-secondary">
                            Reset
                        </a>

                    </div>

                </form>

            </div>


            <div class="panel products-panel">

                <div class="table-container">

                    <table>

                        <thead>

                            <tr>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Category</th>
                                <th>Supplier</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Minimum</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>

                        </thead>

                        <tbody>

                        <?php if ($products->num_rows > 0): ?>

                            <?php while ($product = $products->fetch_assoc()): ?>

                                <?php

                                if ($product["quantity"] == 0) {
                                    $stock_status = "Out of Stock";
                                    $status_class = "status-out";
                                } elseif ($product["quantity"] <= $product["minimum_stock"]) {
                                    $stock_status = "Low Stock";
                                    $status_class = "status-low";
                                } else {
                                    $stock_status = "In Stock";
                                    $status_class = "status-good";
                                }

                                ?>

                                <tr>

                                    <td>
                                        <strong>
                                            <?php echo htmlspecialchars($product["product_name"]); ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($product["sku"]); ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($product["category_name"] ?? "Uncategorized"); ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($product["supplier_name"] ?? "No Supplier"); ?>
                                    </td>

                                    <td>
                                        KES <?php echo number_format($product["price"], 2); ?>
                                    </td>

                                    <td>
                                        <?php echo number_format($product["quantity"]); ?>
                                    </td>

                                    <td>
                                        <?php echo number_format($product["minimum_stock"]); ?>
                                    </td>

                                    <td>
                                        <span class="<?php echo $status_class; ?>">
                                            <?php echo $stock_status; ?>
                                        </span>
                                    </td>

                                    <td>

                                        <div class="product-actions">

                                            <a
                                                href="edit_product.php?id=<?php echo $product["id"]; ?>"
                                                class="action-edit"
                                            >
                                                Edit
                                            </a>

                                            <a
                                                href="delete_product.php?id=<?php echo $product["id"]; ?>"
                                                class="action-delete"
                                                onclick="return confirm('Are you sure you want to delete this product?');"
                                            >
                                                Delete
                                            </a>

                                        </div>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="9" class="empty-state">
                                    No products found.
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

