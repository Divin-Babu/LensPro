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
    // Get POST data
    if (!isset($_POST['image_id'])) {
        throw new Exception('Image ID is required');
    }

    $image_id = intval($_POST['image_id']);

    $stmt = mysqli_prepare($conn, 
        "SELECT image_url FROM tbl_gallery 
         WHERE image_id = ? AND photographer_id = ? AND status = 1");
    
    mysqli_stmt_bind_param($stmt, "ii", $image_id, $photographer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        // Soft delete in database 
        $update_stmt = mysqli_prepare($conn, 
            "UPDATE tbl_gallery SET status = 0 WHERE image_id = ? AND photographer_id = ?");
        
        mysqli_stmt_bind_param($update_stmt, "ii", $image_id, $photographer_id);
        
        if (!mysqli_stmt_execute($update_stmt)) {
            throw new Exception('Failed to delete image from database');
        }

        // //  delete file
        // if (file_exists($row['image_url'])) {
        //     unlink($row['image_url']);
        //}

        $_SESSION['success'] = "Image deleted successfully";
    } else {
        throw new Exception('Image not found or unauthorized');
    }

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

// Redirect back to gallery page
header('Location: gallery.php');
exit();