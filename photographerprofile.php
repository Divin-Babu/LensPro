<?php
session_start();
include 'dbconnect.php';
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'photographer') {
    header('Location: login.php');
    exit();
}

$success_message = $error_message = '';
$photographer_id = $_SESSION['userid'];


$stmt = mysqli_prepare($conn, "SELECT u.*, p.bio, p.location, p.category, p.upi_id
                             FROM tbl_user u 
                             JOIN tbl_photographer p ON u.user_id = p.photographer_id 
                             WHERE u.user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $photographer_id);
mysqli_stmt_execute($stmt);
$photographer = mysqli_stmt_get_result($stmt)->fetch_assoc();

$pricing_data = [];
$pricing_query = "SELECT tp.*, tc.category_name 
                 FROM tbl_photographer_pricing tp 
                 JOIN tbl_categories tc ON tp.category_id = tc.category_id 
                 WHERE tp.photographer_id = ?";
$pricing_stmt = mysqli_prepare($conn, $pricing_query);
mysqli_stmt_bind_param($pricing_stmt, "i", $photographer_id);
mysqli_stmt_execute($pricing_stmt);
$pricing_result = mysqli_stmt_get_result($pricing_stmt);

if($pricing_result && mysqli_num_rows($pricing_result) > 0) {
    while($row = mysqli_fetch_assoc($pricing_result)) {
        $pricing_data[$row['category_id']] = $row;
    }
}

$categories = [];
$cat_query = "SELECT * FROM tbl_categories ORDER BY category_name";
$result = $conn->query($cat_query);
if($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

$selectedState = '';
$selectedCity = '';
if (!empty($photographer['location'])) {
    $location_parts = explode(', ', $photographer['location']);
    if (count($location_parts) == 2) {
        $selectedCity = $location_parts[0];
        $selectedState = $location_parts[1];
    } else {
        $selectedState = $photographer['location'];
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = $_POST['name'];
    $email = $_POST['email'];
    $phno = $_POST['phno'];
    $location = $_POST['location'];
    $bio = $_POST['bio'];
    $upi_id = $_POST['upi_id']; // Added UPI ID field
    $category_ids = isset($_POST["categories"]) ? $_POST["categories"] : [];
    $category = implode(",", $category_ids);
    
    
    
    
    try {
        
        $user_stmt = mysqli_prepare($conn, "UPDATE tbl_user SET name = ?, email = ?, phno = ? WHERE user_id = ?");
        mysqli_stmt_bind_param($user_stmt, "sssi", $name, $email, $phno, $photographer_id);
        mysqli_stmt_execute($user_stmt);
        
        
        $photo_stmt = mysqli_prepare($conn, "UPDATE tbl_photographer SET bio = ?, location = ?, category = ?, upi_id = ? WHERE photographer_id = ?");
        mysqli_stmt_bind_param($photo_stmt, "ssssi", $bio, $location, $category, $upi_id, $photographer_id);
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
if(isset($_POST['category_prices']) && is_array($_POST['category_prices'])) {
    foreach($_POST['category_prices'] as $category_id => $price_data) {
        $price = !empty($price_data['price']) ? floatval($price_data['price']) : 0;
       // $duration = !empty($price_data['duration']) ? $price_data['duration'] : '';
        $description = !empty($price_data['description']) ? $price_data['description'] : '';
        
        // Check if pricing entry already exists
        $check_query = "SELECT pricing_id FROM tbl_photographer_pricing 
                       WHERE photographer_id = ? AND category_id = ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "ii", $photographer_id, $category_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if(mysqli_num_rows($check_result) > 0) {
            // Update existing price
            $row = mysqli_fetch_assoc($check_result);
            $pricing_id = $row['pricing_id'];
            $update_query = "UPDATE tbl_photographer_pricing SET 
                            price = ?,  description = ? 
                            WHERE pricing_id = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "dsi", $price, $description, $pricing_id);
            mysqli_stmt_execute($update_stmt);
        } else {
            // Insert new price
            $insert_query = "INSERT INTO tbl_photographer_pricing 
                           (photographer_id, category_id, price, description) 
                           VALUES (?, ?, ?, ?)";
            $insert_stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($insert_stmt, "iids", $photographer_id, $category_id, $price, $description);
            mysqli_stmt_execute($insert_stmt);
        }
    }
}


$pricing_stmt = mysqli_prepare($conn, $pricing_query);
mysqli_stmt_bind_param($pricing_stmt, "i", $photographer_id);
mysqli_stmt_execute($pricing_stmt);
$pricing_result = mysqli_stmt_get_result($pricing_stmt);
$pricing_data = [];

if($pricing_result && mysqli_num_rows($pricing_result) > 0) {
    while($row = mysqli_fetch_assoc($pricing_result)) {
        $pricing_data[$row['category_id']] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - LensPro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="location-selector.js"></script>
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
                                <label for="state">State</label>
                                <select id="state" name="state" required>
                                    <option value="">Select State</option>
                                    <!-- States will be populated by JavaScript -->
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="city">City</label>
                                <select id="city" name="city" required>
                                    <option value="">Select City</option>
                                    <!-- Cities will be populated based on selected state -->
                                </select>
                            </div>

                            <!-- Add a hidden field to store the combined location string -->
                            <input type="hidden" id="location" name="location" value="<?php echo htmlspecialchars($photographer['location']); ?>">
                            
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
                            <div class="form-group full-width">
    <h3 style="margin-bottom: 15px; color: var(--dark-gray);">Pricing Information</h3>
    <div class="pricing-container">
        <?php foreach($categories as $category): ?>
            <div class="pricing-item" id="pricing-<?php echo $category['category_id']; ?>" style="display: <?php echo in_array($category['category_name'], $selected_categories) ? 'block' : 'none'; ?>; margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; background-color: #f9f9f9;">
                <h4 style="margin-bottom: 10px;"><?php echo htmlspecialchars($category['category_name']); ?></h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <label for="price-<?php echo $category['category_id']; ?>">Price (₹)</label>
                        <input type="number" step="0.01" min="0" 
                               id="price-<?php echo $category['category_id']; ?>" 
                               name="category_prices[<?php echo $category['category_id']; ?>][price]"
                               value="<?php echo isset($pricing_data[$category['category_id']]) ? $pricing_data[$category['category_id']]['price'] : ''; ?>"
                               style="width: 100%; padding: 8px; margin-top: 5px;"
                               placeholder="Enter price">
                    </div>
                    <!-- <div>
                        <label for="duration-<?php echo $category['category_id']; ?>">Duration</label>
                        <input type="text" 
                               id="duration-<?php echo $category['category_id']; ?>" 
                               name="category_prices[<?php echo $category['category_id']; ?>][duration]" 
                               value="<?php echo isset($pricing_data[$category['category_id']]) ? htmlspecialchars($pricing_data[$category['category_id']]['duration']) : ''; ?>"
                               style="width: 100%; padding: 8px; margin-top: 5px;"
                               placeholder="e.g. 2 hours, Half day">
                    </div> -->
                </div>
                <div style="margin-top: 10px;">
                    <label for="desc-<?php echo $category['category_id']; ?>">Description</label>
                    <textarea id="desc-<?php echo $category['category_id']; ?>" 
                              name="category_prices[<?php echo $category['category_id']; ?>][description]" 
                              rows="2" style="width: 100%; padding: 8px; margin-top: 5px;"
                              placeholder="What's included in this package"><?php echo isset($pricing_data[$category['category_id']]) ? htmlspecialchars($pricing_data[$category['category_id']]['description']) : ''; ?></textarea>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
                            <div class="form-group">
                                <label for="upi_id">UPI ID</label>
                                <input type="text" id="upi_id" name="upi_id" value="<?php echo htmlspecialchars($photographer['upi_id']); ?>" required placeholder="yourname@upi">
                                <small style="color: #666; margin-top: 5px; display: block;">Enter your UPI ID for receiving payments (e.g., yourname@upi)</small>
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
        const upiInput = document.getElementById('upi_id');
        
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
            
            // UPI ID validation
            if (upiInput.value.trim() === '') {
                upiInput.style.borderColor = 'red';
                isValid = false;
            } else {
                upiInput.style.borderColor = '';
            }
            
            if (!isValid) {
                event.preventDefault();
                alert('Please fix the errors before submitting.');
            }
        });
        document.addEventListener("DOMContentLoaded", function() {
    // First, make sure the LocationSelector is initialized
    const locationSelector = new LocationSelector('state', 'city');
    
    // Define the selected state and city from PHP
    const selectedState = "<?php echo addslashes($selectedState); ?>";
    const selectedCity = "<?php echo addslashes($selectedCity); ?>";
    
    // Wait for the states to be loaded before setting values
    const checkStatesLoaded = setInterval(function() {
        const stateSelect = document.getElementById('state');
        
        // Once states are loaded
        if (stateSelect.options.length > 1) {
            clearInterval(checkStatesLoaded);
            
            // Set the selected state if it exists
            if (selectedState) {
                stateSelect.value = selectedState;
                
                // Trigger the change event to load cities
                const changeEvent = new Event('change');
                stateSelect.dispatchEvent(changeEvent);
                
                // Wait for cities to be loaded before setting the city value
                const checkCitiesLoaded = setInterval(function() {
                    const citySelect = document.getElementById('city');
                    
                    // Once cities are loaded
                    if (!citySelect.disabled && citySelect.options.length > 1) {
                        clearInterval(checkCitiesLoaded);
                        
                        // Set the selected city if it exists
                        if (selectedCity) {
                            citySelect.value = selectedCity;
                        }
                    }
                }, 100);
            }
        }
    }, 100);
    
    // Update the hidden location field when form is submitted
    document.querySelector('form').addEventListener('submit', function(e) {
        const locationString = locationSelector.getLocationString();
        if (locationString) {
            document.getElementById('location').value = locationString;
        } else {
            e.preventDefault();
            alert('Please select both state and city');
        }
    });
});

// Improved categories selection handler to show pricing sections in real-time
document.getElementById('categories').addEventListener('change', function() {
    // Get all selected options
    const selectElement = this;
    const selectedOptions = [];
    
    // Get all selected options text
    for (let i = 0; i < selectElement.options.length; i++) {
        if (selectElement.options[i].selected) {
            selectedOptions.push(selectElement.options[i].text);
        }
    }
    
    // Update the display of pricing sections
    document.querySelectorAll('.pricing-item').forEach(item => {
        const categoryName = item.querySelector('h4').textContent;
        if (selectedOptions.includes(categoryName)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
});

// Run the categories change handler once on page load to ensure correct initial state
document.addEventListener('DOMContentLoaded', function() {
    // Trigger the change event on the categories select element
    const categoriesSelect = document.getElementById('categories');
    if (categoriesSelect) {
        const event = new Event('change');
        categoriesSelect.dispatchEvent(event);
    }
});
</script>
<script src="location-selector.js"></script>
</body>
</html>