<?php
session_start();
include 'dbconnect.php';

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    header('Location: login.php');
    exit();
}

// Check if booking_id is provided
if (!isset($_GET['booking_id'])) {
    $_SESSION['message'] = "No booking ID provided.";
    $_SESSION['message_type'] = "error";
    header('Location: my-booking.php');
    exit();
}

$booking_id = intval($_GET['booking_id']);

// Get booking and payment details
$query = "
    SELECT 
        b.booking_id, 
        b.session_type, 
        b.status, 
        b.event_date, 
        b.location, 
        b.total_amt,
        b.booking_date,
        b.user_id,
        b.photographer_id,
        p.name AS photographer_name,
        p.email AS photographer_email,
        p.phno AS photographer_phone,
        p.profile_pic AS photographer_pic,
        c.name AS customer_name,
        c.email AS customer_email,
        c.phno AS customer_phone,
        py.payment_id,
        py.payment_at,
        py.status AS payment_status
    FROM 
        tbl_booking b 
    JOIN 
        tbl_user p ON b.photographer_id = p.user_id
    JOIN 
        tbl_user c ON b.user_id = c.user_id
    JOIN
        tbl_payment py ON b.booking_id = py.booking_id
    WHERE 
        b.booking_id = ? AND b.user_id = ?
";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $booking_id, $_SESSION['userid']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Check if booking exists and belongs to logged-in user
if (mysqli_num_rows($result) == 0) {
    $_SESSION['message'] = "Booking not found or you don't have permission to view this receipt.";
    $_SESSION['message_type'] = "error";
    header('Location: my-booking.php');
    exit();
}

$booking = mysqli_fetch_assoc($result);

// Check if refund exists for this booking
$refund_check_sql = "SELECT * FROM tbl_refunds WHERE booking_id = ?";
$refund_stmt = mysqli_prepare($conn, $refund_check_sql);
mysqli_stmt_bind_param($refund_stmt, "i", $booking_id);
mysqli_stmt_execute($refund_stmt);
$refund_result = mysqli_stmt_get_result($refund_stmt);
$has_refund = mysqli_num_rows($refund_result) > 0;

if ($has_refund) {
    $_SESSION['message'] = "This booking has been refunded. Receipt is not available.";
    $_SESSION['message_type'] = "error";
    header('Location: my-booking.php');
    exit();
}

// Generate receipt number based on payment ID and timestamp
$receipt_number = 'INV-' . str_pad($booking['payment_id'], 6, '0', STR_PAD_LEFT) . '-' . date('Ymd', strtotime($booking['payment_at']));

// Format dates
$booking_date = date('d M Y, h:i A', strtotime($booking['booking_date']));
$payment_date = date('d M Y, h:i A', strtotime($booking['payment_at']));
$event_date = date('d M Y', strtotime($booking['event_date']));

// Company details
$company_name = "LensPro Photography";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt #<?php echo $receipt_number; ?> - LensPro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
            padding: 0;
            margin: 0;
        }
        
        .container {
            max-width: 800px;
            margin: 20px auto;
            background-color: white;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .header {
            background-color: #2c3e50;
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }
        
        .logo {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .receipt-title {
            font-size: 1.2rem;
            font-weight: 500;
        }
        
        .receipt-number {
            position: absolute;
            top: 20px;
            right: 20px;
            background-color: rgba(255, 255, 255, 0.2);
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        
        .content {
            padding: 30px;
        }
        
        .section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 1.2rem;
            color: #2c3e50;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .detail-group {
            margin-bottom: 15px;
        }
        
        .detail-label {
            font-weight: 600;
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 5px;
        }
        
        .detail-value {
            font-size: 1rem;
            color: #333;
        }
        
        .payment-info {
            background-color: #f1f8ff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .payment-total {
            font-size: 1.8rem;
            color: #2c3e50;
            text-align: right;
            font-weight: 700;
            margin-top: 10px;
        }
        
        .footer {
            background-color: #f5f5f5;
            padding: 20px;
            text-align: center;
            font-size: 0.9rem;
            color: #666;
            border-top: 1px solid #e0e0e0;
        }
        
        .footer p {
            margin-bottom: 5px;
        }
        
        .actions {
            margin: 30px 0;
            text-align: center;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 25px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            margin: 0 10px;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }
        
        .btn-print {
            background-color: #2c3e50;
        }
        
        .btn-back {
            background-color: #7f8c8d;
        }
        
        .photographer-info {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .photographer-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 20px;
            border: 3px solid #3498db;
        }
        
        .photographer-details {
            flex-grow: 1;
        }
        
        .photographer-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .photographer-contact {
            font-size: 0.9rem;
            color: #666;
        }
        
        .qr-code {
            text-align: center;
            margin: 20px 0;
        }
        
        .qr-code img {
            width: 120px;
            height: 120px;
            border: 1px solid #e0e0e0;
            padding: 5px;
        }
        
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 8rem;
            opacity: 0.03;
            color: #000;
            pointer-events: none;
            white-space: nowrap;
            font-weight: 700;
        }
        
        @media print {
            body {
                background-color: white;
            }
            
            .container {
                box-shadow: none;
                margin: 0;
                max-width: 100%;
                border-radius: 0;
            }
            
            .actions {
                display: none;
            }
            
            @page {
                margin: 0.5cm;
            }
        }
        
        @media (max-width: 768px) {
            .details-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .receipt-number {
                position: static;
                margin-top: 15px;
                display: inline-block;
            }
            
            .header {
                text-align: center;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="watermark">LENSPRO</div>
        
        <div class="header">
            <div class="logo">LensPro</div>
            <div class="receipt-title">Payment Receipt</div>
            <div class="receipt-number"><?php echo $receipt_number; ?></div>
        </div>
        
        <div class="content">
            <div class="section">
                <h3 class="section-title">Booking Details</h3>
                <div class="details-grid">
                    <div>
                        <div class="detail-group">
                            <div class="detail-label">Booking ID</div>
                            <div class="detail-value">#<?php echo $booking['booking_id']; ?></div>
                        </div>
                        <div class="detail-group">
                            <div class="detail-label">Booking Date</div>
                            <div class="detail-value"><?php echo $booking_date; ?></div>
                        </div>
                        <div class="detail-group">
                            <div class="detail-label">Session Type</div>
                            <div class="detail-value"><?php echo htmlspecialchars($booking['session_type']); ?></div>
                        </div>
                    </div>
                    <div>
                        <div class="detail-group">
                            <div class="detail-label">Event Date</div>
                            <div class="detail-value"><?php echo $event_date; ?></div>
                        </div>
                        <div class="detail-group">
                            <div class="detail-label">Location</div>
                            <div class="detail-value"><?php echo htmlspecialchars($booking['location']); ?></div>
                        </div>
                        <div class="detail-group">
                            <div class="detail-label">Status</div>
                            <div class="detail-value"><?php echo htmlspecialchars($booking['status']); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="section">
                <h3 class="section-title">Customer Information</h3>
                <div class="details-grid">
                    <div>
                        <div class="detail-group">
                            <div class="detail-label">Name</div>
                            <div class="detail-value"><?php echo htmlspecialchars($booking['customer_name']); ?></div>
                        </div>
                        <div class="detail-group">
                            <div class="detail-label">Email</div>
                            <div class="detail-value"><?php echo htmlspecialchars($booking['customer_email']); ?></div>
                        </div>
                    </div>
                    <div>
                        <div class="detail-group">
                            <div class="detail-label">Phone</div>
                            <div class="detail-value"><?php echo htmlspecialchars($booking['customer_phone']); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="section">
                <h3 class="section-title">Photographer Details</h3>
                <div class="photographer-info">
                    <?php 
                    $photographer_pic = $booking['photographer_pic'] ? 
                        htmlspecialchars($booking['photographer_pic']) : 
                        'images/default-photographer.jpg'; 
                    ?>
                    <img src="<?php echo $photographer_pic; ?>" alt="Photographer" class="photographer-avatar">
                    <div class="photographer-details">
                        <div class="photographer-name"><?php echo htmlspecialchars($booking['photographer_name']); ?></div>
                        <div class="photographer-contact">
                            <div><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($booking['photographer_email']); ?></div>
                            <div><i class="fas fa-phone"></i> <?php echo htmlspecialchars($booking['photographer_phone']); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="section">
                <h3 class="section-title">Payment Information</h3>
                <div class="payment-info">
                    <div class="details-grid">
                        <div>
                            <div class="detail-group">
                                <div class="detail-label">Payment ID</div>
                                <div class="detail-value">#<?php echo $booking['payment_id']; ?></div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Payment Date</div>
                                <div class="detail-value"><?php echo $payment_date; ?></div>
                            </div>
                        </div>
                        <!-- <div>
                            <div class="detail-group">
                                <div class="detail-label">Transaction ID</div>
                                <div class="detail-value"><?php echo htmlspecialchars($booking['transaction_id']); ?></div>
                            </div>
                        </div> -->
                    </div>
                </div>
                <div class="payment-total">
                    Total Paid: ₹<?php echo number_format($booking['total_amt'], 2); ?>
                </div>
            </div>
            
            <!-- <div class="qr-code">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=<?php echo urlencode($receipt_number); ?>" alt="Receipt QR Code">
                <div style="font-size: 0.8rem; margin-top: 5px; color: #666;">Scan to verify receipt</div>
            </div> -->
            
            <div class="actions">
                <a href="javascript:window.print()" class="btn btn-print">
                    <i class="fas fa-print"></i> Print Receipt
                </a>
                <a href="my-booking.php" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Bookings
                </a>
            </div>
        </div>
        
        <div class="footer">
            <p><strong><?php echo $company_name; ?></strong></p>
            <p style="margin-top: 15px;">Thank you for choosing LensPro Photography!</p>
        </div>
    </div>
</body>
</html>