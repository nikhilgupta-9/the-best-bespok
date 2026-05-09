<?php
session_start();
include "db-conn.php";

if (isset($_GET['type']) && $_GET['type'] == 'category' && isset($_GET['id']) && isset($_GET['status'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $current_status = mysqli_real_escape_string($conn, $_GET['status']);

    $new_status = ($current_status == '1') ? '0' : '1';

    $sql = "UPDATE categories SET status = '$new_status' WHERE id = '$id'";

    if (mysqli_query($conn, $sql)) {
        $status_text = ($new_status == '1') ? 'activated' : 'deactivated';
        $_SESSION['success'] = "Category $status_text successfully!";
    } else {
        $_SESSION['error'] = "Failed to update status: " . mysqli_error($conn);
    }

    header("Location: view-categories.php");
    exit();
} else {
    $_SESSION['error'] = "Invalid request";
    header("Location: view-categories.php");
    exit();
}
?>