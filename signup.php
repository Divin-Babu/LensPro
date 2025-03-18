<?php
include 'dbconnect.php';
$name = $email = $phno = $password = $reppass = "";
$name_err = $email_err = $phno_err = $password_err = $reppass_err = "";
$form_valid = true;

// Check if this is an AJAX request to validate email
if(isset($_POST['check_email'])) {
    $email = test_input($_POST['check_email']);
    
    
    $check_sql = "SELECT * FROM tbl_user WHERE email = '$email'";
    $result = mysqli_query($conn, $check_sql);
    
    if(mysqli_num_rows($result) > 0) {
        echo "exists";
    } else {
        echo "not_exists";
    }
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['check_email'])) {
    // Validate name
    $name = test_input($_POST["name"]);
    if (!preg_match("/^[a-zA-Z-' ]+$/", $name)) {
        $name_err = "*Only letters and white space allowed";
        $form_valid = false;
    }
    
    // Validate email
    $email = test_input($_POST["email"]);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $email_err = "*Please enter a valid email address";
        $form_valid = false;
    } else {
        // Check if email already exists
        $check_sql = "SELECT * FROM tbl_user WHERE email = '$email'";
        $result = mysqli_query($conn, $check_sql);
        
        if(mysqli_num_rows($result) > 0) {
            $email_err = "*Email already exists. Please try login</a>";
            $form_valid = false;
        }
    }
    
    // Validate phone number
    $phno = test_input($_POST["phno"]);
    if (!preg_match("/^[6-9][0-9]{9}$/", $phno)) {
        $phno_err = "*Invalid mobile number, must be 10 digits";
        $form_valid = false;
    }
    
    // Validate password
    $password = test_input($_POST["password"]);
    if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d@$!%?&]{8,}$/", $password)) {
        $password_err = "*Password must have atleast 8 characters with uppercase, lowercase, and a number.";
        $form_valid = false;
    }
    
    // Validate repeated password
    $reppass = test_input($_POST["repeatpassword"]);
    if ($reppass != $password) {
        $reppass_err = "*Password not matching";
        $form_valid = false;
    }
    
    // If form is valid, proceed with database insertion
    if ($form_valid) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO tbl_user (name, email, phno, password, status, role) VALUES ('$name', '$email', '$phno', '$hashed_password', TRUE, 'user')";
        if (mysqli_query($conn, $sql)) {
            header('location:login.php'); 
            exit;
        } else {
            echo "Error: " . mysqli_error($conn);
        }
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
    <form class="form" action="signup.php" method="POST" onsubmit="return validateForm()">
       <p class="form-title">Create Account</p>
        <div class="input-container">
          <input type="text" placeholder="Your Name" name="name" value="<?php echo $name; ?>" required>
          <span style="color:red;"><?php echo $name_err; ?></span>
        </div>
      <div class="input-container">
          <input type="email" placeholder="Your Email" name="email" id="email" value="<?php echo $email; ?>" required>
          <span style="color:red;" id="email_error"><?php echo $email_err; ?></span>
        </div>
        <div class="input-container">
            <input type="text" placeholder="Phone Number" name="phno" value="<?php echo $phno; ?>" required>
            <span style="color:red;"><?php echo $phno_err; ?></span>
          </div>
      <div class="input-container">
        <input type="password" placeholder="Password" name="password" required>
        <span style="color:red;"><?php echo $password_err; ?></span>
        </div>
        <div class="input-container">
            <input type="password" placeholder="Repeat Password" name="repeatpassword" required>
            <span style="color:red;"><?php echo $reppass_err; ?></span>
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

    emailInput.addEventListener("input", function() {
      const emailPattern = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
      if (!emailPattern.test(this.value)) {
        showError(this, "*Please enter a valid email address");
      } else {
        clearError(this);
        // Check if email exists in database
        checkEmailExists(this.value);
      }
    });

    // Function to check if email exists using AJAX
    function checkEmailExists(email) {
      if (!email) return;
      
      
      const formData = new FormData();
      formData.append('check_email', email);
      
      
      fetch('signup.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.text())
      .then(data => {
        const emailErrorSpan = document.getElementById('email_error');
        if (data === 'exists') {
          emailErrorSpan.innerHTML = "*Email already exists. Please try login";
        } else {
          emailErrorSpan.textContent = "";
        }
      })
      .catch(error => {
        console.error('Error checking email:', error);
      });
    }

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
  });


  function validateForm() {
    const nameInput = document.querySelector("input[name='name']");
    const emailInput = document.querySelector("input[name='email']");
    const phnoInput = document.querySelector("input[name='phno']");
    const passwordInput = document.querySelector("input[name='password']");
    const repeatPasswordInput = document.querySelector("input[name='repeatpassword']");
    const emailErrorSpan = document.getElementById('email_error');
    
    let isValid = true;
    
   
    const namePattern = /^[a-zA-Z-' ]+$/;
    if (!namePattern.test(nameInput.value)) {
      isValid = false;
    }
    
  
    const emailPattern = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    if (!emailPattern.test(emailInput.value)) {
      isValid = false;
    }
    
    // Check if there's an email error message about existing email
    if (emailErrorSpan.textContent.includes("already exists")) {
      isValid = false;
    }

    const phnoPattern = /^[6-9][0-9]{9}$/;
    if (!phnoPattern.test(phnoInput.value)) {
      isValid = false;
    }
    
  
    const passPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d@$!%?&]{8,}$/;
    if (!passPattern.test(passwordInput.value)) {
      isValid = false;
    }
    
  
    if (repeatPasswordInput.value !== passwordInput.value) {
      isValid = false;
    }
    
    
    if (!isValid) {
      alert("Please fix the errors before submitting");
    }
    
    return isValid;
  }
</script>

</body>
</html>