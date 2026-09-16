
<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/db.php";

$success = $_GET["success"] ?? "";
$error = $_GET["error"] ?? "";

$selected_product = filter_input(INPUT_GET, "product_id", FILTER_VALIDATE_INT);
$selected_type = $_GET["type"] ?? "";

if (!in_array($selected_type, ["Stock In", "Stock Out", "Adjustment"], true)) {
    $selected_type = "";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $product_id = filter_input(INPUT_POST, "product_id", FILTER_VALIDATE_INT);
    $movement_type = $_POST["movement_type"] ?? "";
    $quantity = filter_input(INPUT_POST, "quantity", FILTER_VALIDATE_INT);
    $notes = trim($_POST["notes"] ?? "");

    if (!$product_id) {

        $error = "Please select a product.";

    } elseif (!in_array($movement_type, ["Stock In", "Stock Out", "Adjustment"], true)) {

        $error = "Please select a valid movement type.";

    } elseif ($quantity === false || $quantity < 0) {

        $error = "Please enter a valid quantity.";

    } else {

        $stmt = $conn->prepare("
            SELECT quantity
            FROM products
            WHERE id = ?
        ");

        $stmt->bind_param("i", $product_id);
        $stmt->execute();

        $product = $stmt->get_result()->fetch_assoc();

        if (!$product) {

            $error = "Selected product was not found.";

        } else {

            $previous_quantity = (int)$product["quantity"];

            if ($movement_type === "Stock In") {

                $new_quantity = $previous_quantity + $quantity;

            } elseif ($movement_type === "Stock Out") {

                if ($quantity > $previous_quantity) {

                    $error = "Stock Out quantity cannot exceed available stock.";

                } else {

                    $new_quantity = $previous_quantity - $quantity;
                }

            } else {

                $new_quantity = $quantity;
            }

            if ($error === "") {

                $conn->begin_transaction();

                try {

                    $update = $conn->prepare("
                        UPDATE products
                        SET quantity = ?
                        WHERE id = ?
                    ");

                    $update->bind_param(
                        "ii",
                        $new_quantity,
                        $product_id
                    );

                    $update->execute();

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
                        $product_id,
                        $movement_type,
                        $quantity,
                        $previous_quantity,
                        $new_quantity,
                        $notes,
                        $_SESSION["user_id"]
                    );

                    $movement->execute();

                    $conn->commit();

                    header(
                        "Location: stock_management.php?success=" .
                        urlencode("Stock transaction recorded successfully.")
                    );

                    exit;

                } catch (Exception $e) {

                    $conn->rollback();

                    $error = "Unable to complete the stock transaction.";
                }
            }
        }
    }

    $selected_product = $product_id;
    $selected_type = $movement_type;
}

$products = $conn->query("
    SELECT
        id,
        product_name,
        sku,
        quantity,
        minimum_stock
    FROM products
    ORDER BY product_name ASC
");

$movements = $conn->query("
    SELECT
        stock_movements.id,
        products.product_name,
        products.sku,
        stock_movements.movement_type,
        stock_movements.quantity,
        stock_movements.previous_quantity,
        stock_movements.new_quantity,
        stock_movements.notes,
        users.full_name,
        stock_movements.created_at
    FROM stock_movements
    INNER JOIN products
        ON stock_movements.product_id = products.id
    LEFT JOIN users
        ON stock_movements.created_by = users.id
    ORDER BY stock_movements.created_at DESC
    LIMIT 15
");

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
        Stock Management | Inventory Management System
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
                class="nav-link active"
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
                class="nav-link"
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
                    Stock Management
                </h1>

                <p>
                    Record and monitor inventory stock movements.
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


        <section class="stock-management-container">


            <?php if ($success !== ""): ?>

                <div class="alert alert-success">

                    <?php
                    echo htmlspecialchars($success);
                    ?>

                </div>

            <?php endif; ?>


            <?php if ($error !== ""): ?>

                <div class="alert alert-error">

                    <?php
                    echo htmlspecialchars($error);
                    ?>

                </div>

            <?php endif; ?>


            <div class="stock-management-grid">


                <div class="panel stock-form-panel">

                    <div class="panel-header">

                        <div>

                            <h3>
                                Record Stock Movement
                            </h3>

                            <p>
                                Update inventory quantities.
                            </p>

                        </div>

                    </div>


                    <form
                        method="POST"
                        action="stock_management.php"
                        class="stock-form"
                    >


                        <div class="form-group">

                            <label>
                                Product <span>*</span>
                            </label>

                            <select
                                name="product_id"
                                class="form-control"
                                required
                            >

                                <option value="">
                                    Select Product
                                </option>

                                <?php if ($products): ?>

                                    <?php while ($product = $products->fetch_assoc()): ?>

                                        <option
                                            value="<?php echo $product["id"]; ?>"
                                            <?php
                                            echo $selected_product == $product["id"]
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
                                            (Stock:
                                            <?php
                                            echo number_format(
                                                $product["quantity"]
                                            );
                                            ?>
                                            )
                                        </option>

                                    <?php endwhile; ?>

                                <?php endif; ?>

                            </select>

                        </div>


                        <div class="form-group">

                            <label>
                                Movement Type <span>*</span>
                            </label>

                            <select
                                name="movement_type"
                                class="form-control"
                                required
                            >

                                <option value="">
                                    Select Movement
                                </option>

                                <option
                                    value="Stock In"
                                    <?php
                                    echo $selected_type === "Stock In"
                                        ? "selected"
                                        : "";
                                    ?>
                                >
                                    Stock In
                                </option>

                                <option
                                    value="Stock Out"
                                    <?php
                                    echo $selected_type === "Stock Out"
                                        ? "selected"
                                        : "";
                                    ?>
                                >
                                    Stock Out
                                </option>

                                <option
                                    value="Adjustment"
                                    <?php
                                    echo $selected_type === "Adjustment"
                                        ? "selected"
                                        : "";
                                    ?>
                                >
                                    Adjustment
                                </option>

                            </select>

                        </div>


                        <div class="form-group">

                            <label>
                                Quantity <span>*</span>
                            </label>

                            <input
                                type="number"
                                name="quantity"
                                class="form-control"
                                min="0"
                                required
                            >

                            <small class="field-help">
                                For Adjustment, enter the new total stock quantity.
                            </small>

                        </div>


                        <div class="form-group">

                            <label>
                                Notes
                            </label>

                            <textarea
                                name="notes"
                                class="form-control stock-notes"
                                rows="4"
                                placeholder="Enter optional notes..."
                            ></textarea>

                        </div>


                        <div class="form-actions stock-form-actions">

                            <button
                                type="reset"
                                class="button button-secondary"
                            >
                                Clear
                            </button>

                            <button
                                type="submit"
                                class="button button-primary"
                            >
                                Record Movement
                            </button>

                        </div>

                    </form>

                </div>


                <div class="panel stock-summary-panel">

                    <div class="panel-header">

                        <div>

                            <h3>
                                Stock Operations
                            </h3>

                            <p>
                                Understand how each movement works.
                            </p>

                        </div>

                    </div>


                    <div class="stock-operation">

                        <div class="operation-icon stock-in-icon">
                            ↓
                        </div>

                        <div>

                            <strong>
                                Stock In
                            </strong>

                            <p>
                                Adds incoming inventory to the current stock.
                            </p>

                        </div>

                    </div>


                    <div class="stock-operation">

                        <div class="operation-icon stock-out-icon">
                            ↑
                        </div>

                        <div>

                            <strong>
                                Stock Out
                            </strong>

                            <p>
                                Removes inventory when products are issued or sold.
                            </p>

                        </div>

                    </div>


                    <div class="stock-operation">

                        <div class="operation-icon adjustment-icon">
                            ↕
                        </div>

                        <div>

                            <strong>
                                Adjustment
                            </strong>

                            <p>
                                Sets the stock to a corrected total quantity.
                            </p>

                        </div>

                    </div>

                </div>

            </div>


            <section class="panel movements-panel stock-movements-section">

                <div class="panel-header">

                    <div>

                        <h3>
                            Recent Stock Movements
                        </h3>

                        <p>
                            Latest inventory transactions.
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

                        <?php if ($movements && $movements->num_rows > 0): ?>

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
                                    No stock movements recorded yet.
                                </td>

                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </section>

        </section>

    </main>

</div>

</body>

</html>

