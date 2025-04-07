<?php
session_start();
include 'dbconnect.php';

if ($_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Function to get all users
function getAllUsers($conn, $search = '', $statusFilter = 'all') {
    $sql = "SELECT * FROM tbl_user WHERE role='user'";
    
    // Add search condition if provided
    if (!empty($search)) {
        $search = $conn->real_escape_string($search);
        $sql .= " AND (name LIKE '%$search%' OR email LIKE '%$search%' OR phno LIKE '%$search%')";
    }
    
    // Add status filter if not 'all'
    if ($statusFilter !== 'all') {
        $statusFilter = $conn->real_escape_string($statusFilter);
        $sql .= " AND status = " . ($statusFilter === 'active' ? '1' : '0');
    }
    
    $sql .= " ORDER BY user_id DESC";
    
    $result = $conn->query($sql);
    return $result;
}

// Handle status update via AJAX
if (isset($_POST['update_status']) && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    
    // First, get the current status
    $checkSql = "SELECT status FROM tbl_user WHERE user_id = $user_id";
    $checkResult = $conn->query($checkSql);
    
    if ($checkResult && $row = $checkResult->fetch_assoc()) {
        // Toggle the status (0 to 1 or 1 to 0)
        $newStatus = $row['status'] ? 0 : 1;
        
        $updateSql = "UPDATE tbl_user SET status = $newStatus WHERE user_id = $user_id";
        
        if ($conn->query($updateSql)) {
            echo json_encode(['success' => true, 'newStatus' => $newStatus]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'User not found']);
    }
    exit();
}

// Get search and filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';

$users = getAllUsers($conn, $search, $statusFilter);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LensPro - Users</title>
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
            --danger-color: #e74c3c;
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
            z-index: 1000;
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

        .search-filter-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .search-box {
            flex: 1;
            display: flex;
            align-items: center;
            background: #f5f6fa;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            min-width: 300px;
            max-width: 500px;
        }

        .search-box input {
            border: none;
            background: none;
            outline: none;
            padding: 0.5rem;
            width: 100%;
            margin-left: 0.5rem;
            font-size: 0.9rem;
        }

        .search-box button {
            background: var(--secondary-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 0.5rem;
        }

        .filter-box {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .filter-box select {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            outline: none;
            font-size: 0.9rem;
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

        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            display: inline-block;
        }

        .status-badge.active {
            background: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
        }

        .status-badge.inactive {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            border: none;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-activate {
            background-color: var(--success-color);
            color: white;
        }

        .btn-deactivate {
            background-color: var(--danger-color);
            color: white;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 2rem;
            gap: 0.5rem;
        }

        .pagination-button {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .pagination-button:hover, .pagination-button.active {
            background: var(--secondary-color);
            color: white;
            border-color: var(--secondary-color);
        }

        @media (max-width: 1024px) {
            .search-filter-container {
                flex-direction: column;
            }
            .search-box, .filter-box {
                width: 100%;
                max-width: 100%;
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
        <a href="adminviewusers.php" class="menu-item active">
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
        <a href="adminviewreview.php " class="menu-item">
            <i class="fas fa-star"></i>
            Reviews
        </a>
    </nav>

    <header class="header">
        <div class="search-bar">
            <!-- Header search placeholder -->
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
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Users</h2>
            </div>

            <div class="search-filter-container">
                <form class="search-box" method="GET" action="">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Search by name, email or phone..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit">Search</button>
                </form>

                <div class="filter-box">
                    <span>Filter by Status:</span>
                    <select name="status" onchange="this.form.submit()" form="filter-form">
                        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Users</option>
                        <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                    <form id="filter-form" method="GET" action="">
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    </form>
                </div>
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($users && $users->num_rows > 0) {
                        while($user = $users->fetch_assoc()) {
                            $isActive = $user['status'] == 1;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phno']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $isActive ? 'active' : 'inactive'; ?>">
                                        <?php echo $isActive ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <button 
                                        class="btn <?php echo $isActive ? 'btn-deactivate' : 'btn-activate'; ?>"
                                        onclick="updateUserStatus(<?php echo $user['user_id']; ?>)"
                                        data-user-id="<?php echo $user['user_id']; ?>"
                                    >
                                        <?php echo $isActive ? 'Deactivate' : 'Activate'; ?>
                                    </button>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">No users found</td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>

            <!-- Pagination can be added here if needed -->
            <!-- <div class="pagination">
                <button class="pagination-button active">1</button>
                <button class="pagination-button">2</button>
                <button class="pagination-button">3</button>
                <button class="pagination-button">Next</button>
            </div> -->
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

        function updateUserStatus(userId) {
            // Send AJAX request to update user status
            $.ajax({
                url: "adminviewusers.php",
                type: "POST",
                data: {
                    update_status: 1,
                    user_id: userId
                },
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        // Get the button and status badge elements
                        var button = $('button[data-user-id="' + userId + '"]');
                        var statusBadge = button.closest('tr').find('.status-badge');
                        
                        // Update the button text and class based on the new status
                        if (response.newStatus == 1) {
                            button.text('Deactivate');
                            button.removeClass('btn-activate').addClass('btn-deactivate');
                            statusBadge.text('Active');
                            statusBadge.removeClass('inactive').addClass('active');
                        } else {
                            button.text('Activate');
                            button.removeClass('btn-deactivate').addClass('btn-activate');
                            statusBadge.text('Inactive');
                            statusBadge.removeClass('active').addClass('inactive');
                        }
                    } else {
                        alert("Error updating status: " + response.error);
                    }
                },
                error: function() {
                    alert("Server error. Please try again.");
                }
            });
        }
    </script>
</body>
</html>