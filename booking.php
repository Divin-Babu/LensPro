<?php
session_start();
include 'dbconnect.php';

// Redirect if not logged in
if (!isset($_SESSION['userid'])) {
    header("Location: login.php?redirect=booking.php");
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

// Fetch photographers from database for dropdown
$photographerQuery = "SELECT u.user_id, u.name, p.location, p.category
                     FROM tbl_user u 
                     JOIN tbl_photographer p ON u.user_id = p.photographer_id 
                     WHERE u.role = 'photographer' AND u.status = TRUE
                     ORDER BY u.name";
$photographerResult = mysqli_query($conn, $photographerQuery);
$photographers = [];
if ($photographerResult) {
    while ($photographerRow = mysqli_fetch_assoc($photographerResult)) {
        $photographers[] = $photographerRow;
    }
}

// Handle form submission
$successMessage = "";
$errorMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $photographerId = mysqli_real_escape_string($conn, $_POST['photographer_id']);
    $eventDate = mysqli_real_escape_string($conn, $_POST['event_date']);
    $eventTime = mysqli_real_escape_string($conn, $_POST['event_time']);
    $eventType = mysqli_real_escape_string($conn, $_POST['session_type']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $additionalInfo = mysqli_real_escape_string($conn, $_POST['additional_info']);
    $userId = $_SESSION['userid'];
    $status = 'pending'; // Initial status changed to 'requested'
    
    
    // Validate inputs
    if (empty($photographerId) || empty($eventDate) || empty($eventTime) || empty($eventType) || empty($location)) {
        $errorMessage = "Please fill all required fields";
    } else {
        // Check if the selected date is in the future
        $selectedDateTime = strtotime($eventDate);
        $currentDate = strtotime(date('Y-m-d'));
        
        if ($selectedDateTime < $currentDate) {
            $errorMessage = "Please select a future date for your booking request";
        } else {
            // Insert booking request into database
            $insertBooking = "INSERT INTO tbl_booking (user_id, photographer_id, event_date, event_time, session_type, location, additional_info, status) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmtInsert = mysqli_prepare($conn, $insertBooking);
            mysqli_stmt_bind_param($stmtInsert, "iissssss", $userId, $photographerId, $eventDate, $eventTime, $eventType, $location, $additionalInfo, $status);
            
            if (mysqli_stmt_execute($stmtInsert)) {
                $bookingId = mysqli_insert_id($conn);
                $successMessage = "Your booking request has been submitted successfully! Request ID: #" . $bookingId;
                
                // Redirect to my-bookings page after successful request
                header("Location: booking-success.php?success=1&booking_id=" . $bookingId);
                exit();
            } else {
                $errorMessage = "Error: " . mysqli_error($conn);
            }
        }
    }
}

$categoryQuery = "SELECT category_id, category_name FROM tbl_categories ORDER BY category_name";
$categoryResult = mysqli_query($conn, $categoryQuery);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Photographer - LensPro</title>
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

        .section-title {
            text-align: center;
            margin: 3rem 0;
            color: var(--primary-color);
            font-size: 2.5rem;
            font-weight: 600;
        }

        .booking-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            flex-wrap: wrap;
            padding: 0 20px;
            gap: 30px;
        }

        .booking-form-container {
            flex: 1;
            min-width: 300px;
            padding: 20px;
        }

        .booking-info {
            flex: 1;
            min-width: 300px;
            padding: 20px;
        }

        .booking-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .info-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .info-card h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 1.3rem;
        }

        .info-card ul {
            list-style-type: none;
            padding-left: 0;
        }

        .info-card li {
            margin-bottom: 10px;
            padding-left: 25px;
            position: relative;
        }

        .info-card li i {
            position: absolute;
            left: 0;
            top: 5px;
            color: var(--secondary-color);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
            font-weight: 500;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--secondary-color);
            outline: none;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .cta-button {
            background: var(--secondary-color);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 5px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background 0.3s;
            text-decoration: none;
            display: inline-block;
            width: 100%;
            text-align: center;
        }

        .cta-button:hover {
            background: var(--hover-color);
        }

        .required {
            color: var(--accent-color);
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background-color: rgba(46, 204, 113, 0.2);
            border: 1px solid var(--success-color);
            color: var(--success-color);
        }

        .alert-error {
            background-color: rgba(231, 76, 60, 0.2);
            border: 1px solid var(--error-color);
            color: var(--error-color);
        }

        .form-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .form-row .form-group {
            flex: 1;
            min-width: 200px;
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

        /* Responsive */
        @media (max-width: 768px) {
            .booking-container {
                flex-direction: column;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
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
            <!-- <a href="booking.php">Book Now</a> -->
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
                        <a href="my-booking.php"><i class="fas fa-calendar-check"></i> My Bookings</a>
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
        <h2 class="section-title">Book a Photographer</h2>

        <div class="booking-container">
            <div class="booking-form-container">
                <?php if (!empty($errorMessage)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $errorMessage; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($successMessage)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $successMessage; ?>
                    </div>
                <?php endif; ?>

                <div class="booking-form">
                    <form action="booking.php" method="post">
                        <div class="form-group">
                            <label for="photographer_id">Select Photographer <span class="required">*</span></label>
                            <select name="photographer_id" id="photographer_id" required>
                                <option value="">-- Select a Photographer --</option>
                                <?php foreach ($photographers as $photographer): ?>
                                <option value="<?php echo $photographer['user_id']; ?>">
                                    <?php echo htmlspecialchars($photographer['name']); ?> 
                                    (<?php echo htmlspecialchars($photographer['location']); ?>) - 
                                    <?php echo htmlspecialchars($photographer['category']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="event_date">Event Date <span class="required">*</span></label>
                                <input type="date" name="event_date" id="event_date" min="<?php echo date('Y-m-d'); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="event_time">Event Time <span class="required">*</span></label>
                                <input type="time" name="event_time" id="event_time" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="event_type">Event Type <span class="required">*</span></label>
                                <select name="session_type">
                                    <option value="">Select Category</option>
                                    <?php while ($category = mysqli_fetch_assoc($categoryResult)) { ?>
                                        <option value="<?php echo htmlspecialchars($category['category_name']); ?>">
                                            <?php echo htmlspecialchars($category['category_name']); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div> 

                        <div class="form-group">
                            <label for="location">Location <span class="required">*</span></label>
                            <input type="text" name="location" id="location" placeholder="Enter the event location" required>
                        </div>

                        <div class="form-group">
                            <label for="additional_info">Additional Information</label>
                            <textarea name="additional_info" id="additional_info" placeholder="Please provide any additional details about your event that would help the photographer prepare..."></textarea>
                        </div>

                        <button type="submit" class="cta-button">Submit Booking Request</button>
                    </form>
                </div>
            </div>

            <div class="booking-info">
                <div class="info-card">
                    <h3>Booking Guidelines</h3>
                    <ul>
                        <li><i class="fas fa-info-circle"></i> All bookings require approval from the photographer.</li>
                        <li><i class="fas fa-clock"></i> Please book at least 48 hours in advance.</li>
                        <li><i class="fas fa-dollar-sign"></i> The photographer will contact you with pricing details after your booking request.</li>
                        <li><i class="fas fa-ban"></i> Cancellations cannot be done after the payment is done.</li>
                    </ul>
                </div>

                <div class="info-card">
                    <h3>What to Expect</h3>
                    <ul>
                        <li><i class="fas fa-check-circle"></i> After submitting your request, you'll receive a confirmation email.</li>
                        <li><i class="fas fa-comments"></i> The photographer will contact you to discuss details and pricing.</li>
                        <li><i class="fas fa-handshake"></i> Once terms are agreed, your booking will be confirmed.</li>
                        <li><i class="fas fa-camera"></i> The photographer will arrive prepared with all necessary equipment.</li>
                        <li><i class="fas fa-images"></i> After the session, you'll receive your photos according to the agreed timeline.</li>
                    </ul>
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
        // Set minimum date for the date picker to today
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('event_date').setAttribute('min', today);
            
            // Photographer selection from URL parameter if exists
            const urlParams = new URLSearchParams(window.location.search);
            const photographerId = urlParams.get('photographer_id');
            if (photographerId) {
                document.getElementById('photographer_id').value = photographerId;
            }
        });
    </script>
</body>

</html>