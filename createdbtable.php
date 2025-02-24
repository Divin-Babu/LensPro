<?php
$servername = 'localhost';
$username = 'root';  
$password = '';  

// Create connection
$mysqli = new mysqli($servername, $username, $password);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS lenspro";
if ($mysqli->query($sql)) {
    echo "Database created successfully<br>";
} else {
    echo "Error creating database: " . $mysqli->error . "<br>";
}

// Select the database
$mysqli->select_db("lenspro");

// Create tables
$sql = "CREATE TABLE IF NOT EXISTS tbl_user (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(30) NOT NULL,
    email VARCHAR(30) UNIQUE NOT NULL,
    phno varchar(10) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status BOOLEAN DEFAULT TRUE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    role ENUM('user','photographer','admin') NOT NULL,
    profile_pic varchar(255)
)";

if ($mysqli->query($sql)) {
    echo "Table user created successfully<br>";
} else {
    echo "Error creating table: " . $mysqli->error . "<br>";
}

$sql = "CREATE TABLE IF NOT EXISTS tbl_photographer (
    photographer_detail_id INT PRIMARY KEY AUTO_INCREMENT,
    photographer_id INT,
    bio VARCHAR(500) NOT NULL,
    location VARCHAR(200) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (photographer_id) REFERENCES tbl_user(user_id)
)";

if ($mysqli->query($sql)) {
    echo "Table photographer created successfully<br>";
} else {
    echo "Error creating table: " . $mysqli->error . "<br>";
}

$sql="CREATE TABLE IF NOT EXISTS tbl_categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(30) NOT NULL UNIQUE,
    description VARCHAR(100) ,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status BOOLEAN DEFAULT TRUE
)";

if ($mysqli->query($sql)) {
    echo "Table categories created successfully<br>";
} else {
    echo "Error creating table: " . $mysqli->error . "<br>";
}

$sql="CREATE TABLE IF NOT EXISTS tbl_gallery (
    image_id INT PRIMARY KEY AUTO_INCREMENT,
    photographer_id INT,
    image_url VARCHAR(255) NOT NULL,
    title VARCHAR(50) NOT NULL,
    category_id INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (photographer_id) REFERENCES tbl_user(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES tbl_categories(category_id) ON DELETE SET NULL
)";

if ($mysqli->query($sql)) {
    echo "Table gallery created successfully<br>";
} else {
    echo "Error creating table: " . $mysqli->error . "<br>";
}

$mysqli->close();
?>