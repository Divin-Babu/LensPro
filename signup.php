<?php
include 'dbconnect.php';
$name = $email = $phno = $password = $reppass = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = test_input($_POST["name"]);
    $email = test_input($_POST["email"]);
    $phno = test_input($_POST["phno"]);
    $password = test_input($_POST["password"]);
    $reppass = test_input($_POST["repeatpassword"]);
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO tbl_user (name, email, phno, password, status, role) VALUES ('$name', '$email', '$phno', '$hashed_password', TRUE, 'user')";
        if (mysqli_query($conn, $sql)) {
            header('location:login.php'); 
            exit;
        } else {
            
            echo "Error: " . mysqli_error($conn);
        }
    mysqli_close($conn);
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
    <title>Signup</title>
    <style>
        *{
            font-family: 'Poppins', sans-serif;

        }
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column; /* Stack content vertically */
            height: 110vh;
            margin: 0;
            background-image: url('images/bgimgsign.jpg'); /* Replace with your image URL */
            background-size: cover;
            background-position: center;
        }

        .form {
            background-color: rgba(255, 255, 255, 0.8); /* Semi-transparent white background */
            display: block;
            padding: 1rem;
            max-width: 350px;
            width: 100%; /* Make sure form doesn't exceed screen width on smaller devices */
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            animation: slideUp 0.5s ease-out;
        }

        .form-title {
            font-size: 1.75rem;
            line-height: 0.75rem;
            font-weight: 600;
            text-align: center;
            color: #000;
        }

        .input-container {
            position: relative;
            font-family: 'Poppins', sans-serif;
        }

        .input-container input, .form button {
            outline: none;
            border: 1px solid #e5e7eb;
            margin: 8px 0;
        }

        .input-container input {
            background-color: #fff;
            padding: 1rem;
            padding-right: 2rem;
            font-size: 0.875rem;
            line-height: 1.25rem;
            width: 300px;
            border-radius: 0.5rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }

        .submit {
            display: block;
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
            padding-left: 1.25rem;
            padding-right: 1.25rem;
            background-color: #4F46E5;
            color: #ffffff;
            font-size: 0.875rem;
            line-height: 1.25rem;
            font-weight: 500;
            width: 100%;
            border-radius: 0.5rem;
            text-transform: uppercase;
        }

        .signup-link {
            color: #6B7280;
            font-size: 1rem;
            line-height: 1.25rem;
            text-align: center;
        }

        .signup-link a {
            text-decoration: underline;
            color: #4F46E5;
        }

      
        img {
            width: 150px; /* Adjust image size */
            height: auto;
            padding-bottom: 0.5rem;
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
    <a href="index.php"> <img src="images/lenspro.png" alt="LensPro Logo"></a>
    <form class="form" action="signup.php" method="POST">
       <p class="form-title">Create Account</p>
        <div class="input-container">
          <input type="text" placeholder="Your Name" name="name" required><br>
          <span style="color:red;"></span>
        </div>
      <div class="input-container">
          <input type="email" placeholder="Your Email" name="email" required>
        </div>
        <div class="input-container">
            <input type="text" placeholder="Phone Number" name="phno" required><br>
            <span style="color:red;"></span>
          </div>
      <div class="input-container">
        <input type="password" placeholder="Password" name="password" required><br>
        <span style="color:red;"></span>
        </div>
        <div class="input-container">
            <input type="password" placeholder="Repeat Password" name="repeatpassword" required><br>
            <span style="color:red;"></span>
            </div>
         <button type="submit" class="submit">
        Signup
      </button>

      <p class="signup-link">
        Already have an account?&nbsp; 
        <a href="login.php">Login</a>
      </p>
   </form>
   <script>
  document.addEventListener("DOMContentLoaded", function() {
    const nameInput = document.querySelector("input[name='name']");
    const emailInput = document.querySelector("input[name='email']");
    const phnoInput = document.querySelector("input[name='phno']");
    const passwordInput = document.querySelector("input[name='password']");
    const repeatPasswordInput = document.querySelector("input[name='repeatpassword']");
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

    nameInput.addEventListener("input", function() {
      const namePattern = /^[a-zA-Z-' ]+$/;
      if (!namePattern.test(this.value)) {
        showError(this, "*Only letters and white space allowed");
      } else {
        clearError(this);
      }
    });

    phnoInput.addEventListener("input", function() {
      const phnoPattern = /^[6-9][0-9]{9}$/;
      if (!phnoPattern.test(this.value)) {
        showError(this, "*Invalid mobile number, must be 10 digits");
      } else {
        clearError(this);
      }
    });

    passwordInput.addEventListener("input", function() {
      const passPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d@$!%?&]{8,}$/;
      if (!passPattern.test(this.value)) {
        showError(this, "*Password must have atleast 8 characters with uppercase, lowercase, and a number.");
      } else {
        clearError(this);
      }
    });

    repeatPasswordInput.addEventListener("input", function() {
      if (this.value !== passwordInput.value) {
        showError(this, "*Password not matching");
      } else {
        clearError(this);
      }
    });

    submitButton.addEventListener("click", function(event) {
      if (
        document.querySelector("span")?.textContent !== "" 
      ) {
        event.preventDefault();
        alert("Please fix the errors before submitting");
      }
    });
  });
</script>

</body>
</html>
