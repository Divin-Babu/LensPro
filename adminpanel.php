<?php
session_start();
include 'dbconnect.php';

if ($_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Functions to fetch actual data from database
function getPendingPhotographers($conn) {
    $sql = "SELECT u.*, p.bio, p.location, p.category, p.approval_status, p.id_proof 
            FROM tbl_user u 
            JOIN tbl_photographer p ON u.user_id = p.photographer_id 
            WHERE u.role = 'photographer' AND p.approval_status = 'pending'
            ORDER BY u.user_id DESC";
    
    $result = $conn->query($sql);
    return $result;
}

function getTotalPhotographers($conn) {
    $sql = "SELECT COUNT(*) as total FROM tbl_user WHERE role = 'photographer'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return $row['total'];
}

function getActiveBookings($conn) {
    $sql = "SELECT COUNT(*) as active FROM tbl_booking WHERE status = 'confirmed'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return $row['active'];
}

function getTotalRevenue($conn) {
    $sql = "SELECT SUM(total_amt) as revenue FROM tbl_booking WHERE status IN ('completed', 'confirmed')";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return $row['revenue'] ?: 0;
}

function getAverageRating($conn) {
    $sql = "SELECT AVG(rating) as avg_rating FROM tbl_reviews";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return number_format($row['avg_rating'] ?: 0, 1);
}

function getRecentBookings($conn, $limit = 5) {
    $sql = "SELECT b.booking_id, u.name as client_name, p.name as photographer_name, 
            b.event_date, b.total_amt, b.status
            FROM tbl_booking b
            JOIN tbl_user u ON b.user_id = u.user_id
            JOIN tbl_user p ON b.photographer_id = p.user_id
            ORDER BY b.booking_date DESC
            LIMIT $limit";
    
    $result = $conn->query($sql);
    return $result;
}

function getTopPhotographers($conn, $limit = 5) {
    $sql = "SELECT u.user_id, u.name, u.profile_pic, c.category_name,
            AVG(r.rating) as avg_rating
            FROM tbl_user u
            JOIN tbl_photographer p ON u.user_id = p.photographer_id
            LEFT JOIN tbl_reviews r ON u.user_id = r.photographer_id
            LEFT JOIN tbl_categories c ON p.category LIKE CONCAT('%', c.category_id, '%')
            WHERE u.role = 'photographer'
            GROUP BY u.user_id
            ORDER BY avg_rating DESC
            LIMIT $limit";
    
    $result = $conn->query($sql);
    return $result;
}

// Fetch actual data
$totalPhotographers = getTotalPhotographers($conn);
$activeBookings = getActiveBookings($conn);
$totalRevenue = getTotalRevenue($conn);
$averageRating = getAverageRating($conn);

$recentBookings = getRecentBookings($conn);
$topPhotographers = getTopPhotographers($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LensPro - Admin Dashboard</title>
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
            background-color: rgba(0,0,0,0.7);
        }

        .modal-content {
        position: relative;
        background-color: #fefefe;
        margin: 2% auto; 
        padding: 25px;
        border-radius: 10px;
        width: 85%; 
        max-width: 1000px; 
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
    max-height: 80vh; 
    overflow: auto;
}

        .view-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            margin-right: 0.5rem;
            background-color: var(--secondary-color);
            color: white;
        }

        .view-btn:hover {
            background-color: #2980b9;
        }

        .modal-body img {
    max-width: 100%;
    max-height: 75vh; /* Added to ensure images aren't too tall */
    height: auto;
    display: block;
    margin: 0 auto;
}

.modal-body object {
    width: 100%;
    height: 75vh; /* Increased from 500px fixed height */
    display: block;
    margin: 0 auto;
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .stat-card h3 {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .stat-card .value {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .stat-card .trend {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }

        .trend.up {
            color: var(--success-color);
        }

        .trend.down {
            color: var(--accent-color);
        }

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
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

        .status.active, .status.confirmed, .status.completed {
            background: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
        }

        .status.pending {
            background: rgba(241, 196, 15, 0.1);
            color: var(--warning-color);
        }

        .status.cancelled, .status.rejected {
            background: rgba(231, 76, 60, 0.1);
            color: var(--accent-color);
        }

        .photographer-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .photographer-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.5rem 0;
        }

        .photographer-item img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .photographer-info {
            flex: 1;
        }

        .photographer-info h4 {
            font-size: 0.9rem;
            margin-bottom: 0.2rem;
        }

        .photographer-info p {
            font-size: 0.8rem;
            color: #666;
        }

        .approve-btn, .reject-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            margin-right: 0.5rem;
        }
        .approve-btn {
            background-color: var(--success-color);
            color: white;
        }
        .stat-card h3 i {
            margin-right: 10px;
            color: var(--secondary-color);
        }

        .reject-btn {
            background-color: var(--accent-color);
            color: white;
        }

        .approve-btn:hover {
            background-color: #27ae60;
        }

        .reject-btn:hover {
            background-color: #c0392b;
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

        .view-all {
            color: var(--secondary-color);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .rating {
            font-weight: 600;
            color: var(--warning-color);
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
        <a href="adminpanel.php" class="menu-item active">
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
        <a href="adminviewreview.php" class="menu-item">
            <i class="fas fa-star"></i>
            Reviews
        </a>
    </nav>
 
    <header class="header">
        <div class="search-bar">
            <!-- Future implementation for search functionality -->
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
                <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><i class="fas fa-user-tie"></i> Total Photographers</h3>
                <div class="value"><?php echo $totalPhotographers; ?></div>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-calendar-alt"></i> Active Bookings</h3>
                <div class="value"><?php echo $activeBookings; ?></div>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-dollar-sign"></i> Total Payments</h3>
                <div class="value">₹<?php echo number_format($totalRevenue, 2); ?></div>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-star"></i> Average Rating</h3>
                <div class="value"><?php echo $averageRating; ?></div>
            </div>
        </div>
        <!-- Pending Photographer Approvals -->
        <?php
        $pending_photographers = getPendingPhotographers($conn);
        if ($pending_photographers->num_rows > 0) { ?>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Pending Photographer Approvals</h2>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Location</th>
                            <th>Categories</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $pending_photographers->fetch_assoc()) { ?>
                            <tr>
                                <td><?php echo $row['name']; ?></td>
                                <td><?php echo $row['email']; ?></td>
                                <td><?php echo $row['phno']; ?></td>
                                <td><?php echo $row['location']; ?></td>
                                <td><?php echo $row['category']; ?></td>
                                <td>
                                    <button onclick="viewIdProof('<?php echo $row['id_proof']; ?>')" class="view-btn">View ID</button>
                                    <button onclick="approvePhotographer(<?php echo $row['user_id']; ?>)" class="approve-btn">Approve</button>
                                    <button onclick="rejectPhotographer(<?php echo $row['user_id']; ?>)" class="reject-btn">Reject</button>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } ?>

        <div class="content-grid">
            <!-- Recent Bookings -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Recent Bookings</h2>
                    <a href="adminviewbooking.php" class="view-all">View All</a>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Photographer</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($booking = $recentBookings->fetch_assoc()) { ?>
                            <tr>
                                <td><?php echo $booking['client_name']; ?></td>
                                <td><?php echo $booking['photographer_name']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($booking['event_date'])); ?></td>
                                <td>₹<?php echo number_format($booking['total_amt'], 2); ?></td>
                                <td><span class="status <?php echo strtolower($booking['status']); ?>"><?php echo ucfirst($booking['status']); ?></span></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <!-- Top Photographers -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Top Photographers</h2>
                    <a href="adminviewphotographer.php" class="view-all">View All</a>
                </div>
                <div class="photographer-list">
                    <?php while($photographer = $topPhotographers->fetch_assoc()) { ?>
                        <div class="photographer-item">
                            <?php if ($photographer['profile_pic']) { ?>
                                <img src="<?php echo $photographer['profile_pic']; ?>" alt="<?php echo $photographer['name']; ?>">
                            <?php } else { ?>
                                <img src="images/default-photographer.jpg" alt="Default Profile">
                            <?php } ?>
                            <div class="photographer-info">
                                <h4><?php echo $photographer['name']; ?></h4>
                            </div>
                            <div class="rating"><?php echo number_format($photographer['avg_rating'], 1); ?> ★</div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>

        <!-- Modal for viewing ID proof -->
        <div id="idProofModal" class="modal">
            <div class="modal-content">
                <span class="close-modal" onclick="closeModal()">&times;</span>
                <div class="modal-header">
                    <h2>Photographer ID Proof</h2>
                </div>
                <div class="modal-body" id="idProofContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </main>

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

        function approvePhotographer(photographerId) {
            updateStatus(photographerId, 'approved');
        }

        function rejectPhotographer(photographerId) {
            if(confirm('Are you sure you want to reject this photographer?')) {
                updateStatus(photographerId, 'rejected');
            }
        }

        function updateStatus(id, status) {
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "update_photographer_status.php", true);
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        if (xhr.responseText.trim() === "success") {
                            if(status=='approved'){
                                alert("Approved photographer successfully!");
                                location.reload();
                            }
                            else{
                                alert("Rejected photographer successfully!");
                                location.reload();
                            }
                        } else {
                            alert("Error: " + xhr.responseText);
                        }
                    } else {
                        alert("Server error. Please try again.");
                    }
                }
            };

            xhr.send("photographer_id=" + id + "&status=" + status);
        }

        function viewIdProof(idProofPath) {
            var modal = document.getElementById("idProofModal");
            var contentArea = document.getElementById("idProofContent");
            
            if (!idProofPath) {
                contentArea.innerHTML = "<p>No ID proof available</p>";
                modal.style.display = "block";
                return;
            }
            
            // Determine the file type based on extension
            var fileExtension = idProofPath.split('.').pop().toLowerCase();
            
            if (fileExtension === 'pdf') {
                contentArea.innerHTML = `<object data="${idProofPath}" type="application/pdf" width="100%" height="500px">
                    <p>Your browser doesn't support PDFs. 
                    <a href="${idProofPath}" target="_blank">Download Instead</a></p>
                </object>`;
            } else if (['jpg', 'jpeg', 'png'].includes(fileExtension)) {
                contentArea.innerHTML = `<img src="${idProofPath}" alt="Photographer ID Proof">`;
            } else {
                contentArea.innerHTML = `<p>Unsupported file type. <a href="${idProofPath}" target="_blank">Download Instead</a></p>`;
            }
            
            modal.style.display = "block";
        }

        function closeModal() {
            var modal = document.getElementById("idProofModal");
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            var modal = document.getElementById("idProofModal");
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>