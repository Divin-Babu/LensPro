<?php
//session_start();
include 'dbconnect.php';
// $sql = "SELECT name, profile_pic, role FROM tbl_user WHERE user_id = ?";
// $stmt = mysqli_prepare($conn, $sql);
// mysqli_stmt_bind_param($stmt, "i", $_SESSION['userid'] ?? 0);
// mysqli_stmt_execute($stmt); 
// $result = mysqli_stmt_get_result($stmt);
// $row = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LensPro - Find Photographers</title>
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
            padding-bottom: 0%;
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

        .main-container {
            margin-top: 80px;
            padding: 2rem 5%;
            display: flex;
            gap: 2rem;
        }

        .filter-container {
            flex: 0 0 280px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            height: fit-content;
        }

        .filter-title {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

        .filter-group {
            margin-bottom: 1.5rem;
        }

        .filter-group h3 {
            font-size: 1.1rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }

        .filter-group h3 i {
            font-size: 0.9rem;
            transition: transform 0.3s;
        }

        .filter-group h3.expanded i {
            transform: rotate(180deg);
        }

        .filter-options {
            margin-top: 0.5rem;
        }

        .checkbox-option {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .checkbox-option input {
            margin-right: 8px;
        }

        .search-box {
            display: flex;
            margin-bottom: 1.5rem;
        }

        .search-box input {
            flex: 1;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px 0 0 5px;
        }

        .search-box button {
            background: var(--secondary-color);
            color: white;
            border: none;
            padding: 0 1rem;
            border-radius: 0 5px 5px 0;
            cursor: pointer;
        }

        .clear-filter {
            background: var(--light-gray);
            color: var(--primary-color);
            border: none;
            padding: 0.8rem;
            border-radius: 5px;
            width: 100%;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.3s;
        }

        .clear-filter:hover {
            background: #ddd;
        }

        .photographers-container {
            flex: 1;
        }

        .search-results {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .results-count {
            font-size: 1.2rem;
            color: var(--primary-color);
        }

        .sort-options {
            display: flex;
            align-items: center;
        }

        .sort-options label {
            margin-right: 10px;
            color: var(--primary-color);
        }

        .sort-options select {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .photographer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
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
            height: 220px;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .photographer-card:hover .photographer-img {
            transform: scale(1.05);
        }

        .photographer-info {
            padding: 1.5rem;
        }

        .photographer-info h3 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            font-size: 1.3rem;
            font-weight: 600;
        }

        .photographer-meta {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            color: #666;
        }

        .photographer-meta i {
            margin-right: 5px;
            color: var(--secondary-color);
        }

        .photographer-meta span {
            margin-right: 15px;
        }

        .rating {
            color: #f1c40f;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }

        .rating span {
            color: #666;
            margin-left: 5px;
        }

        .price-range {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .starting-price {
            font-weight: 600;
            color: var(--primary-color);
        }

        .view-profile {
            display: block;
            background: var(--secondary-color);
            color: white;
            text-align: center;
            padding: 0.8rem;
            border-radius: 5px;
            text-decoration: none;
            transition: background 0.3s;
        }

        .view-profile:hover {
            background: var(--hover-color);
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
        }

        .pagination a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            margin: 0 5px;
            border-radius: 5px;
            text-decoration: none;
            color: var(--primary-color);
            background: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: background 0.3s;
        }

        .pagination a:hover,
        .pagination a.active {
            background: var(--secondary-color);
            color: white;
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

        @media (max-width: 992px) {
            .main-container {
                flex-direction: column;
            }

            .filter-container {
                flex: 0 0 100%;
            }
        }

        @media (max-width: 768px) {
            .photographer-grid {
                grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            }
        }

        @media (max-width: 576px) {
            .photographer-grid {
                grid-template-columns: 1fr;
            }

            .search-results {
                flex-direction: column;
                align-items: flex-start;
            }

            .sort-options {
                margin-top: 1rem;
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
            <a href="index.php#booking">Book Now</a>
            <?php if (isset($_SESSION['userid']) && $row['role']=='user'): ?>
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
                <a href="login.php" class="nav-link"><i class="fas fa-user"></i> Login/Signup</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="main-container">
        <!-- Filter sidebar -->
        <div class="filter-container">
            <h2 class="filter-title">Filters</h2>
            <div class="search-box">
                <input type="text" placeholder="Search photographers..." id="searchInput">
                <button type="button"><i class="fas fa-search"></i></button>
            </div>

            <div class="filter-group">
                <h3 class="expanded">Location <i class="fas fa-chevron-down"></i></h3>
                <div class="filter-options">
                    <div class="checkbox-option">
                        <input type="checkbox" id="location-mumbai" name="location" value="Mumbai">
                        <label for="location-mumbai">Mumbai</label>
                    </div>
                    <div class="checkbox-option">
                        <input type="checkbox" id="location-delhi" name="location" value="Delhi">
                        <label for="location-delhi">Delhi</label>
                    </div>
                    <div class="checkbox-option">
                        <input type="checkbox" id="location-bangalore" name="location" value="Bangalore">
                        <label for="location-bangalore">Bangalore</label>
                    </div>
                    <div class="checkbox-option">
                        <input type="checkbox" id="location-chennai" name="location" value="Chennai">
                        <label for="location-chennai">Chennai</label>
                    </div>
                    <div class="checkbox-option">
                        <input type="checkbox" id="location-hyderabad" name="location" value="Hyderabad">
                        <label for="location-hyderabad">Hyderabad</label>
                    </div>
                </div>
            </div>

            <div class="filter-group">
                <h3>Category <i class="fas fa-chevron-down"></i></h3>
                <div class="filter-options" style="display: none;">
                    <div class="checkbox-option">
                        <input type="checkbox" id="category-wedding" name="category" value="Wedding">
                        <label for="category-wedding">Wedding</label>
                    </div>
                    <div class="checkbox-option">
                        <input type="checkbox" id="category-portrait" name="category" value="Portrait">
                        <label for="category-portrait">Portrait</label>
                    </div>
                    <div class="checkbox-option">
                        <input type="checkbox" id="category-commercial" name="category" value="Commercial">
                        <label for="category-commercial">Commercial</label>
                    </div>
                    <div class="checkbox-option">
                        <input type="checkbox" id="category-event" name="category" value="Event">
                        <label for="category-event">Event</label>
                    </div>
                    <div class="checkbox-option">
                        <input type="checkbox" id="category-fashion" name="category" value="Fashion">
                        <label for="category-fashion">Fashion & Editorial</label>
                    </div>
                    <div class="checkbox-option">
                        <input type="checkbox" id="category-nature" name="category" value="Nature">
                        <label for="category-nature">Nature & Wildlife</label>
                    </div>
                </div>
            </div>

            <div class="filter-group">
                <h3>Price Range <i class="fas fa-chevron-down"></i></h3>
                <div class="filter-options" style="display: none;">
                    <div class="checkbox-option">
                        <input type="checkbox" id="price-1" name="price" value="0-1000">
                        <label for="price-1">Under ₹1,000</label>
                    </div>
                    <div class="checkbox-option">
                        <input type="checkbox" id="price-2" name="price" value="1000-3000">
                        <label for="price-2">₹1,000 - ₹3,000</label>
                    </div>
                    <div class="checkbox-option">
                        <input type="checkbox" id="price-3" name="price" value="3000-5000">
                        <label for="price-3">₹3,000 - ₹5,000</label>
                    </div>
                    <div class="checkbox-option">
                        <input type="checkbox" id="price-4" name="price" value="5000-10000">
                        <label for="price-4">₹5,000 - ₹10,000</label>
                    </div>
                    <div class="checkbox-option">
                        <input type="checkbox" id="price-5" name="price" value="10000+">
                        <label for="price-5">Above ₹10,000</label>
                    </div>
                </div>
            </div>

            <div class="filter-group">
                <h3>Rating <i class="fas fa-chevron-down"></i></h3>
                <div class="filter-options" style="display: none;">
                    <div class="checkbox-option">
                        <input type="checkbox" id="rating-5" name="rating" value="5">
                        <label for="rating-5">5 Stars</label>
                    </div>
                    <div class="checkbox-option">
                        <input type="checkbox" id="rating-4" name="rating" value="4">
                        <label for="rating-4">4 Stars & Above</label>
                    </div>
                    <div class="checkbox-option">
                        <input type="checkbox" id="rating-3" name="rating" value="3">
                        <label for="rating-3">3 Stars & Above</label>
                    </div>
                </div>
            </div>

            <div class="filter-group">
                <h3>Experience <i class="fas fa-chevron-down"></i></h3>
                <div class="filter-options" style="display: none;">
                    <div class="checkbox-option">
                        <input type="checkbox" id="exp-1" name="experience" value="0-2">
                        <label for="exp-1">0 - 2 Years</label>
                    </div>
                    <div class="checkbox-option">
                        <input type="checkbox" id="exp-2" name="experience" value="2-5">
                        <label for="exp-2">2 - 5 Years</label>
                    </div>
                    <div class="checkbox-option">
                        <input type="checkbox" id="exp-3" name="experience" value="5-10">
                        <label for="exp-3">5 - 10 Years</label>
                    </div>
                    <div class="checkbox-option">
                        <input type="checkbox" id="exp-4" name="experience" value="10+">
                        <label for="exp-4">10+ Years</label>
                    </div>
                </div>
            </div>

            <button class="clear-filter" id="clearFilter">Clear All Filters</button>
        </div>

        <!-- Photographers listing -->
        <div class="photographers-container">
            <div class="search-results">
                <div class="results-count"><span id="totalResults">8</span> photographers found</div>
                <div class="sort-options">
                    <label for="sort-by">Sort by:</label>
                    <select id="sort-by">
                        <option value="popularity">Popularity</option>
                        <option value="rating-high">Rating (High to Low)</option>
                        <option value="rating-low">Rating (Low to High)</option>
                        <option value="price-low">Price (Low to High)</option>
                        <option value="price-high">Price (High to Low)</option>
                    </select>
                </div>
            </div>

            <div class="photographer-grid" id="photographerGrid">
                <!-- Photographer cards will be populated here by JavaScript -->
            </div>

            <div class="pagination">
                <a href="#" class="active">1</a>
                <a href="#">2</a>
                <a href="#">3</a>
                <a href="#"><i class="fas fa-angle-right"></i></a>
            </div>
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
        // Sample data for photographers
        const photographers = [
            {
                name: "Bruno Andrews",
                specialty: "Portrait & Wedding Photography",
                location: "Mumbai",
                category: ["Wedding", "Portrait"],
                rating: 4.8,
                price: 5000,
                experience: 7,
                image: "images/pgimg1.jpg"
            },
            {
                name: "Anna Warner",
                specialty: "Commercial Photography",
                location: "Delhi",
                category: ["Commercial", "Event"],
                rating: 4.9,
                price: 8000,
                experience: 10,
                image: "images/pgimg2.jpg"
            },
            {
                name: "Emma Williams",
                specialty: "Nature & Wildlife Photography",
                location: "Bangalore",
                category: ["Nature", "Portrait"],
                rating: 4.7,
                price: 3500,
                experience: 5,
                image: "images/pgimg3.jpg"
            },
            {
                name: "David Lee",
                specialty: "Advertising Photography",
                location: "Chennai",
                category: ["Commercial", "Fashion"],
                rating: 4.6,
                price: 7500,
                experience: 9,
                image: "images/pgimg4.png"
            },
            {
                name: "Oliver Smith",
                specialty: "Fashion & Editorial Photography",
                location: "Hyderabad",
                category: ["Fashion", "Commercial"],
                rating: 4.9,
                price: 12000,
                experience: 12,
                image: "images/pgimg5.jpg"
            },
            {
                name: "Johnson Junior",
                specialty: "Event & Corporate Photography",
                location: "Mumbai",
                category: ["Event", "Commercial"],
                rating: 4.5,
                price: 4500,
                experience: 4,
                image: "images/pgimg6.jpg"
            },
            {
                name: "Tom Williamson",
                specialty: "Fine Art Photography",
                location: "Delhi",
                category: ["Portrait", "Nature"],
                rating: 4.8,
                price: 9000,
                experience: 8,
                image: "images/pgimg7.jpg"
            },
            {
                name: "Sarah Johnson",
                specialty: "Sports & Action Photography",
                location: "Bangalore",
                category: ["Event", "Nature"],
                rating: 4.7,
                price: 6000,
                experience: 6,
                image: "images/pgimg8.jpg"
            }
        ];

        // Function to render photographer cards
        function renderPhotographers(photographersList) {
            const grid = document.getElementById('photographerGrid');
            grid.innerHTML = '';

            if (photographersList.length === 0) {
                document.getElementById('totalResults').textContent = 0;
                grid.innerHTML = '<div class="no-results">No photographers found matching your criteria. Please try different filters.</div>';
                return;
            }

            document.getElementById('totalResults').textContent = photographersList.length;

            photographersList.forEach(photographer => {
                const card = document.createElement('div');
                card.className = 'photographer-card';
                card.innerHTML = `
                    <img src="${photographer.image}" alt="${photographer.name}" class="photographer-img">
                    <div class="photographer-info">
                        <h3>${photographer.name}</h3>
                        <div class="photographer-meta">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>${photographer.location}</span>
                            <i class="fas fa-briefcase"></i>
                            <span>${photographer.experience} Yrs</span>
                        </div>
                        <p>${photographer.specialty}</p>
                        <div class="rating">
                            ${'★'.repeat(Math.floor(photographer.rating))}${photographer.rating % 1 !== 0 ? '½' : ''}${'☆'.repeat(5 - Math.ceil(photographer.rating))}
                            <span>(${photographer.rating})</span>
                        </div>
                        <div class="price-range">
                            <span class="starting-price">Starting from ₹${photographer.price}</span>
                        </div>
                        <a href="photographer-detail.php?id=${photographer.name.toLowerCase().replace(' ', '-')}" class="view-profile">View Profile</a>
                    </div>
                `;
                grid.appendChild(card);
            });
        }

        // Initialize the page with all photographers
        renderPhotographers(photographers);

        // Filter functionality
        function filterPhotographers() {
            // Get selected locations
            const selectedLocations = Array.from(document.querySelectorAll('input[name="location"]:checked')).map(input => input.value);
            
            // Get selected categories
            const selectedCategories = Array.from(document.querySelectorAll('input[name="category"]:checked')).map(input => input.value);
            
            // Get selected price ranges
            const selectedPrices = Array.from(document.querySelectorAll('input[name="price"]:checked')).map(input => input.value);
            
            // Get selected ratings
            const selectedRatings = Array.from(document.querySelectorAll('input[name="rating"]:checked')).map(input => input.value);
            
            // Get selected experience ranges
            const selectedExperiences = Array.from(document.querySelectorAll('input[name="experience"]:checked')).map(input => input.value);
            
            // Get search text
            const searchText = document.getElementById('searchInput').value.toLowerCase();

            // Filter photographers based on selections
            let filtered = photographers;

            // Filter by search text
            if (searchText) {
                filtered = filtered.filter(p => 
                    p.name.toLowerCase().includes(searchText) || 
                    p.specialty.toLowerCase().includes(searchText) ||
                    p.location.toLowerCase().includes(searchText)
                );
            }

            // Filter by location
            if (selectedLocations.length > 0) {
                filtered = filtered.filter(p => selectedLocations.includes(p.location));
            }

            // Filter by category
            if (selectedCategories.length > 0) {
                filtered = filtered.filter(p => 
                    p.category.some(cat => selectedCategories.includes(cat))
                );
            }

            // Filter by price range
            // Complete the existing filter functionality script
            // Filter by price range
            if (selectedPrices.length > 0) {
                filtered = filtered.filter(p => {
                    return selectedPrices.some(range => {
                        const [min, max] = range.split('-').map(Number);
                        if (max) {
                            return p.price >= min && p.price <= max;
                        } else {
                            // For "10000+" case
                            return p.price >= min;
                        }
                    });
                });
            }

            // Filter by rating
            if (selectedRatings.length > 0) {
                filtered = filtered.filter(p => {
                    return selectedRatings.some(rating => {
                        const minRating = Number(rating);
                        return p.rating >= minRating;
                    });
                });
            }

            // Filter by experience
            if (selectedExperiences.length > 0) {
                filtered = filtered.filter(p => {
                    return selectedExperiences.some(range => {
                        const [min, max] = range.split('-').map(Number);
                        if (max) {
                            return p.experience >= min && p.experience <= max;
                        } else {
                            // For "10+" case
                            return p.experience >= min;
                        }
                    });
                });
            }

            // Apply sorting
            const sortBy = document.getElementById('sort-by').value;
            switch (sortBy) {
                case 'rating-high':
                    filtered.sort((a, b) => b.rating - a.rating);
                    break;
                case 'rating-low':
                    filtered.sort((a, b) => a.rating - b.rating);
                    break;
                case 'price-low':
                    filtered.sort((a, b) => a.price - b.price);
                    break;
                case 'price-high':
                    filtered.sort((a, b) => b.price - a.price);
                    break;
                // Default is popularity (no sorting needed for demo)
            }

            renderPhotographers(filtered);
        }

        // Event listeners for filter changes
        document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', filterPhotographers);
        });

        document.getElementById('searchInput').addEventListener('input', filterPhotographers);
        document.getElementById('sort-by').addEventListener('change', filterPhotographers);

        // Clear all filters
        document.getElementById('clearFilter').addEventListener('click', function() {
            document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                checkbox.checked = false;
            });
            document.getElementById('searchInput').value = '';
            document.getElementById('sort-by').value = 'popularity';
            renderPhotographers(photographers);
        });

        // Filter group expand/collapse
        document.querySelectorAll('.filter-group h3').forEach(header => {
            header.addEventListener('click', function() {
                this.classList.toggle('expanded');
                const options = this.nextElementSibling;
                options.style.display = options.style.display === 'none' ? 'block' : 'none';
            });
        });

        // Pagination functionality
        document.querySelectorAll('.pagination a').forEach(page => {
            page.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.pagination a').forEach(p => p.classList.remove('active'));
                this.classList.add('active');
                
                // In a real application, you would load different sets of photographers here
                // For this demo, we'll just scroll to the top of the results
                document.querySelector('.photographers-container').scrollIntoView({ behavior: 'smooth' });
            });
        });

        // Load more photographers function (for real implementation)
        function loadMorePhotographers(page) {
            // In a real application, you would make an AJAX call to fetch more photographers
            // For example:
            /*
            fetch(`api/photographers?page=${page}`)
                .then(response => response.json())
                .then(data => {
                    const newPhotographers = data.photographers;
                    renderPhotographers(newPhotographers);
                })
                .catch(error => console.error('Error loading photographers:', error));
            */
        }

        // Initialize the filter groups
        document.querySelectorAll('.filter-group h3').forEach(header => {
            const options = header.nextElementSibling;
            if (header.classList.contains('expanded')) {
                options.style.display = 'block';
            } else {
                options.style.display = 'none';
            }
        });

        // Optional: Add geolocation feature to find photographers near user
        document.getElementById('findNearMe').addEventListener('click', function() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(position => {
                    const latitude = position.coords.latitude;
                    const longitude = position.coords.longitude;
                    
                    // In a real application, you would make an API call to find photographers near these coordinates
                    // For this demo, we'll just filter by a predefined city
                    document.querySelectorAll('input[name="location"]').forEach(input => {
                        input.checked = input.value === 'Mumbai';
                    });
                    filterPhotographers();
                });
            } else {
                alert("Geolocation is not supported by this browser.");
            }
        });