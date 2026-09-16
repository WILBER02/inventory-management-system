
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
        $description = trim($_POST["description"] ?? "");

        if ($name === "") {

            $error = "Category name is required.";

        } else {

            $check = $conn->prepare("
                SELECT id
                FROM categories
                WHERE name = ?
            ");

            $check->bind_param("s", $name);
            $check->execute();

            $existing = $check->get_result();

            if ($existing->num_rows > 0) {

                $error = "A category with this name already exists.";

            } else {

                $stmt = $conn->prepare("
                    INSERT INTO categories
                    (name, description)
                    VALUES (?, ?)
                ");

                $stmt->bind_param(
                    "ss",
                    $name,
                    $description
                );

                if ($stmt->execute()) {

                    header(
                        "Location: categories.php?success=" .
                        urlencode("Category added successfully.")
                    );

                    exit;

                } else {

                    $error = "Unable to add the category.";
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
        $description = trim($_POST["description"] ?? "");

        if (!$id) {

            $error = "Invalid category.";

        } elseif ($name === "") {

            $error = "Category name is required.";

        } else {

            $check = $conn->prepare("
                SELECT id
                FROM categories
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

                $error = "A category with this name already exists.";

            } else {

                $stmt = $conn->prepare("
                    UPDATE categories
                    SET name = ?, description = ?
                    WHERE id = ?
                ");

                $stmt->bind_param(
                    "ssi",
                    $name,
                    $description,
                    $id
                );

                if ($stmt->execute()) {

                    header(
                        "Location: categories.php?success=" .
                        urlencode("Category updated successfully.")
                    );

                    exit;

                } else {

                    $error = "Unable to update the category.";
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

            $error = "Invalid category.";

        } else {

            $check = $conn->prepare("
                SELECT COUNT(*) AS total
                FROM products
                WHERE category_id = ?
            ");

            $check->bind_param("i", $id);
            $check->execute();

            $product_count = (int)$check
                ->get_result()
                ->fetch_assoc()["total"];

            if ($product_count > 0) {

                $error = "This category cannot be deleted because it has products assigned to it.";

            } else {

                $stmt = $conn->prepare("
                    DELETE FROM categories
                    WHERE id = ?
                ");

                $stmt->bind_param("i", $id);

                if ($stmt->execute()) {

                    header(
                        "Location: categories.php?success=" .
                        urlencode("Category deleted successfully.")
                    );

                    exit;

                } else {

                    $error = "Unable to delete the category.";
                }
            }
        }
    }
}

$sql = "
    SELECT
        categories.id,
        categories.name,
        categories.description,
        categories.created_at,
        COUNT(products.id) AS product_count
    FROM categories
    LEFT JOIN products
        ON products.category_id = categories.id
    WHERE 1=1
";

$params = [];
$types = "";

if ($search !== "") {

    $sql .= "
        AND (
            categories.name LIKE ?
            OR categories.description LIKE ?
        )
    ";

    $search_value = "%" . $search . "%";

    $params[] = $search_value;
    $params[] = $search_value;

    $types .= "ss";
}

$sql .= "
    GROUP BY
        categories.id,
        categories.name,
        categories.description,
        categories.created_at
    ORDER BY categories.name ASC
";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param(
        $types,
        ...$params
    );
}

$stmt->execute();

$categories = $stmt->get_result();

$total_categories = $categories->num_rows;

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
        Categories | Inventory Management System
    </title>

    <link
        rel="stylesheet"
        href="css/style.css?v=5"
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
                class="nav-link active"
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
                    Categories
                </h1>

                <p>
                    Organize products into manageable inventory categories.
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


        <section class="categories-container">


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


            <div class="categories-toolbar">

                <div>

                    <h2>
                        Product Categories
                    </h2>

                    <p>
                        <?php
                        echo number_format($total_categories);
                        ?>
                        categor<?php echo $total_categories == 1 ? "y" : "ies"; ?>
                        found
                    </p>

                </div>


                <button
                    type="button"
                    class="button button-primary"
                    onclick="openCategoryModal()"
                >
                    + Add Category
                </button>

            </div>


            <div class="panel category-filter-panel">

                <form
                    method="GET"
                    action="categories.php"
                    class="category-search-form"
                >

                    <div class="category-search-field">

                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="Search category..."
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
                        href="categories.php"
                        class="button button-secondary"
                    >
                        Reset
                    </a>

                </form>

            </div>


            <div class="panel categories-panel">

                <div class="table-container">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    Category
                                </th>

                                <th>
                                    Description
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

                        <?php if ($categories->num_rows > 0): ?>

                            <?php while ($category = $categories->fetch_assoc()): ?>

                                <tr>

                                    <td>

                                        <strong>
                                            <?php
                                            echo htmlspecialchars(
                                                $category["name"]
                                            );
                                            ?>
                                        </strong>

                                    </td>


                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $category["description"] !== ""
                                                ? $category["description"]
                                                : "No description"
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <span class="category-count">

                                            <?php
                                            echo number_format(
                                                $category["product_count"]
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?php
                                        echo date(
                                            "d M Y",
                                            strtotime(
                                                $category["created_at"]
                                            )
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <div class="category-actions">

                                            <button
                                                type="button"
                                                class="action-edit"
                                                onclick='editCategory(
                                                    <?php echo json_encode($category["id"]); ?>,
                                                    <?php echo json_encode($category["name"]); ?>,
                                                    <?php echo json_encode($category["description"]); ?>
                                                )'
                                            >
                                                Edit
                                            </button>


                                            <form
                                                method="POST"
                                                action="categories.php"
                                                class="delete-category-form"
                                                onsubmit="return confirm('Are you sure you want to delete this category?');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="delete"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?php echo $category["id"]; ?>"
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
                                    colspan="5"
                                    class="empty-state"
                                >
                                    No categories found.
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
    id="categoryModal"
    class="category-modal"
>

    <div class="category-modal-content">

        <div class="category-modal-header">

            <div>

                <h3 id="categoryModalTitle">
                    Add Category
                </h3>

                <p>
                    Create or update an inventory category.
                </p>

            </div>


            <button
                type="button"
                class="category-modal-close"
                onclick="closeCategoryModal()"
                aria-label="Close"
            >
                ×
            </button>

        </div>


        <form
            method="POST"
            action="categories.php"
            class="category-form"
        >

            <input
                type="hidden"
                name="action"
                id="categoryAction"
                value="add"
            >


            <input
                type="hidden"
                name="id"
                id="categoryId"
                value=""
            >


            <div class="form-group">

                <label>
                    Category Name <span>*</span>
                </label>

                <input
                    type="text"
                    name="name"
                    id="categoryName"
                    class="form-control"
                    placeholder="Enter category name"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Description
                </label>

                <textarea
                    name="description"
                    id="categoryDescription"
                    class="form-control"
                    rows="4"
                    placeholder="Enter category description..."
                ></textarea>

            </div>


            <div class="form-actions">

                <button
                    type="button"
                    class="button button-secondary"
                    onclick="closeCategoryModal()"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    class="button button-primary"
                    id="categorySubmitButton"
                >
                    Add Category
                </button>

            </div>

        </form>

    </div>

</div>


<script>

function openCategoryModal() {

    document.getElementById("categoryModal").classList.add("show");

    document.getElementById("categoryModalTitle").textContent = "Add Category";

    document.getElementById("categoryAction").value = "add";

    document.getElementById("categoryId").value = "";

    document.getElementById("categoryName").value = "";

    document.getElementById("categoryDescription").value = "";

    document.getElementById("categorySubmitButton").textContent = "Add Category";

    document.getElementById("categoryName").focus();
}


function editCategory(id, name, description) {

    document.getElementById("categoryModal").classList.add("show");

    document.getElementById("categoryModalTitle").textContent = "Edit Category";

    document.getElementById("categoryAction").value = "edit";

    document.getElementById("categoryId").value = id;

    document.getElementById("categoryName").value = name;

    document.getElementById("categoryDescription").value = description;

    document.getElementById("categorySubmitButton").textContent = "Update Category";

    document.getElementById("categoryName").focus();
}


function closeCategoryModal() {

    document.getElementById("categoryModal").classList.remove("show");
}


window.addEventListener("click", function(event) {

    const modal = document.getElementById("categoryModal");

    if (event.target === modal) {
        closeCategoryModal();
    }

});

</script>

</body>

</html>

