<?php
session_start();
include 'dbconnect.php';

// Check if user is logged in and is a photographer
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'photographer') {
    header('Location: login.php');
    exit();
}

$photographer_id = $_SESSION['userid'];

// Fetch reviews for this photographer with user details
$reviewsQuery = "SELECT r.*, u.name AS reviewer_name, u.profile_pic 
                 FROM tbl_reviews r
                 JOIN tbl_user u ON r.user_id = u.user_id
                 WHERE r.photographer_id = ? AND r.status = TRUE
                 ORDER BY r.created_at DESC";
$stmt = mysqli_prepare($conn, $reviewsQuery);
mysqli_stmt_bind_param($stmt, "i", $photographer_id);
mysqli_stmt_execute($stmt);
$reviewsResult = mysqli_stmt_get_result($stmt);
$reviews = [];
while ($review = mysqli_fetch_assoc($reviewsResult)) {
    $reviews[] = $review;
}

// Calculate average rating
$avgRatingQuery = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
                   FROM tbl_reviews 
                   WHERE photographer_id = ? AND status = TRUE";
$avgStmt = mysqli_prepare($conn, $avgRatingQuery);
mysqli_stmt_bind_param($avgStmt, "i", $photographer_id);
mysqli_stmt_execute($avgStmt);
$avgResult = mysqli_stmt_get_result($avgStmt)->fetch_assoc();
$averageRating = $avgResult['avg_rating'] ?? 0;
$totalReviews = $avgResult['total_reviews'] ?? 0;

// Function to get text description of rating
function getRatingDescription($rating) {
    if ($rating >= 4.5) return "Excellent";
    if ($rating >= 4.0) return "Very Good";
    if ($rating >= 3.0) return "Good";
    if ($rating >= 2.0) return "Average";
    if ($rating > 0) return "Poor";
    return "No Rating";
}

$ratingDescription = getRatingDescription($averageRating);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews - LensPro</title>
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

        .reviews-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .overall-rating {
            display: flex;
            align-items: center;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .overall-rating-content {
            display: flex;
            flex-direction: column;
        }

        .overall-rating-number {
            font-size: 48px;
            font-weight: bold;
            color: var(--primary-color);
            margin-right: 15px;
        }

        .rating-description {
            color: var(--secondary-color);
            font-weight: 500;
            margin-top: 5px;
        }

        .rating-stars {
            display: flex;
            color: var(--warning-color);
            font-size: 24px;
        }

        .reviews-list {
            display: grid;
            gap: 20px;
        }

        .review-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: flex-start;
        }

        .reviewer-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-right: 15px;
            object-fit: cover;
        }

        .review-content {
            flex: 1;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .reviewer-name {
            font-weight: 600;
            color: var(--dark-gray);
        }

        .review-date {
            color: #7f8c8d;
            font-size: 0.8em;
        }

        .review-rating {
            color: var(--warning-color);
        }

        .review-text {
            color: var(--dark-gray);
        }

        .no-reviews {
            text-align: center;
            color: #7f8c8d;
            padding: 50px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 70px;
            }

            .reviews-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .overall-rating {
                width: 100%;
                margin-bottom: 20px;
            }

            .review-card {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .reviewer-avatar {
                margin-right: 0;
                margin-bottom: 15px;
            }
        }
        .rating-intro {
            color: #7f8c8d;
            font-size: 1.2em;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <?php include 'photographer_sidebar.php'; ?>

    <div class="main-content">
        <div class="reviews-header">
            <div class="overall-rating">
                <div class="overall-rating-content">
                <div class="rating-intro">Average Rating</div>
                    <div style="display: flex; align-items: center;">
                        <div class="overall-rating-number"><?php echo number_format($averageRating, 1); ?></div>
                        <div class="rating-stars">
                            <?php 
                            $fullStars = floor($averageRating);
                            $halfStar = $averageRating - $fullStars >= 0.5 ? 1 : 0;
                            $emptyStars = 5 - $fullStars - $halfStar;

                            for ($i = 0; $i < $fullStars; $i++) {
                                echo '<i class="fas fa-star"></i>';
                            }
                            if ($halfStar) {
                                echo '<i class="fas fa-star-half-alt"></i>';
                            }
                            for ($i = 0; $i < $emptyStars; $i++) {
                                echo '<i class="far fa-star"></i>';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="rating-description"><?php echo $ratingDescription; ?> Rating</div>
                </div>
            </div>
            <h1>Reviews (<?php echo $totalReviews; ?>)</h1>
        </div>

        <div class="reviews-list">
            <?php if (!empty($reviews)): ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <img src="<?php echo !empty($review['profile_pic']) ? htmlspecialchars($review['profile_pic']) : 'images/default-avatar.png'; ?>" 
                             alt="Reviewer Avatar" class="reviewer-avatar">
                        <div class="review-content">
                            <div class="review-header">
                                <div class="reviewer-name"><?php echo htmlspecialchars($review['reviewer_name']); ?></div>
                                <div class="review-date">
                                    <?php echo date('F j, Y', strtotime($review['created_at'])); ?>
                                </div>
                            </div>
                            <div class="review-rating">
                                <?php 
                                for ($i = 0; $i < $review['rating']; $i++) {
                                    echo '<i class="fas fa-star"></i>';
                                }
                                for ($i = $review['rating']; $i < 5; $i++) {
                                    echo '<i class="far fa-star"></i>';
                                }
                                ?>
                            </div>
                            <p class="review-text"><?php echo htmlspecialchars($review['review_text']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-reviews">
                    <h2>No Reviews Yet</h2>
                    <p>Once clients start booking your photography services, their reviews will appear here.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>