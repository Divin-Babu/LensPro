<?php
session_start();
include 'dbconnect.php';

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    header('Location: login.php');
    exit();
}

// Handle booking cancellation
if (isset($_POST['cancel_booking']) && isset($_POST['booking_id'])) {
    $booking_id = $_POST['booking_id'];
    $update_sql = "UPDATE tbl_booking SET status = 'cancelled' WHERE booking_id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt, "ii", $booking_id, $_SESSION['userid']);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['message'] = "Booking successfully cancelled.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error cancelling booking. Please try again.";
        $_SESSION['message_type'] = "error";
    }
    mysqli_stmt_close($stmt);
    header('Location: my-booking.php');
    exit();
}

// Initialize search and sort variables
$search_query = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$sort_by = isset($_GET['sort']) ? mysqli_real_escape_string($conn, $_GET['sort']) : 'booking_date';
$sort_order = isset($_GET['order']) ? mysqli_real_escape_string($conn, $_GET['order']) : 'DESC';

// Validate sort options
$allowed_sort_columns = ['booking_date', 'event_date', 'status', 'session_type'];
$allowed_sort_orders = ['ASC', 'DESC'];

if (!in_array($sort_by, $allowed_sort_columns)) {
    $sort_by = 'booking_date';
}
if (!in_array($sort_order, $allowed_sort_orders)) {
    $sort_order = 'DESC';
}

// Prepare the booking query with search and sort
$booking_query = "
    SELECT 
        b.booking_id, 
        b.session_type, 
        b.status, 
        b.event_date, 
        b.location, 
        b.total_amt,
        b.booking_date,
        b.photographer_id AS photographer_id,
        p.name AS photographer_name,
        p.profile_pic AS photographer_pic
    FROM 
        tbl_booking b 
    JOIN 
        tbl_user p ON b.photographer_id = p.user_id
    WHERE 
        b.user_id = ?
    AND (
        p.name LIKE ? OR 
        b.session_type LIKE ? OR 
        b.status LIKE ? OR 
        b.location LIKE ?
    )
    ORDER BY 
        b.$sort_by $sort_order
";

$stmt = mysqli_prepare($conn, $booking_query);
$search_param = "%{$search_query}%";
mysqli_stmt_bind_param($stmt, "issss", $_SESSION['userid'], $search_param, $search_param, $search_param, $search_param);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$bookings = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Check existing reviews
$existing_reviews_query = "
    SELECT 
        r.review_id, 
        r.photographer_id,
        r.rating, 
        r.review_text, 
        r.created_at 
    FROM tbl_reviews r 
    WHERE r.user_id = ?
";
$reviews_stmt = mysqli_prepare($conn, $existing_reviews_query);
mysqli_stmt_bind_param($reviews_stmt, "i", $_SESSION['userid']);
mysqli_stmt_execute($reviews_stmt);
$reviews_result = mysqli_stmt_get_result($reviews_stmt);
$existing_reviews = [];
while ($review = mysqli_fetch_assoc($reviews_result)) {
    $existing_reviews[$review['photographer_id']] = $review;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - LensPro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            /* Color Palette */
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-gray: #f5f6fa;
            --dark-gray: #2c3e50;
            --hover-color: #2980b9;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --overlay-color: rgba(44, 62, 80, 0.7);
            --white: #ffffff;
            --text-color: #333;
        }

        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-image: url('images/mybookingbg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            position: relative;
            line-height: 1.6;
            color: var(--text-color);
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: var(--overlay-color);
            z-index: -1;
        }

        /* Container Styles */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            width: 95%;
        }

        /* Section Title */
        .section-title {
            text-align: center;
            margin-bottom: 30px;
            color: var(--white);
            font-size: 2.5rem;
            font-weight: 600;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        /* Search and Sort Container */
        .search-sort-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background-color: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        .search-form {
            display: flex;
            flex-grow: 1;
            gap: 15px;
            align-items: center;
        }

        .search-input {
            flex-grow: 1;
            padding: 12px 15px;
            border: 2px solid var(--secondary-color);
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        .sort-select {
            padding: 12px;
            border: 2px solid var(--secondary-color);
            border-radius: 8px;
            background-color: white;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .sort-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        .search-button, 
        .home-button {
            margin-right:10px;
            padding: 12px 18px;
            background-color: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 16px;
            text-decoration:none;
        }

        .search-button:hover, 
        .home-button:hover {
            background-color: var(--primary-color);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        /* Bookings Grid */
        .bookings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            padding: 20px;
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .booking-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
            padding: 25px;
            display: flex;
            flex-direction: column;
            position: relative;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .booking-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 12px 22px rgba(0, 0, 0, 0.15);
        }

        .photographer-avatar {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            object-fit: cover;
            align-self: center;
            margin-bottom: 20px;
            border: 4px solid var(--secondary-color);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .booking-details {
            text-align: center;
            flex-grow: 1;
        }

        .booking-details h3 {
            color: var(--primary-color);
            margin-bottom: 10px;
            font-size: 1.3rem;
        }

        .booking-details p {
            margin-bottom: 8px;
            color: var(--text-color);
        }

        .booking-status {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background-color: var(--warning-color);
            color: white;
        }

        .status-confirmed {
            background-color: var(--hover-color);
            color: white;
        }

        .status-completed {
            background-color: var(--success-color);
            color: white;
        }

        .status-cancelled,.status-rejected {
            background-color: var(--danger-color);
            color: white;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 15px;
        }

        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .btn-cancel {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-payment {
            background-color: var(--secondary-color);
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .no-bookings {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 80%;
            max-width: 600px;
            text-align: center;
            color: var(--white);
            background-color: var(--secondary-color);
            padding: 30px;
            border-radius: 15px;
            z-index: 10;
        }

        .no-bookings p {
            font-size: 1.5rem;
        }

        .no-bookings a {
            color: black;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s ease;
        }

        .no-bookings a:hover {
            color: var(--white);
            text-decoration: underline;
        }
        
        .btn-review {
            background-color: #27ae60;
            color: white;
        }
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
            font-size: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .message-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .search-sort-container {
                flex-direction: column;
                gap: 15px;
            }

            .search-form {
                width: 100%;
                flex-direction: column;
            }

            .search-input, 
            .sort-select, 
            .search-button {
                width: 100%;
                margin-bottom: 10px;
            }

            .bookings-grid {
                grid-template-columns: 1fr;
            }

            .section-title {
                font-size: 2rem;
            }
        }

        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 10px;
        }

        ::-webkit-scrollbar-track {
            background: var(--light-gray);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--secondary-color);
            border-radius: 5px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="search-sort-container">
            <form method="GET" class="search-form">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search bookings..." 
                    class="search-input"
                    value="<?php echo htmlspecialchars($search_query); ?>"
                >
                <select name="sort" class="sort-select">
                    <option value="booking_date" <?php echo $sort_by == 'booking_date' ? 'selected' : ''; ?>>
                        Sort by Booking Date
                    </option>
                    <option value="event_date" <?php echo $sort_by == 'event_date' ? 'selected' : ''; ?>>
                        Sort by Event Date
                    </option>
                    <option value="status" <?php echo $sort_by == 'status' ? 'selected' : ''; ?>>
                        Sort by Status
                    </option>
                    <option value="session_type" <?php echo $sort_by == 'session_type' ? 'selected' : ''; ?>>
                        Sort by Session Type
                    </option>
                </select>
                <select name="order" class="sort-select">
                    <option value="DESC" <?php echo $sort_order == 'DESC' ? 'selected' : ''; ?>>
                        Descending
                    </option>
                    <option value="ASC" <?php echo $sort_order == 'ASC' ? 'selected' : ''; ?>>
                        Ascending
                    </option>
                </select>
                <button type="submit" class="search-button">
                    <i class="fas fa-search"></i> Search
                </button>
            </form>
            
            <a href="index.php" class="home-button">
                <i class="fas fa-home"></i> Home
            </a>
        </div>

        <h1 class="section-title">My Bookings</h1>

        <?php
        if (isset($_SESSION['message'])) {
            $message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'info';
            echo "<div class='message message-{$message_type}'>{$_SESSION['message']}</div>";
            
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
        }
        ?>

            <?php if (empty($bookings)): ?>
                <div class="no-bookings">
                    <p>No bookings found. <?php echo $search_query ? "Try a different search." : "<a href='booking.php'>Book your first session!</a>"; ?></p>
                </div>
            <?php else: ?>
            <div class="bookings-grid">
                <?php foreach ($bookings as $booking): ?>
                    <div class="booking-card">
                        <?php 
                        $photographer_pic = $booking['photographer_pic'] ? 
                            htmlspecialchars($booking['photographer_pic']) : 
                            'images/default-photographer.jpg'; 
                        ?>
                        <img src="<?php echo $photographer_pic; ?>" alt="Photographer" class="photographer-avatar">
                        
                        <div class="booking-details">
                            <h3><?php echo htmlspecialchars($booking['photographer_name']); ?></h3>
                            <p><strong>Session Type:</strong> <?php echo htmlspecialchars($booking['session_type']); ?></p>
                            <p><strong>Event Date:</strong> <?php echo date('F j, Y', strtotime($booking['event_date'])); ?></p>
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($booking['location']); ?></p>
                            <p><strong>Booked on:</strong> <?php echo date('F j, Y, g:i a', strtotime($booking['booking_date'])); ?></p>
                        </div>

                        <div class="booking-status status-<?php echo strtolower($booking['status']); ?>">
                            <?php echo htmlspecialchars($booking['status']); ?>
                        </div>

                        <div class="action-buttons">
                            <?php if ($booking['status'] == 'pending'): ?>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                    <button type="submit" name="cancel_booking" class="btn btn-cancel">
                                        <i class="fas fa-times"></i> Cancel Booking
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if ($booking['status'] == 'confirmed' && $booking['total_amt'] === '0'): ?>
                                <a href="payment.php?booking_id=<?php echo $booking['booking_id']; ?>" class="btn btn-payment">
                                    <i class="fas fa-credit-card"></i> Pay Now
                                </a>
                            <?php endif; ?>

                            <?php 
                                    if ($booking['status'] == 'completed'): 
                                        $review_exists = isset($existing_reviews[$booking['photographer_id']]);
                                        $review_details = $review_exists ? $existing_reviews[$booking['photographer_id']] : null;
                                    ?>
                                        <?php if (!$review_exists): ?>
                                            <a href="write-review.php?photographer_id=<?php echo $booking['photographer_id']; ?>&booking_id=<?php echo $booking['booking_id']; ?>" class="btn btn-review">
                                                <i class="fas fa-star"></i> Write Review
                                            </a>
                                        <?php else: ?>
                                            <a href="user-view-review.php?photographer_id=<?php echo $booking['photographer_id']; ?>&booking_id=<?php echo $booking['booking_id']; ?>" class="btn btn-review">
                                                <i class="fas fa-eye"></i> View My Review
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>