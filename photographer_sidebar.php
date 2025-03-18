<?php
// Determine current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">
    <div class="logo-container">
        <div class="logo">
            <img src="images/logowithoutname.png" alt="LensPro Logo">
            <span>LensPro</span>
        </div>
    </div>
    <ul class="nav-links">
        <li>
            <a href="photographerdash.php" class="<?php echo $current_page == 'photographerdash.php' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="#" class="<?php echo $current_page == 'bookings.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt"></i>
                <span>Bookings</span>
            </a>
        </li>
        <li>
            <a href="gallery.php" class="<?php echo $current_page == 'gallery.php' ? 'active' : ''; ?>">
                <i class="fas fa-images"></i>
                <span>Gallery</span>
            </a>
        </li>
        <li>
            <a href="#" class="<?php echo $current_page == 'reviews.php' ? 'active' : ''; ?>">
                <i class="fas fa-star"></i>
                <span>Reviews</span>
            </a>
        </li>
        <li>
            <a href="photographerprofile.php" class="<?php echo $current_page == 'photographerprofile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
        </li>
        <li>
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</div>

<style>
.sidebar {
    width: 250px;
    background: var(--primary-color);
    color: white;
    padding: 20px;
    position: fixed;
    height: 100vh;
    transition: all 0.3s ease;
}

.logo-container {
    margin-bottom: 40px;
}

.logo {
    color: white;
    font-size: 1.8rem;
    font-weight: 600;
    letter-spacing: 2px;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 10px;
}

.logo img {
    width: 70px;
    height: auto;
}

.nav-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.nav-links li {
    margin-bottom: 10px;
}

.nav-links a {
    color: white;
    text-decoration: none;
    display: flex;
    align-items: center;
    padding: 12px;
    border-radius: 5px;
    transition: all 0.3s ease;
}

.nav-links a:hover {
    background: rgba(255, 255, 255, 0.1);
}

.nav-links a.active {
    background: var(--secondary-color);
}

.nav-links i {
    width: 20px;
    margin-right: 10px;
    text-align: center;
}

@media (max-width: 768px) {
    .sidebar {
        width: 70px;
    }

    .logo span,
    .nav-links span {
        display: none;
    }

    .logo img {
        width: 40px;
    }

    .nav-links a {
        justify-content: center;
        padding: 15px;
    }

    .nav-links i {
        margin: 0;
        font-size: 1.2rem;
    }
}
</style>