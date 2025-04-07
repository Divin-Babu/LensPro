<?php
session_start();
include('dbconnect.php');

if ($_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $category_name = $_POST['category_name'];
    $description = $_POST['description'];
    $status = isset($_POST['status']) ? 1 : 0;

    $sql = "INSERT INTO tbl_categories (category_name, description, status) VALUES (?, ?, TRUE)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $category_name, $description);
    $stmt->execute();
    header("Location: categories.php");
    exit();
}


$categories = $conn->query("SELECT * FROM tbl_categories");


// if (isset($_GET['delete'])) {
//     $id = $_GET['delete'];
//     $conn->query("DELETE FROM tbl_categories WHERE category_id = $id");
//     header("Location: categories.php");
//     exit();
// }

if (isset($_GET['toggle_status'])) {
    $id = $_GET['toggle_status'];
    
    $result = $conn->query("SELECT status FROM tbl_categories WHERE category_id = $id");
    $row = $result->fetch_assoc();
    $new_status = $row['status'] ? 0 : 1;

    $conn->query("UPDATE tbl_categories SET status = $new_status WHERE category_id = $id");
    header("Location: categories.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LensPro - Manage Categories</title>
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
            padding-top: calc(var(--header-height) + 2rem);
        }

        .header {
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            height: var(--header-height);
            background: white;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            z-index: 100;
        }

        .search-bar {
            display: flex;
            align-items: center;
            background: #f5f6fa;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            width: 300px;
        }

        .search-bar input {
            border: none;
            background: none;
            outline: none;
            padding: 0.2rem;
            width: 100%;
            margin-left: 0.5rem;
        }

        /* Category specific styles */
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
        }

        .btn-primary {
            background: var(--secondary-color);
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .category-table {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th, .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .table th {
            font-weight: 500;
            color: #666;
        }

        .badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
        }

        .badge-success {
            background: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
        }

        .badge-warning {
            background: rgba(241, 196, 15, 0.1);
            color: var(--warning-color);
        }

        .action-btns {
            display: flex;
            gap: 0.5rem;
        }

        .btn-edit, .btn-delete, .btn-toggle {
            padding: 0.4rem 0.8rem;
            border-radius: 4px;
            font-size: 0.85rem;
            cursor: pointer;
            text-decoration:none;
        }

        .btn-edit {
            background: var(--secondary-color);
            color: white;
        }

        .btn-delete {
            background: var(--accent-color);
            color: white;
        }

        .btn-toggle-active {
            background: var(--warning-color);
            color: white;
        }

        .btn-toggle-inactive {
            background: var(--success-color);
            color: white;
        }

        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .checkbox-wrapper input[type="checkbox"] {
            width: 1rem;
            height: 1rem;
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
        <a href="adminviewbooking.php" class="menu-item">
            <i class="fas fa-calendar-check"></i>
            Bookings
        </a>
        <a href="categories.php" class="menu-item active">
            <i class="fas fa-list"></i>
            Categories
        </a>
        <a href="adminviewreview.php" class="menu-item">
            <i class="fas fa-star"></i>
            Reviews
        </a>
    </nav>

    <main class="main-content">
            <?php if (isset($_GET['update']) && $_GET['update'] == 'success'): ?>
        <div class="alert alert-success" style="margin-bottom: 1rem; padding: 0.75rem 1.25rem; border-radius: 0.25rem; color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb;">
            Category updated successfully!
        </div>
        <?php endif; ?>
        <div class="category-form-card">
            <h2 class="form-title">Add New Category</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="category_name">Category Name</label>
                    <input type="text" id="category_name" name="category_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" required></textarea>
                </div>
                <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
            </form>
        </div>

        <div class="category-table">
            <h2 class="form-title">Category List</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $categories->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['category_name'] ?></td>
                        <td><?= $row['description'] ?></td>
                        <td>
                            <span class="badge <?= $row['status'] ? 'badge-success' : 'badge-warning' ?>">
                                <?= $row['status'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td class="action-btns">
                            <a href="edit_category.php?id=<?= $row['category_id'] ?>" class="btn-edit">Edit</a>
                            <a href="categories.php?toggle_status=<?= $row['category_id'] ?>" 
                               class="btn-toggle <?= $row['status'] ? 'btn-toggle-active' : 'btn-toggle-inactive' ?>">
                                <?= $row['status'] ? 'Deactivate' : 'Activate' ?>
                            </a>
                            <!-- <a href="categories.php?delete=<?= $row['category_id'] ?>" 
                               onclick="return confirm('Are you sure you want to delete this category?')" 
                               class="btn-delete">Delete</a> -->
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        
        function toggleDropdown() {
            var menu = document.getElementById("dropdownMenu");
            menu.style.display = menu.style.display === "block" ? "none" : "block";
        }

        document.addEventListener("click", function(event) {
            var profile = document.querySelector(".admin-profile");
            var menu = document.getElementById("dropdownMenu");

            if (!profile.contains(event.target)) {
                menu.style.display = "none";
            }
        });
    </script>
</body>
</html>