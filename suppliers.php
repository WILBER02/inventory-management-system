
<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/db.php";

$search = trim($_GET["search"] ?? "");
$success = $_GET["success"] ?? "";
$error = $_GET["error"] ?? "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";

    if ($action === "add") {

        $name = trim($_POST["name"] ?? "");
        $phone = trim($_POST["phone"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $address = trim($_POST["address"] ?? "");

        if ($name === "") {

            $error = "Supplier name is required.";

        } elseif ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $error = "Please enter a valid email address.";

        } else {

            $check = $conn->prepare("
                SELECT id
                FROM suppliers
                WHERE name = ?
            ");

            $check->bind_param("s", $name);
            $check->execute();

            $existing = $check->get_result();

            if ($existing->num_rows > 0) {

                $error = "A supplier with this name already exists.";

            } else {

                $stmt = $conn->prepare("
                    INSERT INTO suppliers
                    (name, phone, email, address)
                    VALUES (?, ?, ?, ?)
                ");

                $stmt->bind_param(
                    "ssss",
                    $name,
                    $phone,
                    $email,
                    $address
                );

                if ($stmt->execute()) {

                    header(
                        "Location: suppliers.php?success=" .
                        urlencode("Supplier added successfully.")
                    );

                    exit;

                } else {

                    $error = "Unable to add the supplier.";
                }
            }
        }
    }

    if ($action === "edit") {

        $id = filter_input(
            INPUT_POST,
            "id",
            FILTER_VALIDATE_INT
        );

        $name = trim($_POST["name"] ?? "");
        $phone = trim($_POST["phone"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $address = trim($_POST["address"] ?? "");

        if (!$id) {

            $error = "Invalid supplier.";

        } elseif ($name === "") {

            $error = "Supplier name is required.";

        } elseif ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $error = "Please enter a valid email address.";

        } else {

            $check = $conn->prepare("
                SELECT id
                FROM suppliers
                WHERE name = ?
                AND id != ?
            ");

            $check->bind_param(
                "si",
                $name,
                $id
            );

            $check->execute();

            $existing = $check->get_result();

            if ($existing->num_rows > 0) {

                $error = "A supplier with this name already exists.";

            } else {

                $stmt = $conn->prepare("
                    UPDATE suppliers
                    SET
                        name = ?,
                        phone = ?,
                        email = ?,
                        address = ?
                    WHERE id = ?
                ");

                $stmt->bind_param(
                    "ssssi",
                    $name,
                    $phone,
                    $email,
                    $address,
                    $id
                );

                if ($stmt->execute()) {

                    header(
                        "Location: suppliers.php?success=" .
                        urlencode("Supplier updated successfully.")
                    );

                    exit;

                } else {

                    $error = "Unable to update the supplier.";
                }
            }
        }
    }

    if ($action === "delete") {

        $id = filter_input(
            INPUT_POST,
            "id",
            FILTER_VALIDATE_INT
        );

        if (!$id) {

            $error = "Invalid supplier.";

        } else {

            $check = $conn->prepare("
                SELECT COUNT(*) AS total
                FROM products
                WHERE supplier_id = ?
            ");

            $check->bind_param("i", $id);
            $check->execute();

            $product_count = (int)$check
                ->get_result()
                ->fetch_assoc()["total"];

            if ($product_count > 0) {

                $error = "This supplier cannot be deleted because products are assigned to it.";

            } else {

                $stmt = $conn->prepare("
                    DELETE FROM suppliers
                    WHERE id = ?
                ");

                $stmt->bind_param("i", $id);

                if ($stmt->execute()) {

                    header(
                        "Location: suppliers.php?success=" .
                        urlencode("Supplier deleted successfully.")
                    );

                    exit;

                } else {

                    $error = "Unable to delete the supplier.";
                }
            }
        }
    }
}

$sql = "
    SELECT
        suppliers.id,
        suppliers.name,
        suppliers.phone,
        suppliers.email,
        suppliers.address,
        suppliers.created_at,
        COUNT(products.id) AS product_count
    FROM suppliers
    LEFT JOIN products
        ON products.supplier_id = suppliers.id
    WHERE 1=1
";

$params = [];
$types = "";

if ($search !== "") {

    $sql .= "
        AND (
            suppliers.name LIKE ?
            OR suppliers.phone LIKE ?
            OR suppliers.email LIKE ?
            OR suppliers.address LIKE ?
        )
    ";

    $search_value = "%" . $search . "%";

    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;

    $types .= "ssss";
}

$sql .= "
    GROUP BY
        suppliers.id,
        suppliers.name,
        suppliers.phone,
        suppliers.email,
        suppliers.address,
        suppliers.created_at
    ORDER BY suppliers.name ASC
";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param(
        $types,
        ...$params
    );
}

$stmt->execute();

$suppliers = $stmt->get_result();

$total_suppliers = $suppliers->num_rows;

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
        Suppliers | Inventory Management System
    </title>

    <link
        rel="stylesheet"
        href="css/style.css?v=6"
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
                class="nav-link active"
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
                    Suppliers
                </h1>

                <p>
                    Manage supplier information and product relationships.
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


        <section class="suppliers-container">


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


            <div class="suppliers-toolbar">

                <div>

                    <h2>
                        Supplier Directory
                    </h2>

                    <p>
                        <?php
                        echo number_format($total_suppliers);
                        ?>
                        supplier<?php echo $total_suppliers == 1 ? "" : "s"; ?>
                        found
                    </p>

                </div>


                <button
                    type="button"
                    class="button button-primary"
                    onclick="openSupplierModal()"
                >
                    + Add Supplier
                </button>

            </div>


            <div class="panel supplier-filter-panel">

                <form
                    method="GET"
                    action="suppliers.php"
                    class="supplier-search-form"
                >

                    <div class="supplier-search-field">

                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="Search supplier, phone, email or address..."
                            value="<?php echo htmlspecialchars($search); ?>"
                        >

                    </div>


                    <button
                        type="submit"
                        class="button button-primary"
                    >
                        Search
                    </button>


                    <a
                        href="suppliers.php"
                        class="button button-secondary"
                    >
                        Reset
                    </a>

                </form>

            </div>


            <div class="panel suppliers-panel">

                <div class="table-container">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    Supplier
                                </th>

                                <th>
                                    Phone
                                </th>

                                <th>
                                    Email
                                </th>

                                <th>
                                    Address
                                </th>

                                <th>
                                    Products
                                </th>

                                <th>
                                    Created
                                </th>

                                <th>
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php if ($suppliers->num_rows > 0): ?>

                            <?php while ($supplier = $suppliers->fetch_assoc()): ?>

                                <tr>

                                    <td>

                                        <strong>
                                            <?php
                                            echo htmlspecialchars(
                                                $supplier["name"]
                                            );
                                            ?>
                                        </strong>

                                    </td>


                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $supplier["phone"] !== ""
                                                ? $supplier["phone"]
                                                : "—"
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $supplier["email"] !== ""
                                                ? $supplier["email"]
                                                : "—"
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $supplier["address"] !== ""
                                                ? $supplier["address"]
                                                : "—"
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <span class="supplier-count">

                                            <?php
                                            echo number_format(
                                                $supplier["product_count"]
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?php
                                        echo date(
                                            "d M Y",
                                            strtotime(
                                                $supplier["created_at"]
                                            )
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <div class="supplier-actions">

                                            <button
                                                type="button"
                                                class="action-edit"
                                                onclick='editSupplier(
                                                    <?php echo json_encode($supplier["id"]); ?>,
                                                    <?php echo json_encode($supplier["name"]); ?>,
                                                    <?php echo json_encode($supplier["phone"]); ?>,
                                                    <?php echo json_encode($supplier["email"]); ?>,
                                                    <?php echo json_encode($supplier["address"]); ?>
                                                )'
                                            >
                                                Edit
                                            </button>


                                            <form
                                                method="POST"
                                                action="suppliers.php"
                                                class="delete-supplier-form"
                                                onsubmit="return confirm('Are you sure you want to delete this supplier?');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="delete"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?php echo $supplier["id"]; ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="action-delete"
                                                >
                                                    Delete
                                                </button>

                                            </form>

                                        </div>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>

                                <td
                                    colspan="7"
                                    class="empty-state"
                                >
                                    No suppliers found.
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


<div
    id="supplierModal"
    class="supplier-modal"
>

    <div class="supplier-modal-content">

        <div class="supplier-modal-header">

            <div>

                <h3 id="supplierModalTitle">
                    Add Supplier
                </h3>

                <p>
                    Create or update supplier information.
                </p>

            </div>


            <button
                type="button"
                class="supplier-modal-close"
                onclick="closeSupplierModal()"
                aria-label="Close"
            >
                ×
            </button>

        </div>


        <form
            method="POST"
            action="suppliers.php"
            class="supplier-form"
        >

            <input
                type="hidden"
                name="action"
                id="supplierAction"
                value="add"
            >


            <input
                type="hidden"
                name="id"
                id="supplierId"
                value=""
            >


            <div class="form-group">

                <label>
                    Supplier Name <span>*</span>
                </label>

                <input
                    type="text"
                    name="name"
                    id="supplierName"
                    class="form-control"
                    placeholder="Enter supplier name"
                    required
                >

            </div>


            <div class="supplier-form-grid">

                <div class="form-group">

                    <label>
                        Phone
                    </label>

                    <input
                        type="text"
                        name="phone"
                        id="supplierPhone"
                        class="form-control"
                        placeholder="Enter phone number"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Email
                    </label>

                    <input
                        type="email"
                        name="email"
                        id="supplierEmail"
                        class="form-control"
                        placeholder="Enter email address"
                    >

                </div>

            </div>


            <div class="form-group">

                <label>
                    Address
                </label>

                <input
                    type="text"
                    name="address"
                    id="supplierAddress"
                    class="form-control"
                    placeholder="Enter supplier address"
                >

            </div>


            <div class="form-actions">

                <button
                    type="button"
                    class="button button-secondary"
                    onclick="closeSupplierModal()"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    class="button button-primary"
                    id="supplierSubmitButton"
                >
                    Add Supplier
                </button>

            </div>

        </form>

    </div>

</div>


<script>

function openSupplierModal() {

    document
        .getElementById("supplierModal")
        .classList
        .add("show");

    document.getElementById("supplierModalTitle").textContent = "Add Supplier";

    document.getElementById("supplierAction").value = "add";

    document.getElementById("supplierId").value = "";

    document.getElementById("supplierName").value = "";

    document.getElementById("supplierPhone").value = "";

    document.getElementById("supplierEmail").value = "";

    document.getElementById("supplierAddress").value = "";

    document.getElementById("supplierSubmitButton").textContent = "Add Supplier";

    document.getElementById("supplierName").focus();
}


function editSupplier(
    id,
    name,
    phone,
    email,
    address
) {

    document
        .getElementById("supplierModal")
        .classList
        .add("show");

    document.getElementById("supplierModalTitle").textContent = "Edit Supplier";

    document.getElementById("supplierAction").value = "edit";

    document.getElementById("supplierId").value = id;

    document.getElementById("supplierName").value = name;

    document.getElementById("supplierPhone").value = phone;

    document.getElementById("supplierEmail").value = email;

    document.getElementById("supplierAddress").value = address;

    document.getElementById("supplierSubmitButton").textContent = "Update Supplier";

    document.getElementById("supplierName").focus();
}


function closeSupplierModal() {

    document
        .getElementById("supplierModal")
        .classList
        .remove("show");
}


window.addEventListener(
    "click",
    function(event) {

        const modal = document.getElementById("supplierModal");

        if (event.target === modal) {
            closeSupplierModal();
        }

    }
);

</script>

</body>

</html>

