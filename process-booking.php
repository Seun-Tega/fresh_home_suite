<?php
// process-booking.php - Complete fixed version
require_once 'config/config.php';
require_once 'includes/functions.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Get form data
        $room_id = $_POST['room_id'];
        $check_in = $_POST['check_in'];
        $check_out = $_POST['check_out'];
        $adults = $_POST['adults'];
        $children = $_POST['children'];
        $total_amount = $_POST['total_amount'];
        $payment_method = $_POST['payment_method'];
        $special_requests = $_POST['special_requests'] ?? '';
        
        // Guest information
        $full_name = $_POST['full_name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $address = $_POST['address'] ?? '';
        
        // Validate required fields
        if (empty($room_id) || empty($check_in) || empty($check_out) || empty($adults) || empty($total_amount) || empty($payment_method) || empty($full_name) || empty($email) || empty($phone)) {
            $_SESSION['error'] = "All required fields must be filled";
            redirect('booking.php');
            exit();
        }
        
        // Check if guest exists or create new
        $stmt = $pdo->prepare("SELECT id FROM guests WHERE email = ?");
        $stmt->execute([$email]);
        $guest = $stmt->fetch();
        
        if ($guest) {
            $guest_id = $guest['id'];
            
            // Update guest information
            $stmt = $pdo->prepare("UPDATE guests SET full_name = ?, phone = ?, address = ? WHERE id = ?");
            $stmt->execute([$full_name, $phone, $address, $guest_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO guests (full_name, email, phone, address, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$full_name, $email, $phone, $address]);
            $guest_id = $pdo->lastInsertId();
        }
        
        // Generate shorter booking number (to avoid database length issues)
        $booking_number = 'RM' . date('ymd') . rand(100, 999); // Example: RM240101123
        // Alternative: $booking_number = 'RM' . time(); // Example: RM1643123456
        
        // Handle receipt upload if bank transfer
        $receipt_path = null;
        if ($payment_method == 'bank_transfer' && isset($_FILES['receipt']) && $_FILES['receipt']['error'] == 0) {
            $upload_result = uploadFile($_FILES['receipt'], 'receipts');
            if ($upload_result['success']) {
                $receipt_path = $upload_result['file_path'];
            } else {
                $_SESSION['error'] = $upload_result['message'];
                redirect('booking.php');
                exit();
            }
        }
        
        // Calculate extra charges
        $stmt = $pdo->prepare("SELECT max_occupancy, base_price FROM rooms WHERE id = ?");
        $stmt->execute([$room_id]);
        $room = $stmt->fetch();
        
        if (!$room) {
            $_SESSION['error'] = "Invalid room selected";
            redirect('booking.php');
            exit();
        }
        
        $total_guests = $adults + $children;
        $extra_charge = 0;
        
        if ($total_guests > $room['max_occupancy']) {
            $days = ceil((strtotime($check_out) - strtotime($check_in)) / (60 * 60 * 24));
            $extra_guests = $total_guests - $room['max_occupancy'];
            $extra_charge = $extra_guests * 1000 * $days;
        }
        
        // Calculate subtotal
        $days = ceil((strtotime($check_out) - strtotime($check_in)) / (60 * 60 * 24));
        $subtotal = $room['base_price'] * $days;
        
        // Insert booking
        $sql = "INSERT INTO room_bookings (
                    booking_number, guest_id, room_id, check_in, check_out, 
                    adults, children, total_guests, extra_bed_charge, subtotal, 
                    total_amount, payment_method, payment_status, receipt_path, 
                    special_requests, booking_status, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?, 
                    ?, ?, ?, ?, ?, 
                    ?, ?, ?, ?, 
                    ?, ?, NOW()
                )";
        
        $payment_status = ($payment_method == 'bank_transfer') ? 'pending' : 'pending';
        $booking_status = 'pending';
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $booking_number, 
            $guest_id, 
            $room_id, 
            $check_in, 
            $check_out,
            $adults, 
            $children, 
            $total_guests, 
            $extra_charge, 
            $subtotal,
            $total_amount, 
            $payment_method, 
            $payment_status, 
            $receipt_path,
            $special_requests, 
            $booking_status
        ]);
        
        if (!$result) {
            throw new Exception("Failed to insert booking");
        }
        
        $booking_id = $pdo->lastInsertId();
        
        // Send confirmation email (try-catch to prevent breaking if email fails)
        try {
            $subject = "Booking Confirmation - " . $booking_number;
            $message = "<h1>Thank you for your booking!</h1>";
            $message .= "<p>Your booking number is: <strong>$booking_number</strong></p>";
            $message .= "<p>Check-in: " . date('M d, Y', strtotime($check_in)) . "</p>";
            $message .= "<p>Check-out: " . date('M d, Y', strtotime($check_out)) . "</p>";
            $message .= "<p>Total Amount: " . formatCurrency($total_amount) . "</p>";
            
            if ($payment_method == 'bank_transfer') {
                $message .= "<p>Payment Status: Pending (Awaiting receipt verification)</p>";
                $message .= "<p>Please upload your payment receipt in your account.</p>";
            } else {
                $message .= "<p>Payment Method: Pay at Hotel</p>";
            }
            
            sendEmail($email, $subject, $message);
            
            // Send SMS
            $sms_message = "Thank you for booking at Fresh Home and Suite Hotel. Your booking number is: $booking_number";
            sendSMS($phone, $sms_message);
        } catch (Exception $e) {
            // Log error but don't stop the process
            error_log("Email/SMS error: " . $e->getMessage());
        }
        
        // Set session and redirect
        $_SESSION['booking_success'] = true;
        $_SESSION['booking_number'] = $booking_number;
        $_SESSION['success_message'] = "Booking successful! Your booking number is: $booking_number";
        
        redirect('booking-confirmation.php?booking=' . $booking_number);
        exit();
        
    } catch (PDOException $e) {
        // Log the error
        error_log("Database error in process-booking.php: " . $e->getMessage());
        
        // Check if it's the booking_number length error
        if (strpos($e->getMessage(), 'Data too long for column') !== false) {
            $_SESSION['error'] = "Booking number generation error. Please try again.";
        } else {
            $_SESSION['error'] = "A database error occurred. Please try again.";
        }
        
        redirect('booking.php');
        exit();
        
    } catch (Exception $e) {
        // Log the error
        error_log("General error in process-booking.php: " . $e->getMessage());
        
        $_SESSION['error'] = "An error occurred: " . $e->getMessage();
        redirect('booking.php');
        exit();
    }
    
} else {
    // Not a POST request
    redirect('index.php');
    exit();
}
?>