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
    $sql = "SELECT user_id, full_name, email, phone, profile_image 
            FROM users WHERE user_id = :id";
    $userData = fetchRow($sql, ['id' => $_SESSION['user_id']]);
}

// Fetch upcoming appointments
$appointments = [];
if (isset($_SESSION['user_id'])) {
    $sql = "SELECT a.*, v.make, v.model, s.service_name 
            FROM appointments a
            JOIN vehicles v ON a.vehicle_id = v.vehicle_id
            JOIN services s ON a.service_id = s.service_id
            WHERE a.user_id = :user_id AND a.status IN ('pending', 'confirmed')
            ORDER BY a.appointment_date, a.appointment_time
            LIMIT 5";
    $appointments = fetchAll($sql, ['user_id' => $_SESSION['user_id']]);
}

// Fetch completed services
$completedServices = [];
if (isset($_SESSION['user_id'])) {
    $sql = "SELECT a.*, v.make, v.model, s.service_name 
            FROM appointments a
            JOIN vehicles v ON a.vehicle_id = v.vehicle_id
            JOIN services s ON a.service_id = s.service_id
            WHERE a.user_id = :user_id AND a.status = 'completed'
            ORDER BY a.appointment_date DESC
            LIMIT 5";
    $completedServices = fetchAll($sql, ['user_id' => $_SESSION['user_id']]);
}

// Fetch dashboard statistics
$stats = [
    'upcoming' => 0,
    'completed' => 0,
    'in_progress' => 0
];

if (isset($_SESSION['user_id'])) {
    // Count upcoming appointments
    $sql = "SELECT COUNT(*) as count FROM appointments 
            WHERE user_id = :user_id AND status IN ('pending', 'confirmed')";
    $result = fetchRow($sql, ['user_id' => $_SESSION['user_id']]);
    $stats['upcoming'] = $result ? $result['count'] : 0;
    
    // Count completed services
    $sql = "SELECT COUNT(*) as count FROM appointments 
            WHERE user_id = :user_id AND status = 'completed'";
    $result = fetchRow($sql, ['user_id' => $_SESSION['user_id']]);
    $stats['completed'] = $result ? $result['count'] : 0;
    
    // Count services in progress
    $sql = "SELECT COUNT(*) as count FROM appointments 
            WHERE user_id = :user_id AND status = 'in_progress'";
    $result = fetchRow($sql, ['user_id' => $_SESSION['user_id']]);
    $stats['in_progress'] = $result ? $result['count'] : 0;
}
?>

<!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="UTF-8">

    <!--Page Title-->
    <title>Customer Dashboard - TECH13</title>

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
                                <a href="#" class="active">Dashboard</a>
                            </li>
                            <li>
                                <a href="profile.php">Profile</a>
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
            <!-- User Profile Section -->
            <section class="profile-section">
                <div class="row clearfix">
                    <div class="col-4">
                        <div class="user-profile">
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
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Dashboard Statistics -->
            <section class="stats-section">
                <div class="row clearfix">
                    <div class="col-3">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fa fa-calendar"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $stats['upcoming']; ?></h3>
                                <p>Upcoming Appointments</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fa fa-check-circle"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $stats['completed']; ?></h3>
                                <p>Completed Services</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fa fa-wrench"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $stats['in_progress']; ?></h3>
                                <p>Services In Progress</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Upcoming Appointments Section -->
            <section class="appointments-section">
                <div class="section-heading">
                    <h2>Upcoming Appointments</h2>
                </div>
                <div class="responsive-table">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Vehicle</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($appointments)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No upcoming appointments</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($appointments as $appointment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($appointment['service_name']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['make'] . ' ' . $appointment['model']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></td>
                                        <td><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $appointment['status']; ?>">
                                                <?php echo ucfirst($appointment['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="view_appointment.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn-view" title="View Details">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <?php if ($appointment['status'] === 'pending'): ?>
                                                    <a href="edit_appointment.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn-edit" title="Edit Appointment">
                                                        <i class="fa fa-pencil"></i>
                                                    </a>
                                                    <a href="cancel_appointment.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn-delete" title="Cancel Appointment">
                                                        <i class="fa fa-times"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="view-all-link">
                    <a href="reservations.php" class="button">View All Appointments</a>
                </div>
            </section>

            <!-- Recent Service History -->
            <section class="service-history-section">
                <div class="section-heading">
                    <h2>Recent Service History</h2>
                </div>
                <div class="responsive-table">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Vehicle</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($completedServices)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">No service history available</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($completedServices as $service): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($service['service_name']); ?></td>
                                        <td><?php echo htmlspecialchars($service['make'] . ' ' . $service['model']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($service['appointment_date'])); ?></td>
                                        <td>
                                            <span class="status-badge status-completed">
                                                Completed
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="view_service.php?id=<?php echo $service['appointment_id']; ?>" class="btn-view" title="View Details">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="view-all-link">
                    <a href="service_history.php" class="button">View Full History</a>
                </div>
            </section>

            <!-- Quick Actions -->
            <section class="quick-actions-section">
                <div class="section-heading">
                    <h2>Quick Actions</h2>
                </div>
                <div class="row clearfix">
                    <div class="col-3">
                        <div class="action-card">
                            <div class="action-icon">
                                <i class="fa fa-calendar-plus-o"></i>
                            </div>
                            <h3>Schedule Appointment</h3>
                            <p>Book a new service appointment for your vehicle</p>
                            <a href="schedule_appointment.php" class="button">Schedule Now</a>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="action-card">
                            <div class="action-icon">
                                <i class="fa fa-user"></i>
                            </div>
                            <h3>Update Profile</h3>
                            <p>Update your personal information and preferences</p>
                            <a href="profile.php" class="button">Update Profile</a>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="action-card">
                            <div class="action-icon">
                                <i class="fa fa-support"></i>
                            </div>
                            <h3>Contact Support</h3>
                            <p>Need help? Contact our customer support team</p>
                            <a href="contact.php" class="button">Contact Us</a>
                        </div>
                    </div>
                </div>
            </section>
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

<script>
    // Logout confirmation
    document.querySelector('.logout-btn').addEventListener('click', function(e) {
        if (!confirm('Are you sure you want to logout?')) {
            e.preventDefault();
        }
    });
</script>

</body>
</html> 