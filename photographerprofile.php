<?php
session_start();
include 'dbconnect.php';
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'photographer') {
    header('Location: login.php');
    exit();
}

$success_message = $error_message = '';
$photographer_id = $_SESSION['userid'];

$stmt = mysqli_prepare($conn, "SELECT u.*, p.bio, p.location, p.category
                             FROM tbl_user u 
                             JOIN tbl_photographer p ON u.user_id = p.photographer_id 
                             WHERE u.user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $photographer_id);
mysqli_stmt_execute($stmt);
$photographer = mysqli_stmt_get_result($stmt)->fetch_assoc();

$categories = [];
$cat_query = "SELECT * FROM tbl_categories ORDER BY category_name";
$result = $conn->query($cat_query);
if($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = $_POST['name'];
    $email = $_POST['email'];
    $phno = $_POST['phno'];
    $location = $_POST['location'];
    $bio = $_POST['bio'];
    $category_ids = isset($_POST["categories"]) ? $_POST["categories"] : [];
    $category = implode(",", $category_ids);
    
    
    
    
    try {
        
        $user_stmt = mysqli_prepare($conn, "UPDATE tbl_user SET name = ?, email = ?, phno = ? WHERE user_id = ?");
        mysqli_stmt_bind_param($user_stmt, "sssi", $name, $email, $phno, $photographer_id);
        mysqli_stmt_execute($user_stmt);
        
        
        $photo_stmt = mysqli_prepare($conn, "UPDATE tbl_photographer SET bio = ?, location = ?, category = ? WHERE photographer_id = ?");
        mysqli_stmt_bind_param($photo_stmt, "sssi", $bio, $location, $category, $photographer_id);
        mysqli_stmt_execute($photo_stmt);
        
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
            $file_type = $_FILES['profile_image']['type'];
            
            if (in_array($file_type, $allowed_types)) {
                $file_name = 'profile_pic_' . $photographer_id . '_' . time() . '.' . pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                $upload_dir = 'uploads/profile_images/';
                
                
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $target_file = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                    $img_stmt = mysqli_prepare($conn, "UPDATE tbl_user SET profile_pic = ? WHERE user_id = ?");
                    mysqli_stmt_bind_param($img_stmt, "si", $target_file, $photographer_id);
                    mysqli_stmt_execute($img_stmt);
                } else {
                    throw new Exception("Failed to upload image");
                }
            } else {
                throw new Exception("Invalid file type. Please upload JPEG or PNG images only.");
            }
        }
        $success_message = "Profile updated successfully!";
        mysqli_stmt_execute($stmt);
        $photographer = mysqli_stmt_get_result($stmt)->fetch_assoc();
        
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

$selected_categories = explode(",", $photographer['category']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - LensPro</title>
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

        .page-title {
            margin-bottom: 30px;
            color: var(--dark-gray);
            font-size: 24px;
            font-weight: 600;
            border-bottom: 2px solid var(--secondary-color);
            padding-bottom: 10px;
        }

        .profile-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
        }

        .profile-photo-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .profile-photo {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            border: 5px solid var(--secondary-color);
            object-fit: cover;
            margin-bottom: 20px;
        }

        .photo-placeholder {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            border: 5px solid var(--secondary-color);
            background-color: var(--light-gray);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .photo-placeholder i {
            font-size: 60px;
            color: var(--dark-gray);
        }

        .upload-btn {
            background-color: var(--secondary-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
            margin-top: 10px;
        }

        .upload-btn:hover {
            background-color: #2980b9;
        }

        .profile-details-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark-gray);
            font-weight: 500;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            background-color: var(--light-gray);
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-group select[multiple] {
            height: 150px;
        }

        .submit-btn {
            background-color: var(--secondary-color);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: background-color 0.3s;
            margin-top: 20px;
        }

        .submit-btn:hover {
            background-color: #2980b9;
        }

        .alert {
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background-color: rgba(46, 204, 113, 0.2);
            border: 1px solid var(--success-color);
            color: var(--success-color);
        }

        .alert-danger {
            background-color: rgba(231, 76, 60, 0.2);
            border: 1px solid var(--accent-color);
            color: var(--accent-color);
        }

        #file-name {
            font-size: 13px;
            color: var(--dark-gray);
            margin-top: 5px;
            text-align: center;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        @media (max-width: 992px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 70px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full-width {
                grid-column: span 1;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'photographer_sidebar.php'; ?>
        <div class="main-content">
            <h1 class="page-title">My Profile</h1>
            
            <?php if($success_message): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if($error_message): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <form action="photographerprofile.php" method="POST" enctype="multipart/form-data">
                <div class="profile-container">
                    <div class="profile-photo-section">
                        <?php if(isset($photographer['profile_pic']) && !empty($photographer['profile_pic'])): ?>
                            <img src="<?php echo htmlspecialchars($photographer['profile_pic']); ?>" alt="Profile Photo" class="profile-photo">
                        <?php else: ?>
                            <div class="photo-placeholder">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                        
                        <input type="file" id="profile-image-input" name="profile_image" style="display: none;" accept="image/jpeg, image/png">
                        <label for="profile-image-input" class="upload-btn">
                            <i class="fas fa-camera"></i> Change Photo
                        </label>
                        <div id="file-name"></div>
                    </div>
                    <div class="profile-details-section">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($photographer['name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($photographer['email']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phno" value="<?php echo htmlspecialchars($photographer['phno']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="location">Location</label>
                                <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($photographer['location']); ?>" required>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="bio">Professional Bio</label>
                                <textarea id="bio" name="bio" rows="4" required><?php echo htmlspecialchars($photographer['bio']); ?></textarea>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="categories">Photography Categories (Hold Ctrl/Cmd to select multiple)</label>
                                <select id="categories" name="categories[]" multiple required>
                                    <?php foreach($categories as $category): ?>
                                        <option value="<?php echo $category['category_name']; ?>" <?php echo in_array($category['category_name'], $selected_categories) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="submit-btn">Save Changes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
    
        document.getElementById('profile-image-input').addEventListener('change', function() {
            const fileName = this.files[0] ? this.files[0].name : '';
            document.getElementById('file-name').textContent = fileName;
            
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const photoElement = document.querySelector('.profile-photo') || document.querySelector('.photo-placeholder');
                    
                    if (photoElement.classList.contains('photo-placeholder')) {
                        
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.alt = "Profile Photo";
                        img.className = "profile-photo";
                        
                        
                        photoElement.parentNode.replaceChild(img, photoElement);
                    } else {
                        
                        photoElement.src = e.target.result;
                    }
                }
                reader.readAsDataURL(this.files[0]);
            }
        });
        
        
        const form = document.querySelector('form');
        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');
        const phoneInput = document.getElementById('phone');
        
        form.addEventListener('submit', function(event) {
            let isValid = true;
            
            
            if (nameInput.value.trim() === '') {
                nameInput.style.borderColor = 'red';
                isValid = false;
            } else {
                nameInput.style.borderColor = '';
            }
            
            // Email validation
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(emailInput.value)) {
                emailInput.style.borderColor = 'red';
                isValid = false;
            } else {
                emailInput.style.borderColor = '';
            }
            
            const phonePattern = /^[6-9][0-9]{9}$/;
            if (!phonePattern.test(phoneInput.value)) {
                phoneInput.style.borderColor = 'red';
                isValid = false;
            } else {
                phoneInput.style.borderColor = '';
            }
            
            if (!isValid) {
                event.preventDefault();
                alert('Please fix the errors before submitting.');
            }
        });
    </script>
</body>
</html>