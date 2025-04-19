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

// Fetch all appointments
$appointments = [];
if (isset($_SESSION['user_id'])) {
    $sql = "SELECT a.*, v.make, v.model, s.service_name, s.category, s.base_price 
            FROM appointments a
            JOIN vehicles v ON a.vehicle_id = v.vehicle_id
            JOIN services s ON a.service_id = s.service_id
            WHERE a.user_id = :user_id
            ORDER BY 
                CASE 
                    WHEN a.status = 'confirmed' THEN 1
                    WHEN a.status = 'pending' THEN 2
                    WHEN a.status = 'in_progress' THEN 3
                    WHEN a.status = 'completed' THEN 4
                    WHEN a.status = 'canceled' THEN 5
                END,
                a.appointment_date ASC, 
                a.appointment_time ASC";
    $appointments = fetchAll($sql, ['user_id' => $_SESSION['user_id']]);
}

// Get available services for reservation
$services = [];
$sql = "SELECT * FROM services WHERE is_active = TRUE ORDER BY category, base_price";
$services = fetchAll($sql);

// Group services by category
$servicesByCategory = [];
foreach ($services as $service) {
    if (!isset($servicesByCategory[$service['category']])) {
        $servicesByCategory[$service['category']] = [];
    }
    $servicesByCategory[$service['category']][] = $service;
}

// Fetch user vehicles
$vehicles = [];
if (isset($_SESSION['user_id'])) {
    $sql = "SELECT * FROM vehicles WHERE user_id = :user_id ORDER BY make, model";
    $vehicles = fetchAll($sql, ['user_id' => $_SESSION['user_id']]);
}

// Handle appointment booking
$bookingMessage = '';
$bookingSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    $serviceId = filter_input(INPUT_POST, 'service_id', FILTER_VALIDATE_INT);
    $vehicleId = filter_input(INPUT_POST, 'vehicle_id', FILTER_VALIDATE_INT);
    $appointmentDate = filter_input(INPUT_POST, 'appointment_date', FILTER_SANITIZE_STRING);
    $appointmentTime = filter_input(INPUT_POST, 'appointment_time', FILTER_SANITIZE_STRING);
    $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);
    
    // Validate input
    if (!$serviceId || !$vehicleId || empty($appointmentDate) || empty($appointmentTime)) {
        $bookingMessage = 'All fields are required';
    } else {
        try {
            // Check if date is in the future
            $selectedDate = new DateTime($appointmentDate);
            $today = new DateTime();
            $today->setTime(0, 0, 0);
            
            if ($selectedDate < $today) {
                $bookingMessage = 'Appointment date must be in the future';
            } else {
                // Insert new appointment
                $appointmentData = [
                    'user_id' => $_SESSION['user_id'],
                    'vehicle_id' => $vehicleId,
                    'service_id' => $serviceId,
                    'appointment_date' => $appointmentDate,
                    'appointment_time' => $appointmentTime,
                    'status' => 'pending',
                    'notes' => $notes
                ];
                
                insert('appointments', $appointmentData);
                
                // Refresh appointments
                $sql = "SELECT a.*, v.make, v.model, s.service_name, s.category, s.base_price 
                        FROM appointments a
                        JOIN vehicles v ON a.vehicle_id = v.vehicle_id
                        JOIN services s ON a.service_id = s.service_id
                        WHERE a.user_id = :user_id
                        ORDER BY 
                            CASE 
                                WHEN a.status = 'confirmed' THEN 1
                                WHEN a.status = 'pending' THEN 2
                                WHEN a.status = 'in_progress' THEN 3
                                WHEN a.status = 'completed' THEN 4
                                WHEN a.status = 'canceled' THEN 5
                            END,
                            a.appointment_date ASC, 
                            a.appointment_time ASC";
                $appointments = fetchAll($sql, ['user_id' => $_SESSION['user_id']]);
                
                $bookingMessage = 'Appointment booked successfully';
                $bookingSuccess = true;
            }
        } catch (Exception $e) {
            $bookingMessage = 'An error occurred: ' . $e->getMessage();
        }
    }
}

// Handle appointment cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_appointment'])) {
    $appointmentId = filter_input(INPUT_POST, 'appointment_id', FILTER_VALIDATE_INT);
    
    if (!$appointmentId) {
        $bookingMessage = 'Invalid appointment';
    } else {
        try {
            // Check if appointment belongs to user and is not already completed/canceled
            $sql = "SELECT * FROM appointments WHERE appointment_id = :id AND user_id = :user_id AND status IN ('pending', 'confirmed')";
            $appointment = fetchRow($sql, ['id' => $appointmentId, 'user_id' => $_SESSION['user_id']]);
            
            if (!$appointment) {
                $bookingMessage = 'Cannot cancel this appointment';
            } else {
                // Update appointment status
                $sql = "UPDATE appointments SET status = 'canceled' WHERE appointment_id = :id";
                executeQuery($sql, ['id' => $appointmentId]);
                
                // Refresh appointments
                $sql = "SELECT a.*, v.make, v.model, s.service_name, s.category, s.base_price 
                        FROM appointments a
                        JOIN vehicles v ON a.vehicle_id = v.vehicle_id
                        JOIN services s ON a.service_id = s.service_id
                        WHERE a.user_id = :user_id
                        ORDER BY 
                            CASE 
                                WHEN a.status = 'confirmed' THEN 1
                                WHEN a.status = 'pending' THEN 2
                                WHEN a.status = 'in_progress' THEN 3
                                WHEN a.status = 'completed' THEN 4
                                WHEN a.status = 'canceled' THEN 5
                            END,
                            a.appointment_date ASC, 
                            a.appointment_time ASC";
                $appointments = fetchAll($sql, ['user_id' => $_SESSION['user_id']]);
                
                $bookingMessage = 'Appointment canceled successfully';
                $bookingSuccess = true;
            }
        } catch (Exception $e) {
            $bookingMessage = 'An error occurred: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="UTF-8">

    <!--Page Title-->
    <title>My Reservations - TECH13</title>

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
                                <a href="profile.php">Profile</a>
                            </li>
                            <li>
                                <a href="reservations.php" class="active">Reservations</a>
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
                <!-- Appointments & Booking Section -->
                <div class="col-2-3">
                    <section class="appointments-section">
                        <div class="section-heading">
                            <h2>My Reservations</h2>
                        </div>
                        
                        <?php if (!empty($bookingMessage)): ?>
                            <div class="message-container <?php echo $bookingSuccess ? 'success' : 'error'; ?>">
                                <?php echo $bookingMessage; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="reservation-card">
                            <div class="reservation-tabs">
                                <button class="tab-button active" data-tab="all">All</button>
                                <button class="tab-button" data-tab="upcoming">Upcoming</button>
                                <button class="tab-button" data-tab="completed">Completed</button>
                                <button class="tab-button" data-tab="canceled">Canceled</button>
                            </div>
                            
                            <div class="responsive-table">
                                <table class="table" id="appointments-table">
                                    <thead>
                                        <tr>
                                            <th>Service</th>
                                            <th>Vehicle</th>
                                            <th>Date & Time</th>
                                            <th>Price</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($appointments)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No appointments found</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($appointments as $appointment): ?>
                                                <tr data-status="<?php echo $appointment['status']; ?>">
                                                    <td>
                                                        <div class="service-info">
                                                            <div class="service-name"><?php echo htmlspecialchars($appointment['service_name']); ?></div>
                                                            <div class="service-category"><?php echo htmlspecialchars($appointment['category']); ?></div>
                                                        </div>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($appointment['make'] . ' ' . $appointment['model']); ?></td>
                                                    <td>
                                                        <div class="date-time">
                                                            <div class="date"><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></div>
                                                            <div class="time"><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></div>
                                                        </div>
                                                    </td>
                                                    <td>₱<?php echo number_format($appointment['base_price'], 2); ?></td>
                                                    <td>
                                                        <span class="status-badge status-<?php echo $appointment['status']; ?>">
                                                            <?php 
                                                                $statusText = '';
                                                                switch($appointment['status']) {
                                                                    case 'pending': $statusText = 'Pending'; break;
                                                                    case 'confirmed': $statusText = 'Confirmed'; break;
                                                                    case 'in_progress': $statusText = 'In Progress'; break;
                                                                    case 'completed': $statusText = 'Completed'; break;
                                                                    case 'canceled': $statusText = 'Canceled'; break;
                                                                    default: $statusText = ucfirst($appointment['status']);
                                                                }
                                                                echo $statusText;
                                                            ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="action-buttons">
                                                            <a href="view_appointment.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn-view" title="View Details">
                                                                <i class="fa fa-eye"></i>
                                                            </a>
                                                            
                                                            <?php if (in_array($appointment['status'], ['pending', 'confirmed'])): ?>
                                                                <form method="post" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to cancel this appointment?');">
                                                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointment_id']; ?>">
                                                                    <button type="submit" name="cancel_appointment" class="btn-delete" title="Cancel Appointment">
                                                                        <i class="fa fa-times"></i>
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <!-- Book New Appointment Section -->
                    <section class="book-appointment-section">
                        <div class="section-heading">
                            <h2>Book New Appointment</h2>
                        </div>
                        
                        <div class="reservation-card">
                            <?php if (empty($vehicles)): ?>
                                <div class="no-vehicles-message">
                                    <p>You need to add a vehicle before booking an appointment.</p>
                                    <a href="profile.php" class="button">Add Vehicle</a>
                                </div>
                            <?php else: ?>
                                <form action="reservations.php" method="post" class="booking-form">
                                    <div class="row clearfix">
                                        <div class="col-2">
                                            <div class="form-group">
                                                <label for="service_id">Select Service</label>
                                                <select id="service_id" name="service_id" required>
                                                    <option value="">Select a service</option>
                                                    <?php foreach ($servicesByCategory as $category => $categoryServices): ?>
                                                        <optgroup label="<?php echo htmlspecialchars($category); ?>">
                                                            <?php foreach ($categoryServices as $service): ?>
                                                                <option value="<?php echo $service['service_id']; ?>">
                                                                    <?php echo htmlspecialchars($service['service_name'] . ' - ₱' . number_format($service['base_price'], 2)); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </optgroup>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="col-2">
                                            <div class="form-group">
                                                <label for="vehicle_id">Select Vehicle</label>
                                                <select id="vehicle_id" name="vehicle_id" required>
                                                    <option value="">Select a vehicle</option>
                                                    <?php foreach ($vehicles as $vehicle): ?>
                                                        <option value="<?php echo $vehicle['vehicle_id']; ?>">
                                                            <?php echo htmlspecialchars($vehicle['year'] . ' ' . $vehicle['make'] . ' ' . $vehicle['model']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row clearfix">
                                        <div class="col-2">
                                            <div class="form-group">
                                                <label for="appointment_date">Preferred Date</label>
                                                <input type="date" id="appointment_date" name="appointment_date" min="<?php echo date('Y-m-d'); ?>" required>
                                            </div>
                                        </div>
                                        
                                        <div class="col-2">
                                            <div class="form-group">
                                                <label for="appointment_time">Preferred Time</label>
                                                <select id="appointment_time" name="appointment_time" required>
                                                    <option value="">Select a time</option>
                                                    <option value="08:00:00">8:00 AM</option>
                                                    <option value="09:00:00">9:00 AM</option>
                                                    <option value="10:00:00">10:00 AM</option>
                                                    <option value="11:00:00">11:00 AM</option>
                                                    <option value="13:00:00">1:00 PM</option>
                                                    <option value="14:00:00">2:00 PM</option>
                                                    <option value="15:00:00">3:00 PM</option>
                                                    <option value="16:00:00">4:00 PM</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="notes">Additional Notes (Optional)</label>
                                        <textarea id="notes" name="notes" rows="3"></textarea>
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="submit" name="book_appointment" class="button">Book Appointment</button>
                                    </div>
                                </form>
                            <?php endif; ?>
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
                            </div>
                        </div>
                        
                        <div class="sidebar-section">
                            <h3>Quick Links</h3>
                            <ul class="sidebar-nav">
                                <li><a href="customer-dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a></li>
                                <li><a href="profile.php"><i class="fa fa-user"></i> My Profile</a></li>
                                <li><a href="reservations.php" class="active"><i class="fa fa-calendar"></i> My Reservations</a></li>
                                <li><a href="prizes.php"><i class="fa fa-gift"></i> Prizes & Offers</a></li>
                                <li><a href="service_history.php"><i class="fa fa-history"></i> Service History</a></li>
                            </ul>
                        </div>
                        
                        <div class="sidebar-section">
                            <h3>Reservation Tips</h3>
                            <div class="tip-item">
                                <i class="fa fa-clock-o"></i>
                                <p>Book at least 3 days in advance for the best availability</p>
                            </div>
                            <div class="tip-item">
                                <i class="fa fa-calendar-check-o"></i>
                                <p>You can reschedule appointments up to 24 hours before the scheduled time</p>
                            </div>
                            <div class="tip-item">
                                <i class="fa fa-info-circle"></i>
                                <p>For complex services like ECU Remapping or Engine Overhauls, please mention any specific requirements in the notes</p>
                            </div>
                        </div>
                        
                        <div class="sidebar-section">
                            <h3>Service Hours</h3>
                            <div class="business-hours">
                                <div class="day-hours">
                                    <span class="day">Monday - Friday:</span>
                                    <span class="hours">8:00 AM - 6:00 PM</span>
                                </div>
                                <div class="day-hours">
                                    <span class="day">Saturday:</span>
                                    <span class="hours">9:00 AM - 3:00 PM</span>
                                </div>
                                <div class="day-hours">
                                    <span class="day">Sunday:</span>
                                    <span class="hours">Closed</span>
                                </div>
                            </div>
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

<script>
    // Reservation tabs functionality
    document.addEventListener('DOMContentLoaded', function() {
        const tabButtons = document.querySelectorAll('.tab-button');
        const appointmentRows = document.querySelectorAll('#appointments-table tbody tr[data-status]');
        
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                tabButtons.forEach(btn => btn.classList.remove('active'));
                
                // Add active class to clicked button
                this.classList.add('active');
                
                const tabType = this.getAttribute('data-tab');
                
                // Show all rows if "all" tab, otherwise filter by status
                appointmentRows.forEach(row => {
                    const status = row.getAttribute('data-status');
                    
                    if (tabType === 'all') {
                        row.style.display = '';
                    } else if (tabType === 'upcoming' && (status === 'pending' || status === 'confirmed')) {
                        row.style.display = '';
                    } else if (tabType === 'completed' && status === 'completed') {
                        row.style.display = '';
                    } else if (tabType === 'canceled' && status === 'canceled') {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
                
                // Check if no rows are visible and show message if needed
                let visibleRows = false;
                appointmentRows.forEach(row => {
                    if (row.style.display !== 'none') {
                        visibleRows = true;
                    }
                });
                
                // Get or create the no-data row
                let noDataRow = document.querySelector('#appointments-table tbody .no-data-row');
                if (!noDataRow) {
                    noDataRow = document.createElement('tr');
                    noDataRow.className = 'no-data-row';
                    noDataRow.innerHTML = '<td colspan="6" class="text-center">No appointments found</td>';
                    document.querySelector('#appointments-table tbody').appendChild(noDataRow);
                }
                
                // Show or hide the no-data row
                if (!visibleRows && appointmentRows.length > 0) {
                    noDataRow.style.display = '';
                } else {
                    noDataRow.style.display = 'none';
                }
            });
        });
    });
</script>

</body>
</html> 