<?php
// functions.php - Complete file with fixed redirect function
// COPY AND PASTE THIS ENTIRE CODE

/**
 * Safely redirect to another page without headers error
 * 
 * @param string $url The URL to redirect to
 * @return void
 */
function redirect($url) {
    // Clean any output buffers
    if (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // Check if headers already sent
    if (!headers_sent()) {
        // Use PHP header redirect
        header("Location: " . $url);
        exit();
    } else {
        // Use JavaScript redirect
        echo "<script>window.location.href='" . $url . "';</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=" . $url . "'></noscript>";
        exit();
    }
}

// Generate unique booking number
function generateBookingNumber($type = 'RM') {
    return $type . '-' . date('Ymd') . '-' . strtoupper(uniqid());
}

// Upload file function
function uploadFile($file, $folder = 'receipts') {
    $target_dir = UPLOAD_PATH . $folder . '/';
    
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $file_name = time() . '_' . uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $file_name;
    
    // Check if image file is actual image
    $check = getimagesize($file['tmp_name']);
    if($check === false) {
        return ['success' => false, 'message' => 'File is not an image.'];
    }
    
    // Check file size (max 5MB)
    if ($file['size'] > 5000000) {
        return ['success' => false, 'message' => 'File too large. Max 5MB.'];
    }
    
    // Allow certain file formats
    if($file_extension != "jpg" && $file_extension != "png" && $file_extension != "jpeg" && $file_extension != "gif" ) {
        return ['success' => false, 'message' => 'Only JPG, JPEG, PNG & GIF files allowed.'];
    }
    
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return [
            'success' => true, 
            'file_path' => 'uploads/' . $folder . '/' . $file_name,
            'file_name' => $file_name
        ];
    } else {
        return ['success' => false, 'message' => 'Error uploading file.'];
    }
}

// Check room availability
function checkRoomAvailability($pdo, $room_id, $check_in, $check_out) {
    $sql = "SELECT COUNT(*) as count FROM room_bookings 
            WHERE room_id = ? 
            AND booking_status IN ('confirmed', 'checked_in')
            AND (
                (check_in <= ? AND check_out > ?) OR
                (check_in < ? AND check_out >= ?) OR
                (check_in >= ? AND check_out <= ?)
            )";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$room_id, $check_in, $check_in, $check_out, $check_out, $check_in, $check_out]);
    $result = $stmt->fetch();
    
    return $result['count'] == 0;
}

// Calculate total price for room booking
function calculateRoomPrice($pdo, $room_id, $check_in, $check_out, $adults, $children) {
    // Get room base price
    $stmt = $pdo->prepare("SELECT base_price, max_occupancy FROM rooms WHERE id = ?");
    $stmt->execute([$room_id]);
    $room = $stmt->fetch();
    
    $days = ceil((strtotime($check_out) - strtotime($check_in)) / (60 * 60 * 24));
    $base_total = $room['base_price'] * $days;
    
    // Check for seasonal pricing
    $stmt = $pdo->prepare("SELECT price FROM room_pricing 
                           WHERE room_id = ? 
                           AND start_date <= ? 
                           AND end_date >= ? 
                           LIMIT 1");
    $stmt->execute([$room_id, $check_in, $check_in]);
    $seasonal = $stmt->fetch();
    
    if ($seasonal) {
        $base_total = $seasonal['price'] * $days;
    }
    
    // Extra person charge
    $total_guests = $adults + $children;
    $extra_charge = 0;
    if ($total_guests > $room['max_occupancy']) {
        $extra_guests = $total_guests - $room['max_occupancy'];
        $extra_charge = $extra_guests * 1000 * $days; // $1000 per extra person per night
    }
    
    return [
        'subtotal' => $base_total,
        'extra_charge' => $extra_charge,
        'total' => $base_total + $extra_charge,
        'days' => $days
    ];
}

// Get bank accounts
function getBankAccounts($pdo) {
    $stmt = $pdo->query("SELECT * FROM bank_accounts WHERE is_active = 1");
    return $stmt->fetchAll();
}

// Send email notification (simplified - use PHPMailer in production)
function sendEmail($to, $subject, $message) {
    // In production, use PHPMailer or similar
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: ' . SITE_NAME . ' <noreply@freshhomehotel.com>' . "\r\n";
    
    return mail($to, $subject, $message, $headers);
}

// Send SMS (simplified - integrate with SMS gateway in production)
function sendSMS($phone, $message) {
    // Integrate with SMS gateway like Twilio, Africa's Talking, etc.
    return true;
}

// Format currency
function formatCurrency($amount) {
    return '₦' . number_format($amount, 2);
}

// Get booking status badge
function getStatusBadge($status) {
    $badges = [
        'pending' => 'bg-yellow-100 text-yellow-800',
        'confirmed' => 'bg-green-100 text-green-800',
        'checked_in' => 'bg-blue-100 text-blue-800',
        'checked_out' => 'bg-gray-100 text-gray-800',
        'cancelled' => 'bg-red-100 text-red-800',
        'receipt_uploaded' => 'bg-purple-100 text-purple-800',
        'verified' => 'bg-green-100 text-green-800'
    ];
    
    $class = $badges[$status] ?? 'bg-gray-100 text-gray-800';
    return '<span class="px-2 py-1 rounded-full text-xs font-semibold ' . $class . '">' . ucfirst($status) . '</span>';
}

// Sanitize input
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

// =========== NEW FUNCTIONS ADDED BELOW (Won't affect existing code) ===========

/**
 * Helper function to get setting with default value
 * 
 * @param array $settings The settings array
 * @param string $key The setting key to retrieve
 * @param mixed $default Default value if setting doesn't exist
 * @return mixed The setting value or default
 */
function getSetting($settings, $key, $default = '') {
    return isset($settings[$key]) && !empty($settings[$key]) ? $settings[$key] : $default;
}

/**
 * Fetch all site settings from database with caching
 * 
 * @param PDO $pdo Database connection
 * @return array Settings array
 */
function getSiteSettings($pdo) {
    static $settings = null; // Cache settings to avoid multiple queries
    
    if ($settings === null) {
        try {
            $stmt = $pdo->query("SELECT * FROM site_settings");
            $settings = [];
            while($row = $stmt->fetch()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (PDOException $e) {
            $settings = [];
        }
    }
    
    return $settings;
}

/**
 * Get all bank accounts (including inactive ones for admin)
 * 
 * @param PDO $pdo Database connection
 * @param bool $onlyActive Whether to return only active accounts
 * @return array Bank accounts
 */
function getAllBankAccounts($pdo, $onlyActive = false) {
    try {
        if ($onlyActive) {
            $stmt = $pdo->query("SELECT * FROM bank_accounts WHERE is_active = 1 ORDER BY bank_name");
        } else {
            $stmt = $pdo->query("SELECT * FROM bank_accounts ORDER BY is_active DESC, bank_name");
        }
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get business hours settings
 * 
 * @param array $settings Settings array
 * @return array Business hours
 */
function getBusinessHours($settings) {
    return [
        'weekdays' => getSetting($settings, 'hours_weekdays', '24 Hours'),
        'saturday' => getSetting($settings, 'hours_saturday', '24 Hours'),
        'sunday' => getSetting($settings, 'hours_sunday', '24 Hours'),
        'front_desk' => getSetting($settings, 'hours_front_desk', '24 Hours'),
        'restaurant' => getSetting($settings, 'hours_restaurant', '7:00 AM - 11:00 PM'),
        'fitness' => getSetting($settings, 'hours_fitness', '6:00 AM - 10:00 PM')
    ];
}

/**
 * Get social media links
 * 
 * @param array $settings Settings array
 * @return array Social media links
 */
function getSocialLinks($settings) {
    $socials = ['facebook', 'twitter', 'instagram', 'linkedin', 'whatsapp'];
    $links = [];
    
    foreach ($socials as $social) {
        $key = $social === 'whatsapp' ? 'whatsapp_number' : 'social_' . $social;
        $value = getSetting($settings, $key, '');
        if (!empty($value)) {
            $links[$social] = $value;
        }
    }
    
    return $links;
}

/**
 * Get contact information
 * 
 * @param array $settings Settings array
 * @return array Contact information
 */
function getContactInfo($settings) {
    return [
        'address' => getSetting($settings, 'hotel_address', '123 Hotel Street, City, Country'),
        'phone' => getSetting($settings, 'hotel_phone', '+123 456 7890'),
        'phone_alt' => getSetting($settings, 'hotel_phone_alt', ''),
        'email' => getSetting($settings, 'hotel_email', 'info@freshhomehotel.com'),
        'email_alt' => getSetting($settings, 'hotel_email_alt', ''),
        'whatsapp' => getSetting($settings, 'whatsapp_number', '')
    ];
}

/**
 * Get hotel information
 * 
 * @param array $settings Settings array
 * @return array Hotel information
 */
function getHotelInfo($settings) {
    return [
        'name' => getSetting($settings, 'hotel_name', 'Fresh Home & Suite Hotel'),
        'description' => getSetting($settings, 'hotel_description', 'Experience luxury and comfort at Fresh Home and Suite Hotel.'),
        'currency' => getSetting($settings, 'currency', '₦'),
        'timezone' => getSetting($settings, 'timezone', 'Africa/Lagos')
    ];
}

?>