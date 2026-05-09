<?php
include_once "../db-conn.php";


// Handle add testimonial
if (isset($_POST['add-testimonial'])) {
    // Validate and sanitize input
    $client_name = mysqli_real_escape_string($conn, $_POST['client_name']);
    $client_title = mysqli_real_escape_string($conn, $_POST['client_title']);
    $client_company = mysqli_real_escape_string($conn, $_POST['client_company']);
    $testimonial_text = mysqli_real_escape_string($conn, $_POST['testimonial_text']);
    $rating = intval($_POST['rating']);
    $project_name = mysqli_real_escape_string($conn, $_POST['project_name']);
    $project_date = mysqli_real_escape_string($conn, $_POST['project_date']);
    $featured = isset($_POST['featured']) ? 1 : 0;
    $display_order = intval($_POST['display_order']);

    // Handle file upload
    $client_photo = '';
    if (isset($_FILES['client_photo']) && $_FILES['client_photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/testimonials/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_name = time() . '_' . basename($_FILES['client_photo']['name']);
        $target_path = $upload_dir . $file_name;

        // Check if image file is an actual image
        $check = getimagesize($_FILES['client_photo']['tmp_name']);
        if ($check !== false) {
            // Move the uploaded file
            if (move_uploaded_file($_FILES['client_photo']['tmp_name'], $target_path)) {
                $client_photo = $file_name;
            }
        }
    }

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO testimonials (
        client_name, 
        client_title, 
        client_company, 
        client_photo, 
        testimonial_text, 
        rating, 
        project_name, 
        project_date, 
        featured, 
        display_order,
        created_at,
        updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");

    $stmt->bind_param(
        "sssssisssi",
        $client_name,
        $client_title,
        $client_company,
        $client_photo,
        $testimonial_text,
        $rating,
        $project_name,
        $project_date,
        $featured,
        $display_order
    );

    if ($stmt->execute()) {
        $testimonial_id = $stmt->insert_id;
        $_SESSION['success'] = "Testimonial added successfully!";
        header("Location: testimonials.php?edit=" . $testimonial_id);
        exit();
    } else {
        $_SESSION['error'] = "Error adding testimonial: " . $conn->error;
        header("Location: add-testimonial.php");
        exit();
    }
}

?>