<?php
session_start();
include 'dbconnect.php';

// Check if user is logged in and is a photographer
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'photographer') {
    header('Location: login.php');
    exit();
}

// Fetch photographer details
$photographer_id = $_SESSION['userid'];
$stmt = mysqli_prepare($conn, "SELECT u.*, p.bio, p.location, p.upi_id 
                             FROM tbl_user u 
                             JOIN tbl_photographer p ON u.user_id = p.photographer_id 
                             WHERE u.user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $photographer_id);
mysqli_stmt_execute($stmt);
$photographer = mysqli_stmt_get_result($stmt)->fetch_assoc();

// Get filter parameters from query string
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$month_filter = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$year_filter = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Build query based on filters
$paymentQuery = "
    SELECT 
        p.payment_id,
        p.booking_id,
        p.status AS payment_status,
        p.payment_at,
        b.user_id AS client_id,
        u.name AS client_name,
        b.session_type,
        b.status AS booking_status,
        b.event_date,
        b.location,
        b.total_amt,
        CASE 
            WHEN b.status = 'cancelled' AND p.status = 'completed' THEN 'refunded'
            ELSE p.status
        END AS display_status
    FROM 
        tbl_payment p
    JOIN 
        tbl_booking b ON p.booking_id = b.booking_id
    JOIN 
        tbl_user u ON b.user_id = u.user_id
    WHERE 
        b.photographer_id = ?";

// Add status filter if not "all"
if ($status_filter != 'all') {
    if ($status_filter == 'refunded') {
        $paymentQuery .= " AND b.status = 'cancelled' AND p.status = 'completed'";
    } else {
        $paymentQuery .= " AND p.status = ? AND b.status != 'cancelled'";
    }
}

// Add date filters
$paymentQuery .= " AND MONTH(p.payment_at) = ? AND YEAR(p.payment_at) = ?";

// Order by most recent first
$paymentQuery .= " ORDER BY p.payment_at DESC";

// Prepare and execute the query
$stmt = mysqli_prepare($conn, $paymentQuery);

// Bind parameters based on filters
if ($status_filter != 'all' && $status_filter != 'refunded') {
    mysqli_stmt_bind_param($stmt, "isis", $photographer_id, $status_filter, $month_filter, $year_filter);
} else {
    mysqli_stmt_bind_param($stmt, "iii", $photographer_id, $month_filter, $year_filter);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$payments = [];
$booking_ids = [];

while ($payment = mysqli_fetch_assoc($result)) {
    // Store booking IDs for refund lookup
    if ($payment['display_status'] == 'refunded') {
        $booking_ids[] = $payment['booking_id'];
    }
    $payments[] = $payment;
}

// Fetch refund information for the relevant bookings
$refund_amounts = [];
if (!empty($booking_ids)) {
    $placeholders = str_repeat('?,', count($booking_ids) - 1) . '?';
    $refundQuery = "SELECT booking_id, refund_amount FROM tbl_refunds WHERE booking_id IN ($placeholders)";
    
    $stmt = mysqli_prepare($conn, $refundQuery);
    
    // Create type string for bind_param (all integers)
    $types = str_repeat('i', count($booking_ids));
    
    // Create array of references for bind_param
    $bind_params = [$stmt, $types];
    foreach ($booking_ids as $key => $id) {
        $bind_params[] = &$booking_ids[$key];
    }
    
    // Call bind_param with unpacked array
    call_user_func_array('mysqli_stmt_bind_param', $bind_params);
    
    mysqli_stmt_execute($stmt);
    $refund_result = mysqli_stmt_get_result($stmt);
    
    while ($refund = mysqli_fetch_assoc($refund_result)) {
        $refund_amounts[$refund['booking_id']] = $refund['refund_amount'];
    }
}

// Calculate summary statistics
$completedPaymentsAmount = 0;
$pendingAmount = 0;
$totalRefundedAmount = 0;

foreach ($payments as $payment) {
    if ($payment['display_status'] == 'completed' && $payment['booking_status'] != 'cancelled') {
        $completedPaymentsAmount += $payment['total_amt'];
    } else if ($payment['display_status'] == 'incomplete') {
        $pendingAmount += $payment['total_amt'];
    } else if ($payment['display_status'] == 'refunded') {
        // Calculate the amount that was refunded
        $refundAmount = isset($refund_amounts[$payment['booking_id']]) ? $refund_amounts[$payment['booking_id']] : 0;
        $totalRefundedAmount += $refundAmount;
        
        // Add the non-refunded portion to completed payments
        $nonRefundedAmount = $payment['total_amt'] - $refundAmount;
        $completedPaymentsAmount += $nonRefundedAmount;
    }
}

// Get available years for the filter
$yearsQuery = "
    SELECT DISTINCT YEAR(p.payment_at) as year
    FROM tbl_payment p
    JOIN tbl_booking b ON p.booking_id = b.booking_id
    WHERE b.photographer_id = ?
    ORDER BY year DESC";
$stmtYears = mysqli_prepare($conn, $yearsQuery);
mysqli_stmt_bind_param($stmtYears, "i", $photographer_id);
mysqli_stmt_execute($stmtYears);
$yearsResult = mysqli_stmt_get_result($stmtYears);
$years = [];
while ($year = mysqli_fetch_assoc($yearsResult)) {
    $years[] = $year['year'];
}
// If no years found (new photographer), add current year
if (empty($years)) {
    $years[] = date('Y');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History - LensPro</title>
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
            --refund-color: #9b59b6;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f0f2f5;
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

        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
            margin-bottom: 20px;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-label {
            font-size: 14px;
            font-weight: 500;
            color: var(--dark-gray);
        }

        select, button {
            padding: 8px 12px;
            border-radius: 5px;
            border: 1px solid #ddd;
            background-color: white;
            font-size: 14px;
        }

        button {
            background-color: var(--secondary-color);
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        button:hover {
            background-color: #2980b9;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-card h3 {
            color: var(--dark-gray);
            font-size: 14px;
            margin-bottom: 10px;
        }

        .stat-card .value {
            font-size: 24px;
            font-weight: 600;
        }

        .received .value {
            color: var(--success-color);
        }

        .pending .value {
            color: var(--warning-color);
        }

        .refunded .value {
            color: var(--refund-color);
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: var(--light-gray);
            color: var(--dark-gray);
            font-weight: 500;
        }

        tr:hover {
            background-color: rgba(245, 245, 245, 0.5);
        }

        .badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }

        .badge-completed {
            background-color: var(--success-color);
            color: white;
        }

        .badge-incomplete {
            background-color: var(--warning-color);
            color: white;
        }

        .badge-refunded {
            background-color: var(--refund-color);
            color: white;
        }

        .refund-note {
            display: block;
            font-size: 11px;
            margin-top: 5px;
            color: var(--refund-color);
        }

        .empty-state {
            text-align: center;
            padding: 40px 0;
            color: #888;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ddd;
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }

        .pagination a {
            padding: 5px 10px;
            border: 1px solid #ddd;
            color: var(--secondary-color);
            text-decoration: none;
            border-radius: 3px;
        }

        .pagination a.active {
            background-color: var(--secondary-color);
            color: white;
            border-color: var(--secondary-color);
        }

        .tooltip {
            position: relative;
            display: inline-block;
            cursor: help;
        }

        .tooltip .tooltiptext {
            visibility: hidden;
            width: 250px;
            background-color: #555;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -125px;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 70px;
            }

            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }

            .filters {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'photographer_sidebar.php'; ?>

        <div class="main-content">
            <h1 style="margin-bottom: 20px;">Payment History</h1>

            <div class="card">
                <div class="filters">
                    <form method="get" action="photographerviewpayment.php" style="display: flex; flex-wrap: wrap; gap: 15px; width: 100%;">
                        <div class="filter-group">
                            <span class="filter-label">Status:</span>
                            <select name="status">
                                <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Payments</option>
                                <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="incomplete" <?php echo $status_filter == 'incomplete' ? 'selected' : ''; ?>>Pending</option>
                                <option value="refunded" <?php echo $status_filter == 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <span class="filter-label">Month:</span>
                            <select name="month">
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $month_filter == $i ? 'selected' : ''; ?>>
                                        <?php echo date('F', mktime(0, 0, 0, $i, 1, date('Y'))); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <span class="filter-label">Year:</span>
                            <select name="year">
                                <?php foreach ($years as $year): ?>
                                    <option value="<?php echo $year; ?>" <?php echo $year_filter == $year ? 'selected' : ''; ?>>
                                        <?php echo $year; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit">Apply Filters</button>
                    </form>
                </div>

                <div class="stats-grid">
                    <div class="stat-card received">
                        <h3>Payments Received</h3>
                        <div class="value">₹<?php echo number_format($completedPaymentsAmount, 2); ?></div>
                    </div>
                    <div class="stat-card pending">
                        <h3>Pending</h3>
                        <div class="value">₹<?php echo number_format($pendingAmount, 2); ?></div>
                    </div>
                </div>

                <div class="table-container">
                    <?php if (!empty($payments)): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Booking ID</th>
                                    <th>Client</th>
                                    <th>Session Type</th>
                                    <th>Event Date</th>
                                    <th>Payment Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td>#<?php echo $payment['booking_id']; ?></td>
                                        <td><?php echo htmlspecialchars($payment['client_name']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['session_type']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($payment['event_date'])); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($payment['payment_at'])); ?></td>
                                        <td>
                                            ₹<?php echo number_format($payment['total_amt'], 2); ?>
                                            <?php if ($payment['display_status'] == 'refunded' && isset($refund_amounts[$payment['booking_id']])): ?>
                                                <span class="refund-note">Refunded: ₹<?php echo number_format($refund_amounts[$payment['booking_id']], 2); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($payment['display_status'] == 'completed'): ?>
                                                <span class="badge badge-completed">Received</span>
                                            <?php elseif ($payment['display_status'] == 'incomplete'): ?>
                                                <span class="badge badge-incomplete">Pending</span>
                                            <?php elseif ($payment['display_status'] == 'refunded'): ?>
                                                <span class="badge badge-refunded">Refunded</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-money-bill-wave"></i>
                            <h3>No Payment Records Found</h3>
                            <p>There are no payment records matching your filters for this period.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Payment Tips Card -->
            <div class="card">
                <h2 style="margin-bottom: 15px;">Payment Information</h2>
                <p><strong>Your UPI ID:</strong> <?php echo htmlspecialchars($photographer['upi_id']); ?></p>
                <p style="margin-top: 15px;"><strong>How Payments Work:</strong></p>
                <ul style="margin-left: 20px; margin-top: 10px;">
                    <li>Clients pay 10% advance when confirming a booking</li>
                    <li>The remaining amount is paid directly to you on the day of the event</li>
                    <li>Refunds are automatically processed for cancellations</li>
                </ul>
                <p style="margin-top: 15px;">If you need to update your UPI ID, please visit the <a href="photographerprofile.php" style="color: var(--secondary-color);">Profile Settings</a> page.</p>
            </div>
        </div>
    </div>
</body>
</html>