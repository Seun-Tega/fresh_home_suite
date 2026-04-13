<?php
require_once 'config/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_receipt'])) {
    $booking_type = $_POST['booking_type'];
    $booking_id = $_POST['booking_id'];
    
    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] == 0) {
        $upload_result = uploadFile($_FILES['receipt'], 'receipts');
        
        if ($upload_result['success']) {
            if ($booking_type == 'room') {
                $stmt = $pdo->prepare("UPDATE room_bookings SET receipt_path = ?, payment_status = 'receipt_uploaded', receipt_uploaded_at = NOW() WHERE id = ?");
            } else {
                $stmt = $pdo->prepare("UPDATE hall_bookings SET receipt_path = ?, payment_status = 'receipt_uploaded', receipt_uploaded_at = NOW() WHERE id = ?");
            }
            
            if ($stmt->execute([$upload_result['file_path'], $booking_id])) {
                // Get booking number for redirect
                if ($booking_type == 'room') {
                    $stmt = $pdo->prepare("SELECT booking_number FROM room_bookings WHERE id = ?");
                } else {
                    $stmt = $pdo->prepare("SELECT booking_number FROM hall_bookings WHERE id = ?");
                }
                $stmt->execute([$booking_id]);
                $booking = $stmt->fetch();
                
                $_SESSION['success'] = "Receipt uploaded successfully! Awaiting verification.";
                redirect('booking-confirmation.php?booking=' . $booking['booking_number']);
            }
        } else {
            $_SESSION['error'] = $upload_result['message'];
        }
    } else {
        $_SESSION['error'] = "Please select a file to upload.";
    }
}

redirect('index.php');
?>