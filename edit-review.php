<?php
session_start();
include 'dbconnect.php';

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    header('Location: login.php');
    exit();
}

// Check if review_id is provided
if (!isset($_GET['review_id'])) {
    $_SESSION['message'] = "Review ID is missing.";
    $_SESSION['message_type'] = "error";
    header('Location: my-booking.php');
    exit();
}

$review_id = $_GET['review_id'];

// Fetch review details and verify ownership
$review_query = "
    SELECT 
        r.review_id, 
        r.rating, 
        r.review_text, 
        r.created_at,
        r.photographer_id,
        u.name AS photographer_name,
        u.profile_pic AS photographer_pic
    FROM tbl_reviews r 
    JOIN tbl_user u ON r.photographer_id = u.user_id
    WHERE r.review_id = ? AND r.user_id = ?
";

$stmt = mysqli_prepare($conn, $review_query);
mysqli_stmt_bind_param($stmt, "ii", $review_id, $_SESSION['userid']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    $_SESSION['message'] = "Review not found or you don't have permission to edit it.";
    $_SESSION['message_type'] = "error";
    header('Location: my-booking.php');
    exit();
}

$review = mysqli_fetch_assoc($result);

// Check if the review is within the 7-day edit window
$review_date = new DateTime($review['created_at']);
$current_date = new DateTime();
$days_since_review = $current_date->diff($review_date)->days;

if ($days_since_review > 7) {
    $_SESSION['message'] = "Reviews can only be edited within 7 days of posting.";
    $_SESSION['message_type'] = "error";
    header('Location: my-booking.php');
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
    $review_text = mysqli_real_escape_string($conn, $_POST['review_text']);
    
    // Validate input
    $errors = [];
    
    if (!$rating || $rating < 1 || $rating > 5) {
        $errors[] = "Please select a valid rating between 1 and 5 stars.";
    }
    
    if (empty($review_text)) {
        $errors[] = "Please provide review comments.";
    } elseif (strlen($review_text) > 500) {
        $errors[] = "Review text is too long. Maximum 500 characters allowed.";
    }
    
    if (empty($errors)) {
        // Update the review
        $update_query = "UPDATE tbl_reviews SET rating = ?, review_text = ? WHERE review_id = ? AND user_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "isii", $rating, $review_text, $review_id, $_SESSION['userid']);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $_SESSION['message'] = "Your review has been updated successfully.";
            $_SESSION['message_type'] = "success";
            header('Location: my-booking.php');
            exit();
        } else {
            $_SESSION['message'] = "Error updating review: " . mysqli_error($conn);
            $_SESSION['message_type'] = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Review - LensPro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-gray: #f5f6fa;
            --dark-gray: #2c3e50;
            --hover-color: #2980b9;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --overlay-color: rgba(44, 62, 80, 0.7);
            --white: #ffffff;
            --text-color: #333;
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
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            text-align: center;
            margin-bottom: 30px;
            color: var(--primary-color);
            font-size: 2rem;
            font-weight: 600;
        }

        .photographer-profile {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .photographer-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 20px;
            border: 3px solid var(--secondary-color);
        }

        .photographer-info h3 {
            margin: 0;
            color: var(--primary-color);
        }

        .review-form {
            margin-top: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
            color: var(--primary-color);
        }

        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
        }

        .star-rating input {
            display: none;
        }

        .star-rating label {
            cursor: pointer;
            width: 40px;
            height: 40px;
            margin: 0;
            background-image: url('data:image/svg+xml;charset=UTF-8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="%23ccc" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>');
            background-repeat: no-repeat;
            background-position: center;
            background-size: 36px;
        }

        .star-rating input:checked ~ label,
        .star-rating input:checked ~ label ~ label {
            background-image: url('data:image/svg+xml;charset=UTF-8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="%23f39c12" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>');
        }

        .star-rating label:hover,
        .star-rating label:hover ~ label {
            background-image: url('data:image/svg+xml;charset=UTF-8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="%23f1c40f" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>');
        }

        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            resize: vertical;
            min-height: 150px;
            transition: all 0.3s;
        }

        textarea:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        .btn-container {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            flex: 1;
            text-align: center;
            text-decoration: none;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background-color: var(--secondary-color);
            color: white;
        }

        .btn-secondary {
            background-color: #95a5a6;
            color: white;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .btn-primary:hover {
            background-color: var(--hover-color);
        }

        .btn-secondary:hover {
            background-color: #7f8c8d;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }

        .error-list {
            margin: 0;
            padding-left: 20px;
        }

        .review-timestamp {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
        }

        .days-left {
            font-weight: bold;
            color: var(--accent-color);
        }

        @media (max-width: 768px) {
            .container {
                width: 95%;
                padding: 20px;
            }

            .photographer-avatar {
                width: 80px;
                height: 80px;
            }

            .btn-container {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="section-title">Edit Your Review</h1>
        
        <div class="photographer-profile">
            <?php 
            $photographer_pic = $review['photographer_pic'] ? 
                htmlspecialchars($review['photographer_pic']) : 
                'images/default-photographer.jpg'; 
            ?>
            <img src="<?php echo $photographer_pic; ?>" alt="Photographer" class="photographer-avatar">
            <div class="photographer-info">
                <h3><?php echo htmlspecialchars($review['photographer_name']); ?></h3>
                <p class="review-timestamp">
                    Review posted on: <?php echo date('F j, Y, g:i a', strtotime($review['created_at'])); ?>
                </p>
                <p>
                    You have <span class="days-left"><?php echo 7 - $days_since_review; ?> days</span> left to edit this review.
                </p>
            </div>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <ul class="error-list">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form class="review-form" method="POST">
            <div class="form-group">
                <label>Your Rating:</label>
                <div class="star-rating">
                    <input type="radio" id="star5" name="rating" value="5" <?php echo $review['rating'] == 5 ? 'checked' : ''; ?>>
                    <label for="star5" title="5 stars"></label>
                    
                    <input type="radio" id="star4" name="rating" value="4" <?php echo $review['rating'] == 4 ? 'checked' : ''; ?>>
                    <label for="star4" title="4 stars"></label>
                    
                    <input type="radio" id="star3" name="rating" value="3" <?php echo $review['rating'] == 3 ? 'checked' : ''; ?>>
                    <label for="star3" title="3 stars"></label>
                    
                    <input type="radio" id="star2" name="rating" value="2" <?php echo $review['rating'] == 2 ? 'checked' : ''; ?>>
                    <label for="star2" title="2 stars"></label>
                    
                    <input type="radio" id="star1" name="rating" value="1" <?php echo $review['rating'] == 1 ? 'checked' : ''; ?>>
                    <label for="star1" title="1 star"></label>
                </div>
            </div>
            
            <div class="form-group">
                <label for="review_text">Your Review:</label>
                <textarea name="review_text" id="review_text" rows="5" placeholder="Share your experience with this photographer..."><?php echo htmlspecialchars($review['review_text']); ?></textarea>
            </div>
            
            <div class="btn-container">
                <a href="my-booking.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Review
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>