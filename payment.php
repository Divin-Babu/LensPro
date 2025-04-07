<?php
session_start();
include 'dbconnect.php';

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    header('Location: login.php');
    exit();
}

// Verify if booking_id is provided
if (!isset($_GET['booking_id']) || empty($_GET['booking_id'])) {
    $_SESSION['message'] = "Invalid booking request.";
    $_SESSION['message_type'] = "error";
    header('Location: my-booking.php');
    exit();
}

$booking_id = intval($_GET['booking_id']);

// Fetch booking details and photographer's UPI ID
$booking_query = "
    SELECT 
        b.booking_id, 
        b.session_type, 
        b.status, 
        b.event_date, 
        b.location,
        b.user_id,
        b.photographer_id,
        p.name AS photographer_name,
        p.profile_pic AS photographer_pic,
        c.category_name,
        c.category_id,
        pp.price AS original_price,
        pd.upi_id AS photographer_upi
    FROM 
        tbl_booking b 
    JOIN 
        tbl_user p ON b.photographer_id = p.user_id
    JOIN
        tbl_categories c ON b.session_type = c.category_name
    JOIN
        tbl_photographer_pricing pp ON (pp.photographer_id = b.photographer_id AND pp.category_id = c.category_id)
    JOIN
        tbl_photographer pd ON pd.photographer_id = b.photographer_id
    WHERE 
        b.booking_id = ? AND b.user_id = ? AND b.status = 'confirmed'
";

$stmt = mysqli_prepare($conn, $booking_query);
mysqli_stmt_bind_param($stmt, "ii", $booking_id, $_SESSION['userid']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    $_SESSION['message'] = "Booking not found or payment not required.";
    $_SESSION['message_type'] = "error";
    header('Location: my-booking.php');
    exit();
}

$booking = mysqli_fetch_assoc($result);

// Get photographer's UPI ID
$photographer_upi = $booking['photographer_upi'];

// Calculate 10% advance payment amount
$original_price = $booking['original_price'];
$advance_amount = $original_price * 0.10;

// Razorpay API keys
$razorpay_key_id = 'rzp_test_VdzXhpDXJNNqf9';
$razorpay_key_secret = '03afEMkwrFUUZURXxNGr9vm7';

// Process payment if form is submitted
if (isset($_POST['payment_id']) && isset($_POST['razorpay_payment_id'])) {
    $razorpay_payment_id = $_POST['razorpay_payment_id'];
    
    // Begin transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Update booking total amount
        $update_booking = "UPDATE tbl_booking SET total_amt = ? WHERE booking_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_booking);
        mysqli_stmt_bind_param($update_stmt, "di", $advance_amount, $booking_id);
        mysqli_stmt_execute($update_stmt);
        
        // Insert payment record
        $insert_payment = "INSERT INTO tbl_payment (booking_id, status) VALUES (?, 'completed')";
        $payment_stmt = mysqli_prepare($conn, $insert_payment);
        mysqli_stmt_bind_param($payment_stmt, "i", $booking_id);
        mysqli_stmt_execute($payment_stmt);
        
        // Commit transaction
        mysqli_commit($conn);
        
        $_SESSION['message'] = "Payment successful!";
        $_SESSION['message_type'] = "success";
        header('Location: my-booking.php');
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        
        $_SESSION['message'] = "Payment processing error. Please try again.";
        $_SESSION['message_type'] = "error";
        header('Location: payment.php?booking_id=' . $booking_id);
        exit();
    }
}

// Format date for display
$event_date = date('F j, Y', strtotime($booking['event_date']));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - LensPro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        :root {
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-image: url('images/paymentbg.jpg');
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

        .container {
            max-width: 1200px;
            margin: 50px auto;
            padding: 0 20px;
        }

        .payment-container {
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            max-width: 800px;
            margin: 0 auto;
        }

        .payment-header {
            background-color: var(--primary-color);
            color: white;
            padding: 25px;
            text-align: center;
            font-size: 24px;
            font-weight: 600;
        }

        .booking-details {
            display: flex;
            flex-wrap: wrap;
            padding: 30px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .photographer-info {
            flex: 0 0 150px;
            margin-right: 30px;
            text-align: center;
        }

        .photographer-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--secondary-color);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 10px;
        }

        .booking-info {
            flex: 1;
        }

        .booking-info h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 22px;
        }

        .booking-info p {
            margin-bottom: 10px;
            font-size: 16px;
            display: flex;
            align-items: center;
        }

        .booking-info i {
            width: 25px;
            color: var(--secondary-color);
            margin-right: 10px;
        }

        .payment-details {
            padding: 30px;
        }

        .price-breakdown {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px dashed rgba(0, 0, 0, 0.1);
        }

        .price-row:last-child {
            border-bottom: none;
        }

        .price-row.total {
            border-top: 2px solid rgba(0, 0, 0, 0.1);
            border-bottom: none;
            padding-top: 15px;
            font-weight: 600;
            font-size: 18px;
            color: var(--primary-color);
        }

        .upi-info {
            background-color: #fff8e1;
            border: 1px solid #ffe082;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .upi-icon {
            font-size: 24px;
            color: #f57c00;
            margin-right: 15px;
        }

        .upi-details {
            flex: 1;
        }

        .upi-id {
            font-weight: 600;
            font-size: 18px;
            margin-bottom: 5px;
            color: #333;
        }

        .upi-note {
            color: #666;
            font-size: 14px;
        }

        .payment-actions {
            display: flex;
            justify-content: space-between;
            gap: 15px;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            flex: 1;
            justify-content: center;
        }

        .btn-primary {
            background-color: var(--secondary-color);
            color: white;
        }

        .btn-secondary {
            background-color: #95a5a6;
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--hover-color);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .btn-secondary:hover {
            background-color: #7f8c8d;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
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

        @media (max-width: 768px) {
            .booking-details {
                flex-direction: column;
            }
            
            .photographer-info {
                margin-right: 0;
                margin-bottom: 20px;
            }
            
            .payment-actions {
                flex-direction: column;
            }
            
            .payment-container {
                margin: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php
        if (isset($_SESSION['message'])) {
            $message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'info';
            echo "<div class='message message-{$message_type}'>{$_SESSION['message']}</div>";
            
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
        }
        ?>
        
        <div class="payment-container">
            <div class="payment-header">
                Complete Your Payment
            </div>
            
            <div class="booking-details">
                <div class="photographer-info">
                    <?php 
                    $photographer_pic = $booking['photographer_pic'] ? 
                        htmlspecialchars($booking['photographer_pic']) : 
                        'images/default-photographer.jpg'; 
                    ?>
                    <img src="<?php echo $photographer_pic; ?>" alt="Photographer" class="photographer-avatar">
                    <div><?php echo htmlspecialchars($booking['photographer_name']); ?></div>
                </div>
                
                <div class="booking-info">
                    <h3>Booking Details</h3>
                    <p><i class="fas fa-camera"></i> <strong>Session Type:</strong> <?php echo htmlspecialchars($booking['session_type']); ?></p>
                    <p><i class="fas fa-calendar"></i> <strong>Event Date:</strong> <?php echo $event_date; ?></p>
                    <p><i class="fas fa-map-marker-alt"></i> <strong>Location:</strong> <?php echo htmlspecialchars($booking['location']); ?></p>
                    <p><i class="fas fa-id-card"></i> <strong>Booking ID:</strong> #<?php echo $booking_id; ?></p>
                </div>
            </div>
            
            <div class="payment-details">
                <h4>Payment Summary</h4>
                
                <!-- UPI Information Section -->
                <div class="upi-info">
                    <div class="upi-icon">
                        <i class="fas fa-qrcode"></i>
                    </div>
                    <div class="upi-details">
                        <div class="upi-id">Photographer's UPI: <?php echo htmlspecialchars($photographer_upi); ?></div>
                        <div class="upi-note">Your payment will be sent to the photographer's account</div>
                    </div>
                </div>
                
                <div class="price-breakdown">
                    <div class="price-row total">
                        <span>Amount to be Paid:</span>
                        <span>₹<?php echo number_format($advance_amount, 2); ?></span>
                    </div>
                </div>
                
                <div class="payment-actions">
                    <a href="my-booking.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Bookings
                    </a>
                    <button id="pay-button" class="btn btn-primary">
                        <i class="fas fa-credit-card"></i> Pay Now
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('pay-button').addEventListener('click', function() {
            const options = {
                key: '<?php echo $razorpay_key_id; ?>',
                amount: <?php echo $advance_amount * 100; ?>, // Amount in smallest currency unit (paise)
                currency: 'INR',
                name: '<?php echo htmlspecialchars($booking['photographer_name']); ?>',
                description: 'Payment for Photography Session #<?php echo $booking_id; ?>',
                image: 'images/default-photographer.jpg', // Replace with your logo path
                handler: function(response) {
                    // Handle the payment success
                    if (response.razorpay_payment_id) {
                        // Create a form to submit payment details to server
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'payment.php?booking_id=<?php echo $booking_id; ?>';
                        
                        const paymentIdInput = document.createElement('input');
                        paymentIdInput.name = 'payment_id';
                        paymentIdInput.value = '<?php echo $booking_id; ?>';
                        form.appendChild(paymentIdInput);
                        
                        const razorpayPaymentIdInput = document.createElement('input');
                        razorpayPaymentIdInput.name = 'razorpay_payment_id';
                        razorpayPaymentIdInput.value = response.razorpay_payment_id;
                        form.appendChild(razorpayPaymentIdInput);
                        
                        document.body.appendChild(form);
                        form.submit();
                    }
                },
                prefill: {
                    name: '<?php echo htmlspecialchars($_SESSION["username"] ?? ""); ?>',
                    email: '<?php echo htmlspecialchars($_SESSION["email"] ?? ""); ?>',
                },
                notes: {
                    photographer_upi: '<?php echo htmlspecialchars($photographer_upi); ?>',
                    booking_id: '<?php echo $booking_id; ?>',
                    payment_type: 'advance_payment'
                },
                theme: {
                    color: '#3498db'
                },
                modal: {
                    ondismiss: function() {
                        console.log('Payment modal closed');
                    }
                },
                // Configure UPI as the primary payment method
                payment_methods: {
                    upi: {
                        flow: 'collect',
                        vpa: '<?php echo htmlspecialchars($photographer_upi); ?>', // Set photographer's UPI as the default
                        fallback: true
                    },
                    netbanking: {
                        banks: ['HDFC', 'ICICI', 'SBIN', 'UTIB', 'FDRL'] 
                    }
                }
            };
            
            // Create Razorpay instance and open the payment modal
            const rzp = new Razorpay(options);
            rzp.open();
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>