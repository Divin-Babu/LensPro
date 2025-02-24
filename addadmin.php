<?php
$host = "localhost"; 
$username = "root"; 
$password = ""; 
$database = "lenspro"; 

$conn = new mysqli($host, $username, $password, $database);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$admin_name = "Admin";
$admin_email = "admin@gmail.com";
$admin_password = "Admin@123";
$hashed_password = password_hash($admin_password, PASSWORD_BCRYPT); 


$sql = "INSERT INTO tbl_user (name, email, password, role, created_at) VALUES (?, ?, ?, 'admin', NOW())";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $admin_name, $admin_email, $hashed_password);

if ($stmt->execute()) {
    echo "Admin added successfully!";
} else {
    echo "Error: " . $stmt->error;
}


$stmt->close();
$conn->close();
?>
