<?php
session_start();
include 'dbconnect.php';


$email = "";
$email_error = "";
$password_error = "";
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email)) {
        $email_error = "Email is required.";
    }
    if (empty($password)) {
        $password_error = "Password is required.";
    }

    if (empty($email_error) && empty($password_error)) {
        $email = mysqli_real_escape_string($conn, $email);
        
        $sql = "SELECT user_id, name, password, profile_pic , role FROM tbl_user WHERE email = ? and status = TRUE";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt); 
            $result = mysqli_stmt_get_result($stmt);

            if ($row = mysqli_fetch_assoc($result)) {
                
                if (password_verify($password, $row['password'])) {

                    if ($row['role'] == 'admin') {
                        $_SESSION['role'] = 'admin';
                        header("Location: adminpanel.php");
                        exit();
                    }
                    else if ($row['role'] == 'user'){
                        // $_SESSION['user'] = $row['name'];
                        $_SESSION['userid']= $row['user_id'];
                        $_SESSION['role'] = 'user';
                        // $_SESSION['profile_pic']=$row['profile_pic'];
                        header('location:index.php');
                        exit();
                    }
                    else
                    {
                        $_SESSION['userid']= $row['user_id'];
                        $_SESSION['role'] = 'photographer';
                        header('location:photographerdash.php');
                        exit();
                    }
                } 
             
                else {
                    $error_message = "Invalid email or password.";
                }
            } 
            else {
                $error_message = "Invalid email or password.";
            }
            mysqli_stmt_close($stmt);
        }
        else {
            $error_message = "Database error: " . mysqli_error($conn);
        }
    }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        *{
            font-family: 'Poppins', sans-serif;
        }
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            height: 100vh;
            margin: 0;
            background-image: url('images/bgimglog.jpg');
            background-size: cover;
            background-position: center;
        }
        .form {
            background-color: rgba(255, 255, 255, 0.8);
            padding: 1rem;
            max-width: 350px;
            width: 100%;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            animation: slideUp 0.5s ease-out;
        }
        .form-title {
            font-size: 1.5rem;
            font-weight: 600;
            text-align: center;
            color: #000;
        }
        .input-container input, .form button {
            outline: none;
            border: 1px solid #e5e7eb;
            margin: 8px 0;
        }
        span {
            margin-left: 10px;
            color: red;
            font-size: 0.9rem;
        }
        .input-container input {
            width: 90%;
            padding: 1rem;
            margin: 8px 0;
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
        }

        .input-container input::placeholder {
            font-family: 'Poppins', sans-serif; 
            font-size: 0.9rem;
            color: #6B7280; 
        }
    
        .submit {
            display: block;
            margin-top: 10px;
            padding: 0.75rem;
            background-color: #4F46E5;
            color: #ffffff;
            font-size: 0.875rem;
            font-weight: 500;
            width: 100%;
            border-radius: 0.5rem;
            text-transform: uppercase;
        }
        .signup-link, .forgot-password {
            color: #6B7280;
            font-size: 1rem;
            text-align: center;
        }
        .signup-link a, .forgot-password a {
            text-decoration: underline;
            color: #4F46E5;
        }
        .error-message {
            color: red;
            text-align: center;
            min-height: 20px;
            margin-bottom: 5px;
            font-size: 1rem;
        }
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <a href="index.php"><img src="images/lenspro.png" alt="LensPro Logo"></a>
    <form class="form" method="POST">
        <p class="form-title">Sign in to your account</p>
        <div class="error-message">
            <?php if (!empty($error_message)) echo "<p>$error_message</p>"; ?>
        </div>
        <div class="input-container">
            <input type="email" placeholder="Enter email" name="email" value="<?php echo htmlspecialchars($email); ?>">
            <span><?php echo $email_error; ?></span>
        </div>
        <div class="input-container">
            <input type="password" placeholder="Enter password" name="password">
            <span><?php echo $password_error; ?></span>
        </div>
        <button type="submit" class="submit">Sign in</button>
        <p class="forgot-password"><a href="forgetpass.php">Forgot Password?</a></p>
        <p class="signup-link">New to LensPro? <a href="signup.php">Create Account</a></p>
    </form>
    
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const emailInput = document.querySelector("input[name='email']");
        const passwordInput = document.querySelector("input[name='password']");
        const submitButton = document.querySelector(".submit");

        function showError(input, message) {
            let errorSpan = input.nextElementSibling;
            if (!errorSpan || errorSpan.tagName !== "SPAN") {
                errorSpan = document.createElement("span");
                errorSpan.style.color = "red";
                input.parentNode.insertBefore(errorSpan, input.nextSibling);
            }
            errorSpan.textContent = message;
        }

        function clearError(input) {
            let errorSpan = input.nextElementSibling;
            if (errorSpan && errorSpan.tagName === "SPAN") {
                errorSpan.textContent = "";
            }
        }

        emailInput.addEventListener("input", function() {
            // RFC 5322 compliant email validation regex
            const emailPattern = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
            if (!emailPattern.test(this.value)) {
                showError(this, "*Please enter a valid email address");
            } else {
                clearError(this);
            }
        });

        passwordInput.addEventListener("input", function() {
            if (this.value.length < 8) {
                showError(this, "*Password must be at least 8 characters");
            } else {
                clearError(this);
            }
        });

        submitButton.addEventListener("click", function(event) {
            if (document.querySelector("span")?.textContent !== "") {
                event.preventDefault();
                alert("Please fix the errors before submitting");
            }
        });
    });
    </script>
</body>
</html>