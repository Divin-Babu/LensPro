<?php
session_start();
include 'dbconnect.php';

// Redirect if not logged in
if (!isset($_SESSION['userid'])) {
    header("Location: login.php?redirect=my-bookings.php");
    exit();
}

// Get user information if logged in
$row = [];
if (isset($_SESSION['userid'])) {
    $sql = "SELECT name, profile_pic, role, email, phno FROM tbl_user WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['userid']);
    mysqli_stmt_execute($stmt); 
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
}

// Get booking details if booking ID is provided
$bookingDetails = null;
if (isset($_GET['booking_id']) && is_numeric($_GET['booking_id'])) {
    $bookingId = $_GET['booking_id'];
    
    // Fetch booking details
    $bookingQuery = "SELECT b.*, u.name as photographer_name 
                    FROM tbl_booking b 
                    JOIN tbl_user u ON b.photographer_id = u.user_id 
                    WHERE b.booking_id = ? AND b.user_id = ?";
    $stmt = mysqli_prepare($conn, $bookingQuery);
    mysqli_stmt_bind_param($stmt, "ii", $bookingId, $_SESSION['userid']);
    mysqli_stmt_execute($stmt);
    $bookingResult = mysqli_stmt_get_result($stmt);
    
    if ($bookingResult && mysqli_num_rows($bookingResult) > 0) {
        $bookingDetails = mysqli_fetch_assoc($bookingResult);
    } else {
        // Redirect if booking doesn't exist or doesn't belong to user
        header("Location: my-bookings.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Successful - LensPro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-gray: #f5f6fa;
            --dark-gray: #2c3e50;
            --hover-color: #2980b9;
            --success-color: #2ecc71;
            --error-color: #e74c3c;
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
        }

        nav {
            background: var(--primary-color);
            padding: 0.5rem 1%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
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
            color: rgb(255, 255, 255);
            font-size: 2.5rem;
            font-weight: 600;
            letter-spacing: 2px;
            font-family: Georgia, 'Times New Roman', Times, serif;
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

        .user-profile {
            position: relative;
            display: flex;
            align-items: center;
            cursor: pointer;
            color: white;
        }

        .profile-photo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #fff;
        }

        .profile-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .profile-photo i {
            font-size: 1.8rem;
            color: var(--primary-color);
        }

        .username {
            margin-left: 10px;
            font-size: 1.25rem;
            font-weight: 500;
            padding-bottom: 7px;
        }

        .user-profile i.fas.fa-chevron-down {
            margin-left: 8px;
            font-size: 0.9rem;
            transition: transform 0.3s ease;
            padding-bottom: 7px;
        }

        /* Dropdown Menu */
        .dropdown-content {
            display: none;
            position: absolute;
            top: 50px;
            right: 0;
            background: #2c3e50;
            border-radius: 8px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            min-width: 200px;
            z-index: 1000;
            overflow: hidden;
            transition: opacity 0.3s ease, transform 0.3s ease;
            transform: translateY(-10px);
        }

        .dropdown-content.show {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .user-profile:hover .dropdown-content {
            display: block;
        }

        .dropdown-content a {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            text-decoration: none;
            color: white;
            font-size: 1rem;
            transition: background 0.3s;
        }

        .dropdown-content a i {
            margin-right: 10px;
            font-size: 1.2rem;
            color: var(--secondary-color);
        }

        .dropdown-content a:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .dropdown-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.2);
            margin: 5px 0;
        }

        .logout-link {
            color: var(--accent-color);
            font-weight: 600;
        }

        .logout-link i {
            color: var(--accent-color);
        }

        main {
            padding-top: 100px;
            min-height: calc(100vh - 100px);
            padding-bottom: 3rem;
        }

        .success-container {
            max-width: 800px;
            margin: 2rem auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .success-header {
            background: var(--success-color);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .success-header i {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .success-header h2 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .success-body {
            padding: 2rem;
        }

        .booking-info {
            margin-top: 1.5rem;
        }

        .info-row {
            display: flex;
            border-bottom: 1px solid #eee;
            padding: 1rem 0;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            flex: 1;
            font-weight: 600;
            color: var(--primary-color);
        }

        .info-value {
            flex: 2;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            justify-content: center;
        }

        .btn {
            padding: 0.8rem 1.5rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            text-align: center;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--secondary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--hover-color);
        }

        .btn-outline {
            border: 2px solid var(--secondary-color);
            color: var(--secondary-color);
        }

        .btn-outline:hover {
            background: var(--secondary-color);
            color: white;
        }

        .success-note {
            margin-top: 2rem;
            padding: 1rem;
            background: rgba(46, 204, 113, 0.1);
            border-left: 4px solid var(--success-color);
            border-radius: 4px;
        }

        .success-note p {
            margin-bottom: 0.5rem;
        }

        .success-note p:last-child {
            margin-bottom: 0;
        }

        footer {
            background: var(--primary-color);
            color: white;
            padding: 2rem 5%;
            text-align: center;
        }

        .social-links {
            margin-top: 1rem;
        }

        .social-links a {
            color: white;
            margin: 0 1rem;
            font-size: 1.5rem;
            transition: color 0.3s;
        }

        .social-links a:hover {
            color: var(--secondary-color);
        }

        /* Animation */
        @keyframes checkmark {
            0% {
                transform: scale(0);
                opacity: 0;
            }
            50% {
                transform: scale(1.2);
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .animate-check {
            animation: checkmark 0.8s ease-in-out;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .success-container {
                margin: 2rem 20px;
            }
            
            .info-row {
                flex-direction: column;
            }
            
            .info-label {
                margin-bottom: 0.5rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <nav>
        <div class="logo-container">
            <div class="logo">
                <a href="index.php"><img src="images/logowithoutname.png" alt=""></a>LensPro
            </div>
        </div>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="photographers.php">Photographers</a>
            <?php if (isset($_SESSION['userid'])): ?>
                <div class="user-profile">
                    <div class="profile-photo">
                        <?php if (isset($row['profile_pic']) && !empty($row['profile_pic'])): ?>
                            <img src="<?php echo htmlspecialchars($row['profile_pic']); ?>" alt="">
                        <?php else: ?>
                            <i class="fas fa-user-circle"></i>
                        <?php endif; ?>
                    </div>
                    <span class="username"><?php echo htmlspecialchars($row['name']); ?></span>
                    <i class="fas fa-chevron-down"></i>
                    <div class="dropdown-content">
                        <a href="userprofile.php"><i class="fas fa-user"></i> My Profile</a>
                        <a href="my-bookings.php"><i class="fas fa-calendar-check"></i> My Bookings</a>
                        <div class="dropdown-divider"></div>
                        <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php" class="nav-link"><i class="fas fa-user"></i> Login/Signup</a>
            <?php endif; ?>
        </div>
    </nav>

    <main>
        <div class="success-container">
            <div class="success-header">
                <i class="fas fa-check-circle animate-check"></i>
                <h2>Booking Successful!</h2>
                <p>Your booking request has been submitted successfully.</p>
            </div>

            <div class="success-body">
                <?php if ($bookingDetails): ?>
                <div class="booking-info">
                    <div class="info-row">
                        <div class="info-label">Booking ID:</div>
                        <div class="info-value">#<?php echo $bookingDetails['booking_id']; ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Photographer:</div>
                        <div class="info-value"><?php echo htmlspecialchars($bookingDetails['photographer_name']); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Event Date:</div>
                        <div class="info-value"><?php echo date('F j, Y', strtotime($bookingDetails['event_date'])); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Event Time:</div>
                        <div class="info-value"><?php echo date('g:i A', strtotime($bookingDetails['event_time'])); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Session Type:</div>
                        <div class="info-value"><?php echo htmlspecialchars($bookingDetails['session_type']); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Location:</div>
                        <div class="info-value"><?php echo htmlspecialchars($bookingDetails['location']); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Status:</div>
                        <div class="info-value">
                            <span class="badge badge-pending">Pending Approval</span>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="booking-info">
                    <p>Your booking has been submitted successfully! The photographer will review your request soon.</p>
                </div>
                <?php endif; ?>

                <div class="success-note">
                    <p><strong>Next Steps:</strong></p>
                    <p><i class="fas fa-envelope"></i> You will receive a confirmation email shortly.</p>
                    <p><i class="fas fa-comments"></i> The photographer will contact you within 24-48 hours to discuss details and pricing.</p>
                    <p><i class="fas fa-check-circle"></i> Once terms are agreed upon and payment is done, your booking will be confirmed.</p>
                </div>

                <div class="action-buttons">
                    <a href="my-booking.php" class="btn btn-primary"><i class="fas fa-calendar-check"></i> View My Bookings</a>
                    <a href="index.php" class="btn btn-outline"><i class="fas fa-home"></i> Return to Home</a>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2025 LensPro. All rights reserved.</p>
        <div class="social-links">
            <a href="#"><i class="fab fa-facebook"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add animation class to checkmark icon
            const checkIcon = document.querySelector('.fa-check-circle');
            checkIcon.classList.add('animate-check');
            
            // Auto redirect after some time (optional)
            // setTimeout(function() {
            //     window.location.href = 'my-bookings.php';
            // }, 10000); // 10 seconds
        });
    </script>
</body>

</html>