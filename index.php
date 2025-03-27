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

// Fetch photographers from database (limit to 8 random photographers)
$photographerQuery = "SELECT u.user_id, u.name, u.profile_pic, p.bio, p.location 
                     FROM tbl_user u 
                     JOIN tbl_photographer p ON u.user_id = p.photographer_id 
                     WHERE u.role = 'photographer' AND u.status = TRUE
                     ORDER BY RAND() 
                     LIMIT 8";
$photographerResult = mysqli_query($conn, $photographerQuery);
$photographers = [];
if ($photographerResult) {
    while ($photographerRow = mysqli_fetch_assoc($photographerResult)) {
        $photographers[] = $photographerRow;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LensPro - Professional Photography Studio</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
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
    color: var(--dark-gray);
    font-size: 1rem;
    transition: background 0.3s;
}

.dropdown-content a i {
    margin-right: 10px;
    font-size: 1.2rem;
    color: var(--secondary-color);
}

.dropdown-content a:hover {
    background: var(--light-gray);
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
            padding-bottom: 0%;
        }

        .logo-container {
            display: flex;
            align-items: center; 
        }

        .logo img {
            width: 70px; /* Set the logo size */
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

        .hero {
            height: 100vh;
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)),
                        url('images/indpgimg1.jpg') center/cover;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
            padding: 0 1rem;
            animation: fadeIn 2s ease-out;
        }

        .hero-content h1 {
            font-size: 4rem;
            margin-bottom: 1rem;
            animation: fadeIn 1s ease-out;
        }

        .hero-content p {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            animation: fadeIn 1.5s ease-out;
        }

            a.cta-button, 
            .photographer-info a.cta-button {
                text-decoration: none;
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
            
        }
        .cta-button a{
            text-decoration:none;
            color:white;
        }

        .cta-button:hover {
            background: var(--hover-color);
        }

        .photographers {
            padding: 5rem 5%;
            background: var(--light-gray);
        }

        .section-title {
            text-align: center;
            margin-bottom: 4rem;
            color: var(--primary-color);
            font-size: 2.5rem;
            font-weight: 600;
        }

        .photographer-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr); /* 4 columns on larger screens */
            gap: 2rem;
            margin-top: 2rem;
        }

        /* Responsive Layout */
        @media (max-width: 1200px) {
            .photographer-grid {
                grid-template-columns: repeat(3, 1fr); /* 3 columns on medium screens */
            }
        }

        @media (max-width: 768px) {
            .photographer-grid {
                grid-template-columns: repeat(2, 1fr); /* 2 columns on smaller screens */
            }
        }

        @media (max-width: 480px) {
            .photographer-grid {
                grid-template-columns: 1fr; /* 1 column on extra small screens */
            }
        }

        .photographer-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }

        .photographer-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .photographer-img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .photographer-card:hover .photographer-img {
            transform: scale(1.05);
        }

        .photographer-info {
            padding: 1.5rem;
            text-align: center;
        }

        .photographer-info h3 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            font-size: 1.3rem;
            font-weight: 600;
        }

        .photographer-info p {
            color: #666;
            margin-bottom: 1rem;
        }

        .rating {
            color: #f1c40f;
            margin-bottom: 1rem;
        }

        .about-us {
            padding: 5rem 5%;
            background: white;
        }

        .about-container {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 3rem;
        }

        .about-image {
            flex: 1;
            min-width: 300px;
        }

        .about-image img {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .about-content {
            flex: 1;
            min-width: 300px;
        }

        .about-content h3 {
            color: var(--primary-color);
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
        }

        .about-content p {
            margin-bottom: 1.5rem;
            color: #555;
            line-height: 1.8;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
            margin-top: 2rem;
        }

        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }

        .feature-icon {
            background: var(--light-gray);
            color: var(--secondary-color);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.5rem;
        }

        .feature-text h4 {
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }

        footer {
            background: var(--primary-color);
            color: white;
            padding: 2rem 5%;
            text-align: center;
            position: relative;
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

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
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
            <?php if (isset($_SESSION['userid'])): ?>
            <a href="photographers.php">Photographers</a>
            <?php else: ?>
                <a href="photographerregis.php">Become A Photographer</a>
            <?php endif; ?>
            <a href="booking.php">Book Now</a>
            <?php if (isset($_SESSION['userid'])&& $row['role']=='user'): ?>
                <div class="user-profile">
                    <div class="profile-photo">
                        <?php if (isset($row['profile_pic'])): ?>
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
                <a href="login.php" class="nav-link" onclick="showLoginModal(); return false;"><i class="fas fa-user"></i> Login/Signup</a>
            <?php endif; ?>
        </div>
    </nav>

    <section class="hero" id="home">
        <div class="hero-content">
            <h1>Capture Your Perfect Moment</h1>
            <p>Connect with professional photographers for your special occasions</p>
            <button class="cta-button"><a href="photographers.php">Explore Photographers</a></button>
        </div>
    </section>

    <section class="photographers" id="photographers">
    <h2 class="section-title">Our Professional Photographers</h2>
    <div class="photographer-grid">
        <?php if (!empty($photographers)): ?>
            <?php foreach ($photographers as $photographer): ?>
                <div class="photographer-card">
                    <?php if (!empty($photographer['profile_pic'])): ?>
                        <img src="<?php echo htmlspecialchars($photographer['profile_pic']); ?>" alt="<?php echo htmlspecialchars($photographer['name']); ?>" class="photographer-img">
                    <?php else: ?>
                        <img src="images/default-photographer.jpg" alt="<?php echo htmlspecialchars($photographer['name']); ?>" class="photographer-img">
                    <?php endif; ?>
                    <div class="photographer-info">
                        <h3><?php echo htmlspecialchars($photographer['name']); ?></h3>
                        <p><?php echo htmlspecialchars(substr($photographer['bio'], 0, 100) . (strlen($photographer['bio']) > 100 ? '...' : '')); ?></p>
                        <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($photographer['location']); ?></p>
                        <div class="rating">
                            <?php
                            // Placeholder for rating - in a real app, you'd calculate this from a ratings table
                            $rating = 4.5; // Example rating
                            echo str_repeat('★', floor($rating));
                            echo ($rating - floor($rating) >= 0.5) ? '½' : '';
                            echo str_repeat('☆', 5 - ceil($rating));
                            ?>
                            <span>(<?php echo $rating; ?>)</span>
                        </div>
                        <a href="photographer-profileview.php?id=<?php echo $photographer['user_id']; ?>" class="cta-button">View Profile</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-photographers">No photographers found. Check back later!</p>
        <?php endif; ?>
    </div>
    
    <!-- Add Explore More button -->
    <div style="text-align: center; margin-top: 3rem;">
        <a href="photographers.php" class="cta-button">Explore More Photographers</a>
    </div>
</section>

    <!-- New About Us Section (replacing the Booking section) -->
    <section class="about-us" id="about">
        <h2 class="section-title">About LensPro</h2>
        <div class="about-container">
            <div class="about-image">
                <img src="images/illusimg.png" alt="About LensPro">
            </div>
            <div class="about-content">
                <h3>Your Premier Photography Platform</h3>
                <p>Founded in 2023, LensPro brings together talented photographers and clients seeking to capture life's most precious moments. We believe that every significant event deserves to be immortalized through the lens of a skilled professional.</p>
                <p>Our mission is to make professional photography accessible to everyone while providing photographers with a platform to showcase their unique talents and grow their business.</p>
                
                <div class="feature-grid">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-camera"></i>
                        </div>
                        <div class="feature-text">
                            <h4>Vetted Professionals</h4>
                            <p>All photographers on our platform are thoroughly vetted for quality and professionalism.</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="feature-text">
                            <h4>Easy Booking</h4>
                            <p>Our streamlined booking process makes scheduling your photography session simple and hassle-free.</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="feature-text">
                            <h4>Verified Reviews</h4>
                            <p>Read authentic reviews from real clients to help you choose the perfect photographer.</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="feature-text">
                            <h4>Secure Payments</h4>
                            <p>Your transactions are protected with our secure payment system.</p>
                        </div>
                    </div>
                </div>
                
                <div style="margin-top: 2rem; text-align: center;">
                <?php if (isset($_SESSION['userid'])): ?> 
                    <a href="booking.php" class="cta-button">Book Now</a>
                <?php else: ?>
                    <a href="login.php" class="cta-button">Login to Book</a>
                <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <p>&copy; 2025 LensPro. All rights reserved.</p>
        <div class="social-links">
            <a href="https://www.facebook.com/"><i class="fab fa-facebook"></i></a>
            <a href="https://www.instagram.com/"><i class="fab fa-instagram"></i></a>
            <a href="https://x.com/"><i class="fab fa-twitter"></i></a>
        </div>
    </footer>

    <script>
        // document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        //     anchor.addEventListener('click', function (e) {
        //         e.preventDefault();
        //         document.querySelector(this.getAttribute('href')).scrollIntoView({
        //             behavior: 'smooth'
        //         });
        //     });
        // })
    </script>

</body>

</html>