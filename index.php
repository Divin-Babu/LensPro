<?php
session_start();
include 'dbconnect.php';
$sql = "SELECT name, profile_pic, role FROM tbl_user WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['userid']);
mysqli_stmt_execute($stmt); 
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
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

        .cta-button {
            background: var(--secondary-color);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 5px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background 0.3s;
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

        .booking {
            padding: 5rem 5%;
            background: var(--light-gray);
        }

        .booking-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .booking-form {
            max-width: 535px;
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            flex: 1;
        }

        .booking-image {
            flex: 1;
            padding-left: 2rem;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .booking-image img {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--secondary-color);
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
            <a href="#home">Home</a>
            <?php if (isset($_SESSION['userid'])): ?>
            <a href="#photographers">Photographers</a>
            <?php else: ?>
                <a href="photographerregis.php">Become A Photographer</a>
            <?php endif; ?>
            <a href="#booking">Book Now</a>
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
                        <a href="my-bookings.php"><i class="fas fa-calendar-check"></i> My Bookings</a>
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
            <button class="cta-button">Explore Photographers</button>
        </div>
    </section>

    <section class="photographers" id="photographers">
        <h2 class="section-title">Our Professional Photographers</h2>
        <div class="photographer-grid" id="photographerGrid"></div>
    </section>

    <section class="booking" id="booking">
        <h2 class="section-title">Book Your Session</h2>
        <div class="booking-container">
            <form class="booking-form">
                <div class="form-group">
                    <label for="photographer">Select Photographer</label>
                    <select id="photographer" required>
                        <option value="">Choose a photographer</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="date">Preferred Date</label>
                    <input type="date" id="date" required>
                </div>
                <div class="form-group">
                    <label for="time">Preferred Time</label>
                    <input type="time" id="time" required>
                </div>
                <div class="form-group">
                    <label for="type">Session Type</label>
                    <select id="type" required>
                        <option value="">Select session type</option>
                        <option value="portrait">Portrait</option>
                        <option value="wedding">Wedding</option>
                        <option value="event">Event</option>
                        <option value="commercial">Commercial</option>
                    </select>
                </div>
                <button type="submit" class="cta-button">Book Now</button>
            </form>
            <div class="booking-image">
                <img src="images/illusimg.png" alt="">
            </div>
        </div>
    </section>

    <footer>
        <p>&copy; 2025 LensPro. All rights reserved.</p>
        <div class="social-links">
            <a href="#"><i class="fab fa-facebook"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
        </div>
    </footer>

    <script>
        const photographers = [
            { name: "Bruno Andrews", specialty: "Portrait & Wedding Photography", rating: 4.8, image: "images/pgimg1.jpg" },
            { name: "Anna Warner", specialty: "Commercial Photography", rating: 4.9, image: "images/pgimg2.jpg" },
            { name: "Emma Williams", specialty: "Nature & Wildlife Photography", rating: 4.7, image: "images/pgimg3.jpg" },
            { name: "David Lee", specialty: "Advertising Photography", rating: 4.6, image: "images/pgimg4.png" },
            { name: "Oliver Smith", specialty: "Fashion & Editorial Photography", rating: 4.9, image: "images/pgimg5.jpg" },
            { name: "Johnson Junior", specialty: "Event & Corporate Photography", rating: 4.5, image: "images/pgimg6.jpg" },
            { name: "Tom Williamson", specialty: "Fine Art Photography", rating: 4.8, image: "images/pgimg7.jpg" },
            { name: "Sarah Johnson", specialty: "Sports & Action Photography", rating: 4.7, image: "images/pgimg8.jpg" }
        ];

        const photographerGrid = document.getElementById('photographerGrid');
        const photographerSelect = document.getElementById('photographer');

        photographers.forEach(photographer => {
            const card = document.createElement('div');
            card.className = 'photographer-card';
            card.innerHTML = `
                <img src="${photographer.image}" alt="${photographer.name}" class="photographer-img">
                <div class="photographer-info">
                    <h3>${photographer.name}</h3>
                    <p>${photographer.specialty}</p>
                    <div class="rating">
                        ${'★'.repeat(Math.floor(photographer.rating))}${photographer.rating % 1 !== 0 ? '½' : ''}${'☆'.repeat(5 - Math.ceil(photographer.rating))}
                        <span>(${photographer.rating})</span>
                    </div>
                    <button class="cta-button">View Profile</button>
                </div>
            `;
            photographerGrid.appendChild(card);

            const option = document.createElement('option');
            option.value = photographer.name.toLowerCase().replace(' ', '-');
            option.textContent = photographer.name;
            photographerSelect.appendChild(option);
        });

        document.querySelector('.booking-form').addEventListener('submit', function (e) {
            e.preventDefault();
            alert('Booking submitted! We will contact you shortly to confirm your appointment.');
        });

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
