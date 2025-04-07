<?php
session_start();
include 'dbconnect.php';

// Get user information if logged in
$row = [];
if (isset($_SESSION['userid'])) {
    $sql = "SELECT name, profile_pic, role FROM tbl_user WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['userid']);
    mysqli_stmt_execute($stmt); 
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
}

// Check if photographer ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$photographer_id = $_GET['id'];


$photographerQuery = "SELECT u.user_id, u.name, u.email, u.profile_pic, p.bio, p.location 
                     FROM tbl_user u 
                     LEFT JOIN tbl_photographer p ON u.user_id = p.photographer_id 
                     WHERE u.user_id = ? AND u.role = 'photographer' AND u.status = TRUE";
$stmt = mysqli_prepare($conn, $photographerQuery);
mysqli_stmt_bind_param($stmt, "i", $photographer_id);
mysqli_stmt_execute($stmt);
$photographerResult = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($photographerResult) == 0) {
    header("Location: index.php");
    exit();
}

$reviewsQuery = "SELECT r.review_id, r.rating, r.review_text, r.created_at, 
                u.name, u.profile_pic 
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
$photographer = mysqli_fetch_assoc($photographerResult);

// Fetch photographer's gallery
$galleryQuery = "SELECT g.*, c.category_name 
                FROM tbl_gallery g 
                LEFT JOIN tbl_categories c ON g.category_id = c.category_id 
                WHERE g.photographer_id = ? AND g.status = TRUE";
$stmt = mysqli_prepare($conn, $galleryQuery);
mysqli_stmt_bind_param($stmt, "i", $photographer_id);
mysqli_stmt_execute($stmt);
$galleryResult = mysqli_stmt_get_result($stmt);

$gallery = [];
while ($image = mysqli_fetch_assoc($galleryResult)) {
    $gallery[] = $image;
}

$avgRating = 0;
$totalReviews = count($reviews);

if ($totalReviews > 0) {
    $ratingSum = 0;
    foreach ($reviews as $review) {
        $ratingSum += $review['rating'];
    }
    $avgRating = round($ratingSum / $totalReviews, 1);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($photographer['name']); ?> - LensPro</title>
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
            background: var(--hover-color);
        }

        .dropdown-divider {
            height: 1px;
            background: #ddd;
            margin: 5px 0;
        }

        .logout-link {
            color: var(--accent-color);
            font-weight: 600;
        }

        .logout-link i {
            color: var(--accent-color);
        }

        .main-content {
            margin-top: 80px;
            padding: 2rem 5%;
        }

        .photographer-profile {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .profile-header {
            display: flex;
            flex-wrap: wrap;
            padding: 2rem;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .profile-photo-large {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            overflow: hidden;
            border: 5px solid white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            margin-right: 2rem;
        }

        .profile-photo-large img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-info {
            flex: 1;
        }

        .profile-info h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .profile-info .location {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }

        .profile-info .location i {
            margin-right: 0.5rem;
        }

        .bio {
            padding: 2rem;
            font-size: 1.1rem;
            line-height: 1.8;
        }

        .contact-btn {
            background: var(--accent-color);
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s;
            margin-top: 1rem;
            display: inline-block;
            text-decoration: none;
        }

        .contact-btn:hover {
            background: #c0392b;
        }

        .book-now-btn {
            background: var(--secondary-color);
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s;
            margin-left: 1rem;
            display: inline-block;
            text-decoration: none;
        }

        .book-now-btn:hover {
            background: var(--hover-color);
        }

        .gallery-section {
            padding: 2rem 0;
        }

        .gallery-section h2 {
            font-size: 2rem;
            margin-bottom: 1.5rem;
            color: var(--primary-color);
            text-align: center;
        }

        .gallery-filters {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 0.5rem 1rem;
            background: white;
            border: 1px solid #ddd;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .filter-btn:hover, .filter-btn.active {
            background: var(--secondary-color);
            color: white;
            border-color: var(--secondary-color);
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .gallery-item {
            position: relative;
            overflow: hidden;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .gallery-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .gallery-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .gallery-item:hover img {
            transform: scale(1.1);
        }

        .gallery-item-info {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 1rem;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            transform: translateY(100%);
            transition: transform 0.3s;
        }

        .gallery-item:hover .gallery-item-info {
            transform: translateY(0);
        }

        .gallery-item-info h3 {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .gallery-item-info p {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .reviews-section {
            padding: 2rem 0;
        }

        .reviews-section h2 {
            font-size: 2rem;
            margin-bottom: 1.5rem;
            color: var(--primary-color);
            text-align: center;
        }

        .reviews-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .review-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .review-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .reviewer-photo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 1rem;
        }

        .reviewer-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .reviewer-info h4 {
            margin-bottom: 0.2rem;
            color: var(--primary-color);
        }

        .review-rating {
            color: #f1c40f;
            margin-bottom: 0.5rem;
        }

        .review-text {
            line-height: 1.6;
            color: #555;
        }

        .review-date {
            font-size: 0.9rem;
            color: #888;
            margin-top: 1rem;
            text-align: right;
        }

        .book-section {
            background: var(--light-gray);
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }

        .book-section h2 {
            font-size: 2rem;
            margin-bottom: 1.5rem;
            color: var(--primary-color);
            text-align: center;
        }

        .booking-form {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
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
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--secondary-color);
            outline: none;
        }

        .submit-btn {
            background: var(--secondary-color);
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s;
            width: 100%;
        }

        .submit-btn:hover {
            background: var(--hover-color);
        }

        footer {
            background: var(--primary-color);
            color: white;
            padding: 2rem 5%;
            text-align: center;
            margin-top: 3rem;
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

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.8);
        }

        .modal-content {
            position: relative;
            margin: auto;
            padding: 0;
            width: 90%;
            max-width: 1200px;
            max-height: 90vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal-content img {
            max-width: 100%;
            max-height: 85vh;
            object-fit: contain;
        }

        .close {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            transition: 0.3s;
            z-index: 1060;
        }

        .close:hover,
        .close:focus {
            color: #bbb;
            text-decoration: none;
            cursor: pointer;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            
            .profile-photo-large {
                margin-right: 0;
                margin-bottom: 1.5rem;
            }
            
            .profile-info .location {
                justify-content: center;
            }
            
            .booking-form {
                padding: 1.5rem;
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
            <a href="booking.php">Book Now</a>
            <?php if (isset($_SESSION['userid']) && $row['role'] == 'user'): ?>
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
                <a href="login.php"><i class="fas fa-user"></i> Login/Signup</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="main-content">
        <div class="photographer-profile">
            <div class="profile-header">
                <div class="profile-photo-large">
                    <?php if (!empty($photographer['profile_pic'])): ?>
                        <img src="<?php echo htmlspecialchars($photographer['profile_pic']); ?>" alt="<?php echo htmlspecialchars($photographer['name']); ?>">
                    <?php else: ?>
                        <img src="images/default-photographer.jpg" alt="<?php echo htmlspecialchars($photographer['name']); ?>">
                    <?php endif; ?>
                </div>
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($photographer['name']); ?></h1>
                    <div class="location">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?php echo htmlspecialchars($photographer['location']); ?></span>
                    </div>
                    <div class="rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <?php if ($i <= floor($avgRating)): ?>
                                <i class="fas fa-star"></i>
                            <?php elseif ($i - $avgRating > 0 && $i - $avgRating < 1): ?>
                                <i class="fas fa-star-half-alt"></i>
                            <?php else: ?>
                                <i class="far fa-star"></i>
                            <?php endif; ?>
                        <?php endfor; ?>
                        <span>(<?php echo $avgRating; ?>)</span>
                    </div>
                    <p>Professional Photographer</p>
                    <div class="actions">
                        <a href="mailto:<?php echo htmlspecialchars($photographer['email']); ?>" class="contact-btn">
                            <i class="fas fa-envelope"></i> Contact
                        </a>
                        <a href="#book-section" class="book-now-btn">
                            <i class="fas fa-calendar-plus"></i> Book Now
                        </a>
                    </div>
                </div>
            </div>
            <div class="bio">
                <h2>About Me</h2>
                <p><?php echo nl2br(htmlspecialchars($photographer['bio'])); ?></p>
            </div>
        </div>

        <div class="gallery-section">
            <h2>Photography Portfolio</h2>
            <div class="gallery-filters">
                <button class="filter-btn active" data-filter="all">All</button>
                <?php
                // Get unique categories from gallery
                $categories = [];
                foreach ($gallery as $image) {
                    if (!in_array($image['category_name'], $categories) && !empty($image['category_name'])) {
                        $categories[] = $image['category_name'];
                    }
                }
                
                foreach ($categories as $category) {
                    echo '<button class="filter-btn" data-filter="' . htmlspecialchars(strtolower($category)) . '">' . htmlspecialchars($category) . '</button>';
                }
                ?>
            </div>
            <div class="gallery-grid">
                <?php if (!empty($gallery)): ?>
                    <?php foreach ($gallery as $image): ?>
                        <div class="gallery-item" data-category="<?php echo htmlspecialchars(strtolower($image['category_name'])); ?>">
                            <img src="<?php echo htmlspecialchars($image['image_url']); ?>" alt="<?php echo htmlspecialchars($image['title']); ?>" onclick="openModal(this)">
                            <div class="gallery-item-info">
                                <h3><?php echo htmlspecialchars($image['title']); ?></h3>
                                <p><?php echo htmlspecialchars($image['category_name']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No photos in gallery yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="reviews-section">
    <h2>Client Reviews</h2>
    <div class="reviews-container">
        <?php if (!empty($reviews)): ?>
            <?php foreach ($reviews as $review): ?>
                <div class="review-card">
                    <div class="review-header">
                        <div class="reviewer-photo">
                            <?php if (!empty($review['profile_pic'])): ?>
                                <img src="<?php echo htmlspecialchars($review['profile_pic']); ?>" alt="<?php echo htmlspecialchars($review['name']); ?>">
                            <?php else: ?>
                                <img src="images/default-user.jpg" alt="<?php echo htmlspecialchars($review['name']); ?>">
                            <?php endif; ?>
                        </div>
                        <div class="reviewer-info">
                            <h4><?php echo htmlspecialchars($review['name']); ?></h4>
                            <div class="review-rating">
                                <?php
                                // Show filled stars based on rating
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $review['rating']) {
                                        echo '<i class="fas fa-star"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="review-text"><?php echo htmlspecialchars($review['review_text']); ?></div>
                    <div class="review-date">
                        <?php 
                        // Format the date
                        $reviewDate = new DateTime($review['created_at']);
                        $now = new DateTime();
                        $interval = $reviewDate->diff($now);
                        
                        if ($interval->y > 0) {
                            echo $interval->y . ' year' . ($interval->y > 1 ? 's' : '') . ' ago';
                        } elseif ($interval->m > 0) {
                            echo $interval->m . ' month' . ($interval->m > 1 ? 's' : '') . ' ago';
                        } elseif ($interval->d > 0) {
                            echo $interval->d . ' day' . ($interval->d > 1 ? 's' : '') . ' ago';
                        } else {
                            echo 'Today';
                        }
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-reviews">No reviews yet for this photographer.</p>
        <?php endif; ?>
    </div>
</div>

        <div class="book-section" id="book-section">
            <h2>Book a Session</h2>
            <form class="booking-form" action="process-booking.php" method="post">
                <input type="hidden" name="photographer_id" value="<?php echo $photographer_id; ?>">
                
                <div class="form-group">
                    <label for="date">Preferred Date</label>
                    <input type="date" id="date" name="booking_date" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="time">Preferred Time</label>
                    <input type="time" id="time" name="booking_time" required>
                </div>
                
                <div class="form-group">
                    <label for="session_type">Session Type</label>
                    <select id="session_type" name="session_type" required>
                        <option value="">Select session type</option>
                        <option value="portrait">Portrait</option>
                        <option value="wedding">Wedding</option>
                        <option value="event">Event</option>
                        <option value="commercial">Commercial</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="message">Additional Details</label>
                    <textarea id="message" name="message" rows="4" placeholder="Please provide any specific requirements or details about your session"></textarea>
                </div>
                
                <?php if (isset($_SESSION['userid'])): ?>
                    <button type="submit" class="submit-btn">Book Now</button>
                <?php else: ?>
                    <a href="login.php" class="submit-btn" style="display: block; text-align: center; text-decoration: none;">Login to Book</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Modal for image preview -->
    <div id="imageModal" class="modal">
        <span class="close" onclick="closeModal()">&times;</span>
        <div class="modal-content">
            <img id="modalImg" src="">
        </div>
    </div>

    <footer>
        <p>&copy; 2025 LensPro. All rights reserved.</p>
        <div class="social-links">
            <a href="#"><i class="fab fa-facebook"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
        </div>
    </footer>

    <script>
        // Gallery filtering
        document.addEventListener('DOMContentLoaded', function() {
            const filterButtons = document.querySelectorAll('.filter-btn');
            const galleryItems = document.querySelectorAll('.gallery-item');
            
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const filter = this.getAttribute('data-filter');
                    
                    // Remove active class from all buttons and add to clicked button
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Filter gallery items
                    galleryItems.forEach(item => {
                        if (filter === 'all' || item.getAttribute('data-category') === filter) {
                            item.style.display = 'block';
                        } else {
                            item.style.display = 'none';
                        }
                    })
                });
            });
        });
        
        // Image modal functionality
        const modal = document.getElementById('imageModal');
        const modalImg = document.getElementById('modalImg');
        
        function openModal(img) {
            modal.style.display = "block";
            modalImg.src = img.src;
        }
        
        function closeModal() {
            modal.style.display = "none";
        }
        
        // Close modal when clicking outside the image
        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }
        
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>