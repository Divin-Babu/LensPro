<?php
session_start();
include 'dbconnect.php';

// Check if user is logged in and is a photographer
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'photographer') {
    $_SESSION['error'] = "Unauthorized access";
    header('Location: login.php');
    exit();
}

$photographer_id = $_SESSION['userid'];

try {
    // Validate input
    if (!isset($_POST['title']) || !isset($_POST['category_id'])) {
        throw new Exception('Missing required fields');
    }

    $title = trim($_POST['title']);
    $category_id = intval($_POST['category_id']);

    // Validate file upload
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Invalid file upload');
    }

    // Validate file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
    $file_info = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($file_info, $_FILES['image']['tmp_name']);
    finfo_close($file_info);

    if (!in_array($mime_type, $allowed_types)) {
        throw new Exception('Invalid file type. Only JPG and PNG files are allowed.');
    }

    // Generate unique filename
    $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $filename = uniqid('img_') . '.' . $extension;
    $upload_path = 'uploads/gallery/' . $filename;

    // Create directory if it doesn't exist
    if (!file_exists('uploads/gallery')) {
        mkdir('uploads/gallery', 0777, true);
    }

    // Move uploaded file
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
        throw new Exception('Failed to save image');
    }

    // Insert into database
    $stmt = mysqli_prepare($conn, 
        "INSERT INTO tbl_gallery (photographer_id, image_url, title, category_id, status) 
         VALUES (?, ?, ?, ?, 1)");
    
    mysqli_stmt_bind_param($stmt, "issi", 
        $photographer_id, 
        $upload_path, 
        $title,
        $category_id
    );

    if (!mysqli_stmt_execute($stmt)) {
        // Delete uploaded file if database insert fails
        unlink($upload_path);
        throw new Exception('Failed to save image information to database');
    }

    $_SESSION['success'] = "Image uploaded successfully";

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

// Redirect back to gallery page
header('Location: gallery.php');
exit();