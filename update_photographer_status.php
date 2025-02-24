<?php
include 'dbconnect.php';

if (isset($_POST['photographer_id']) && isset($_POST['status'])) {
    $id = $_POST['photographer_id'];
    $status = $_POST['status'];

    $query = mysqli_query($conn, "UPDATE tbl_photographer SET approval_status='$status' WHERE photographer_id='$id'");
    if ($query) {
        echo "success"; 
    } else {
        echo "error: " . mysqli_error($conn); 
    }

    if($status=='approved'){
        $query = mysqli_query($conn, "UPDATE tbl_user SET status = TRUE WHERE user_id='$id'");
        if (!$query) {
            echo "error: " . mysqli_error($conn);  
        } 
    }
    else{
        $query = mysqli_query($conn, "UPDATE tbl_user SET status = FALSE WHERE user_id='$id'");
        if (!$query) {
            echo "error: " . mysqli_error($conn);
        }
    }

} else {
    echo "Invalid request";
}
?>
