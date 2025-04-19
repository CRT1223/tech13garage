<?php
// Start session
session_start();

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'customer') {
    // Redirect to login page if not logged in or not a customer
    header('Location: index.html');
    exit;
}

// Include database connection
require_once 'database/db_connect.php';

// Fetch user data
$userData = null;
if (isset($_SESSION['user_id'])) {
    $sql = "SELECT user_id, full_name, email, phone, profile_image, created_at 
            FROM users WHERE user_id = :id";
    $userData = fetchRow($sql, ['id' => $_SESSION['user_id']]);
}

// Fetch user vehicles
$vehicles = [];
if (isset($_SESSION['user_id'])) {
    $sql = "SELECT * FROM vehicles WHERE user_id = :user_id ORDER BY created_at DESC";
    $vehicles = fetchAll($sql, ['user_id' => $_SESSION['user_id']]);
}

// Handle profile update
$updateMessage = '';
$updateSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullName = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate input
    if (empty($fullName) || empty($phone)) {
        $updateMessage = 'Name and phone are required';
    } else {
        try {
            // Start with basic profile update
            $updateData = [
                'full_name' => $fullName,
                'phone' => $phone,
                'user_id' => $_SESSION['user_id']
            ];
            
            // If changing password
            if (!empty($currentPassword) && !empty($newPassword)) {
                // Verify current password
                $user = fetchRow("SELECT password FROM users WHERE user_id = :id", ['id' => $_SESSION['user_id']]);
                
                if (!password_verify($currentPassword, $user['password'])) {
                    $updateMessage = 'Current password is incorrect';
                } else if ($newPassword !== $confirmPassword) {
                    $updateMessage = 'New passwords do not match';
                } else if (strlen($newPassword) < 8) {
                    $updateMessage = 'New password must be at least 8 characters';
                } else {
                    // Add password to update data
                    $updateData['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
                }
            }
            
            // If no errors, update profile
            if (empty($updateMessage)) {
                $fields = [];
                $params = [];
                
                foreach ($updateData as $key => $value) {
                    if ($key !== 'user_id') {
                        $fields[] = "$key = :$key";
                    }
                    $params[$key] = $value;
                }
                
                $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE user_id = :user_id";
                executeQuery($sql, $params);
                
                // Update session data
                $_SESSION['user_name'] = $fullName;
                
                // Refresh user data
                $sql = "SELECT user_id, full_name, email, phone, profile_image, created_at 
                        FROM users WHERE user_id = :id";
                $userData = fetchRow($sql, ['id' => $_SESSION['user_id']]);
                
                $updateMessage = 'Profile updated successfully';
                $updateSuccess = true;
            }
        } catch (Exception $e) {
            $updateMessage = 'An error occurred: ' . $e->getMessage();
        }
    }
}

// Handle vehicle addition
$vehicleMessage = '';
$vehicleSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_vehicle'])) {
    $make = filter_input(INPUT_POST, 'make', FILTER_SANITIZE_STRING);
    $model = filter_input(INPUT_POST, 'model', FILTER_SANITIZE_STRING);
    $year = filter_input(INPUT_POST, 'year', FILTER_VALIDATE_INT);
    $licensePlate = filter_input(INPUT_POST, 'license_plate', FILTER_SANITIZE_STRING);
    $engineType = filter_input(INPUT_POST, 'engine_type', FILTER_SANITIZE_STRING);
    $transmissionType = filter_input(INPUT_POST, 'transmission_type', FILTER_SANITIZE_STRING);
    
    // Validate input
    if (empty($make) || empty($model) || empty($year)) {
        $vehicleMessage = 'Make, model, and year are required';
    } else {
        try {
            // Insert new vehicle
            $vehicleData = [
                'user_id' => $_SESSION['user_id'],
                'make' => $make,
                'model' => $model,
                'year' => $year,
                'license_plate' => $licensePlate,
                'engine_type' => $engineType,
                'transmission_type' => $transmissionType
            ];
            
            insert('vehicles', $vehicleData);
            
            // Refresh vehicles
            $sql = "SELECT * FROM vehicles WHERE user_id = :user_id ORDER BY created_at DESC";
            $vehicles = fetchAll($sql, ['user_id' => $_SESSION['user_id']]);
            
            $vehicleMessage = 'Vehicle added successfully';
            $vehicleSuccess = true;
        } catch (Exception $e) {
            $vehicleMessage = 'An error occurred: ' . $e->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="UTF-8">

    <!--Page Title-->
    <title>My Profile - TECH13</title>

    <!--Meta Keywords and Description-->
    <meta name="keywords" content="">
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no"/>

    <!--Favicon-->
    <link rel="shortcut icon" href="images/favicon.ico" title="Favicon"/>

    <!-- Main CSS Files -->
    <link rel="stylesheet" href="css/style.css">

    <!-- Namari Color CSS -->
    <link rel="stylesheet" href="css/namari-color.css">

    <!--Icon Fonts - Font Awesome Icons-->
    <link rel="stylesheet" href="css/font-awesome.min.css">

    <!-- Animate CSS-->
    <link href="css/animate.css" rel="stylesheet" type="text/css">

    <!-- Dashboard CSS -->
    <link href="css/dashboard.css" rel="stylesheet" type="text/css">

    <!--Google Webfonts-->
    <link href='https://fonts.googleapis.com/css?family=Open+Sans:400,300,600,700,800' rel='stylesheet' type='text/css'>
</head>
<body>

<!-- Preloader -->
<div id="preloader">
    <div id="status" class="la-ball-triangle-path">
        <div></div>
        <div></div>
        <div></div>
    </div>
</div>
<!--End of Preloader-->

<div class="page-border" data-wow-duration="0.7s" data-wow-delay="0.2s">
    <div class="top-border wow fadeInDown animated" style="visibility: visible; animation-name: fadeInDown;"></div>
    <div class="right-border wow fadeInRight animated" style="visibility: visible; animation-name: fadeInRight;"></div>
    <div class="bottom-border wow fadeInUp animated" style="visibility: visible; animation-name: fadeInUp;"></div>
    <div class="left-border wow fadeInLeft animated" style="visibility: visible; animation-name: fadeInLeft;"></div>
</div>

<div id="wrapper">

    <header id="dashboard-header" class="clearfix">
        <div id="header" class="nav-collapse">
            <div class="row clearfix">
                <div class="col-1">

                    <!--Logo-->
                    <div id="logo">
                        <!--Logo that is shown on the banner-->
                        <h1>TECH13 GARAGE</h1>
                        <!--End of Navigation Logo-->
                    </div>
                    <!--End of Logo-->

                    <!--Main Navigation-->
                    <nav id="nav-main">
                        <ul>
                            <li>
                                <a href="customer-dashboard.php">Dashboard</a>
                            </li>
                            <li>
                                <a href="profile.php" class="active">Profile</a>
                            </li>
                            <li>
                                <a href="reservations.php">Reservations</a>
                            </li>
                            <li>
                                <a href="prizes.php">Prizes</a>
                            </li>
                            <li>
                                <a href="auth/user_auth.php?action=logout" class="logout-btn">Logout</a>
                            </li>
                        </ul>
                    </nav>
                    <!--End of Main Navigation-->

                    <div id="nav-trigger"><span></span></div>
                    <nav id="nav-mobile"></nav>

                </div>
            </div>
        </div>
    </header>

    <!--Main Content Area-->
    <main id="content" class="dashboard-content">
        <div class="container">
            <div class="row clearfix">
                <!-- Profile Information Section -->
                <div class="col-2-3">
                    <section class="profile-edit-section">
                        <div class="section-heading">
                            <h2>My Profile</h2>
                        </div>
                        
                        <?php if (!empty($updateMessage)): ?>
                            <div class="message-container <?php echo $updateSuccess ? 'success' : 'error'; ?>">
                                <?php echo $updateMessage; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="profile-card">
                            <form action="profile.php" method="post">
                                <div class="form-group">
                                    <label for="full_name">Full Name</label>
                                    <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($userData['full_name'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email Address</label>
                                    <input type="email" id="email" value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>" disabled>
                                    <p class="form-note">Email cannot be changed. Contact support if needed.</p>
                                </div>
                                
                                <div class="form-group">
                                    <label for="phone">Phone Number</label>
                                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="current_password">Current Password (only if changing password)</label>
                                    <input type="password" id="current_password" name="current_password">
                                </div>
                                
                                <div class="form-group">
                                    <label for="new_password">New Password</label>
                                    <input type="password" id="new_password" name="new_password">
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirm_password">Confirm New Password</label>
                                    <input type="password" id="confirm_password" name="confirm_password">
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" name="update_profile" class="button">Update Profile</button>
                                </div>
                            </form>
                        </div>
                    </section>
                    
                    <!-- My Vehicles Section -->
                    <section class="vehicles-section">
                        <div class="section-heading">
                            <h2>My Vehicles</h2>
                        </div>
                        
                        <?php if (!empty($vehicleMessage)): ?>
                            <div class="message-container <?php echo $vehicleSuccess ? 'success' : 'error'; ?>">
                                <?php echo $vehicleMessage; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="profile-card">
                            <div class="vehicle-list">
                                <?php if (empty($vehicles)): ?>
                                    <p class="no-data">No vehicles added yet.</p>
                                <?php else: ?>
                                    <?php foreach ($vehicles as $vehicle): ?>
                                        <div class="vehicle-item">
                                            <div class="vehicle-icon">
                                                <i class="fa fa-car"></i>
                                            </div>
                                            <div class="vehicle-details">
                                                <h3><?php echo htmlspecialchars($vehicle['year'] . ' ' . $vehicle['make'] . ' ' . $vehicle['model']); ?></h3>
                                                <div class="vehicle-info">
                                                    <?php if (!empty($vehicle['license_plate'])): ?>
                                                        <p><strong>License Plate:</strong> <?php echo htmlspecialchars($vehicle['license_plate']); ?></p>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!empty($vehicle['engine_type'])): ?>
                                                        <p><strong>Engine:</strong> <?php echo htmlspecialchars($vehicle['engine_type']); ?></p>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!empty($vehicle['transmission_type'])): ?>
                                                        <p><strong>Transmission:</strong> <?php echo htmlspecialchars($vehicle['transmission_type']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="vehicle-actions">
                                                <a href="edit_vehicle.php?id=<?php echo $vehicle['vehicle_id']; ?>" class="button small">Edit</a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="add-vehicle-form">
                                <h3>Add New Vehicle</h3>
                                <form action="profile.php" method="post">
                                    <div class="row clearfix">
                                        <div class="col-3">
                                            <div class="form-group">
                                                <label for="make">Make</label>
                                                <input type="text" id="make" name="make" required>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="form-group">
                                                <label for="model">Model</label>
                                                <input type="text" id="model" name="model" required>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="form-group">
                                                <label for="year">Year</label>
                                                <input type="number" id="year" name="year" min="1900" max="2099" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row clearfix">
                                        <div class="col-3">
                                            <div class="form-group">
                                                <label for="license_plate">License Plate</label>
                                                <input type="text" id="license_plate" name="license_plate">
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="form-group">
                                                <label for="engine_type">Engine Type</label>
                                                <input type="text" id="engine_type" name="engine_type">
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="form-group">
                                                <label for="transmission_type">Transmission</label>
                                                <select id="transmission_type" name="transmission_type">
                                                    <option value="">Select Transmission</option>
                                                    <option value="Automatic">Automatic</option>
                                                    <option value="Manual">Manual</option>
                                                    <option value="CVT">CVT</option>
                                                    <option value="Semi-Automatic">Semi-Automatic</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="submit" name="add_vehicle" class="button">Add Vehicle</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </section>
                </div>
                
                <!-- Sidebar -->
                <div class="col-3">
                    <div class="dashboard-sidebar">
                        <div class="user-card">
                            <div class="profile-image">
                                <?php if ($userData && $userData['profile_image']): ?>
                                    <img src="<?php echo htmlspecialchars($userData['profile_image']); ?>" alt="Profile Image">
                                <?php else: ?>
                                    <img src="images/default-profile.jpg" alt="Default Profile">
                                <?php endif; ?>
                            </div>
                            <div class="profile-info">
                                <h2><?php echo htmlspecialchars($userData['full_name'] ?? 'Customer'); ?></h2>
                                <p><i class="fa fa-envelope"></i> <?php echo htmlspecialchars($userData['email'] ?? 'Email not available'); ?></p>
                                <p><i class="fa fa-phone"></i> <?php echo htmlspecialchars($userData['phone'] ?? 'Phone not available'); ?></p>
                                <p><i class="fa fa-calendar"></i> Member since: <?php echo date('M d, Y', strtotime($userData['created_at'] ?? 'now')); ?></p>
                            </div>
                        </div>
                        
                        <div class="sidebar-section">
                            <h3>Quick Links</h3>
                            <ul class="sidebar-nav">
                                <li><a href="customer-dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a></li>
                                <li><a href="profile.php" class="active"><i class="fa fa-user"></i> My Profile</a></li>
                                <li><a href="reservations.php"><i class="fa fa-calendar"></i> My Reservations</a></li>
                                <li><a href="prizes.php"><i class="fa fa-gift"></i> Prizes & Offers</a></li>
                                <li><a href="service_history.php"><i class="fa fa-history"></i> Service History</a></li>
                                <li><a href="schedule_appointment.php"><i class="fa fa-plus-circle"></i> Schedule Service</a></li>
                            </ul>
                        </div>
                        
                        <div class="sidebar-section">
                            <h3>Need Help?</h3>
                            <p>Contact our customer support team if you have any questions or need assistance.</p>
                            <a href="contact.php" class="button full-width">Contact Support</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <!--End Main Content Area-->

    <!--Footer-->
    <footer id="landing-footer" class="clearfix">
        <div class="row clearfix">

            <p id="copyright" class="col-2">Made with love by <a href="https://www.facebook.com/profile.php?id=61561640515884">TECH13 GARAGE</a></p>

            <!--Social Icons in Footer-->
            <ul class="col-2 social-icons">
                <li>
                    <a target="_blank" title="Facebook" href="https://www.facebook.com/peterluigi.nelmida.5">
                        <i class="fa fa-facebook fa-1x"></i><span>Facebook</span>
                    </a>
                </li>
                <li>
                    <a target="_blank" title="Google+" href="http://google.com/+username">
                        <i class="fa fa-google-plus fa-1x"></i><span>Google+</span>
                    </a>
                </li>
                <li>
                    <a target="_blank" title="Twitter" href="http://www.twitter.com/username">
                        <i class="fa fa-twitter fa-1x"></i><span>Twitter</span>
                    </a>
                </li>
                <li>
                    <a target="_blank" title="Instagram" href="http://www.instagram.com/username">
                        <i class="fa fa-instagram fa-1x"></i><span>Instagram</span>
                    </a>
                </li>
            </ul>
            <!--End of Social Icons in Footer-->
        </div>
    </footer>
    <!--End of Footer-->

</div>

<!-- Include JavaScript resources -->
<script src="js/jquery.1.8.3.min.js"></script>
<script src="js/wow.min.js"></script>
<script src="js/featherlight.min.js"></script>
<script src="js/jquery.enllax.min.js"></script>
<script src="js/jquery.scrollUp.min.js"></script>
<script src="js/jquery.easing.min.js"></script>
<script src="js/jquery.stickyNavbar.min.js"></script>
<script src="js/jquery.waypoints.min.js"></script>
<script src="js/site.js"></script>

</body>
</html> 