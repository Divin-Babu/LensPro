<?php
session_start();
include 'dbconnect.php';

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    header('Location: login.php');
    exit();
}

// Validate input parameters
if (!isset($_GET['photographer_id']) || !isset($_GET['booking_id'])) {
    $_SESSION['message'] = "Invalid review request.";
    $_SESSION['message_type'] = "error";
    header('Location: my-booking.php');
    exit();
}

$photographer_id = intval($_GET['photographer_id']);
$booking_id = intval($_GET['booking_id']);

// Fetch review details
$review_query = "
    SELECT 
        r.review_id, 
        r.rating, 
        r.review_text, 
        r.created_at,
        p.name AS photographer_name,
        p.profile_pic AS photographer_pic,
        b.session_type,
        b.event_date
    FROM 
        tbl_reviews r
    JOIN 
        tbl_user p ON r.photographer_id = p.user_id
    JOIN
        tbl_booking b ON r.booking_id = b.booking_id
    WHERE 
        r.user_id = ? 
        AND r.photographer_id = ? 
        AND r.booking_id = ?
";

$stmt = mysqli_prepare($conn, $review_query);
mysqli_stmt_bind_param($stmt, "iii", $_SESSION['userid'], $photographer_id, $booking_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$review = mysqli_fetch_assoc($result);

if (!$review) {
    $_SESSION['message'] = "Review not found.";
    $_SESSION['message_type'] = "error";
    header('Location: my-booking.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Review - LensPro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-gray: #f5f6fa;
            --white: #ffffff;
        }

        body {
            background-image: url('images/mybookingbg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            position: relative;
            font-family: 'Poppins', sans-serif;
            color: #333;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(44, 62, 80, 0.7);
            z-index: -1;
        }

        .review-container {
            max-width: 700px;
            margin: 50px auto;
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .photographer-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--secondary-color);
            margin-bottom: 20px;
        }

        .review-content {
            text-align: center;
        }

        .review-stars .fa-star.text-warning {
            color: #f1c40f;
            font-size: 1.5rem;
        }

        .review-stars .fa-star.text-muted {
            color: #ddd;
            font-size: 1.5rem;
        }

        .btn-back {
            background-color: var(--secondary-color);
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: inline-block;
            margin-top: 20px;
        }

        .btn-back:hover {
            background-color: var(--primary-color);
            transform: translateY(-3px);
        }
    </style>
</head>
<body>
    <div class="review-container">
        <?php 
        $photographer_pic = $review['photographer_pic'] ? 
            htmlspecialchars($review['photographer_pic']) : 
            'images/default-photographer.jpg'; 
        ?>
        <div class="review-content">
            <img src="<?php echo $photographer_pic; ?>" alt="Photographer" class="photographer-avatar">
            
            <h2><?php echo htmlspecialchars($review['photographer_name']); ?></h2>
            <p><strong>Session Type:</strong> <?php echo htmlspecialchars($review['session_type']); ?></p>
            <p><strong>Event Date:</strong> <?php echo date('F j, Y', strtotime($review['event_date'])); ?></p>

            <div class="review-stars mt-3 mb-3">
                <?php 
                $rating = $review['rating'];
                for ($i = 1; $i <= 5; $i++) {
                    echo $i <= $rating ? 
                        '<i class="fas fa-star text-warning"></i>' : 
                        '<i class="far fa-star text-muted"></i>';
                }
                ?>
            </div>

            <div class="review-text">
                <h4>Your Review</h4>
                <p><?php echo htmlspecialchars($review['review_text']); ?></p>
                <small class="text-muted">Reviewed on: <?php echo date('F j, Y, g:i a', strtotime($review['created_at'])); ?></small>
            </div>

            <a href="my-booking.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back to My Bookings
            </a>
        </div>
    </div>
</body>
</html>