<?php
session_start();
include('dbconnect.php');

// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Check if category ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: categories.php');
    exit();
}

$category_id = $_GET['id'];

// Handle form submission for updating category
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_category'])) {
    $category_name = $_POST['category_name'];
    $description = $_POST['description'];

    $sql = "UPDATE tbl_categories SET category_name = ?, description = ? WHERE category_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $category_name, $description, $category_id);
    
    if ($stmt->execute()) {
        // Redirect with success message
        header("Location: categories.php?update=success");
    } else {
        // Set error message
        $error_message = "Failed to update category: " . $conn->error;
    }
    exit();
}

// Get category details
$stmt = $conn->prepare("SELECT * FROM tbl_categories WHERE category_id = ?");
$stmt->bind_param("i", $category_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: categories.php');
    exit();
}

$category = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LensPro - Edit Category</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
            --sidebar-width: 250px;
            --header-height: 60px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f5f6fa;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background: var(--primary-color);
            padding: 1rem;
            color: white;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 1rem;
        }

        .brand img {
            width: 40px;
            height: auto;
        }

        .brand span {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 0.8rem 1rem;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }

        .menu-item:hover, .menu-item.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .menu-item i {
            width: 20px;
            margin-right: 1rem;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            padding-top: 2rem;
        }

        .category-form-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .form-title {
            font-size: 1.2rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #666;
        }

        .form-control {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        textarea.form-control {
            min-height: 100px;
        }

        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-right: 10px;
        }

        .btn-primary {
            background: var(--secondary-color);
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-cancel {
            background: #95a5a6;
            color: white;
            text-decoration:none;
        }

        .btn-cancel:hover {
            background: #7f8c8d;
        }
        .alert {
            padding: 0.75rem 1.25rem;
            margin-bottom: 1rem;
            border: 1px solid transparent;
            border-radius: 0.25rem;
        }

        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        .form-actions {
            display: flex;
            justify-content: flex-start;
            margin-top: 1.5rem;
        }
    </style>
</head>
<body>
    <nav class="sidebar">
        <div class="brand">
            <img src="images/logowithoutname.png" alt="LensPro Logo">
            <span>LensPro</span>
        </div>
        <a href="adminpanel.php" class="menu-item">
            <i class="fas fa-th-large"></i>
            Dashboard
        </a>
        <a href="adminviewusers.php" class="menu-item">
            <i class="fas fa-user"></i>
            Users
        </a>
        <a href="adminviewphotographer.php" class="menu-item">
            <i class="fas fa-camera"></i>
            Photographers
        </a>
        <a href="#" class="menu-item">
            <i class="fas fa-calendar-check"></i>
            Bookings
        </a>
        <a href="categories.php" class="menu-item active">
            <i class="fas fa-list"></i>
            Categories
        </a>
        <a href="#" class="menu-item">
            <i class="fas fa-star"></i>
            Reviews
        </a>
    </nav>

    <main class="main-content">
        <div class="category-form-card">
            <h2 class="form-title">Edit Category</h2>
            
            <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <?= $error_message ?>
            </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="category_name">Category Name</label>
                    <input type="text" id="category_name" name="category_name" class="form-control" 
                           value="<?= htmlspecialchars($category['category_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" required><?= htmlspecialchars($category['description']) ?></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" name="update_category" class="btn btn-primary">Update Category</button>
                    <a href="categories.php" class="btn btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
    </main>
</body>
</html>