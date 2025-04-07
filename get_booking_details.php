<?php
session_start();
include 'dbconnect.php';

// Check if the user is an admin
if ($_SESSION['role'] !== 'admin') {
    echo "Unauthorized access";
    exit();
}

// Check if booking ID is provided
if (!isset($_GET['booking_id']) || !is_numeric($_GET['booking_id'])) {
    echo "Invalid booking ID";
    exit();
}

$bookingId = $_GET['booking_id'];

// Get booking details
$sql = "SELECT b.*, 
        u1.name as user_name, u1.email as user_email, u1.phno as user_phone,
        u2.name as photographer_name, u2.email as photographer_email, u2.phno as photographer_phone,
        p.status as payment_status, p.payment_at
        FROM tbl_booking b
        JOIN tbl_user u1 ON b.user_id = u1.user_id
        JOIN tbl_user u2 ON b.photographer_id = u2.user_id
        LEFT JOIN tbl_payment p ON b.booking_id = p.booking_id
        WHERE b.booking_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Booking not found";
    exit();
}

$booking = $result->fetch_assoc();

// Get any reviews associated with this booking
$reviewSql = "SELECT r.*, u.name as reviewer_name
              FROM tbl_reviews r
              JOIN tbl_user u ON r.user_id = u.user_id
              WHERE r.booking_id = ?";

$reviewStmt = $conn->prepare($reviewSql);
$reviewStmt->bind_param("i", $bookingId);
$reviewStmt->execute();
$reviewResult = $reviewStmt->get_result();

// Format the output
?>

<div class="booking-details">
    <div>
        <div class="detail-group">
            <h4>Booking ID</h4>
            <p>#<?php echo $booking['booking_id']; ?></p>
        </div>
        <div class="detail-group">
            <h4>Status</h4>
            <p><span class="status <?php echo $booking['status']; ?>"><?php echo ucfirst($booking['status']); ?></span></p>
        </div>
        <div class="detail-group">
            <h4>Session Type</h4>
            <p><?php echo $booking['session_type']; ?></p>
        </div>
        <div class="detail-group">
            <h4>Event Date</h4>
            <p><?php echo date('d M Y', strtotime($booking['event_date'])); ?></p>
        </div>
        <div class="detail-group">
            <h4>Location</h4>
            <p><?php echo $booking['location']; ?></p>
        </div>
    </div>
    
    <div>
        <div class="detail-group">
            <h4>Payment Status</h4>
            <p><?php echo isset($booking['payment_status']) ? ucfirst($booking['payment_status']) : 'No payment record'; ?></p>
        </div>
        <?php if (isset($booking['payment_status']) && $booking['payment_status'] === 'completed'): ?>
        <div class="detail-group">
            <h4>Payment Date</h4>
            <p><?php echo date('d M Y, h:i A', strtotime($booking['payment_at'])); ?></p>
        </div>
        <?php endif; ?>
        <div class="detail-group">
            <h4>Amount</h4>
            <p>₹<?php echo number_format($booking['total_amt'], 2); ?></p>
        </div>
        <div class="detail-group">
            <h4>Booking Date</h4>
            <p><?php echo date('d M Y, h:i A', strtotime($booking['booking_date'])); ?></p>
        </div>
    </div>
</div>

<hr style="margin: 20px 0;">

<div class="booking-parties">
    <h3 style="margin-bottom: 15px;">Booking Parties</h3>
    
    <div class="booking-details">
        <div>
            <div class="detail-group">
                <h4>Client Name</h4>
                <p><?php echo $booking['user_name']; ?></p>
            </div>
            <div class="detail-group">
                <h4>Client Email</h4>
                <p><?php echo $booking['user_email']; ?></p>
            </div>
            <div class="detail-group">
                <h4>Client Phone</h4>
                <p><?php echo $booking['user_phone']; ?></p>
            </div>
        </div>
        
        <div>
            <div class="detail-group">
                <h4>Photographer Name</h4>
                <p><?php echo $booking['photographer_name']; ?></p>
            </div>
            <div class="detail-group">
                <h4>Photographer Email</h4>
                <p><?php echo $booking['photographer_email']; ?></p>
            </div>
            <div class="detail-group">
                <h4>Photographer Phone</h4>
                <p><?php echo $booking['photographer_phone']; ?></p>
            </div>
        </div>
    </div>
</div>

<?php if ($reviewResult->num_rows > 0): ?>
<hr style="margin: 20px 0;">

<div class="review-section">
    <h3 style="margin-bottom: 15px;">Review</h3>
    
    <?php while($review = $reviewResult->fetch_assoc()): ?>
    <div class="detail-group">
        <h4>Rating</h4>
        <p>
            <?php
            $rating = $review['rating'];
            for ($i = 1; $i <= 5; $i++) {
                echo $i <= $rating ? '<i class="fas fa-star" style="color: gold;"></i>' : '<i class="far fa-star"></i>';
            }
            echo " ({$rating}/5)";
            ?>
        </p>
    </div>
    <div class="detail-group">
        <h4>Review by <?php echo $review['reviewer_name']; ?></h4>
        <p><?php echo $review['review_text']; ?></p>
    </div>
    <div class="detail-group">
        <h4>Review Date</h4>
        <p><?php echo date('d M Y', strtotime($review['created_at'])); ?></p>
    </div>
    <?php endwhile; ?>
</div>
<?php endif; ?>
