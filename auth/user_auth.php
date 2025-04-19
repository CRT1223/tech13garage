<?php
/**
 * User Authentication Handler
 * 
 * This file handles user registration, login, and authentication for the TECH13 Garage website.
 */

// Include database connection
require_once '../database/db_connect.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Determine which form was submitted
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    switch ($action) {
        case 'login':
            handleLogin();
            break;
        case 'register':
            handleRegistration();
            break;
        case 'logout':
            handleLogout();
            break;
        default:
            // Invalid action
            respondWithError('Invalid action specified');
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'logout') {
    // Handle logout via GET request
    handleLogout();
} else {
    // Only POST requests are allowed for login/register
    respondWithError('Invalid request method');
}

/**
 * Handle user login
 */
function handleLogin() {
    // Get form data
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['rememberMe']) && $_POST['rememberMe'] === 'on';
    
    // Validate input
    if (empty($email) || empty($password)) {
        respondWithError('Email and password are required');
    }
    
    try {
        // Check if user exists
        $sql = "SELECT * FROM users WHERE email = :email";
        $user = fetchRow($sql, ['email' => $email]);
        
        if (!$user) {
            respondWithError('Invalid email or password');
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            respondWithError('Invalid email or password');
        }
        
        // Update last login time
        $updateSql = "UPDATE users SET last_login = NOW() WHERE user_id = :id";
        executeQuery($updateSql, ['id' => $user['user_id']]);
        
        // Set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        
        // Set remember me cookie if requested
        if ($remember) {
            // Generate a token and store it in the database (simplified for this example)
            $token = bin2hex(random_bytes(32));
            $expires = time() + (30 * 24 * 60 * 60); // 30 days
            
            setcookie('remember_token', $token, $expires, '/', '', true, true);
            
            // In a real application, store this token securely in the database
            // along with the user ID and expiration time
        }
        
        // Redirect based on user role
        $redirectUrl = '../index.html';
        if ($user['role'] === 'admin') {
            $redirectUrl = '../admin/dashboard.php';
        } else if ($user['role'] === 'customer') {
            $redirectUrl = '../customer-dashboard.php';
        }
        
        // Respond with success
        respondWithSuccess('Login successful', [
            'redirect' => $redirectUrl
        ]);
        
    } catch (Exception $e) {
        respondWithError('An error occurred: ' . $e->getMessage());
    }
}

/**
 * Handle user registration
 */
function handleRegistration() {
    // Get form data
    $fullName = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $termsAgree = isset($_POST['termsAgree']) && $_POST['termsAgree'] === 'on';
    
    // Validate input
    if (empty($fullName) || empty($email) || empty($phone) || empty($password)) {
        respondWithError('All fields are required');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respondWithError('Invalid email format');
    }
    
    if (strlen($password) < 8) {
        respondWithError('Password must be at least 8 characters long');
    }
    
    if ($password !== $confirmPassword) {
        respondWithError('Passwords do not match');
    }
    
    if (!$termsAgree) {
        respondWithError('You must agree to the terms and conditions');
    }
    
    try {
        // Check if email already exists
        $sql = "SELECT user_id FROM users WHERE email = :email";
        $existingUser = fetchRow($sql, ['email' => $email]);
        
        if ($existingUser) {
            respondWithError('Email address is already registered');
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $userData = [
            'full_name' => $fullName,
            'email' => $email,
            'phone' => $phone,
            'password' => $hashedPassword,
            'role' => 'customer'
        ];
        
        $userId = insert('users', $userData);
        
        if (!$userId) {
            respondWithError('Failed to create user account');
        }
        
        // Set session variables
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $fullName;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = 'customer';
        
        // Respond with success
        respondWithSuccess('Registration successful', [
            'redirect' => '../index.html'
        ]);
        
    } catch (Exception $e) {
        respondWithError('An error occurred: ' . $e->getMessage());
    }
}

/**
 * Handle user logout
 */
function handleLogout() {
    // Clear session
    session_unset();
    session_destroy();
    
    // Clear any cookies
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
    // Respond with success
    respondWithSuccess('Logout successful', [
        'redirect' => '../index.html'
    ]);
}

/**
 * Check if user is logged in
 *
 * @return bool Whether the user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Get current user data
 *
 * @return array|null User data or null if not logged in
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $sql = "SELECT user_id, full_name, email, phone, role, profile_image, created_at, last_login 
            FROM users WHERE user_id = :id";
    return fetchRow($sql, ['id' => $_SESSION['user_id']]);
}

/**
 * Check if current user has required role
 *
 * @param string|array $roles Required role(s)
 * @return bool Whether user has required role
 */
function hasRole($roles) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $userRole = $_SESSION['user_role'];
    
    if (is_array($roles)) {
        return in_array($userRole, $roles);
    }
    
    return $userRole === $roles;
}

/**
 * Send JSON response with error
 *
 * @param string $message Error message
 */
function respondWithError($message) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}

/**
 * Send JSON response with success
 *
 * @param string $message Success message
 * @param array $data Additional data
 */
function respondWithSuccess($message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
    exit;
} 