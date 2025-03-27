<?php
include 'dbconnect.php';

$categories = [];
$cat_query = "SELECT * FROM tbl_categories ORDER BY category_name";
$result = $conn->query($cat_query);
if($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}
$name = $email = $phno = $password = $location = $bio = $category = $idproof_path = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = test_input($_POST["name"]);
    $email = test_input($_POST["email"]);
    $phno = test_input($_POST["phno"]);
    $password = test_input($_POST["password"]);
    $state = isset($_POST["state"]) ? test_input($_POST["state"]) : "";
    $city = isset($_POST["city"]) ? test_input($_POST["city"]) : "";
    $location = $city . ", " . $state;
    $bio = test_input($_POST["bio"]);
    
    $category_ids = isset($_POST["categories"]) ? $_POST["categories"] : [];
    $category = implode(",", $category_ids);
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Handle file upload
    if(isset($_FILES['idproof']) && $_FILES['idproof']['error'] == 0) {
        $allowed = array('jpg', 'jpeg', 'png', 'pdf');
        $filename = $_FILES['idproof']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        if(in_array(strtolower($filetype), $allowed)) {
            // Create unique filename
            $new_filename = uniqid('idproof_') . '.' . $filetype;
            $upload_dir = 'uploads/idproofs/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $upload_path = $upload_dir . $new_filename;
            
            if(move_uploaded_file($_FILES['idproof']['tmp_name'], $upload_path)) {
                $idproof_path = $upload_path;
            } else {
                echo "Error uploading file.";
                exit;
            }
        } else {
            echo "Invalid file type. Please upload PDF, JPG or PNG files only.";
            exit;
        }
    } else {
        echo "Error: ID proof is required.";
        exit;
    }
   
    $stmt = $conn->prepare("INSERT INTO tbl_user (name, email, phno, password, status, role) VALUES (?, ?, ?, ?, ?, ?)");
    $status = false; 
    $role = "photographer";
    $stmt->bind_param("ssssis", $name, $email, $phno, $hashed_password, $status, $role);

    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;

        $stmt2 = $conn->prepare("INSERT INTO tbl_photographer (photographer_id, bio, location, category, approval_status, id_proof) VALUES (?, ?, ?, ?, ?, ?)");
        $approval_status = 'pending';
        $stmt2->bind_param("isssss", $user_id, $bio, $location, $category, $approval_status, $idproof_path);
        
        if ($stmt2->execute()) {
            header('location: pending_approval.php');
            exit;
        } else {
            echo "Error inserting photographer details: " . $stmt2->error;
        }

        $stmt2->close();
    } else {
        echo "Error inserting user details: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}

function test_input($data) {
    $data = trim($data);        
    $data = stripslashes($data); 
    $data = htmlspecialchars($data); 
    return $data;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LensPro - Photographer Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="location-selector.js"></script>
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-gray: #f5f6fa;
            --dark-gray: #2c3e50;
            --hover-color: #2980b9;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #ecf0f1;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        nav {
            background: var(--primary-color);
            padding: 0.5rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .logo-container {
            display: flex;
            align-items: center;
        }

        .logo img {
            width: 70px;
            height: auto;
        }

        .logo {
            color: white;
            font-size: 2.5rem;
            font-weight: 600;
            letter-spacing: 2px;
            font-family: Georgia, 'Times New Roman', Times, serif;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .auth-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
            display: flex;
            gap: 2rem;
            flex: 1;
        }

        .auth-form {
            flex: 1;
            background: white;
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .form-title {
            color: var(--primary-color);
            margin-bottom: 2rem;
            font-size: 2rem;
            text-align: center;
            position: relative;
            padding-bottom: 10px;
        }

        .form-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: var(--secondary-color);
            border-radius: 2px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
            font-weight: 500;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease-in-out;
            background: #fff;
            box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.05);
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: var(--secondary-color);
            outline: none;
            box-shadow: 0 0 8px rgba(52, 152, 219, 0.6);
        }

        /* Enhancing the Select Dropdown */
        .form-group select {
            appearance: none;
            background: white url("https://cdn-icons-png.flaticon.com/512/25/25623.png") no-repeat right 15px center;
            background-size: 15px;
            padding-right: 2.5rem;
            cursor: pointer;
            min-height: 3.5rem;
        }

        .form-group select[multiple] {
            min-height: 180px;
            background-image: none;
        }

        .submit-btn {
            background: linear-gradient(135deg, var(--secondary-color), var(--hover-color));
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease-in-out;
            font-weight: 600;
            box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.2);
            width: 100%;
            margin-top: 1rem;
            letter-spacing: 1px;
        }

        .submit-btn:hover {
            background: linear-gradient(135deg, var(--hover-color), #1e67a1);
            transform: translateY(-2px);
        }

        .form-group textarea {
            resize: none;
            min-height: 120px;
        }

        .toggle-form {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--dark-gray);
        }

        .toggle-form a {
            color: var(--secondary-color);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .toggle-form a:hover {
            color: var(--hover-color);
            text-decoration: underline;
        }

        .benefits-section {
            flex: 1;
            padding: 2.5rem;
            background: var(--primary-color);
            border-radius: 15px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .benefits-title {
            font-size: 2.2rem;
            margin-bottom: 2.5rem;
            text-align: center;
            position: relative;
            padding-bottom: 15px;
        }

        .benefits-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: var(--secondary-color);
            border-radius: 2px;
        }
        .benefit-logo{
            text-align:center;
        }

        .benefit-item {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            transition: transform 0.3s ease;
        }

        .benefit-item:hover {
            transform: translateX(10px);
        }

        .benefit-item i {
            font-size: 2rem;
            margin-right: 1.5rem;
            color: var(--secondary-color);
            width: 40px;
            text-align: center;
        }

        .benefit-item p {
            font-size: 1.1rem;
            line-height: 1.5;
        }

        footer {
            background: var(--primary-color);
            color: white;
            padding: 1.5rem 5%;
            text-align: center;
            margin-top: auto;
            font-size: 0.9rem;
        }
        .nav-links {
            display: flex;
            gap: 2rem;
            padding-right: 2%;
            
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            text-transform: uppercase;
            font-size: 1rem;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: var(--secondary-color);
        }

        @media (max-width: 992px) {
            .auth-container {
                flex-direction: column;
                padding: 0 1.5rem;
            }
            
            .benefits-section {
                order: -1;
                margin-bottom: 2rem;
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
    <nav>
        <div class="logo-container">
            <a href="index.php" class="logo">
                <img src="images/logowithoutname.png" alt="LensPro Logo">
                LensPro
            </a>
        </div>
        <div class="nav-links">
            <a href="index.php">Home</a>
         </div>
    </nav>

    <div class="auth-container">
        <div class="auth-form" id="registerForm">
            <h2 class="form-title">Photographer Registration</h2>
            <form action="photographerregis.php" method="POST" enctype="multipart/form-data">
            <div class="form-grid">
    <div class="form-group">
        <label for="name">Full Name</label>
        <input type="text" id="name" name="name" placeholder="Enter your full name" required>
        <span style="color:red;"></span>
    </div>
    <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" placeholder="Enter your email" required>
        <span style="color:red;"></span>
    </div>
    <div class="form-group">
        <label for="phone">Phone Number</label>
        <input type="tel" id="phone" name="phno" placeholder="Enter your phone number" required>
        <span style="color:red;"></span>
    </div>
    <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Create a strong password" required>
        <span style="color:red;"></span>
    </div>
    <div class="form-group">
    <label for="state">State</label>
    <select id="state" name="state" required>
        <option value="">Select State</option>
        <!-- States will be populated by JavaScript -->
        </select>
    </div>

    <input type="hidden" id="location" name="location" value="">

    <div class="form-group">
        <label for="city">City</label>
        <select id="city" name="city" required>
            <option value="">Select City</option>
            <!-- Cities will be populated based on selected state -->
        </select>
    </div>

    <div class="form-group full-width">
        <label for="bio">Professional Bio</label>
        <textarea id="bio" name="bio" rows="3" placeholder="Tell clients about yourself, your experience, and your style" required></textarea>
    </div>
    <div class="form-group full-width">
    <label for="categories">Photography Categories (Hold Ctrl/Cmd to select multiple)</label>
    <select id="categories" name="categories[]" multiple required>
        <?php foreach($categories as $category): ?>
            <option value="<?php echo $category['category_name']; ?>"><?php echo $category['category_name']; ?></option>
        <?php endforeach; ?>
    </select>
</div>

    <div class="form-group full-width">
    <label for="idproof">Any Photographer Association ID Proof</label>
    <small>Upload a valid photographer association ID card or certificate (PDF, JPG, PNG formats only)</small>
    <input type="file" id="idproof" name="idproof" accept=".pdf,.jpg,.jpeg,.png" required>
    
    </div>
    </div>
            <button type="submit" class="submit-btn">Create Your Profile</button>
            </form>
            <div class="toggle-form">
                Already have an account? <a href="login.php">Login Here</a>
            </div>
        </div>

        <div class="benefits-section">
            <div class="benefit-logo"><img src="images/lenspro.png" alt="LensPro Logo"></a></div>
            <h2 class="benefits-title">Why Join LensPro?</h2>
            <div class="benefit-item">
                <i class="fas fa-users"></i>
                <p>Connect with clients looking for professional photography services</p>
            </div>
            <div class="benefit-item">
                <i class="fas fa-calendar-check"></i>
                <p>Manage your bookings and schedule efficiently</p>
            </div>
            <div class="benefit-item">
                <i class="fas fa-image"></i>
                <p>Showcase your portfolio to a wider audience</p>
            </div>
            <div class="benefit-item">
                <i class="fas fa-star"></i>
                <p>Build your reputation through client reviews</p>
            </div>
            <div class="benefit-item">
                <i class="fas fa-globe"></i>
                <p>Expand your reach and grow your photography business</p>
            </div>
        </div>
    </div>
    
    <footer>
        <p>&copy; 2025 LensPro. All rights reserved.</p>
    </footer>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
    const form = document.querySelector("form");
    const nameInput = document.querySelector("input[name='name']");
    const emailInput = document.querySelector("input[name='email']");
    const phnoInput = document.querySelector("input[name='phno']");
    const passwordInput = document.querySelector("input[name='password']");
    
    function showError(input, message) {
        let errorSpan = input.nextElementSibling;
        if (!errorSpan || errorSpan.tagName !== "SPAN") {
            errorSpan = document.createElement("span");
            errorSpan.style.color = "red";
            input.parentNode.insertBefore(errorSpan, input.nextSibling);
        }
        errorSpan.textContent = message;
        return false;
    }

    function clearError(input) {
        let errorSpan = input.nextElementSibling;
        if (errorSpan && errorSpan.tagName === "SPAN") {
            errorSpan.textContent = "";
        }
        return true;
    }

    function validateName(name) {
        const namePattern = /^[a-zA-Z-' ]+$/;
        return namePattern.test(name) ? 
            clearError(nameInput) : 
            showError(nameInput, "*Only letters and white space allowed");
    }

    function validatePhone(phone) {
        const phnoPattern = /^[6-9][0-9]{9}$/;
        return phnoPattern.test(phone) ? 
            clearError(phnoInput) : 
            showError(phnoInput, "*Invalid mobile number, must be 10 digits");
    }

    function validatePassword(password) {
        const passPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d@$!%?&]{8,}$/;
        return passPattern.test(password) ? 
            clearError(passwordInput) : 
            showError(passwordInput, "*Password must have atleast 8 characters with uppercase, lowercase, and a number.");
    }

    function validateEmail(email) {
        const emailPattern = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return emailPattern.test(email) ? 
            clearError(emailInput) : 
            showError(emailInput, "*Please enter a valid email address");
    }

    // Real-time validation
    nameInput.addEventListener("input", function() {
        validateName(this.value);
    });

    phnoInput.addEventListener("input", function() {
        validatePhone(this.value);
    });

    passwordInput.addEventListener("input", function() {
        validatePassword(this.value);
    });

    emailInput.addEventListener("input", function() {
        validateEmail(this.value);
    });

   
    form.addEventListener("submit", function(event) {
       
        event.preventDefault();
        
      
        const isNameValid = validateName(nameInput.value);
        const isPhoneValid = validatePhone(phnoInput.value);
        const isPasswordValid = validatePassword(passwordInput.value);
        const isEmailValid = validateEmail(emailInput.value);

        if (isNameValid && isPhoneValid && isPasswordValid && isEmailValid) {
            this.submit();
        } else {
            alert("Please fix all errors before submitting");
        }
    });
});
// Initialize the location selector
document.addEventListener("DOMContentLoaded", function() {
    const locationSelector = new LocationSelector('state', 'city');
    
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
</script>
</body>
</html>