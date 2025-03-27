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
                    <!-- <div class="value"><?php echo $stats['monthly_bookings']; ?></div> -->
                </div>
                <div class="stat-card">
                    <h3>Total Bookings</h3>
                    <!-- <div class="value"><?php echo $stats['total_bookings']; ?></div> -->
                </div>
                <div class="stat-card">
                    <h3>Average Rating</h3>
                    <!-- <div class="value"><?php echo number_format($stats['avg_rating'], 1); ?> ⭐</div> -->
                </div>
                <div class="stat-card">
                    <h3>Total Reviews</h3>
                    <!-- <div class="value"><?php echo $stats['total_reviews']; ?></div> -->
                </div>
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
                                        at <?php echo date('h:i A', strtotime($booking['event_time'])); ?>
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
                    <!-- <?php foreach ($recent_reviews as $review): ?>
                        <div class="review-item">
                            <div class="rating">
                                <?php echo str_repeat('⭐', $review['rating']); ?>
                            </div>
                            <h4><?php echo htmlspecialchars($review['client_name']); ?></h4>
                            <p><?php echo htmlspecialchars($review['review_text']); ?></p>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($recent_reviews)): ?>
                        <p>No reviews yet</p>
                    <?php endif; ?> -->
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