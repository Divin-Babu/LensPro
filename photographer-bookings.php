<?php
session_start();
include 'dbconnect.php';

// Check if user is logged in and is a photographer
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'photographer') {
    header('Location: login.php');
    exit();
}

$photographer_id = $_SESSION['userid'];

// Fetch bookings with client details
$bookingsQuery = "SELECT b.*, u.name AS client_name, u.email AS client_email, u.phno AS client_phone 
                  FROM tbl_booking b 
                  JOIN tbl_user u ON b.user_id = u.user_id 
                  WHERE b.photographer_id = ? 
                  ORDER BY 
                    CASE b.status 
                      WHEN 'pending' THEN 1 
                      WHEN 'confirmed' THEN 2 
                      WHEN 'completed' THEN 3 
                      WHEN 'rejected' THEN 4 
                      ELSE 5 
                    END, 
                    b.event_date";
$bookingsStmt = mysqli_prepare($conn, $bookingsQuery);
mysqli_stmt_bind_param($bookingsStmt, "i", $photographer_id);
mysqli_stmt_execute($bookingsStmt);
$bookingsResult = mysqli_stmt_get_result($bookingsStmt);
$bookings = [];
while ($booking = mysqli_fetch_assoc($bookingsResult)) {
    $bookings[] = $booking;
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['booking_id']) && isset($_POST['status'])) {
    $bookingId = mysqli_real_escape_string($conn, $_POST['booking_id']);
    $newStatus = mysqli_real_escape_string($conn, $_POST['status']);
    
    $updateQuery = "UPDATE tbl_booking SET status = ? WHERE booking_id = ? AND photographer_id = ?";
    $updateStmt = mysqli_prepare($conn, $updateQuery);
    mysqli_stmt_bind_param($updateStmt, "sii", $newStatus, $bookingId, $photographer_id);
    
    if (mysqli_stmt_execute($updateStmt)) {
        $successMessage = "Booking status updated successfully!";
        // Redirect to prevent form resubmission
        header("Location: photographer-bookings.php");
        exit();
    } else {
        $errorMessage = "Failed to update booking status.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Photographer Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
            --light-gray: #f5f6fa;
            --dark-gray: #2c3e50;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f0f2f5;
            line-height: 1.6;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }

        .page-title {
            margin-bottom: 30px;
            color: var(--dark-gray);
            font-size: 24px;
            font-weight: 600;
            border-bottom: 2px solid var(--secondary-color);
            padding-bottom: 10px;
        }

        .bookings-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }

        .bookings-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .bookings-table th, 
        .bookings-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }

        .bookings-table th {
            background-color: var(--light-gray);
            color: var(--dark-gray);
            font-weight: 500;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }

        .status-pending {
            background-color: var(--warning-color);
            color: white;
        }

        .status-confirmed {
            background-color: var(--success-color);
            color: white;
        }

        .status-completed {
            background-color: var(--secondary-color);
            color: white;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .action-buttons button {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-size: 14px;
        }

        .btn-confirm {
            background-color: var(--success-color);
            color: white;
        }

        .btn-complete {
            background-color: var(--secondary-color);
            color: white;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 14px;
        }

        .alert-success {
            background-color: rgba(46, 204, 113, 0.2);
            color: var(--success-color);
            border: 1px solid var(--success-color);
        }

        .alert-error {
            background-color: rgba(231, 76, 60, 0.2);
            color: var(--accent-color);
            border: 1px solid var(--accent-color);
        }

        @media (max-width: 992px) {
            .bookings-table th, 
            .bookings-table td {
                padding: 10px;
                font-size: 14px;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 70px;
            }

            .bookings-table {
                font-size: 12px;
            }

            .action-buttons {
                flex-direction: column;
                gap: 5px;
            }

            .action-buttons button {
                width: 100%;
            }
        }
        
        .status-rejected {
            background-color: var(--accent-color);
            color: white;
        }

        .btn-reject {
            background-color: var(--accent-color);
            color: white;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'photographer_sidebar.php'; ?>   
        <div class="main-content">
            <h1 class="page-title">My Bookings</h1>

            <?php if (isset($successMessage)): ?>
                <div class="alert alert-success"><?php echo $successMessage; ?></div>
            <?php endif; ?>

            <?php if (isset($errorMessage)): ?>
                <div class="alert alert-error"><?php echo $errorMessage; ?></div>
            <?php endif; ?>

            <div class="bookings-table">
                <table>
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Event Type</th>
                            <th>Date & Time</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bookings)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">No bookings found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($booking['client_name']); ?>
                                        <br><small><?php echo htmlspecialchars($booking['client_email']); ?></small>
                                        <br><small><?php echo htmlspecialchars($booking['client_phone']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($booking['session_type']); ?></td>
                                    <td>
                                        <?php echo date('F j, Y', strtotime($booking['event_date'])); ?>
                                        <br><small><?php echo date('h:i A', strtotime($booking['event_time'])); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($booking['location']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($booking['status']); ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($booking['status'] == 'pending'): ?>
                                                <form method="post" style="display: inline;">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                    <input type="hidden" name="status" value="confirmed">
                                                    <button type="submit" class="btn-confirm">Confirm</button>
                                                </form>
                                                <form method="post" style="display: inline;">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                    <input type="hidden" name="status" value="rejected">
                                                    <button type="submit" class="btn-reject">Reject</button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <?php if ($booking['status'] == 'confirmed'): ?>
                                                <form method="post" style="display: inline;">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                    <input type="hidden" name="status" value="completed">
                                                    <button type="submit" class="btn-complete">Mark Completed</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>