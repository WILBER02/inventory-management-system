
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

$stmt = $conn->prepare("
    SELECT product_name
    FROM products
    WHERE id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header("Location: products.php?error=" . urlencode("Product not found."));
    exit;
}

$delete = $conn->prepare("
    DELETE FROM products
    WHERE id = ?
");

$delete->bind_param("i", $id);

if ($delete->execute()) {
    header("Location: products.php?success=" . urlencode($product["product_name"] . " deleted successfully."));
    exit;
}

header("Location: products.php?error=" . urlencode("Unable to delete the product."));
exit;

