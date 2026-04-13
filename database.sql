-- Create Database
CREATE DATABASE IF NOT EXISTS fresh_home_suite;
USE fresh_home_suite;

-- Users Table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE,
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    full_name VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    role ENUM('super_admin', 'front_desk', 'kitchen', 'hall_manager') DEFAULT 'front_desk',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Guests Table
CREATE TABLE guests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    password VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Rooms Table
CREATE TABLE rooms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_number VARCHAR(10) UNIQUE,
    room_type VARCHAR(50),
    description TEXT,
    base_price DECIMAL(10,2),
    max_occupancy INT,
    bed_type VARCHAR(50),
    square_feet INT,
    amenities TEXT,
    status ENUM('available', 'maintenance') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Room Images Table
CREATE TABLE room_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_id INT,
    image_path VARCHAR(255),
    is_primary BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

-- Room Pricing Table (Seasonal)
CREATE TABLE room_pricing (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_id INT,
    season_type ENUM('peak', 'off_peak', 'weekend'),
    price DECIMAL(10,2),
    start_date DATE,
    end_date DATE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

-- Hall Table
CREATE TABLE hall (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100),
    description TEXT,
    base_price_hourly DECIMAL(10,2),
    base_price_half_day DECIMAL(10,2),
    base_price_full_day DECIMAL(10,2),
    capacity_theater INT,
    capacity_banquet INT,
    capacity_classroom INT,
    amenities TEXT,
    status ENUM('available', 'maintenance') DEFAULT 'available'
);

-- Hall Images Table
CREATE TABLE hall_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hall_id INT,
    image_path VARCHAR(255),
    setup_type VARCHAR(50),
    FOREIGN KEY (hall_id) REFERENCES hall(id) ON DELETE CASCADE
);

-- Hall Amenities Table
CREATE TABLE hall_amenities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100),
    price DECIMAL(10,2),
    description TEXT,
    is_available BOOLEAN DEFAULT TRUE
);

-- Hall Event Packages Table
CREATE TABLE hall_packages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100),
    description TEXT,
    price DECIMAL(10,2),
    amenities_included TEXT
);

-- Food Categories Table
CREATE TABLE food_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50),
    description TEXT,
    display_order INT
);

-- Food Items Table
CREATE TABLE food_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT,
    name VARCHAR(100),
    description TEXT,
    price DECIMAL(10,2),
    image_path VARCHAR(255),
    dietary_type ENUM('veg', 'non_veg', 'spicy', 'chef_special'),
    is_available BOOLEAN DEFAULT TRUE,
    display_order INT,
    FOREIGN KEY (category_id) REFERENCES food_categories(id) ON DELETE CASCADE
);

-- Room Bookings Table
CREATE TABLE room_bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_number VARCHAR(20) UNIQUE,
    guest_id INT,
    room_id INT,
    check_in DATE,
    check_out DATE,
    adults INT,
    children INT,
    total_guests INT,
    extra_bed_charge DECIMAL(10,2) DEFAULT 0,
    subtotal DECIMAL(10,2),
    total_amount DECIMAL(10,2),
    payment_method ENUM('pay_at_hotel', 'bank_transfer') DEFAULT 'pay_at_hotel',
    payment_status ENUM('pending', 'receipt_uploaded', 'verified', 'cancelled') DEFAULT 'pending',
    receipt_path VARCHAR(255),
    receipt_uploaded_at DATETIME,
    receipt_verified_by INT,
    receipt_verified_at DATETIME,
    booking_status ENUM('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled') DEFAULT 'pending',
    special_requests TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (guest_id) REFERENCES guests(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id),
    FOREIGN KEY (receipt_verified_by) REFERENCES users(id)
);

-- Hall Bookings Table
CREATE TABLE hall_bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_number VARCHAR(20) UNIQUE,
    guest_id INT,
    hall_id INT,
    event_type VARCHAR(100),
    booking_date DATE,
    start_time TIME,
    end_time TIME,
    booking_type ENUM('hourly', 'half_day', 'full_day') DEFAULT 'hourly',
    selected_amenities TEXT,
    subtotal DECIMAL(10,2),
    total_amount DECIMAL(10,2),
    payment_method ENUM('pay_at_hotel', 'bank_transfer') DEFAULT 'pay_at_hotel',
    payment_status ENUM('pending', 'receipt_uploaded', 'verified', 'cancelled') DEFAULT 'pending',
    receipt_path VARCHAR(255),
    receipt_uploaded_at DATETIME,
    receipt_verified_by INT,
    receipt_verified_at DATETIME,
    booking_status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    special_requests TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (guest_id) REFERENCES guests(id),
    FOREIGN KEY (hall_id) REFERENCES hall(id),
    FOREIGN KEY (receipt_verified_by) REFERENCES users(id)
);

-- Media Library Table
CREATE TABLE media_library (
    id INT PRIMARY KEY AUTO_INCREMENT,
    file_name VARCHAR(255),
    file_path VARCHAR(255),
    file_type VARCHAR(50),
    file_size INT,
    category VARCHAR(50),
    uploaded_by INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

-- Site Settings Table
CREATE TABLE site_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(50) UNIQUE,
    setting_value TEXT
);

-- Bank Account Details Table (for manual payments)
CREATE TABLE bank_accounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bank_name VARCHAR(100),
    account_name VARCHAR(100),
    account_number VARCHAR(50),
    branch_details TEXT,
    is_active BOOLEAN DEFAULT TRUE
);

-- Insert Default Data
INSERT INTO site_settings (setting_key, setting_value) VALUES
('hotel_name', 'Fresh Home and Suite Hotel'),
('hotel_email', 'info@freshhomehotel.com'),
('hotel_phone', '+1234567890'),
('hotel_address', '123 Hotel Street, City'),
('whatsapp_number', '+1234567890');

INSERT INTO bank_accounts (bank_name, account_name, account_number, branch_details) VALUES
('First Bank', 'Fresh Home and Suite Hotel Ltd', '1234567890', 'Main Branch, City'),
('Access Bank', 'Fresh Home and Suite Hotel Ltd', '0987654321', 'City Mall Branch');

-- Insert Sample Rooms
INSERT INTO rooms (room_number, room_type, description, base_price, max_occupancy, bed_type, square_feet, amenities) VALUES
('101', 'Standard', 'Cozy room with city view', 5000, 2, 'Queen Bed', 250, 'TV, WiFi, AC, Mini Fridge'),
('102', 'Deluxe', 'Spacious room with ocean view', 8000, 3, 'King Bed', 350, 'TV, WiFi, AC, Mini Bar, Bathtub'),
('103', 'Suite', 'Luxury suite with separate living area', 15000, 4, 'King Bed + Sofa Bed', 500, 'TV, WiFi, AC, Jacuzzi, Mini Bar, Living Area');

-- Insert Sample Hall
INSERT INTO hall (name, description, base_price_hourly, base_price_half_day, base_price_full_day, capacity_theater, capacity_banquet, capacity_classroom, amenities) VALUES
('Grand Multi-Purpose Hall', 'Perfect for weddings, conferences, and parties', 5000, 15000, 25000, 200, 150, 120, 'Sound System, Projector, AC, Lighting');

-- Insert Sample Food Categories
INSERT INTO food_categories (name, description, display_order) VALUES
('Starters', 'Delicious appetizers', 1),
('Main Course', 'Hearty meals', 2),
('Desserts', 'Sweet treats', 3),
('Beverages', 'Refreshing drinks', 4);

-- Insert Sample Food Items
INSERT INTO food_items (category_id, name, description, price, dietary_type, is_available) VALUES
(1, 'Chicken Wings', 'Spicy grilled chicken wings', 1200, 'non_veg', true),
(1, 'Vegetable Spring Rolls', 'Crispy spring rolls with veggies', 800, 'veg', true),
(2, 'Grilled Salmon', 'Fresh salmon with herbs', 3500, 'non_veg', true),
(2, 'Paneer Butter Masala', 'Cottage cheese in creamy gravy', 1800, 'veg', true),
(3, 'Chocolate Lava Cake', 'Warm cake with molten center', 600, 'veg', true),
(4, 'Fresh Lime Soda', 'Refreshing lime drink', 300, 'veg', true);

-- Insert Default Admin User (password: admin123)
INSERT INTO users (username, email, password, full_name, role) VALUES
('superadmin', 'admin@freshhomehotel.com', '$2y$10$YourHashedPasswordHere', 'Super Admin', 'super_admin');