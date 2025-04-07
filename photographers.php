<?php
session_start();
include 'dbconnect.php';

// Get user information if logged in
$user_info = [];
if (isset($_SESSION['userid'])) {
    $sql = "SELECT name, profile_pic, role FROM tbl_user WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['userid']);
    mysqli_stmt_execute($stmt); 
    $result = mysqli_stmt_get_result($stmt);
    $user_info = mysqli_fetch_assoc($result);
}

// Fetch all photographers from database by joining tbl_user and tbl_photographer tables
$photographers_query = "SELECT u.user_id, u.name, u.profile_pic, p.bio, p.location, 
                       (SELECT COUNT(*) FROM tbl_gallery WHERE photographer_id = u.user_id) as photo_count,
                       (SELECT AVG(rating) FROM tbl_reviews WHERE photographer_id = u.user_id AND status = TRUE) as avg_rating,
                       (SELECT COUNT(*) FROM tbl_reviews WHERE photographer_id = u.user_id AND status = TRUE) as review_count
                       FROM tbl_user u 
                       JOIN tbl_photographer p ON u.user_id = p.photographer_id 
                       WHERE u.role = 'photographer' AND u.status = TRUE";
$photographers_result = mysqli_query($conn, $photographers_query);

// Get all categories for filters
$categories_query = "SELECT category_id, category_name FROM tbl_categories WHERE status = TRUE";
$categories_result = mysqli_query($conn, $categories_query);

// Get all unique locations from photographers
$locations_query = "SELECT DISTINCT location FROM tbl_photographer";
$locations_result = mysqli_query($conn, $locations_query);
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

        .no-results {
            grid-column: 1 / -1;
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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
            <a href="booking.php">Book Now</a>
            <?php if (isset($_SESSION['userid']) && isset($user_info['role']) && $user_info['role'] == 'user'): ?>
                <div class="user-profile">
                    <div class="profile-photo">
                        <?php if (isset($user_info['profile_pic']) && $user_info['profile_pic']): ?>
                            <img src="<?php echo htmlspecialchars($user_info['profile_pic']); ?>" alt="">
                        <?php else: ?>
                            <i class="fas fa-user-circle"></i>
                        <?php endif; ?>
                    </div>
                    <span class="username"><?php echo htmlspecialchars($user_info['name']); ?></span>
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
                    <?php
                    while ($location = mysqli_fetch_assoc($locations_result)) {
                        $location_name = htmlspecialchars($location['location']);
                        $location_id = str_replace(' ', '-', strtolower($location_name));
                        echo '<div class="checkbox-option">
                                <input type="checkbox" id="location-'.$location_id.'" name="location" value="'.$location_name.'">
                                <label for="location-'.$location_id.'">'.$location_name.'</label>
                              </div>';
                    }
                    ?>
                </div>
            </div>

            <div class="filter-group">
                <h3>Category <i class="fas fa-chevron-down"></i></h3>
                <div class="filter-options" style="display: none;">
                    <?php
                    while ($category = mysqli_fetch_assoc($categories_result)) {
                        $category_name = htmlspecialchars($category['category_name']);
                        $category_id = $category['category_id'];
                        echo '<div class="checkbox-option">
                                <input type="checkbox" id="category-'.$category_id.'" name="category" value="'.$category_name.'">
                                <label for="category-'.$category_id.'">'.$category_name.'</label>
                              </div>';
                    }
                    ?>
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

            <button class="clear-filter" id="clearFilter">Clear All Filters</button>
        </div>

        
        <div class="photographers-container">
            <div class="search-results">
                <div class="results-count"><span id="totalResults">
                    <?php echo mysqli_num_rows($photographers_result); ?>
                </span> photographers found</div>
                <div class="sort-options">
                    <label for="sort-by">Sort by:</label>
                    <select id="sort-by">
                        <option value="name">Name</option>
                        <option value="location">Location</option>
                    </select>
                </div>
            </div>

            <div class="photographer-grid" id="photographerGrid">
                <?php
                if (mysqli_num_rows($photographers_result) > 0) {
                    while ($photographer = mysqli_fetch_assoc($photographers_result)) {
                        
                        // Get random starting price between 2000 and 15000 (would be real data in production)
                        $price = rand(2, 15) * 1000;
                        
                        // Generate profile image path (use actual profile_pic if available)
                        $image_path = $photographer['profile_pic'] ? htmlspecialchars($photographer['profile_pic']) : 'images/default-photographer.jpg';
                        
                        // Short bio or specialty
                        $bio = htmlspecialchars(substr($photographer['bio'], 0, 100)) . '...';
                        
                        echo '<div class="photographer-card" data-name="'.htmlspecialchars($photographer['name']).'" 
                             data-location="'.htmlspecialchars($photographer['location']).'" 
                             data-photo-count="'.$photographer['photo_count'].'">
                                <img src="'.$image_path.'" alt="'.htmlspecialchars($photographer['name']).'" class="photographer-img">
                                <div class="photographer-info">
                                    <h3>'.htmlspecialchars($photographer['name']).'</h3>
                                    <div class="photographer-meta">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span>'.htmlspecialchars($photographer['location']).'</span>
                                    </div>
                                    <p>'.$bio.'</p>';
                                    
                        $rating = $photographer['avg_rating'] ? number_format($photographer['avg_rating'], 1) : 0;
                        $review_count = $photographer['review_count'] ? $photographer['review_count'] : 0;
                        echo '<div class="rating">';
                        
                        $full_stars = floor($rating);
                        $half_star = ($rating - $full_stars) >= 0.5;
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $full_stars) {
                                echo '<i class="fas fa-star"></i>';
                            } elseif ($half_star && $i == $full_stars + 1) {
                                echo '<i class="fas fa-star-half-alt"></i>';
                                $half_star = false;
                            } else {
                                echo '<i class="far fa-star"></i>';
                            }
                        }
                        echo ' <span>('.$rating.' - '.$review_count.' reviews)</span>';
                        echo '</div>';
                        
                        echo '<div class="price-range">
                                <span class="starting-price">Starting from ₹'.$price.'</span>
                              </div>
                              <a href="photographer-profileview.php?id='.$photographer['user_id'].'" class="view-profile">View Profile</a>
                            </div>
                        </div>';
                    }
                } else {
                    echo '<div class="no-results">No photographers found in the database.</div>';
                }
                ?>
            </div>

            <!-- Pagination will be controlled by JavaScript in real implementation -->
            <!-- <div class="pagination">
                <a href="#" class="active">1</a>
                <a href="#">2</a>
                <a href="#">3</a>
                <a href="#"><i class="fas fa-angle-right"></i></a>
            </div> -->
        </div>
    </div>

    <footer>
        <p>&copy; 2025 LensPro. All rights reserved.</p>
        <!-- <div class="social-links">
            <a href="#"><i class="fab fa-facebook"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
        </div> -->
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Store all photographer cards for filtering
            const photographerCards = document.querySelectorAll('.photographer-card');
            const totalResultsElement = document.getElementById('totalResults');
            
            // Filter functionality
            function filterPhotographers() {
                // Get search input
                const searchText = document.getElementById('searchInput').value.toLowerCase();
                
                // Get selected locations
                const selectedLocations = Array.from(document.querySelectorAll('input[name="location"]:checked')).map(input => input.value);
                
                // Get selected categories
                const selectedCategories = Array.from(document.querySelectorAll('input[name="category"]:checked')).map(input => input.value);
                
                // Get selected price ranges
                const selectedPrices = Array.from(document.querySelectorAll('input[name="price"]:checked')).map(input => input.value);
                
                let visibleCount = 0;
                
                // Loop through all photographer cards
                photographerCards.forEach(card => {
                    const name = card.dataset.name.toLowerCase();
                    const location = card.dataset.location;
                    
                    // Check if card matches search text
                    const matchesSearch = !searchText || name.includes(searchText) || location.toLowerCase().includes(searchText);
                    
                    // Check if card matches selected locations
                    //const matchesLocation = selectedLocations.length === 0 || selectedLocations.includes(location);
                    
                    // More filters would be implemented similarly with real data
                    // For now, we'll just use the search and location filters
                    
                    // Show or hide card based on filters
                    if (matchesSearch /*&& matchesLocation*/) {
                        card.style.display = 'block';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });
                
                // Update total results count
                totalResultsElement.textContent = visibleCount;
                
                // Show no results message if needed
                const noResultsElement = document.querySelector('.no-results');
                if (visibleCount === 0) {
                    if (!noResultsElement) {
                        const noResults = document.createElement('div');
                        noResults.className = 'no-results';
                        noResults.innerText = 'No photographers found matching your criteria. Please try different filters.';
                        document.getElementById('photographerGrid').appendChild(noResults);
                    }
                } else if (noResultsElement) {
                    noResultsElement.remove();
                }
            }
            
            // Sort functionality
            function sortPhotographers() {
                const sortBy = document.getElementById('sort-by').value;
                const grid = document.getElementById('photographerGrid');
                
                // Convert NodeList to Array for sorting
                const cardsArray = Array.from(photographerCards);
                
                // Sort based on selected option
                cardsArray.sort((a, b) => {
                    switch (sortBy) {
                        case 'name':
                            return a.dataset.name.localeCompare(b.dataset.name);
                        case 'location':
                            return a.dataset.location.localeCompare(b.dataset.location);
                        default:
                            return 0;
                    }
                });
                
                // Re-append sorted cards to the grid
                cardsArray.forEach(card => grid.appendChild(card));
            }
            
            // Event listeners for filter and sort
            document.getElementById('searchInput').addEventListener('input', filterPhotographers);
            document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                checkbox.addEventListener('change', filterPhotographers);
            });
            document.getElementById('sort-by').addEventListener('change', sortPhotographers);
            
            // Clear all filters
            document.getElementById('clearFilter').addEventListener('click', function() {
                document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                    checkbox.checked = false;
                });
                document.getElementById('searchInput').value = '';
                filterPhotographers();
            });
            
            // Filter group expand/collapse
            document.querySelectorAll('.filter-group h3').forEach(header => {
                header.addEventListener('click', function() {
                    this.classList.toggle('expanded');
                    const options = this.nextElementSibling;
                    options.style.display = options.style.display === 'none' ? 'block' : 'none';
                });
            });
            
            // Pagination functionality (would be implemented with real data)
            document.querySelectorAll('.pagination a').forEach(page => {
                page.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.querySelectorAll('.pagination a').forEach(p => p.classList.remove('active'));
                    this.classList.add('active');
                    
                    // In a real implementation, this would load different pages of photographers
                    document.querySelector('.photographers-container').scrollIntoView({ behavior: 'smooth' });
                });
            });
        });
    </script>
</body>
</html>