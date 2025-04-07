<?php
session_start();
include 'dbconnect.php';

if ($_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

function getAllBookings($conn) {
    $sql = "SELECT b.*, 
            u1.name as user_name, u1.email as user_email, u1.phno as user_phone,
            u2.name as photographer_name, u2.email as photographer_email
            FROM tbl_booking b
            JOIN tbl_user u1 ON b.user_id = u1.user_id
            JOIN tbl_user u2 ON b.photographer_id = u2.user_id
            ORDER BY b.booking_date DESC";
    
    $result = $conn->query($sql);
    return $result;
}

function getBookingStats($conn) {
   
    $total = $conn->query("SELECT COUNT(*) as count FROM tbl_booking")->fetch_assoc()['count'];
    
    $pending = $conn->query("SELECT COUNT(*) as count FROM tbl_booking WHERE status = 'pending'")->fetch_assoc()['count'];

    $confirmed = $conn->query("SELECT COUNT(*) as count FROM tbl_booking WHERE status = 'confirmed'")->fetch_assoc()['count'];
    $completed = $conn->query("SELECT COUNT(*) as count FROM tbl_booking WHERE status = 'completed'")->fetch_assoc()['count'];
    
    
    $revenue = $conn->query("SELECT SUM(total_amt) as total FROM tbl_booking WHERE status IN ('completed', 'confirmed')")->fetch_assoc()['total'];
    
    return [
        'total' => $total,
        'pending' => $pending,
        'confirmed' => $confirmed,
        'completed' => $completed,
        'revenue' => $revenue ?? 0
    ];
}

$bookings = getAllBookings($conn);
$stats = getBookingStats($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LensPro - Booking Management</title>
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
            display: inline-block;
        }

        .status.pending {
            background: rgba(241, 196, 15, 0.1);
            color: var(--warning-color);
        }

        .status.confirmed {
            background: rgba(52, 152, 219, 0.1);
            color: var(--secondary-color);
        }

        .status.completed {
            background: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
        }

        .status.cancelled, .status.rejected {
            background: rgba(231, 76, 60, 0.1);
            color: var(--accent-color);
        }

        .action-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            margin-right: 0.5rem;
            background-color: var(--secondary-color);
            color: white;
        }

        .action-btn:hover {
            background-color: #2980b9;
        }

        .filter-container {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: white;
            cursor: pointer;
        }

        .booking-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .detail-group {
            margin-bottom: 15px;
        }

        .detail-group h4 {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 5px;
        }

        .detail-group p {
            font-size: 1rem;
            color: #333;
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

            .booking-details {
                grid-template-columns: 1fr;
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
        <a href="adminviewbooking.php" class="menu-item active">
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
            <!-- Search functionality could be added here -->
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
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Bookings</h3>
                <div class="value"><?php echo $stats['total']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Pending Bookings</h3>
                <div class="value"><?php echo $stats['pending']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Confirmed Bookings</h3>
                <div class="value"><?php echo $stats['confirmed']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Revenue</h3>
                <div class="value">₹<?php echo number_format($stats['revenue'], 2); ?></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">All Bookings</h2>
            </div>
            
            <div class="filter-container">
                <select id="statusFilter" class="filter-select">
                    <option value="all">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="rejected">Rejected</option>
                </select>
                
                <select id="dateFilter" class="filter-select">
                    <option value="all">All Dates</option>
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                </select>
            </div>
            
            <table class="table" id="bookingsTable">
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Client</th>
                        <th>Photographer</th>
                        <th>Session Type</th>
                        <th>Event Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($booking = $bookings->fetch_assoc()) { ?>
                    <tr data-status="<?php echo $booking['status']; ?>">
                        <td>#<?php echo $booking['booking_id']; ?></td>
                        <td><?php echo $booking['user_name']; ?></td>
                        <td><?php echo $booking['photographer_name']; ?></td>
                        <td><?php echo $booking['session_type']; ?></td>
                        <td><?php echo date('d M Y', strtotime($booking['event_date'])); ?></td>
                        <td>₹<?php echo number_format($booking['total_amt'], 2); ?></td>
                        <td>
                            <span class="status <?php echo $booking['status']; ?>">
                                <?php echo ucfirst($booking['status']); ?>
                            </span>
                        </td>
                        <td>
                            <button class="action-btn" onclick="viewBookingDetails(<?php echo $booking['booking_id']; ?>)">View Details</button>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Modal for viewing booking details -->
    <div id="bookingDetailsModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <div class="modal-header">
                <h2>Booking Details</h2>
            </div>
            <div class="modal-body" id="bookingDetailsContent">
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

        function viewBookingDetails(bookingId) {
            var modal = document.getElementById("bookingDetailsModal");
            var contentArea = document.getElementById("bookingDetailsContent");
            
            // AJAX request to get booking details
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "get_booking_details.php?booking_id=" + bookingId, true);
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        contentArea.innerHTML = xhr.responseText;
                    } else {
                        contentArea.innerHTML = "<p>Error loading booking details.</p>";
                    }
                }
            };
            
            xhr.send();
            modal.style.display = "block";
        }

        function closeModal() {
            var modal = document.getElementById("bookingDetailsModal");
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            var modal = document.getElementById("bookingDetailsModal");
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        // Status filter functionality
        document.getElementById('statusFilter').addEventListener('change', function() {
            filterBookings();
        });

        // Date filter functionality
        document.getElementById('dateFilter').addEventListener('change', function() {
            filterBookings();
        });

        function filterBookings() {
            var statusFilter = document.getElementById('statusFilter').value;
            var dateFilter = document.getElementById('dateFilter').value;
            
            var rows = document.getElementById('bookingsTable').getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            for (var i = 0; i < rows.length; i++) {
                var row = rows[i];
                var status = row.getAttribute('data-status');
                
                var showRow = true;
                
                // Apply status filter
                if (statusFilter !== 'all' && status !== statusFilter) {
                    showRow = false;
                }
                
                // Date filtering would require additional implementation with date attributes on rows
                
                row.style.display = showRow ? '' : 'none';
            }
        }
    </script>
</body>
</html>