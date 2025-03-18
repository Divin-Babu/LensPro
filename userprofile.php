<?php
session_start();
include 'dbconnect.php';

if (!isset($_SESSION['userid'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['userid'];
$sql = "SELECT * FROM tbl_user WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
  
    $errors = [];
    if (empty($name)) $errors[] = "Name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (!preg_match("/^[6-9][0-9]{9}$/", $phone)) $errors[] = "Invalid phone number";
    
    $profile_image = $user['profile_pic']; 
   
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/jpeg', 'image/png'];
    $file_type = $_FILES['profile_image']['type'];
    
    if (!in_array($file_type, $allowed_types)) {
        $errors[] = "Invalid file type. Only JPG, PNG are allowed.";
    } else {
        $upload_dir = 'uploads/profile_images/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $new_filename = 'profile_pic_' . $user_id . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
            
            if (!empty($user['profile_pic']) && file_exists($user['profile_pic']) && $user['profile_pic'] !== $upload_path) {
                unlink($user['profile_pic']);
            }
            $profile_image = $upload_path;
        } else { 
            $errors[] = "Error uploading file. Please try again.";
        }
    }
}
    
    if (empty($errors)) {
        $update_sql = "UPDATE tbl_user SET name = ?, email = ?, phno = ?, profile_pic = ? WHERE user_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssssi", $name, $email, $phone, $profile_image, $user_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['success_message'] = "Profile updated successfully!";
            // $_SESSION['user']=$name;
            // $_SESSION['profile_pic'] = $profile_image;
            header("Location: userprofile.php");
            exit();
        } else {
            $errors[] = "Error updating profile. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - LensPro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
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
            background-image: url('images/bgimgusrpro.jpg');
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-size: cover;
            background-position: center;
        }

        .container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .profile-card {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .profile-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: var(--light-gray);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 2rem;
            overflow: hidden;
        }

        .profile-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-photo i {
            font-size: 3rem;
            color: var(--primary-color);
        }

        .image-upload-wrapper {
            display: none;
            margin-top: 1rem;
        }

        .image-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            overflow: hidden;
            margin: 1rem 0;
        }

        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-title h1 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .profile-title p {
            color: var(--dark-gray);
            opacity: 0.8;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .form-group input:disabled {
            background-color: var(--light-gray);
            cursor: not-allowed;
        }

        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-primary {
            background-color: var(--secondary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

        .btn-edit {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-edit:hover {
            background-color: #234567;
        }

        .btn-home {
            background-color: var(--dark-gray);
            color: white;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            margin-right: 75px;
        }

        .btn-home:hover {
            background-color: #1a2530;
        }

        .buttons {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            align-items: center;
        }

        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-photo">
                    <?php if (!empty($user['profile_pic']) && file_exists($user['profile_pic'])): ?>
                        <img src="<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Profile Photo">
                    <?php else: ?>
                        <i class="fas fa-user"></i>
                    <?php endif; ?>
                </div>
                <div class="profile-title">
                    <h1>My Profile</h1>
                    <p>Manage your account information</p>
                </div>
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php 
                        echo $_SESSION['success_message'];
                        unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="userprofile.php" id="profileForm" enctype="multipart/form-data">
                <div class="image-upload-wrapper" id="imageUploadWrapper">
                    <label for="profile_image">Profile Photo</label>
                    <input type="file" id="profile_image" name="profile_image" accept="image/*">
                    <div class="image-preview" id="imagePreview">
                        <?php if (!empty($user['profile_pic']) && file_exists($user['profile_pic'])): ?>
                            <img src="<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Profile Preview">
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" disabled required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phno']); ?>" disabled required>
                </div>

                <div class="buttons">
                    <a href="index.php" class="btn btn-home">
                        <i class="fas fa-home"></i> Back to Home
                    </a>
                    <button type="button" class="btn btn-edit" id="editButton">Edit Profile</button>
                    <button type="submit" class="btn btn-primary" id="saveButton" style="display: none;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('editButton').addEventListener('click', function() {
           
            document.querySelectorAll('input').forEach(input => {
                input.disabled = false;
            });
            
           
            document.getElementById('imageUploadWrapper').style.display = 'block';
            
            
            this.style.display = 'none';
            document.getElementById('saveButton').style.display = 'block';
        });

        document.getElementById('profile_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('imagePreview');
                    preview.innerHTML = `<img src="${e.target.result}" alt="Profile Preview">`;
                }
                reader.readAsDataURL(file);
            }
        });

        // Form validation
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const phone = document.getElementById('phone').value;
            
            let isValid = true;
            const errors = [];

            if (!/^[a-zA-Z-' ]+$/.test(name)) {
                errors.push("Name should only contain letters and spaces");
                isValid = false;
            }

            if (!/^[6-9][0-9]{9}$/.test(phone)) {
                errors.push("Invalid phone number format");
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
                alert(errors.join("\n"));
            }
        });
    </script>
</body>
</html>