<?php
session_start();
include 'dbconnect.php';

if ($_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

function getAllReviews($conn) {
    $sql = "SELECT r.*, u.name as user_name, p.name as photographer_name, b.session_type 
            FROM tbl_reviews r
            JOIN tbl_user u ON r.user_id = u.user_id
            JOIN tbl_user p ON r.photographer_id = p.user_id
            JOIN tbl_booking b ON r.booking_id = b.booking_id
            ORDER BY r.created_at DESC";
    
    $result = $conn->query($sql);
    return $result;
}

// Function to toggle review status
function toggleReviewStatus($reviewId, $conn) {
    $sql = "UPDATE tbl_reviews SET status = NOT status WHERE review_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $reviewId);
    return $stmt->execute();
}

// Handle review status toggle if requested
if (isset($_POST['toggle_status']) && isset($_POST['review_id'])) {
    $reviewId = $_POST['review_id'];
    if (toggleReviewStatus($reviewId, $conn)) {
        // Redirect to avoid form resubmission
        header('Location: adminviewreview.php?status_updated=1');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LensPro - Admin Reviews</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
            --sidebar-width: 250px;
            --header-height: 60px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f5f6fa;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background: var(--primary-color);
            padding: 1rem;
            color: white;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 1rem;
        }

        .brand img {
            width: 40px;
            height: auto;
        }

        .brand span {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 0.8rem 1rem;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }

        .menu-item:hover, .menu-item.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .menu-item i {
            width: 20px;
            margin-right: 1rem;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            padding-top: calc(var(--header-height) + 2rem);
        }

        .header {
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            height: var(--header-height);
            background: white;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            z-index: 100;
        }

        .admin-profile {
            position: relative;
            display: flex;
            align-items: center;
            cursor: pointer;
            padding: 10px;
            background: #fff;
            border-radius: 5px;
            transition: 0.3s;
        }

        .admin-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }

        .admin-profile span {
            font-size: 16px;
            font-weight: bold;
        }

        .admin-profile i {
            margin-left: auto;
        }

        .dropdown-menu {
            position: absolute;
            top: 50px;
            right: 0;
            background: white;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            width: 150px;
            display: none; 
            flex-direction: column;
        }

        .dropdown-menu a {
            padding: 10px;
            text-decoration: none;
            color: black;
            display: block;
            transition: 0.3s;
            font-weight: bold;
        }

        .dropdown-menu a:hover {
            background: #f0f0f0;
        }

        .card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th, .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .table th {
            font-weight: 500;
            color: #666;
        }

        .status {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
        }

        .status.active {
            background: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
        }

        .status.inactive {
            background: rgba(231, 76, 60, 0.1);
            color: var(--accent-color);
        }

        .toggle-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            background-color: var(--secondary-color);
            color: white;
        }

        .toggle-btn:hover {
            background-color: #2980b9;
        }

        .toggle-btn.active {
            background-color: var(--success-color);
        }

        .toggle-btn.inactive {
            background-color: var(--accent-color);
        }

        .review-text {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Star rating display */
        .star-rating {
            color: #f1c40f;
            font-size: 1.2rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
            border: 1px solid var(--success-color);
        }

        /* Filter controls */
        .filter-controls {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .filter-select {
            padding: 0.5rem;
            border-radius: 4px;
            border: 1px solid #ddd;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.7);
        }

        .modal-content {
            position: relative;
            background-color: #fefefe;
            margin: 10% auto;
            padding: 25px;
            border-radius: 10px;
            width: 70%;
            max-width: 800px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            animation: modalopen 0.3s;
        }

        @keyframes modalopen {
            from {opacity: 0}
            to {opacity: 1}
        }

        .close-modal {
            position: absolute;
            right: 20px;
            top: 15px;
            font-size: 28px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
        }

        .close-modal:hover {
            color: #555;
        }

        .modal-header {
            padding-bottom: 15px;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }

        .modal-body {
            max-height: 60vh;
            overflow: auto;
        }

        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .header {
                left: 0;
            }
        }
    </style>
</head>
<body>
    <nav class="sidebar">
        <div class="brand">
            <img src="images/logowithoutname.png" alt="LensPro Logo">
            <span>LensPro</span>
        </div>
        <a href="adminpanel.php" class="menu-item">
            <i class="fas fa-th-large"></i>
            Dashboard
        </a>
        <a href="adminviewusers.php" class="menu-item">
            <i class="fas fa-user"></i>
            Users
        </a>
        <a href="adminviewphotographer.php" class="menu-item">
            <i class="fas fa-camera"></i>
            Photographers
        </a>
        <a href="adminviewbooking.php" class="menu-item">
            <i class="fas fa-calendar-check"></i>
            Bookings
        </a>
        <a href="categories.php" class="menu-item">
            <i class="fas fa-list"></i> 
            Categories
        </a>
        <a href="adminviewreview.php" class="menu-item active">
            <i class="fas fa-star"></i>
            Reviews
        </a>
    </nav>

    <header class="header">
        <div class="search-bar">
            <!-- Search functionality can be added here if needed -->
        </div>
        <div class="admin-profile" onclick="toggleDropdown()">
            <img src="images/adminimg.jpg" alt="Admin">
            <span>Admin</span>
            <i class="fas fa-chevron-down"></i>
            <div class="dropdown-menu" id="dropdownMenu">
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </header>

    <main class="main-content">
        <?php if (isset($_GET['status_updated']) && $_GET['status_updated'] == 1): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> Review status updated successfully!
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">All Reviews</h2>
                <div class="filter-controls">
                    <select id="statusFilter" class="filter-select">
                        <option value="all">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <select id="ratingFilter" class="filter-select">
                        <option value="all">All Ratings</option>
                        <option value="5">5 Stars</option>
                        <option value="4">4 Stars</option>
                        <option value="3">3 Stars</option>
                        <option value="2">2 Stars</option>
                        <option value="1">1 Star</option>
                    </select>
                </div>
            </div>
            <table class="table" id="reviewsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Client</th>
                        <th>Photographer</th>
                        <th>Rating</th>
                        <th>Review</th>
                        <th>Session Type</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $reviews = getAllReviews($conn);
                    if ($reviews && $reviews->num_rows > 0) {
                        while($row = $reviews->fetch_assoc()) {
                            $statusClass = $row['status'] ? 'active' : 'inactive';
                            $statusText = $row['status'] ? 'Active' : 'Inactive';
                            $buttonClass = $row['status'] ? 'active' : 'inactive';
                            $buttonText = $row['status'] ? 'Disable' : 'Enable';
                            
                            echo "<tr data-status='{$statusClass}' data-rating='{$row['rating']}'>";
                            echo "<td>{$row['review_id']}</td>";
                            echo "<td>{$row['user_name']}</td>";
                            echo "<td>{$row['photographer_name']}</td>";
                            echo "<td class='star-rating'>" . str_repeat("★", $row['rating']) . str_repeat("☆", 5 - $row['rating']) . "</td>";
                            echo "<td class='review-text'>{$row['review_text']}</td>";
                            echo "<td>{$row['session_type']}</td>";
                            echo "<td>" . date('M d, Y', strtotime($row['created_at'])) . "</td>";
                            echo "<td><span class='status {$statusClass}'>{$statusText}</span></td>";
                            echo "<td>
                                    <form method='post' style='display:inline;'>
                                        <input type='hidden' name='review_id' value='{$row['review_id']}'>
                                        <input type='hidden' name='toggle_status' value='1'>
                                        <button type='submit' class='toggle-btn {$buttonClass}'>{$buttonText}</button>
                                    </form>
                                    <button onclick=\"viewFullReview('{$row['review_id']}', '{$row['user_name']}', '{$row['photographer_name']}', '{$row['rating']}', `{$row['review_text']}`, '{$row['created_at']}')\" class='toggle-btn'>View</button>
                                </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='9' style='text-align:center;'>No reviews found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Modal for viewing full review -->
    <div id="reviewModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <div class="modal-header">
                <h2>Review Details</h2>
            </div>
            <div class="modal-body" id="reviewDetails">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>

    <script>
        function toggleDropdown() {
            var menu = document.getElementById("dropdownMenu");
            menu.style.display = menu.style.display === "block" ? "none" : "block";
        }

        // Close dropdown when clicking outside
        document.addEventListener("click", function(event) {
            var profile = document.querySelector(".admin-profile");
            var menu = document.getElementById("dropdownMenu");

            if (!profile.contains(event.target)) {
                menu.style.display = "none";
            }
        });

        // Filter functions
        document.getElementById('statusFilter').addEventListener('change', filterReviews);
        document.getElementById('ratingFilter').addEventListener('change', filterReviews);

        function filterReviews() {
            var statusFilter = document.getElementById('statusFilter').value;
            var ratingFilter = document.getElementById('ratingFilter').value;
            var rows = document.querySelectorAll('#reviewsTable tbody tr');

            rows.forEach(function(row) {
                var status = row.getAttribute('data-status');
                var rating = row.getAttribute('data-rating');
                var statusMatch = statusFilter === 'all' || status === statusFilter;
                var ratingMatch = ratingFilter === 'all' || rating === ratingFilter;

                if (statusMatch && ratingMatch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // View full review modal
        function viewFullReview(id, user, photographer, rating, reviewText, date) {
            var modal = document.getElementById("reviewModal");
            var contentArea = document.getElementById("reviewDetails");
            
            var formattedDate = new Date(date).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            var stars = '';
            for (var i = 0; i < rating; i++) {
                stars += '<span style="color: #f1c40f;">★</span>';
            }
            for (var i = rating; i < 5; i++) {
                stars += '<span style="color: #f1c40f;">☆</span>';
            }
            
            contentArea.innerHTML = `
                <div style="margin-bottom: 20px;">
                    <strong>Review ID:</strong> ${id}
                </div>
                <div style="margin-bottom: 20px;">
                    <strong>Client:</strong> ${user}
                </div>
                <div style="margin-bottom: 20px;">
                    <strong>Photographer:</strong> ${photographer}
                </div>
                <div style="margin-bottom: 20px;">
                    <strong>Rating:</strong> <span style="font-size: 1.2rem;">${stars}</span> (${rating}/5)
                </div>
                <div style="margin-bottom: 20px;">
                    <strong>Review Date:</strong> ${formattedDate}
                </div>
                <div style="margin-bottom: 20px;">
                    <strong>Review:</strong>
                    <p style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin-top: 10px;">${reviewText}</p>
                </div>
            `;
            
            modal.style.display = "block";
        }

        function closeModal() {
            var modal = document.getElementById("reviewModal");
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            var modal = document.getElementById("reviewModal");
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>