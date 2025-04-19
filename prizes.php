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

// Fetch active offers
$offers = [];
$sql = "SELECT * FROM offers 
        WHERE is_active = TRUE 
        AND NOW() BETWEEN start_date AND end_date 
        ORDER BY discount_value DESC";
$offers = fetchAll($sql);

// Fetch all services
$services = [];
$sql = "SELECT * FROM services WHERE is_active = TRUE ORDER BY category, service_name";
$services = fetchAll($sql);

// Group services by category
$servicesByCategory = [];
foreach ($services as $service) {
    if (!isset($servicesByCategory[$service['category']])) {
        $servicesByCategory[$service['category']] = [];
    }
    $servicesByCategory[$service['category']][] = $service;
}

// Sample motor parts data (would be from a database in a real application)
$motorParts = [
    [
        'name' => 'Performance Air Filter',
        'category' => 'Air Intake',
        'price' => 2500,
        'description' => 'High-flow air filter for increased engine performance and better fuel efficiency.',
        'image' => 'images/parts/air-filter.jpg'
    ],
    [
        'name' => 'Iridium Spark Plugs (Set of 4)',
        'category' => 'Ignition',
        'price' => 3200,
        'description' => 'Premium iridium spark plugs for better combustion, improved throttle response, and longer life.',
        'image' => 'images/parts/spark-plug.jpg'
    ],
    [
        'name' => 'Sport Exhaust System',
        'category' => 'Exhaust',
        'price' => 18000,
        'description' => 'Performance exhaust system with stainless steel construction for better flow and aggressive sound.',
        'image' => 'images/parts/exhaust.jpg'
    ],
    [
        'name' => 'Coilover Suspension Kit',
        'category' => 'Suspension',
        'price' => 25000,
        'description' => 'Adjustable coilover suspension kit for improved handling and lowered ride height.',
        'image' => 'images/parts/suspension.jpg'
    ],
    [
        'name' => 'Performance ECU Chip',
        'category' => 'Engine Management',
        'price' => 15000,
        'description' => 'Programmable ECU chip to optimize engine performance, torque, and horsepower.',
        'image' => 'images/parts/ecu-chip.jpg'
    ],
    [
        'name' => 'Carbon Fiber Hood',
        'category' => 'Exterior',
        'price' => 35000,
        'description' => 'Lightweight carbon fiber hood for weight reduction and improved aesthetics.',
        'image' => 'images/parts/carbon-hood.jpg'
    ],
    [
        'name' => 'Racing Brake Pads',
        'category' => 'Brakes',
        'price' => 5500,
        'description' => 'High-performance brake pads with improved stopping power and heat resistance.',
        'image' => 'images/parts/brake-pads.jpg'
    ],
    [
        'name' => 'Short Shifter Kit',
        'category' => 'Transmission',
        'price' => 7500,
        'description' => 'Precision short shifter kit for quicker, more precise gear changes.',
        'image' => 'images/parts/shifter.jpg'
    ]
];

// Group parts by category
$partsByCategory = [];
foreach ($motorParts as $part) {
    if (!isset($partsByCategory[$part['category']])) {
        $partsByCategory[$part['category']] = [];
    }
    $partsByCategory[$part['category']][] = $part;
}
?>

<!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="UTF-8">

    <!--Page Title-->
    <title>Prizes & Offers - TECH13</title>

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
    
    <style>
        /* Additional CSS for Prizes Page */
        .prize-tabs {
            display: flex;
            border-bottom: 1px solid #e9ecef;
            margin-bottom: 20px;
        }
        
        .prize-tabs .tab-button {
            padding: 12px 20px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-weight: 600;
            color: #7f8c8d;
            transition: all 0.2s;
        }
        
        .prize-tabs .tab-button.active {
            color: #3498db;
            border-bottom-color: #3498db;
        }
        
        .prize-tabs .tab-button:hover:not(.active) {
            color: #2c3e50;
            border-bottom-color: #bdc3c7;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .offer-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
            border-left: 5px solid #3498db;
            position: relative;
            overflow: hidden;
        }
        
        .offer-card.featured {
            border-left-color: #e74c3c;
        }
        
        .offer-card .offer-badge {
            position: absolute;
            top: 20px;
            right: -30px;
            background-color: #e74c3c;
            color: white;
            padding: 5px 30px;
            font-size: 12px;
            font-weight: 600;
            transform: rotate(45deg);
        }
        
        .offer-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .offer-title h3 {
            margin: 0;
            font-size: 20px;
            color: #2c3e50;
        }
        
        .offer-dates {
            font-size: 13px;
            color: #7f8c8d;
        }
        
        .offer-discount {
            font-size: 24px;
            font-weight: 700;
            color: #e74c3c;
            margin: 15px 0;
        }
        
        .offer-details {
            margin-bottom: 15px;
            color: #34495e;
        }
        
        .offer-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .promo-code {
            background-color: #f8f9fa;
            padding: 8px 15px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 16px;
            letter-spacing: 1px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        /* Service & Parts Cards */
        .service-grid, .parts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .service-card, .part-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .service-card:hover, .part-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
        }
        
        .service-image, .part-image {
            height: 180px;
            background-color: #f8f9fa;
            position: relative;
        }
        
        .service-image img, .part-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .service-category, .part-category {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(44, 62, 80, 0.8);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .service-content, .part-content {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .service-title, .part-title {
            margin: 0 0 10px;
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .service-description, .part-description {
            color: #7f8c8d;
            margin-bottom: 15px;
            flex-grow: 1;
        }
        
        .service-price, .part-price {
            font-size: 18px;
            font-weight: 700;
            color: #3498db;
            margin-bottom: 15px;
        }
        
        .service-card .button, .part-card .button {
            margin-top: auto;
            width: 100%;
            text-align: center;
        }
        
        .category-heading {
            margin: 30px 0 15px;
            font-size: 22px;
            color: #2c3e50;
            border-left: 4px solid #3498db;
            padding-left: 10px;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .service-grid, .parts-grid {
                grid-template-columns: 1fr;
            }
            
            .prize-tabs {
                overflow-x: auto;
                white-space: nowrap;
                -webkit-overflow-scrolling: touch;
                padding-bottom: 5px;
            }
            
            .prize-tabs .tab-button {
                padding: 12px 15px;
            }
        }
    </style>
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
                                <a href="reservations.php">Reservations</a>
                            </li>
                            <li>
                                <a href="prizes.php" class="active">Prizes</a>
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
                <!-- Main Content Section -->
                <div class="col-2-3">
                    <section class="prizes-section">
                        <div class="section-heading">
                            <h2>Prizes, Services & Parts</h2>
                            <p>Explore our premium services, high-quality parts, and special offers for TECH13 customers</p>
                        </div>
                        
                        <div class="prize-tabs">
                            <button class="tab-button active" data-tab="offers">Special Offers</button>
                            <button class="tab-button" data-tab="services">Services</button>
                            <button class="tab-button" data-tab="parts">Motor Parts</button>
                        </div>
                        
                        <!-- Special Offers Tab -->
                        <div id="offers-tab" class="tab-content active">
                            <?php if (empty($offers)): ?>
                                <div class="no-offers-message">
                                    <p>No special offers available at the moment. Check back soon!</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($offers as $index => $offer): ?>
                                    <div class="offer-card <?php echo $index === 0 ? 'featured' : ''; ?>">
                                        <?php if ($index === 0): ?>
                                            <div class="offer-badge">FEATURED</div>
                                        <?php endif; ?>
                                        
                                        <div class="offer-header">
                                            <div class="offer-title">
                                                <h3><?php echo htmlspecialchars($offer['title']); ?></h3>
                                            </div>
                                            <div class="offer-dates">
                                                Valid: <?php echo date('M d', strtotime($offer['start_date'])); ?> - <?php echo date('M d, Y', strtotime($offer['end_date'])); ?>
                                            </div>
                                        </div>
                                        
                                        <div class="offer-discount">
                                            <?php if ($offer['discount_type'] === 'percentage'): ?>
                                                <?php echo htmlspecialchars($offer['discount_value']); ?>% OFF
                                            <?php else: ?>
                                                ₱<?php echo number_format($offer['discount_value'], 2); ?> OFF
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="offer-details">
                                            <?php echo htmlspecialchars($offer['description']); ?>
                                        </div>
                                        
                                        <div class="offer-footer">
                                            <?php if (!empty($offer['promo_code'])): ?>
                                                <div class="promo-info">
                                                    <span>Use code:</span>
                                                    <span class="promo-code"><?php echo htmlspecialchars($offer['promo_code']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <a href="reservations.php" class="button">Book Now</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Services Tab -->
                        <div id="services-tab" class="tab-content">
                            <?php foreach ($servicesByCategory as $category => $categoryServices): ?>
                                <h3 class="category-heading"><?php echo htmlspecialchars($category); ?></h3>
                                
                                <div class="service-grid">
                                    <?php foreach ($categoryServices as $service): ?>
                                        <div class="service-card">
                                            <div class="service-image">
                                                <?php if ($service['image']): ?>
                                                    <img src="<?php echo htmlspecialchars($service['image']); ?>" alt="<?php echo htmlspecialchars($service['service_name']); ?>">
                                                <?php else: ?>
                                                    <img src="images/services/default-service.jpg" alt="<?php echo htmlspecialchars($service['service_name']); ?>">
                                                <?php endif; ?>
                                                <span class="service-category"><?php echo htmlspecialchars($service['category']); ?></span>
                                            </div>
                                            <div class="service-content">
                                                <h3 class="service-title"><?php echo htmlspecialchars($service['service_name']); ?></h3>
                                                <p class="service-description"><?php echo htmlspecialchars($service['description']); ?></p>
                                                <div class="service-price">₱<?php echo number_format($service['base_price'], 2); ?></div>
                                                <div class="service-duration">
                                                    <i class="fa fa-clock-o"></i> 
                                                    <?php 
                                                        $hours = floor($service['duration'] / 60);
                                                        $minutes = $service['duration'] % 60;
                                                        $durationText = '';
                                                        
                                                        if ($hours > 0) {
                                                            $durationText .= $hours . ' hour' . ($hours > 1 ? 's' : '');
                                                        }
                                                        
                                                        if ($minutes > 0) {
                                                            if ($hours > 0) $durationText .= ' ';
                                                            $durationText .= $minutes . ' min';
                                                        }
                                                        
                                                        echo $durationText;
                                                    ?>
                                                </div>
                                                <a href="reservations.php" class="button">Book Service</a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Motor Parts Tab -->
                        <div id="parts-tab" class="tab-content">
                            <?php foreach ($partsByCategory as $category => $categoryParts): ?>
                                <h3 class="category-heading"><?php echo htmlspecialchars($category); ?></h3>
                                
                                <div class="parts-grid">
                                    <?php foreach ($categoryParts as $part): ?>
                                        <div class="part-card">
                                            <div class="part-image">
                                                <?php if (isset($part['image'])): ?>
                                                    <img src="<?php echo htmlspecialchars($part['image']); ?>" alt="<?php echo htmlspecialchars($part['name']); ?>">
                                                <?php else: ?>
                                                    <img src="images/parts/default-part.jpg" alt="<?php echo htmlspecialchars($part['name']); ?>">
                                                <?php endif; ?>
                                                <span class="part-category"><?php echo htmlspecialchars($part['category']); ?></span>
                                            </div>
                                            <div class="part-content">
                                                <h3 class="part-title"><?php echo htmlspecialchars($part['name']); ?></h3>
                                                <p class="part-description"><?php echo htmlspecialchars($part['description']); ?></p>
                                                <div class="part-price">₱<?php echo number_format($part['price'], 2); ?></div>
                                                <a href="#" class="button">Inquire Now</a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
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
                                <li><a href="reservations.php"><i class="fa fa-calendar"></i> My Reservations</a></li>
                                <li><a href="prizes.php" class="active"><i class="fa fa-gift"></i> Prizes & Offers</a></li>
                                <li><a href="service_history.php"><i class="fa fa-history"></i> Service History</a></li>
                            </ul>
                        </div>
                        
                        <div class="sidebar-section">
                            <h3>Featured Promotion</h3>
                            <div class="promo-banner">
                                <h4>Summer Special Tune-Up</h4>
                                <p>Get your vehicle ready for summer with our comprehensive tune-up package.</p>
                                <ul>
                                    <li>ECU diagnostics & optimization</li>
                                    <li>Cooling system check</li>
                                    <li>Air conditioning service</li>
                                    <li>Complete fluid inspection</li>
                                </ul>
                                <div class="promo-price">
                                    <span class="original-price">₱12,000</span>
                                    <span class="sale-price">₱8,999</span>
                                </div>
                                <a href="reservations.php" class="button full-width">Book Now</a>
                            </div>
                        </div>
                        
                        <div class="sidebar-section">
                            <h3>Need Help?</h3>
                            <p>Have questions about our services or parts? Our team is here to help!</p>
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

<script>
    // Tab functionality
    document.addEventListener('DOMContentLoaded', function() {
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons and contents
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));
                
                // Add active class to clicked button
                this.classList.add('active');
                
                // Show corresponding content
                const tabId = this.getAttribute('data-tab') + '-tab';
                document.getElementById(tabId).classList.add('active');
            });
        });
    });
</script>

</body>
</html> 