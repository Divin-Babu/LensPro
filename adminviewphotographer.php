<?php
session_start();
include 'dbconnect.php';

if ($_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

function getAllPhotographers($conn) {
    $sql = "SELECT u.*, p.bio, p.location, p.category, p.approval_status, p.id_proof 
            FROM tbl_user u 
            JOIN tbl_photographer p ON u.user_id = p.photographer_id 
            WHERE u.role = 'photographer' AND p.approval_status = 'approved' AND u.status = '1'
            ORDER BY u.name ASC";
    
    $result = $conn->query($sql);
    return $result;
}

function getPhotographerById($conn, $id) {
    $sql = "SELECT u.*, p.bio, p.location, p.category, p.approval_status, p.id_proof 
            FROM tbl_user u 
            JOIN tbl_photographer p ON u.user_id = p.photographer_id 
            WHERE u.user_id = ? AND u.role = 'photographer'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getPhotographerGallery($conn, $id) {
    $sql = "SELECT * FROM tbl_gallery WHERE photographer_id = ? ORDER BY uploaded_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result;
}

function removePhotographer($conn, $id) {
    // Set status to '0' (inactive) in tbl_user
    $sql = "UPDATE tbl_user SET status = '0' WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $result = $stmt->execute();
    
    
    if ($result) {
        $sql2 = "UPDATE tbl_photographer SET approval_status = 'removed' WHERE photographer_id = ?";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param("i", $id);
        return $stmt2->execute();
    }
    
    return $result;
}

$photographer = null;
$gallery = null;
$message = '';

if (isset($_POST['remove_photographer']) && isset($_POST['photographer_id'])) {
    $photographer_id = $_POST['photographer_id'];
    if (removePhotographer($conn, $photographer_id)) {
        $_SESSION['success_message'] = "Photographer has been successfully removed.";
        header("Location: adminviewphotographer.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Error removing photographer.";
        header("Location: adminviewphotographer.php");
        exit();
    }
}

if (isset($_GET['id'])) {
    $photographer = getPhotographerById($conn, $_GET['id']);
    $gallery = getPhotographerGallery($conn, $_GET['id']);
}

if (isset($_GET['removed']) && $_GET['removed'] === 'true') {
    $message = "Photographer has been successfully removed.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LensPro - Photographers</title>
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

        .search-bar {
            display: flex;
            align-items: center;
            background: #f5f6fa;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            width: 300px;
        }

        .search-bar input {
            border: none;
            background: none;
            outline: none;
            padding: 0.2rem;
            width: 100%;
            margin-left: 0.5rem;
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

        .back-button {
            background: var(--secondary-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
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

        .view-btn {
            padding: 0.5rem 1rem;
            background-color: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
        }

        .view-btn:hover {
            background-color: #2980b9;
        }

        .photographer-profile {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .profile-image {
            width: 200px;
            height: 200px;
            border-radius: 10px;
            object-fit: cover;
        }

        .profile-info h2 {
            margin-bottom: 1rem;
            color: var(--primary-color);
        }

        .info-item {
            margin-bottom: 0.5rem;
            display: flex;
        }

        .info-label {
            font-weight: 500;
            width: 120px;
            color: #666;
        }
        .remove-btn {
            padding: 0.5rem 1rem;
            background-color: var(--accent-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            margin-left: 0.5rem;
        }

        .remove-btn:hover {
            background-color: #c0392b;
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
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 400px;
            max-width: 90%;
        }

        .modal-content h3 {
            margin-bottom: 1rem;
            color: var(--primary-color);
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
        }

        .cancel-btn {
            padding: 0.5rem 1rem;
            background-color: #e0e0e0;
            color: #333;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .confirm-btn {
            padding: 0.5rem 1rem;
            background-color: var(--accent-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .info-value {
            flex: 1;
        }

        .bio {
            margin-top: 1.5rem;
            line-height: 1.6;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .gallery-item {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .gallery-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .gallery-item:hover img {
            transform: scale(1.05);
        }

        .gallery-info {
            padding: 1rem;
            background: white;
        }

        .gallery-info h3 {
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }

        .gallery-info p {
            font-size: 0.9rem;
            color: #666;
        }
        .alert {
        padding: 12px 15px;
        margin-bottom: 20px;
        border-radius: 8px;
        font-weight: 500;
    }

    .alert.success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert.error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #ddd;
        }

        @media (max-width: 1024px) {
            .photographer-profile {
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
        <a href="adminviewphotographer.php" class="menu-item active">
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
            <i class="fas fa-search"></i>
            <input type="text" id="photographerSearch" placeholder="Search photographers...">
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
        <?php if($photographer): ?>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Photographer Details</h2>
                    <a href="adminviewphotographer.php" class="back-button">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
                <div class="photographer-profile">
                    <img src="<?php echo !empty($photographer['profile_pic']) ? $photographer['profile_pic'] : 'images/default-profile.jpg'; ?>" alt="<?php echo $photographer['name']; ?>" class="profile-image">
                    <div class="profile-info">
                        <h2><?php echo $photographer['name']; ?></h2>
                        
                        <div class="info-item">
                            <div class="info-label">Email:</div>
                            <div class="info-value"><?php echo $photographer['email']; ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Phone:</div>
                            <div class="info-value"><?php echo $photographer['phno']; ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Location:</div>
                            <div class="info-value"><?php echo $photographer['location']; ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Categories:</div>
                            <div class="info-value"><?php echo $photographer['category']; ?></div>
                        </div>
                        
                        <!-- <div class="info-item">
                            <div class="info-label">Status:</div>
                            <div class="info-value">
                                <span class="status <?php echo $photographer['approval_status']; ?>">
                                    <?php echo ucfirst($photographer['approval_status']); ?>
                                </span>
                            </div>
                        </div> -->
                        
                        <div class="bio">
                            <h3>About</h3>
                            <p><?php echo $photographer['bio']; ?></p>
                            <div>
                            <img src="<?php echo !empty($photographer['id_proof']) ? $photographer['id_proof'] : 'images/default-profile.jpg'; ?>" alt="<?php echo $photographer['name']; ?>" class="id-proof-image">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Photographer Gallery -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Photo Gallery</h2>
                </div>
                
                <?php if($gallery && $gallery->num_rows > 0): ?>
                    <div class="gallery-grid">
                        <?php while($photo = $gallery->fetch_assoc()): ?>
                            <div class="gallery-item">
                                <img src="<?php echo $photo['image_url']; ?>" alt="<?php echo $photo['title']; ?>">
                                <div class="gallery-info">
                                    <h3><?php echo $photo['title']; ?></h3>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-images"></i>
                        <p>No photos in this photographer's gallery yet.</p>
                    </div>
                <?php endif; ?>
            </div>
            
        <?php else: ?>
            <!-- Photographers List View -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">All Photographers</h2>
                </div>
                <table class="table" id="photographersTable">
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
                        <?php
                        $photographers = getAllPhotographers($conn);
                        if($photographers->num_rows > 0) {
                            while($row = $photographers->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>{$row['name']}</td>";
                                echo "<td>{$row['email']}</td>";
                                echo "<td>{$row['phno']}</td>";
                                echo "<td>{$row['location']}</td>";
                                echo "<td>{$row['category']}</td>";
                                echo "<td>
                                        <a href='adminviewphotographer.php?id={$row['user_id']}' class='view-btn'>View Details</a>
                                        <button class='remove-btn' onclick='showRemoveModal({$row['user_id']}, \"{$row['name']}\")'>Remove</button>
                                    </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' class='empty-state'><i class='fas fa-user-slash'></i><p>No photographers found.</p></td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
    <div id="removeModal" class="modal">
    <div class="modal-content">
        <h3>Remove Photographer</h3>
        <p>Are you sure you want to remove <span id="photographerName"></span>?</p>
        <form method="POST" action="">
            <input type="hidden" name="photographer_id" id="photographer_id" value="">
            <div class="modal-actions">
                <button type="button" class="cancel-btn" onclick="closeModal()">Cancel</button>
                <button type="submit" name="remove_photographer" class="confirm-btn">Remove</button>
            </div>
        </form>
    </div>
</div>
    <script>
        function toggleDropdown() {
            var menu = document.getElementById("dropdownMenu");
            menu.style.display = menu.style.display === "block" ? "none" : "block";
        }

        
        document.addEventListener("click", function(event) {
            var profile = document.querySelector(".admin-profile");
            var menu = document.getElementById("dropdownMenu");

            if (!profile.contains(event.target)) {
                menu.style.display = "none";
            }
        });
        // Modal functions
        function showRemoveModal(photographerId, photographerName) {
            document.getElementById('photographer_id').value = photographerId;
            document.getElementById('photographerName').textContent = photographerName;
            document.getElementById('removeModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('removeModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById('removeModal');
            if (event.target == modal) {
                closeModal();
            }
        };
        // Search functionality
        document.getElementById('photographerSearch').addEventListener('keyup', function() {
            var input, filter, table, tr, td, i, txtValue;
            input = document.getElementById('photographerSearch');
            filter = input.value.toUpperCase();
            table = document.getElementById('photographersTable');
            tr = table.getElementsByTagName('tr');

            for (i = 1; i < tr.length; i++) {
                let found = false;
                // Loop through all table cells in the row
                for (let j = 0; j < 5; j++) { 
                    td = tr[i].getElementsByTagName('td')[j];
                    if (td) {
                        txtValue = td.textContent || td.innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                if (found) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        });
    </script>
</body>
</html>