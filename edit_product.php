
<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/db.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: products.php");
    exit;
}

$categories = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
$suppliers = $conn->query("SELECT id, name FROM suppliers ORDER BY name ASC");

$stmt = $conn->prepare("
    SELECT
        id,
        product_name,
        sku,
        category_id,
        supplier_id,
        price,
        quantity,
        minimum_stock
    FROM products
    WHERE id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header("Location: products.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $product_name = trim($_POST["product_name"] ?? "");
    $sku = trim($_POST["sku"] ?? "");
    $category_id = filter_input(INPUT_POST, "category_id", FILTER_VALIDATE_INT);
    $supplier_id = filter_input(INPUT_POST, "supplier_id", FILTER_VALIDATE_INT);
    $price = $_POST["price"] ?? "";
    $quantity = filter_input(INPUT_POST, "quantity", FILTER_VALIDATE_INT);
    $minimum_stock = filter_input(INPUT_POST, "minimum_stock", FILTER_VALIDATE_INT);

    if ($product_name === "" || $sku === "") {
        $error = "Product name and SKU are required.";
    } elseif (!is_numeric($price) || $price < 0) {
        $error = "Please enter a valid price.";
    } elseif ($quantity === false || $quantity < 0) {
        $error = "Please enter a valid stock quantity.";
    } elseif ($minimum_stock === false || $minimum_stock < 0) {
        $error = "Please enter a valid minimum stock level.";
    } else {

        $duplicate = $conn->prepare("
            SELECT id
            FROM products
            WHERE sku = ? AND id != ?
        ");

        $duplicate->bind_param("si", $sku, $id);
        $duplicate->execute();

        if ($duplicate->get_result()->num_rows > 0) {
            $error = "Another product already uses this SKU.";
        } else {

            $old_quantity = (int)$product["quantity"];
            $new_quantity = (int)$quantity;

            $conn->begin_transaction();

            try {

                $update = $conn->prepare("
                    UPDATE products
                    SET
                        product_name = ?,
                        sku = ?,
                        category_id = ?,
                        supplier_id = ?,
                        price = ?,
                        quantity = ?,
                        minimum_stock = ?
                    WHERE id = ?
                ");

                $update->bind_param(
                    "ssiidiii",
                    $product_name,
                    $sku,
                    $category_id,
                    $supplier_id,
                    $price,
                    $new_quantity,
                    $minimum_stock,
                    $id
                );

                $update->execute();

                if ($old_quantity !== $new_quantity) {

                    $movement_type = "Adjustment";
                    $movement_quantity = abs($new_quantity - $old_quantity);
                    $notes = "Stock quantity updated while editing product.";

                    $movement = $conn->prepare("
                        INSERT INTO stock_movements
                        (
                            product_id,
                            movement_type,
                            quantity,
                            previous_quantity,
                            new_quantity,
                            notes,
                            created_by
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");

                    $movement->bind_param(
                        "isiiisi",
                        $id,
                        $movement_type,
                        $movement_quantity,
                        $old_quantity,
                        $new_quantity,
                        $notes,
                        $_SESSION["user_id"]
                    );

                    $movement->execute();
                }

                $conn->commit();

                header("Location: products.php?success=" . urlencode("Product updated successfully."));
                exit;

            } catch (Exception $e) {

                $conn->rollback();
                $error = "Unable to update the product. Please try again.";
            }
        }
    }

    $product["product_name"] = $product_name;
    $product["sku"] = $sku;
    $product["category_id"] = $category_id;
    $product["supplier_id"] = $supplier_id;
    $product["price"] = $price;
    $product["quantity"] = $quantity;
    $product["minimum_stock"] = $minimum_stock;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product | Inventory Management System</title>
    <link rel="stylesheet" href="css/style.css?v=2">
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
                <h1>Edit Product</h1>
                <p>Update product information and stock details.</p>
            </div>

            <div class="header-user">
                <span>Welcome,</span>
                <strong>
                    <?php echo htmlspecialchars($_SESSION["full_name"]); ?>
                </strong>
            </div>

        </header>

        <section class="product-form-container">

            <?php if ($error !== ""): ?>

                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>

            <?php endif; ?>

            <div class="product-form-card">

                <div class="form-card-header">

                    <div class="form-title-area">

                        <div class="form-title-icon">
                            ✎
                        </div>

                        <div>
                            <h2>Edit Product</h2>
                            <p>Modify the details of this inventory item.</p>
                        </div>

                    </div>

                    <a href="products.php" class="back-link">
                        ← Back to Products
                    </a>

                </div>

                <form method="POST" action="edit_product.php?id=<?php echo $id; ?>">

                    <div class="form-section">

                        <h3 class="form-section-title">
                            Basic Information
                        </h3>

                        <div class="form-grid">

                            <div class="form-group">

                                <label>
                                    Product Name <span>*</span>
                                </label>

                                <input
                                    type="text"
                                    name="product_name"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($product["product_name"]); ?>"
                                    required
                                >

                            </div>

                            <div class="form-group">

                                <label>
                                    SKU <span>*</span>
                                </label>

                                <input
                                    type="text"
                                    name="sku"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($product["sku"]); ?>"
                                    required
                                >

                            </div>

                            <div class="form-group">

                                <label>
                                    Category
                                </label>

                                <select name="category_id" class="form-control">

                                    <option value="">
                                        Select Category
                                    </option>

                                    <?php while ($category = $categories->fetch_assoc()): ?>

                                        <option
                                            value="<?php echo $category["id"]; ?>"
                                            <?php echo $product["category_id"] == $category["id"] ? "selected" : ""; ?>
                                        >
                                            <?php echo htmlspecialchars($category["name"]); ?>
                                        </option>

                                    <?php endwhile; ?>

                                </select>

                            </div>

                            <div class="form-group">

                                <label>
                                    Supplier
                                </label>

                                <select name="supplier_id" class="form-control">

                                    <option value="">
                                        Select Supplier
                                    </option>

                                    <?php while ($supplier = $suppliers->fetch_assoc()): ?>

                                        <option
                                            value="<?php echo $supplier["id"]; ?>"
                                            <?php echo $product["supplier_id"] == $supplier["id"] ? "selected" : ""; ?>
                                        >
                                            <?php echo htmlspecialchars($supplier["name"]); ?>
                                        </option>

                                    <?php endwhile; ?>

                                </select>

                            </div>

                        </div>

                    </div>

                    <div class="form-section">

                        <h3 class="form-section-title">
                            Stock Information
                        </h3>

                        <div class="form-grid">

                            <div class="form-group">

                                <label>
                                    Price <span>*</span>
                                </label>

                                <div class="input-prefix">

                                    <span>KES</span>

                                    <input
                                        type="number"
                                        name="price"
                                        class="form-control"
                                        min="0"
                                        step="0.01"
                                        value="<?php echo htmlspecialchars($product["price"]); ?>"
                                        required
                                    >

                                </div>

                            </div>

                            <div class="form-group">

                                <label>
                                    Current Quantity <span>*</span>
                                </label>

                                <input
                                    type="number"
                                    name="quantity"
                                    class="form-control"
                                    min="0"
                                    value="<?php echo htmlspecialchars($product["quantity"]); ?>"
                                    required
                                >

                                <small class="field-help">
                                    Changing this value will create a stock adjustment record.
                                </small>

                            </div>

                            <div class="form-group">

                                <label>
                                    Minimum Stock Level <span>*</span>
                                </label>

                                <input
                                    type="number"
                                    name="minimum_stock"
                                    class="form-control"
                                    min="0"
                                    value="<?php echo htmlspecialchars($product["minimum_stock"]); ?>"
                                    required
                                >

                                <small class="field-help">
                                    Used to determine when stock is considered low.
                                </small>

                            </div>

                        </div>

                    </div>

                    <div class="form-footer">

                        <div class="required-note">
                            <span>*</span> Required fields
                        </div>

                        <div class="form-actions">

                            <a href="products.php" class="button button-secondary">
                                Cancel
                            </a>

                            <button type="submit" class="button button-primary">
                                Save Changes
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
