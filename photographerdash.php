<?php
session_start();
include 'dbconnect.php';

// Check if user is logged in and is a photographer
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'photographer') {
    header('Location: login.php');
    exit();
}

// Fetch photographer details
$photographer_id = $_SESSION['userid'];
$stmt = mysqli_prepare($conn, "SELECT u.*, p.bio, p.location 
                             FROM tbl_user u 
                             JOIN tbl_photographer p ON u.user_id = p.photographer_id 
                             WHERE u.user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $photographer_id);
mysqli_stmt_execute($stmt);
$photographer = mysqli_stmt_get_result($stmt)->fetch_assoc();

// Calculate statistics for photographer dashboard
$stats = [];

// Monthly bookings (bookings for the current month)
$monthlyBookingsQuery = "SELECT COUNT(*) as monthly_bookings 
                       FROM tbl_booking 
                       WHERE photographer_id = ? 
                       AND MONTH(booking_date) = MONTH(CURRENT_DATE()) 
                       AND YEAR(booking_date) = YEAR(CURRENT_DATE())";
$stmtMonthly = mysqli_prepare($conn, $monthlyBookingsQuery);
mysqli_stmt_bind_param($stmtMonthly, "i", $photographer_id);
mysqli_stmt_execute($stmtMonthly);
$result = mysqli_stmt_get_result($stmtMonthly);
$stats['monthly_bookings'] = mysqli_fetch_assoc($result)['monthly_bookings'];

// Total bookings
$totalBookingsQuery = "SELECT COUNT(*) as total_bookings 
                      FROM tbl_booking 
                      WHERE photographer_id = ?";
$stmtTotal = mysqli_prepare($conn, $totalBookingsQuery);
mysqli_stmt_bind_param($stmtTotal, "i", $photographer_id);
mysqli_stmt_execute($stmtTotal);
$result = mysqli_stmt_get_result($stmtTotal);
$stats['total_bookings'] = mysqli_fetch_assoc($result)['total_bookings'];

// Average rating
$avgRatingQuery = "SELECT AVG(rating) as avg_rating 
                  FROM tbl_reviews 
                  WHERE photographer_id = ? 
                  AND status = TRUE";
$stmtRating = mysqli_prepare($conn, $avgRatingQuery);
mysqli_stmt_bind_param($stmtRating, "i", $photographer_id);
mysqli_stmt_execute($stmtRating);
$result = mysqli_stmt_get_result($stmtRating);
$avgRating = mysqli_fetch_assoc($result)['avg_rating'];
$stats['avg_rating'] = $avgRating ?: 0; // Default to 0 if no ratings

// Total reviews
$totalReviewsQuery = "SELECT COUNT(*) as total_reviews 
                     FROM tbl_reviews 
                     WHERE photographer_id = ? 
                     AND status = TRUE";
$stmtReviews = mysqli_prepare($conn, $totalReviewsQuery);
mysqli_stmt_bind_param($stmtReviews, "i", $photographer_id);
mysqli_stmt_execute($stmtReviews);
$result = mysqli_stmt_get_result($stmtReviews);
$stats['total_reviews'] = mysqli_fetch_assoc($result)['total_reviews'];

// Fetch recent reviews for the photographer - only 4 instead of 5
$recentReviewsQuery = "SELECT r.*, u.name as client_name 
                      FROM tbl_reviews r 
                      JOIN tbl_user u ON r.user_id = u.user_id 
                      WHERE r.photographer_id = ? 
                      AND r.status = TRUE 
                      ORDER BY r.created_at DESC 
                      LIMIT 4";
$stmtRecentReviews = mysqli_prepare($conn, $recentReviewsQuery);
mysqli_stmt_bind_param($stmtRecentReviews, "i", $photographer_id);
mysqli_stmt_execute($stmtRecentReviews);
$reviewsResult = mysqli_stmt_get_result($stmtRecentReviews);
$recent_reviews = [];
while ($review = mysqli_fetch_assoc($reviewsResult)) {
    $recent_reviews[] = $review;
}

// Fetch bookings for this photographer
$bookingsQuery = "SELECT b.*, u.name AS client_name 
                  FROM tbl_booking b 
                  JOIN tbl_user u ON b.user_id = u.user_id 
                  WHERE b.photographer_id = ? 
                  ORDER BY b.event_date";
$bookingsStmt = mysqli_prepare($conn, $bookingsQuery);
mysqli_stmt_bind_param($bookingsStmt, "i", $photographer_id);
mysqli_stmt_execute($bookingsStmt);
$bookingsResult = mysqli_stmt_get_result($bookingsStmt);
$bookings = [];
while ($booking = mysqli_fetch_assoc($bookingsResult)) {
    $bookings[] = $booking;
}

// Separate bookings by status
$upcomingBookings = array_filter($bookings, function($booking) {
    return $booking['status'] == 'confirmed' && strtotime($booking['event_date']) > time();
});
$pendingBookings = array_filter($bookings, function($booking) {
    return $booking['status'] == 'pending';
});
$completedBookings = array_filter($bookings, function($booking) {
    return $booking['status'] == 'completed';
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Photographer Dashboard - LensPro</title>
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

        .sidebar {
            width: 250px;
            background: var(--primary-color);
            color: white;
            padding: 20px;
            position: fixed;
            height: 100vh;
        }

        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }

        .logo {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 40px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-links {
            list-style: none;
        }

        .nav-links li {
            margin-bottom: 10px;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 10px;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .nav-links i {
            margin-right: 10px;
            width: 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .stat-card h3 {
            color: var(--dark-gray);
            font-size: 14px;
            margin-bottom: 10px;
        }

        .stat-card .value {
            font-size: 24px;
            font-weight: 600;
            color: var(--primary-color);
        }

        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        .card h2 {
            color: var(--dark-gray);
            margin-bottom: 20px;
            font-size: 18px;
        }

        .booking-item, .review-item {
            padding: 15px;
            border-radius: 5px;
            background: var(--light-gray);
            margin-bottom: 10px;
        }

        .booking-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .booking-info h4 {
            color: var(--dark-gray);
            margin-bottom: 5px;
        }

        .booking-date {
            color: var(--secondary-color);
            font-size: 14px;
        }

        .rating {
            color: var(--warning-color);
            margin-bottom: 5px;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-booked {
            background: var(--success-color);
            color: white;
        }
        
        .status-pending {
            background: var(--warning-color);
            color: white;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn {
            padding: 5px 10px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
        }
        
        .btn-accept {
            background: var(--success-color);
            color: white;
        }
        
        .btn-reject {
            background: var(--accent-color);
            color: white;
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
            font-size: 1.8rem;
            font-weight: 600;
            letter-spacing: 2px;
            font-family: Georgia, 'Times New Roman', Times, serif;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                padding: 20px 10px;
            }

            .main-content {
                margin-left: 70px;
            }

            .logo span, .nav-links span {
                display: none;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'photographer_sidebar.php'; ?>

        <div class="main-content">
            <h1 style="margin-bottom: 30px;">Welcome <?php echo htmlspecialchars($photographer['name']); ?>!</h1>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Monthly Bookings</h3>
                    <div class="value"><?php echo $stats['monthly_bookings']; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total Bookings</h3>
                    <div class="value"><?php echo $stats['total_bookings']; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Average Rating</h3>
                    <div class="value"><?php echo number_format($stats['avg_rating'], 1); ?> ⭐</div>
                </div>
                <div class="stat-card">
                    <h3>Total Reviews</h3>
                    <div class="value"><?php echo $stats['total_reviews']; ?></div>
                </div>
            </div>

            <!-- Pending Approvals Section -->
            <div class="card" style="margin-bottom: 20px;">
                <h2>Pending Approvals</h2>
                <?php if (!empty($pendingBookings)): ?>
                    <?php foreach ($pendingBookings as $booking): ?>
                        <div class="booking-item">
                            <div class="booking-info">
                                <h4><?php echo htmlspecialchars($booking['client_name']); ?></h4>
                                <div class="booking-date">
                                    <i class="far fa-calendar"></i>
                                    <?php echo date('F j, Y', strtotime($booking['event_date'])); ?> 
                                    <?php if (isset($booking['event_time'])): ?>
                                        at <?php echo date('h:i A', strtotime($booking['event_time'])); ?>
                                    <?php endif; ?>
                                </div>
                                <small><?php echo htmlspecialchars($booking['session_type']); ?> - <?php echo htmlspecialchars($booking['location']); ?></small>
                                <div style="margin-top: 5px;">
                                    <span style="font-weight: 500;">Amount: </span>
                                    <span>₹<?php echo number_format($booking['total_amt'], 2); ?></span>
                                </div>
                            </div>
                            <div>
                                <span class="status-badge status-pending">Pending</span>
                                <!-- <div class="action-buttons" style="margin-top: 8px;">
                                    <form method="post" action="update_booking_status.php" style="display: inline;">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                        <input type="hidden" name="status" value="confirmed">
                                        <button type="submit" class="btn btn-accept">Accept</button>
                                    </form>
                                    <form method="post" action="update_booking_status.php" style="display: inline;">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                        <input type="hidden" name="status" value="rejected">
                                        <button type="submit" class="btn btn-reject">Reject</button> 
                                    </form>
                                </div>-->
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No pending bookings</p>
                <?php endif; ?>
            </div>

            <div class="content-grid">
                <div class="card">
                    <h2>Upcoming Bookings</h2>
                    <?php if (!empty($upcomingBookings)): ?>
                        <?php foreach ($upcomingBookings as $booking): ?>
                            <div class="booking-item">
                                <div class="booking-info">
                                    <h4><?php echo htmlspecialchars($booking['client_name']); ?></h4>
                                    <div class="booking-date">
                                        <i class="far fa-calendar"></i>
                                        <?php echo date('F j, Y', strtotime($booking['event_date'])); ?> 
                                        <?php if (isset($booking['event_time'])): ?>
                                            at <?php echo date('h:i A', strtotime($booking['event_time'])); ?>
                                        <?php endif; ?>
                                    </div>
                                    <small><?php echo htmlspecialchars($booking['session_type']); ?> - <?php echo htmlspecialchars($booking['location']); ?></small>
                                </div>
                                <span class="status-badge status-booked">Confirmed</span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No upcoming bookings</p>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <h2>Recent Reviews</h2>
                    <?php if (!empty($recent_reviews)): ?>
                        <?php foreach ($recent_reviews as $review): ?>
                            <div class="review-item">
                                <div class="rating">
                                    <?php echo str_repeat('⭐', $review['rating']); ?>
                                </div>
                                <h4><?php echo htmlspecialchars($review['client_name']); ?></h4>
                                <p><?php echo htmlspecialchars($review['review_text']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No reviews yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add active class to current nav item
        const navLinks = document.querySelectorAll('.nav-links a');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                navLinks.forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>