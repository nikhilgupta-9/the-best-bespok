<?php
ob_start();
session_start();
include "functions.php";
include "db-conn.php";

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // FIX: use `id` not `cate_id` — matches your improved schema
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Category deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete category!";
    }

    $stmt->close();
}

header("Location: view-categories.php");
exit;