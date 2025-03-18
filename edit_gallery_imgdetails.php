<?php

session_start();
include 'dbconnect.php';


if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'photographer') {
    header('Location: login.php');
    exit();
}

$photographer_id = $_SESSION['userid'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $image_id = $_POST['image_id'];
    $title = $_POST['title'];
    $category_id = $_POST['category_id'];
    $description=$_POST['description'];
    
    $stmt = mysqli_prepare($conn, "UPDATE tbl_gallery 
                                  SET title = ?, category_id = ?, description=?
                                  WHERE image_id = ? AND photographer_id = ?");
    mysqli_stmt_bind_param($stmt, "sisii", $title, $category_id, $description, $image_id, $photographer_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "Image updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update image: " . mysqli_error($conn);
    }
    
    header('Location: gallery.php');
    exit();
}


if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $image_id = $_GET['id'];
    
    // Get image details
    $stmt = mysqli_prepare($conn, "SELECT * FROM tbl_gallery 
                                  WHERE image_id = ? AND photographer_id = ? AND status = 1");
    mysqli_stmt_bind_param($stmt, "ii", $image_id, $photographer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($image = mysqli_fetch_assoc($result)) {
        
        $categories_query = "SELECT * FROM tbl_categories WHERE status = 1";
        $categories_result = mysqli_query($conn, $categories_query);
    } else {
        $_SESSION['error'] = "Image not found or you don't have permission to edit it.";
        header('Location: gallery.php');
        exit();
    }
} else {
    header('Location: gallery.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Image - LensPro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #2ecc71;
            --light-gray: #f5f6fa;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f0f2f5;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            margin-bottom: 30px;
            color: var(--primary-color);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: var(--primary-color);
            font-weight: 500;
        }

        .form-group input,
        .form-group textarea, 
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        .form-group select {
            cursor: pointer;
        }

        .image-preview {
            margin-bottom: 20px;
        }

        .image-preview img {
            max-width: 100%;
            max-height: 300px;
            border-radius: 5px;
        }

        .buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
        }

        .btn-primary {
            background: var(--secondary-color);
            color: white;
            text-decoration:none;
        }

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-success:hover {
            background: #27ae60;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Edit Image</h1>
        
        <div class="image-preview">
            <img src="<?php echo htmlspecialchars($image['image_url']); ?>" alt="<?php echo htmlspecialchars($image['title']); ?>">
        </div>
        
        <form action="edit_gallery_imgdetails.php" method="POST">
            <input type="hidden" name="image_id" value="<?php echo $image_id; ?>">
            
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($image['title']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="category">Category</label>
                <select id="category" name="category_id" required>
                    <?php while ($category = mysqli_fetch_assoc($categories_result)): ?>
                        <option value="<?php echo $category['category_id']; ?>" <?php if ($category['category_id'] == $image['category_id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($category['category_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"><?php echo htmlspecialchars($image['description']); ?></textarea>
                </div>
            <div class="buttons">
                <a href="gallery.php" class="btn btn-primary">Cancel</a>
                <button type="submit" class="btn btn-success">Update Image</button>
            </div>
        </form>
    </div>
</body>
</html>