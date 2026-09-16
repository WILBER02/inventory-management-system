<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/db.php";

$product_name = "";
$sku = "";
$category_id = "";
$supplier_id = "";
$price = "";
$quantity = "";
$minimum_stock = "";

$error = "";

$categories = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
$suppliers = $conn->query("SELECT id, name FROM suppliers ORDER BY name ASC");

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $product_name = trim($_POST["product_name"] ?? "");
    $sku = trim($_POST["sku"] ?? "");
    $category_id = $_POST["category_id"] ?? "";
    $supplier_id = $_POST["supplier_id"] ?? "";
    $price = trim($_POST["price"] ?? "");
    $quantity = trim($_POST["quantity"] ?? "");
    $minimum_stock = trim($_POST["minimum_stock"] ?? "");

    if (
        $product_name === "" ||
        $sku === "" ||
        $price === "" ||
        $quantity === "" ||
        $minimum_stock === ""
    ) {
        $error = "Please fill in all required fields.";
    } elseif (!is_numeric($price) || $price < 0) {
        $error = "Please enter a valid price.";
    } elseif (!ctype_digit($quantity)) {
        $error = "Quantity must be a whole number.";
    } elseif (!ctype_digit($minimum_stock)) {
        $error = "Minimum stock must be a whole number.";
    } else {

        $check = $conn->prepare("SELECT id FROM products WHERE sku = ?");
        $check->bind_param("s", $sku);
        $check->execute();

        $existing = $check->get_result();

        if ($existing->num_rows > 0) {
            $error = "A product with this SKU already exists.";
        } else {

            $category_value = $category_id !== "" ? (int)$category_id : null;
            $supplier_value = $supplier_id !== "" ? (int)$supplier_id : null;
            $price_value = (float)$price;
            $quantity_value = (int)$quantity;
            $minimum_stock_value = (int)$minimum_stock;

            $stmt = $conn->prepare("
                INSERT INTO products
                (
                    product_name,
                    sku,
                    category_id,
                    supplier_id,
                    price,
                    quantity,
                    minimum_stock
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "ssiiddi",
                $product_name,
                $sku,
                $category_value,
                $supplier_value,
                $price_value,
                $quantity_value,
                $minimum_stock_value
            );

            if ($stmt->execute()) {
                header("Location: products.php?success=Product+added+successfully");
                exit;
            }

            $error = "Unable to add the product. Please try again.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product | Inventory Management System</title>
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

            <a href="dashboard.php" class="nav-link">
                <span>▣</span>
                Dashboard
            </a>

            <a href="products.php" class="nav-link active">
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
                <h1>Add Product</h1>
                <p>Create a new inventory product.</p>
            </div>

            <div class="header-user">
                <span>Welcome,</span>
                <strong>
                    <?php echo htmlspecialchars($_SESSION["full_name"]); ?>
                </strong>
            </div>

        </header>


        <section class="product-form-container">

            <div class="product-form-card">

                <div class="form-card-header">

                    <div class="form-title-area">

                        <div class="form-title-icon">
                            +
                        </div>

                        <div>
                            <h2>Product Information</h2>
                            <p>Enter the details of the product you want to add to your inventory.</p>
                        </div>

                    </div>

                    <a href="products.php" class="back-link">
                        ← Back to Products
                    </a>

                </div>


                <?php if ($error !== ""): ?>

                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>

                <?php endif; ?>


                <form method="POST" action="add_product.php">

                    <div class="form-section">

                        <div class="form-section-title">
                            Basic Information
                        </div>

                        <div class="form-grid">

                            <div class="form-group">

                                <label for="product_name">
                                    Product Name
                                    <span>*</span>
                                </label>

                                <input
                                    type="text"
                                    id="product_name"
                                    name="product_name"
                                    class="form-control"
                                    placeholder="e.g. HP Laptop"
                                    value="<?php echo htmlspecialchars($product_name); ?>"
                                    required
                                >

                            </div>


                            <div class="form-group">

                                <label for="sku">
                                    SKU
                                    <span>*</span>
                                </label>

                                <input
                                    type="text"
                                    id="sku"
                                    name="sku"
                                    class="form-control"
                                    placeholder="e.g. HP-LAP-002"
                                    value="<?php echo htmlspecialchars($sku); ?>"
                                    required
                                >

                            </div>


                            <div class="form-group">

                                <label for="category_id">
                                    Category
                                </label>

                                <select
                                    id="category_id"
                                    name="category_id"
                                    class="form-control"
                                >

                                    <option value="">
                                        Select category
                                    </option>

                                    <?php if ($categories): ?>

                                        <?php while ($category_row = $categories->fetch_assoc()): ?>

                                            <option
                                                value="<?php echo $category_row["id"]; ?>"
                                                <?php echo $category_id == $category_row["id"] ? "selected" : ""; ?>
                                            >
                                                <?php echo htmlspecialchars($category_row["name"]); ?>
                                            </option>

                                        <?php endwhile; ?>

                                    <?php endif; ?>

                                </select>

                            </div>


                            <div class="form-group">

                                <label for="supplier_id">
                                    Supplier
                                </label>

                                <select
                                    id="supplier_id"
                                    name="supplier_id"
                                    class="form-control"
                                >

                                    <option value="">
                                        Select supplier
                                    </option>

                                    <?php if ($suppliers): ?>

                                        <?php while ($supplier_row = $suppliers->fetch_assoc()): ?>

                                            <option
                                                value="<?php echo $supplier_row["id"]; ?>"
                                                <?php echo $supplier_id == $supplier_row["id"] ? "selected" : ""; ?>
                                            >
                                                <?php echo htmlspecialchars($supplier_row["name"]); ?>
                                            </option>

                                        <?php endwhile; ?>

                                    <?php endif; ?>

                                </select>

                            </div>

                        </div>

                    </div>


                    <div class="form-section">

                        <div class="form-section-title">
                            Stock Information
                        </div>

                        <div class="form-grid">

                            <div class="form-group">

                                <label for="price">
                                    Unit Price
                                    <span>*</span>
                                </label>

                                <div class="input-prefix">

                                    <span>KES</span>

                                    <input
                                        type="number"
                                        id="price"
                                        name="price"
                                        class="form-control"
                                        placeholder="0.00"
                                        min="0"
                                        step="0.01"
                                        value="<?php echo htmlspecialchars($price); ?>"
                                        required
                                    >

                                </div>

                            </div>


                            <div class="form-group">

                                <label for="quantity">
                                    Initial Quantity
                                    <span>*</span>
                                </label>

                                <input
                                    type="number"
                                    id="quantity"
                                    name="quantity"
                                    class="form-control"
                                    placeholder="0"
                                    min="0"
                                    step="1"
                                    value="<?php echo htmlspecialchars($quantity); ?>"
                                    required
                                >

                            </div>


                            <div class="form-group">

                                <label for="minimum_stock">
                                    Minimum Stock Level
                                    <span>*</span>
                                </label>

                                <input
                                    type="number"
                                    id="minimum_stock"
                                    name="minimum_stock"
                                    class="form-control"
                                    placeholder="5"
                                    min="0"
                                    step="1"
                                    value="<?php echo htmlspecialchars($minimum_stock); ?>"
                                    required
                                >

                                <small class="field-help">
                                    The system will identify the product as low stock when it reaches this level.
                                </small>

                            </div>

                        </div>

                    </div>


                    <div class="form-footer">

                        <div class="required-note">
                            <span>*</span>
                            Required fields
                        </div>

                        <div class="form-actions">

                            <a href="products.php" class="button button-secondary">
                                Cancel
                            </a>

                            <button type="submit" class="button button-primary">
                                Save Product
                            </button>

                        </div>

                    </div>

                </form>

            </div>

        </section>

    </main>

</div>

</body>
</html>