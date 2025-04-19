-- TECH13 Garage Database Schema
-- This SQL script creates the database structure for the TECH13 Garage website

-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS tech13_garage;

-- Use the database
USE tech13_garage;

-- Users Table - Stores user account information
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL, -- Will store hashed passwords
    role ENUM('customer', 'technician', 'admin') DEFAULT 'customer',
    profile_image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Services Table - Stores information about available services
CREATE TABLE IF NOT EXISTS services (
    service_id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    base_price DECIMAL(10, 2) NOT NULL,
    duration INT NOT NULL, -- In minutes
    category ENUM('ECU Remapping', 'CVT Tuning', 'Fuel Injector Cleaning', 'Engine Overhaul', 'Engine Upgrade', 'Other') NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Vehicles Table - Stores information about customer vehicles
CREATE TABLE IF NOT EXISTS vehicles (
    vehicle_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    make VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    year INT NOT NULL,
    license_plate VARCHAR(20) DEFAULT NULL,
    vin VARCHAR(50) DEFAULT NULL,
    engine_type VARCHAR(50) DEFAULT NULL,
    transmission_type VARCHAR(50) DEFAULT NULL,
    color VARCHAR(30) DEFAULT NULL,
    mileage INT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Appointments Table - Stores service appointments
CREATE TABLE IF NOT EXISTS appointments (
    appointment_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    service_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('pending', 'confirmed', 'in_progress', 'completed', 'canceled') DEFAULT 'pending',
    technician_id INT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(service_id) ON DELETE CASCADE,
    FOREIGN KEY (technician_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Invoices Table - Stores billing information
CREATE TABLE IF NOT EXISTS invoices (
    invoice_id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    user_id INT NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    tax DECIMAL(10, 2) NOT NULL,
    discount DECIMAL(10, 2) DEFAULT 0.00,
    total DECIMAL(10, 2) NOT NULL,
    payment_status ENUM('pending', 'paid', 'refunded', 'failed') DEFAULT 'pending',
    payment_method ENUM('credit_card', 'debit_card', 'cash', 'bank_transfer', 'other') DEFAULT NULL,
    payment_date TIMESTAMP NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Service_Details Table - Stores details about services performed
CREATE TABLE IF NOT EXISTS service_details (
    detail_id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    service_id INT NOT NULL,
    description TEXT NOT NULL,
    parts_used TEXT DEFAULT NULL,
    labor_hours DECIMAL(5, 2) DEFAULT NULL,
    price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(service_id) ON DELETE CASCADE
);

-- Testimonials Table - Stores customer reviews and feedback
CREATE TABLE IF NOT EXISTS testimonials (
    testimonial_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    service_id INT DEFAULT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT NOT NULL,
    is_approved BOOLEAN DEFAULT FALSE,
    is_featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(service_id) ON DELETE SET NULL
);

-- Gallery Table - Stores images for the website gallery
CREATE TABLE IF NOT EXISTS gallery (
    gallery_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    image_path VARCHAR(255) NOT NULL,
    category VARCHAR(50) DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Offers Table - Stores promotional offers
CREATE TABLE IF NOT EXISTS offers (
    offer_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    discount_type ENUM('percentage', 'fixed_amount') NOT NULL,
    discount_value DECIMAL(10, 2) NOT NULL,
    applicable_services VARCHAR(255) DEFAULT NULL, -- Comma separated service_ids or NULL for all services
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    promo_code VARCHAR(20) DEFAULT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert basic services data
INSERT INTO services (service_name, description, base_price, duration, category, is_active) VALUES
('Basic ECU Remapping', 'Standard ECU remapping to improve vehicle performance and fuel efficiency.', 299.99, 120, 'ECU Remapping', TRUE),
('Advanced ECU Remapping', 'Advanced ECU remapping with custom specifications for maximum performance.', 499.99, 180, 'ECU Remapping', TRUE),
('CVT Tuning Basic', 'Basic CVT tuning to improve transmission response and efficiency.', 249.99, 120, 'CVT Tuning', TRUE),
('CVT Tuning Premium', 'Premium CVT tuning with advanced adjustments for optimal performance.', 399.99, 180, 'CVT Tuning', TRUE),
('Fuel Injector Cleaning', 'Professional cleaning of fuel injectors to improve engine performance and fuel economy.', 149.99, 90, 'Fuel Injector Cleaning', TRUE),
('Basic Engine Overhaul', 'Basic engine overhaul service to restore engine performance.', 899.99, 480, 'Engine Overhaul', TRUE),
('Complete Engine Overhaul', 'Complete engine overhaul with replacement of worn components.', 1499.99, 720, 'Engine Overhaul', TRUE),
('Engine Performance Upgrade', 'Engine modifications to increase power and performance.', 1299.99, 360, 'Engine Upgrade', TRUE);

-- Insert sample admin user
INSERT INTO users (full_name, email, phone, password, role) VALUES 
('Admin User', 'admin@tech13garage.com', '123-456-7890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'); -- password: password

-- Create a PHP database connection file
CREATE TABLE IF NOT EXISTS settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    setting_name VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default settings
INSERT INTO settings (setting_name, setting_value) VALUES
('site_name', 'TECH13 GARAGE'),
('contact_email', 'info@tech13garage.com'),
('contact_phone', '123-456-7890'),
('address', '123 Auto Street, Manila, Philippines'),
('business_hours', 'Monday-Friday: 8:00 AM - 6:00 PM, Saturday: 9:00 AM - 3:00 PM, Sunday: Closed'),
('facebook_url', 'https://www.facebook.com/tech13garage'),
('instagram_url', 'https://www.instagram.com/tech13garage'); 