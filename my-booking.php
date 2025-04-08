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
    
    // First, check if payment exists for this booking
    $payment_check_sql = "SELECT p.payment_id, p.payment_at, b.total_amt 
                         FROM tbl_payment p
                         JOIN tbl_booking b ON p.booking_id = b.booking_id
                         WHERE p.booking_id = ? AND b.user_id = ?";
    $check_stmt = mysqli_prepare($conn, $payment_check_sql);
    mysqli_stmt_bind_param($check_stmt, "ii", $booking_id, $_SESSION['userid']);
    mysqli_stmt_execute($check_stmt);
    $payment_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($payment_result) > 0) {
        // Payment exists, calculate refund based on time elapsed
        $payment_data = mysqli_fetch_assoc($payment_result);
        $payment_date = new DateTime($payment_data['payment_at']);
        $current_date = new DateTime();
        $days_difference = $current_date->diff($payment_date)->days;
        $refund_percentage = 0;
        
        // Define refund policy based on days elapsed
        if ($days_difference == 0) {
            // Same day cancellation - 90% refund
            $refund_percentage = 0.90;
        } elseif ($days_difference == 1) {
            // Next day cancellation - 75% refund
            $refund_percentage = 0.75;
        } elseif ($days_difference <= 3) {
            // 2-3 days after payment - 50% refund
            $refund_percentage = 0.50;
        } elseif ($days_difference <= 7) {
            // 4-7 days after payment - 25% refund
            $refund_percentage = 0.25;
        } else {
            // More than a week - no refund
            $refund_percentage = 0;
        }
        
        $original_amount = $payment_data['total_amt'];
        $refund_amount = $original_amount * $refund_percentage;
        
        // Begin transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Update booking status to cancelled
            $update_booking_sql = "UPDATE tbl_booking SET status = 'cancelled' WHERE booking_id = ? AND user_id = ?";
            $update_stmt = mysqli_prepare($conn, $update_booking_sql);
            mysqli_stmt_bind_param($update_stmt, "ii", $booking_id, $_SESSION['userid']);
            mysqli_stmt_execute($update_stmt);
            
            // Create a refund record - We'll add a new table for this
            // First check if the table exists, if not create it
            $check_table_sql = "SHOW TABLES LIKE 'tbl_refunds'";
            $table_result = mysqli_query($conn, $check_table_sql);
            
            if (mysqli_num_rows($table_result) == 0) {
                // Create the refunds table if it doesn't exist
                $create_table_sql = "CREATE TABLE tbl_refunds (
                    refund_id INT AUTO_INCREMENT PRIMARY KEY,
                    payment_id INT,
                    booking_id INT,
                    original_amount DECIMAL(10,2) NOT NULL,
                    refund_amount DECIMAL(10,2) NOT NULL,
                    refund_percentage DECIMAL(5,2) NOT NULL,
                    refund_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    status ENUM('pending', 'processed', 'completed') DEFAULT 'pending',
                    FOREIGN KEY (payment_id) REFERENCES tbl_payment(payment_id),
                    FOREIGN KEY (booking_id) REFERENCES tbl_booking(booking_id)
                )";
                mysqli_query($conn, $create_table_sql);
            }
            
            // Insert the refund record
            $insert_refund_sql = "INSERT INTO tbl_refunds (payment_id, booking_id, original_amount, refund_amount, refund_percentage) 
                                 VALUES (?, ?, ?, ?, ?)";
            $refund_stmt = mysqli_prepare($conn, $insert_refund_sql);
            mysqli_stmt_bind_param($refund_stmt, "iiddd", $payment_data['payment_id'], $booking_id, $original_amount, $refund_amount, $refund_percentage);
            mysqli_stmt_execute($refund_stmt);
            
            // Commit transaction
            mysqli_commit($conn);
            
            $_SESSION['message'] = "Booking cancelled successfully. " . 
                                  ($refund_percentage > 0 ? 
                                   "You will receive the refund amount soon." : 
                                   "No refund is applicable as per the cancellation policy.");
            $_SESSION['message_type'] = "success";
            
        } catch (Exception $e) {
            // Rollback on error
            mysqli_rollback($conn);
            $_SESSION['message'] = "Error processing cancellation. Please try again.";
            $_SESSION['message_type'] = "error";
        }
    } else {
        // No payment found, simple cancellation
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
    }
    
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
        p.profile_pic AS photographer_pic,
        CASE 
            WHEN pay.payment_id IS NOT NULL THEN 1
            ELSE 0
        END AS has_payment,
        CASE 
            WHEN pay.payment_id IS NOT NULL THEN pay.payment_at
            ELSE NULL
        END AS payment_date
    FROM 
        tbl_booking b 
    JOIN 
        tbl_user p ON b.photographer_id = p.user_id
    LEFT JOIN
        tbl_payment pay ON b.booking_id = pay.booking_id
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

// Check existing reviews - Change this part to include booking_id
$existing_reviews_query = "
    SELECT 
        r.review_id, 
        r.photographer_id,
        r.booking_id,
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
    // Store reviews indexed by both photographer_id AND booking_id
    if (!isset($existing_reviews[$review['photographer_id']])) {
        $existing_reviews[$review['photographer_id']] = [];
    }
    $existing_reviews[$review['photographer_id']][$review['booking_id']] = $review;
}

// Fetch refund policy for display
$refund_policy = [
    ['days' => 0, 'percentage' => 90, 'description' => 'Same day cancellation'],
    ['days' => 1, 'percentage' => 75, 'description' => 'Next day cancellation'],
    ['days' => 3, 'percentage' => 50, 'description' => '2-3 days after payment'],
    ['days' => 7, 'percentage' => 25, 'description' => '4-7 days after payment'],
    ['days' => 999, 'percentage' => 0, 'description' => 'More than 7 days after payment']
];
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
        .btn-secondary {
                background-color: #7f8c8d;
                color: white;
            }

            .btn-secondary:hover {
                background-color: #95a5a6;
            }

            .action-buttons {
                display: flex;
                flex-wrap: wrap;
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
        
        /* Modal Styles for Refund Policy */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.7);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border-radius: 10px;
            width: 80%;
            max-width: 600px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            animation: modalFadeIn 0.3s;
        }

        @keyframes modalFadeIn {
            from {opacity: 0; transform: translateY(-50px);}
            to {opacity: 1; transform: translateY(0);}
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            transition: color 0.3s;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .policy-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .policy-table th, .policy-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
        }

        .policy-table th {
            background-color: var(--secondary-color);
            color: white;
        }

        .policy-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .info-badge {
            display: inline-block;
            background-color: var(--secondary-color);
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            text-align: center;
            line-height: 20px;
            font-size: 12px;
            margin-left: 5px;
            cursor: pointer;
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
            
            .modal-content {
                width: 95%;
                margin: 30% auto;
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
        
        /* Add badge for bookings with payment */
        .payment-badge {
            position: absolute;
            top: 20px;
            left: 20px;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.8rem;
            background-color: #6c5ce7;
            color: white;
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

        <h1 class="section-title">
            My Bookings <span class="info-badge" id="show-policy" title="Show Cancellation Policy">?</span>
        </h1>

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
                        
                        <?php if ($booking['has_payment']): ?>
                            <div class="payment-badge">
                                <i class="fas fa-check-circle"></i> Paid
                            </div>
                        <?php endif; ?>
                        
                        <div class="booking-details">
                            <h3><?php echo htmlspecialchars($booking['photographer_name']); ?></h3>
                            <p><strong>Session Type:</strong> <?php echo htmlspecialchars($booking['session_type']); ?></p>
                            <p><strong>Event Date:</strong> <?php echo date('F j, Y', strtotime($booking['event_date'])); ?></p>
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($booking['location']); ?></p>
                            <p><strong>Booked on:</strong> <?php echo date('F j, Y, g:i a', strtotime($booking['booking_date'])); ?></p>
                            <?php if ($booking['has_payment']): ?>
                                <p><strong>Amount Paid:</strong> ₹<?php echo number_format($booking['total_amt'], 2); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="booking-status status-<?php echo strtolower($booking['status']); ?>">
                            <?php echo htmlspecialchars($booking['status']); ?>
                        </div>

                        <div class="action-buttons">
                            <?php if ($booking['status'] == 'pending' || $booking['status'] == 'confirmed'): ?>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this booking? <?php echo $booking['has_payment'] ? 'A refund will be processed according to our cancellation policy.' : ''; ?>');">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                    <button type="submit" name="cancel_booking" class="btn btn-cancel">
                                        <i class="fas fa-times"></i> Cancel Booking
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if ($booking['status'] == 'confirmed' && !$booking['has_payment']): ?>
                                <a href="payment.php?booking_id=<?php echo $booking['booking_id']; ?>" class="btn btn-payment">
                                    <i class="fas fa-credit-card"></i> Pay Now
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($booking['has_payment']): ?>
                                    <?php 
                                    // Check if refund exists for this booking
                                    $refund_check_sql = "SELECT * FROM tbl_refunds WHERE booking_id = ?";
                                    $refund_stmt = mysqli_prepare($conn, $refund_check_sql);
                                    mysqli_stmt_bind_param($refund_stmt, "i", $booking['booking_id']);
                                    mysqli_stmt_execute($refund_stmt);
                                    $refund_result = mysqli_stmt_get_result($refund_stmt);
                                    $has_refund = mysqli_num_rows($refund_result) > 0;
                                    
                                    if (!$has_refund): 
                                    ?>
                                        <a href="payment-receipt.php?booking_id=<?php echo $booking['booking_id']; ?>" class="btn btn-payment" target="_blank">
                                            <i class="fas fa-file-invoice"></i> View Receipt
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>


                                <?php 
                                    if ($booking['status'] == 'completed'): 
                                        $review_exists = isset($existing_reviews[$booking['photographer_id']]) && 
                                                        isset($existing_reviews[$booking['photographer_id']][$booking['booking_id']]);
                                        if (!$review_exists):
                                ?>
                                    <a href="write-review.php?photographer_id=<?php echo $booking['photographer_id']; ?>&booking_id=<?php echo $booking['booking_id']; ?>" class="btn btn-review">
                                        <i class="fas fa-star"></i> Write a Review
                                    </a>
                                <?php else: 
                                    $review = $existing_reviews[$booking['photographer_id']][$booking['booking_id']];
                                ?>
                                    <a href="user-view-review.php?review_id=<?php echo $review['review_id']; ?>" class="btn btn-review">
                                        <i class="fas fa-eye"></i> View Review
                                    </a>
                                    <?php
                                        // Check if review is within 7 days for editing option
                                        $review_date = new DateTime($review['created_at']);
                                        $current_date = new DateTime();
                                        $days_since_review = $current_date->diff($review_date)->days;
                                        if ($days_since_review <= 7):
                                    ?>
                                    <a href="edit-review.php?review_id=<?php echo $review['review_id']; ?>" class="btn btn-secondary">
                                        <i class="fas fa-edit"></i> Edit Review
                                    </a>
                                    <?php endif; ?>
                                <?php 
                                        endif;
                                    endif; 
                                ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Refund Policy Modal -->
    <div id="refundPolicyModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Booking Cancellation Policy</h2>
            <p>Our refund policy is based on how soon you cancel after making payment:</p>
            
            <table class="policy-table">
                <thead>
                    <tr>
                        <th>Time Since Payment</th>
                        <th>Refund Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($refund_policy as $policy): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($policy['description']); ?></td>
                        <td><?php echo $policy['percentage']; ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p style="margin-top: 20px; font-style: italic;">Note: Cancellations for bookings without payment will not incur any charges.</p>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Modal functionality
        const modal = document.getElementById("refundPolicyModal");
        const btn = document.getElementById("show-policy");
        const span = document.getElementsByClassName("close")[0];

        btn.onclick = function() {
            modal.style.display = "block";
        }

        span.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>