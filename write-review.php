<?php
session_start();
include 'dbconnect.php';

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    header('Location: login.php');
    exit();
}

// Validate incoming parameters
$photographer_id = isset($_GET['photographer_id']) ? intval($_GET['photographer_id']) : 0;
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;


$verify_query = "SELECT * FROM tbl_booking 
                 WHERE booking_id = ? 
                 AND user_id = ? 
                 AND status = 'completed'";
$stmt = mysqli_prepare($conn, $verify_query);
mysqli_stmt_bind_param($stmt, "ii", $booking_id, $_SESSION['userid']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$booking = mysqli_fetch_assoc($result);

if (!$booking) {
    $_SESSION['message'] = "Invalid booking or access denied.";
    $_SESSION['message_type'] = "error";
    header('Location: my-booking.php');
    exit();
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    $review_text = isset($_POST['review_text']) ? trim($_POST['review_text']) : '';

    // Validate inputs
    if ($rating < 1 || $rating > 5) {
        $_SESSION['message'] = "Please select a valid rating between 1 and 5 stars.";
        $_SESSION['message_type'] = "error";
    } elseif (empty($review_text)) {
        $_SESSION['message'] = "Please provide a review description.";
        $_SESSION['message_type'] = "error";
    } else {
        $check_review_query = "SELECT * FROM tbl_reviews 
                      WHERE user_id = ? 
                      AND booking_id = ?";
            $check_stmt = mysqli_prepare($conn, $check_review_query);
            mysqli_stmt_bind_param($check_stmt, "ii", $_SESSION['userid'], $booking_id);
            mysqli_stmt_execute($check_stmt);
            $existing_review = mysqli_stmt_get_result($check_stmt);

            if (mysqli_num_rows($existing_review) > 0) {
                $_SESSION['message'] = "You have already reviewed this booking.";
                $_SESSION['message_type'] = "error";
            } else {
            // Insert review
            $insert_query = "INSERT INTO tbl_reviews 
                             (user_id, photographer_id, booking_id, rating, review_text) 
                             VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($insert_stmt, "iiiss", 
                $_SESSION['userid'], $photographer_id, $booking_id, $rating, $review_text);
            
            if (mysqli_stmt_execute($insert_stmt)) {
                $_SESSION['message'] = "Review submitted successfully!";
                $_SESSION['message_type'] = "success";
                header('Location: my-booking.php');
                exit();
            } else {
                $_SESSION['message'] = "Error submitting review. Please try again.";
                $_SESSION['message_type'] = "error";
            }
        }
    }
}

// Get photographer details
$photographer_query = "SELECT name, profile_pic FROM tbl_user WHERE user_id = ?";
$photographer_stmt = mysqli_prepare($conn, $photographer_query);
mysqli_stmt_bind_param($photographer_stmt, "i", $photographer_id);
mysqli_stmt_execute($photographer_stmt);
$photographer_result = mysqli_stmt_get_result($photographer_stmt);
$photographer = mysqli_fetch_assoc($photographer_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Write Review - LensPro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Reuse styles from my-booking.php */
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --white: #ffffff;
            --text-color: #333;
            --light-gray: #f5f6fa;
            --overlay-color: rgba(44, 62, 80, 0.7);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-image: url('images/mybookingbg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            position: relative;
            line-height: 1.6;
            color: var(--text-color);
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: var(--overlay-color);
            z-index: -1;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            width: 95%;
        }

        .review-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-top: 50px;
        }

        .photographer-avatar {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            object-fit: cover;
            align-self: center;
            margin: 0 auto 20px;
            display: block;
            border: 4px solid var(--secondary-color);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            text-align: center;
            margin-bottom: 30px;
            color: var(--white);
            font-size: 2.5rem;
            font-weight: 600;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .review-textarea {
            width: 100%;
            min-height: 150px;
            padding: 15px;
            border: 2px solid var(--secondary-color);
            border-radius: 8px;
            margin-bottom: 20px;
            resize: vertical;
        }

        .submit-review-btn {
            display: block;
            width: 100%;
            padding: 15px;
            background-color: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }

        .submit-review-btn:hover {
            background-color: var(--primary-color);
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
            font-size: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .message-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .rating {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
            direction: rtl;  /* Right to left direction */
        }

        .rating input {
            display: none;
        }

        .rating label {
            font-size: 3rem;
            color: #ddd;
            cursor: pointer;
            transition: color 0.3s;
            margin: 0 5px;
        }

        .rating input:checked ~ label,
        .rating input:hover ~ label,
        .rating label:hover {
            color: #f1c40f;
        }

    .button-container {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .go-back-btn {
            display: block;
            width: 50%;
            padding: 15px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s ease;
            text-align: center;
            text-decoration: none;
        }

        .submit-review-btn {
            width: 50%;
        }

        .go-back-btn:hover {
            background-color: var(--secondary-color);
        }
        </style>
</head>
<body>
    <div class="container">
        <?php
        if (isset($_SESSION['message'])) {
            $message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'info';
            echo "<div class='message message-{$message_type}'>{$_SESSION['message']}</div>";
            
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
        }
        ?>

        <h1 class="section-title">Write a Review</h1>

        <div class="review-card">
            <?php 
            $photographer_pic = $photographer['profile_pic'] ? 
                htmlspecialchars($photographer['profile_pic']) : 
                'images/default-photographer.jpg'; 
            ?>
            <img src="<?php echo $photographer_pic; ?>" alt="Photographer" class="photographer-avatar">
            
            <form method="POST">
                <div class="rating">
                    <input type="radio" id="star5" name="rating" value="5">
                    <label for="star5" class="fas fa-star"></label>
                    <input type="radio" id="star4" name="rating" value="4">
                    <label for="star4" class="fas fa-star"></label>
                    <input type="radio" id="star3" name="rating" value="3">
                    <label for="star3" class="fas fa-star"></label>
                    <input type="radio" id="star2" name="rating" value="2">
                    <label for="star2" class="fas fa-star"></label>
                    <input type="radio" id="star1" name="rating" value="1">
                    <label for="star1" class="fas fa-star"></label>
                </div>

                <textarea 
                    name="review_text" 
                    class="review-textarea" 
                    placeholder="Tell us about your experience with <?php echo htmlspecialchars($photographer['name']); ?>..."
                ></textarea>

                <div class="button-container">
                    <a href="my-booking.php" class="go-back-btn">Go Back</a>
                    <button type="submit" class="submit-review-btn">Submit Review</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>