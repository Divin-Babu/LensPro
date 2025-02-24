<?php
session_start();
include 'dbconnect.php';

// Check if user is logged in and is a photographer
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'photographer') {
    header('Location: login.php');
    exit();
}

if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success']) . '</div>';
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-error">' . htmlspecialchars($_SESSION['error']) . '</div>';
    unset($_SESSION['error']);
}
$photographer_id = $_SESSION['userid'];

// Fetch photographer's gallery images
$stmt = mysqli_prepare($conn, "SELECT g.*, c.category_name 
                             FROM tbl_gallery g 
                             LEFT JOIN tbl_categories c ON g.category_id = c.category_id 
                             WHERE g.photographer_id = ? AND g.status = 1 
                             ORDER BY g.uploaded_at DESC");
mysqli_stmt_bind_param($stmt, "i", $photographer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Fetch categories for dropdown
$categories_query = "SELECT * FROM tbl_categories WHERE status = 1";
$categories_result = mysqli_query($conn, $categories_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Gallery - LensPro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
            --light-gray: #f5f6fa;
            --dark-gray: #2c3e50;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f0f2f5;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }

        .gallery-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .add-image-btn {
            background: var(--success-color);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: background 0.3s;
        }

        .add-image-btn:hover {
            background: #27ae60;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px 0;
        }

        .gallery-item {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }

        .gallery-item:hover {
            transform: translateY(-5px);
        }

        .gallery-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }

        .gallery-info {
            padding: 15px;
        }

        .gallery-title {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 5px;
            color: var(--dark-gray);
        }

        .gallery-category {
            color: var(--secondary-color);
            font-size: 14px;
            margin-bottom: 10px;
        }
        .gallery-actions {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }

        .edit-btn {
            background: var(--warning-color);
            color: white;
        }

        .edit-btn:hover {
            background: #f39c12;
        }

        .delete-btn {
            background: var(--accent-color);
            color: white;
        }

        .delete-btn:hover {
            background: #c0392b;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-content {
            background: white;
            width: 90%;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            border-radius: 10px;
            position: relative;
        }

        .close-btn {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: var(--dark-gray);
        }

        .form-group input[type="text"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-group textarea {
            height: 100px;
            resize: vertical;
        }

        .submit-btn {
            background: var(--success-color);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
        }

        .submit-btn:hover {
            background: #27ae60;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 70px;
            }

            .gallery-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'photographer_sidebar.php'; ?>

        <div class="main-content">
            <div class="gallery-header">
                <h1>My Gallery</h1>
                <button class="add-image-btn" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> Add New Image
                </button>
            </div>

            <div class="gallery-grid">
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <div class="gallery-item">
                        <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>" class="gallery-image">
                        <div class="gallery-info">
                            <h3 class="gallery-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                            <div class="gallery-category">
                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($row['category_name']); ?>
                            </div>
                            <div class="gallery-actions">
                                <button class="action-btn edit-btn" onclick="openEditModal(<?php echo $row['image_id']; ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="action-btn delete-btn" onclick="deleteImage(<?php echo $row['image_id']; ?>)">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <!-- Add Image Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('addModal')">&times;</span>
            <h2>Add New Image</h2>
            <form id="addImageForm" action="process_image.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="image">Select Image</label>
                    <input type="file" id="image" name="image" accept="image/*" required>
                </div>
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category_id" required>
                        <?php while ($category = mysqli_fetch_assoc($categories_result)): ?>
                            <option value="<?php echo $category['category_id']; ?>">
                                <?php echo htmlspecialchars($category['category_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" class="submit-btn">Upload Image</button>
            </form>
        </div>
    </div>

    <script>
    function openAddModal() {
        document.getElementById('addModal').style.display = 'block';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    function openEditModal(imageId) {
        // Implement edit functionality
        alert('Edit functionality to be implemented');
    }

    function deleteImage(imageId) {
        if (confirm('Are you sure you want to delete this image?')) {
            // Create and submit a form for deletion
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'delete_image.php';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'image_id';
            input.value = imageId;
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
    }
    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target.className === 'modal') {
            event.target.style.display = 'none';
        }
    }

    </script>
</body>
</html>