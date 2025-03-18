<?php
include 'dbconnect.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

function sendEmail($recipientEmail, $photographerName, $status) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'lenspro25@gmail.com';
        $mail->Password   = 'kwfw psxo djqu kvcz';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('lenspro25@gmail.com', 'LensPro');
        $mail->addAddress($recipientEmail);

        if ($status == 'approved') {
            $mail->Subject = 'Approval Notification';
            $mail->Body    = "Dear $photographerName,\n\nCongratulations! Your account has been approved. You can now log in and start using our platform.\n\nBest regards,\nLensPro Team";
        } else {
            $mail->Subject = 'Rejection Notification';
            $mail->Body    = "Dear $photographerName,\n\nWe regret to inform you that your application has been rejected. If you have any questions, feel free to reach out to us.\n\nBest regards,\nLensPro Team";
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}

if (isset($_POST['photographer_id']) && isset($_POST['status'])) {
    $id = $_POST['photographer_id'];
    $status = $_POST['status'];

    $query = mysqli_query($conn, "UPDATE tbl_photographer SET approval_status='$status' WHERE photographer_id='$id'");
    if ($query) {
        echo "success"; 
    } else {
        echo "error: " . mysqli_error($conn); 
    }

    if ($status == 'approved') {
        $query = mysqli_query($conn, "UPDATE tbl_user SET status = TRUE WHERE user_id='$id'");
    } else {
        $query = mysqli_query($conn, "UPDATE tbl_user SET status = FALSE WHERE user_id='$id'");
    }

    if ($query) {
        // Fetch photographer email and name
        $result = mysqli_query($conn, "SELECT email, name FROM tbl_user WHERE user_id='$id'");
        $row = mysqli_fetch_assoc($result);
        if ($row) {
            sendEmail($row['email'], $row['name'], $status);
        }
    } else {
        echo "error: " . mysqli_error($conn);
    }
} else {
    echo "Invalid request";
}
?>
