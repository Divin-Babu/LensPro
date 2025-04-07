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
    upi_id VARCHAR(30) NOT NULL,
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
    description TEXT,
    status BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (photographer_id) REFERENCES tbl_user(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES tbl_categories(category_id) ON DELETE SET NULL
)";

if ($mysqli->query($sql)) {
    echo "Table gallery created successfully<br>";
} else {
    echo "Error creating table: " . $mysqli->error . "<br>";
}


$sql="CREATE TABLE IF NOT EXISTS tbl_booking (
    booking_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    session_type VARCHAR(50) NOT NULL,
    status ENUM('pending', 'completed', 'cancelled','confirmed','rejected') NOT NULL,
    photographer_id INT,
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    event_date DATE,
    location VARCHAR(100) NOT NULL,
    total_amt DECIMAL NOT NULL,
    FOREIGN KEY (user_id) REFERENCES tbl_user(user_id),
    FOREIGN KEY (photographer_id) REFERENCES tbl_user(user_id)
);";

if ($mysqli->query($sql)) {
    echo "Table Booking created successfully<br>";
} else {
    echo "Error creating table: " . $mysqli->error . "<br>";
}

$sql="CREATE TABLE IF NOT EXISTS tbl_reviews (
    review_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    photographer_id INT,
    rating INT,
    booking_id INT,
    status BOOLEAN DEFAULT TRUE,
    review_text text,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES tbl_booking(booking_id),
    FOREIGN KEY (user_id) REFERENCES tbl_user(user_id),
    FOREIGN KEY (photographer_id) REFERENCES tbl_user(user_id)
);";

if ($mysqli->query($sql)) {
    echo "Table Reviews created successfully<br>";
} else {
    echo "Error creating table: " . $mysqli->error . "<br>";
}


$sql="CREATE TABLE IF NOT EXISTS tbl_payment (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT,
    status ENUM('completed', 'incomplete') NOT NULL,
    payment_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES tbl_booking(booking_id)
);";

if ($mysqli->query($sql)) {
    echo "Table payment created successfully<br>";
} else {
    echo "Error creating table: " . $mysqli->error . "<br>";
}


$sql = "CREATE TABLE IF NOT EXISTS tbl_photographer_pricing (
    pricing_id INT PRIMARY KEY AUTO_INCREMENT,
    photographer_id INT NOT NULL,
    category_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (photographer_id) REFERENCES tbl_user(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES tbl_categories(category_id) ON DELETE CASCADE
)";

if ($mysqli->query($sql)) {
    echo "Table payment created successfully<br>";
} else {
    echo "Error creating table: " . $mysqli->error . "<br>";
}
$mysqli->close();
?>